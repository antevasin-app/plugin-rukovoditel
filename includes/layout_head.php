<?php

namespace Antevasin;

global $this_plugin;

foreach ( $this_plugin->get_modules( false ) as $name => $module )
{
    // load module layout_head.php files if they exist
    if ( is_file( $layout_head_file = $module['path'] . 'includes/layout_head.php' ) )
    {          
        require $layout_head_file;
    }
    
    // load module .js.php files if they exist in the module components directory
    $module_head_js_file = $module['app_path'] . $name . '.js';
    if ( file_exists( $module['path'] . 'components/' . $name . '.js.php' ) )
    {    
        if ( component_path( $module_head_js_file ) )
        {    
            echo '<script>';
                require component_path( $module_head_js_file );
            echo '</script>';
        }
    }
}

// load plugin css file
echo '<style>';
    require PLUGIN_PATH . 'css/plugin.css';
echo '</style>';

// load plugin javascript file
echo '<script>';
    require PLUGIN_PATH . 'js/plugin.js.php';
echo '</script>';