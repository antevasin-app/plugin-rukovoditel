<?php

namespace Antevasin;

global $app_logged_users_id, $this_plugin;

// print_rr($this_plugin); print_rr($core);
$url = url_for( 'antevasin/core/', 'token=' . $app_session_token );
$modules = json_encode( $this_plugin->get_modules() );

?>

$.getScript( "<?php echo PLUGIN_PATH ?>js/jquery.serializeToJSON.js", function() {
    // console.log("jquery.serializeToJSON library loaded.");
});
var plugin = plugin || {  
    url: "<?php echo $url; ?>",  
    logged_users_id: <?php echo $app_logged_users_id; ?>,
    modules: <?php echo $modules; ?>,   
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
        this.get_form();   
        this.load_modal_form_js();
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
                    plugin.on_modal_load( $( '#items_form' ) );
                });
                return;
                break;
            case 'process':
                js = `process_${this.form['process_id']}`;
                break;
            case 'items_form':
                js = `entity_${entities_id}`
                break;
            default:
                js = 'no function to run'
        }
        this.run_function( js );
    },
    run_function:function( function_name ) {
        // console.log('js function to run',js)
        $.each( this.modules, function( name, info ) {
            if ( window[name] ) {
                if ( name != '' && typeof window[name][js] === 'function' ) {
                    window[name][js]();
                }
            }
        });
        if ( typeof this[js] === 'function' ) this[js]();
    },
    get_form:function() {
        // console.trace();
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
        // return;

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
        core.get_form_url_params();
        console.log('in get_form functionn - this.form is ',this.form)
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
        // console.log('wait until modal exists',modal_id)
        let selector = `#${modal_id}`;
        // console.log('wait until modal exists',modal_id,selector)
        plugin.wait_until_exists( selector ).then( function( element ) {
            // console.log('wait until exists - modal loaded')
            $( selector ).on( 'show.bs.modal', function() {
                // console.log('modal show event')
                let modal_form = $( selector + ' form' );
                if ( modal_form.length > 0 ) plugin.on_modal_load( modal_form );
            });
            $( '.btn-process-button-dropdown' ).on( 'click', function() {
                // console.log('actions dropdown clicked')
                plugin.wait_until_modal_exists( 'ajax-modal' );
                // $( this ).off( 'click' );
            })
            $( selector ).on( 'shown.bs.modal', function() {
                // console.log('modal shown event')
                plugin.wait_until_modal_exists( 'ajax-modal' );                
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




