<?php

namespace Antevasin;

class core implements module
{
    private $plugin_name;
    private $plugin_path;
    private $plugin_version;
    private $core_path;
    private $app_user;
    private $user_group_ids;
    private $user_settings;
    private $entities;
    private $name;
    private $title;
    private $path;
    private $app_path;
    private $info;
    private $cfg;
    private $config;
    private $index_tabs;
    
    protected $data;
    protected $user_id;

    public function __construct( $name = null )
    {
        global $app_user;

        // print_rr('core module class constructor');
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
        // print_rr("system admin is " . IS_SYSTEM_ADMIN .", plugin admin is " . IS_PLUGIN_ADMIN . ", module user is " . IS_MODULE_USER . ", plugin user id is " . PLUGIN_USER_ID);
        // die(print_rr('pause'));
        if ( !IS_MODULE_USER ) redirect_to( 'dashboard/access_forbidden' );
        if ( isset( $app_user ) )  
        {  
            $this->app_user = $app_user;
            $this->user_id = $app_user['id'];
            $this->user_group_ids = $this->get_user_group_ids();
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
                        'field' => PLUGIN_VERSION . '<a id="plugin_version" style="padding: 0 10px 0 10px;" data-action="download" data-module="core" data-version="' . PLUGIN_VERSION . '" data-source="' . $source . '" data-file_url="' . $file_url . '" data-private="' . $private . '" onclick="core.files( this )"><i class="fa fa-download"></i></a>' . $reinstall_link
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
            // array_unshift( $sections, $plugin_settings );
            $tabs =  array(
                'name' => 'plugin',
                'sections' => $sections
            );
            // $this->set_index_tabs( array( $tabs ) ); 
        }
    }

