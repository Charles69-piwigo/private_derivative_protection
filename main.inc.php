<?php
/*
Plugin Name: Private Derivative Protection
Version: 1.0
Description: Protège par jeton signé les vignettes des photos appartenant uniquement à des albums privés
Author: Charles69
*/

// ================================================
/*
version 1.0 - 22/06/2026


*/
//=================================================

if (!defined('PHPWG_ROOT_PATH')) die('Hacking attempt!');


// Debug — décommenter pour activer les logs =============================
/*
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
$_pdp_log = PHPWG_ROOT_PATH . 'plugins/private_derivative_protection/pdp_debug.log';
if (file_exists($_pdp_log) && filesize($_pdp_log) > 256 * 1024) {
    file_put_contents($_pdp_log, '');
}
ini_set('error_log', $_pdp_log);
unset($_pdp_log);
*/
// =========================================================================

global $conf;

if (empty($conf['question_mark_in_urls']))
{
  error_log('[private_derivative_protection] Plugin désactivé...');
  return;
}


define('PDP_TOKEN_TTL', 3*3600); // durée du token

function plugin_activate() {}
function plugin_deactivate() {}
function plugin_uninstall() {}



add_event_handler('get_derivative_url', 'pdp_get_derivative_url', EVENT_HANDLER_PRIORITY_NEUTRAL, null, 4);

function pdp_get_derivative_url($url, $params, $src_image, $rel_url)
{
  if (!pdp_image_is_private($src_image->id))
  {
    return $url;
  }

  global $conf;

  $tokens = array();
  $tokens[] = substr($params->type, 0, 2);
  if ($params->type == IMG_CUSTOM)
  {
    $params->add_url_tokens($tokens);
  }
  $size_token = implode('_', $tokens);

  $loc = $src_image->rel_path;
  if (substr($loc, 0, 2) === './')  $loc = substr($loc, 2);
  elseif (substr($loc, 0, 3) === '../') $loc = substr($loc, 3);
  $dot_pos = strrpos($loc, '.');
  $deriv_loc = substr_replace($loc, '-'.$size_token, $dot_pos, 0);

  $exp = time() + PDP_TOKEN_TTL;
  $payload = $deriv_loc.'-'.$exp;
  $sig = hash_hmac('sha256', $payload, $conf['secret_key']);

  return get_root_url().'plugins/private_derivative_protection/protect.php'
    .'?p='.rawurlencode($deriv_loc)
    .'&e='.$exp
    .'&t='.$sig;
}

function pdp_image_is_private($image_id)
{
  global $prefixeTable;
  static $cache = array();

  if (isset($cache[$image_id]))
  {
    return $cache[$image_id];
  }

  $query = '
SELECT status
  FROM '.$prefixeTable.'categories c
  INNER JOIN '.$prefixeTable.'image_category ic ON ic.category_id = c.id
  WHERE ic.image_id = '.(int)$image_id.'
;';
  $result = pwg_query($query);
  $is_private = true;
  while ($row = pwg_db_fetch_assoc($result))
  {
    if ($row['status'] == 'public')
    {
      $is_private = false;
      break;
    }
  }
  return $cache[$image_id] = $is_private;
}
?>