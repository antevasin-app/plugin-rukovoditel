<?php

namespace Antevasin;

class form
{
    private $module; 
    private $id;
    private $action;
    private $title;
    private $title_link;
    private $form_tag;
    private $form_hidden_inputs;
    private $form_body_hidden_inputs;
    private $form;
    private $styles = array();
    private $style_tag = '';
    private $scripts = array();
    private $script_tag = '';
    private $tabs = array();
    private $tabbable = true;
    private $wizard = false;
    private static $default_selector = array( '0' => TEXT_NO, '1' => TEXT_YES );
    private $form_body_class = '';
    private $tabbable_class = '';
    private $tabs_class = '';
    private $tab_list = '';
    private $tab_content_class = '';
    private $tab_content = '';
    private $tab_pane_class = '';
    private $button_class = '';

    public function __construct( module $module )
    {
        $this->module = $module;
    }    

    public function set_id( $id )
    {
        $this->id = $id;
    }
    
    public function set_action( $action )
    {
        $this->action = $action;
    }

    public function set_title( $title, $link = '' )
    {
        $this->title = $title;
        $this->title_link = $link;
    }

    public function add_tabs( $tabs )
    {
        $this->tabs = array_merge( $this->tabs, $tabs );
    }

    public function add_style( $style )
    {
        $this->styles[] = $style;
    }

    public function add_script( $script )
    {
        $this->scripts[] = $script;
    }

    private function get_tabs()
    {
        foreach ( $this->tabs as $index => $tab ) 
        {
            $active_tab = ( $index == 0 ) ? 'active' : '';
            $active_content = ( $index == 0 ) ? 'active in' : '';
            $tab_name = $tab['name'];
            $tab_description = ( isset( $tab['description'] ) ) ? $tab['description'] : '';
            $tab_title = ( isset( $tab['title'] ) ) ? $tab['title'] : $tab_name;
            if ( isset( $tab['format'] ) && function_exists( $tab['format'] ) )
            {
                $formatted_tab_title = ( isset( $tab['format'] ) ) ? $tab['format']( $tab_title ) : ucfirst( $tab_title );
            }
            else
            {
                $formatted_tab_title = ucfirst( $tab_title );
            }
            // $formatted_tab_title = ( isset( $tab['format'] ) ) ? $tab['format']( $tab_title ) : ucfirst( $tab_title );
            $tab_class = ( isset( $tab['class'] ) ) ? $tab['class'] : '';
            $this->tab_list .= <<<TAB_LIST
            <li class="$active_tab $tab_class" id="$tab_name"><a href="#tab_pane_$index" data-toggle="tab" aria-expanded="true">$formatted_tab_title</a></li>
            TAB_LIST;
            if ( isset( $tab['sections'] ) )
            {
                $form_groups = '';
                $form_group_class = ( isset( $tab['group_class'] ) ) ? $tab['group_class'] : '';
                foreach ( $tab['sections'] as $section ) {
                    $section_description = ( isset( $section['description'] ) ) ? $section['description'] : '';
                    $section_id = ( isset( $section['id'] ) ) ? $section['id'] : $this->text_to_tag( $section['title'] );
                    $form_groups .= '<h3 class="form-section">' . $section['title'] . ' <span class="section-alert" id="alert_' . $section_id . '"></span></h3><span class="section-description" id="description_' . $section_id . '">' . $section_description . '</span>';        
                    if ( isset( $section['content'] ) )
                    {
                        $form_groups .= $section['content'];
                    }
                    else
                    {
                        foreach ( $section['groups'] as $group ) {
                            $form_group_label_class = ( isset( $group['label_class'] ) ) ? $group['label_class'] : '';
                            $form_group_field_class = ( isset( $group['field_class'] ) ) ? $group['field_class'] : '';
                            $label = ( isset( $group['label'] ) ) ? '<label class="col-md-3 control-label ' . $form_group_label_class . '">' . $group['label'] . '</label>' : '';
                            $form_groups .= <<<FORM_GROUP
                            <div class="form-group $form_group_class">
                                $label
                                <div class="col-md-9 $form_group_field_class">
                                    {$group['field']}
                                </div>
                            </div>
                            FORM_GROUP;
                        }
                    }
                } 
            }
            else
            {
                $form_groups = '';
                $form_group_class = ( isset( $tab['group_class'] ) ) ? $tab['group_class'] : '';
                $form_group_label_class = ( isset( $tab['label_class'] ) ) ? $tab['label_class'] : '';
                $form_group_field_class = ( isset( $tab['field_class'] ) ) ? $tab['field_class'] : '';
                foreach ( $tab['groups'] as $group ) {
                    $form_groups .= <<<FORM_GROUP
                    <div class="form-group $form_group_class">
                        <label class="col-md-3 control-label $form_group_label_class">{$group['label']}</label>
                        <div class="col-md-9 control-info $form_group_field_class">
                            {$group['field']}
                        </div>
                    </div>
                    FORM_GROUP;
                }
            }
            $this->tab_content .= <<<TAB_CONTENT
            <div class="tab-pane fade $active_content $this->tab_pane_class" id="tab_pane_$index">
                <span class="section-description">$tab_description</span>
                $form_groups
            </div>
            TAB_CONTENT;
        }
    }

