<?php
// plugins/private_derivative_protection/serve_original.php
//
// Sert les fichiers originaux après vérification des droits.
// Images publiques : servies à tous.
// Images privées   : vérification session + forbidden_categories.
// PHP lit le fichier filesystem directement — bypasse Require all denied.

//error_reporting(E_ALL);
//ini_set('display_errors', 0);
//ini_set('log_errors', 1);
//ini_set('error_log', __DIR__ . '/pdp_debug.log');

chdir(realpath(__DIR__ . '/../..'));
define('PHPWG_ROOT_PATH', './');

error_log('PDP_ORIG --- ' . date('Y-m-d H:i:s') . ' id=' . ($_GET['id'] ?? '?'));

include(PHPWG_ROOT_PATH . 'include/common.inc.php');

error_log('PDP_ORIG user=' . ($user['username'] ?? 'n/a') . ' status=' . ($user['status'] ?? 'n/a'));

// ── Validation ────────────────────────────────────────────────────────────

$image_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($image_id <= 0)
{
  error_log('PDP_ORIG DENY 400 id invalide');
  header('HTTP/1.1 400 Bad Request');
  exit('Bad request');
}

// ── Chemin depuis la DB ───────────────────────────────────────────────────

global $prefixeTable;

$query = 'SELECT id, path, level FROM ' . $prefixeTable . 'images WHERE id = ' . $image_id . ' LIMIT 1;';
$row   = pwg_db_fetch_assoc(pwg_query($query));
error_log('PDP_ORIG image : ' . json_encode($row));

if (!$row)
{
  error_log('PDP_ORIG DENY 404 image introuvable');
  header('HTTP/1.1 404 Not Found');
  exit('Not found');
}

$rel_path = (substr($row['path'], 0, 2) === './') ? substr($row['path'], 2) : $row['path'];

// Garde-fou sur le chemin DB.
// On n'utilise PAS realpath() + comparaison de préfixe : galleries/ et upload/
// peuvent être des symlinks pointant hors de l'arborescence Piwigo.
// La sécurité est garantie par le fait que $rel_path vient de la DB
// via un $image_id intval() — jamais d'une entrée utilisateur directe.
if (!preg_match('#^(galleries|upload)/#', $rel_path) || strpos($rel_path, '..') !== false)
{
  error_log('PDP_ORIG DENY 403 chemin DB invalide : ' . $rel_path);
  header('HTTP/1.1 403 Forbidden');
  exit('Forbidden');
}

$file_path = PHPWG_ROOT_PATH . $rel_path;
error_log('PDP_ORIG file_path : ' . $file_path);

if (!is_file($file_path))
{
  error_log('PDP_ORIG DENY 404 fichier absent');
  header('HTTP/1.1 404 Not Found');
  exit('Not found');
}

// ── Vérification des droits ───────────────────────────────────────────────

$image_level = (int) $row['level'];
error_log('PDP_ORIG level image=' . $image_level . ' user=' . (int) $user['level']);

if ($image_level > (int) $user['level'])
{
  error_log('PDP_ORIG DENY 403 niveau insuffisant');
  header('HTTP/1.1 403 Forbidden');
  exit('Forbidden');
}

$query  = 'SELECT category_id FROM ' . $prefixeTable . 'image_category WHERE image_id = ' . $image_id . ';';
$result = pwg_query($query);

$image_categories = array();
while ($cat_row = pwg_db_fetch_assoc($result))
{
  $image_categories[] = intval($cat_row['category_id']);
}
error_log('PDP_ORIG categories : ' . json_encode($image_categories));

if (empty($image_categories))
{
  error_log('PDP_ORIG DENY 403 aucune catégorie');
  header('HTTP/1.1 403 Forbidden');
  exit('Forbidden');
}

$forbidden = array();
if (!empty($user['forbidden_categories']))
{
  $forbidden = array_map('intval', explode(',', $user['forbidden_categories']));
}
error_log('PDP_ORIG forbidden : ' . json_encode($forbidden));

$accessible = false;
foreach ($image_categories as $cat_id)
{
  if (!in_array($cat_id, $forbidden))
  {
    $accessible = true;
    break;
  }
}
error_log('PDP_ORIG accessible : ' . ($accessible ? 'OUI' : 'NON'));

if (!$accessible)
{
  error_log('PDP_ORIG DENY 403 accès refusé');
  header('HTTP/1.1 403 Forbidden');
  exit('Forbidden');
}

// ── Service du fichier ────────────────────────────────────────────────────

$ext = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
$mime_map = array(
  'jpg'  => 'image/jpeg',
  'jpeg' => 'image/jpeg',
  'png'  => 'image/png',
  'gif'  => 'image/gif',
  'webp' => 'image/webp',
  'tif'  => 'image/tiff',
  'tiff' => 'image/tiff',
  'mp4'  => 'video/mp4',
  'm4v'  => 'video/mp4',
  'webm' => 'video/webm',
  'ogv'  => 'video/ogg',
  'mov'  => 'video/quicktime',
  'avi'  => 'video/x-msvideo',
  'mkv'  => 'video/x-matroska',
);
$mime  = isset($mime_map[$ext]) ? $mime_map[$ext] : 'application/octet-stream';
$mtime = filemtime($file_path);
$fsize = filesize($file_path);

if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])
    && strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) == $mtime)
{
  header('HTTP/1.1 304 Not Modified');
  exit;
}

error_log('PDP_ORIG OK ' . basename($file_path) . ' (' . $fsize . ' o)');

header('Content-Type: ' . $mime);
header('Accept-Ranges: bytes');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $mtime) . ' GMT');
header('Cache-Control: private, max-age=3600');

// ── Support des requêtes Range (nécessaire pour le seek vidéo, VideoJS etc.) ──

$start = 0;
$end   = $fsize - 1;
$is_range = false;

if (isset($_SERVER['HTTP_RANGE']) && preg_match('/bytes=(\d*)-(\d*)/', $_SERVER['HTTP_RANGE'], $matches))
{
  $is_range = true;
  if ($matches[1] !== '')
  {
    $start = (int) $matches[1];
    $end   = ($matches[2] !== '') ? (int) $matches[2] : $fsize - 1;
  }
  elseif ($matches[2] !== '')
  {
    // suffix range : les N derniers octets
    $start = max(0, $fsize - (int) $matches[2]);
    $end   = $fsize - 1;
  }

  if ($start > $end || $end >= $fsize)
  {
    header('HTTP/1.1 416 Range Not Satisfiable');
    header('Content-Range: bytes */' . $fsize);
    exit;
  }
}

$length = $end - $start + 1;

if ($is_range)
{
  header('HTTP/1.1 206 Partial Content');
  header('Content-Range: bytes ' . $start . '-' . $end . '/' . $fsize);
}
else
{
  header('HTTP/1.1 200 OK');
}
header('Content-Length: ' . $length);

if ($_SERVER['REQUEST_METHOD'] === 'HEAD')
{
  exit;
}

$fp = fopen($file_path, 'rb');
fseek($fp, $start);
$remaining = $length;
$chunk = 8192;
while ($remaining > 0 && !feof($fp))
{
  $read = ($remaining > $chunk) ? $chunk : $remaining;
  echo fread($fp, $read);
  $remaining -= $read;
  flush();
}
fclose($fp);
exit;