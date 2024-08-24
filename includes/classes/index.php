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
        $form->set_action ( url_for( 'configuration/save', "redirect_to=" . PLUGIN_NAME . "/{$this->module->get_name()}/index" ) );
        $form->set_title ( $this->title, $this->title_link );   
        $this->module->module_index_tabs( $form );
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
                                'label' => 'Source File',
                                'field' => $this->get_repository_link()
                            ),
                            array(
                                'field_class' => 'plugin-info',
                                'label' => 'Module Description',
                                'field' => $this->module->get_info()->description
                            ),
                            array(
                                'label' => 'Module Notes',
                                'field' => $form->add_tag( 'textarea', 'module[notes]', null, '', array( 'size' => 'xlarge' ) )
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
        $source = $this->module->get_info()->source;
        return $this->module->get_info()->version . '<a style="padding: 0 10px 0 10px;" href="https://api.github.com/repos/' . $source . '/zipball/v' . $this->module->get_info()->version . '"><i class="fa fa-download"></i></a>' . $this->module->get_reinstall_link( $this->module->get_name() );
    }

    private function get_repository_link()
    {
        $source = $this->module->get_info()->source;
        return '<div id="repoistory"><a href="https://github.com/' . $source . '" target="_blank">' . $source . '</a></div>';
    }

    public function render_()
    {
        echo '<h3>' . $this->module->get_title() . '</h3>';
        $form = new form( $this->module );
        $form->set_id ( 'cfg' );
        $module_name = $this->module->get_name();
        $cfg_name = "CFG[MODULE_" . strtoupper( $module_name ) . "_CONFIG]";
        $form->add_hidden_inputs( array( 'module_name' => $module_name, $cfg_name => '' ) );
        $form->set_action( url_for( 'configuration/save', 'redirect_to=antevasin/readme/index' ) );
        // $form->set_params( array( 'id' => 'config_form', 'class' => 'test' ) );
        $form->set_submit_btn_options( array( 'id' => 'submit_config', 'btn_text' => 'Save Config' ) );
        $form->set_tabbable( true );

        /*
        add tabs
        add tab content
            add sections
                add group / field

        */
        $form->add_tabs( array_merge(  $this->module->get_index_tabs(), array( 'module', 'access', 'menus' ) ) );
        $form->render();

        $form->add_tag( 'input', 'Tag Label', 'module[test]', null, 'input value' );
        $form->add_tabs_content( 
            array(
                array(
                    'tab' => array(
                        'name' => 'module', 
                        'format' => 'ucfirst'
                    ),
                    'class' => 'module-info-index',
                    'title' => 'Module Info', 
                    'groups' => array( 
                        'Path' => $this->module->get_path(),
                        'Version' => $this->module->get_info()->version,
                        'Description' => $this->module->get_info()->description,
                        'Release Date' => $this->module->format_date( $this->module->get_info()->date ),
                        'Source' => $this->module->get_info()->source,
                        'Notes' => textarea_tag( 'module[notes]', $this->module->get_config()->notes, array('class'=>'form-control input-xlarge' ) ),
                    ) 
                ),
                array(
                    'tab' => array(
                        'name' => 'access',
                        'format' => 'ucfirst'
                    ),
                    'sections' => array(
                        array(
                            'title' => 'Administration Access', 
                            'groups' => array( $this->module->user_access_tags() )
                        ),
                        array(
                            'title' => 'System Admin Access', 
                            'groups' => array( $this->module->user_access_tags() )
                        ),
                        array(
                            'title' => 'User Access', 
                            'groups' => array( $this->module->user_access_tags() )
                        ),
                    )
                ),
                array(
                    'tab' => array(
                        'name' => 'menus',
                        'format' => 'ucfirst'
                    ),
                    'sections' => array(
                        array(
                            'title' => 'Icons', 
                            'groups' => array( 
                                'Use same icon for all menus' => select_tag( 'module[menus][all_menus]', $form->get_default_selector(), $this->module->get_config()->menus->all_menus, array( 'class' => 'form-control input-small' ) ),
                                'Icon' => input_tag( 'module[menus][icon]', $this->module->get_config()->menus->icon, array( 'class' => 'form-control input-medium required' ) )
                            )
                        ),
                        array(
                            'title' => 'Sidebar Menu', 
                            'groups' => array( 
                                'Show Menu' => select_tag( 'module[menus][sidebar][show]', $form->get_default_selector(), $this->module->get_config()->menus->sidebar->show, array( 'class' => 'form-control input-small' ) ),
                                'Icon' => input_tag( 'module[menus][sidebar][icon]', $this->module->get_config()->menus->sidebar->icon, array( 'class' => 'form-control input-medium required' ) ),
                                'Menu Link Text' => input_tag( 'module[menus][sidebar][link_text]', $this->module->get_config()->menus->sidebar->link_text, array( 'class' => 'form-control input-large required' ) ),
                                'Menu Link URL' => input_tag( 'module[menus][sidebar][link_url]', $this->module->get_config()->menus->sidebar->link_url, array( 'class' => 'form-control input-large required' ) ),
                            )
                        ),
                        array(
                            'title' => 'User Menu', 
                            'groups' => array( 
                                'Show Menu' => select_tag( 'module[menus][user][show]', $form->get_default_selector(), $this->module->get_config()->menus->user->show, array( 'class' => 'form-control input-small' ) ),
                                'Icon' => input_tag( 'module[menus][user][icon]', $this->module->get_config()->menus->user->icon, array( 'class' => 'form-control input-medium required' ) ),
                                'Menu Link Text' => input_tag( 'module[menus][user][link_text]', $this->module->get_config()->menus->user->link_text, array( 'class' => 'form-control input-large required' ) ),
                                'Menu Link URL' => input_tag( 'module[menus][user][link_url]', $this->module->get_config()->menus->user->link_url, array( 'class' => 'form-control input-large required' ) ),
                            )
                        ),
                    )
                )
            )
        );
        $onload = <<<SCRIPT
        core.setup_module_index();
        SCRIPT;
        $script_2 = <<<SCRIPT
        // console.log('script 2 test - onload');
        // console.log('script 2 another test - onload');
        SCRIPT;
        $script_3 = <<<SCRIPT
        // console.log('script 3 test - form add scripts');
        // console.log('script 3 another test - form add scripts');
        SCRIPT;
        $script_4 = array( 'form' => "console.log('script 4 form array')", 'onload' => "console.log('script 4 onload array')" );
        // $form->add_js( $onload );
        // $form->add_js( $script_2, false );
        // $form->add_scripts( array( $script_2, $script_3, $script_4 ) );
        // echo $form->render();
    }
}