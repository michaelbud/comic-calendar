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
    if ( ! isset( $_POST['cc_featured_nonce'] ) || ! wp_verify_nonce( $_POST['cc_featured_nonce'], 'cc_featured_save_' . $post_id ) ) {
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

function cc_add_featured_column( $columns ) {
    $new_columns = [];

    foreach ( $columns as $key => $label ) {
        if ( 'title' === $key ) {
            $new_columns['cc_featured'] = esc_html__( 'Featured', 'comic-calendar' );
        }

        $new_columns[ $key ] = $label;
    }

    return $new_columns;
}
add_filter( 'manage_cc_comic_posts_columns', 'cc_add_featured_column' );

function cc_render_featured_column( $column, $post_id ) {
    if ( 'cc_featured' !== $column ) {
        return;
    }

    $is_featured = get_post_meta( $post_id, 'cc_featured', true ) === '1';
    $icon_class  = $is_featured ? 'dashicons-star-filled' : 'dashicons-star-empty';
    $sr_text     = $is_featured ? esc_html__( 'Unset as featured comic', 'comic-calendar' ) : esc_html__( 'Mark as featured comic', 'comic-calendar' );

    printf(
        '<button type="button" class="button-link cc-featured-toggle" data-post="%1$d" data-featured="%2$d" aria-pressed="%2$d">'
        . '<span class="dashicons %3$s" aria-hidden="true"></span>'
        . '<span class="screen-reader-text">%4$s</span>'
        . '</button>',
        absint( $post_id ),
        $is_featured ? 1 : 0,
        esc_attr( $icon_class ),
        esc_html( $sr_text )
    );
}
add_action( 'manage_cc_comic_posts_custom_column', 'cc_render_featured_column', 10, 2 );

function cc_enqueue_featured_column_assets( $hook ) {
    global $typenow;

    if ( 'edit.php' !== $hook || 'cc_comic' !== $typenow ) {
        return;
    }

    wp_enqueue_style( 'dashicons' );

    $script_path = CC_PLUGIN_PATH . 'admin/cc-featured.js';
    $version     = file_exists( $script_path ) ? filemtime( $script_path ) : '1.0';

    wp_enqueue_script(
        'cc-featured-admin',
        CC_PLUGIN_URL . 'admin/cc-featured.js',
        array( 'jquery' ),
        $version,
        true
    );

    wp_localize_script(
        'cc-featured-admin',
        'ccFeatured',
        array(
            'nonce' => wp_create_nonce( 'cc_toggle_featured' ),
        )
    );
}
add_action( 'admin_enqueue_scripts', 'cc_enqueue_featured_column_assets' );

function cc_handle_toggle_featured_ajax() {
    if ( ! current_user_can( 'edit_posts' ) ) {
        wp_send_json_error( array( 'message' => esc_html__( 'You do not have permission to update this comic.', 'comic-calendar' ) ) );
    }

    $nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';

    if ( ! wp_verify_nonce( $nonce, 'cc_toggle_featured' ) ) {
        wp_send_json_error( array( 'message' => esc_html__( 'Invalid request. Please refresh and try again.', 'comic-calendar' ) ) );
    }

    $post_id = isset( $_POST['postId'] ) ? absint( $_POST['postId'] ) : 0;

    if ( ! $post_id || 'cc_comic' !== get_post_type( $post_id ) ) {
        wp_send_json_error( array( 'message' => esc_html__( 'Invalid comic.', 'comic-calendar' ) ) );
    }

    $is_featured = get_post_meta( $post_id, 'cc_featured', true ) === '1';

    if ( $is_featured ) {
        delete_post_meta( $post_id, 'cc_featured' );
    } else {
        update_post_meta( $post_id, 'cc_featured', '1' );
    }

    wp_send_json_success( array( 'featured' => $is_featured ? 0 : 1 ) );
}
add_action( 'wp_ajax_cc_toggle_featured', 'cc_handle_toggle_featured_ajax' );
