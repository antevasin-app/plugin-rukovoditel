<?php

namespace Antevasin;

$plugin_name = basename( __DIR__ );
define( 'PLUGIN_NAME', $plugin_name );
define ( 'PLUGIN_PATH', 'plugins/' . $plugin_name . '/');
define ( 'PLUGIN_MODULES_PATH', 'plugins/' . $plugin_name . '/modules/' );
require "plugins/{$plugin_name}/includes/classes/plugin.php";
require "plugins/{$plugin_name}/includes/classes/menus.php";
require "plugins/{$plugin_name}/includes/classes/entities.php";
require "plugins/{$plugin_name}/includes/classes/interfaces.php";
$this_plugin = new plugin();
