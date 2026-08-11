<?php
// plugins/private_derivative_protection/maintain.inc.php

class private_derivative_protection_maintain extends PluginMaintain
{
  private $htaccess_content = "# private_derivative_protection\nRequire all denied\n";

function activate($plugin_version, &$errors = array())
{
  conf_update_param('original_url_protection', 'all');
  conf_update_param('derivative_url_style', 2);
  file_put_contents(PHPWG_ROOT_PATH . 'galleries/.htaccess', $this->htaccess_content);
  file_put_contents(PHPWG_ROOT_PATH . 'upload/.htaccess',   $this->htaccess_content);
  file_put_contents(PHPWG_ROOT_PATH . '_data/i/.htaccess',  $this->htaccess_content);
}

function deactivate()
{
  conf_update_param('original_url_protection', '');
  conf_delete_param('derivative_url_style');
  @unlink(PHPWG_ROOT_PATH . 'galleries/.htaccess');
  @unlink(PHPWG_ROOT_PATH . 'upload/.htaccess');
  @unlink(PHPWG_ROOT_PATH . '_data/i/.htaccess');
}

function uninstall()
{
  conf_update_param('original_url_protection', '');
  conf_delete_param('derivative_url_style');
  @unlink(PHPWG_ROOT_PATH . 'galleries/.htaccess');
  @unlink(PHPWG_ROOT_PATH . 'upload/.htaccess');
  @unlink(PHPWG_ROOT_PATH . '_data/i/.htaccess');
}
}