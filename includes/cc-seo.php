<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Handle Meta Tags.
 */
add_action( 'wp_head', function() {
    // Only run on the Comic Page or if query var is present
    if ( ! is_page( cc_get_page_id() ) && ! get_query_var( 'cc_comic_id' ) ) {
        return;
    }

    $id = cc_resolve_current_comic_id();
    if ( ! $id ) return;

    $title = get_the_title( $id );
    $img   = get_the_post_thumbnail_url( $id, 'full' );
    $url   = trailingslashit( cc_get_page_url() ) . $id . '/';
    $desc  = get_the_excerpt( $id ) ?: 'Comic for ' . get_the_date('', $id);

    // Output clean tags
    echo "\n\n";
    echo '<meta property="og:title" content="' . esc_attr($title) . '" />' . "\n";
    echo '<meta property="og:image" content="' . esc_url($img) . '" />' . "\n";
    echo '<meta property="og:url" content="' . esc_url($url) . '" />' . "\n";
    echo '<meta name="twitter:card" content="summary_large_image" />' . "\n";
    echo "\n";
    
    // Disable Yoast Images for this specific page request to avoid conflicts
    add_filter( 'wpseo_opengraph_image', '__return_false', 99 );
}, 1 );

/**
 * Filter Document Title
 */
add_filter( 'document_title_parts', function( $title ) {
    if ( is_page( cc_get_page_id() ) ) {
        $id = cc_resolve_current_comic_id();
        if ( $id ) {
            $title['title'] = get_the_title( $id );
        }
    }
    return $title;
}, 20 );