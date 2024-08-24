<?php

namespace Antevasin;

function get_plugin_modules( $path )
{
    $dirs = glob( $path . 'modules/*', GLOB_ONLYDIR );
    $modules = array();
    foreach ( $dirs as $dir )
    {
        $module_name = basename( $dir );
        $module_info = $module_info = json_decode( file_get_contents( $dir . '/module.json' ), true );
        $module_data = array(
            'name' => $module_name,
            'app_path' => PLUGIN_NAME . '/' . $module_name . '/',
            'path' => $dir . '/',
            'info' => $module_info,
        );
        $modules[$module_name] = $module_data;
    }    
    return $modules;
}
