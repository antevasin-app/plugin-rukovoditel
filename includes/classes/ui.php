<?php

namespace Antevasin;

class ui 
{
    private $data;

    public function __construct( $data = array())
    {
        $this->data = $data;
    }

    public function render_tabs()
    {
        if ( isset( $this->data['tabs'] ) ) 
        {
            // die(print_rr($this->data));
            $core = new core();
            $html = $tab_content = '';
            $tabs = $this->data['tabs'];
            $tabs_size = $this->data['content_size'];
            if ( count( $tabs ) >= 1 ) {
                $html .= '<ul class="nav nav-tabs nav-reports-groups" id="tabMenu">';
    
                foreach ( $tabs as $index => $tab ) 
                {
                    $icon_color = ( isset( $tab['icon_color'] ) and strlen( $tab['icon_color'] ) ) ? 'style="color: ' . $tab['icon_color'] . '"' : '';
                    $icon = strlen( $tab['class'] ) ? app_render_icon( $tab['class'], $icon_color ) . ' ' : '';
                    $bg_color = strlen( $tab['bg_color'] ) ? 'style="background-color: ' . $tab['bg_color'] . '"' : '';
    
                    // Add "active" class to the first tab
                    $active_class = ($index == 0) ? 'active' : '';
                    $html .= '<li class="tab-item ' . $active_class . '" id="tab_' . $index . '"><a ' . $bg_color . '">' . $icon . $tab['title'] . '</a></li>';
                    if ( isset( $tab['url'] ) )
                    {
                        $tab_url = $core->check_url( $tab['url'] );
                        // $iframe_style = ( isset( $tabs_data['iframe_style'] ) ) ? $tabs_data['iframe_style'] : '';
                        // print_rr($tab_url);
                        $tab_content .= ( is_array( $tab_url ) ) 
                        ? '<div id="tab_content_' . $index . '" class="tab-content" id="preview_link_' . $index . '">' . $tab_url['content'] . '</div>' 
                        : '<iframe id="tab_content_' . $index . '" class="tab-content" title="Tabs" width="' . $tabs_size['width'] . '" height="' . $tabs_size['height'] . '" src="' . $tab_url . '"></iframe>';
                    }
                    else if ( isset( $tab['html'] ) )
                    {
                        // $tab['url'] = $tab['html'];
                        // $tab_content .= '<div id="tab_content_' . $index . '" class="tab-content" id="preview_link_' . $index . '">' . $tab_url['content'] . '</div>';
                        $tab_content .= '<div id="tab_content_' . $index . '" class="tab-content" id="preview_link_' . $index . '">' . $tab['html'] . '</div>';
                    }
                    else 
                    {
                        $tab_content .= '';
                    }
    
    
                }
                $html .= '</ul>';
            }
            $html .= '
            <div class="tab-content-container">' . $tab_content . '</div>
            <style>
                .tab-item {
                    cursor: pointer;
                }
                .tab-content {
                    // width: 95%;
                    margin: auto; 
                    min-height: 800px; 
                    // padding-top: 20px;
                }
                .tab-content {
                    display: none; 
                }
                .iframe-error {
                    padding: 20px;
                    background-color: #f8d7da;
                    border-color: #f5c6cb;
                    color: #721c24;
                    border: 1px solid;
                    border-radius: 5px;
                }
            </style>
            <script>
                $( function() {
                    $( `#tab_content_0` ).show();
                    $( "#tabMenu li" ).click( function() {
                        let index = $( this ).index();
                        $( `.tab-content` ).hide();
                        $( `#tab_content_${ index }` ).show();
                        $( `.tab-item` ).removeClass( "active" );
                        $( `#tab_${index}` ).addClass( "active" )
                    });
                });
            </script>
            ';
            echo $html;            
        }
    }


}