<?php

namespace Antevasin;

?>

var core = core || {
    expand_pre:function() {
        plugin.log('expand pre function')
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
    }  
}

$( function() {
    core.expand_pre();
});