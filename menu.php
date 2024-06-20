<?php

namespace Antevasin;

$menus = new menus( $this_plugin );
$app_plugin_menu['menu'] = $menus->plugin_sidebar_menus();