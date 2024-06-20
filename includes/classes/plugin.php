<?php

namespace Antevasin;

class plugin
{    
    // private properties
    private $plugin_path;
    private $plugin_info;
    private $plugin_user_id;
    private $core_path;
    private $modules;
    
    function __construct()
    {        
        $this->set_plugin_path();
        $this->set_plugin_info();
        $this->set_core_path();
        $this->set_modules();
        $this->set_plugin_user_id();
        $this->require_languages();
        $this->require_functions();
        $this->require_classes();
    }  
    
    // setter functions
    private function set_plugin_path()
    {
        $this->plugin_path = 'plugins/' . PLUGIN_NAME . '/';    
    }
    
    private function set_plugin_info()
    {
        $this->plugin_info = json_decode( file_get_contents( $this->plugin_path . 'plugin.json' ), true );
    }
  
    private function set_core_path()
    {
        $this->core_path = $this->plugin_path . 'modules/core/';
    }

    protected function get_core_path()
    {
        return $this->core_path;
    }

    private function set_modules()
    {
        $dirs = glob( $this->plugin_path . 'modules/*', GLOB_ONLYDIR );
        $modules = array();
        foreach ( $dirs as $dir )
        {
            $module_name = basename( $dir );
            $module_info = $module_info = json_decode( file_get_contents( $dir . '/module.json' ), true );
            $module_data = array(
                'name' => $module_name,
                'app_path' => PLUGIN_NAME . '/' . $module_name . '/',
                'path' => $dir . '/',
                'info' => $module_info,
            );
            $modules[$module_name] = $module_data;
            if ( $module_name === 'core' )
            {
                // $this->core = $module_data;
            }
            else 
            {
                // $modules[$module_name] = $module_data;
            }
        }
        $this->modules = $modules;
        // $this->all_modules = array_merge( array( 'core' => $this->core ), $this->modules );
    }
    
    private function set_plugin_user_id()
    {
        $this->plugin_user_id = 4;
        if ( !defined( 'PLUGIN_USER_ID' ) ) define( 'PLUGIN_USER_ID', $this->plugin_user_id );
    } 
    
    // getter functions

    public function get_modules( $exclude_core = true )
    {
        $modules = $this->modules;
        if ( $exclude_core ) unset( $modules['core'] );
        return $modules;
    }

    // require functions

    private function require_languages()
    {
        global $app_user;
        
        foreach ( $this->modules as $module_name => $module )
        {
            if ( is_file( $language_file = $module['path'] .'languages/' . ( isset($app_user['language'] ) ? $app_user['language'] : CFG_APP_LANGUAGE ) ) )
            {          
                require $language_file;
            }  
        }
    }
    
    private function require_functions()
    {
        foreach ( $this->modules as $module_name => $module )
        {
            if ( is_file( $functions_file = $module['path'] . 'includes/functions.php' ) )
            {          
                require $functions_file;
            }           
        }     
    }
    
    private function require_classes()
    {
        require_once $this->core_path . 'includes/classes/core.php';
        foreach ( $this->modules as $module_name => $module )
        {
            if ( is_file( $classes_file = $module['path'] . 'includes/classes/' . $module_name . '.php' ) )
            {        
                require_once $classes_file;
            }   
        }  
    }

    public function plugin_sidebar_menu()
    {   
        $menu = new menus( $this );
    }

}