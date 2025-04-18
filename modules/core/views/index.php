<?php

namespace Antevasin;

$index = new index( $core );
$index->set_title( 'Antevasin Plugin', 'Index Page Title Link - remove if not needed' );
$core_module_tabs = array(    
    array(
        'name' => 'plugin',
        'sections' => array(
            array(
                'title' => 'Tools',
                // 'id' => 'testing',
                'groups' => array(
                    array(
                        'field_class' => 'plugin-info',
                        'label' => 'Form Entities',
                        'field' => '<a onclick="plugin.run_module_action( `update_entities` )">Update</a>'
                    ),
                    array(
                        'field_class' => 'plugin-info',
                        'label' => 'Auto Actions',
                        'field' => '<a onclick="plugin.run_module_action( `update_auto_actions` )">Update</a>'
                    )
                )
            ),
            array(
                'title' => 'Rukovoditel Extension',
                // 'id' => 'testing',
                'groups' => array(
                    array(
                        'field_class' => 'plugin-info',
                        'label' => 'Key',
                        'field' => CFG_PLUGIN_EXT_LICENSE_KEY
                    )
                )
            ),
        )
    ),
);
$core->set_index_tabs( $core_module_tabs );
$index->render();