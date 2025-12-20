<?php

namespace Antevasin;

global $app_logged_users_id, $this_plugin;

// print_rr($this_plugin); print_rr($core);
$url = url_for( 'antevasin/core/', 'token=' . $app_session_token );
$modules = json_encode( $this_plugin->get_modules( false ) );

?>

$.getScript( "<?php echo PLUGIN_PATH ?>js/jquery.serializeToJSON.js", function() {
    // console.log("jquery.serializeToJSON library loaded.");
});
var plugin = plugin || {  
    url: "<?php echo $url; ?>",  
    logged_users_id: <?php echo $app_logged_users_id; ?>,
    modules: <?php echo $modules; ?>, 
    country_code: "<?php echo CFG_APP_COUNTRY_CODE; ?>",  
    modal_url: '',
    form_url: '',
    action_url: '',
    form_element: null,
    form: {},
    log:function( text ) {
        console.log(text)   
    },
    on_modal_load:function( form ) {
        // console.log('on modal load',form)
        this.form_element = form;
        // console.log('plugin form',plugin.form)
        if ( Object.keys( plugin.form ).length === 0 ) {
            this.get_form();
            this.load_modal_form_js();
        }
    },
    on_submodal_load:function() {
        // console.log('on submodal load',plugin.form)
        let sub_items_form = $( '#sub_items_form' );
        // console.log('sub items form',sub_items_form)
        plugin.wait_until_exists( '#sub_items_form' ).then( function( element ) {
            // console.log('sub items form exists',element)
            let form = $( element );
            let hidden_input_elements = form.find('.form-body').find( 'input[type="hidden"]' );
            let info = { "action_url":form.prop( 'action' ) };
            $.each( hidden_input_elements, function( index, element ) {
                // console.log(index,element)
                let input = $( element );
                let value = input.val();
                info[input.prop( 'id' )] = input.val();
            }); 
            plugin.items_form( info.path );
            plugin.form['sub_items'] = info;
            console.log(`sub modal has loaded and exists - sub items entities id is ${plugin.form.sub_items.path} - entities id is ${plugin.form.entities_id}`,entity.ajax_fields) 
            if ( false ) { // field forms exists
                let forms_field_id = 581;
                let entity_name = entity.entities[plugin.form.entities_id]['name'];
                core.set_ajax_dropdown_value( {field_id:forms_field_id,id:plugin.form.entities_id,text:entity_name} );
                if ( plugin.form.sub_items.path > 0 ) plugin.run_function( `entity_${plugin.form.sub_items.path}` );
            } 
        });
    },
    get_entities_info:function( path ) {
        let paths = path.split( '/' );
        let entities_info = [];
        $.each( paths, function( index, path ) {
            let item_info = path.split( '-' );
            entities_info.push( item_info );
        });
        return entities_info;
    },
    load_modal_form_js:function() {
        var entities_id = 0;
        if ( this.form['path'] !== undefined ) {
            entities_info = this.get_entities_info( this.form['path'] );
            entities_id = entities_info[entities_info.length - 1][0];
            plugin.form.entities_id = entities_id;
        }
        switch ( this.form['type'] ) {
            case 'prepare_add_item_form':
                // console.log('this form',this.form);
                let callback = function( response ) {
                    // console.log('response',response);
                    let response_obj = JSON.parse( response );
                    if ( response_obj.success ) {
                        let data = response_obj.data;
                        js = `prepare_add_item_${data.entities_id}`;
                        plugin.run_function( js );
                    } else {
                        console.log('Error: ' + response_obj.data);
                    }
                }
                let reports_id = core.get_reports_id();
                let url = `${this.url}&action=get_reports_info&reports_id=${reports_id}`;
                // console.log('prepare_add_item_form url',url)
                core.ajax_get( url, callback );
                plugin.wait_until_exists( '#items_form' ).then( function( element ) {
                    console.log('in load modal form js wait until exists function about to run on_modal_load')
                    plugin.on_modal_load( $( '#items_form' ) );
                });
                return;
                break;
            case 'process':
                js = `process_${this.form['process_id']}`;
                break;
            case 'items_form':
                // console.log('form is items form - entities id is',this.form['entities_id'])
                js = `entity_${entities_id}`;
                // this.run_function( 'items_form' );
                plugin.items_form( entities_id );
                break;
            case 'form_single_field':
                js = 'form_single_field';
                break;
            default:
                console.log('form type default',this.form['type'])
                js = 'no function to run'
        }
        this.run_function( js );
    },
    run_function:function( function_name ) {
        $.each( this.modules, function( name, info ) {
            if ( window[name] ) {
                if ( name != '' && typeof window[name][function_name] === 'function' ) {
                    window[name][function_name]();
                }
            }
        });
        if ( typeof this[function_name] === 'function' ) this[function_name]();
    },
    run_module_action:function( module ) {
        let callback = function( response ) {
            alert(`Module action ${module} was run - check console for response`);
            console.log(response);
        }
        let url = `${this.url}&action=${module}`;
        core.ajax_get( url, callback );
    },
    items_form:function( entities_id = plugin.form.entities_id ) {
        console.log('in service items_form function',plugin.form.entities_id,entities_id,entity);
        if ( entity.ajax_fields.entity.name[entities_id] ) {
            // console.log(`entity ajax fields entity name for entities id`,entities_id,entity)
            let status_ajax_fields = Object.fromEntries(
                Object.entries( entity.ajax_fields.entity.name[entities_id] ).filter( ( [key] ) => key.includes( 'Status' ) )
            );
            // console.log('status ajax fiels',status_ajax_fields,'object keys length',Object.keys(status_ajax_fields).length);
            if ( Object.keys( status_ajax_fields ).length > 0 ) service.filter_status_field( entities_id );
            core.on_click_handler( '.btn-submodal-open', core.submodal_load );
            // $( '.btn-submodal-open' ).on( 'click', function() {
            //     console.log('clicked on submodal');
            //     plugin.on_submodal_load();
            // });
            // $.each( status_ajax_fields, function( title, fields ) {
            //     console.log( title, fields );
            //     $.each( fields, function( field_id, field ) {
            //         console.log( field_id, field );
                    
            //     });
            // });
        }
    },
    get_form:function() {
        plugin.form = {}
        // console.log('in get_form function',plugin.form)
        // let action = ( $( '#export-form' ).length > 0 ) ? $( 'form' ).prop( 'action' ) : this.form_element.prop( 'action' );
        // console.log('action',action,'form element',this.form_element); 
        let page_url = window.location.href;
        let form_url = this.form_element.prop( 'action' );
        let action_url = this.form_element.prop( 'action' );
        // console.log('page url',page_url,$( '.form-body #page_url' ).length )
        // console.log('modal url',this.modal_url)
        // console.log('form url',form_url)
        // console.log('action url',action_url); 
        if ( $( '.form-body #page_url' ).length == 0 ) $( '.form-body' ).prepend( `<input type="hidden" id="page_url" name="page_url" value="${page_url}">` );
        if ( $( '.form-body #modal_url' ).length == 0 ) $( '.form-body' ).prepend( `<input type="hidden" id="modal_url" name="modal_url" value="${this.modal_url}">` );
        if ( $( '.form-body #form_url' ).length == 0 ) $( '.form-body' ).prepend( `<input type="hidden" id="form_url" name="form_url" value="${form_url}">` );
        if ( $( '.form-body #action_url' ).length == 0 ) $( '.form-body' ).prepend( `<input type="hidden" id="action_url" name="action_url" value="${action_url}">` );
        // this.form = this.get_action_params( action );
        // console.log(this); 
        let info = {}
        info['element'] = this.form_element;
        // info['action'] = action;
        info['id'] = this.form_element.attr( 'id' ); // using .attr as using .prop returns any child elments with id="id"
        this.form['type'] = info['id'];
        info['name'] = this.form_element.prop( 'name' );
        info['method']= this.form_element.prop( 'method' );
        this.form['info'] = info;
        this.get_form_hidden_inputs();
        core.get_url_params();
        core.get_form_url_params();
        // console.log('in get_form functionn - this.form is ',this.form)
    },
    get_form_hidden_inputs:function() {
        let obj = this;
        let hidden_input_elements = this.form['info']['element'].find( 'input[type="hidden"]' );
        $.each( hidden_input_elements, function( index, element ) {
            let input = $( element );
            let value = input.val();
            obj.form[input.prop( 'id' )] = input.val();
        });
    },
    wait_until_exists:function( selector ) {
        return new Promise( resolve => {
            if ( document.querySelector( selector ) ) {
                return resolve( document.querySelector( selector ) );
            }    
            const observer = new MutationObserver( mutations => {
                if ( document.querySelector( selector ) ) {
                    observer.disconnect();
                    resolve( document.querySelector( selector ) );
                }
            });    
            // If you get "parameter 1 is not of type 'Node'" error, see https://stackoverflow.com/a/77855838/492336
            observer.observe( document.body, {
                childList: true,
                subtree: true
            });
        });
    },
    wait_until_modal_exists:function( modal_id ) {
        let selector = `#${modal_id}`;
        // console.log('wait until modal exists',modal_id,selector)
        plugin.wait_until_exists( selector ).then( function( element ) {
            // console.log('wait until exists - modal loaded')
            $( selector ).on( 'show.bs.modal', function() {
                // console.log('modal show event')
                let modal_form = $( selector + ' form' );
                // console.log('in wait until exists function and then plugin.wait_until_exists .then function about to run on_modal_load')
                if ( modal_form.length > 0 ) plugin.on_modal_load( modal_form );
            });
            $( '.btn-process-button-dropdown' ).on( 'click', function() {
                // console.log('actions dropdown clicked')
                plugin.wait_until_modal_exists( 'ajax-modal' );
                // $( this ).off( 'click' );
            })
            $( selector ).on( 'shown.bs.modal', function() {
                // console.log('modal shown event')
                // plugin.wait_until_modal_exists( 'ajax-modal' );                
            });
            $( selector ).on( 'hidden.bs.modal', function() {
                console.log('modal hidden event')
                plugin.form = {}
                setTimeout( plugin.wait_until_modal_exists, 500, 'ajax-modal' )
            });
        });  
    },
    get_modal_url:function( element ) {
        // console.log(element);
        var url = 'get_modal_url function url - here for development';
        let target = $( element.currentTarget );
        if ( target.prop( 'onclick' )  ) {
            // console.log('onclick exists')
            let onclick = target.prop( 'onclick' ).toString();
            url = onclick.match( /'([^']+)'/ )[1];
        } else if ( target.prop( 'href' ) ) {
            // console.log('href exists so return - modal_url not set')
            return;
        } else {
            url = 'no href or onclick';
        }
        this.modal_url = url;
    }
}

