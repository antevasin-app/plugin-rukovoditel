<?php 

namespace Antevasin;

global $this_plugin;

// load module header header files
foreach ( $this_plugin->get_modules() as $name => $module )
{
    $module_file = $module['path'] . 'includes/header_dropdown_menu.php';
    if ( file_exists( $module_file ) )
    {   
        require( $module_file );        
    } 
}