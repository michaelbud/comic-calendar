<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'wp_enqueue_scripts', function() {
    if ( is_page( cc_get_page_id() ) ) {
        wp_enqueue_style( 'cc-styles', CC_PLUGIN_URL . 'assets/css/cc-public.css', [], '2.0' );
    }
});