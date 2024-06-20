<?php

namespace Antevasin;

error_reporting( E_ALL );
ini_set( 'display_errors', 1 );
ini_set( 'display_startup_errors', 1 );
ini_set( 'error_reporting', E_ALL ); 

print_rr('this is the ' . PLUGIN_NAME . ' plugin sandbox file');

// PUT ALL CODE BELOW

$antevasin = new antevasin();
$antevasin->get_countries();

exit();

print_rr("plugin name that has been defined is " . PLUGIN_NAME);
print_rr($antevasin);
print_rr($test);
print_rr(TEXT_MODULE_CORE_INDEX_TITLE);

echo '<p class="antevasin-test">This is my red text</p>';

$date = new \DateTime( date( CFG_APP_DATETIME_FORMAT, 1714944117 ) );
print_rr($date);
$week = $date->format( 'W' );
print_rr("week number using datetime is $week");

$output_value = date("W", 1716444000 );
print_rr("week number using date is $output_value");