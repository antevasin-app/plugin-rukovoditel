<?php

namespace Antevasin;

// for adding to $allowed_modules array via the plugin and its modules
// e.g. $allowed_modules[] = 'antevasin/core/my_public_module';

$plugin_name = basename( __DIR__ );
$modules = glob( 'plugins/' . $plugin_name . '/modules/*', GLOB_ONLYDIR );
$public_actions = array();
foreach ( $modules as $index => $module_path )
{
    $module = basename( $module_path );
    // print_rr("index is $index - module is $module - path is $module_path");
    $public_modules_file = $module_path . '/public_modules.php';
    if ( file_exists( $public_modules_file ) )
    {   
        require( $public_modules_file );  
        $layout_file = "plugins/$plugin_name/modules/$app_module/views/$app_action.php";
        if ( 
            file_exists( $layout_file ) &&
            $app_module == $module && 
            isset( $public_actions[$app_module] ) && 
            in_array( $app_action, $public_actions[$app_module] ) 
        )     
        {
            // print_rr("action exists - module is $module - plugin path is $app_plugin_path - app module is $app_module - action is $app_action - layout is $app_layout");
            $allowed_modules[] = "$plugin_name/$app_module/$app_action";
            $app_layout = $layout_file; 
            // print_rr($allowed_modules); print_rr($app_layout);
        }
    }  
}