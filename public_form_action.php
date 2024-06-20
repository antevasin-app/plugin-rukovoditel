<?php

namespace Antevasin;

// for adding code to the default public form action 

foreach ( $this_plugin->get_modules() as $name => $module )
{
    $module_file = $module['path'] . 'public_form_action.php';
    if ( file_exists( $module_file ) )
    {   
        // 
        require( $module_file );        
    } 
}