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
