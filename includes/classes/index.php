<?php 

namespace Antevasin;

class index
{
    private $module;
    private $title;
    private $title_link;

    public function __construct( module $module )
    {
        $this->module = $module;
        $this->set_title( $module->get_title() . " Module" );
    }

    public function set_title( $title, $link = '' )
    {
        $this->title = $title;
        $this->title_link = $link;
    }

    public function render()
    {
        $form = new form( $this->module );
        $form->set_id ( 'cfg' );
        $module_name = $this->module->get_name();
        $form->set_action ( url_for( 'configuration/save', "redirect_to=" . PLUGIN_NAME . "/$module_name/index" ) );
        $form->set_title ( $this->title, $this->title_link );  
        $this->module->module_index_tabs( $form );
        $config = $this->module->get_config();
        $private = ( isset( $this->module->get_info()->private ) && $this->module->get_info()->private ) ? true : false;
        $token = ( isset( $config->token ) ) ? $config->token : '';
        $module_info = $this->module->get_info();
        $tabs = array( 
            array(
                'name' => 'module',
                'description' => 'tab module description',
                'sections' => array(
                    array(
                        'title' => 'Module Settings',
                        // 'id' => 'testing',
                        'description' => 'section module settings description',
                        'groups' => array(
                            array(
                                'field_class' => 'plugin-info',
                                'label' => 'Module Path',
                                'field' => $this->module->get_path()
                            ),
                            array(
                                'field_class' => 'plugin-info version',
                                'label' => 'Installed Version',
                                'field' => $this->get_source_link()
                            ),
                            array(
                                'field_class' => 'plugin-info',
                                'label' => 'Module Description',
                                'field' => $this->module->get_info()->description
                            ),
                        )
                    ),
                )            
            ),
            array(
                'name' => 'access',
                'description' => 'tab access description',
                'groups' => array(
                    array(
                        'label' => 'Tab 2 Field 1 Label',
                        'field' => 'Tab 2 Field 1'
                    ),
                    array(
                        'label' => 'Tab 2 Field 2 Label',
                        'field' => 'Tab 2 Field 2'
                    )    
                )
                    ),
            array(
                'name' => 'menus',
                'description' => 'tag menus description',
                'groups' => array(
                    array(
                        'label' => 'Tab 2 Field 1 Label',
                        'field' => 'Tab 2 Field 1'
                    ),
                    array(
                        'label' => 'Tab 2 Field 2 Label',
                        'field' => 'Tab 2 Field 2'
                    )    
                )
            )
        );
        if ( $module_name == 'core' )
        {
            $tabs[0]['sections'][] = array(
                'title' => 'Module Management',
                'content' => $this->module->module_management()
            );
        }
        if ( isset( $this->module->get_info()->source ) )
        {
            $tabs[0]['sections'][0]['groups'][] = array(
                'field_class' => 'plugin-info',
                'label' => 'Source File',
                'field' => $this->get_repository_link()
            );
            $tabs[0]['sections'][0]['groups'][] = array( 
                'label' => 'Branch',
                'field_class' => 'plugin-info',
                'field' => $form->add_tag( 'select', 'module_branches', array( $module_info->branch => $module_info->branch ), $module_info->branch, array( 'size' => 'small' ) )
            );
        } 
        if ( $private )
        {
            $tabs[0]['sections'][0]['groups'][] = array(
                'field_class' => 'plugin-info',
                'label' => 'Token',
                'field' => $form->add_tag( 'input', 'module[token]', null, $token, array( 'size' => 'x-large' ) )
            );
            $tabs[0]['sections'][0]['groups'][] = array(
                'field_class' => 'plugin-info',
                'label' => 'Token Expiry',
                'field' => $form->add_tag( 'input', 'module[token_expiry]', null, $config->token_expiry, array( 'size' => 'medium' ) )
            );          
        }
        $notes = ( isset( $config->notes ) ) ? $config->notes : '';
        $tabs[0]['sections'][0]['groups'][] = array(
            'label' => 'Module Notes',
            'field' => $form->add_tag( 'textarea', 'module[notes]', null, $notes, array( 'size' => 'large' ) )
        );
        $tabs[0]['sections'][0]['groups'][] = array( 
            'field' => $form->add_tag( 'input_hidden', 'CFG[MODULE_' . strtoupper( $module_name ) . '_CONFIG]', null, '' )
        );
        $tabs[0]['sections'][0]['groups'][] = array( 
            'field' => $form->add_tag( 'input_hidden', 'current_config', null, json_encode( $config ) )
        );
        $tabs[0]['sections'][0]['groups'][] = array( 
            'field' => $form->add_tag( 'input_hidden', 'module_name', null, $module_name )
        );
        if ( !empty( $this->module->get_index_tabs() ) ) $tabs = array_merge( $this->module->get_index_tabs(), $tabs );
        $form->add_tabs( $tabs );
        $style = <<<STYLE
            .action {
                cursor: pointer;               
                text-decoration: underline;
            }
            .plugin-info { 
                padding-top: 8px; 
                font-size: 15px; 
                font-weight: bold; 
            }
        STYLE;
        $form->add_style( $style );
        $form->add_script( 'core.setup_module_index();' );
        $form->add_script( $this->module->get_source_script( $this->module ) );
        echo $form->render();
    }

    private function get_source_link()
    {
        $module_name = $this->module->get_name();
        $version = ( $module_name == 'core' ) ? PLUGIN_VERSION : $this->module->get_info()->version;
        if ( !isset( $this->module->get_info()->source ) ) return $version;
        $source = $this->module->get_info()->source;
        $file_url = 'https://api.github.com/repos/' . $source . '/zipball/v' . $version;
        $private = ( isset( $this->module->get_config()->token ) ) && !empty( isset( $this->module->get_config()->token ) ) ? 1 : 0;
        $token = ( $private ) ? 'data-source_token="' . $this->module->get_config()->token . '"' : '';
        return $this->module->get_info()->version . '<a style="padding: 0 10px 0 10px;" data-action="download" data-module="' . $module_name . '" data-version="' . PLUGIN_VERSION . '" data-source="' . $source . '" data-file_url="' . $file_url . '" data-private="' . $private . '"'. $token . ' onclick="core.files( this )"><i class="fa fa-download"></i></a>' . $this->module->get_reinstall_link( $module_name );
    }

    private function get_repository_link()
    {
        $source = $this->module->get_info()->source;
        return '<div id="repository"><a id="source" href="https://github.com/' . $source . '" target="_blank">' . $source . '</a></div>';
    }
}