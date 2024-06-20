<?php

namespace Antevasin;

// for adding code to the default public form view 

foreach ( $this_plugin->get_modules() as $name => $module )
{
    $module_file = $module['path'] . 'public_form_view.php';
    if ( file_exists( $module_file ) )
    {   
        // 
        require( $module_file );        
    } 
}