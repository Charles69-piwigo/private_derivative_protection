<?php
// plugins/private_derivative_protection/protect.php

define('PHPWG_ROOT_PATH', '../../');

// Debug — décommenter pour activer les logs =============================
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
$_pdp_log = PHPWG_ROOT_PATH . 'plugins/private_derivative_protection/pdp_debug.log';
ini_set('error_log', $_pdp_log);
unset($_pdp_log);
// =========================================================================

include(PHPWG_ROOT_PATH . 'include/config_default.inc.php');
@include(PHPWG_ROOT_PATH . 'local/config/config.inc.php');

defined('PWG_LOCAL_DIR') or define('PWG_LOCAL_DIR', 'local/');
defined('PWG_DERIVATIVE_DIR') or define('PWG_DERIVATIVE_DIR', $conf['data_location'].'i/');

@include(PHPWG_ROOT_PATH.PWG_LOCAL_DIR.'config/database.inc.php');
include(PHPWG_ROOT_PATH.'include/dblayer/functions_'.$conf['dblayer'].'.inc.php');

// --- connexion DB et chargement de secret_key, avant toute validation ---
pwg_db_connect($conf['db_host'], $conf['db_user'], $conf['db_password'], $conf['db_base']);

$query = 'SELECT value FROM '.$prefixeTable.'config WHERE param=\'secret_key\';';
$result = pwg_query($query);
list($conf['secret_key']) = pwg_db_fetch_row($result);

function pdp_deny($code, $msg = '')
{
  error_log('PDP DENY '.$code.' '.$msg);
  http_response_code($code);
  if ($msg) echo $msg;
  exit;
}

// --- 1. validation du jeton ---
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$s  = isset($_GET['s'])  ? preg_replace('/[^a-z0-9]/', '', $_GET['s']) : '';
$e  = isset($_GET['e'])  ? (int)$_GET['e'] : 0;
$t  = isset($_GET['t'])  ? $_GET['t'] : '';

if (!$id || !$s || !$e || !$t) pdp_deny(400);
if (time() > $e) pdp_deny(403, 'Link expired');

$expected_sig = hash_hmac('sha256', $id.'-'.$s.'-'.$e, $conf['secret_key']);
if (!hash_equals($expected_sig, $t)) pdp_deny(403, 'Invalid token');

// --- 2. retrouver le chemin de la photo (la connexion DB est déjà ouverte) ---
$query = 'SELECT path FROM '.$prefixeTable.'images WHERE id = '.$id.';';
$result = pwg_query($query);
$row = pwg_db_fetch_assoc($result);
if (!$row) pdp_deny(404);

$loc = $row['path'];
if (substr($loc, 0, 2) === './')  $loc = substr($loc, 2);
elseif (substr($loc, 0, 3) === '../') $loc = substr($loc, 3);

$dot_pos = strrpos($loc, '.');
$deriv_loc = substr_replace($loc, '-'.$s, $dot_pos, 0);
$derivative_path = PHPWG_ROOT_PATH.PWG_DERIVATIVE_DIR.$deriv_loc;

// --- 3. générer si absent, via appel interne à i.php ---
if (!is_file($derivative_path))
{
  $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
  $host   = $_SERVER['SERVER_NAME'];
  $internal_url = $scheme.'://127.0.0.1'.dirname(dirname($_SERVER['SCRIPT_NAME'].'/..')).'/../i.php?/'.$deriv_loc;
  // chemin volontairement explicite ci-dessous, voir remarque en dessous du code

  $ch = curl_init($internal_url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_HTTPHEADER, array('Host: '.$host));
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
  curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
  curl_setopt($ch, CURLOPT_TIMEOUT, 15);
  curl_exec($ch);

  if (curl_errno($ch))
  {
    error_log('PDP CURL ERROR: '.curl_error($ch).' (errno='.curl_errno($ch).') | url='.$internal_url);
  }
  else
  {
    error_log('PDP CURL HTTP CODE: '.curl_getinfo($ch, CURLINFO_HTTP_CODE).' | url='.$internal_url);
  }
  curl_close($ch);

  clearstatcache(true, $derivative_path);
  if (!is_file($derivative_path)) pdp_deny(500, 'Generation failed');
}

// --- 4. servir le fichier ---
$ext = strtolower(substr($derivative_path, strrpos($derivative_path, '.')));
$ctype = 'application/octet-stream';
switch ($ext)
{
  case '.jpg': case '.jpeg': case '.jpe': $ctype = 'image/jpeg'; break;
  case '.png': $ctype = 'image/png'; break;
  case '.gif': $ctype = 'image/gif'; break;
  case '.webp': $ctype = 'image/webp'; break;
}
header('Content-Type: '.$ctype);
header('Cache-Control: private, max-age=3600');
readfile($derivative_path);
?>