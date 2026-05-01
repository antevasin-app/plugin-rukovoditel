<?php

namespace Antevasin;

// Get plugin name from directory
$plugin_name = basename( __DIR__ );

// Define plugin constants
if ( !defined( 'PLUGIN_NAME' ) ) define( 'PLUGIN_NAME', $plugin_name );
if ( !defined( 'PLUGIN_PATH' ) ) define( 'PLUGIN_PATH', 'plugins/' . $plugin_name . '/' );
if ( !defined( 'PLUGIN_MODULES_PATH' ) ) define( 'PLUGIN_MODULES_PATH', PLUGIN_PATH . 'modules/' );

// Load all plugin classes from includes/classes directory
$plugin_class_files = glob( PLUGIN_PATH . 'includes/classes/*.php' );
foreach ( $plugin_class_files as $class_file )
{
    $class_name = basename( $class_file, '.php' );   
    require_once( $class_file );
}
$this_plugin = new plugin();
global $this_plugin;

// Debug output (remove after testing)
// print_rr("Plugin '$plugin_name' loaded successfully");
// print_rr("Available instances: \$" . $plugin_name . " and module instances like \$core");




