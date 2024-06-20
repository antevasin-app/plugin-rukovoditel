<?php

namespace Antevasin;

print_rr('core module index file');
print_rr($core);
$core->update_entities();
// print_rr($core->get_path());
// print_rr($core->get_modules());
// print_rr($this_plugin);
$antevasin = new antevasin( 'antevasin' );
print_rr($antevasin);
