<?php
// plugins/private_derivative_protection/protect.php

/*
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
$_pdp_log = __DIR__ . '/pdp_debug.log';
ini_set('error_log', $_pdp_log);
unset($_pdp_log);
*/

define('PDP_TOKEN_TTL', 6*3600); // doit rester cohérent avec la valeur dans main.inc.php

chdir(realpath(__DIR__ . '/../..'));
define('PHPWG_ROOT_PATH', './');

include(PHPWG_ROOT_PATH . 'include/config_default.inc.php');
@include(PHPWG_ROOT_PATH . 'local/config/config.inc.php');

defined('PWG_LOCAL_DIR') or define('PWG_LOCAL_DIR', 'local/');
defined('PWG_DERIVATIVE_DIR') or define('PWG_DERIVATIVE_DIR', $conf['data_location'].'i/');

@include(PHPWG_ROOT_PATH.PWG_LOCAL_DIR.'config/database.inc.php');
include_once(PHPWG_ROOT_PATH.'include/dblayer/functions_'.$conf['dblayer'].'.inc.php');

pwg_db_connect($conf['db_host'], $conf['db_user'], $conf['db_password'], $conf['db_base']);
$query = 'SELECT value FROM '.$prefixeTable.'config WHERE param=\'secret_key\';';
$result = pwg_query($query);
list($conf['secret_key']) = pwg_db_fetch_row($result);
pwg_db_close();

function pdp_deny($code, $msg = '')
{
  error_log('PDP DENY '.$code.' '.$msg);
  http_response_code($code);
  if ($msg) echo $msg;
  exit;
}

// on capture l'URL d'appel d'ORIGINE (vers protect.php), avant toute modification de $_SERVER
$pdp_self_path = strtok($_SERVER['REQUEST_URI'], '?');

// --- validation du jeton ---
$p = isset($_GET['p']) ? $_GET['p'] : '';
$e = isset($_GET['e']) ? (int)$_GET['e'] : 0;
$t = isset($_GET['t']) ? $_GET['t'] : '';

if (!$p || !$e || !$t) pdp_deny(400);
if (time() > $e) pdp_deny(403, 'Link expired');

$expected_sig = hash_hmac('sha256', $p.'-'.$e, $conf['secret_key']);
if (!hash_equals($expected_sig, $t)) pdp_deny(403, 'Invalid token');

// --- requête synthétique attendue par i.php ---
$encoded_parts = array_map('rawurlencode', explode('/', $p));
$_SERVER['QUERY_STRING'] = '/'.implode('/', $encoded_parts);
$_SERVER['REQUEST_URI']  = '/i.php?'.$_SERVER['QUERY_STRING'];
unset($_SERVER['PATH_INFO']);
unset($_GET['p'], $_GET['e'], $_GET['t']);
// IMPORTANT : on NE touche PAS à $_GET['ajaxload']. i.php doit pouvoir utiliser
// son mécanisme JSON natif, sinon le JS de Piwigo plante et affiche l'image cassée.
// On intercepte sa réponse ci-dessous pour ne jamais laisser fuiter le chemin réel.

if (isset($_GET['ajaxload']) && $_GET['ajaxload'] == 'true')
{
  ob_start(function($buffer) use ($p, $conf, $pdp_self_path)
  {
    $decoded = json_decode($buffer, true);
    if (is_array($decoded) && isset($decoded['url']))
    {
      $new_exp = time() + PDP_TOKEN_TTL;
      $new_sig = hash_hmac('sha256', $p.'-'.$new_exp, $conf['secret_key']);
      $decoded['url'] = $pdp_self_path
        .'?p='.rawurlencode($p)
        .'&e='.$new_exp
        .'&t='.$new_sig;
      return json_encode($decoded);
    }
    return $buffer;
  });
}

error_log('PDP DELEGATING TO i.php : '.$_SERVER['QUERY_STRING']);

include(PHPWG_ROOT_PATH.'i.php');