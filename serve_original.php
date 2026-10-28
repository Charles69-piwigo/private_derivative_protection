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

include(PHPWG_ROOT_PATH . 'include/common.inc.php');

// ── Validation ────────────────────────────────────────────────────────────

$image_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($image_id <= 0)
{
  header('HTTP/1.1 400 Bad Request');
  exit('Bad request');
}

// ── Chemin depuis la DB ───────────────────────────────────────────────────

global $prefixeTable;

$query = 'SELECT id, path, level FROM ' . $prefixeTable . 'images WHERE id = ' . $image_id . ' LIMIT 1;';
$row   = pwg_db_fetch_assoc(pwg_query($query));

if (!$row)
{
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
  header('HTTP/1.1 403 Forbidden');
  exit('Forbidden');
}

$file_path = PHPWG_ROOT_PATH . $rel_path;

if (!is_file($file_path))
{
  header('HTTP/1.1 404 Not Found');
  exit('Not found');
}

// ── Vérification des droits ───────────────────────────────────────────────

$image_level = (int) $row['level'];

if ($image_level > (int) $user['level'])
{
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

if (empty($image_categories))
{
  header('HTTP/1.1 403 Forbidden');
  exit('Forbidden');
}

$forbidden = array();
if (!empty($user['forbidden_categories']))
{
  $forbidden = array_map('intval', explode(',', $user['forbidden_categories']));
}

$accessible = false;
foreach ($image_categories as $cat_id)
{
  if (!in_array($cat_id, $forbidden))
  {
    $accessible = true;
    break;
  }
}

if (!$accessible)
{
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
  'pdf'  => 'application/pdf',
);
$mime  = isset($mime_map[$ext]) ? $mime_map[$ext] : 'application/octet-stream';
$mtime = filemtime($file_path);
$fsize = filesize($file_path);

// ── CORS : le récepteur Chromecast (castor) télécharge les vidéos avec une requête CORS ──
// '*' sans credentials : les cookies de session ne sont jamais envoyés, les droits restent vérifiés plus haut.
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Range');
header('Access-Control-Expose-Headers: Content-Length, Content-Range, Accept-Ranges');
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS')
{
  header('HTTP/1.1 204 No Content');
  exit;
}

if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])
    && strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) == $mtime)
{
  header('HTTP/1.1 304 Not Modified');
  exit;
}

header('Content-Type: ' . $mime);
if ($ext === 'pdf')
{
  header('Content-Disposition: inline; filename="' . basename($file_path) . '"');
}
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
