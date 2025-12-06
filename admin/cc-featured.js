(function( $ ) {
    $(function() {
        if ( typeof ccFeatured === 'undefined' ) {
            return;
        }

        $( document ).on( 'click', '.cc-featured-toggle', function( event ) {
            event.preventDefault();

            var $button = $( this );
            var postId  = $button.data( 'post' );

            if ( ! postId ) {
                return;
            }

            $button.prop( 'disabled', true );

            $.post( ajaxurl, {
                action: 'cc_toggle_featured',
                nonce: ccFeatured.nonce,
                postId: postId
            } ).done( function( response ) {
                if ( response && response.success ) {
                    var isFeatured = response.data && response.data.featured === 1;

                    $button.attr( 'aria-pressed', isFeatured ? '1' : '0' );
                    $button.data( 'featured', isFeatured ? 1 : 0 );
                    $button.find( '.dashicons' )
                        .removeClass( 'dashicons-star-filled dashicons-star-empty' )
                        .addClass( isFeatured ? 'dashicons-star-filled' : 'dashicons-star-empty' );
                } else {
                    window.alert( response && response.data && response.data.message ? response.data.message : 'Unable to update featured status.' );
                }
            } ).fail( function() {
                window.alert( 'Unable to update featured status.' );
            } ).always( function() {
                $button.prop( 'disabled', false );
            } );
        } );
    } );
})( jQuery );
