<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'wp_enqueue_scripts', function() {
    if ( is_page( cc_get_page_id() ) ) {
        $css_path = CC_PLUGIN_PATH . 'assets/css/cc-public.css';
        $version  = file_exists( $css_path ) ? filemtime( $css_path ) : '2.0';

        wp_enqueue_style( 'cc-styles', CC_PLUGIN_URL . 'assets/css/cc-public.css', [], $version );

        $js_path = CC_PLUGIN_PATH . 'assets/js/cc-lightbox.js';
        wp_enqueue_script(
            'cc-lightbox',
            CC_PLUGIN_URL . 'assets/js/cc-lightbox.js',
            [],
            file_exists( $js_path ) ? filemtime( $js_path ) : '2.1.0',
            true
        );
    }
});