<?php

namespace Antevasin;

global $app_session_token;

$url = url_for( 'antevasin/core/', 'token=' . $app_session_token );
$files_url = url_for( 'antevasin/core/files', 'token=' . $app_session_token );

?>

var core = core || {
    url: "<?php echo $url; ?>",
    files_url: "<?php echo $files_url; ?>",
    plugin_path: "<?php echo PLUGIN_PATH; ?>",
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
            // let config = {'module':'test','token':'123456','access':{admin:{'groups':'users','users':''}}}
            let config = JSON.parse( $( '#current_config' ).val() );
            let form_config = $( '#cfg' ).serializeJSON();
            console.log(config,form_config);
            $.extend( config, form_config.module );
            console.log(config);
            // if ( common_icon_flag ) {
            //     let common_icon = $( '#settings_module_' + module_name + '_menus_icon' ).val();
            //     $( '** input[id^=settings][id$=icon]' ).val( common_icon );
            // }
            // allow for empty config values to be saved so they don't disappear
            // for ( var key in module_access_settings ) {
            //     if ( module_access_settings.hasOwnProperty( key ) ) {
            //         if ( !object_has_property( form_config, module_access_settings[key] ) ) {
            //             object_set_property( form_config, module_access_settings[key], '' );
            //         }
            //     }
            // }
            let module_name = config.module.toUpperCase();
            console.log(module_name);
            let config_json = JSON.stringify( config );
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
            headers: core.ajax_headers
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
        let source_token = $( element ).data( 'source_token' );
        let data = {
            module_name: module,
            file_url: file_url,
            private: private,
            source_token: source_token
        }
        console.log(action)
        if ( action == 'download' ) {
            let callback = function( response ) {
                if ( response != '') {
                    let response_obj = JSON.parse( response );
                    if ( response_obj.success && response_obj.download_url ) {
                        window.location = response_obj.download_url;
                    } else if ( response_obj.error ) {
                        let data = response_obj.data;
                        alert(JSON.stringify( data, null, 2 ));
                    }
                }   
            }
            let url = `${core.files_url}&action=${action}`;
            // core.ajax_get( url, callback );
            core.ajax_post( url, data, callback );
        } else {
            let url = `${core.files_url}&module_action=${action}&module_name=${module}&file_url=${file_url}&private=${private}`;
            console.log(element,action,module,core.files_url,file_url,url,private);
            open_dialog( url );
        }
        // return false;
    },
    filter_status_field:function() {
        console.log(plugin.form); 
        if ( plugin.form.entities_id ) {
            // let url = `${core.url}&action=filter_status_field&entities_id=${plugin.form.entities_id}`;
            let get_default_url = `${core.url}&action=filter_statuses&get_default=true&entities_id=${plugin.form.entities_id}`;
            let get_default_callback = function( response ) {
                // console.log(response);
                if ( response != '' ) {
                    let response_obj = JSON.parse( response );
                    // console.log(response_obj);
                    if ( response_obj.field_id && response_obj.default ) {
                        let field_id = response_obj.field_id;
                        let status_field = $( `#fields_${field_id}` );
                        if ( status_field.val() === null ) {
                            $.each( response_obj.default, function( index, option_obj ) {
                                // console.log(option_obj);
                                core.set_ajax_dropdown_value( option_obj );
                            });
                        } else {
                            console.log('status field already has a value');
                        }
                        let ajax_dropdown_callback = function() {
                            console.log('in ajax_dropdown_callback function');
                        }
                        let ajax_dropdown_url = `${core.url}&action=filter_statuses&entities_id=${plugin.form.entities_id}`;
                        core.set_ajax_dropdown( {url:ajax_dropdown_url,field_id:field_id,ajax_dropdown_callback} );
                        // console.log(response_obj.data);
                        // $( '#fields_status_id' ).html( response_obj.data );
                    }
                }
            }
            core.ajax_get( get_default_url, get_default_callback );
        }
    },
    set_ajax_field_default:function( field_id, disabled = false ) {
        if ( plugin.form.entities_id ) {
            let url = `${core.url}&action=set_ajax_field_default&entities_id=${plugin.form.entities_id}&field_id=${field_id}`; 
            let callback = function( response ) {
                console.log(response);
                if ( response != '' ) {
                    let response_obj = JSON.parse( response );
                    console.log(response_obj);
                    if ( response_obj.default ) {
                        let field = $( `#fields_${field_id}` );
                        if ( field.val() === null || field.val().length == 0 ) {
                            $.each( response_obj.default, function( index, option_obj ) {
                                // console.log(option_obj);
                                if ( disabled ) {
                                    option_obj['disabled'] = true;
                                }
                                core.set_ajax_dropdown_value( option_obj );
                            });
                        } else {
                            console.log('field already has a value');
                        }
                    }
                }
            } 
            core.ajax_get( url, callback );
        }
    },
    set_ajax_dropdown:function( fields_obj ) {
        console.log('in set_ajax_dropdown',fields_obj);
        let obj = {
            width: <?php echo ( is_mobile() ? '$("body").width()-70' : '"100%"' ) ?>,
            <?php echo ( ( isset( $app_layout ) && in_array( $app_layout, ['public_layout.php'] ) || in_array( $app_module_path, ['users/account'] ) ) ? '':'dropdownParent: $("#ajax-modal"),') ?>
            "language":{
                "noResults" : function () { return "<?php echo addslashes( TEXT_NO_RESULTS_FOUND ) ?>"; },
                "searching" : function () { return "<?php echo addslashes( TEXT_SEARCHING ) ?>"; },
                "errorLoading" : function () { return "<?php echo addslashes( TEXT_RESULTS_COULD_NOT_BE_LOADED ) ?>"; },
                "loadingMore" : function () { return "<?php echo addslashes( TEXT_LOADING_MORE_RESULTS ) ?>"; }
            },
            allowClear: true,
            placeholder: "",
            ajax: {
                url: fields_obj.url,
                dataType: "json",
                type: "POST",
                data: function( params ) {
                    var query = {
                        search: params.term,
                        page: params.page || 1,
                        form_data: $( `#${current_from_id}` ).serializeArray(),
                    }                  
                    // Query parameters will be ?search=[term]&page=[page]
                    return query;
                },
            },
            templateResult: function( d ) { return $( d.html ); },
        }
        let dropdown = $( `#fields_${fields_obj.field_id}` );
        $( function() {
            dropdown.select2( 'destroy' );        
            dropdown.select2( obj );
            if ( fields_obj.callback ) fields_obj.callback();
        })
    },
    set_ajax_dropdown_value:function( option_obj ) {
        // console.log(option_obj);
        let options = new Option( option_obj.text, option_obj.id, false, false );
        let field = $( `#fields_${option_obj.field_id}` );
        field.append( options ).trigger( 'change' );
        // console.log(`option obj is `,option_obj,`existing field value is`,field.val()); 
        if ( field.prop( 'multiple' ) ) {
            let selected_values = field.val() || [];
            if ( !selected_values.includes( option_obj.id ) ) {
                selected_values.push( option_obj.id );
            }
            field.val( selected_values )
        } else {
            field.val( option_obj.id );
        }
        if ( option_obj.disabled ) {
            this.disable_ajax_dropdown( option_obj.field_id );
        }
        field.trigger( 'change' );
    },
    disable_ajax_dropdown:function( field_id ) {
        let field = $( `#fields_${field_id}` );
        field.on( 'select2:opening.select2-disable', function( e ) {
            e.preventDefault()
        })
        .on( 'select2:clearing.select2-disable', function( e ) {
            e.preventDefault()
        })
    },
    enable_ajax_dropdown:function( field_id ) {
        let field = $( `#fields_${field_id}` );
        field.off( 'select2:opening.select2-disable' )
        .off( 'select2:clearing.select2-disable' )
    },
    manually_assign_user_checkbox:function( fields_obj ) {
        $( `#fields_${fields_obj.trigger_field_id}` ).on( 'change', function() {
            if (  $( this ).closest( 'span' ).hasClass( 'checked' ) ) {
                core.users_id = $( `#fields_${fields_obj.users_field_id}` ).val();
                console.log(core.users_id);
                core.enable_ajax_dropdown( fields_obj.users_field_id );
            } else {
                $( `#fields_${fields_obj.users_field_id}` ).val( core.users_id ).trigger( 'change' );
                core.disable_ajax_dropdown( fields_obj.users_field_id );                    
            }
        });
    },
    console_response:function( response ) {
        console.log(response);
    }
}

