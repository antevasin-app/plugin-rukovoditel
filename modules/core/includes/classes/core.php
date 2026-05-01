<?php

namespace Antevasin;

class core implements module
{
    private static $instance = null;

    private $plugin_name;
    private $plugin_path;
    private $plugin_version;
    private $core_path;
    private $app_user;
    private $user_group_ids;
    private $entities;
    private $name;
    private $title;
    private $path;
    private $app_path;
    private $info;
    private $cfg;
    private $config;
    private $index_tabs;
    private $get_default = false;
    
    protected $debug = false;
    protected $items = array(); 
    protected $items_info = array();
    protected $user_id;
    protected $system_log_data = array();
    protected $entity_user_fields = array();
    protected $modules;
    
    public $data;
    public $user_settings;

    public function __construct( $name = null )
    {
        global $app_user;

        // print_rr("core module constructor called");
        // print_rr(debug_backtrace());
        $this->data = array_merge( $_GET, $_POST );   
        $this->plugin_name = PLUGIN_NAME;
        $this->plugin_path = PLUGIN_PATH;
        $this->plugin_version = PLUGIN_VERSION;
        $this->plugin_version = PLUGIN_DESCRIPTION;
        $this->core_path = PLUGIN_MODULES_PATH .  'core/';
        $this->name = ( $name === null )  ? 'core' : $name;
        $this->title = ucfirst( $this->name );
        $this->path = PLUGIN_MODULES_PATH . $this->name . '/';
        $this->app_path = PLUGIN_NAME . "/$this->name/";
        $this->set_config();
        $this->set_info();
        // $this->set_modules();
        if ( isset( $app_user ) && $app_user['id'] > 0 )  
        {  
            // if ( !IS_MODULE_USER ) redirect_to( 'dashboard/access_forbidden' );
            $this->app_user = $app_user;
            $this->user_id = $app_user['id'];
            $this->user_group_ids = $this->get_user_group_ids();
            $this->get_user_settings();
            $this->set_error_reporting();
        }
    }

    public static function generate_password( $legnth = 11 )
    {
        $hasher = new \PasswordHash( $legnth, false );    
        $password = \users::get_random_password();
        return array( 'password' => $password, 'hashed' => $hasher->HashPassword( $password ) );
    }

    public static function set_public_module_token( $public_modules )
    {
        global $app_session_token;

        // print_rr('in set_public_module_token function');
        if ( isset( $_GET['module'] ) && in_array( $_GET['module'], $public_modules ) )
        {
            // print_rr('we are making a request to a public module - check if the user is logged in');
            // print_rr($_GET);
            // print_rr($_POST);
            // print_rr($_COOKIE);
            // print_rr(app_session_is_registered('app_logged_users_id'));
            // print_rr($app_session_token);
            if ( isset( $_COOKIE['app_remember_user'] ) && isset( $_COOKIE['app_remember_pass'] ) && app_session_is_registered('app_logged_users_id') )
            {
                // print_rr('user is logged in set token to avoid CSRF flag');
                $_GET['token'] = $app_session_token;
            }
        }
        else
        {
            // print_rr('we are not making a request to a public module - do nothing');
        }
        // foreach ( $public_modules as $public_module )
        // {
        //     print_rr($public_module);
        // }   
        // print_rr($_GET);
        // print_rr($_POST);
        // print_rr($_COOKIE);
    }

    public static function get_instance() 
    {
        if ( self::$instance === null ) 
        {
            self::$instance = new self();
        }
        return self::$instance;
    }

    // setter functions 
    public function set_info()
    {
        $this->info = $this->get_module_info();
    }
    
    public function set_config()
    {
        $this->config = $this->get_module_config();
    }

    public function set_data( $data )
    {
        $this->data = $data;
    }   

    public function add_data( $data )
    {
        if ( is_array( $data ) )
        {
            $this->data = array_merge( $this->data, $data );
        }
        else
        {
            $this->data[] = $data;
        }
    }

    public function set_index_tabs( $tabs )
    {
        $this->index_tabs = $tabs;
    }

    public function set_user_id( $user_id )
    {
        $this->user_id = $user_id;
    }

    // getter functions
    public function get_data()
    {
        return $this->data;
    }

    public function get_name()
    {
        return $this->name;
    }
    
    public function get_title( $format = false )
    {
        if ( $format )
        {
            $this->name = ( function_exists( $format ) ) ? $format( $this->name ) : ucfirst( $this->name );
        }
        return $this->title;
    }

    public function get_path()
    {
        return $this->path;
    } 

    public function get_app_path()
    {
        return $this->app_path;
    } 

    public function get_info()
    {
        return $this->info;
    }

    public function get_config()
    {
        return $this->config;
    }
    
    public function get_index_tabs()
    {
        return $this->index_tabs;
    }

    private function get_user_group_ids()
    {
        $group_ids = array( 0, 1 );
        return $group_ids;
    }

    public function download_attachment()
    {
        // die(print_rr('in download_attachment function'));
        if ( isset( $this->data['file'] ) )
        {
            $file = \attachments::parse_filename( base64_decode( $this->data['file'] ) );
            //check if using file storage for field
            if ( class_exists( 'file_storage' ) and isset( $this->data['field'] ) )
            {      	
                \file_storage::download_file(_get::int('field'), base64_decode($_GET['file']));      	
            }
    
            if ( is_file( $file['file_path'] ) )
            {
              if ( $file['is_image'] and isset( $this->data['preview'] ) )
              {                          
                  if($this->data['preview']=='small' and CFG_CREATE_ATTACHMENTS_PREVIEW==1)
                  {                
                      $file['file_path'] = \attachments::prepare_image_preview($file);                               
                  }
                                        
                  header("Content-type: " . $file['mime_type']);
                  header('Content-Disposition: filename="' . $file['name'] . '"');
      
                  flush();
      
                  readfile($file['file_path']);
              }
              elseif($file['is_audio'] and isset($this->data['preview']))
              {
                  $type = mime_content_type($path);
                  header("Content-type: " . $type);
                  header('Content-Disposition: filename="' . $file['name'] . '"');
                  
                  flush();
                  
                  readfile($file['file_path']);
              }
              elseif($file['is_pdf'] and isset($this->data['preview']))
              {                                                        
                header("Content-type: application/pdf");
                header('Content-Disposition: filename="' . $file['name'] . '"');
                
                flush();
                
                readfile($file['file_path']);
              }
              else
              {                     
                header('Content-Description: File Transfer');
                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment; filename='.$file['name']);
                header('Content-Transfer-Encoding: binary');
                header('Expires: 0');
                header('Cache-Control: must-revalidate');
                header('Pragma: public');
                header('Content-Length: ' . filesize($file['file_path']));
                
                flush();
                      
                readfile( $file['file_path'] );          
              }
    
            }
            else
            {
              echo TEXT_FILE_NOT_FOUD;
            }
        }
    }

    public function destroy_session() : bool
    {
        if ( isset( $this->data['token'] ) ) 
        {
            $sql = "SELECT * FROM app_sessions WHERE value LIKE '%" . db_input( $this->data['token'] ) . "%'";
            if ( $result = db_fetch_array( db_query( $sql ) ) )
            {
                $sesskey = $result['sesskey'];
                print_rr("Destroying session with sesskey: $sesskey");
                // db_query("delete from app_sessions where sesskey = '" . db_input($sesskey) . "'");
                return true;
            }
            else
            {
                return false;
            }
        }
    }

    public function module_index_tabs( form $form )
    {
        if ( get_class( $this ) == 'Antevasin\core' )
        {
            $source = $this->get_info()->source;
            $version = ( $this->get_name() == 'core' ) ? PLUGIN_VERSION : $this->get_info()->version;
            $branch = $this->get_info()->branch;
            $file_url = 'https://api.github.com/repos/' . $this->get_info()->source . '/zipball/v' . $version;
            $private = ( isset( $this->get_info()->token ) ) ? 1 : 0;
            $reinstall_link = $this->get_reinstall_link();
            $is_link_dir = PLUGIN_PATH . 'application_core.php';
            // print_rr("private is $private");
            if ( is_link( $is_link_dir ) )
            {
                // $_SESSION['alerts']->messages[] = array('params' => 'class="alert alert-danger"',  'text' => "Download aborted as destination directory is a symbolic link" );
                // die( '{"error":"Download aborted as destination directory is a symbolic link"}' ); 
                $reinstall_link = '';
            }
            $plugin_settings = array(
                'title' => 'Plugin Settings',
                'groups' => array(
                    array( 
                        'label' => 'Branch',
                        'field_class' => 'plugin-info',
                        'field' => $form->add_tag( 'select', 'plugin_branches', array( $branch => $branch ), $branch, array( 'size' =>
                         'small' ) )
                    ),
                    array(     
                        'label' => 'Plugin Path',
                        'field_class' => 'plugin-info',
                        'field' => PLUGIN_PATH
                    ),
                    array(     
                        'label' => 'Installed Version',
                        'field_class' => 'plugin-info',
                        'field' => PLUGIN_VERSION . '<a id="plugin_version" class="action" data-action="download" data-module="core" data-version="' . PLUGIN_VERSION . '" data-source="' . $source . '" data-file_url="' . $file_url . '" data-private="' . $private . '" onclick="core.files( this )"><i class="fa fa-download"></i></a>' . $reinstall_link
                    ),
                    array(     
                        'label' => 'Plugin Source',
                        'field_class' => 'plugin-info',
                        'field' => '<div><a href="https://github.com/' . PLUGIN_SOURCE . '">' . PLUGIN_SOURCE . '</a></div>'
                    ),
                    array(     
                        'label' => 'Description',
                        'field_class' => 'plugin-info',
                        'field' => PLUGIN_DESCRIPTION
                    ),
                    array(  
                        'label' => 'Licence Key',
                        'field' => $form->add_tag( 'input', 'module[key]', null, 123456789, array( 'size' => 'x-large' ) )
                    )
                )
            );
            // $sections = array(
            //     array(
            //         'title' => 'Module Management',
            //         'content' => $this->module_management()
            //     ),                    
            // );
            // array_unshift( $sections, $plugin_settings );
            // $tabs =  array(
            //     'name' => 'plugin',
            //     'sections' => $sections
            // );
            // $this->set_index_tabs( array( $tabs ) ); 
        }
    }

