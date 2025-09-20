<?php

namespace Antevasin;

$public_actions = array(
    'check_email',
    'run_process',
    'download_attachment',
);
if ( in_array( $app_module_action, $public_actions ) )
{
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
        exit(); 
    }
    else
    {
        die( '{"error":"the action you specified was not found in the core module","data":{"function":"' . $app_module_action . '"}}' );
    }
}
else
{
    die( '{"error":"access denied for the action you specified in core actions","data":{"function":"' . $app_module_action . '"}}' );
}