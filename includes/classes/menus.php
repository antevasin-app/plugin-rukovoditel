<?php

namespace Antevasin;

class menus
{
    private $plugin;

    public function __construct( plugin $plugin )
    {
        $this->plugin = $plugin;
    }

    public function plugin_sidebar_menus()
    {
        $plugin_module_links = $this->plugin_module_links();
        $plugin_menu = array( 
            'title' => TEXT_PLUGIN_SIDEBAR_MENU_TITLE, 
            'url' => url_for( 'antevasin/core/' . 'index' ), 
            'class' => 'fa-plug', 
            'submenu' => $plugin_module_links 
        );
        $plugin_sidebar = $this->plugin_modules_sidebar_menus();
        $plugin_sidebar[] = $plugin_menu;
        return $plugin_sidebar;
    }

    public function plugin_module_links()
    {
        $module_links = array();
        foreach ( $this->plugin->get_modules( false ) as $name => $module )
        {
            $module_links[] = array( 'title' => $name, 'url' => url_for( $module['app_path'] . 'index' ), 'class' => 'fa-plug' );
        }
        return $module_links;
    }

    public function plugin_modules_sidebar_menus()
    {
        $sidebar_menus = array();
        foreach ( $this->plugin->get_modules( false ) as $name => $module )
        {
            // $sidebar_menus[] = array( 'title' => $name, 'url' => url_for( $module['app_path'] . 'index' ), 'class' => 'fa-plug' );
            if ( is_file( $module['path'] . 'menu.php' ) )
            {
                require $module['path'] . 'menu.php';
            }
        }
        // print_rr( $sidebar_menus );
        return $sidebar_menus;
    }
}