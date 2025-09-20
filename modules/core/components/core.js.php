<?php

    namespace Antevasin;

    global $app_session_token, $is_logged_on;

    // print_rr("in core.js.php is logged on is $is_logged_on");
    $app_path_dir = ( true ) ? 'app/' : '';
    $app_path = ( defined( 'APP_PATH' ) ) ? APP_PATH : '';
    $url = url_for( 'antevasin/core/', 'token=' . $app_session_token );
    $files_url = url_for( 'antevasin/core/files', 'token=' . $app_session_token );
    $core = new core();
    $entity_ajax_fields = $core->get_entiity_fields_info( 'entity_ajax', true );
    $entities = $core->get_entities( true );
    $user_id = ( isset( $app_user['id'] ) ? $app_user['id'] : 0 );
    $user_name = ( isset( $app_user['name'] ) ? $app_user['name'] : '' );
    $username = ( isset( $app_user['username'] ) ? $app_user['username'] : '' );
    $is_logged_on = ( $is_logged_on ) ? 'true' : 'false';
    // $url_token_param = ( $is_logged_on ) ? "&token={$app_session_token}" : "";
    // print_rr("in core.js.php is logged on is $is_logged_on url_token_param is $url_token_param");
?>

var core = core || {
    app_path: "<?php echo $app_path; ?>",
    url: "<?php echo $url; ?>",
    files_url: "<?php echo $files_url; ?>",
    plugin_path: "<?php echo PLUGIN_PATH; ?>",
    ajax_headers: {},    
    user_id: <?php echo $user_id ?>,
    user_name: "<?php echo $user_name ?>",
    username: "<?php echo $username ?>",
    is_logged_on: '<?php echo ( $is_logged_on ) ? 'true' : 'false' ?>',
    url_token_param: '<?php echo ( $is_logged_on ) ? "&token={$app_session_token}" : "" ?>',
    session_token: '<?php echo "$app_session_token" ?>',
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
    ajax_post:function( url, data, callback = this.console_response ) {
        $.ajax({
            method: "POST",
            url: url,
            data: data,
            headers: core.ajax_headers
        })
        .done( callback )
        .fail( function( jqXHR, textStatus, errorThrown ) {
            // do something based on failure
            core.log_ajax_error( jqXHR, textStatus, errorThrown );
        })
    },
    ajax_promise_post:function( url, data, callback = this.console_response ) {
        let settings = {
            method: "POST",
            url: url,
            data: data,
            headers: core.ajax_headers
        }
        if ( Object.keys( core.ajax_headers ).length > 0 ) {
            settings.headers = core.ajax_headers;
        }
        const promise = $.ajax( settings )
            .done( function( response ) {
                callback( response );
            })
            .fail( function( jqXHR, textStatus, errorThrown ) {
                // do something based on failure
                core.log_ajax_error( jqXHR, textStatus, errorThrown );
            });
        core.ajax_headers = {}
        return promise;
    },    
    ajax_get:function( url, callback = this.console_response ) {
        let settings = {
            method: "GET",
            url: url
        }
        if ( Object.keys( core.ajax_headers ).length > 0 ) {
            settings.headers = core.ajax_headers;
        }
        $.ajax( settings )
        .done( function( response ) {
            callback( response );
        })
        .fail( function( jqXHR, textStatus, errorThrown ) {
            // do something based on failure
            core.log_ajax_error( jqXHR, textStatus, errorThrown );
        });
        core.ajax_headers = {}
    },
    ajax_promise_get:function( url, callback = this.console_response ) {
        let settings = {
            method: "GET",
            url: url
        }
        if ( Object.keys( core.ajax_headers ).length > 0 ) {
            settings.headers = core.ajax_headers;
        }
        const promise = $.ajax( settings )
            .done( function( response ) {
                callback( response );
            })
            .fail( function( jqXHR, textStatus, errorThrown ) {
                // do something based on failure
                core.log_ajax_error( jqXHR, textStatus, errorThrown );
            });
        core.ajax_headers = {}
        return promise;
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
    submodal_load:function() {
            // console.log('clicked on submodal');
            plugin.on_submodal_load();
    },  
    form_single_field:function() {
        console.log('in form_single_field function',plugin.form.url_params.action_url.field_id);
        let field_id = plugin.form.url_params.action_url.field_id;
        // if ( $( `#fields_${field_id}` ).val() == 1 ) $( `#btn_submodal_edit_item_${field_id}` ).hide()
    },
    on_click_handler:function( selector, handler ) {
            var $elements = $( selector );
            $elements.each( function() {
                var events = $._data( this, 'events' );
                // Check if 'click' events exist and if the handler is already bound
                if ( !events || !events.click || !events.click.some( function( e ) {
                    return e.handler === handler;
                })) {
                    // Add the handler if it doesn't exist
                    $( this ).on( 'click', handler );
                }
            });
    },
    sub_modal_visibility:function( field_id, system ) {
        if ( system ) {
            $( `#btn_submodal_edit_item_${field_id}` ).hide()
        } else {
            $( `#btn_submodal_edit_item_${field_id}` ).show()
        }        
    },
    get_cookie:function( name, path = false ) {
        console.log('in get_cookie function',name,path);
        // Get all cookies as a string
        var cookies = document.cookie.split( ';' );
        
        // Iterate through cookies
        for ( var i = 0; i < cookies.length; i++ ) {
            var cookie = $.trim( cookies[i] ); // Trim whitespace using jQuery
            var cookieParts = cookie.split( '=' );
            var cookieName = cookieParts[0];            
            // Check if this is the cookie we're looking for
            if ( cookieName === name ) {
                // Get cookie value
                var cookieValue = cookieParts.slice( 1 ).join( '=' );
                console.log('found cookie',document.cookie,cookieName,cookieValue,cookieParts);
                // Check if the cookie's path matches (optional, as document.cookie only returns accessible cookies)
                // Note: document.cookie doesn't include path info, so path check requires server-side or manual tracking
                if ( path ) {
                    // Since document.cookie doesn't provide path info, you may need to maintain a map of cookie paths
                    // This is a limitation of client-side JavaScript; path filtering is typically handled server-side
                    console.warn("Path verification is limited client-side. Ensure the current page path matches the cookie path.");
                }                
                return decodeURIComponent(cookieValue); // Decode the cookie value
            }
        }        
        return null; // Cookie not found
    },
    get_action_params:function( url = null ) {
        // console.log('url',url);
        let query_string = ( url === null ) ? window.location.search : new URL( url ).search;
        let search_params = new URLSearchParams( query_string );
        let module = search_params.get( 'module' );
        let params = {};
        // console.log(search_params);
        for( const param of search_params ) {
            // console.log(param);
            let key = param[0];
            if ( module == 'items/processes' && key == 'id' ) {
                console.log('key',key,'value',param[1]);
                key = 'process_id';
            }
            if ( $( `#${key}` ).length == 0 ) {
                // console.log('create input as it does not exist',key);
                $( '.form-body' ).prepend( `<input type="hidden" id="${key}" name="${key}" value="${param[1]}">` );
            }
            params[param[0]] = param[1];
        }
        return params;
    },
    get_form_url_params() {
        let properties = [ 'action_url', 'form_url', 'modal_url', 'page_url' ];
        // Initialize plugin.form.url_params as an object if it doesn't exist
        if ( !plugin.form.url_params ) plugin.form.url_params = {};
        $.each( properties, function( index, property ) {
            if ( plugin.form[property] ) {
                let params = core.get_url_params( plugin.form[property] );
                // console.log('property and params',property,params);
                plugin.form.url_params[property] = params;
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
        let query_string = window.location.search; // Default fallback
        if ( url != null && typeof url === 'string' && url !== '' ) {
            try {
                query_string = new URL(url).search;
            } catch (e) {
                console.error('Invalid URL provided:', url, e);
            }
        }
        let search_params = new URLSearchParams( query_string );
        let params = {};
        for( const param of search_params ) {
            params[param[0]] = param[1];
        }
        if ( params.path ) {
            if ( typeof plugin.form.path === 'undefined' ) {
                plugin.form['path'] = params.path;
            } 
        }
        // console.log('params',params);
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
    render_system_buttons:function( buttons_obj ) {
        console.log('in get_system_btn function',buttons_obj);
        var buttons = '';
        $.each( buttons_obj, function( id, button ) {
            // console.log('button',button);
            try {
                let icon = ( button.icon ) ? `<i class="fa ${button.icon}"></i> ` : '';
                let params = ( button.params ) ? button.params : {};
                let url = core.get_system_url( button.module, params );
                let js_onclick = ( button.modal ) ? `open_dialog('${url}'); return false;` : `window.location.assign('${url}')`;
                buttons += `<button onclick="${js_onclick}" class="btn btn-primary" type="button">${icon} ${button.title}</button>`;

            } catch (error) {
                console.error('Error creating button:', error.message, error);
            }
        });
        plugin.wait_until_exists( '#system_buttons' ).then( function( element ) {
            $( '#system_buttons' ).html( buttons );
        });        
    },
    get_system_url:function( module, params = {} ) {
        // console.log('in get_system_url function',module);
        var url_params = [];
        $.each( params, function( key, value ) {
                url_params.push( `${key}=${value}` );
        });
        console.log('url params',url_params);
        let url = `<?php echo url_for( '${module}' ) ?>&${url_params.join('&')}`;
        return url;
    },
    get_uat_btn_url:function() {
        console.log('uat button clicked');
        let public_form_url = `<?php echo url_for( 'ext/public/form', 'id=1' ) ?>`
        let user_id = core.get_user_id();  
        let window_url = window.location.href.replace( new RegExp( "https?:\/\/","gm" ), '' ); 
        let page_url = encodeURIComponent( window_url );
        let url = public_form_url + '&fields[183]=69' + `&fields[1461]=` + user_id + `&fields[1448]=` + page_url;
        console.log(window_url,url);
        return url;
        // window.open( url, '_blank')
    },
    set_app_cookie:function() {
        // console.log('in set_app_cookie function core app_path is ',core.app_path);
        // if ( core.app_path.length === 0 ) core.set_cookie( `app_token`, '<?php echo $app_session_token ?>', 'Session', '/' );
        core.set_cookie( `app_token`, '<?php echo $app_session_token ?>', 'Session', '/' )
    },
    set_cookie:function( name, value, days, path ) {
        var expires = "";
        if ( days ) {
            var date = new Date();
            date.setTime( date.getTime() + ( days * 24 * 60 * 60 * 1000 ) );
            expires = "; expires=" + date.toUTCString();
        }
        var pathStr = path ? "; path=" + path : "";
        document.cookie = name + "=" + encodeURIComponent( value ) + expires + pathStr;
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
            // console.log(url)
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
        if ( plugin.form.type == 'prepare_add_item_form' ) current_from_id = plugin.form.type;
        // console.log('form',plugin.form,'fields obj',fields_obj,'current form id',current_from_id);
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
        let dropdown = ( Number.isInteger( fields_obj.field_id ) ) ? $( `#fields_${fields_obj.field_id}` ) : $( `#${fields_obj.field_id}` );
        // if ( fields_obj.field_id == 'select2-parent_item_id-container' ) dropdown = $( `#parent_item_id` );
        // console.log('field id',fields_obj.field_id,'dropdown',dropdown);
        $( function() {
            dropdown.select2( 'destroy' );        
            dropdown.select2( obj );
            if ( fields_obj.callback ) fields_obj.callback();
        })
    },
    set_ajax_dropdown_value:function( option_obj ) {
        // console.log('in set_ajax_dropdown_value function - option object',option_obj);
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
        // console.log('in ajax_dropdown_trigger',fields_obj);
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
                case 'true':
                    if ( $( this ).val() == 'true' ) {
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
    populate_job_fields:function( fields_obj ) {
        $.each( fields_obj, function( field_name, field_id ) {
            let data = { 'fields': fields_obj };
            $( `#fields_${field_id}` ).on( 'change', function() {
                let items_ids = $( this ).val(); 
                data['field_name'] = field_name;
                data['field_id'] = field_id;
                data['items_ids'] = items_ids;
                let url = `${core.url}&action=populate_job_fields`;
                core.ajax_post( url, data, function( response ) {
                    // console.log('in populate_job_fields callback function',response);
                    core.job_fields_customer( response, fields_obj );
                })
            })
        });
    },
    populate_fields:function() {
        console.log('in populate_fields function');
        var shift = ctrl = false;
        $( document ).on( 'keydown', function( event ) {
            if ( event.shiftKey ) {
                console.log('Shift key is pressed');
                shift = true;
            }
            if ( event.ctrlKey ) {
                console.log('Ctrl key is pressed');
                ctrl = true;
            }
        });
        let entity_ajax_fields = $( '.form-control.fieldtype_entity_ajax' );
        $.each( entity_ajax_fields, function( index, element ) {
            let field = $( element ).attr( 'id' );
            console.log('field',element,field);
            let data = { 'shift': shift, 'ctrl': ctrl };
            $( `#${field}` ).on( 'change', function() {
                let items_ids = $( this ).val(); 
                // data['field_name'] = field_name;
                data['field'] = field;
                data['items_ids'] = items_ids;
                let url = `${core.url}&action=populate_fields`;
                core.ajax_post( url, data, function( response ) {
                    console.log('in populate_job_fields callback function',response);
                    // core.job_fields_customer( response, fields_obj );
                })
            })
        });
    },
    job_fields_customer:function( response, fields_obj ) {
        if ( response !== '' ) {
            let response_obj = JSON.parse( response );
            if ( response_obj.success ) {
                let response_data = response_obj.data;
                // console.log(response,data,data.customer_info);
                if ( response_data == '' ) {
                    $.each( fields_obj, function( field_name, field_id ) {
                        // console.log('empty fields',field_name,field_id);
                        $( `#fields_${field_id}` ).empty();
                    });
                }
                $( `#fields_${fields_obj.customer_info}` ).val( response_data.customer_info );
                $.each( response_data.fields, function( field_id, items ) {
                    let field = $( `#fields_${field_id}` ); 
                    field.off( 'change' );
                    // Get current options in the Select2 dropdown
                    let current_options = field.find( 'option' ).map( function() {
                        return $( this ).val();
                    }).get();            
                    if ( field.val() === null || field.val().length === 0 ) {
                        // Case 1: Field is empty, populate with new items
                        $.each( items, function( items_id, title ) {
                            let option_obj = { field_id: field_id, id: items_id, text: title };
                            core.set_ajax_dropdown_value( option_obj );
                        }); 
                    } else if ( Object.keys( items ).length === 0 ) {
                        // console.log('items is empty');
                        field.empty().trigger( 'change' );
                    } else {
                        // Case 3: Field has values, update options
                        // Remove options that are no longer in the response
                        current_options.forEach( function( option_value ) {
                            if ( !items.hasOwnProperty( option_value ) ) {
                                field.find( `option[value="${option_value}"]` ).remove();
                            }
                        });
    
                        // Add or update options from the response
                        Object.keys( items ).forEach( key => {
                            // Check if the option with this value (key) already exists
                            if ( !field.find( `option[value="${key}"]` ).length) {
                                let option_obj = { field_id: field_id, id: key, text: items[key] };
                                core.set_ajax_dropdown_value( option_obj );
                            } else {
                                // Update the text of existing option if necessary
                                let existing_option = field.find( `option[value="${key}"]` );
                                if ( existing_option.text() !== items[key] ) {
                                    existing_option.text( items[key] );
                                }
                            }
                        });            
                        // Trigger change to refresh Select2
                        field.trigger( 'change' );
                    }
                });
                // console.log(response_data.fields,fields_obj);
                const fields_obj_flippped = Object.fromEntries(
                    Object.entries( fields_obj ).map( ( [key, value] ) => [value, key] )
                );
                // console.log(fields_obj_flippped);
                $.each( response_data.fields, function( field_id, items ) {
                    let data = { 'fields': fields_obj };
                    $( `#fields_${field_id}` ).on( 'change', function() {
                        let items_ids = $( this ).val(); 
                        data['field_name'] = fields_obj_flippped[field_id];
                        data['field_id'] = field_id;
                        data['items_ids'] = items_ids;
                        console.log(data);
                        let url = `${core.url}&action=populate_job_fields`;
                        core.ajax_post( url, data, function( response ) {
                            // console.log('in populate_job_fields callback function',response);
                            core.job_fields_customer( response, fields_obj );
                        })
                    })
                });
            }
        }
    },
    job_fields_addresses:function( response, fields_obj ) {
        console.log(response,fields_obj)
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
    setup_manual_address:function() {
        console.log('in manual_address function');
        let address_field = $( '#fields_557' );
        if ( address_field.val().length > 0 ) {
            // console.log('address field has a value');
        }
        if ( $( '#save_manual_address' ).length == 0 ) {
            $( '#fields_566_rendered_value' ).after( '<div style="clear: both;"><button id="save_manual_address" class="btn" type="button" style="margin-top: 5px;">Save Address</button></div>' )
            $( '#save_manual_address' ).on( 'click', function() {
                console.log('save manual address button clicked');
                let manual_address_fields = [ 562, 563 ,564 ,565 ,566 ];
                let manual_address = [];
                $.each( manual_address_fields, function( index, field_id ) {
                    let field = $( `#fields_${field_id}` );
                    let value = field.val();
                    if ( value ) {
                        manual_address.push( value );
                    }
                });
                let address = manual_address.join( ', ' );
                address_field.val( address );
            }); 
        }
        
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
    render_tabs: function( function_name, class_name = 'core' ) {
        let url = `${core.url}&action=render_tabs&function=${function_name}&class=${class_name}`;
        console.log('in ui render_tabs function',url);
        let callback = function( response ) {
            let tabs_container = $( `#${function_name}` );
            tabs_container.html( response ).css( 'height', '100vh' );
        };  
        core.ajax_get( url, callback );
    }
}

let placesLoaded = false;

// Load Google Maps Places API dynamically
function loadPlacesAPI() {
    if (placesLoaded) return Promise.resolve();
    return new Promise((resolve, reject) => {
        if (window.google && window.google.maps && window.google.maps.places) {
            placesLoaded = true;
            resolve();
            return;
        }
        const script = document.createElement('script');
        script.src = 'https://maps.googleapis.com/maps/api/js?key=AIzaSyCJue_fSK533hqpKHe5LSSkgizsG9mzyXU&libraries=places&callback=initPlacesCallback';
        script.async = true;
        script.defer = true;
        script.onload = () => {
            // console.log('Google Maps Places API loaded successfully');
            placesLoaded = true;
            resolve();
        };
        script.onerror = () => {
            console.error('Failed to load Google Maps Places API script');
            reject(new Error('Failed to load Google Maps Places API'));
        };
        document.head.appendChild(script);
    });
}

// Callback function for Google Maps script
window.initPlacesCallback = function( fields_obj ) {
    placesLoaded = true;
};

var google_places = google_places || {
    // Initialize Places API and dynamically create input
    initPlaces: async function() {
        // console.log('in initPlaces function');
        $( '#fields_557' ).after( '<div id="address-details"></div>')
        const error_div = document.getElementById('address-details');
        $( '#fields_557' ).after( '<div id="input-container"></div>')
        const inputContainer = document.getElementById('input-container');
        
        try {
            await loadPlacesAPI();
            if (!window.google || !window.google.maps || !window.google.maps.places) {
                throw new Error('Google Maps Places library not available');
            }

            const { Place, AutocompleteSessionToken, AutocompleteSuggestion } = await google.maps.importLibrary("places");
            // console.log('Places library imported successfully');

            // Clear existing content in input container
            inputContainer.innerHTML = '';

            // Dynamically create input element
            const input = document.createElement('input');
            input.type = 'text';
            input.id = 'address-input';
            input.className = 'form-control';
            input.placeholder = 'Enter an address to search for...';

            // Dynamically create suggestions div
            const suggestionsDiv = document.createElement('div');
            suggestionsDiv.id = 'suggestions';

            // Append elements to input container
            inputContainer.appendChild(input);
            inputContainer.appendChild(suggestionsDiv);

            // Clear details
            error_div.innerHTML = '';

            let sessionToken = new AutocompleteSessionToken();

            // Fetch suggestions on input
            input.addEventListener('input', async () => {
                if (input.value.length < 3) {
                    suggestionsDiv.style.display = 'none';
                    return;
                }

                try {
                    const request = {
                        input: input.value,
                        sessionToken: sessionToken
                    };

                    const { suggestions } = await AutocompleteSuggestion.fetchAutocompleteSuggestions(request);
                    // console.log('Fetched suggestions:', suggestions);

                    // Display suggestions
                    suggestionsDiv.innerHTML = '';
                    suggestions.forEach(suggestion => {
                        const div = document.createElement('div');
                        div.className = 'suggestion';
                        div.textContent = suggestion.placePrediction.text.text;
                        div.dataset.placeId = suggestion.placePrediction.placeId;
                        div.addEventListener('click', () => this.handlePlaceSelection(suggestion.placePrediction, sessionToken));
                        suggestionsDiv.appendChild(div);
                    });
                    suggestionsDiv.style.display = suggestions.length ? 'block' : 'none';
                } catch (error) {
                    console.error('Error fetching suggestions:', error.message, error);
                    suggestionsDiv.style.display = 'none';
                    error_div.innerHTML = 'Error fetching suggestions. Check console for details.';
                }
            });

            // Hide suggestions when clicking outside
            document.addEventListener('click', (e) => {
                if (!input.contains(e.target) && !suggestionsDiv.contains(e.target)) {
                    suggestionsDiv.style.display = 'none';
                }
            });
        } catch (error) {
            console.error('Error initializing Places API:', error.message, error);
            error_div.innerHTML = 'Error loading address lookup. Please check your API key or network connection.';
        }
    },
    handlePlaceSelection: async function (placePrediction, sessionToken) {
        const suggestionsDiv = document.getElementById('suggestions');
        const error_div = document.getElementById('address-details');
        suggestionsDiv.style.display = 'none';
    
        try {
            const place = placePrediction.toPlace();
            await place.fetchFields({
                fields: ['displayName', 'formattedAddress', 'location']
            });
    
            $( '#fields_557' ).val( place.formattedAddress );
            $( '#fields_568' ).val( place.location.lat() );
            $( '#fields_569' ).val( place.location.lng() );
            console.log('Place details fetched:', place);
    
            // Reset session token after successful selection
            sessionToken = new google.maps.places.AutocompleteSessionToken();
        } catch (error) {
            console.error('Error fetching place details:', error.message, error);
            error_div.innerHTML = 'Error fetching address details. Check console for details.';
        }
    }    
}

var entity = entity || {
    ajax_fields: <?php echo $entity_ajax_fields ?>,
    entities: <?php echo $entities ?>,
}

$( function() {
    core.expand_pre();
    core.set_app_cookie();
});
