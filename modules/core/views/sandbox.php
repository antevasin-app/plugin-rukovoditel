<?php

namespace Antevasin;

error_reporting( E_ALL );
ini_set( 'display_errors', 1 );
ini_set( 'display_startup_errors', 1 );
ini_set( 'error_reporting', E_ALL ); 

print_rr('this is the ' . PLUGIN_NAME . ' plugin sandbox file');

// PUT ALL CODE BELOW

$service = new \Antevasin\service();
// $service->add_data( array( 'entities_id' => 65, 'get_default' => true ) );
// $service->get_statuses();
// exit();

$service->recurring_jobs();
exit();

$service->add_data( array( 'entities_id' => 65 ) );
$items_list = array_keys( $service->get_statuses() );

exit();
print_rr($core);
$core->update_entities();
exit();

$service = new service();
$service->add_data( array( 'entities_id' => 63, 'field_id' => 1208 )  );
print_rr($service->get_data());
$service->set_user_id( 24 );    
$users = $service->get_companies_users();
print_rr($users);
exit();

?>
<link type="text/css" rel="stylesheet" href="https://dev.antevasin.app/testing/maps/interactive/style.css">
<div class="form-group form-group-557 form-group-fieldtype_input">
    <label class="col-md-3 control-label" for="fields_557">Address</label>
    <div class="col-md-9">	
        <div id="fields_557_rendered_value"><input name="fields[557]" id="fields_557" value="" type="text" class="form-control input-xlarge fieldtype_input field_557 autofocus"></div>
    </div>			
</div>
<div class="form-group form-group-558 form-group-fieldtype_input">
    <label class="col-md-3 control-label" for="fields_558">LAT</label>
    <div class="col-md-9">	
        <div id="fields_558_rendered_value"><input name="fields[558]" id="fields_5597" value="" type="text" class="form-control input-xlarge fieldtype_input field_557 autofocus"></div>
    </div>			
</div>
<div class="form-group form-group-559 form-group-fieldtype_input">
    <label class="col-md-3 control-label" for="fields_559">Long</label>
    <div class="col-md-9">	
        <div id="fields_559_rendered_value"><input name="fields[559]" id="fields_559" value="" type="text" class="form-control input-xlarge fieldtype_input field_557 autofocus"></div>
    </div>			
</div>
<div id="map"></div>

