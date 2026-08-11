<?php
$lang['Private Derivative Protection'] = 'Private Derivative Protection';
$lang['État']                          = 'Status';
$lang['Aide']                          = 'Help';
$lang['Serveur']                       = 'Server';
$lang['Serveur compatible (Apache)']   = 'Compatible server (Apache)';
$lang['Serveur Nginx détecté : les fichiers .htaccess sont ignorés, la protection pdp n\'est pas active.']
    = 'Nginx server detected: .htaccess files are ignored, pdp protection is not active.';
$lang['Protection des fichiers originaux'] = 'Original file protection';
$lang['Protection des dérivées']       = 'Derivative protection';
$lang['Actif']                         = 'Active';
$lang['Absent ou incorrect']           = 'Missing or incorrect';
$lang['Albums privés protégés']        = 'Protected private albums';
$lang['Aucun album privé, pdp n\'a rien à protéger.']
    = 'No private albums, pdp has nothing to protect.';
$lang['Gérer les plugins'] = 'Manage plugins';

$lang['pdp_nginx_global_warning']
    = 'Private Derivative Protection: Nginx server detected, file protection is inactive.'
    . ' Photos from private albums remain accessible via direct URL.'
    . ' Deactivate the plugin to dismiss this message.';

$lang['pdp_nginx_config_warning']
    = 'Nginx server detected: file protection is inactive.'
    . ' Photos from private albums remain accessible via direct URL despite the plugin.'
    . ' Deactivate the plugin to dismiss this message.';

// ── Help tab ─────────────────────────────────────────────────────────────

$lang['pdp_help_tagline']
    = 'Protects private Piwigo albums against direct URL access to their photos, bypassing Piwigo\'s usual permission checks.';

$lang['pdp_help_problem_title'] = 'The problem pdp solves';

$lang['pdp_help_problem_p1']
    = 'Piwigo checks access rights when you <strong>browse</strong> the site normally: a guest doesn\'t see private albums in listings, thumbnails, or full-size photos.';

$lang['pdp_help_problem_p2']
    = 'But this control applies to browsing, not to the file itself. Piwigo generates and caches derivative files (thumbnails, display sizes) on disk, at a predictable location. If someone knows or guesses the direct URL of one of these files, they can access it — even if the photo belongs to a private album — without ever going through a Piwigo page that would have checked their rights. The same applies to original files (full resolution).';

$lang['pdp_help_problem_p3']
    = 'This isn\'t a Piwigo bug: it\'s a known architectural limitation, documented by the project itself (see related tickets on the forum and GitHub). pdp closes this gap.';

$lang['pdp_help_protects_title'] = 'What pdp protects, and how';

$lang['pdp_help_derivatives_title'] = 'Derivatives (thumbnails and display sizes)';

$lang['pdp_help_derivatives_p1']
    = 'For any photo belonging to a private album (or with a high access level), pdp replaces the usual links to the cached file with a signed URL (HMAC token, limited lifetime — 3 hours by default, configurable). This URL goes through a script (<code>protect.php</code>) that re-checks permissions before serving the image.';

$lang['pdp_help_derivatives_p2']
    = 'So that this protection can\'t be bypassed by directly accessing the cached file, pdp also forces <code>$conf[\'derivative_url_style\'] = 2</code>: <strong>all</strong> thumbnails, including those from public albums, now go through a PHP script instead of a direct link to the file. The derivative cache folder (<code>_data/i/</code>) is then itself protected by a <code>.htaccess</code>, without breaking anything on public albums since no page points directly to it anymore.';

$lang['pdp_help_derivatives_p3']
    = 'Tests carried out on typically-sized public albums (up to ~250 photos), including under light concurrent load, showed no measurable performance impact compared to Piwigo\'s usual direct display mode.';

$lang['pdp_help_originals_title'] = 'Originals (full resolution)';

$lang['pdp_help_originals_intro'] = 'Two cases are covered, for images as well as videos and PDF documents:';

$lang['pdp_help_originals_list']
    = '<li><strong>Piwigo\'s native path</strong> (size bar, download): pdp relies on the core\'s native mechanism (<code>action.php</code>) when it\'s sufficient, and only rewrites to its own script (<code>serve_original.php</code>) when an extra check is needed (private album, or high access level in an otherwise public album).</li>'
    . '<li><strong>URLs built directly by certain third-party plugins</strong> (for example <code>super_zoom</code>), which bypass Piwigo\'s native URL generation mechanism. A <code>.htaccess</code> blocks direct access to the file, and a dedicated hook routes these cases to <code>serve_original.php</code>, which re-checks permissions before serving the file.</li>';

