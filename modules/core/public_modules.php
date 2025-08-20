<?php

namespace Antevasin;

$core_public_modules = [
    'antevasin/core/public'
];
$allowed_modules = array_merge( $allowed_modules, $core_public_modules );
core::set_public_module_token( $core_public_modules );