$( function() {
    // console.log('primary button')
    // $( '.btn-primary' ).on( 'click', function( e ) {
    //     console.log('primary button clicked')
    //     plugin.get_modal_url( e );
    //     // console.log('modal url',plugin.modal_url);
    //     $( '#ajax-modal' ).on( 'show.bs.modal', function( e ) {
    //         // console.log('primary button modal show event')
    //         let modal_form = $( '#ajax-modal form' );
    //         if ( modal_form.length > 0 ) plugin.on_modal_load( modal_form );
    //     });
    // });
    // allow for modal to be loaded from action buttons
    plugin.wait_until_modal_exists( 'ajax-modal' );
    // wait until entity_items_listing is loaded and then attach event listener for on click event
    plugin.wait_until_exists( '.listing-table-tr' ).then( function( element ) {
        // console.log('entity items listing exists')
        let default_button = $( '.btn-default' );
        $( '.btn-default' ).on( 'click', function( e ) {
            // console.log('default button clicked',e.target)
            plugin.get_modal_url( e );
            // console.log('default button modal url',plugin.modal_url);
            $( '#ajax-modal' ).on( 'show.bs.modal', function( e ) {
                // console.log('default button modal show event')
                let modal_form = $( '#ajax-modal form' );
                console.log(`in page load wait until exists .then function in btn-default on click and then ajax-modal on show about to run on_modal_load if modal_form.length > 0 - the value of it is ${modal_form.length}`)
                if ( modal_form.length > 0 ) plugin.on_modal_load( modal_form );
            });
        });
    });
});

( function () {
    this.entity_69 = function() {
        // console.log('run entity 69 plugin function',plugin.form)
        
    }; 
}).apply( plugin );




