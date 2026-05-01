<?php

namespace Antevasin;

class plugin
{    
    // private properties
    private $plugin_path;
    private $plugin_info;
    private $core_path;
    private $modules;
    private $public_module_access = false;
    
    function __construct()
    {        
        // print_rr('in antevasin plugin class constructor');
        $this->set_plugin_path();
        $this->set_plugin_info();
        $this->set_core_path();
        $this->set_modules();
        $this->require_languages();
        $this->require_functions();
        $this->require_classes();
        $this->require_modules();
        $this->set_country_code();
    }  
    
    // setter functions
    private function set_country_code()
    {
        $timezone = new \DateTimeZone( CFG_APP_TIMEZONE );
        $country_code = $timezone->getLocation()['country_code'];
        if ( !defined( 'CFG_APP_COUNTRY_CODE' ) ) define( 'CFG_APP_COUNTRY_CODE', $country_code );
    }

    private function set_plugin_path()
    {
        $this->plugin_path = 'plugins/' . PLUGIN_NAME . '/';    
    }
    
    private function set_plugin_info()
    {
        $this->plugin_info = json_decode( file_get_contents( $this->plugin_path . '/modules/core/module.json' ) );
        define( 'PLUGIN_VERSION', $this->plugin_info->version );
        define( 'PLUGIN_DESCRIPTION', $this->plugin_info->description );
        define( 'PLUGIN_SOURCE', $this->plugin_info->source );
    }
  
    private function set_core_path()
    {
        $this->core_path = $this->plugin_path . 'modules/core/';
    }



    public function set_public_module_access()
    {
        $this->public_module_access = true;
    }  

    private function set_modules()
    {
        // Discover all modules in the plugin
        $modules = glob( PLUGIN_MODULES_PATH . '*', GLOB_ONLYDIR );
        // Load and instantiate each module class
        foreach ( $modules as $module_path )
        {
            $module_name = basename( $module_path );
            $module_info = json_decode( file_get_contents( $module_path . '/module.json' ), true );
            $this->modules[$module_name] = array(
                'name' => $module_name,
                'path' => $module_path . '/',
                'app_path' => PLUGIN_NAME . '/' . $module_name . '/',
                'info' => $module_info
            );
            $module_class_file = $module_path . '/includes/classes/' . $module_name . '.php';
            
            // Check if module class file exists
            if ( file_exists( $module_class_file ) )
            {
                // Load the module class file
                require_once( $module_class_file );
                
                // Create instance of the module class (e.g., $core)
                // Use fully qualified class name with namespace
                $module_class = __NAMESPACE__ . '\\' . $module_name;
                
                // Check if class exists and can be instantiated
                if ( class_exists( $module_class ) )
                {
                    // Create dynamic variable with module name (e.g., $core)
                    ${$module_name} = new $module_class();
                    
                    // Make module instance available globally
                    global ${$module_name};
                }
            }
        }
        // die(print_rr($this));
    }
      
    public function set_user_access()
    {
        global $app_user, $plugin, $app_module, $app_action;

        $is_system_admin = $is_plugin_admin = false;
        $is_module_user = true;
        if ( $this->public_module_access )
        {
            $is_module_user = true;
        }
        if ( isset( $app_user ) )
        {
            if ( $plugin == 'antevasin' && in_array( $app_module, array_keys( $this->modules ) ) )
            {
                // check plugin and module settings to determine what plugin modules or actions they have access to
                $is_module_user = false;
                
            }
            if ( $app_user['group_id'] === 0 )
            {
                $is_system_admin = true;
                $is_plugin_admin = true;
                $is_module_user = true;
            }
            if ( IS_AJAX ) $is_module_user = true;
            // print_rr(IS_AJAX); print_rr($this); print_rr(array_keys( $this->modules )); print_rr("plugin is $plugin"); print_rr("app module is $app_module"); print_rr("app action is $app_action"); print_rr("is module user is $is_module_user"); die('pause');
        }
        if ( !defined( 'IS_SYSTEM_ADMIN' ) ) define( 'IS_SYSTEM_ADMIN', $is_system_admin );
        if ( !defined( 'IS_PLUGIN_ADMIN' ) ) define( 'IS_PLUGIN_ADMIN', $is_plugin_admin );
        if ( !defined( 'IS_MODULE_USER' ) ) define( 'IS_MODULE_USER', $is_module_user );
        $plugin_user_id = 4;
        if ( !defined( 'PLUGIN_USER_ID' ) ) define( 'PLUGIN_USER_ID', $plugin_user_id );
    }

    // getter functions

    protected function get_core_path()
    {
        return $this->core_path;
    }
 
    public function get_header_dropdowns()
    {
        // load module header header files
        foreach ( $this->get_modules() as $name => $module )
        {
            $module_file = $module['path'] . 'includes/header_dropdown_menu.php';
            if ( file_exists( $module_file ) )
            {   
                require( $module_file );        
            } 
        }
    }

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
        $class_files = glob( PLUGIN_PATH . 'includes/classes/*.php' );
        foreach ( $class_files as $class_file )
        {
            $class_name = basename( $class_file, '.php' );
            if ( $class_name == 'plugin' ) continue;
            require_once $class_file;
        }
        require_once $this->core_path . 'includes/classes/core.php';
        foreach ( $this->modules as $module_name => $module )
        {
            if ( is_file( $classes_file = $module['path'] . 'includes/classes/' . $module_name . '.php' ) )
            {        
                require_once $classes_file;
            }   
        }  
    }

    private function require_modules()
    {
        foreach ( $this->modules as $module_name => $module )
        {
            if ( is_file( $module_file = $module['path'] . 'application_core.php' ) )
            {      
                require_once $module_file;
            }   
            if ( is_file( $module_file = $module['path'] . 'application_top.php' ) )
            {      
                require_once $module_file;
            }   
        }  
    }
    
    public function check_token()
    {
        if ( isset( $_GET['token'] ) )
        {
            return true;
        }
    }
}