<?php
defined('PHPWG_ROOT_PATH') or die('Hacking attempt!');

// ── Détection du serveur ──────────────────────────────────────────────────────
$server_is_nginx = stripos($_SERVER['SERVER_SOFTWARE'] ?? '', 'nginx') !== false
               && stripos($_SERVER['SERVER_SOFTWARE'] ?? '', 'apache') === false;

// ── Vérification des .htaccess ────────────────────────────────────────────────
$htaccess_galleries_ok = false;
$htaccess_upload_ok    = false;

$galleries_htaccess = PHPWG_ROOT_PATH . 'galleries/.htaccess';
$upload_htaccess    = PHPWG_ROOT_PATH . 'upload/.htaccess';

if (file_exists($galleries_htaccess)) {
    $content = file_get_contents($galleries_htaccess);
    $htaccess_galleries_ok = (strpos($content, 'Require all denied') !== false);
}
if (file_exists($upload_htaccess)) {
    $content = file_get_contents($upload_htaccess);
    $htaccess_upload_ok = (strpos($content, 'Require all denied') !== false);
}

// ── Nombre d'albums privés ────────────────────────────────────────────────────
$result = pwg_query('SELECT COUNT(*) AS nb FROM ' . CATEGORIES_TABLE . ' WHERE status = \'private\'');
$row = pwg_db_fetch_assoc($result);
$private_albums_count = (int)$row['nb'];

// ── Rendu via template Piwigo ─────────────────────────────────────────────────
$tab     = isset($_GET['tab']) && $_GET['tab'] === 'help' ? 'help' : 'status';
$tab_tpl = PDP_PATH . 'template/' . $tab . '.tpl';
$base_url = get_root_url() . 'admin.php?page=plugin-private_derivative_protection';

$template->assign([
    'SERVER_IS_NGINX'       => $server_is_nginx,
    'HTACCESS_GALLERIES_OK' => $htaccess_galleries_ok,
    'HTACCESS_UPLOAD_OK'    => $htaccess_upload_ok,
    'PRIVATE_ALBUMS_COUNT'  => $private_albums_count,
    'TAB'                   => $tab,
    'BASE_URL'              => $base_url,
    'PLUGINS_URL'           => get_root_url() . 'admin.php?page=plugins',
]);

$template->set_filename('pdp_tab', $tab_tpl);
$template->assign_var_from_handle('TAB_CONTENT', 'pdp_tab');

$template->set_filename('pdp_admin', PDP_PATH . 'template/admin.tpl');
$template->assign_var_from_handle('ADMIN_CONTENT', 'pdp_admin');
