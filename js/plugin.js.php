<?php

namespace Antevasin;

global $app_logged_users_id, $this_plugin;

// print_rr($this_plugin); print_rr($core);
$modules = json_encode( $this_plugin->get_modules() );

?>

$.getScript( "<?php echo PLUGIN_PATH ?>js/jquery.serializeToJSON.js", function() {
    // console.log("jquery.serializeToJSON library loaded.");
});
var plugin = plugin || {    
    logged_users_id: <?php echo $app_logged_users_id; ?>,
    modules: <?php echo $modules; ?>,   
    form_element: null,
    form: {},
    log:function( text ) {
        console.log(text)   
    },
    on_modal_load:function( form ) {
        // console.log('on modal load')
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
                console.log('this form',this.form)
                js = 'prepare_add_item_form';
                plugin.wait_until_exists( '#items_form' ).then( function( element ) {
                    plugin.on_modal_load( $( '#items_form' ) );
                });
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
        let info = {}
        let action = ( $( '#export-form' ).length > 0 ) ? $( 'form' ).prop( 'action' ) : this.form_element.prop( 'action' );
        // console.log(this.form_element,action); return;
        this.form = this.get_action_params( action );
        info['element'] = this.form_element;
        info['action'] = action;
        info['name'] = this.form_element.prop( 'name' );
        info['id'] = this.form_element.attr( 'id' ); // using .attr as using .prop returns any child elments with id="id"
        info['method']= this.form_element.prop( 'method' );
        this.form['info'] = info;
        this.form['type'] = info['id'];
        this.get_form_hidden_inputs();
        // console.log(this.form)
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
    get_action_params:function( url = null ) {
        let query_string = ( url === null ) ? window.location.search : new URL( url ).search;
        let search_params = new URLSearchParams( query_string );
        let module = search_params.get( 'module' );
        let params = {};
        for( const param of search_params ) {
            if ( module == 'items/processes' && param[0] == 'id' ) {
                param[0] = 'process_id';
            }
            params[param[0]] = param[1];
        }
        return params;
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
}

$( function() {
    // console.log('primary button')
    $( '.btn-primary' ).on( 'click', function() {
        // console.log('primary button clicked')
        $( '#ajax-modal' ).on( 'show.bs.modal', function() {
            // console.log('modal show event')
            let modal_form = $( '#ajax-modal form' );
            if ( modal_form.length > 0 ) plugin.on_modal_load( modal_form );
        });
    });
    plugin.wait_until_modal_exists( 'ajax-modal' );
    // wait until entity_items_listing is loaded and then attach event listener for on click event
    plugin.wait_until_exists( '.listing-table-tr' ).then( function( element ) {
        // console.log('default button')
        let default_button = $( '.btn-default' );
        $( '.btn-default' ).on( 'click', function() {
            console.log('default button clicked')
            $( '#ajax-modal' ).on( 'show.bs.modal', function() {
                // console.log('modal show event')
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




