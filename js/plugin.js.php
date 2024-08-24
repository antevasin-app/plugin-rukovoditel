<?php

namespace Antevasin;

?>

var plugin = plugin || {
    form_element: null,
    form: {},
    log:function( text ) {
        console.log(text)   
    },
    on_modal_load:function( form ) {
        this.form_element = form;
        this.get_form();   
        this.load_modal_form_js();
    },
    load_modal_form_js:function() {
        switch ( this.form['type'] ) {
            case 'process':
                js = `process_${this.form['process_id']}`

                break;
            case 'items_form':
                js = `entity_${this.form['path']}`
                break;
            default:
                js = 'no function to run'
        }
        console.log('js to run',js)
        if ( typeof this[js] === 'function' ) this[js]();
    },
    get_form:function() {
        let info = {}
        let action = this.form_element.prop( 'action' );
        this.form = this.get_action_params( action );
        info['element'] = this.form_element;
        info['action'] = action;
        info['name'] = this.form_element.prop( 'name' );
        info['id'] = this.form_element.attr( 'id' ); // using .attr as using .prop returns any child elments with id="id"
        info['method']= this.form_element.prop( 'method' );
        this.form['info'] = info;
        this.form['type'] = info['id'];
        this.get_form_hidden_inputs();
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
        let query_string = ( url === null ) ? window.location.search :new URL( url ).search;
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
    testing:function() {
        console.log('this is the plugin testing function')
    }
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
    // plugin.entity_26 = function() {
    //     console.log('run entity 26 test function')
    // }; 
    // plugin.process_1 = function() {
    //     console.log('run process 1 test function')
    // };
});

( function () {
    this.entity_26 = function() {
        console.log('run entity 26 test function')
    }; 
    this.process_1 = function() {
        console.log('run process 1 test function')
    };
}).apply( plugin );




