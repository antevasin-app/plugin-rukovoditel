<?php

namespace Antevasin;

class core implements module
{
    private $plugin_name;
    private $plugin_path;
    private $core_path;
    private $app_user;
    private $user_id;
    private $user_group_ids;
    private $is_system_admin = false;
    private $is_plugin_admin = false;
    private $is_module_user = false;
    private $user_settings;
    private $entities;

    protected $name;
    protected $path;
    protected $info;
    protected $config;

    public function __construct( $name = null )
    {
        global $app_user;

        $this->plugin_name = PLUGIN_NAME;
        $this->plugin_path = PLUGIN_PATH;
        $this->set_config( $name );
        $this->set_info( $name );
        if ( $name === null ) $name = 'core';
        $this->core_path = PLUGIN_MODULES_PATH .  'core/';
        // print_rr('in core class constructor - module name is ' . $name);
        $this->name = $name;
        $this->path = PLUGIN_MODULES_PATH . $name . '/';
        if ( isset( $app_user ) )  
        {  
            $this->app_user = $app_user;
            $this->user_id = $app_user['id'];
            $this->user_group_ids = $this->get_user_group_ids();
            $this->is_plugin_admin();
            $this->get_user_settings();
            $this->set_error_reporting();
        }
    }

    // setter functions

    public function set_info( $module = null )
    {
        // print_rr('in core set_info method');
        $this->info = ( $module ) ? 'info of the module supplied as argument' : 'info of the core module';
    }

    public function set_config( $module = null )
    {
        // print_rr('in core set_config method');
        $this->config = ( $module ) ? 'config of the module supplied as argument' : 'config of the core module';
    }

    // getter functions

    public function get_path( module $module = null )
    {
        // print_rr('in core get_path method');
        print_rr("path to this module is " . $module->path);
    } 

    private function get_user_group_ids()
    {
        $group_ids = array( 0, 1 );
        return $group_ids;
    }

    private function is_plugin_admin()
    {
        if ( $this->app_user['group_id'] === 0 )
        {
            $this->is_system_admin = true;
            $this->is_plugin_admin = true;
            $this->is_module_user = true;
        }
        if ( false )
        {
            // check plugin settings
            $this->is_plugin_admin = true;
            $this->is_module_user = true;
        }
        return $this->is_plugin_admin;
    }

    public function is_system_admin()
    {
        return $this->is_system_admin;
    } 

    public function is_module_user()
    {
        return $this->is_module_user;
    }

    private function get_user_settings()
    {
        global $app_logged_users_id;
        
        // print_rr('in get_user_settings');
        $sql = "SELECT * FROM app_users_configuration WHERE users_id=$app_logged_users_id AND configuration_name LIKE 'plugin%'";
        $user_query = db_query( $sql );
        $user_settings = array();
        while ( $results = db_fetch_array( $user_query ) )
        {
            $name = explode( '-', $results['configuration_name'] );
            $this->user_settings[$name[1]] = $results['configuration_value'];
        }
        if ( empty( $this->user_settings ))
        {
            // print_rr('user settings are empty');
            $this->user_settings = array();
            if ( $this->is_system_admin )
            {
                $admin_settings = array(
                    'error_reporting' => 0,  
                ); 
                $this->user_settings = array_merge( $this->user_settings, $admin_settings );
            }
            foreach ( $this->user_settings as $name => $value )
            {
                $setting_name = "plugin-$name";
                $sql = "INSERT INTO app_users_configuration ( `users_id`, `configuration_name`, `configuration_value` ) VALUES ( $app_logged_users_id, '$setting_name', '$value' )";
                // print_rr($sql);
                db_query( $sql );
            }
        }
        // print_rr($user_settings);
    } 

    private function set_error_reporting()
    {
        if ( $this->is_system_admin && $this->user_settings['error_reporting'] )
        {
            error_reporting( E_ALL );
            ini_set( 'display_errors', 1 );
            ini_set( 'display_startup_errors', 1 );
            ini_set( 'error_reporting', E_ALL ); 
        }
    }

    // curl functions

    public static function curl_get( $url )
    {
        $ch = curl_init();    
        curl_setopt( $ch, CURLOPT_URL, $url );
        curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, FALSE ); 
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
        
        curl_setopt( $ch, CURLOPT_AUTOREFERER, TRUE );
        curl_setopt( $ch, CURLOPT_HEADER, 0 );

        curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, TRUE ); 
        $response = curl_exec( $ch );
        if ( $e = curl_error( $ch ) ) {
            $curl_response = '{"Error":{"message":"' . $e . '","reasonCode":"http_request_error"}}';
            die($curl_response);
        } else {
            $curl_response = $response;  
        }
        curl_close( $ch );
        return $curl_response;          
    }
    
    public static function curl_post( $url, $data, $headers = array() )
    {
        $ch = curl_init();
        curl_setopt( $ch, CURLOPT_URL, $url );
        curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, 0 );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
        

        curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers );
        
        curl_setopt( $ch, CURLOPT_POST, true );
        curl_setopt( $ch, CURLOPT_POSTFIELDS, $data );
        $response = curl_exec( $ch );
        if ( $e = curl_error( $ch ) ) {
            $curl_response = '{"Error":{"message":"' . $e . '","reasonCode":"http_request_error"}}';
            die($curl_response);
        } else {
            $curl_response = $response;  
        }
        curl_close( $ch );
        return $curl_response;        
    }

    // entity functions

    private function get_entities()
    {
        global $app_entities_cache;
        
        $this->entities = $app_entities_cache;
    }

    private function get_entity_id( $entity_name )
    {
        $sql = "SELECT * FROM app_entities WHERE name='$entity_name'";
        if ( $result = db_fetch_array( db_query( $sql ) ) )
        {
            return $result['id'];
        }
    }

    public function update_entities()
    {
        print_rr('in update_entities function');
        $this->get_entities();
        $entities_entity_id = $this->get_entity_id( 'entities' );
        print_rr("entitities id is $entities_entity_id");
        if ( $entities_entity_id > 0 )
        {
            $sql = "SELECT * FROM app_entity_{$entities_entity_id}";
            // print_rr($sql);
            $user_query = db_query( $sql );
            $existing_entities = array();
            while ( $results = db_fetch_array( $user_query ) )
            {
                $entity_id = $results['id'];
                // $group_id = $this->entities[$entity_id]['group_id'];
                $existing_entities[$entity_id] = $results;
            }
            // print_rr($existing_entities);
            foreach ( $this->entities as $entity_id => $info )
            {
                if ( !isset( $existing_entities[$entity_id] ) )
                {
                    // print_rr("add item to entity with id $entity_id");
                    $now = time();
                    $sql = "
                        INSERT INTO `app_entity_29`
                        ( `id`, `parent_id`, `parent_item_id`, `linked_id`, `date_added`, `date_updated`, `created_by`, `sort_order`, `field_263`, `field_264` ) 
                        VALUES 
                        ( $entity_id, 0, 0, 0, $now, 0, 1, 0, 'true', '{$info['name']}' )
                    ";
                    // print_rr($sql);
                    db_query( $sql );
                }
            }
        }
    }
}

class core_old extends plugin
{
    // private properties
    private $path;
    private $app_user;
    private $is_system_admin = false;
    private $is_plugin_admin = false;
    private $is_module_user = false;
    private $user_settings;
    // private $entities;
    private $entity_name;
    private $entity_id;
    private $entity_info;
    
    function __construct( $data = array() )
    {
        global $app_user;

        parent::__construct( $data );
        if ( isset( $app_user ) )  
        {  
            $this->app_user = $app_user;
            $this->is_plugin_admin();
            $this->get_user_settings();
            $this->set_error_reporting();
        }
        $this->path = $this->get_core_path();
        // $this->get_entities();
    }

    // user functions
    private function get_user_settings()
    {
        global $app_logged_users_id;
        
        // print_rr('in get_user_settings');
        $sql = "SELECT * FROM app_users_configuration WHERE users_id=$app_logged_users_id AND configuration_name LIKE 'plugin%'";
        $user_query = db_query( $sql );
        $user_settings = array();
        while ( $results = db_fetch_array( $user_query ) )
        {
            $name = explode( '-', $results['configuration_name'] );
            $this->user_settings[$name[1]] = $results['configuration_value'];
        }
        if ( empty( $this->user_settings ))
        {
            // print_rr('user settings are empty');
            $this->user_settings = array();
            if ( $this->is_system_admin )
            {
                $admin_settings = array(
                    'error_reporting' => 0,  
                ); 
                $this->user_settings = array_merge( $this->user_settings, $admin_settings );
            }
            foreach ( $this->user_settings as $name => $value )
            {
                $setting_name = "plugin-$name";
                $sql = "INSERT INTO app_users_configuration ( `users_id`, `configuration_name`, `configuration_value` ) VALUES ( $app_logged_users_id, '$setting_name', '$value' )";
                // print_rr($sql);
                db_query( $sql );
            }
        }
        // print_rr($user_settings);
    }

    private function is_plugin_admin()
    {
        if ( $this->app_user['group_id'] === 0 )
        {
            $this->is_system_admin = true;
            $this->is_plugin_admin = true;
            $this->is_module_user = true;
        }
        if ( false )
        {
            // check plugin settings
            $this->is_plugin_admin = true;
            $this->is_module_user = true;
        }
        return $this->is_plugin_admin;
    }
  
    public function is_system_admin()
    {
        return $this->is_system_admin;
    } 
    
    public function is_module_user( $module_name )
    {
        return $this->is_module_user;
    }
    
    private function set_error_reporting()
    {
        if ( $this->is_system_admin && $this->user_settings['error_reporting'] )
        {
            error_reporting( E_ALL );
            ini_set( 'display_errors', 1 );
            ini_set( 'display_startup_errors', 1 );
            ini_set( 'error_reporting', E_ALL ); 
        }
    }

    
    // module functions
    
    public function get_path()
    {
        return $this->path;
    }
    
    // entity functions
    

    
    private function get_entity_info()
    {
        $sql = "SELECT * FROM app_entities_configuration WHERE entities_id={$this->entity_id}";
        if ( $result = db_fetch_array( db_query( $sql ) ) )
        {
            $this->entity_info = $result;
        }
    }

    public function get_entity_by_name()
    {
        $sql = "SELECT * FROM app_entities WHERE name='{$this->entity_name}'";
        if ( $result = db_fetch_array( db_query( $sql ) ) )
        {
            $this->entity_id = $result['id'];
        }
    }


    

    



}