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
        $path = get_page_uri( $page_id );
        
        // Rule: /page-path/123/ -> index.php?page_id=ID&cc_comic_id=123
        add_rewrite_rule(
            '^' . preg_quote( $path, '#' ) . '/([0-9]+)/?$',
            'index.php?page_id=' . $page_id . '&cc_comic_id=$matches[1]',
            'top'
        );
    }
}
add_action( 'init', 'cc_register_rewrites' );


/**
 * Redirect standalone /comic-strip/POST-SLUG/ URLs to the calendar page
 * with the correct comic loaded. This catches "View Post" clicks from the
 * admin and any old standalone URLs that may exist in the wild.
 */
add_action( 'template_redirect', function() {
    // Only act on singular cc_comic posts (the standalone template WP would render)
    if ( ! is_singular( 'cc_comic' ) ) return;

    $page_id = (int) get_option( 'cc_comic_page_id' );
    if ( ! $page_id ) return;

    $comic_id  = get_the_ID();
    $base_url  = get_permalink( $page_id );
    $target    = trailingslashit( trailingslashit( $base_url ) . $comic_id );

    // 301 redirect to the calendar page with this comic loaded
    wp_redirect( $target, 301 );
    exit;
} );


/**
 * Rewrite "View Post" in the admin list and post editor to point to the
 * calendar page URL instead of the standalone /comic-strip/ URL.
 */
add_filter( 'get_sample_permalink', function( $permalink, $post_id, $title, $name, $post ) {
    if ( ! $post || $post->post_type !== 'cc_comic' ) return $permalink;

    $page_id = (int) get_option( 'cc_comic_page_id' );
    if ( ! $page_id ) return $permalink;

    $base_url = get_permalink( $page_id );
    $url      = trailingslashit( trailingslashit( $base_url ) . $post_id );

    // get_sample_permalink returns [ $permalink, $post_name ] — preserve that format
    return [ $url, $post->post_name ];
}, 10, 5 );


/**
 * Also fix the "View Post" link in the post list table (Comics > All Comics).
 */
add_filter( 'post_row_actions', function( $actions, $post ) {
    if ( $post->post_type !== 'cc_comic' ) return $actions;

    $page_id = (int) get_option( 'cc_comic_page_id' );
    if ( ! $page_id ) return $actions;

    $base_url = get_permalink( $page_id );
    $url      = trailingslashit( trailingslashit( $base_url ) . $post->ID );

    if ( isset( $actions['view'] ) ) {
        $actions['view'] = sprintf(
            '<a href="%s" rel="bookmark" aria-label="%s">%s</a>',
            esc_url( $url ),
            /* translators: post title */
            esc_attr( sprintf( __( 'View &#8220;%s&#8221;' ), get_the_title( $post ) ) ),
            __( 'View' )
        );
    }

    return $actions;
}, 10, 2 );


/**
 * Prevent WordPress from canonical-redirecting our calendar URLs.
 */
add_filter( 'redirect_canonical', function( $redirect, $requested ) {
    if ( get_query_var( 'cc_comic_id' ) ) return false;

    $page_id = (int) get_option( 'cc_comic_page_id' );
    if ( $page_id ) {
        $path = get_page_uri( $page_id );
        if ( strpos( $requested, $path . '/' ) !== false && preg_match( '#/[0-9]+/?$#', $requested ) ) {
            return false;
        }
    }

    return $redirect;
}, 10, 2 );


/**
 * Ensure the cc_comic_id variable is public.
 */
add_filter( 'query_vars', function( $vars ) {
    $vars[] = 'cc_comic_id';
    return $vars;
} );
