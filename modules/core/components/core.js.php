<?php

namespace Antevasin;

global $app_session_token;

$files_url = url_for( 'antevasin/core/files', 'token=' . $app_session_token );

?>

var core = core || {
    files_url: "<?php echo $files_url; ?>",
    ajax_headers: {},
    expand_pre:function() {
        $( 'pre' ).on( 'click', function() {  
            let height = $( this ).css( 'max-height' );   
            switch ( height ) {
                case '250px':
                    new_height = '500px'; 
                    break;
                case '500px':
                    new_height = '1000px';
                    break;
                default:
                    new_height = '250px';
                    break;
            }
            $( this ).css( 'max-height', new_height );
        });
        $( 'pre' ).on( 'dblclick', function() { 
            let height = $( this ).css( 'max-height' );  
            switch ( height ) {
                case '250px':
                    new_height = '1000px'; 
                    break;
                case '1000px':
                    new_height = '3000px';
                    break;
                default:
                    new_height = '250px';
                    break;
            }
            $( this ).css( 'max-height', new_height );
        });
    },
    setup_module_index:function() {
        $( `#module_menus_all_menus` ).on( 'change', function() {
            let flag = this.value;
            if ( flag == 1 ) {
                $( `[id$='_icon']` ).closest( '.form-group' ).hide();       
                $( `#module_menus_icon` ).closest( '.form-group' ).show();       
            } else {
                $( `[id$='_icon']` ).closest( '.form-group' ).show();       
                $( `#module_menus_icon` ).closest( '.form-group' ).hide();    
           }
        });
        $( '#module_menus_all_menus' ).trigger( 'change' );
        $( '#submit_config' ).on( 'click', function( event ) {
            event.preventDefault();
            let config_obj = $( '#cfg' ).serializeJSON();
            console.log(config_obj);
            // if ( common_icon_flag ) {
            //     let common_icon = $( '#settings_module_' + module_name + '_menus_icon' ).val();
            //     $( '** input[id^=settings][id$=icon]' ).val( common_icon );
            // }
            // allow for empty config values to be saved so they don't disappear
            // for ( var key in module_access_settings ) {
            //     if ( module_access_settings.hasOwnProperty( key ) ) {
            //         if ( !object_has_property( config_obj, module_access_settings[key] ) ) {
            //             object_set_property( config_obj, module_access_settings[key], '' );
            //         }
            //     }
            // }
            // console.log(config_obj);
            let module_name = config_obj.module_name.toUpperCase();
            console.log(module_name);
            let config_json = JSON.stringify( config_obj.module );
            console.log(config_json)
            $( `#CFG_MODULE_${module_name}_CONFIG` ).val( config_json );
            $( '#cfg' ).submit();
        });
    },    
    log_ajax_error:function( jqXHR, textStatus, errorThrown ) {
        console.log({"error_thrown":errorThrown,"status":textStatus,"object":jqXHR});
        if ( $( '#api_error' ).length > 0 ) {
            error_text = `${ textStatus } - ${ errorThrown }<br/>${ jqXHR.responseText }`;
            $( this ).html( error_text );
        }
        if ( $( '#return_data' ).length > 0 ) {
            $( '.return-data' ).show();
            // error_text = `${textStatus} - ${errorThrown} - ${jqXHR.responseText}`
            // console.log('i am here', error_text)
            $( '#return_data' ).val( jqXHR.responseText );
        }
    },
    ajax_post:function( url, data, done = this.console_response ) {
        $.ajax({
            method: "POST",
            url: url,
            data: data,
        })
        .done( done )
        .fail( function( jqXHR, textStatus, errorThrown ) {
            // do something based on failure
            core.log_ajax_error( jqXHR, textStatus, errorThrown );
        })
    },
    ajax_get:function( url, done = this.console_response ) {
        let settings = {
            method: "GET",
            url: url,
            headers: core.ajax_headers
        }
        $.ajax( settings )
        .done( done )
        .fail( function( jqXHR, textStatus, errorThrown ) {
            // do something based on failure
            core.log_ajax_error( jqXHR, textStatus, errorThrown );
        })
    },
    files:function( element ) {
        let action = $( element ).data( 'action' );
        let module = $( element ).data( 'module' );
        let file_url = $( element ).data( 'file_url' );
        let private = $( element ).data( 'private' );
        switch ( action ) {
            case 'install':
                break;
            case 'reinstall':
                break;
            case 'download':
                break;
            default:
                break;
        }
        // console.log(action);
        // console.log(element,module,core.files_url,url);
        if ( action == 'download' ) {
            console.log('download');
            let url = `${core.files_url}&action=${action}&module_name=${module}&file_url=${file_url}&private=${private}`;
            core.ajax_get( url );
        } else {
            let url = `http://localhost/plugin/index.php?module=antevasin/core/files&module_action=${action}&module_name=${module}`;
            open_dialog( url );
        }
        // return false;
    },
    console_response:function( response ) {
        console.log(response);
    }
}

$( function() {
    core.expand_pre();
});