    private function is_plugin_admin_()
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
        if ( \guest_login::is_guest() )
        {
            $this->is_module_user = true;
        }
        return $this->is_plugin_admin;
    }

    public function is_system_admin_()
    {
        return $this->is_system_admin;
    } 

    public function is_module_user_()
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
            if ( IS_SYSTEM_ADMIN )
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
        if ( IS_SYSTEM_ADMIN && $this->user_settings['error_reporting'] )
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

    public function update_entities()
    {
        // print_rr('in update_entities function');
        $this->get_entities();
        $entities_entity_id = $this->get_entity_id( 'entities' );
        if ( $entities_entity_id > 0 )
        {
            // print_rr("entitities id is $entities_entity_id");
            $sql = "SELECT * FROM app_entity_{$entities_entity_id}";
            $user_query = db_query( $sql );
            // print_rr($sql); print_rr($user_query);
            $existing_entities = array();
            while ( $results = db_fetch_array( $user_query ) )
            {
                // print_rr($results);
                $entity_id = $results['id'];
                // $group_id = $this->entities[$entity_id]['group_id'];
                $existing_entities[$entity_id] = $results;
            }
            // print_rr($existing_entities);
            foreach ( $this->entities as $entity_id => $info )
            {
                if ( !isset( $existing_entities[$entity_id] ) )
                {
                    print_rr("add item to entity with id $entity_id");
                    $now = time();
                    $title_field_id = $this->get_field_id( $entities_entity_id, 'title' );
                    $include_field_id = $this->get_field_id( $entities_entity_id, 'include' );
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

    // module functions
    public function filter_statuses()
    {
        global $app_logged_users_id;

        if ( isset( $this->data['entities_id'] ) )
        {
            $heading_field_id = \fields::get_heading_id( 26 );
            $sql = "SELECT * FROM app_entity_26 WHERE FIND_IN_SET( {$this->data['entities_id']}, field_441 )";
            $statuses = $options = array();
            $user_query = db_query( $sql );
            while ( $results = db_fetch_array( $user_query ) )
            {
                $heading_value = \items::get_heading_field_value( $heading_field_id, $results );
                $results['heading'] = $heading_value;
                $statuses[$results['id']] = $results;
                $options[] = array( 'id' => $results['id'], 'text' => $heading_value, 'html' => '<div>' . $heading_value . '</div>' );
            }
            // print_rr($statuses);
            // print_rr($options);
            // $options = array( 
            //     array( 'id' => '69', 'text' => 'Spencer User', 'html' => '<div>Spencer User 1</div>' ),
            //     array( 'id' => '420', 'text' => 'Rosie User', 'html' => '<div>Rosie User 2</div>' ), 
            //     array( 'id' => '1', 'text' => 'Niamh User', 'html' => '<div>Niamh User 3</div>' ) 
            // );
            $response = array( 'results' => $options );
            // die(print_rr(json_encode( $response )));
            echo json_encode( $response );
        }
    }

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
        // unset( $modules['core'] );
        $installed_modules = '';
        foreach ( $modules as $module_name => $module )
        {
            // print_rr($module);
            $download = $reinstall = $install_info = '';
            $version = $module['info']['version'];
            if ( isset( $module['info']['source'] ) )
            {
                $source = $module['info']['source'];
                $file_url = 'https://api.github.com/repos/' . $source . '/zipball/v' . $version;
                $token = ( isset( $module['config']['token'] ) && !empty( $module['config']['token'] ) ) ? $module['config']['token'] : '';
                $private = ( isset( $module['config']['token'] ) && !empty( $module['config']['token'] ) ) ? 1 : 0;
                $data_attributes = <<<DATA
                    data-module="{$module['name']}" data-source="$source" data-file_url="$file_url" data-private="$private" data-source_token="$token"
                DATA;
                $install_info = <<<INFO
                    <a class="install-info" data-action="install" data-module="$module_name" data-path="{$module['path']}" data-source="$source" data-private="$private" data-token="$token" onclick="core.files( this )"></a>
                INFO;
                $download = <<<DOWNLOAD
                    <a style="padding: 0 10px 0 10px;" data-action="download" $data_attributes onclick="core.files( this )"><i class="fa fa-download"></i></a>
                DOWNLOAD;
                // $download = ( $module['name'] == 'core' ) ? '' : $download_link;
                $reinstall = <<<REINSTALL
                    <a class="action" data-action="reinstall" $data_attributes onclick="core.files( this )"><i class="fa fa-refresh" aria-hidden="true"></i></a>
                REINSTALL;
            }
            // $reinstall = ( $module['name'] == 'core' ) ? '' : $this->get_reinstall_link( $module['name'] );
            // $reinstall = ( $module['name'] == 'core' ) ? '' : $reinstall_link;
            // $latest_version = '<a href="open_dialog( `https://unicloud.co.nz` )" style="color: red;">Version 1.0.1 Available</a>';
            $module_index_url = url_for( $module['app_path'] . 'index' );
            $installed_modules .= '
                <li>
                    <div>
                        <span class="module-name" id="module_' . $module_name . '" data-installed_version="' . $version . '"><a href="' . $module_index_url . '">' . $module['info']['title'] . '</a></span><span class="module-version">v' . $version . '</span>' . $download . $reinstall . '
                        <div class="module-description">' . $module['info']['description'] . '</div>
                        ' . $install_info . '
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
            .install-info {
                color: red;
                display: none;
                cursor: pointer;
            }
        </style>
        HTML;        
        return $html;
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
        $modules = ( $this->get_name() == 'core' ) ? 'let modules = $( `.module-info` )' : 'let modules = $( `#module_test` )';
        $script = <<<SCRIPT
        const repos_url = `https://api.github.com/repos/`
        let modules = $( `.module-info` )
        let branch_select = $( '#module_branches' )
        let branches = {}
        let source = $( '#source' ).html()
        let module_token = $( '#module_token' ).val()
        branch_select.each( function() {
            // console.log($( 'option', this))
            let option = $( 'option', this )
            let branch = option.val()
            branches[branch] = option
        })
        let url = repos_url + source + "/branches"
        let branch_options = $( '#plugin_branch option' )
        // console.log(branch_options)
        let get_branches = function( response ) {
            // console.log(branches) 
            $.each( response, function( index, branch ) {
                let name = branch.name
                let sha = branch.commit.sha
                let url = branch.commit.url
                if ( branches[name] !== undefined ) {
                    // console.log('branch exists',name)
                    $( branches[name] ).attr( 'sha', sha )
                    $( branches[name] ).attr( 'url', url )
                } else {
                    branch_select.append( '<option value="' + name + '" sha-"' + sha + '" url="' + url +'">' + name + '</option>' )
                }
            })    
        }
        if ( module_token !== undefined ) {
            console.log('in module token',module_token)
            core.ajax_headers = {'Authorization': 'token ' + module_token}
        }
        core.ajax_get( url, get_branches )
        let install_versions = $( '.install-info' )
        $.each( install_versions, function( index, element ) {
            // console.log(element)
            let container = $( element )
            let module = container.data( 'module' )
            let installed_version = $( '#module_' + module ).data( 'installed_version' )
            // console.log(module,installed_version)
            let get_latest_version = function( response ) {
                // console.log(response)
                let latest_version = response.tag_name.split( 'v' )
                let update = ( installed_version.trim() == latest_version[1] ) ? false : true
                let zip_url = response.zipball_url
                // let link = '<a data-module="' + module + '" data-action="install" data-file_url="' + zip_url + '" data-private="0" class="install-link action" onclick="core.files( this );">Install Version ' + latest_version[1] + '</a>'
                // console.log(module,update,latest_version,zip_url,container)
                // if ( update ) container.show().html( link )
                if ( update ) {
                    // console.log('update',container)
                    container.show().html( 'Install Version ' + latest_version[1] ).attr( 'data-file_url', zip_url )
                }
            }
            let source = $( element ).data( 'source' )    
            let url = `https://api.github.com/repos/` + source + `/releases/latest`
            let private = container.data( 'private' )
            let module_token = container.data( 'token' )
            if ( private && module_token !== undefined ) {
                // console.log('in module token',module_token)
                core.ajax_headers = {'Authorization': 'token ' + module_token}
            }
            // console.log(module,installed_version,source,url)
            core.ajax_get( url, get_latest_version )
        })
        SCRIPT;
        return $script;
    }
    /*
    console.log(modules)
    $.each( modules, function( index, element ) {
        let module = $( element ).data( 'module' )
        let installed_version = $( element ).data( 'version' )
        let get_latest_version = function( response ) {
            // console.log(response.zipball_url)
            let latest_version = response.tag_name.split( 'v' )
            let update = ( installed_version.trim() == latest_version[1] ) ? false : true
            let zip_url = response.zipball_url
            let link = '<a data-module="' + module + '" data-action="install" data-file_url="' + zip_url + '" data-private="0" class="install-link action" onclick="core.files( this );">Install Version ' + latest_version[1] + '</a>'
            console.log(module,update,latest_version,zip_url,link)
            if ( update ) ( module == 'core' ) ? $( '#alert_plugin_settings' ).html( link ) : $( `#latest_` + module ).show().html( link )
        }
        let source = $( element ).data( 'source' )    
        let url = `https://api.github.com/repos/` + source + `/releases/latest`
        // let url = `https://api.github.com/repos/antevasin-app/module-template/releases/latest`
        core.ajax_get( url, get_latest_version )
    })
    */
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

    public function get_date_obj( $timestamp )
    {
        $date_obj = new \DateTime( date( CFG_APP_DATETIME_FORMAT, $timestamp ) );
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
        if ( isset( $this->data['process_id'] ) )
        {
            $user_id = ( $this->user_id > 0 ) ? $this->user_id : 2 ;
            $this->set_app_user( $user_id );
            //check if the process exists and is active
            $sql = "SELECT * FROM app_ext_processes WHERE id='" . db_input( $data['process_id']) . "' and is_active=1";
            if ( !$app_process_info = db_fetch_array( db_query( $sql ) ) ) redirect_to( 'dashboard/page_not_found' );
            if ( isset( $data['selected_items'] ) )
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
                $current_entity_id = $data['items_info']['entity_id'];
                $current_item_id = $data['items_info']['items_id'];
                $_POST = $data;
                $processes = new \processes( $current_entity_id );
                $processes->items_id = $current_item_id;
                $processes->run( $app_process_info );
            }   
            if ( $redirect ) redirect_to( $this->set_app_redirect_to() );
        }    
    }

    public static function set_app_user( $user_id = '' )
    {
        global $app_user, $app_logged_users_id;
        
        $user_id = ( empty( $user_id ) ) ? $app_logged_users_id : $user_id;
        $sql = "select e.*, ag.name as group_name from app_entity_1 e left join app_access_groups ag on ag.id=e.field_6 where  e.id='" . db_input( $user_id ) . "' and e.field_5=1";
        $user_query = db_query( $sql );
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
            if ( $app_logged_users_id > 0 ) return $app_logged_users_id;
        }
    }

    public function set_app_redirect_to() 
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
        return array( 'entities_id' => $entity_id, 'items_id' => $items_id );    
    }

    public  function get_full_path( $path )
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

    public function get_map_markers()
    {
        // print_rr('in get_map_markers function');
        // print_rr($this->data);
        $sql = "SELECT * FROM app_entities WHERE field_1287 IS NOT NULL";
        $user_query = db_query( $sql );
        $customers = array();
        while ( $results = db_fetch_array( $user_query ) )
        {
            $customers[] = $results;
        }
        print_rr($customers);
        $data = array(
            array(
                'address' => "215 Emily St, MountainView, CA",
                'description' => "Single family house with modern design",
                'price' => "$ 3,889,000",
                'type' => "home",
                'bed' => 5,
                'bath' => 4.5,
                'size' => 300,
                'position' => array(
                    'lat' => 37.50024109655184,
                    'lng' => -122.28528451834352,
                ),
            ),
            array(
                'address' => "108 Squirrel Ln &#128063;, Menlo Park, CA",
                'description' => "Townhouse with friendly neighbors",
                'price' => "$ 3,050,000",
                'type' => "building",
                'bed' => 4,
                'bath' => 3,
                'size' => 200,
                'position' => array(
                    'lat' => 37.44440882321596,
                    'lng' => -122.2160620727,
                ), 
            ),
            array(
                'address' => "100 Chris St, Portola Valley, CA",
                'description' => "Spacious warehouse great for small business",
                'price' => "$ 3,125,000",
                'type' => "warehouse",
                'bed' => 4,
                'bath' => 4,
                'size' => 800,
                'position' => array(
                    'lat' => 37.39561833718522,
                    'lng' => -122.21855116258479,
                ),
            ),
            array(
                'address' => "98 Aleh Ave, Palo Alto, CA",
                'description' => "A lovely store on busy road",
                'price' => "$ 4,225,000",
                'type' => "store-alt",
                'bed' => 2,
                'bath' => 1,
                'size' => 210,
                'position' => array(
                    'lat' => 37.423928529779644,
                    'lng' => -122.1087629822001,
                ),
            ),
            array(
                'address' => "2117 Su St, MountainView, CA",
                'description' => "Single family house near golf club",
                'price' => "$ 1,700,000",
                'type' => "home",
                'bed' => 4,
                'bath' => 3,
                'size' => 200,
                'position' => array(
                    'lat' => 37.40578635332598,
                    'lng' => -122.15043378466069,
                ),
            ),
            array(
                'address' => "197 Alicia Dr, Santa Clara, CA",
                'description' => "Multifloor large warehouse",
                'price' => "$ 5,000,000",
                'type' => "warehouse",
                'bed' => 5,
                'bath' => 4,
                'size' => 700,
                'position' => array(
                    'lat' => 37.36399747905774,
                    'lng' => -122.10465384268522,
                ),
            ),
            array(
                'address' => "700 Jose Ave, Sunnyvale, CA",
                'description' => "3 storey townhouse with 2 car garage",
                'price' => "$ 3,850,000",
                'type' => "building",
                'bed' => 4,
                'bath' => 4,
                'size' => 600,
                'position' => array(
                    'lat' => 37.38343706184458,
                    'lng' => -122.02340436985183,
                ),
            ),
            array(
                'address' => "868 Will Ct, Cupertino, CA",
                'description' => "Single family house in great school zone",
                'price' => "$ 2,500,000",
                'type' => "home",
                'bed' => 3,
                'bath' => 2,
                'size' => 100,
                'position' => array(
                    'lat' => 37.34576403052,
                    'lng' => -122.04455090047453,
                ),
            ),
            array(
                'address' => "655 Haylee St, Santa Clara, CA",
                'description' => "2 storey store with large storage room",
                'price' => "$ 2,500,000",
                'type' => "store-alt",
                'bed' => 3,
                'bath' => 2,
                'size' => 450,
                'position' => array(
                    'lat' => 37.362863347890716,
                    'lng' => -121.97802139023555,
                ),
            ),
            array(
                'address' => "2019 Natasha Dr, San Jose, CA",
                'description' => "Single family house",
                'price' => "$ 2,325,000",
                'type' => "home",
                'bed' => 4,
                'bath' => 3.5,
                'size' => 500,
                'position' => array(
                    'lat' => 37.41391636421949,
                    'lng' => -121.94592071575907,
                ),
            ),
        );
        foreach ( $data as $index => $marker )
        {
            $html = <<<HTML
                <div class="icon">
                    <i aria-hidden="true" class="fa fa-icon fa-{$marker['type']}" title="{$marker['description']}"></i>
                    <span class="fa-sr-only">{$marker['type']}</span>
                </div>
                <div class="details">
                    <div class="price">{$marker['price']}</div>
                    <div class="address">{$marker['address']}</div>
                    <div class="features">
                        <div>
                            <i aria-hidden="true" class="fa fa-bed fa-lg bed" title="bedroom"></i>
                            <span class="fa-sr-only">{$marker['bed']}</span>
                            <span>5</span>
                        </div>
                        <div>
                            <i aria-hidden="true" class="fa fa-bath fa-lg bath" title="bathroom"></i>
                            <span class="fa-sr-only">{$marker['bath']}</span>
                            <span>4.5</span>
                        </div>
                        <div>
                            <i aria-hidden="true" class="fa fa-ruler fa-lg size" title="size"></i>
                            <span class="fa-sr-only">{$marker['description']}</span>
                            <span>{$marker['description']} ft<sup>2</sup></span>
                        </div>
                    </div>
                </div>
            HTML;
            $data[$index]['html'] = $html;
        }
        $markers = json_encode( $data );
        // print_rr($markers);
        echo '{"success":"in core module get_map_markers function","data":' . $markers . '}';
    }
}