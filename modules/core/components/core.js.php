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
    buildContent_:function( property ) {
        const content = document.createElement("div");
    
        content.classList.add("property");
        content.innerHTML = `
        <div class="icon">
            <i aria-hidden="true" class="fa fa-icon fa-${property.type}" title="${property.type}"></i>
            <span class="fa-sr-only">${property.type}</span>
        </div>
        <div class="details">
            <div class="price">${property.price}</div>
            <div class="address">${property.address}</div>
            <div class="features">
            <div>
                <i aria-hidden="true" class="fa fa-bed fa-lg bed" title="bedroom"></i>
                <span class="fa-sr-only">bedroom</span>
                <span>${property.bed}</span>
            </div>
            <div>
                <i aria-hidden="true" class="fa fa-bath fa-lg bath" title="bathroom"></i>
                <span class="fa-sr-only">bathroom</span>
                <span>${property.bath}</span>
            </div>
            <div>
                <i aria-hidden="true" class="fa fa-ruler fa-lg size" title="size"></i>
                <span class="fa-sr-only">size</span>
                <span>${property.size} ft<sup>2</sup></span>
            </div>
            </div>
        </div>
        `;
        return content;
    },
    buildContent:function( marker ) {
        const content = document.createElement("div");
    
        content.classList.add("marker");
        content.innerHTML = marker.html;
        // content.innerHTML = `
        // <div class="icon">
        //     <i aria-hidden="true" class="fa fa-icon fa-${marker.type}" title="${marker.type}"></i>
        //     <span class="fa-sr-only">${marker.type}</span>
        // </div>
        // <div class="details">
        //     <div class="price">${marker.price}</div>
        //     <div class="address">${marker.address}</div>
        //     <div class="features">
        //     <div>
        //         <i aria-hidden="true" class="fa fa-bed fa-lg bed" title="bedroom"></i>
        //         <span class="fa-sr-only">bedroom</span>
        //         <span>${marker.bed}</span>
        //     </div>
        //     <div>
        //         <i aria-hidden="true" class="fa fa-bath fa-lg bath" title="bathroom"></i>
        //         <span class="fa-sr-only">bathroom</span>
        //         <span>${marker.bath}</span>
        //     </div>
        //     <div>
        //         <i aria-hidden="true" class="fa fa-ruler fa-lg size" title="size"></i>
        //         <span class="fa-sr-only">size</span>
        //         <span>${marker.size} ft<sup>2</sup></span>
        //     </div>
        //     </div>
        // </div>
        // `;
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
                            
                                AdvancedMarkerElement.addListener( "click", () => {
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
        if ( navigator.geolocation ) {
            navigator.geolocation.getCurrentPosition( callback );
        } else {
            console.log("Geolocation is not supported by this browser.");
            return {error: "Geolocation is not supported by this browser."};
        }
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