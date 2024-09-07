<?php

namespace Antevasin;

class core implements module
{
    private $plugin_name;
    private $plugin_path;
    private $plugin_version;
    private $core_path;
    private $app_user;
    private $user_id;
    private $user_group_ids;
    private $is_system_admin = false;
    private $is_plugin_admin = false;
    private $is_module_user = false;
    private $user_settings;
    private $entities;
    private $data;
    private $name;
    private $title;
    private $path;
    private $app_path;
    private $info;
    private $cfg;
    private $config;
    private $index_tabs;

    public function __construct( $name = null )
    {
        global $app_user;

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
        if ( isset( $app_user ) )  
        {  
            $this->app_user = $app_user;
            $this->user_id = $app_user['id'];
            $this->user_group_ids = $this->get_user_group_ids();
            $this->is_plugin_admin();
            if ( !$this->is_module_user() ) redirect_to('dashboard/access_forbidden' );
            $this->get_user_settings();
            $this->set_error_reporting();
        }
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

    public function module_index_tabs( form $form )
    {
        if ( get_class( $this ) == 'Antevasin\core' )
        {
            $source = $this->get_info()->source;
            $file_url = 'https://api.github.com/repos/' . $this->get_info()->source . '/zipball/v' . $this->get_info()->version;
            $private = ( isset( $this->get_info()->token ) ) ? 1 : 0;
            $reinstall_link = $this->get_reinstall_link();
            $is_link_dir = PLUGIN_PATH . 'application_core.php';
            // print_rr($is_link_dir);
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
                        'label' => 'Plugin Path',
                        'field_class' => 'plugin-info',
                        'field' => PLUGIN_PATH
                    ),
                    array(     
                        'label' => 'Installed Version',
                        'field_class' => 'plugin-info',
                        'field' => PLUGIN_VERSION . '<a style="padding: 0 10px 0 10px;" data-action="download" data-module="core" data-source="' . $source . '" data-file_url="' . $file_url . '" data-private="' . $private . '" onclick="core.files( this )"><i class="fa fa-download"></i></a>' . $reinstall_link
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
            $sections = array(
                array(
                    'title' => 'Module Management',
                    'content' => $this->module_management()
                ),                    
            );
            array_unshift( $sections, $plugin_settings );
            $tabs =  array(
                'name' => 'plugin',
                'sections' => $sections
            );
            $this->set_index_tabs( array( $tabs ) ); 
        }
    }

    private function is_plugin_admin()
    {
        if ( $this->app_user['group_id'] === 0 )
        {
            $this->is_plugin_admin = true;
            $this->is_system_admin = true;
            $this->is_module_user = true;
        }
        if ( false )
        {
            // check plugin settings
            $this->is_plugin_admin = true;
            $this->is_module_user = true;
        }
        if ( \guest_login::is_guest() )
        {
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
                db_query( $sql );
            }
        }
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

    protected function get_entities()
    {
        global $app_entities_cache;
        
        $this->entities = $app_entities_cache;
        return $this->entities;
    }

    protected function get_entity_id( $entity_name )
    {
        $sql = "SELECT * FROM app_entities WHERE name='$entity_name'";
        if ( $result = db_fetch_array( db_query( $sql ) ) )
        {
            return $result['id'];
        }
    }

    protected function get_field_id( $entity_id, $field_name )
    {
        $sql = "SELECT * FROM app_fields WHERE name='$field_name' AND entities_id=$entity_id";
        if ( $result = db_fetch_array( db_query( $sql ) ) )
        {
            return $result['id'];
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

    // module functions
    public function get_module_info( $path = false )
    {
        $file_path = ( $path ) ? $path : $this->path;
        $module_info = json_decode( file_get_contents( $file_path . 'module.json' ) );
        return $module_info;         
    }

    private function get_module_config()
    {
        $this->cfg = 'CFG_MODULE_' . strtoupper( $this->name ) . '_CONFIG';
        if ( defined( $this->cfg ) )
        {
            return json_decode( constant( $this->cfg ) );
        }
        else
        {
            $default_icon = 'fa-cog';
            $this->config = array(
                'module' => $this->name,
                'notes' => '',
                'token' => '',
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
            $this->set_module_config();
        }
    }

    private function set_module_config()
    {
        \configuration::set( $this->cfg, json_encode( $this->config ) );      
    }

    public function module_management()
    {
        $modules = get_plugin_modules( PLUGIN_PATH );
        $installed_modules = '';
        foreach ( $modules as $module_name => $module )
        {
            // print_rr($module);
            $source = $module['info']['source'];
            $version = $module['info']['version'];
            $file_url = 'https://api.github.com/repos/' . $source . '/zipball/v' . $version;
            $token = ( isset( $module['config']['token'] ) && !empty( $module['config']['token'] ) ) ? $module['config']['token'] : '';
            $private = ( isset( $module['config']['token'] ) && !empty( $module['config']['token'] ) ) ? 1 : 0;
            $data_attributes = <<<DATA
                data-module="{$module['name']}" data-source="$source" data-file_url="$file_url" data-private="$private" data-source_token="$token"
            DATA;
            $module_info = <<<INFO
                <div class="module-info" id="module_{$module['name']}" data-action="install" data-path="{$module['path']}" data-version="{$version}" data-module="{$module['name']}" data-source="$source" data-file_url="$file_url" data-private=$private  onclick="core.files( this )">Install Version Test</div>
            INFO;
            $download_link = <<<DOWNLOAD
                <a style="padding: 0 10px 0 10px;" data-action="download" $data_attributes onclick="core.files( this )"><i class="fa fa-download"></i></a>
            DOWNLOAD;
            $download = ( $module['name'] == 'core' ) ? '' : $download_link;
            $reinstall_link = <<<REINSTALL
                <a class="action" data-action="reinstall" $data_attributes onclick="core.files( this )"><i class="fa fa-refresh" aria-hidden="true"></i></a>
            REINSTALL;
            // $reinstall = ( $module['name'] == 'core' ) ? '' : $this->get_reinstall_link( $module['name'] );
            $reinstall = ( $module['name'] == 'core' ) ? '' : $reinstall_link;
            // $latest_version = '<a href="open_dialog( `https://unicloud.co.nz` )" style="color: red;">Version 1.0.1 Available</a>';
            $installed_modules .= '
                <li>
                    <div>
                        <span class="module-name">' . $module['info']['title'] . '</span><span class="module-version">v' . $version . '</span>' . $download . $reinstall . '
                        <div class="module-description">' . $module['info']['description'] . '</div>
                        ' . $module_info . '
                    </div>
                </li>
            ';
        }
        // $script = $this->get_source_script();
        $html = <<<HTML
        <h5>Installed Modules</h5>
        <div id="installed_modules">
            <ul>
                $installed_modules
            </ul>
        </div>
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
            .module-latest {
                /* display: none; */
            }
            .install-link {
                color: red;
            }
        </style>
        <script type="module">
            import { Octokit, App } from "https://esm.sh/octokit";
            const octokit = new Octokit({
                auth: ''
            })
            const settings = {
                // owner: 'antevasin-app',
                // repo: 'module-hauora',
                headers: {
                    'Accept': 'application/vnd.github+json'
                }
            }
            const info = await octokit.request( `GET /repos/antevasin-app/module-hauora/releases/latest`, settings )
            if ( info.status == 200 ) {
                // console.log(info.data)
            }
        </script>
        HTML;        
        return $html;
    }

    public function get_reinstall_link() : string
    {
        // die(print_rr($this));
        $module_name = $this->get_name();
        $source = $this->get_info()->source;
        $file_url = 'https://api.github.com/repos/' . $this->get_info()->source . '/zipball/v' . $this->get_info()->version;
        $private = ( isset( $this->get_info()->token ) ) ? 1 : 0;
        $link = <<<LINK
            <a class="action" data-action="reinstall" data-module="$module_name" data-source="$source" data-file_url="$file_url" data-private="$private" onclick="core.files( this )"><i class="fa fa-refresh" aria-hidden="true"></i></a>
        LINK;
        // return '<a class="action" data-action="reinstall" data-module="' . $module_name . '" data-module="{$module['name']}" data-source="$source" data-file_url="$file_url" data-private="$private" onclick="core.files( this )"><i class="fa fa-refresh" aria-hidden="true"></i></a>'; 
        return $link;
    }

    public function get_source_script()
    {        
        $modules = ( $this->get_name() == 'core' ) ? 'let modules = $( `.module-info` )' : 'let modules = $( `#module_test` )';
        $script = <<<SCRIPT
        let modules = $( `.module-info` )
        $.each( modules, function( index, element ) {
            let module = $( element ).data( 'module' )
            let callback_test = function( response ) {
                // console.log(response.zipball_url)
                let installed_version = $( '.version' ).html()
                let latest_version = response.tag_name.split( 'v' )
                let update = ( installed_version.trim() == latest_version[1] ) ? false : true
                let zip_url = response.zipball_url
                let link = '<a data-module="' + module + '" data-action="install" data-file_url="' + zip_url + '" class="install-link action" onclick="core.install( this );">Install Version 1.0.1</a>'
                console.log(module,update,latest_version,zip_url,link)
                if ( update ) ( module == 'core' ) ? $( '#alert_plugin_settings' ).html( link ) : $( `#latest_` + module ).show().html( link )
            }
            let source = $( element ).data( 'source' )    
            let url = `https://api.github.com/repos/` + source + `/releases/latest`
            // let url = `https://api.github.com/repos/antevasin-app/module-template/releases/latest`
            // core.ajax_get( url, callback_test )
        })
        SCRIPT;
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
}

class core_old extends plugin
{   
    function __construct( $data = array() )
    {

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