$lang['pdp_help_originals_p2']
    = '<code>serve_original.php</code> serves images as well as videos (with support for <code>Range</code> requests, needed for the video player\'s seek bar) and PDF documents displayed full-page.';

$lang['pdp_help_blocking_title'] = 'Blocking direct file access';

$lang['pdp_help_blocking_p']
    = 'In addition, pdp installs <code>.htaccess</code> rules (<code>Require all denied</code>) on the folders containing original files, files being imported, and the derivative cache (<code>galleries/</code>, <code>upload/</code>, <code>_data/i/</code>), to prevent any direct access that would bypass the mechanisms above.';

$lang['pdp_help_compat_title'] = 'Compatibility';

$lang['pdp_help_compat_intro'] = 'Tested and confirmed compatible with:';

$lang['pdp_help_compat_list']
    = '<li><code>question_mark_in_urls = false</code></li>'
    . '<li><code>category_url_style = id-name</code></li>'
    . '<li><code>picture_url_style = file</code></li>'
    . '<li>The ShareAlbum plugin (share links <code>?xauth=</code>)</li>'
    . '<li>The <code>super_zoom</code>, <code>castor</code>, <code>face_tag_editor</code>, <code>geo_tag_editor</code> plugins (patched versions, available on the official Piwigo plugin repository)</li>';

$lang['pdp_help_compat_other']
    = 'If you use another plugin that builds its own URLs to original or derivative files (rather than using Piwigo\'s standard URL generation mechanisms), it might not be protected automatically — please report it if you come across one.';

$lang['pdp_help_install_title'] = 'Installation';

$lang['pdp_help_install_intro'] = 'Activating the plugin automatically sets up:';

$lang['pdp_help_install_list']
    = '<li>the necessary <code>.htaccess</code> rules (<code>galleries/</code>, <code>upload/</code>, <code>_data/i/</code>)</li>'
    . '<li>the <code>original_url_protection = \'all\'</code> setting</li>'
    . '<li>the <code>derivative_url_style = 2</code> setting</li>';

$lang['pdp_help_install_restore']
    = 'Deactivating or uninstalling cleanly removes these settings, and restores Piwigo\'s usual direct thumbnail display.';

$lang['pdp_help_install_prereq']
    = '<strong>Server requirement:</strong> Apache 2.4 with <code>AllowOverride</code> allowing <code>.htaccess</code> files on the folders concerned. <code>mod_rewrite</code> is not required.';

$lang['pdp_help_limitations_title'] = 'Known limitations';

$lang['pdp_help_limitations_intro']
    = 'This plugin closes a real security gap. The limitation below doesn\'t exist in its default operation, but is worth understanding to grasp what the automatic <code>derivative_url_style = 2</code> setting actually achieves.';

$lang['pdp_help_cache_title'] = 'Why the derivative cache folder is entirely blocked';

$lang['pdp_help_cache_p1']
    = 'Piwigo only generates a limited number of size formats per photo (thumbnail, medium, large, extra-large, formats specific to certain display plugins). As soon as <strong>a single one</strong> of these formats has been generated for a given photo — whatever the reason (preview at import time, viewing through the admin interface, display in an album, etc.) — its path on disk becomes predictable.';

$lang['pdp_help_cache_p2']
    = 'Without intervention, such a path would remain accessible via direct access if known or guessed, even if no page on the site discloses it anymore. It\'s precisely to close this case that pdp doesn\'t just hide the links: it forces <code>derivative_url_style = 2</code> so that no thumbnail, public or private, is ever served via direct file access anymore — which allows the entire <code>_data/i/</code> folder to be blocked via <code>.htaccess</code> without breaking public album display.';

$lang['pdp_help_nginx_title'] = 'Behavior under nginx';

$lang['pdp_help_nginx_p1']
    = 'Under nginx, <code>.htaccess</code> files aren\'t read — that\'s a characteristic of this server, not a limitation of pdp. Direct file access blocking therefore doesn\'t work automatically in this configuration: without Apache to enforce a <code>.htaccess</code>, this protection layer is simply inoperative.';

$lang['pdp_help_nginx_p2']
    = 'What still works regardless: the PHP-side mechanisms (the core\'s <code>action.php</code>, pdp\'s <code>serve_original.php</code> and <code>protect.php</code>) stay active no matter the server, since they don\'t depend on <code>.htaccess</code>. What disappears under nginx is only the additional direct-file-blocking layer.';

$lang['pdp_help_nginx_p3']
    = 'pdp automatically detects the environment (pure Apache, pure nginx, or a hybrid Apache + nginx proxy setup) and displays the result of this detection on its configuration page in the admin, with a warning banner if nginx is identified.';