    private function text_to_tag( $text )
    {
        $tag = strtolower( str_replace( ' ', '_', $text ) );
        return $tag;
    }
    private function get_styles()
    {
        if ( !empty( $this->styles ) )
        {
            $style = '';
            foreach ( $this->styles as $index => $css )
            {
                $style .= $css . PHP_EOL;
            }
            $style = <<<STYLE
            <style>
            $style
            </style>
            STYLE;

            $this->style_tag = $style;
        }
    }

    private function get_scripts()
    {
        if ( !empty( $this->scripts ) )
        {
            $scripts = '';
            foreach ( $this->scripts as $index => $js )
            {
                $scripts .= $js . PHP_EOL;
            }
            $script = <<<SCRIPT
            <script>
            $scripts
            </script>
            SCRIPT;

            $this->script_tag = $script;
        }
    }

    public function render()
    {
        $this->get_tabs();
        $this->get_styles();
        $this->get_scripts();
        $this->form_tag = '<form action="' . $this->action . '" name="cfg" id="cfg" method="post" class="form-horizontal">';
        if ( $this->tabbable )
        {
            $form_body = <<<FORM_BODY
            <div class="tabbable tabbable-custom $this->tabbable_class">
                <ul class="nav nav-tabs $this->tabs_class">
                    <!-- loop through tabs -->
                    $this->tab_list
                    <!-- loop through tabs -->
                </ul>
                <div class="tab-content $this->tab_content_class">
                    <!-- loop through tabs -->
                    $this->tab_content
                    <!-- loop through tabs -->
                </div>
            </div>
            FORM_BODY;
        }
        else
        {
            $form_body = <<<FORM_BODY
            <div>
                Normal Form
            </div>
            FORM_BODY;
        }
        $form = <<<FORM
        <div class="form-body $this->form_body_class">
            $this->form_body_hidden_inputs
            $form_body
            <input id="submit_config" value="Save Config" type="submit" class="btn btn-primary $this->button_class">
        </div> 
        FORM;
        $html = <<<HTML
        <h3>$this->title <span id="form_alert"></span></h3><span class="title-link">$this->title_link</span>
        $this->form_tag
            $this->form_hidden_inputs
            $form
            $this->style_tag
            $this->script_tag
        </form>
        HTML;
        
        echo $html;
    }

    public function add_tag( string $tag, ?string $name, ?array $choices, ?string $value, ?array $attributes = array() )
    {
        $function = "{$tag}_tag";
        $classes = array( 'form-control' => 'form-control' );
        $input_size = ( isset( $attributes['size'] ) ) ? $attributes['size'] : 'medium'; 
        $classes["input-$input_size"] = "input-$input_size";
        if ( isset( $attributes['class'] ) ) 
        {
            $suppplied_classes = explode( ' ', $attributes['class'] );
            foreach ( $suppplied_classes as $class ) {
                $classes[$class] = $class;
            } 
        }
        $attributes['class'] = implode( ' ', $classes );
        switch ( $tag )
        {
            case 'select':
                return $function( $name, $choices, $value, $attributes );
            default:
                return $function( $name, $value, $attributes );
        }        
    }
}