var maps = maps || {
    init_google:function() {
        (g=>{var h,a,k,p="The Google Maps JavaScript API",c="google",l="importLibrary",q="__ib__",m=document,b=window;b=b[c]||(b[c]={});var d=b.maps||(b.maps={}),r=new Set,e=new URLSearchParams,u=()=>h||(h=new Promise(async(f,n)=>{await (a=m.createElement("script"));e.set("libraries",[...r]+"");for(k in g)e.set(k.replace(/[A-Z]/g,t=>"_"+t[0].toLowerCase()),g[k]);e.set("callback",c+".maps."+q);a.src=`https://maps.${c}apis.com/maps/api/js?`+e;d[q]=f;a.onerror=()=>h=n(Error(p+" could not load."));a.nonce=m.querySelector("script[nonce]")?.nonce||"";m.head.append(a)}));d[l]?console.warn(p+" only loads once. Ignoring:",g):d[l]=(f,...n)=>r.add(f)&&u().then(()=>d[l](f,...n))})
        ({key: "AIzaSyCJue_fSK533hqpKHe5LSSkgizsG9mzyXU", v: "beta"});
    },
    add_google_address_lookup:function( fields_obj = {} ) {
        // maps.add_google_address_lookup({on:239,lat:240,lng:241,visibility:{lat:false,lng:false}});
        if ( fields_obj.on ) {
            let on_field = ( Number.isInteger( fields_obj.on ) ) ? `#fields_${fields_obj.on}` : fields_obj.on;
            var to_field = ( fields_obj.to ) ? fields_obj.to : fields_obj.on;
            to_field = ( Number.isInteger( to_field ) ) ? `#fields_${to_field}` : to_field;
            $( on_field ).after( '<div id="google_address_lookup" class="input-large"></div>' )
            if ( fields_obj.visibility  ) {
                $( `.form-group-${fields_obj.lat}` ).toggle( fields_obj.visibility.lat )
                $( `.form-group-${fields_obj.lng}` ).toggle( fields_obj.visibility.lng )
            }
            async function init_map() {
                await google.maps.importLibrary("places");
                const placeAutocomplete = new google.maps.places.PlaceAutocompleteElement();
                $( '#google_address_lookup' ).html( placeAutocomplete )
                
                placeAutocomplete.addEventListener( "gmp-placeselect", async ({ place }) => {
                    await place.fetchFields({
                        fields: ["displayName", "formattedAddress", "location"],
                    });
                    const response_obj = place.toJSON();
                    const address = response_obj.formattedAddress;
                    $( to_field ).val( address );
                    if ( fields_obj.lat ) {
                        $( `#fields_${fields_obj.lat}` ).val( response_obj.location.lat );                        
                    }
                    if ( fields_obj.lng ) {
                        $( `#fields_${fields_obj.lng}` ).val( response_obj.location.lng );
                    }
                });
            }
            init_map();
        }
    },
    buildContent:function( marker ) {
        const content = document.createElement("div");    
        content.classList.add("marker");
        content.innerHTML = marker.html;
        return content;
    },
    toggleHighlight:function( markerView, marker ) {
        if ( markerView.content.classList.contains( "highlight" ) ) {
            markerView.content.classList.remove( "highlight" );
            markerView.zIndex = null;
        } else {
            markerView.content.classList.add( "highlight" );
            markerView.zIndex = 1;
        }
    },
    render:function( data ) {
        // console.log('in maps render function');
        if ( typeof google === 'object' && typeof google.maps === 'object' ) {
            // console.log('google maps already loaded');            
        } else {
            maps.init_google();
        }
        const maps_style = ( data.style ) ? data.style : `${core.plugin_path}css/maps_style.css`;      
        const zoom = ( data.zoom ) ? data.zoom : 11;
        console.log(zoom);
        if ( data.scripts ) {
            $.each( data.scripts, function( index, script ) {
                $.getScript( script, function() {
                    // console.log(`${script} library loaded in maps render.`);
                });
            });
        }
        const maps_div = ( data.div ) ? data.div : `<div style="height: 600px; width: 100%" id="${data.name}"></div>`;
        $( function() {
            $( '#maps' ).append( maps_div );
            $( '#maps' ).after( `<style id="maps_style"></style>` );
            $( "#maps_style" ).load( maps_style );
            render();
        });
        let render = function() {
            // console.log('render map',data);
            /**
            * @license
            * Copyright 2019 Google LLC. All Rights Reserved.
            * SPDX-License-Identifier: Apache-2.0
            */
            async function init_map() {
                // Request needed libraries.
                const { Map } = await google.maps.importLibrary( "maps" );
                const { AdvancedMarkerElement } = await google.maps.importLibrary( "marker" );
                const center = data.center;
                const map = new Map( document.getElementById( data.name ), {
                    zoom: zoom,
                    center,
                    mapId: "c76f6a9d031f9da0",
                    mapTypeControl: true,
                });                
                let render_map_markers = function( response ) {
                    // console.log(response);
                    if ( response != '') {
                        let response_obj = JSON.parse( response );
                        if ( response_obj.success ) {
                            // console.log(response_obj.data)
                            let data = response_obj.data;
                            $.each( data, function( index, marker ) {
                                let position = {lat: parseFloat( marker.position.lat ),lng: parseFloat( marker.position.lng )}
                                // console.log(position)
                                const AdvancedMarkerElement = new google.maps.marker.AdvancedMarkerElement({
                                    map,
                                    content: maps.buildContent( marker ),
                                    position: position,
                                    title: marker.description,
                                });                            
                                AdvancedMarkerElement.addListener( "gmp-click", () => {
                                    maps.toggleHighlight( AdvancedMarkerElement, marker );
                                });
                            });
                        }
                    }
                }
                core.ajax_post( data.url, data.markers, render_map_markers );
            }              
            init_map();           
        }
    },
    get_location:function( callback ) {
        console.log('in core get_location');
        if ( navigator.geolocation ) {
            navigator.geolocation.getCurrentPosition( callback );
        } else {
            console.log("Geolocation is not supported by this browser.");
            return {error: "Geolocation is not supported by this browser."};
        }
    },
    open_map_directions:function( element ) {
        // console.log(element)
        let destination = $( element ).data('destination');
        let url = `https://www.google.com/maps/dir/?api=1&travelmode=driving&destination=${destination}`;
        // console.log(url);
        window.open( url, '_blank' ).focus();
    },
    directions_btn_action:function() {
        $( '.directions-btn' ).on( 'click', function() {
            maps.open_map_directions( this );
            // console.log(this)
            // let destination = $( this ).data('destination');
            // let url = `https://www.google.com/maps/dir/?api=1&travelmode=driving&destination=${destination}`;
            // console.log(url);
            // window.open( url, '_blank' ).focus();
        });
    },
    get_google_direction_url:function( data ) {
        // see https://developers.google.com/maps/documentation/urls/get-started#directions-action
        let origin = `${data.origin.lat},${data.origin.lng}`;
        let destination = `${data.destination.lat},${data.destination.lng}`;
        let mode = ( data.mode ) ? `&travelmode=${data.travel_mode}` : '';
        let url = `https://www.google.com/maps/dir/?api=1&origin=${origin}&destination=${destination}${mode}`;
        return url;
    }
}

$( function() {
    core.expand_pre();
});