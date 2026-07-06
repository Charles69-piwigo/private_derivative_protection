<?php
/*
Plugin Name: Private Derivative Protection
Version: 1.1
Description: Protège par jeton signé les vignettes des photos appartenant uniquement à des albums privés
Author: Charles69
Has Settings: webmaster
*/

// ================================================
/*
version 1.1 - 06/07/2026
    modifications suite retour team piwigo

version 1.0 - 22/06/2026
    traite les originaux et les dérivés
    la ré-écriture des urls doit être désactivée


*/
//=================================================

if (!defined('PHPWG_ROOT_PATH')) die('Hacking attempt!');

define('PDP_PATH',  PHPWG_PLUGINS_PATH . 'private_derivative_protection/');
define('PDP_ADMIN', get_root_url() . 'admin.php?page=plugin-private_derivative_protection');

// Charger d'abord l'anglais comme base, puis écraser avec la langue de l'utilisateur
load_language('plugin.lang', PDP_PATH, array('language' => 'en_UK', 'no_fallback' => true));
load_language('plugin.lang', PDP_PATH);

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




define('PDP_TOKEN_TTL', 3*3600); // durée du token

add_event_handler('loc_begin_page_header', 'pdp_nginx_warning');
function pdp_nginx_warning()
{
    global $page;
    if (!defined('IN_ADMIN') || !IN_ADMIN) return;
    $server_is_nginx = stripos($_SERVER['SERVER_SOFTWARE'] ?? '', 'nginx') !== false
                    && stripos($_SERVER['SERVER_SOFTWARE'] ?? '', 'apache') === false;
    //$server_is_nginx = true; // ← TEST TEMPORAIRE, à retirer après vérification
    if (!$server_is_nginx) return;
    $page['warnings'][] = l10n('pdp_nginx_global_warning');
}




// Niveau de confidentialité de l'invité — récupéré dynamiquement (jamais codé en dur),
// car un webmaster peut relever le niveau par défaut du guest_id.
function pdp_get_guest_level()
{
  global $conf;
  static $level = null;
  if ($level === null)
  {
    $query = 'SELECT level FROM '.USER_INFOS_TABLE.' WHERE user_id = '.(int)$conf['guest_id'].';';
    $row = pwg_db_fetch_assoc(pwg_query($query));
    $level = isset($row['level']) ? (int)$row['level'] : 0;
  }
  return $level;
}

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

  // Niveau de confidentialité de l'image : à protéger même dans un album public
  // si un invité n'a pas le niveau requis pour la voir.
  $query = 'SELECT level FROM '.$prefixeTable.'images WHERE id = '.(int)$image_id.';';
  $row = pwg_db_fetch_assoc(pwg_query($query));
  $image_level = isset($row['level']) ? (int)$row['level'] : 0;

  if ($image_level > pdp_get_guest_level())
  {
    return $cache[$image_id] = true;
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


// ── Protection des originaux via hook get_src_image_url ───────────────────
//
// C'est le point d'accroche utilisé par picture.php (SrcImage::get_url()),
// déjà intercepté par le core lui-même (get_src_image_url_protection_handler,
// common.inc.php) dès que original_url_protection est actif : il redirige
// vers action.php, qui ne vérifie pas images.level (voir pdp_image_is_private).
// On s'accroche avec une priorité > NEUTRAL pour s'exécuter APRÈS ce handler
// du core et pouvoir remplacer son URL par serve_original.php quand la
// protection (album privé ou niveau) l'exige ; sinon on laisse passer l'URL
// action.php.

add_event_handler('get_src_image_url', 'pdp_get_src_image_url', EVENT_HANDLER_PRIORITY_NEUTRAL + 10, null, 2);

function pdp_get_src_image_url($url, $src_image)
{
  if (!pdp_image_is_private($src_image->id))
  {
    return $url;
  }

  return get_root_url()
    . 'plugins/private_derivative_protection/serve_original.php'
    . '?id=' . $src_image->id;
}

// ── Protection des originaux via hook get_original_url (super_zoom) ───────
//
// 'get_original_url' n'est PAS un événement du core Piwigo : c'est un contrat
// privé déclenché par le plugin super_zoom lui-même (main.inc.php:195,
// "trigger_change permet à d'autres plugins d'intercepter l'URL"), avec en
// entrée une URL brute pointant directement sous galleries/ — bloquée par le
// .htaccess Require all denied. On réécrit donc TOUJOURS vers
// serve_original.php (publique et privée), qui gère elle-même la vérification
// des droits (catégories + niveau) et sert le fichier en direct.

add_event_handler('get_original_url', 'pdp_get_original_url', EVENT_HANDLER_PRIORITY_NEUTRAL, null, 2);

function pdp_get_original_url($url, $src_image)
{
  return get_root_url()
    . 'plugins/private_derivative_protection/serve_original.php'
    . '?id=' . $src_image->id;
}

?>