    public function get_user_settings()
    {
        global $app_logged_users_id;
 
        if ( empty( $app_logged_users_id ) ) return;
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
            $this->user_settings = array();
            if ( defined( 'IS_SYSTEM_ADMIN' ) && IS_SYSTEM_ADMIN )
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
                db_query( $sql );
            }
        }
    } 

    public function set_user_setting( $key, $value ) 
    {
        global $app_user, $app_logged_users_id;
        
        $setting = json_encode( array_values( $value ) );
        if ( strlen( $key ) > 0 )
        {
            $cfg_query = db_query( "select * from app_users_configuration where users_id='" . db_input( $app_user['id'] ) . "' and configuration_name='plugin-" . db_input( $key ) . "'" );   
            if ( $cfg = db_fetch_array( $cfg_query ) )
            {
                db_query( "update app_users_configuration set configuration_value='" . db_input( $setting ) . "' where users_id='" . db_input( $app_user['id'] ) . "' and configuration_name='plugin-" . db_input( $key ). "'" );
            }
            else
            {
                db_perform( 'app_users_configuration', array( 'configuration_name' => 'plugin-' . $key, 'configuration_value' => trim( $setting ),'users_id'=>$app_user['id'] ) );
            }
        } 
    }

    private function set_error_reporting()
    {
        if ( defined( 'IS_SYSTEM_ADMIN' ) && IS_SYSTEM_ADMIN && $this->user_settings['error_reporting'] )
        {
            error_reporting( E_ALL );
            ini_set( 'display_errors', 1 );
            ini_set( 'display_startup_errors', 1 );
            ini_set( 'error_reporting', E_ALL ); 
        }
    }

    // curl functions

    public static function curl_get( $url, $headers = array() )
    {
        $ch = curl_init();    
        curl_setopt( $ch, CURLOPT_URL, $url );
        curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, FALSE ); 
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );

        curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers );
        
        curl_setopt( $ch, CURLOPT_AUTOREFERER, TRUE );
        curl_setopt( $ch, CURLOPT_HEADER, 0 );

        curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, TRUE ); 
        $response = curl_exec( $ch );
        if ( $e = curl_error( $ch ) ) {
            $curl_response = '{"CURL Error":{"message":"' . $e . '","reasonCode":"http_request_error"}}';
            die( $curl_response );
        } else {
            $curl_response = $response;  
        }
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close( $ch );
        // Check if request was successful
        if ( $httpCode !== 200 ) die( 'HTTP Error: ' . $httpCode );
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

    protected function get_entity_id( $entity_name )
    {
        $sql = "SELECT * FROM app_entities WHERE name='$entity_name'";
        if ( $result = db_fetch_array( db_query( $sql ) ) )
        {
            return $result['id'];
        }
    }

    public function get_ajax_field_entities_id( $field_id )
    {
        if ( is_array( $field_id ) )
        {
            $field_id = implode( ',', $field_id );
            $sql = "SELECT * FROM app_fields WHERE id IN ( $field_id )";
            $user_query = db_query( $sql );
            $entities_ids = array();
            while ( $results = db_fetch_array( $user_query ) )
            {
                $config = json_decode( $results['configuration'], true );
                if ( isset( $config['entity_id'] ) )
                {
                    $entities_ids[$results['id']] = $config['entity_id'];
                }
            }
            return $entities_ids;
        }
        else
        {
            $sql = "SELECT * FROM app_fields WHERE id=$field_id";
            if ( $result = db_fetch_array( db_query( $sql ) ) )
            {
                $config = json_decode( $result['configuration'], true );
                return $config['entity_id'];
            }
        }
    }
    
    public function get_field_entity_id( $field_id )
    {
        $sql = "SELECT * FROM app_fields WHERE id=$field_id";
        if ( $result = db_fetch_array( db_query( $sql ) ) )
        {
            return $result['entities_id'];
        }
    }

    public function get_field_id( $entity_id, $field_name )
    {
        // print_rr("get field id for $field_name in entity $entity_id");
        $sql = "SELECT * FROM app_fields WHERE name LIKE '$field_name' AND entities_id=$entity_id";
        if ( $result = db_fetch_array( db_query( $sql ) ) )
        {
            return $result['id'];
        }
        else
        {
            $sql = "SELECT * FROM app_fields WHERE name LIKE '%$field_name' AND entities_id=$entity_id";
            if ( $result = db_fetch_array( db_query( $sql ) ) )
            {
                return $result['id'];
            }
        }
    }

    protected function get_entity_group_id( $group_name )
    {
        $sql = "SELECT * FROM app_entities_groups WHERE name='$group_name'";
        if ( $result = db_fetch_array( db_query( $sql ) ) )
        {
            return $result['id'];
        }
    }

    public function get_entities( $json = false )
    {
        global $app_entities_cache;
        
        $this->entities = $app_entities_cache;
        return ( $json ) ? json_encode( $app_entities_cache ) : $app_entities_cache;
    }

    public function get_entity_fields()
    {
        if ( isset( $this->data['entities_id'] ) )
        {
            $entities_id = $this->data['entities_id'];
            $sql = "SELECT * FROM app_fields WHERE entities_id=$entities_id AND type LIKE 'fieldtype_entity_%'";
            $user_query = db_query( $sql );
            $fields = array();
            while ( $results = db_fetch_array( $user_query ) )
            {
                $fields[$results['id']] = $results;
            }
            return $fields;
        }
    }

    public function get_entity_fields_( $entities = false, $entity_id = false )
    {
        if ( isset( $this->data['entities_id'] ) )
        {
            $entities_id = ( $entity_id ) ? $entity_id : $this->data['entities_id'];
            $sql = "SELECT * FROM app_fields WHERE entities_id=$entities_id AND type LIKE 'fieldtype_entity_%'";
            $user_query = db_query( $sql );
            $fields = array();
            while ( $results = db_fetch_array( $user_query ) )
            {
                if  ( $entities )
                {
                    $field_cfg = new \fields_types_cfg( $results['configuration'] );
                    // print_rr($results['id']); print_rr($field_cfg);
                    if ( isset( $fields[$field_cfg->cfg['entity_id']] ) )
                    {     
                        // print_rr($fields[$field_cfg->cfg['entity_id']]);
                        $fields[$field_cfg->cfg['entity_id']] = array( $fields[$field_cfg->cfg['entity_id']] );
                        // $fields = array( $fields[$field_cfg->cfg['entity_id']], $results['id'] );                    
                        $fields[$field_cfg->cfg['entity_id']][] = $results['id'];
                    }
                    else
                    {
                        $fields[$field_cfg->cfg['entity_id']] = $results['id'];    
                    }
                }
                else
                {
                    $fields[$results['id']] = $results;
                }
            }
            return $fields;
        }
    }

    public function get_entiity_fields_info( $type = false, $json = false )
    {
        $sql = "SELECT * FROM app_fields WHERE type LIKE 'fieldtype_$type'";
        $user_query = db_query( $sql );
        $fields_by_entity = $fields_by_entity_type = $fields_by_entity_name = $fields_by_id = $fields_by_type = $fields_by_name = array();
        while (  $results = db_fetch_array( $user_query ) )
        {
            $fields_by_entity[$results['entities_id']][$results['id']] = $results;
            $fields_by_entity_type[$results['entities_id']][$results['type']][$results['id']] = $results;
            $fields_by_entity_name[$results['entities_id']][$results['name']][$results['id']] = $results;
            $fields_by_id[$results['id']] = $results;
            $fields_by_type[$results['type']][$results['id']] = $results;
            $fields_by_name[$results['name']][$results['id']] = $results;
        }
        $fields = array( 
            'entity' => array(
                'id' => $fields_by_entity,
                'type' => $fields_by_entity_type,
                'name' => $fields_by_entity_name
            ), 
            'id' => $fields_by_id, 
            'type' => $fields_by_type, 
            'name' => $fields_by_name 
        );
        // print_rr($fields);
        return ( $json ) ? json_encode( $fields ) : $fields;
    }

    public function update_module_config( $config ) 
    {
        $module_name = strtoupper( $this->get_name() );
        $configuration_name = "CFG_MODULE_{$module_name}_CONFIG";
        $sql = "SELECT * FROM app_configuration WHERE configuration_name='$configuration_name'";
        if ( $result = db_fetch_array( db_query( $sql ) ) )
        {
            $existing_config = json_decode( $result['configuration_value'], true );
            foreach ( $config as $config_key => $config )
            {
                if ( isset( $existing_config[$config_key] ) )
                {
                    // print_rr($config_key); print_rr($config);
                    foreach ( $config as $key => $value )
                    {
                        if ( is_array( $value ) )
                        {
                            // print_rr($value);
                            $existing_value = $existing_config[$config_key][$key];
                            $updated_value = array_merge( $existing_value, $value );
                            // print_rr($updated_value);
                            $existing_config[$config_key][$key] = $updated_value;   
                        }
                        else
                        {
                            $existing_config[$config_key][$key] = $value;
                        }
                    }
                }
            }
            $sql = "UPDATE app_configuration SET configuration_value='" . json_encode( $existing_config ) . "' WHERE configuration_name='$configuration_name'";
            db_query( $sql );
            // print_rr($sql);
            // print_rr($existing_config);
        }
    }
    
    public function update_auto_actions()
    {
        $auto_actions_entity_id = $this->get_entity_id( 'auto actions' );
        $status_field_id = 982;
        if ( $auto_actions_entity_id > 0 )
        {
            $auto_actions_query = db_fetch_all( "app_entity_$auto_actions_entity_id" );
            $auto_actions = array();
            while ( $results = db_fetch_array( $auto_actions_query ) )
            {
                $auto_action = array(
                    'process_id' => $results['id'],
                    'entity_name' => $results['field_977'],
                    'process_name' => $results['field_978'],
                    'button_title' => $results['field_979'],
                    'is_active' => ( $results['field_982'] == 2 ) ? 0 : $results['field_982']
                );
                $auto_actions[$results['id']] = $auto_action;
            }
            // print_rr($auto_actions);
            $sql = "
                SELECT processes.id AS process_id, entities.name AS entity_name, processes.name AS process_name, processes.button_title AS button_title, processes.is_active AS is_active 
                FROM app_ext_processes AS processes
                INNER JOIN app_entities AS entities
                ON entities.id=processes.entities_id;
            ";
            $user_query = db_query( $sql );
            while ( $results = db_fetch_array( $user_query ) )
            {
                // print_rr('process'); print_rr($results);
                $now = time();
                $process_id = $results['process_id'];
                if ( isset( $auto_actions[$process_id] ) )
                {
                    // print_rr("process - is_active is {$results['is_active']}"); print_rr("auto action is {$auto_actions[$process_id]['field_982']}");
                    // if ( $auto_actions[$process_id]['field_982'] == 2 ) $auto_actions[$process_id]['field_982'] = 0;
                    $diffs = array_diff( $results, $auto_actions[$process_id] );
                    // print_rr($diffs);
                    if ( !empty( $diffs ) )
                    {
                        // print_rr("there are changes to update in auto actions with id $process_id");
                        $title = ( empty( $results['button_title'] ) ) ? $results['process_name'] : $results['button_title'];
                        $status_id = ( $results['is_active'] ) ? 1 : 2;
                        $sql = "
                            UPDATE `app_entity_$auto_actions_entity_id`
                            SET `date_updated`=$now, `field_977`='{$results['entity_name']}', `field_978`='{$results['process_name']}', `field_979`='{$results['button_title']}', `field_981`='$title', `field_982`=$status_id
                            WHERE `id`=$process_id
                        ";
                        print_rr("update auto actions with id $process_id");
                        db_query( $sql );
                    }
                }
                else
                {
                    // print_rr("add item to auto actions with id $process_id");
                    $title = ( empty( $results['button_title'] ) ) ? $results['process_name'] : $results['button_title'];
                    $status_id = ( $results['is_active'] ) ? 1 : 2;
                    $sql = "
                        INSERT INTO `app_entity_$auto_actions_entity_id`
                        ( `id`, `parent_id`, `parent_item_id`, `linked_id`, `date_added`, `date_updated`, `created_by`, `sort_order`, `field_977`, `field_978`, `field_979`, `field_981`, `field_982` ) 
                        VALUES 
                        ( $process_id, 0, 0, 0, $now, 0, 1, 0, '{$results['entity_name']}', '{$results['process_name']}', '{$results['button_title']}', '$title', $status_id )
                    ";
                    print_rr("add item to auto actions with id $process_id");
                    db_query( $sql );
                }
                if ( isset( $status_id ) )
                {
                    $this->choices_values( $auto_actions_entity_id, $process_id, $status_field_id, $status_id );
                }
            }
        }
    }

    public function update_entities()
    {
        // print_rr('in update_entities function');
        $this->get_entities();
        $entities_entity_id = $this->get_entity_id( 'entities' );
        if ( $entities_entity_id > 0 )
        {
            // print_rr("entitities id is $entities_entity_id");
            $now = time();
            $sql = "SELECT * FROM app_entity_{$entities_entity_id}";
            $user_query = db_query( $sql );
            // print_rr($sql); print_rr($user_query);
            $existing_entities = array();
            while ( $results = db_fetch_array( $user_query ) )
            {
                $entity_id = $results['id'];
                $existing_entities[$entity_id] = $results;
            }
            // print_rr($existing_entities);
            $title_field_id = $this->get_field_id( $entities_entity_id, 'title' );
            $include_field_id = $this->get_field_id( $entities_entity_id, 'include' );
            foreach ( $this->entities as $entity_id => $info )
            {
                if ( isset( $existing_entities[$entity_id] ) )
                {
                    // print_rr($info); print_rr($existing_entities[$entity_id]);
                    // $diffs = array_diff( $info, $existing_entities[$entity_id] );
                    // print_rr($diffs);
                    if ( $info['name'] != $existing_entities[$entity_id]['field_363'] )
                    {
                        // print_rr("there are changes to update in existing entities with id $entity_id");
                        $sql = "UPDATE `app_entity_$entities_entity_id` SET `date_updated`=$now, `field_$title_field_id`='{$info['name']}' WHERE `id`=$entity_id";
                        // print_rr("update entity item with id $entity_id");
                        print_rr($sql);
                        db_query( $sql );
                    }
                }
                else
                {
                    print_rr("add item to entity with id $entity_id");
                    $exclude_entities = array( 
                        $this->get_entity_id( 'entities' ), 
                        $this->get_entity_id( 'statuses')
                    );
                    $include = ( in_array( $entity_id, $exclude_entities ) ) ? 'false' : 'true';
                    $sql = "
                        INSERT INTO `app_entity_$entities_entity_id`
                        ( `id`, `parent_id`, `parent_item_id`, `linked_id`, `date_added`, `date_updated`, `created_by`, `sort_order`, `field_$title_field_id`, `field_$include_field_id` ) 
                        VALUES 
                        ( $entity_id, 0, 0, 0, $now, 0, 1, 0, '{$info['name']}', '$include' )
                    ";
                    // print_rr($sql);
                    db_query( $sql );
                }
            }
        }
    }

    public function update_currencies( $currencies )
    {
        // print_rr($currencies);
        $currency_entity_id = 30;
        $name_field = 'field_517';
        $status_field = 'field_520';
        $code_field = 'field_521';
        $symobol_field = 'field_522';
        $currency_ids = array();
        foreach ( $currencies as $code => $currency_info )
        {
            $sql = "SELECT * FROM app_entity_$currency_entity_id WHERE field_521='$code'";
            if ( $result = db_fetch_array( db_query( $sql ) ) )
            {
                $currency_id = $result['id'];
                // print_rr("currency exists so check and return");
                // print_rr($result);
                if ( $result[$symobol_field] != $currency_info['symbol'] ) "UPDATE app_entity_$currency_entity_id SET $symobol_field='{$currency_info['symbol']}'"; // print_rr("currency name {$result[$name_field]} - update symbol value {$result[$symobol_field]} - {$currency_info['symbol']}");
                if ( $result[$name_field] != $currency_info['name'] ) "UPDATE app_entity_$currency_entity_id SET $name_field='{$currency_info['name']}'"; // print_rr("update name value {$result[$name_field]} - {$currency_info['name']}");
            }
            else
            {
                // print_rr("currency does not exist so create it");
                $now = time();
                $name = db_input( $this->replace_unicode_ascii( $currency_info['name'] ) );
                $symbol = db_input( $this->replace_unicode_ascii( $currency_info['symbol'] ) );
                $sql_data = array(
                    $name_field => "$name",  
                    $status_field => 1,  
                    $code_field => "$code",  
                    $symobol_field => "$symbol",  
                );
                // print_rr($sql_data);
                $currency_id = $this->db_insert( $currency_entity_id, $sql_data );
            }
            $currency_ids[] = $currency_id;
        }
        // exit();
        return implode( ',', $currency_ids );
    }

    public function update_countries() 
    {
        // API endpoint to get all countries
        $url = 'https://restcountries.com/v3.1/all';
        $response = core::curl_get( $url );
        // Decode JSON response
        $countries = json_decode( $response, true );
        
        // Check if JSON decoding was successful
        if ( json_last_error() !== JSON_ERROR_NONE ) 
        {
            echo 'JSON Decode Error: ' . json_last_error_msg();
            return;
        }
        // print_rr($countries);
        // Iterate through countries and extract name and currencies
        foreach ( $countries as $country ) 
        {
            $currencies = $country['currencies'] ?? [];
            if ( !empty( $currencies ) ) 
            {
                foreach ( $currencies as $code => $details ) 
                {
                    $currencyName = $details['name'] ?? 'Unknown';
                    $currencySymbol = $details['symbol'] ?? '';
                    // print_rr("Currency: $currencyName ( $code ), Symbol: $currencySymbol");
                }
            } 
            else 
            {
                // print_rr($currencies);
                // print_rr("Country: {$details['name']}, Currency: None");
            }
            $sql_data = array();
            $sql_data['field_497'] = $country['name']['common'] ?? '';
            $sql_data['field_498'] = $country['name']['official'] ?? '';
            // $sql_data['field_499'] = $country['name']['common'] ?? 'Unknown';
            $sql_data['field_500'] = 1;
            $sql_data['field_501'] = $this->update_currencies( $currencies );
            $sql_data['field_502'] = $country['ccn3'] ?? '';
            $sql_data['field_503'] = $country['cca2'] ?? '';
            $sql_data['field_504'] = $country['cca3'] ?? '';
            // print_rr($sql_data);
            $this->db_insert( 29, $sql_data );
        }
    }

    public function replace_unicode_ascii( $string ) 
    {
        // print_rr($string);
        if ( !mb_check_encoding( $string, 'UTF-8' ) ) 
        {
            $string = mb_convert_encoding( $string, 'UTF-8', 'auto' );
        }
        $unicodeToAscii = ["\u{02BB}" => "'", "\u{00E9}" => 'e', "\u{00F1}" => 'n'];
        // print_rr($string);
        $string = strtr($string, $unicodeToAscii);
        // print_rr($string);
        // $string = preg_replace('/[^\x00-\x7F]/u', '', $string);
        // print_rr($string);
        return $string;
    }

    public function update_timezones()
    {
        $timezone_entity_id = 37;
        $timezone_field = "field_657";
        $existing_entity_timezones = db_fetch_all( "app_entity_$timezone_entity_id" );
        print_rr($existing_entity_timezones);
        $entity_timezones = array();
        foreach ( $existing_entity_timezones as $id => $timezone )
        {
            $entity_timezones[$timezone[$timezone_field]] = $timezone;
        }
        print_rr($entity_timezones);
        $timezone_list = array();
        $timezone_identifiers = \DateTimeZone::listIdentifiers();
        $now = time();
        for ( $i = 1; $i < sizeof( $timezone_identifiers ); $i++ )
        {
            $timezone = $timezone_identifiers[$i];
            $timezone_list[$i] = $timezone;
            if ( isset( $entity_timezones[$timezone] ) )
            {
                print_rr("timezone already exists - $timezone");
            }
            else
            {
                print_rr("timezone $timezone does not exist");
                $sql = "
                    INSERT INTO `app_entity_$timezone_entity_id`
                    ( `parent_id`, `parent_item_id`, `linked_id`, `date_added`, `date_updated`, `created_by`, `sort_order`, $timezone_field, `field_660` ) 
                    VALUES 
                    ( 0, 0, 0, $now, 0, 1, 0, '$timezone', 1 )
                ";
                print_rr($sql); 
                db_query( $sql );
            }
            // exit();
        }
        print_rr($timezone_list);
    }

    // db functions

    public function db_insert( $entities_id, $sql_data )
    {
        global $app_user;
        
        if ( !isset( $sql_data['created_by'] ) )
        {
            $user_id = ( empty( $app_user ) ) ? 2 : $app_user['id'];
            $sql_data['created_by'] = $user_id;
        }
        foreach ( $sql_data as $field => $value )
        {
            if ( empty( $value ) ) unset( $sql_data[$field] );
        }
        $insert_id = \items::insert( $entities_id, $sql_data );
        return $insert_id;
    }

    // module functions

    public function render_tabs()
    {
        if ( isset( $this->data['function'] ) && isset( $this->data['class'] ) )
        {
            $function = $this->data['function'];
            $class = $this->data['class'];
            $class_name = '\Antevasin\\' . $class;
            $module = ( $class == 'core' ) ? $this : new $class_name();
            if ( method_exists( $module, $function ) )
            {
                $module->$function();  
            }
        }
        
    }

    public function check_url( $iframe_url )
    {
        // check that url will be successful
        $headers = get_headers( $iframe_url );
        $headers_info = array();
        // print_rr($headers);
        foreach ( $headers as $header )
        {
            $header_info = explode( ': ', $header );
            if ( str_starts_with( $header_info[0], 'HTTP' ) )
            {
                $http = explode( ' ', $header_info[0] );
                $http_ver = explode( '/', $http[0] );
                $header_info[0] = $http_ver[0];
                $header_info[1] = array( 
                    'ver' => $http_ver[1],
                    'status' => $http[1],
                    'msg' => $http[2]
                );
            }
            $headers_info[$header_info[0]] = $header_info[1];
        }
        // print_rr($iframe_url); print_rr($headers_info);
        if ( isset( $headers_info['Access-Control-Allow-Origin'] ) && $headers_info['Access-Control-Allow-Origin'] != '*' )
        {
            // print_rr('url has CORS access restrictions');
            $render_issue = true;
        } 
        else if ( isset( $headers_info['X-Frame-Options'] ) && ( $headers_info['X-Frame-Options'] == 'DENY' || $headers_info['X-Frame-Options'] == 'SAMEORIGIN' ) )
        {
            // print_rr('url has X-Frame-Options restrictions');
            $render_issue = true;
        }
        else 
        {
            $render_issue = false;
        }
        if ( $render_issue )
        {
            // print_rr('url is not going to be rendered in an iframe');   
            $iframe_url = array( 
                'url' => $iframe_url,
                'tooltip' => 'Due to security restrictions this link will open in a new browser tab',
                'content' => '<div class="iframe-error">Unable to preview document on this page due to security restrictions imposed by the authors of the link - <a href="' . $iframe_url . '" target="_blank">Click here to view document in another browser tab</a></div>',
            );
        }
        // print_rr($iframe_url);
        return $iframe_url;
    }

    private function set_modules()
    {
        $modules = get_plugin_modules();
        foreach ( $modules as $module_name => $module )
        {
            if ( $module_name == 'core' ) unset( $modules[$module_name] );
        }
        $this->modules = $modules;
    }

    // records visibility

    public function get_records_visibility_sql( $function, $args = array() )
    {
        global $app_action;

        // print_rr($this->data);
        if ( method_exists( $this, $function ) )
        {
            // print_rr('method exists in core class');
            $function_items = $this->$function( ...$args ); 
        }
        else
        {
            // print_rr('method does not exist in core class');
            foreach ( $this->modules as $module_name => $module )
            {
                // print_rr("check in module $module_name - function is $function");
                if ( method_exists( "Antevasin\\$module_name", $function ) )
                {
                    // print_rr($function); print_rr($args);
                    $class = "Antevasin\\$module_name";
                    $obj = new $class();
                    $obj->data = $this->data;
                    $function_items = $obj->$function( ...$args ); 
                }
            }
        }
        // print_rr(array_keys($function_items));
        if ( in_array( $app_action, array( 'form_single_field' ) ) ) return 1;
        $items = ( empty( $function_items ) ) ? array( 0 ) : array_keys( $function_items ); ;
        // print_rr('items in get_records_visibility_sql'); print_rr($items);
        if ( empty( $items ) ) return;
        $sql = "e.id IN ( " . db_input_in( $items ) . " )";
        // die(print_rr($sql));
        return $sql;
    }

    public function filter_by_system()
    {
        if ( isset( $this->data['entities_id'] ) )
        {
            // print_rr('in filter_by_system function');
            $entities_id = $this->data['entities_id'];
            $form_filter_sql = '';
            $form_entities_id = $this->get_field_entity_id( $this->data['field_id'] );
            $entities_field_id = $this->get_field_id( $entities_id, 'forms' );
            if ( !empty( $entities_field_id ) ) $form_filter_sql = "AND FIND_IN_SET( $form_entities_id, field_$entities_field_id)";
            $sql = "SELECT * FROM app_entity_$entities_id WHERE created_by=1 $form_filter_sql";
            $user_query = db_query( $sql );
            $items = array();   
            while ( $results = db_fetch_array( $user_query ) )
            {
                // print_rr($results);
                $items[$results['id']] = $results;
            }
            return $items;
        } 
    }

    public function filter_by_user()
    {
        if ( isset( $this->data['entities_id'] ) )
        {
            $companies_users = $this->get_companies_users(); 
            $where = ( isset( $this->data['admin' ] ) && $this->data['admin'] ) 
            ? "FIND_IN_SET( created_by, '$companies_users' ) OR ( v.fields_id={$this->data['field_id']} AND FIND_IN_SET( v.value, '$companies_users' ) )" 
            : "created_by={$this->user_id} OR ( v.fields_id={$this->data['field_id']} AND v.value={$this->user_id} )";
            $sql = "
                SELECT e.* 
                FROM app_entity_{$this->data['entities_id']} AS e
                LEFT JOIN app_entity_{$this->data['entities_id']}_values AS v
                ON e.id=v.items_id
                WHERE $where
            ";
            // print_rr($sql); 
            $user_query = db_query( $sql );
            $items = array();
            while ( $results = db_fetch_array( $user_query ) )
            {
                $items[$results['id']] = $results;
            }
            ksort( $items );
            return $items;  
        }
    }

    public function filter_entities()
    {
        // TODO filter to those entities which the user has access to
        $entities_entity_id = $this->get_entity_id( 'entities' );
        if ( $entities_entity_id > 0 )
        {
            $sql = "SELECT * FROM app_entity_{$entities_entity_id}";
            $user_query = db_query( $sql );
            $form_entities = array();
            while ( $results = db_fetch_array( $user_query ) )
            {
                $include_field_id = $this->get_field_id( $entities_entity_id, 'include' );
                if ( $results["field_$include_field_id"] == 'true' )
                {
                    $entity_id = $results['id'];
                    $form_entities[$entity_id] = $results;
                }
            }
            // print_rr($form_entities);
            return $form_entities;
        }
    }
    
    public function get_statuses()
    {
        if ( isset( $this->data['entities_id'] ) )
        {        
            // print_rr($this->data);
            $statuses_entity_id = $this->get_entity_id( 'statuses');
            $default_field_id = $this->get_field_id( $statuses_entity_id, 'default' );
            $get_default_where = $title_where = '';
            if ( isset( $this->data['get_default'] ) && $this->data['get_default'] )
            {
                $get_default_where = "AND field_$default_field_id='true'";
                $this->get_default = true;
            };
            if ( isset( $this->data['title'] ) )
            {
                $title_field_id = $this->get_field_id( $statuses_entity_id, 'title' );
                $title_where = "AND field_$title_field_id='{$this->data['title']}'";
            }
            $sql = "SELECT * FROM app_entity_$statuses_entity_id WHERE FIND_IN_SET( {$this->data['entities_id']}, field_441 ) $get_default_where $title_where";
            // print_rr($sql);
            $user_query = db_query( $sql );
            $statuses = array();
            while ( $results = db_fetch_array( $user_query ) )
            {
                $statuses[$results['id']] = $results;
            }
            // print_rr($statuses);
            return $statuses;
        }
    }

    private function get_form_data()
    {
        if ( isset( $this->data['form_data'] ) )
        {
            // print_rr($this->data);
            $inputs = array();
            foreach ( $this->data['form_data'] as $index => $form_data )
            {
                $inputs[$form_data['name']] = $form_data['value'];

                if ( str_ends_with( $form_data['name'], '_url' ) && ! empty( $form_data['value'] ) )
                {
                    $url_parts = parse_url( $form_data['value'] );
                    if ( isset( $url_parts['query'] ) )
                    {
                        parse_str( $url_parts['query'], $query_params );
    
                        foreach ( $query_params as $key => $value )
                        {
                            $inputs[$key] = $value;
                        }
    
                        if ( isset( $query_params['action'] ) && isset( $query_params['id'] ) )
                        {
                            $inputs['process_id'] = $query_params['id'];
                            unset( $inputs['id'] );
                        }
                    }
                }
            }
            // print_rr($inputs);
            $this->data['inputs'] = $inputs;
        }
    }

    /**
     * adds any additional system items to the $this->items array
     */
    public function select2_filter()
    {
        global $app_action;

        // print_rr('in select2_filter function');
        // print_rr($this->items);
        switch ( $app_action )
        {
            case 'select2_json':
                $forms_entity_id = $this->get_field_entity_id( $this->data['field_id'] );
                $field_entity_id = $this->data['entity_id'];
                break;
            case 'select2_entities_filter':
                $forms_entity_id = $this->data['entity_id'];
                $field_entity_id = $this->data['entities_id'];
                break;
            default:
                die(print_rr('default app_action in core module select2_filter'));
                break;
        }
        $field_id = $this->data['field_id'];
        $forms_field_id = $this->get_field_id( $field_entity_id, 'forms' );
        $actions_field_id = $this->get_field_id( $field_entity_id, 'actions' );
        $system_status_field_id = $this->get_field_id( $field_entity_id, 'system status' );
        $admin_status_field_id = $this->get_field_id( $field_entity_id, 'admin status' );
        $this->get_form_data();
        $filter_id = $forms_entity_id;
        $filter_field_id = $forms_field_id;
        if ( !empty( $actions_field_id )  )
        {
            $process_id = $this->data['inputs']['process_id'];
            $filter_id = $process_id; // dependent on whether we are using forms_field_id or actions_field_id sql 
            $filter_field_id = $actions_field_id; // dependent on whether we are using forms_field_id or actions_field_id sql 
            // print_rr("process id is $process_id - filter id is $filter_id - filter field id is $filter_field_id");
        }  
        $where_sql_and = $where_sql_or = array();
        // print_rr("forms entity id is $forms_entity_id - forms status field id is $forms_field_id");
        // print_rr("action process id is $process_id actions field id is $actions_field_id");
        // print_rr("system status field id is $system_status_field_id");
        // print_rr("admin status field id is $admin_status_field_id");
        // print_rr($this->data);        
        // $actions_field_sql = ( empty( $actions_field_id ) ) ? "" : "OR FIND_IN_SET( $process_id, field_$actions_field_id )";
        // $find_in_set_sql = ( empty( $actions_field_id ) ) ? "FIND_IN_SET( $forms_entity_id, field_$forms_field_id )" : "FIND_IN_SET( $process_id, field_$actions_field_id )";
        // $system_status_sql = ( empty( $system_status_field_id ) ) ? "" : "AND field_$system_status_field_id='true'";
        // $admin_status_sql = ( empty( $admin_status_field_id ) ) ? "" : ( ( true ) ? "" : "AND field_$admin_status_field_id='true'" );
        // $sql = "SELECT * FROM app_entity_$field_entity_id WHERE $find_in_set_sql $system_status_sql $admin_status_sql";
        // if ( !empty( $actions_field_id ) ) $where_sql_and[] = "FIND_IN_SET( $process_id, field_$actions_field_id )";
        // $where_sql_and[] = ( empty( $actions_field_id ) ) ? "FIND_IN_SET( $forms_entity_id, field_$forms_field_id )" : "FIND_IN_SET( $process_id, field_$actions_field_id )";
        if ( $forms_field_id > 0 )
        {
            $where_sql_and[] = "FIND_IN_SET( $forms_entity_id, field_$forms_field_id )";
        }
        if ( $actions_field_id > 0 )
        {
            $sql = "FIND_IN_SET( $process_id, field_$actions_field_id )";
            if ( empty( $where_sql_and ) )
            {
                array_push( $where_sql_and, $sql );
            } 
            else
            {
                $where_sql_or = $where_sql_and;
                unset( $where_sql_and );
                array_push( $where_sql_or, $sql );
            }
        }
        if ( !empty( $system_status_field_id ) ) $where_sql_or[] = "field_$system_status_field_id='true'";
        // if ( !empty( $admin_status_field_id ) ) $where_sql_or[] = "field_$admin_status_field_id='true'";
        // print_rr($where_sql_and);
        // print_rr($where_sql_or);
        if ( !empty( $where_sql_or ) )
        {
            $where_and = ( empty( $where_sql_and ) ) ? '' : implode( ' AND ', $where_sql_and );            
            $where_or = ( empty( $where_and ) ) ? implode( ' OR ', $where_sql_or ) : 'AND ' . implode( ' OR ', $where_sql_or );
            // $where_or = implode( ' OR ', $where_sql_or );
            // $sql = "SELECT * FROM app_entity_$field_entity_id WHERE $where_and AND ( $where_or )";
            $sql = "SELECT * FROM app_entity_$field_entity_id WHERE $where_and $where_or";
            // print_rr($sql);
            $user_query = db_query( $sql );
            while ( $results = db_fetch_array( $user_query ) )
            {
                $this->items[$results['id']] = $results;
            }
            // die(print_rr($this->items));

        }
        $this->data['field_entity_id'] = $field_entity_id;
        // $this->data['filter_id'] = $forms_entity_id;
        $this->data['filter_id'] = $filter_id;
        // $this->data['filter_field_id'] = $forms_field_id; 
        $this->data['filter_field_id'] = $filter_field_id; 
        // print_rr($this->items);
        echo $this->get_select2_options();              
    }

    public function select2_statuses_filter()
    {
        global $app_action;

        // print_rr('in select2_statuses_filter function'); 
        switch ( $app_action )
        {
            case 'select2_json':
                $forms_entity_id = $this->get_field_entity_id( $this->data['field_id'] );
                $field_entity_id = $this->data['entity_id'];
                break;
            case 'select2_entities_filter':
                $forms_entity_id = $this->data['entity_id'];
                $field_entity_id = $this->data['entities_id'];
                break;
            default:
                die(print_rr('default app_action in core module select2_statuses_filter'));
                break;
        }
        $field_id = $this->data['field_id'];
        // $this->data['heading_field_id'] = \fields::get_heading_id( $field_entity_id );
        $forms_field_id = $this->get_field_id( $field_entity_id, 'forms' );
        $system_status_field_id = $this->get_field_id( $field_entity_id, 'system status' );
        $this->get_form_data();
        // print_rr($this->items);
        $this->data['sql'] = "SELECT * FROM app_entity_$field_entity_id WHERE FIND_IN_SET( $forms_entity_id, field_$forms_field_id ) AND field_443='true'";
        $this->data['field_entity_id'] = $field_entity_id;
        $this->data['filter_entity_id'] = $forms_entity_id;
        $this->data['filter_field_id'] = $forms_field_id;
        // print_rr($this->data);
        // switch ( $this->data['form_type'] )
        // {
        //     case 'items/processes':
        //         // print_rr("process form_type in core module select2_statuses_filter function");
        //         if ( isset( $this->data['inputs']['process_id'] ) )
        //         {
        //             $process_id = $this->data['inputs']['process_id'];
        //             $actions_field_id = $this->get_field_id( $field_entity_id, 'actions' );
        //             if ( $field_entity_id == 26 )
        //             {
        //                 $sql = $status_sql;
        //             }
        //             else
        //             {
        //                 $sql = "SELECT * FROM app_entity_$field_entity_id WHERE FIND_IN_SET( $process_id, field_$actions_field_id )";
        //             }
        //         }
        //         break;
        //     default:
        //         // print_rr('default form_type in core module select2_statuses_filter function');
        //         $sql = $status_sql;
        //         break;
        // }
        // print_rr($sql);  
        echo $this->get_select2_options();
    }

    public function select2_statuses_filter_()
    {
        global $app_action;

        // print_rr('in select2_statuses_filter function'); 
        switch ( $app_action )
        {
            case 'select2_json':
                $forms_entity_id = $this->get_field_entity_id( $this->data['field_id'] );
                $filter_entity_id = $this->data['entity_id'];
                break;
            case 'select2_entities_filter':
                $forms_entity_id = $this->data['entity_id'];
                $filter_entity_id = $this->data['entities_id'];
                break;
            default:
                die(print_rr('default app_action in core module select2_statuses_filter'));
                break;
        }
        $field_id = $this->data['field_id'];
        $heading_field_id = \fields::get_heading_id( $filter_entity_id );
        $forms_field_id = $this->get_field_id( $filter_entity_id, 'forms' );
        $system_status_field_id = $this->get_field_id( $filter_entity_id, 'system status' );
        $this->get_form_data();
        // print_rr($this->data);
        $status_sql = "SELECT * FROM app_entity_$filter_entity_id WHERE FIND_IN_SET( $forms_entity_id, field_$forms_field_id )";
        switch ( $this->data['form_type'] )
        {
            case 'items/processes':
                // print_rr("process form_type in core module select2_statuses_filter function");
                if ( isset( $this->data['inputs']['process_id'] ) )
                {
                    $process_id = $this->data['inputs']['process_id'];
                    $actions_field_id = $this->get_field_id( $filter_entity_id, 'actions' );
                    if ( $filter_entity_id == 26 )
                    {
                        $sql = $status_sql;
                    }
                    else
                    {
                        $sql = "SELECT * FROM app_entity_$filter_entity_id WHERE FIND_IN_SET( $process_id, field_$actions_field_id )";
                    }
                }
                break;
            default:
                // print_rr('default form_type in core module select2_statuses_filter function');
                $sql = $status_sql;
                break;
        }
        if ( $this->data['entity_id'] == 53 )
        {
            $sql = "SELECT * FROM app_entity_53";
        }
        // print_rr($sql);  
        $user_query = db_query( $sql );
        while ( $results = db_fetch_array( $user_query ) )
        {
            $this->items[$results['id']] = $results;
        }
        uasort( $this->items, function ( $a, $b ) use ( $heading_field_id )
        {
            return strcmp( $a["field_$heading_field_id"], $b["field_$heading_field_id"] );
        });
        $options = array();
        foreach ( $this->items as $items_id => $item )
        {
            $heading_value = \items::get_heading_field_value( $heading_field_id, $item );
            $item['heading'] = $heading_value;
            $option = array( 'id' => $items_id, 'text' => $heading_value, 'html' => '<div>' . $heading_value . '</div>' );
            if ( $this->get_default ) $option['field_id'] = $field_id;
            $options[] = $option;
        }
        // $default_field_id = $this->get_field_id( $filter_entity_id, 'forms' );
        // $get_default_where = '';
        // if ( isset( $this->data['get_default'] ) && $this->data['get_default'] )
        // {
        //     $get_default_where = "AND field_$default_field_id='true'";
        //     $this->get_default = true;
        // };
        // while ( $results = db_fetch_array( $user_query ) )
        // {
        //     $heading_value = \items::get_heading_field_value( $heading_field_id, $results );
        //     $results['heading'] = $heading_value;
        //     $option = array( 'id' => $results['id'], 'text' => $heading_value, 'html' => '<div>' . $heading_value . '</div>' );
        //     if ( $this->get_default ) $option['field_id'] = $field_id;
        //     $options[] = $option;
        // }
        // die(print_rr($options));
        if ( $this->get_default )
        {                
            $response = array( 'field_id' => $status_field_id, 'default' => $options );
        } 
        else
        {
            $response = array( 'results' => $options );
        }
        echo json_encode( $response );
    }

    protected function dialog_filter()
    {
        // print_rr('in dialog_filter function');  
        $this->get_form_data();
        // print_rr($this->items); print_rr($this->data); 
        if ( isset( $this->data['form_type'] ) )  
        {
            switch ( $this->data['form_type'] )
            {
                case 'items/processes':
                    // print_rr("process form_type in core module dialog_filter function");
                    if ( isset( $this->data['inputs']['form_url'] ) )
                    {
                        // print_rr($this->data['inputs']['form_url']);
                        $url_parts = parse_url( $this->data['inputs']['form_url'] );
                        parse_str( $url_parts['query'], $params );
                        // print_rr($params);
                        if ( isset( $params['action'] ) && isset( $params['id'] )  )
                        {
                            // print_rr($params);
                            $field_entity_id = $this->data['entity_id'];
                            $this->data['field_entity_id'] = $field_entity_id;
                            $this->data['filter_entity_id'] = $params['id'];
                            $this->data['filter_field_id'] = $this->get_field_id( $field_entity_id, 'actions' );
                        }
                    }
                    break;
                case 'items/form':
                    // print_rr('items/form form_type in core module dialog_filter function');
                    // print_rr($this->data);
                    $this->data['field_entity_id'] = $this->data['entity_id'];
                    break;
                default:
                    break;  
            }
            echo $this->get_select2_options();
        } 
    }

    protected function get_select2_options()
    {
        // print_rr("in get_select2_options function - field entity id {$this->data['field_entity_id']} - entities id {$this->data['entities_id']}");
        $entities_id = $this->data['entities_id'];
        // print_rr($this->items); print_rr($this->data);
        // if ( isset( $this->data['sql'] )  )
        // {
        //     $user_query = db_query( $this->data['sql'] );
        //     while ( $results = db_fetch_array( $user_query ) )
        //     {
        //         $this->items[$results['id']] = $results;
        //     }
        // }
        // print_rr($this->items);
        $heading_field_id = \fields::get_heading_id( $entities_id );
        uasort( $this->items, function ( $a, $b ) use ( $heading_field_id )
        {
            return strcmp( $a["field_$heading_field_id"], $b["field_$heading_field_id"] );
        });
        $options = array();
        foreach ( $this->items as $items_id => $item )
        {
            // print_rr($item); 
            // print_rr($this->data);
            if ( ( isset( $this->data['filter_id'] ) && isset( $this->data['filter_field_id'] ) ) && !in_array( $this->data['filter_id'], explode( ',', $item["field_{$this->data['filter_field_id']}"] ) ) ) continue;
            $heading_value = \items::get_heading_field_value( $heading_field_id, $item );
            $item['heading'] = $heading_value;
            // print_rr($this->data);
            if ( isset( $this->data['prepare_add_item'] ) ) 
            {
                $form_entity_id = $this->data['form_entity_id'];
                $items_id = "$entities_id-$items_id/$form_entity_id";
            }
            // print_rr($items_id);
            $option = array( 'id' => $items_id, 'text' => $heading_value, 'html' => '<div>' . $heading_value . '</div>' );
            if ( $this->get_default ) $option['field_id'] = $field_id;
            $options[] = $option;
        }
        if ( $this->get_default )
        {                
            $response = array( 'field_id' => $status_field_id, 'default' => $options );
        } 
        else
        {
            $response = array( 'results' => $options );
        }
        return json_encode( $response );        
    }

    public function set_ajax_field_default()
    {
        if ( isset( $this->data['field_id'] ) )
        {   
            $user_companies = $this->get_user_companies();
            $companies_users = $this->get_companies_users(); 
            $field_id = $this->data['field_id'];
            $form_entities_id = $this->data['entities_id'];
            $field_entities_id = $this->get_ajax_field_entities_id( $field_id );
            $heading_field_id = \fields::get_heading_id( $field_entities_id );
            if ( $field_entities_id == 1 )
            {
                $sql = "SELECT * FROM app_entity_1 WHERE id={$this->user_id}";
            }
            else
            {
                $default_field_id = $this->get_field_id( $field_entities_id, 'default' );
                $forms_field_id = $this->get_field_id( $field_entities_id, 'forms' );
                // print_rr("form entities id is $form_entities_id - field entities id is $field_entities_id"); print_rr("form field id $forms_field_id");
                $form_filter_sql = '';
                if ( !empty( $forms_field_id ) ) $form_filter_sql = "AND FIND_IN_SET( $form_entities_id, field_$forms_field_id)";
                $sql = "SELECT * FROM app_entity_{$field_entities_id} WHERE field_{$default_field_id}='true' AND FIND_IN_SET( created_by, '$companies_users' ) $form_filter_sql";
            }
            // die(print_rr($sql)); 
            // print_rr($field_id); print_rr($field_entities_id); print_rr($heading_field_id); print_rr($default_field_id);
            $user_query = db_query( $sql );  
            $items = array();          
            while ( $results = db_fetch_array( $user_query ) )
            {
                $items[$results['id']] = $results;
            }
            $options = array();
            foreach ( $items as $id => $item )
            {
                $heading_value = \items::get_heading_field_value( $heading_field_id, $item );
                $item['heading'] = $heading_value;
                $statuses[$id] = $item;
                $options[] = array( 'id' => $id, 'text' => $heading_value, 'field_id' => $field_id );
            }
            $response = array( 'field_id' => $field_id, 'default' => $options );
            echo json_encode( $response );           
        }
        else
        {
            echo '{"error":"in core set_ajax_field_default function"}';
        }
    }

    public function populate_fields()
    {
        if ( isset( $this->data['field'] ) )
        {
            $field_id = str_replace( "fields_", "", $this->data['field'] );
            // print_rr($field_id);
            if ( isset( $this->data['items_ids'] ) )
            {
                // print_rr($this->data);
                $this->data['entities_id'] = $this->get_field_entity_id( $field_id );
                $field_entities_id = $this->get_ajax_field_entities_id( $field_id );
                $form_entity_ajax_fields = $this->get_entity_fields( true );
                // print_rr($form_entity_ajax_fields);
                $fields = array();
                foreach ( $this->data['items_ids'] as $item_id )
                {
                    // print_rr($item_id);
                    $item_entiity_ajax_fields =$this->get_entity_fields( true, $field_entities_id );
                    // print_rr($item_entiity_ajax_fields);
                    $sql = "SELECT * FROM app_entity_{$field_entities_id} WHERE id=$item_id";
                    // print_rr($sql);
                    if ( $result = db_fetch_array( db_query( $sql ) ) )
                    {
                        // print_rr($result);
                        foreach ( $item_entiity_ajax_fields as $entities_id => $field_ids )
                        {
                            if ( in_array( $entities_id, array( 26 ) ) ) continue;
                            if ( is_array( $field_ids ) ) 
                            {
                                $field_values = array();
                                foreach ( $field_ids as $field_id )
                                {
                                    if ( !empty( $result["field_$field_id"] ) ) $field_values[] = $result["field_$field_id"]; 
                                }
                                $values = implode( ',', $field_values );
                            }  
                            else
                            { 
                                $values = $result["field_$field_ids"];
                            }                          
                            // print_rr($field_id);
                            if ( !empty( $values ) )
                            {
                                $sql = "SELECT * FROM app_entity_{$entities_id} WHERE id IN ( $values )";
                                $user_query = db_query( $sql );
                                $heading_field_id = \fields::get_heading_id( $entities_id );
                                while ( $results = db_fetch_array( $user_query ) )
                                {
                                    if ( isset( $form_entity_ajax_fields[$entities_id] ) )
                                    {
                                        // print_rr($results['id']); print_rr($results["field_$heading_field_id"]);
                                        $field_id = $form_entity_ajax_fields[$entities_id];
                                        if ( is_array( $field_id ) )
                                        {
                                            // print_rr('field id is an array'); print_rr($field_id);
                                        }
                                        else
                                        {
                                            // print_rr("field id is not an array $field_id");
                                            if ( isset( $form_entity_ajax_fields[$entities_id] ) ) $fields[$form_entity_ajax_fields[$entities_id]][$results['id']] = $results["field_$heading_field_id"];
                                        }
                                    }
                                }
                            }
                            
                        }
                    }
                }
                // print_rr($fields);
            }
        }
    }

    public function populate_job_fields()
    {
        if ( isset( $this->data['field_id'] ) )
        {
            if ( isset( $this->data['items_ids'] ) )
            {
                $jobs = new jobs( $this );
                $job_fields_info = $jobs->get_job_fields_info();
                // print_rr($job_fields_info);
                $data = json_encode( $job_fields_info, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
            }
            else
            {
                $data = '""';
            }
            echo '{"success":"in core module populate_job_fields function","data":' . $data . '}';                 
        }
        else
        {
            echo '{"error":"in core module populate_job_fields function"}';
        }
    }

    public function get_ajax_field_options( $obj )
    {
        // print_rr('in core module get_ajax_field_info function');  
        // print_rr($obj->data);
        // print_rr($obj->info);
        $entities_ids = $this->get_ajax_field_entities_id( $obj->data['entity_fields'] );
        foreach ( $obj->data['entity_fields'] as $field => $field_id )
        {
            // print_rr($field); print_rr($field_id); 
            if ( !empty( $obj->item["field_$field_id"] ) )
            {
                $entities_id = $entities_ids[$field_id];
                $sql = "SELECT * FROM app_entity_{$entities_id} WHERE id IN ( {$obj->item["field_$field_id"]} )";
                $user_query = db_query( $sql );
                while ( $results = db_fetch_array( $user_query ) )
                {
                    $status_field_id = ( $entities_id == 1 ) ? 5 : $this->get_field_id( $entities_id, 'status' );
                    // print_rr("entities_id is $entities_id - field is $field - field_id is $field_id");
                    if ( in_array( $results["field_$status_field_id"], array( '', 0, 1 ) ) ) 
                    {
                        $heading_field_id = \fields::get_heading_id( $entities_id );
                        $key = ( isset( $obj->data['use_field_name'] ) && $obj->data['use_field_name'] ) ? $field : $field_id;                        
                        $obj->info['fields'][$key][$results['id']] = $results["field_$heading_field_id"];   
                    }
                }             
            }
        } 
        // print_rr($obj->info);
    }

    public function get_module_info( $path = false )
    {
        $file_path = ( $path ) ? $path : $this->path;
        $module_info = json_decode( file_get_contents( $file_path . 'module.json' ) );
        return $module_info;         
    }

    private function get_module_config()
    {
        $default_icon = 'fa-cog';
        $proposed_config = array(
            'module' => $this->name,
            'notes' => '',
            'source' => array(
                'branch' => '',
                'commit' => array(
                    'sha' => '',
                    'date' => '',
                    'url' => ''
                )
            ),
            'token' => '',
            'token_expiry' => '',
            'access' => array(
                'admin' => array(
                    'groups' => '',
                    'users' => ''
                ),
                'syatem_admin' => array(
                    'groups' => '',
                    'users' => ''
                ),
                'user' => array(
                    'groups' => '',
                    'users' => ''
                )
            ),
            'menus' => array(
                'all_menus' => 0,
                'icon' => $default_icon,
                'sidebar' => array(
                    'show' => 1,
                    'icon' => $default_icon,
                    'link_text' => $this->get_name( 'ucfirst' ),
                    'link_url' => $this->app_path . 'index',
                ),
                'header' => array(
                    'show' => 0,
                    'icon' => $default_icon,
                    'link_text' => $this->get_name( 'ucfirst' ),
                    'link_url' => $this->app_path . 'index',
                ),
                'user' => array(
                    'show' => 0,
                    'icon' => $default_icon,
                    'link_text' => $this->get_name( 'ucfirst' ),
                    'link_url' => $this->app_path . 'index',
                )
            )
        );
        $this->cfg = 'CFG_MODULE_' . strtoupper( $this->name ) . '_CONFIG';
        if ( defined( $this->cfg ) )
        {
            $existing_config = json_decode( constant( $this->cfg ), true );
            // compare existing config with proposed config to pick up any new configuration
            $this->config = $updated_config = array_replace_recursive( $this->array_diff_recursive( $proposed_config, $existing_config ), $existing_config );
            // print_rr($updated_config);
            if ( true ) $this->set_module_config();
            return ( object ) $updated_config;
        }
        else
        {
            $this->config = $proposed_config;
            $this->set_module_config();
        }
    }

    protected function get_company_details( $data )
    {
        // print_rr($data);
        $companies_query = db_fetch_all( "app_entity_60" );
        $companies = array();
        while ( $results = db_fetch_array( $companies_query ) )
        {
            $companies[$results['id']] = $results;
        }
        $job_companies = empty( $data['company_ids'] ) ? array() : explode( ',', $data['company_ids'] );
        // print_rr($job_companies);
        $html = '';
        foreach ( $job_companies as $company_id )
        {
            $company = $companies[$company_id];
            // print_rr($company);
            switch ( $data['setting'] )
            {

                case 'job_card_header':
                    $field = 'field_1132';
                    break;
                case 'job_card_footer':
                    $field = 'field_1135';
                    break;
                default:
                    break;
            }
            if ( $company['field_1139'] && empty( $company[$field] ) ) 
            {
                $parent_company_id = $company['field_1128'];
                if ( !empty( $parent_company_id ) )     
                {
                    $parent_company = $companies[$parent_company_id];
                    $html .= <<<HEADER
                    <div id="job_card_header" class="job-card">{$parent_company[$field]}</div>
                    HEADER;
                }
            }
            else
            {
                $html .= <<<HEADER
                <div id="job_card_header" class="job-card">{$company[$field]}</div>
                HEADER;
                // print_rr($header_html);
            }
        }
        return $html;
    }

    protected function get_export_template_html( $templates_id )
    {
        $current_path = "{$this->items_info['entities_id']}-{$this->items_info['items_id']}";
        return \export_templates::get_html( $this->items_info['entities_id'], $this->items_info['items_id'], $templates_id );
    }

    protected function get_item_comments()
    {
        return $this->get_export_template_html( 7 );
    }

    public function get_reports_info()
    {
        if ( isset( $this->data['reports_id'] ) )
        {
            $sql = "SELECT * FROM app_reports WHERE id={$this->data['reports_id']}";
            if ( $results = db_fetch_array( db_query( $sql ) ) )
            {
                echo '{"success":"in core module get_reports_info function","data":' . json_encode( $results ) . '}';
            }
            else
            {
                echo '{"error":"in core module get_reports_info function","data":"No report found"}';
            }
        }
    }

    private function set_module_config()
    {
        \configuration::set( $this->cfg, json_encode( $this->config ) );      
    }

    public function array_diff_recursive( $array1, $array2 ) 
    {
        $diff = array();
        foreach ( $array1 as $key => $value ) 
        {
            if ( is_array( $value ) ) 
            {
                if ( !isset( $array2[$key] ) || !is_array( $array2[$key] ) ) 
                {
                    $diff[$key] = $value;
                } 
                else 
                {
                    $new_diff = $this->array_diff_recursive( $value, $array2[$key] );
                    if ( !empty( $new_diff ) )  
                    {
                        $diff[$key] = $new_diff;
                    }
                }
            } 
            else if ( !array_key_exists( $key, $array2 ) || $array2[$key] !== $value ) 
            {
                $diff[$key] = $value;
            }
        }
        return $diff;
    }
    
    public function module_management()
    {
        global $this_plugin;

        $installed_modules = '';
        foreach ( $this_plugin->get_modules( false ) as $module_name => $module )
        {
            // print_rr($module_name);
            $set_token = false;
            $download = $reinstall = '';
            $version = $module['info']['version'];
            if ( isset( $module['info']['source'] ) )
            {
                $source = $module['info']['source'];
                $release_url = 'https://api.github.com/repos/' . $source . '/zipball/v' . $version;
                $token = ( isset( $module['config']['token'] ) && !empty( $module['config']['token'] ) ) ? $module['config']['token'] : '';
                $private = ( isset( $module['info']['private'] ) ) ? 1 : 0;
                if ( $module_name != 'core' && $private && empty( $token ) ) $set_token = true;
                $download = <<<DOWNLOAD
                    <a class="action" data-action="download" data-module="$module_name" onclick="core.files( this )"><i class="fa fa-download"></i></a>
                DOWNLOAD;
                $download = ( $set_token ) ? '<span class="install-warning">Module is set to private but no source token has been set</span>' : $download;
                // $download = ( $module['name'] == 'core' ) ? '' : $download_link;
                $reinstall = <<<REINSTALL
                    <a class="action" data-action="reinstall" data-module="$module_name" onclick="core.files( this )"><i class="fa fa-refresh" aria-hidden="true"></i></a>
                REINSTALL;
                $branches = select_tag( "module_branches_$module_name", array( 0 => '' ), 0, array( 'class' => 'module_branches', 'size' => 'small', 'style' => 'margin-left: 20px;', 'data-source_token' => $token ) );
                $latest_branch_commit = <<<LATEST_COMMIT
                    <a class="action" name="latest_branch_$module_name" id="latest_branch_$module_name" data-action="latest_branch_commit" data-module="$module_name" onclick="core.files( this )"><i class="fa fa-code-fork" aria-hidden="true"></i></a>                
                LATEST_COMMIT;
                $file_actions = $download;
                if ( $set_token )
                {
                    $file_actions = '<span class="install-warning">Module is set to private but no source token has been set</span>';
                }
                else
                {
                    $file_actions .= ( $this->check_link_files() ) ? '' : $reinstall . $branches . $latest_branch_commit;
                }   
                // $reinstall = ( $module['name'] == 'core' ) ? '' : $this->get_reinstall_link( $module['name'] );
                // $reinstall = ( $module['name'] == 'core' ) ? '' : $reinstall_link;
                // $latest_version = '<a href="open_dialog( `https://unicloud.co.nz` )" style="color: red;">Version 1.0.1 Available</a>';
                $module_index_url = url_for( $module['app_path'] . 'index' );
                $installed_branch = ( isset( $module['config']['source']['branch'] ) ) ? '<div id="' . $module_name . '_module_branch_info" data-module="' . $module['config']['source']['branch'] . '">Branch Installed: <a class="branch-info-highlight">' . $module['config']['source']['branch'] . '</a></div>' : '';                
                $commit_info = ( isset( $module['config']['source']['commit']['sha'] ) && ( isset( $module['config']['source']['commit']['date'] ) && isset( $module['config']['source']['commit']['url'] ) ) ) ? '<div id="' . $module_name . '_module_commit_info" data-commit_date="' . $module['config']['source']['commit']['date'] . '"><span>Date: <a class="branch-info-highlight">' . $this->format_date_string( $module['config']['source']['commit']['date'] ) . '</a></span> <span>Commit: <a href="' . $module['config']['source']['commit']['url'] . '">' . $module['config']['source']['commit']['sha'] . '</a></span></div>' : '';
                $installed_module_info = <<<MODULE_INFO
                    <span class="installed_modules" name="installed_module_$module_name" id="installed_module_$module_name" data-module="$module_name" data-installed_version="$version" data-source="$source" data-release_url="$release_url" data-private="$private" data-source_token="$token"><a href="$module_index_url">{$module['info']['title']}</a></span>   
                    <span class="module-version">v$version</span>
                    $file_actions
                    <div class="module-description">{$module['info']['description']}</div>
                    $installed_branch
                    $commit_info
                MODULE_INFO;
                $installed_modules .= '
                    <li>
                        <div>
                            ' . $installed_module_info . '
                        </div>
                    </li>
                ';
            }
        }
        $script = $this->get_source_script();
        $html = <<<HTML
        <h5>Installed Modules</h5>
        <div id="installed_modules">
            <ul>
                $installed_modules
            </ul>
        </div>
        <script>
            $script
        </script>
        <style>
            .module-name {
                display: inline-block;
                padding-right: 10px;
                font-weight: bold;
                font-size: 1.1em;
            }
            .module-description {
                font-size: 1em;
            }
            .install-info, .install-warning {
                color: red;
            }
            .install-info {
                display: none;
                cursor: pointer;
            }
            .branch-info-highlight {
                text-decoration: none !important;
            }
        </style>
        HTML;        
        return $html;
    }

    protected function format_date_string( $date_string )
    {
        $date = new \DateTime( $date_string ); 
        return $date->format( CFG_APP_DATETIME_FORMAT );
    }

    private function check_link_files()
    {
        $is_link = false;
        $module_name = $this->get_name();
        $is_link_file = PLUGIN_PATH . 'modules/' . $module_name;
        // print_rr($is_link_file); print_rr("is link is $is_link"); print_rr($_SERVER['HTTP_HOST']);
        if ( is_link( $is_link_file ) && $_SERVER['HTTP_HOST'] == 'localhost' ) $is_link = true;
        return $is_link;  
    }

    public function get_reinstall_link() : string
    {
        // die(print_rr($this));
        $module_name = $this->get_name();
        $source = $this->get_info()->source;
        $version = ( $this->get_name() == 'core' ) ? PLUGIN_VERSION : $this->get_info()->version;
        $file_url = 'https://api.github.com/repos/' . $this->get_info()->source . '/zipball/v' . $version;
        $private = ( isset( $this->get_info()->token ) ) ? 1 : 0;
        $link = <<<LINK
            <a class="action" data-action="reinstall" data-module="$module_name" data-source="$source" data-file_url="$file_url" data-private="$private" onclick="core.files( this )"><i class="fa fa-refresh" aria-hidden="true"></i></a>
        LINK;
        $is_link_dir = PLUGIN_PATH . 'modules/' . $module_name;
        if ( is_link( $is_link_dir ) ) $link = '';
        return $link;
    }

    public function get_source_script()
    {      
        $core_token = $this->config->token;
        $script = sprintf( <<<SCRIPT
        let modules = $( `.installed_modules` );
        let get_branches = function( response, module_name, private, source ) {
            // console.log(response,module_name,source)
            $.each( response, function( index, branch ) {
                let branch_name = branch.name
                let branch_commit_callback = function( response ) {
                    // let commit_date = core.format_date_string( response.commit.author.date );
                    let commit_date = response.commit.author.date;
                    let sha = branch.commit.sha;
                    // let branch_zip_url = 'https://github.com/' + source + '/archive/refs/heads/' + branch_name + '.zip'
                    let branch_zip_url = 'https://codeload.github.com/' + source + '/zip/refs/heads/' + branch_name;
                    let commit_url = branch.commit.url;
                    $( '#module_branches_' + module_name ).append( '<option value="' + branch_name + '" data-module="' + module_name + '" data-branch_zip_url="' + branch_zip_url + '" data-commit_sha="' + sha + '" data-commit_date="' + commit_date + '" data-commit_url="' + commit_url +'">' + branch_name + '</option>' );
                }
                let branch_commit_url = core.repos_url + source + '/commits/' + branch_name
                let module_token = $( '#installed_module_' + module_name ).data( 'source_token' );
                if ( module_name === 'core' ) {
                    module_token = '$core_token';
                }
                core.ajax_headers = module_token ? {'Authorization': 'Bearer ' + module_token} : {};
                // console.log(branch_commit_url,module_name,module_token,private)
                core.ajax_get( branch_commit_url, branch_commit_callback )
            })    
        }
        $.each( modules, function( index, element ) {
            let module = $( element );
            let module_name = module.data( 'module' );
            let source = module.data( 'source' );
            let url = core.repos_url + source + "/branches";
            let module_token = module.data( 'source_token' );
            let private = module.data( 'private' );
            if ( module_name !== 'core' && private && module_token == '' ) {
                $( '#latest_branch_' + module_name ).after( '<span class="install-warning">%s</span>' );
                return;
            }
            if ( module_name === 'core' ) {
                module_token = '$core_token';
            }
            core.ajax_headers = module_token ? {'Authorization': 'Bearer ' + module_token} : {};
            // console.log(url,core.ajax_headers)
            let callback = function( response ) {
                get_branches( response, module_name, private, source );
                $( '#module_branches_' + module_name ).on( 'change', function() {
                    let branch_install_ele = $( `#latest_branch_` + module_name );
                    $( '.install-msg' ).remove();
                    let selected_branch = $( this ).find( ':selected' )
                    let module = selected_branch.data( 'module' )
                    let branch = selected_branch.val()
                    let branch_commit_sha = selected_branch.data( 'commit_sha' )
                    let branch_commit_date = selected_branch.data( 'commit_date' )
                    let branch_commit_url = selected_branch.data( 'commit_url' )
                    let commit_info = $( `#` + module + `_module_commit_info` )
                    let installed_branch = $( `#` + module + `_module_branch_info` ).data( 'module' )
                    let installed_commit_date = commit_info.data( 'commit_date' )
                    if ( branch == installed_branch ) {
                        if ( branch_commit_date == installed_commit_date ) {
                            message = 'Currently installed branch and commit are the same as remote branch';
                        } else {
                            message = 'Currently installed branch will be overwritten with commit <a href="' + branch_commit_url + '">' + branch_commit_sha + '</a> dated ' + core.format_date_string( branch_commit_date );
                        }        
                        branch_install_ele.after( '<div class="install-msg">' + message + '</div>' );
                        console.log(message)   
                        // console.log(module,branch,branch_commit_date,installed_branch,installed_commit_date)
                    }
                    
                })
            }
            core.ajax_get( url, callback )
        });
        SCRIPT,
        TEXT_PRIVATE_MODULE_TOKEN_SET
        );
        return $script;
    }

    public function get_date_string( $date )
    {
        date_default_timezone_set( CFG_APP_TIMEZONE );
        $timestamp = ( empty( $date ) ) ? null : date( CFG_APP_DATETIME_FORMAT, $date );
        return $timestamp;
    }

    public function format_date( $date, $format = CFG_APP_DATE_FORMAT )
    {
        if ( is_numeric( $date ) ) $date = $this->get_date_string( $date );
        return ( new \DateTime( $date, new \DateTimeZone( CFG_APP_TIMEZONE ) ) )->format( $format );
    }

    public function get_date_obj( $timestamp = false )
    {
        $date_obj = ( $timestamp ) ? new \DateTime( date( CFG_APP_DATETIME_FORMAT, $timestamp ) ) : new \DateTime();
        return $date_obj;
    }

    public function get_date_from_timestamp( $timestamp )
    {
        $date_obj = $this->get_date_obj( $timestamp );
        $date = array(
            'obj' => $date_obj,
            'timestamp' => $timestamp,
            'date' => $date_obj->format( CFG_APP_DATETIME_FORMAT ),
        );
        return $date;  
    }

    public function run_process()
    {
        // error_reporting( E_ALL );
        // ini_set( 'display_errors', 1 );
        // ini_set( 'display_startup_errors', 1 );
        // ini_set( 'error_reporting', E_ALL ); 
        // print_rr($this->data);
        if ( isset( $this->data['process_id'] ) )
        {
            // TODO get user with username public (hard coded to 9 for now) or fall back to system administrator user id 1
            $user_id = ( $this->user_id > 0 ) ? $this->user_id : 9;
            $this->set_app_user( $user_id );
            //check if the process exists and is active
            $sql = "SELECT * FROM app_ext_processes WHERE id='" . db_input( $this->data['process_id']) . "' and is_active=1";
            if ( !$app_process_info = db_fetch_array( db_query( $sql ) ) ) redirect_to( 'dashboard/page_not_found' );
            if ( isset( $this->data['selected_items'] ) )
            {
                die(print_rr('selected items'));
                $reports_id = ( isset( $_POST['reports_id'] ) ) ? $_POST['reports_id'] : 0;
                if ( $reports_id > 0 )
                {
                    // need to work on this some more with testing 
                    return;
                    $processes = new \processes( $app_process_info['entities_id'] );
                    $processes->run( $app_process_info, $reports_id );
                }
            }
            else
            {
                /**
                 * TODO pre run check - need module and function to run
                 * for user creation that email address doesn't already exist 
                 */
                if ( isset( $this->data['path'] ) && !empty( $this->data['path'] ) )
                {
                    // print_rr($this->data); die(print_rr('run process'));
                    $items_info = $this->get_items_info( $this->data['path'] );
                    // print_rr($items_info);
                    $current_entity_id = $items_info['entities_id'];
                    if ( !empty( $items_info ) ) $current_item_id = $items_info['items_id'];
                }
                // $current_entity_id = 1; // $this->data['items_info']['entity_id'];
                // $current_item_id = 35; //$this->data['items_info']['items_id'];
                $_POST = $this->data;
                $processes = new \processes( $current_entity_id );
                // $processes->items_id = $current_item_id;
                // print_rr($app_process_info);
                $processes->run( $app_process_info );
                // print_rr("running as user id $user_id"); print_rr($this->data); die(print_rr('run process'));
            }   
            // if ( $redirect ) redirect_to( $this->set_app_redirect_to() );
        }    
    }

    public function check_email()
    {
        // print_rr('in core module check_email function');
        $existing = 'false';
        // print_rr($this->data);
        if ( isset( $this->data['email'] ) && !empty( $this->data['email'] ) )
        {
            $email = db_input( $this->data['email'] );
            $sql = "SELECT * FROM app_entity_1 WHERE field_9='$email' AND field_5=1";
            // print_rr($sql);
            if ( $result = db_fetch_array( db_query( $sql ) ) )
            {
                // print_rr($result);
                $existing = 'true';
            }
        }
        // print_rr("existing is $existing");
        echo $existing;
    }

    public function run_process_()
    {
        if ( isset( $_GET['path'] ) && isset( $_GET['id'] ) )
        {
            if ( !empty( $_GET['path'] ) )
            {
                // print_rr($_GET); die(print_rr('run process'));   
                $user_id = ( isset( $_POST['user_id'] ) ) ? $_POST['user_id'] : 2 ;
                \Antevasin\Plugin::set_app_user( $user_id );
                //check if the process exists and is active
                $sql = "select * from app_ext_processes where id='" . db_input( $_GET['id']) . "' and is_active=1";
                if ( !$app_process_info = db_fetch_array( db_query( $sql ) ) ) redirect_to( 'dashboard/page_not_found' );
                $app_send_to = array();
                $items_info = \Antevasin\Plugin::get_item_info_from_path( $_GET['path'] );
                process_data( array( "items_info" => $items_info, "data" => ( isset( $_POST['process_data'] ) ) ? $_POST['process_data'] : array() ) );
                $current_entity_id = $items_info['entity_id'];
                $current_item_id = $items_info['items_id'];
                $reports_id = ( isset( $_POST['reports_id'] ) ) ? $_POST['reports_id'] : 0;
                if ( $reports_id > 0 )
                {
                    $processes = new \processes( $app_process_info['entities_id'] );
                    $processes->run( $app_process_info, $reports_id );
                }
                else
                {
                    $processes = new \processes( $current_entity_id );
                    $processes->items_id = $current_item_id;
                    // print_rr($app_process_info); print_rr($_POST); die(print_rr($_GET));
                    $processes->run( $app_process_info );
                }
                if ( isset( $_GET['form_id'] ) && isset( $_GET['token'] ) ) redirect_to( 'ext/public/form&action=success&id=' . $_GET['form_id'] . '&token=' . $_GET['token'] ); 	             
            }  
            else
            {
                
                die(print_rr('path is empty'));
            } 
        } 
    }

    public static function set_app_user( $user_id = false )
    {
        global $app_user, $app_logged_users_id;
        
        $user_id = ( $user_id ) ? $user_id : $app_logged_users_id;
        $where = ( is_int( $user_id ) ) ? 'e.id=' . db_input( $user_id ) : "field_12='$user_id'";
        $sql = "SELECT e.*, ag.name AS group_name FROM app_entity_1 e LEFT JOIN app_access_groups ag ON ag.id=e.field_6 WHERE $where AND e.field_5=1";
        $user_query = db_query( $sql );
        // print_rr($app_logged_users_id);
        if ( $user = db_fetch_array( $user_query ) )
        {
            if ( strlen( $user['field_10'] ) > 0 )
            {        
                $file = \attachments::parse_filename( $user['field_10'] );
                $photo = $file['file_sha1'];
            }
            else
            {
                $photo = '';
            }           
            $app_user = array(
                'id' => $user['id'],          
                'group_id' => (int) $user['field_6'],
                'group_name'=> $user['group_name'],
                'client_id' => $user['client_id'],
                'multiple_access_groups' => $user['multiple_access_groups'], 
                'name' => \users::output_heading_from_item( $user ),
                'username' => $user['field_12'],
                'email' => $user['field_9'],
                'is_email_verified' => $user['is_email_verified'],
                'photo' => $photo,
                'language' => $user['field_13'],
                'skin' => $user['field_14'],
                'fields' => $user,
            ); 
            $app_logged_users_id = $user['id'];
            if ( $app_logged_users_id > 0 ) return $app_logged_users_id;
        }
    }

    public function set_app_redirect_to( $module = false ) 
    {
        global $app_redirect_to;

        if ( isset( $this->data['redirect_to'] ) ) {
            $app_redirect_to = $this->data['redirect_to'];    
        }
        else if ( isset( $this->data['reports_id'] ) && is_numeric( $this->data['reports_id'] ) )
        {
            $app_redirect_to = "reports/view&reports_id={$this->data['reports_id']}";  
        }
        else {
            $app_redirect_to = 'dashboard/';
        }
        return $app_redirect_to;
    }

    public function get_entity_field_values( $path, $field_id, $db_value = '' )
    {
        if ( empty( $db_value ) )
        {
            // get value from database
            $db_value = 69;
        }
        $items_info = $this->get_items_info( $path );
        $field_config = $this->get_entity_field_config( $items_info['entities_id'], $field_id );
        $field_entity = $field_config['entity_id'];
        // print_rr($field_config);
        $heading_field_id = \fields::get_heading_id( $field_entity );
        $sql = "SELECT * FROM app_entity_{$field_entity} WHERE id IN ( $db_value )";
        // print_rr($sql);
        $query = db_query( $sql );
        $values = array();
        while ( $results = db_fetch_array( $query ) )
        {
            $values[$results['id']] = $results["field_$heading_field_id"];
        }
        // print_rr($values);
        return implode( ', ', $values );
    }

    public function get_entity_field_config( $entities_id, $field_id )
    {
        global $app_fields_cache;

        $field_info = $this->get_entity_field_info( $entities_id, $field_id );
        $field_config = json_decode( $field_info['configuration'], true );
        return $field_config;
    }

    public function get_entity_field_info( $entities_id, $field_id )
    {
        global $app_fields_cache;

        $field_info = $app_fields_cache[$entities_id][$field_id];
        return $field_info;
    }

    public function get_entities_info( $path )
    {
        $entities_info = array();
        $item_paths = explode( '/', $this->get_full_path( $path ) );
        foreach ( $item_paths as $index => $entity_path )
        {
            $path_info = explode( '-', $entity_path );
            if ( isset( $path_info[0] ) ) {
                $entity_id = $path_info[0];
                $entities_info[$entity_id] = array( 
                    'entity_id' => $entity_id,
                );
                $entities_info[$entity_id]['items_id'] = ( isset( $path_info[1] ) ) ? $path_info[1] : '';   
            }            
        }
        return $entities_info;
    }

    public function get_items_info( $path, $level = 0 )
    {
        if ( !empty( $path ) && strpos( $path, '-' ) )
        {
            $path_array = explode( '/', $this->get_full_path( $path ) );
            $item_info = explode( '-', $path_array[count( $path_array ) - ($level + 1)] );
            $entity_id = $item_info[0]; 
            $items_id =  ( isset( $item_info[1] ) ) ? $item_info[1] : ''; 
        }
        else 
        {
            $entity_id = $path; 
            $items_id = '';
        }
        $items_info = array( 'entities_id' => $entity_id, 'items_id' => $items_id ); 
        $this->items_info = $items_info;
        return $items_info;    
    }

    public function get_full_path( $path )
    {
        if ( strpos( $path, '-' ) )
        {
            $entity_paths = explode( '/', $path );            
            if ( count( $entity_paths ) > 1 )  
            {
                // check that path is valid
                if ( !$this->check_full_path( $path ) ) $path = 'error';
                return $path;
            }  
            else
            {
                $short_path_info = explode( '-', $path );
                $entity_id = $short_path_info[0];
                $items_id = $short_path_info[1];
                $path_info = \items::get_path_info( $entity_id, $items_id );                   
                $full_path = ( empty( $items_id ) ) ? str_replace( '-', '', $path_info['full_path'] ) : $path_info['full_path'];
                return $full_path;
            }
        }
        else
        {
            return $path;  
        }
    }

    public function check_full_path( $path )
    {
        // TODO if required
        $is_valid = true;        
        return $is_valid;
    }

    public function is_html( $text )
    {
        $processed = htmlentities( $text );
        if ( $processed == $text ) return false;
        return true; 
    }

    // public function user_access_tags(  $module_name, $entity = true, $access = '', $help_block = '', $show = true )
    public function user_access_tags()
    {
        // global $app_access_groups_cache, $app_users_cache;
        
        // $visibility = ( $show ) ? '': 'style="display: none;"';
        $help_block = 'Test help block';
        $groups = select_tag( 'module[access][admin][groups', array( 'option 1', 'options 2', 'option 3', 'option 1', 'options 2', 'option 3'  ), $this->get_config()->access->admin->groups, array( 'class'=>'form-control input-xlarge chosen-select', 'multiple'=>'multiple', 'style'=>'display: none;' ) );
        $users = select_tag( 'module[access][admin][users', array( 'option 1', 'options 2', 'option 3', 'option 1', 'options 2', 'option 3'  ), $this->get_config()->access->admin->users, array( 'class'=>'form-control input-xlarge chosen-select', 'multiple'=>'multiple', 'style'=>'display: none;' ) );
        $html = '
        <div class="form-group"><label class="col-md-3 control-label">Access Groups</label><div class="col-md-9">' . $groups . '</div></div>
        <div class="form-group"><label class="col-md-3 control-label">Users</label><div class="col-md-9">' . $users . '<span class="help-block">' . $help_block . '</span></div></div>
        
        ';
        // $html = '
        //     <div class="form-group user-access-tags">
        //         <label class="col-md-3 control-label">Access Groups</label>
        //         <div class="col-md-9">
        //             ' . select_tag( $access_groups_tag_id, $access_groups_choices, $access_groups_value, array( 'class'=>'form-control input-large chosen-select', 'multiple'=>'multiple', 'style'=>'display: none;' ) ) . '   	
        //         </div> 
        //     </div>
        //     <div class="form-group user-access-tags">
        //         <label class="col-md-3 control-label">Users</label>
        //         <div class="col-md-9">
        //             ' . select_tag( $users_tag_id, $users_choices, $users_value, array('class'=>'form-control input-xlarge chosen-select','multiple'=>'multiple', 'style'=>'display: none;' ) ) . '
        //             <span class="help-block">' . $help_block . '</span>
        //         </div>                         
        //     </div>
        //     <script>
        //         var av_plugin_name = "' . $antevasin_plugin->get_plugin_name() . '";
        //         $( function() {
        //             $( ' . $access_groups_ele_id . ' ).on( "change", function( event, action ) {
        //                 let element_id = this.id;
        //                 if ( element_id.endsWith( \'_admin_groups\' ) || element_id.endsWith( \'_manager_groups\' ) || element_id.endsWith( \'_user_groups\' ) ) {
        //                     // do nothing
        //                 } else {
        //                     // console.log( \'this was not the admin, manager or user group access\' );
        //                     let module_user_users_ele_id = ' . $module_user_users_ele_id . ';
        //                     let module_user_groups_ele_id = ' . $module_user_groups_ele_id . ';                   
        //                 }
        //             });
        //             $( ' . $access_users_ele_id . ').on( "change", function( event, action ) {
        //                 let element_id = this.id;
        //                 if ( element_id.endsWith( \'_admin_users\' ) || element_id.endsWith( \'_manager_users\' ) || element_id.endsWith( \'_user_users\' ) ) {
        //                     // do nothing
        //                 } else {                    
        //                     console.log( element_id + \'you clicked on the users\');
        //                     let module_user_users_ele_id = ' . $module_user_users_ele_id . ';
        //                     let module_user_groups_ele_id = ' . $module_user_groups_ele_id . ';                   
        //                 }
        //             });
        //             $(".av-user-access-tags").show();
        //         });

        //         function av_check_access( element_id ) {
        //             if ( element_id.endsWith( \'_admin_groups\' ) || element_id.endsWith( \'_manager_groups\' ) || element_id.endsWith( \'_user_groups\' ) ) {
        //                 return true;
        //             }               
        //             return false; 
        //         }
        //     </script>  
        //     <style>
        //         .user-access-tags {
        //             display: none;
        //         }
        //     </style>
        // ';
        return $html;
        die(print_rr('pause'));
        $access_groups_choices = $app_access_groups_cache;
        $access_groups_choices = array( 0 => 'Administrator' ) + $access_groups_choices;
        $users_query = db_query("SELECT u.id, COALESCE( a.name, 'Administrator' ) AS group_name FROM app_entity_1 u LEFT JOIN app_access_groups AS a ON a.id=u.field_6 WHERE u.field_5=1 ORDER BY u.field_8, u.field_7");
        while($users = db_fetch_array($users_query))
        {	
            $users_choices[$users['group_name']][$users['id']] = $app_users_cache[$users['id']]['name'];
        }
        $access_groups_value = '';
        $users_value = '';
        if ( $entity )
        {
            $access_groups_id = 285;
            $users_id = 286;
            $access_groups_tag_id =  'fields[' . $access_groups_id . '][]';
            $access_groups_ele_id = "'#fields_" . $access_groups_id . "'";
            $users_tag_id = 'fields[' . $users_id . '][]';
            $users_ele_id = "'#fields_" . $users_id . "'";
        }
        else
        {
            // get module configuration settings
            $settings = $antevasin_modules->{$module_name}->get_config();
            if ( !isset( $settings['module_access'][$access]['groups'] ) || !isset( $settings['module_access'][$access]['users'] ) )
            {
                $settings = array(
                    'module_access' => array(
                        $access => array(
                            'groups' => NULL,
                            'users' => NULL,
                            ),
                        )   
                    );
                $antevasin_modules->{$module_name}->add_setting( $settings );
            }
            $access_groups_tag_id =  'settings_module_' . $module_name . '[module_access][' . $access . '][groups][]';
            $access_groups_value = ( empty( $settings['module_access'][$access]['groups'] ) ) ? '' : $settings['module_access'][$access]['groups'];
            $access_users_ele_id = "'#settings_module_" . $module_name . "_module_access_" . $access . "_users'";
            $access_groups_ele_id = "'#settings_module_" . $module_name . "_module_access_" . $access . "_groups'";
            $users_tag_id = 'settings_module_' . $module_name . '[module_access][' . $access . '][users][]';
            $users_value = ( empty( $settings['module_access'][$access]['users'] ) ) ? '' : $settings['module_access'][$access]['users'];
            $users_ele_id = "'#settings_module_" . $module_name . "_module_access_" . $access . "_users'";
            $module_user_users_ele_id = "'#settings_module_" . $module_name . "_module_access_user_users'";
            $module_user_groups_ele_id = "'#settings_module_" . $module_name . "_module_access_user_groups'";
        }

    }

    public function get_user_reports_id( $entities_id )
    {
        $reports_id = 0;
        if ( $entities_id > 0 )
        {
            $sql = "SELECT * FROM `app_reports` WHERE entities_id={$entities_id} AND created_by={$this->user_id} AND reports_type='entity_menu'";
            if ( $results = db_fetch_array( db_query( $sql ) ) )
            {
                $reports_id = $results['id'];
            } 
        }
        return $reports_id;
    }

    public function system_log()
    {
        $sql_data = array(
            'date_added' => time(),
            'created_by' => 4,  
            'field_477' => ( empty( $this->data ) ) ? '' : json_encode( $this->data ), // data
            'field_478' => $this->system_log_data['type'], // type
            'field_479' => ( IS_AJAX ) ? true : false, // is ajax
            'field_480' => ( defined( 'IS_CRON' ) ) ? true : false, // is cron
            'field_481' => $this->system_log_data['module'], // module
            'field_482' => $this->system_log_data['function'], // function  
            'field_483' => $this->system_log_data['type_id'], // type id
        );
        $system_log_entity_id = $this->get_entity_id( 'system log' );
        db_perform( "app_entity_$system_log_entity_id", $sql_data );
        $log_item_id = db_insert_id();
    }

    public function on_create_ticket()
    {
        // print_rr('in on create ticket function');
        if ( isset( $this->data['items_id'] ) )
        {
            $entities_id = 23;
            $user_id_field = 'field_1461';
            $user_field_id = 1449;
            $items_id = $this->data['items_id'];
            $sql = "SELECT * FROM app_entity_$entities_id WHERE id='" . db_input( $items_id ) . "'";
            if ( $result = db_fetch_array( db_query( $sql ) ) )
            {
                $submitted_by_user_id = ( empty( $result[$user_id_field] ) ) ? $result['created_by'] : $result[$user_id_field];
                $this->choices_values( $entities_id, $items_id, $user_field_id, $submitted_by_user_id, true );
                // $this->choices_values( 23, $items_id, 183, 69, true );
            }
        }
        // die(print_rr('pause'));
    }

    protected function choices_values( $entities_id, $items_id, $field_id, $value, $update_entity = false )
    {
        //insert choices values for fields with multiple values
        global $app_fields_cache;

        $choices_values = new \choices_values( $entities_id );
        $options = array(
            'class' => $app_fields_cache[$entities_id][$field_id]['type'],
            'field' => array( 'id' => $field_id ),
            'value' => ( strlen( $value ) ? explode( ',', $value ) : '' )
        );
        $choices_values->prepare( $options );
        $choices_values->process( $items_id );
        if ( $update_entity )
        {
            $sql = "UPDATE app_entity_{$entities_id} SET field_{$field_id}='" . db_input( $value ) . "' WHERE id='" . db_input( $items_id ) . "'";
            db_query( $sql );
        }
    }

    public static function is_logged_on()
    {
        global $is_logged_on;

        $is_logged_on = false;
        // print_rr($_COOKIE);
        // $session_id = ( isset( $_COOKIE['sid'] ) ) ? $_COOKIE['sid'] : '';
        // print_rr("session id is $session_id");
        // session_id( $session_id );
        // if ( empty( $session_id ) ) session_start();
        // print_rr($_SESSION); 
        // print_rr(session_id());       
        // print_rr(session_name());
        // print_rr(session_decode($session_id));
        // $logged_users_id = ( isset( $_SESSION['app_logged_users_id'] ) ) ? $_SESSION['app_logged_users_id'] : 0;
        // print_rr("logged users id is $logged_users_id");
        // print_rr("app session is registered: " . app_session_is_registered('app_logged_users_id'));
        if ( isset( $_COOKIE['app_remember_user'] ) && isset( $_COOKIE['app_remember_pass'] ) )
        {
            $is_logged_on = true;
            $user_name = base64_decode( $_COOKIE["app_remember_user"] );
            self::set_app_user( $user_name );
        }
        // else if ( $logged_users_id > 0 )
        // {
        //     $is_logged_on = true;
        //     $user_name = self::get_app_user_name( $logged_users_id );
        // }
        // else
        // {
        //     // print_rr("app logged users id is 0 - $logged_users_id");            
        // }
        // if ( $is_logged_on ) self::set_app_user( $user_name );
        // print_rr("is logged on is $is_logged_on");
        return $is_logged_on;
    }

    public static function get_app_user_name( $user_id = false )
    {
        global $app_user, $app_logged_users_id;

        // print_rr($app_user); print_rr($app_logged_users_id);
        if ( !$user_id ) $user_id = $app_logged_users_id;
        if ( isset( $app_user['username'] ) && !empty( $app_user['username'] ) ) return $app_user['username'];
        if ( is_numeric( $user_id ) && $user_id > 0 )
        {
            $sql = "SELECT field_12 FROM app_entity_1 WHERE id=" . db_input( $user_id );
            if ( $result = db_fetch_array( db_query( $sql ) ) ) return $result['field_12'];
        }      
    }
}