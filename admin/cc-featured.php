<?php
if ( ! defined( 'ABSPATH' ) ) exit;

function cc_add_featured_metabox() {
    add_meta_box(
        'cc_featured_comic',
        esc_html__( 'Featured Comic', 'comic-calendar' ),
        'cc_render_featured_metabox',
        'cc_comic',
        'side'
    );
}
add_action( 'add_meta_boxes', 'cc_add_featured_metabox' );

function cc_add_featured_quick_edit_field( $column_name, $post_type ) {
    if ( 'cc_comic' !== $post_type || ! in_array( $column_name, array( 'title', 'featured' ), true ) ) {
        return;
    }

    wp_nonce_field( 'cc_featured_quick_edit', 'cc_featured_quick_edit_nonce' );
    ?>
    <fieldset class="inline-edit-col-left">
        <div class="inline-edit-col">
            <label class="alignleft">
                <span class="title"><?php esc_html_e( 'Featured', 'comic-calendar' ); ?></span>
                <span class="input-text-wrap">
                    <input type="checkbox" name="cc_featured" value="1" />
                </span>
            </label>
        </div>
    </fieldset>
    <?php
}
add_action( 'quick_edit_custom_box', 'cc_add_featured_quick_edit_field', 10, 2 );

function cc_render_featured_metabox( $post ) {
    $is_featured = get_post_meta( $post->ID, 'cc_featured', true );
    wp_nonce_field( 'cc_featured_save_' . $post->ID, 'cc_featured_nonce' );
    ?>
    <p>
        <label for="cc_featured">
            <input type="checkbox" name="cc_featured" id="cc_featured" value="1" <?php checked( $is_featured, '1' ); ?> />
            <?php esc_html_e( 'Mark this comic as featured', 'comic-calendar' ); ?>
        </label>
    </p>
    <p class="description"><?php esc_html_e( 'Featured comics can be highlighted in the widget.', 'comic-calendar' ); ?></p>
    <?php
}

function cc_save_featured_metabox( $post_id ) {
    $nonce_valid = false;

    if ( isset( $_POST['cc_featured_nonce'] ) && wp_verify_nonce( $_POST['cc_featured_nonce'], 'cc_featured_save_' . $post_id ) ) {
        $nonce_valid = true;
    }

    if ( isset( $_POST['cc_featured_quick_edit_nonce'] ) && wp_verify_nonce( $_POST['cc_featured_quick_edit_nonce'], 'cc_featured_quick_edit' ) ) {
        $nonce_valid = true;
    }

    if ( isset( $_POST['_inline_edit'] ) && wp_verify_nonce( $_POST['_inline_edit'], 'inlineeditnonce' ) ) {
        $nonce_valid = true;
    }

    if ( ! $nonce_valid ) {
        return;
    }

    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }

    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }

    $featured = isset( $_POST['cc_featured'] ) ? '1' : '0';

    if ( '1' === $featured ) {
        update_post_meta( $post_id, 'cc_featured', $featured );
    } else {
        delete_post_meta( $post_id, 'cc_featured' );
    }
}
add_action( 'save_post_cc_comic', 'cc_save_featured_metabox' );

function cc_add_featured_class_to_rows( $classes, $class, $post_id ) {
    if ( ! is_admin() ) {
        return $classes;
    }

    if ( 'cc_comic' !== get_post_type( $post_id ) ) {
        return $classes;
    }

    $is_featured = get_post_meta( $post_id, 'cc_featured', true );

    if ( '1' === $is_featured ) {
        $classes[] = 'cc-comic-featured';
    }

    return $classes;
}
add_filter( 'post_class', 'cc_add_featured_class_to_rows', 10, 3 );

function cc_featured_quick_edit_script() {
    global $typenow;

    if ( 'cc_comic' !== $typenow ) {
        return;
    }
    ?>
    <script type="text/javascript">
        ( function( $ ) {
            const wpInlineEdit = inlineEditPost.edit;

            inlineEditPost.edit = function( id ) {
                wpInlineEdit.apply( this, arguments );

                let postId = 0;

                if ( typeof id === 'object' ) {
                    postId = parseInt( this.getId( id ), 10 );
                }

                if ( postId > 0 ) {
                    const $editRow = $( '#edit-' + postId );
                    const isFeatured = $( '#post-' + postId ).hasClass( 'cc-comic-featured' );

                    $editRow.find( 'input[name="cc_featured"]' ).prop( 'checked', isFeatured );
                }
            };
        } )( jQuery );
    </script>
    <?php
}
add_action( 'admin_print_footer_scripts-edit.php', 'cc_featured_quick_edit_script' );
