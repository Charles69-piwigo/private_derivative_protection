<?php
// plugins/private_derivative_protection/maintain.inc.php

class private_derivative_protection_maintain extends PluginMaintain
{
  private $htaccess_content = "# private_derivative_protection\nRequire all denied\n";

  function activate($plugin_version, &$errors = array())
  {
    // Protège les répertoires d'originaux contre l'accès HTTP direct.
    // PHP peut toujours lire les fichiers depuis le filesystem (serve_original.php).
    file_put_contents(PHPWG_ROOT_PATH . 'galleries/.htaccess', $this->htaccess_content);
    file_put_contents(PHPWG_ROOT_PATH . 'upload/.htaccess',   $this->htaccess_content);
  }

  function deactivate()
  {
    // Retire la protection pour ne pas bloquer les autres plugins
    // qui construisent des URLs directes vers les originaux.
    @unlink(PHPWG_ROOT_PATH . 'galleries/.htaccess');
    @unlink(PHPWG_ROOT_PATH . 'upload/.htaccess');
  }

  function uninstall()
  {
    @unlink(PHPWG_ROOT_PATH . 'galleries/.htaccess');
    @unlink(PHPWG_ROOT_PATH . 'upload/.htaccess');
  }
}