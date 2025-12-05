<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Register rewrite rules based on the user's selected page.
 */
function cc_register_rewrites() {
    // 1. Register the query variable so WP recognizes it
    add_rewrite_tag( '%cc_comic_id%', '([0-9]+)' );

    $page_id = (int) get_option( 'cc_comic_page_id' );
    
    if ( $page_id > 0 ) {
        // FIX: Use get_page_uri() to handle Child Pages (e.g. /comics/daily/)
        $path = get_page_uri( $page_id );
        
        // Rule: /page-path/123/ -> index.php?page_id=ID&cc_comic_id=123
        // We use TOP priority to override standard page matching
        add_rewrite_rule(
            '^' . preg_quote( $path, '#' ) . '/([0-9]+)/?$',
            'index.php?page_id=' . $page_id . '&cc_comic_id=$matches[1]',
            'top'
        );
    }
}
add_action('init', 'cc_register_rewrites');

/**
 * Prevent WordPress from canonical-redirecting our custom URLs.
 */
add_filter('redirect_canonical', function($redirect, $requested) {
    // If we have found a comic ID, do NOT redirect.
    if ( get_query_var('cc_comic_id') ) {
        return false;
    }
    
    // Backup Check: If the URL looks like our comic URL, stop the redirect.
    // This helps if the query var isn't set yet during the redirect check.
    $page_id = (int) get_option( 'cc_comic_page_id' );
    if ( $page_id ) {
        $path = get_page_uri( $page_id );
        // Check if requested URL contains /page-path/123/
        if ( strpos( $requested, $path . '/' ) !== false && preg_match( '#/[0-9]+/?$#', $requested ) ) {
            return false;
        }
    }

    return $redirect;
}, 10, 2);

/**
 * Ensure the variable is public.
 */
add_filter('query_vars', function($vars) {
    $vars[] = 'cc_comic_id';
    return $vars;
});