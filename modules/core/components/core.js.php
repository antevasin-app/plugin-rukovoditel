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
    user_id: <?php echo $app_user['id']; ?>,
    user_name: "<?php echo $app_user['name']; ?>",
    username: "<?php echo $app_user['username']; ?>",
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
        let module_info = $( `#installed_module_${module}` );
        var file_url = module_info.data( 'release_url' );
        let private = module_info.data( 'private' );
        var source_token = module_info.data( 'source_token' );
        let data = {
            module_name: module,
            file_url: file_url,
            private: private,
            source_token: source_token
        }
        if ( action == 'latest_branch_commit' ) {
            let selected_branch = $( `#module_branches_${module}` ).find( ':selected' );
            let branch = selected_branch.val();
            let commit_sha = selected_branch.data( 'commit_sha' );
            let commit_date = selected_branch.data( 'commit_date' );
            let commit_url = selected_branch.data( 'commit_url' );
            file_url = selected_branch.data( 'branch_zip_url' ) + `&branch=${branch}&commit_sha=${commit_sha}&commit_date=${commit_date}&commit_url=${commit_url}`;
            source_token = $( `#module_branches_${module}` ).data( 'source_token' );
        }
        // console.log(file_url,source_token);        
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
            // console.log(element,action,module,core.files_url,file_url,url,private);
            open_dialog( url );
        }
        // return false;
    },
    format_date_string:function( date_string ) {
        let date = new Date( date_string );
        let options = { year: 'numeric', day: '2-digit', month: '2-digit', hour: 'numeric', minute: 'numeric', second: 'numeric', hour12: true, timeZone: '<?php echo CFG_APP_TIMEZONE ?>' };
        return date.toLocaleDateString( 'en-US', options );
    },
    filter_status_field:function() {
        // console.log('in filter_status_field function',plugin.form); 
        // console.trace();
        if ( plugin.form.entities_id ) {
            // let url = `${core.url}&action=filter_status_field&entities_id=${plugin.form.entities_id}`;
            let get_default_url = `${core.url}&action=filter_statuses&get_default=true&entities_id=${plugin.form.entities_id}`;
            // console.log(get_default_url);
            let get_default_callback = function( response ) {
                // console.log('in get_default_callback function',response);
                if ( response != '' ) {
                    let response_obj = JSON.parse( response );
                    // console.log(response_obj);
                    if ( response_obj.field_id && response_obj.default ) {
                        let field_id = response_obj.field_id;
                        $( `#btn_submodal_edit_item_${field_id}` ).hide()
                        let status_field = $( `#fields_${field_id}` );
                        status_field.on( 'select2:select', function ( e ) {
                            core.get_status_field_value_info( field_id );
                        });
                        if ( status_field.val() === null ) {
                            // console.log('status field has no value');
                            $.each( response_obj.default, function( index, option_obj ) {
                                core.set_ajax_dropdown_value( option_obj );                                
                            });
                        } else {
                            // console.log('status field already has a value',field_id,status_field,'status field value',status_field.val());
                            core.get_status_field_value_info( field_id );
                        }
                        let ajax_dropdown_callback = function( response ) {
                            // console.log('in ajax_dropdown_callback function',response);
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
    sub_modal_visibility:function( field_id, system ) {
        if ( system ) {
            $( `#btn_submodal_edit_item_${field_id}` ).hide()
        } else {
            $( `#btn_submodal_edit_item_${field_id}` ).show()
        }        
    },
    get_action_params:function( url = null ) {
        // console.log('url',url);
        let query_string = ( url === null ) ? window.location.search : new URL( url ).search;
        let search_params = new URLSearchParams( query_string );
        let module = search_params.get( 'module' );
        let params = {};
        console.log(search_params);
        for( const param of search_params ) {
            // console.log(param);
            let key = param[0];
            if ( module == 'items/processes' && key == 'id' ) {
                console.log('key',key,'value',param[1]);
                key = 'process_id';
            }
            if ( $( `#${key}` ).length == 0 ) {
                console.log('create input as it does not exist',key);
                $( '.form-body' ).prepend( `<input type="hidden" id="${key}" name="${key}" value="${param[1]}">` );
            }
            params[param[0]] = param[1];
        }
        return params;
    },
    get_form_url_params() {
        let properties = [ 'action_url', 'form_url', 'modal_url', 'page_url' ];
        $.each( properties, function( index, property ) {
            if ( plugin.form[property] ) {
                let params = core.get_url_params( plugin.form[property] );
                console.log(property,params);
                if ( params.module && params.action && params.id ) {
                    if ( params.module == 'items/processes' ) {
                        if ( typeof plugin.form.module === 'undefined' ) {
                            plugin.form['module'] = 'items/processes';
                        }
                        if ( typeof plugin.form.process_id === 'undefined' ) {
                            plugin.form['process_id'] = params.id;
                        }
                    }                    
                }
                if ( params.path ) {
                    if ( typeof plugin.form.path === 'undefined' ) {
                        plugin.form['path'] = params.path;
                    } 
                }
                if ( params.redirect_to ) {
                    if ( typeof plugin.form.redirect_to === 'undefined' ) {
                        plugin.form['redirect_to'] = params.redirect_to;
                    } 
                }
            }
        });
    },
    get_url_params:function( url = null ) {
        let query_string = ( url === null ) ? window.location.search : new URL( url ).search;
        let search_params = new URLSearchParams( query_string );
        let params = {};
        for( const param of search_params ) {
            params[param[0]] = param[1];
        }
        return params;
    },
    get_status_field_value_info:function( field_id ) {
        // console.log('in get_status_field_value_info',field_id);
        // look up the status field value and see if it is a system status
        let status_field = $( `#fields_${field_id}` );
        let url = `${core.url}&action=get_status_field_value_info&status_id=${status_field.val()}`;
        let callback = function( response ) {
            // console.log('in callback function',response);
            if ( response != '' ) {
                let response_obj = JSON.parse( response );
                core.sub_modal_visibility( field_id, response_obj.data.system );
            }
        }
        core.ajax_get( url, callback );
    },
    get_reports_id:function() {
        var reports_id = 0;
        if ( $( '#reports_id' ).length > 0 ) {
            reports_id = $( this ).val();
        } else {
            let url_params = core.get_url_params();
            // console.log(url_params);
            if ( url_params.reports_id ) reports_id = url_params.reports_id;
        }
        return reports_id;
    },
    get_user_id:function() {
        var user_id = 0;
        $.each( $( 'body' ).prop( 'class' ).split( ' ' ), function ( index, class_name ) {
            if ( class_name != '' ) {
                if ( class_name.startsWith( 'page-user-' ) ) {
                    let info = class_name.split( 'page-user-' ) 
                    user_id = info[1]
                } 
            }
        });
        return user_id;
    },
    get_uat_btn_url:function() {
        console.log('uat button clicked');
        let public_form_url = `<?php echo url_for( 'ext/public/form', 'id=1' ) ?>`
        let user_id = core.get_user_id();   
        let page_url = encodeURIComponent( window.location.href );
        let url = public_form_url + `&fields[1461]=` + user_id + `&fields[1448]=` + page_url;
        console.log(url);
        return url;
        // window.open( url, '_blank')
    },
    set_required_fields:function( fields, remove = false ) {
        // console.log(fields);
        $.each( fields, function( index, field_id ) {
            // $( `#fields_${field_id}` ).prop( 'required', true );
            let label = $( `.form-group-${field_id} label.control-label` );
            if ( remove ) {
                $( `#required_${field_id}` ).remove();
                $( `#fields_${field_id}` ).removeClass( 'required' );  
            } else {
                if ( $( `#required_${field_id}` ).length == 0 )
                {
                    console.log('adding required label');
                    label.prepend( `<span class="required-label" id="required_${field_id}">*</span>` );
                    $( `#fields_${field_id}` ).addClass( 'required' );            
                }
            }
        });
    },
    set_ajax_field_default:function( field_id, disabled = false ) {
        if ( plugin.form.entities_id ) {
            let url = `${core.url}&action=set_ajax_field_default&entities_id=${plugin.form.entities_id}&field_id=${field_id}`; 
            let callback = function( response ) {
                // console.log(response);
                if ( response != '' ) {
                    let response_obj = JSON.parse( response );
                    // console.log(response_obj);
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
        // console.log('in set_ajax_dropdown',fields_obj);
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
            templateResult: function( d ) { return $( '<span>' + d.text + '</span>' ) }
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
        // console.log(`option obj is `,option_obj,`option value to set is `,option_obj.id,`existing field value is`,field.val()); 
        if ( field.prop( 'multiple' ) ) {
            let selected_values = field.val() || [];
            // console.log('selected values before',selected_values);
            if ( !selected_values.includes( option_obj.id ) ) {
                // console.log('pushing value');
                selected_values.push( option_obj.id );
            }
            // console.log('selected values after',selected_values);
            field.val( selected_values )
        } else {
            field.val( option_obj.id );
        }
        if ( option_obj.disabled ) {
            this.disable_ajax_dropdown( option_obj.field_id );
        }
        field.trigger( 'change' );
    },    
    ajax_dropdown_trigger:function( fields_obj ) {
        console.log('in ajax_dropdown_trigger',fields_obj);
        let trigger_field = $( `#fields_${fields_obj.trigger_field_id}` );
        let status_field_id = fields_obj.status_field_id;
        let status_field = $( `#fields_${status_field_id}` );
        trigger_field.on( 'change', function() {
            switch ( fields_obj.action ) {
                case 'check':
                    if ( $( this ).is( ':checked' ) ) {
                        if ( fields_obj.disable ) {
                            core['current_status'] = status_field.val();
                            core.set_ajax_dropdown_value( {field_id:status_field_id,id:fields_obj.status_id,text:fields_obj.status} );
                            core.disable_ajax_dropdown( status_field_id );
                        }
                    } else {
                        if ( fields_obj.disable ) {
                            core.enable_ajax_dropdown( status_field_id );
                            status_field.val( core['current_status'] ).trigger( 'change' );
                        }
                    }
                    break;
                default:
                    break;
            }
        });
    },
    populate_contact_fields:function( fields_obj) {
        let trigger_field_id = fields_obj.trigger_field_id;
        let trigger_field = $( `#fields_${trigger_field_id}` );
        trigger_field.on( 'change', function() {
            let items_id = $( this ).val();     
            let url = `${core.url}&action=populate_contact_fields&field_id=${fields_obj.trigger_field_id}&items_id=${items_id}`;
            let callback = function( response ) {
                if ( response != '' ) {
                    let response_obj = JSON.parse( response );
                    if ( response_obj.success ) {
                        let data = response_obj.data;
                        // console.log(data.fields);
                        $.each( data.fields, function( field_id, items ) {
                            // console.log(field_id,items);
                            let field = $( `#fields_${field_id}` );
                            if ( field.val() === null || field.val().length == 0 ) {
                                $.each( items, function( items_id, title ) {
                                    // console.log(items_id,title);
                                    let option_obj = {field_id:field_id,id:items_id,text:title};
                                    core.set_ajax_dropdown_value( option_obj );
                                });
                            } else if ( items === '' ) {
                                // console.log('field already has a value and items is empty so clear fields');
                                // field.val( null ).trigger( 'change' );
                                $( `#fields_${field_id} option` ).remove();
                            }
                        });
                    }
                }
            }
            core.ajax_get( url, callback );     
        })
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

var ui = ui || {
    render_tabs:function( function_name, class_name = 'core' ) {
        let url = `${core.url}&action=render_tabs&function=${function_name}&class=${class_name}`;
        console.log('in ui render_tabs function',url);
        let callback = function( response ) {
            let tabs_container = $( `#${function_name}` );
            tabs_container.html( response ).css( 'height', '100vh' );
        };  
        core.ajax_get( url, callback );
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