<?php
// plugins/private_derivative_protection/serve_original.php
//
// Sert les fichiers originaux après vérification des droits.
// Images publiques : servies à tous.
// Images privées   : vérification session + forbidden_categories.
// PHP lit le fichier filesystem directement — bypasse Require all denied.

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/pdp_debug.log');

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

$query = 'SELECT id, path FROM ' . $prefixeTable . 'images WHERE id = ' . $image_id . ' LIMIT 1;';
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
header('Content-Length: ' . $fsize);
header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $mtime) . ' GMT');
header('Cache-Control: private, max-age=3600');
header('Connection: close');

$fp = fopen($file_path, 'rb');
fpassthru($fp);
fclose($fp);
exit;