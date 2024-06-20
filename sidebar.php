<?php

namespace Antevasin;

// the core ruko file template/sidebar.php will load this file and allow the plugin to create it's own sidebar menu
$is_plugin_sidebar = false; // setting to false to control override of sidebar 
if ( $app_user['group_id'] > 0 )
{
    foreach ( $this_plugin->get_modules() as $name => $module )
    {
        $module_file = $module['path'] . 'sidebar.php';
        if ( file_exists( $module_file ) )
        {   
            // the module sidebar file will need to set $is_plugin_sidebar = true if it wants to override the default sidebar menu
            require( $module_file );        
        } 
    }
}