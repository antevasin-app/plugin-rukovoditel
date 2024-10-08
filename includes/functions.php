<?php

namespace Antevasin;

// print_rr('plugin functions');
function get_plugin_modules()
{
    $dirs = glob( PLUGIN_PATH . 'modules/*', GLOB_ONLYDIR );
    $modules = array();
    foreach ( $dirs as $dir )
    {
        $module_name = basename( $dir );
        $module_info = $module_info = json_decode( file_get_contents( $dir . '/module.json' ), true );
        // $config = json_decode( constant( 'CFG_MODULE_' . strtoupper( $module_name ) . '_CONFIG' ), true );
        // $config = ''; // json_decode( constant( 'CFG_MODULE_' . strtoupper( $module_name ) . '_CONFIG' ), true );
        $module_data = array(
            'name' => $module_name,
            'app_path' => PLUGIN_NAME . '/' . $module_name . '/',
            'path' => $dir . '/',
            'info' => $module_info,
        );
        if ( defined( 'CFG_MODULE_' . strtoupper( $module_name ) . '_CONFIG' ) )
        {
            $config = json_decode( constant( 'CFG_MODULE_' . strtoupper( $module_name ) . '_CONFIG' ), true );
            $module_data['config'] = $config;
        }
        $modules[$module_name] = $module_data;
    }    
    return $modules;
}
