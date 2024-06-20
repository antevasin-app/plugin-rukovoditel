<?php

namespace Antevasin;

$core = new core();
if ( !$core->is_module_user() ) redirect_to('dashboard/access_forbidden');  

// $allowed_actions = array( $module_name );