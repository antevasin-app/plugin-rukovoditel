<?php

namespace Antevasin;

// error_reporting( E_ALL );
// ini_set( 'display_errors', 1 );
// ini_set( 'display_startup_errors', 1 );
// ini_set( 'error_reporting', E_ALL ); 

$plugin_name = basename( __DIR__ );
define( 'PLUGIN_NAME', $plugin_name );
define ( 'PLUGIN_PATH', 'plugins/' . $plugin_name . '/');
define ( 'PLUGIN_MODULES_PATH', 'plugins/' . $plugin_name . '/modules/' );
\plugins::include_part( 'functions' );
require "plugins/{$plugin_name}/includes/classes/plugin.php";
$this_plugin = new plugin();
