<?php

namespace Antevasin;

\plugins::include_part( 'functions' );
$plugin_name = basename( __DIR__ );
define( 'PLUGIN_NAME', $plugin_name );
require( "plugins/{$plugin_name}/includes/classes/plugin.php" );
$antevasin = new plugin();
