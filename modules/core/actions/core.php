<?php

namespace Antevasin;

switch( $app_module_action )
{
    case 'your_action':
        // add your code here
        break;
    default:
        switch ( $_SERVER['REQUEST_METHOD'] )
        {
            case 'GET':
                $data = $_GET;
                break;
            case 'POST':
                $data = array_merge( $_GET, $_POST );
                break;
            default:
                break;
        }
        $core = new core();
        $core->set_data( $data );
        if ( method_exists( $core, $app_module_action ) )
        {
            $core->$app_module_action();  
        }
        else
        {
            echo '{"error":"the action you specified was not found"}';
        }
        break;
}