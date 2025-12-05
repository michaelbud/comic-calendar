<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Prevent other plugins from injecting their own social tags when
 * viewing a comic. Jetpack and YOAST SEO, for example, add a site-logo fallback
 * image which can result in two images appearing in the share preview.
 */
add_action( 'template_redirect', function() {
    if ( ! is_page( cc_get_page_id() ) && ! get_query_var( 'cc_comic_id' ) ) {
        return;
    }

    // Disable Jetpack Open Graph and Twitter card tags on comic pages.
    add_filter( 'jetpack_enable_open_graph', '__return_false', 99 );
    add_filter( 'jetpack_enable_opengraph', '__return_false', 99 );
    add_filter( 'jetpack_disable_twitter_cards', '__return_true', 99 );

    if ( function_exists( 'jetpack_og_tags' ) ) {
        remove_action( 'wp_head', 'jetpack_og_tags', 3 );
    }
    if ( function_exists( 'jetpack_twitter_cards_tag' ) ) {
        remove_action( 'wp_head', 'jetpack_twitter_cards_tag', 3 );
    }

    // Prevent Yoast SEO from injecting fallback logo images into social tags.
    add_filter( 'wpseo_add_opengraph_images', '__return_empty_array', 99 );
    add_filter( 'wpseo_opengraph_image', '__return_false', 99 );
    add_filter( 'wpseo_twitter_image', '__return_false', 99 );
}, 0 );

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
    $url   = cc_get_comic_pretty_url( $id );
    $desc  = get_the_excerpt( $id ) ?: 'Comic for ' . get_the_date('', $id);

    $width = $height = null;
    $thumb_id = get_post_thumbnail_id( $id );
    if ( $thumb_id ) {
        $meta = wp_get_attachment_metadata( $thumb_id );
        if ( $meta && isset( $meta['width'], $meta['height'] ) ) {
            $width  = (int) $meta['width'];
            $height = (int) $meta['height'];
        }
    }

    // Output clean tags
    echo "\n\n";
    echo '<meta property="og:title" content="' . esc_attr($title) . '" />' . "\n";
    echo '<meta property="og:image" content="' . esc_url($img) . '" />' . "\n";
    if ( $width && $height ) {
        echo '<meta property="og:image:width" content="' . esc_attr( $width ) . '" />' . "\n";
        echo '<meta property="og:image:height" content="' . esc_attr( $height ) . '" />' . "\n";
    }
    echo '<meta property="og:url" content="' . esc_url($url) . '" />' . "\n";
    echo '<meta name="twitter:card" content="summary_large_image" />' . "\n";
    echo '<meta name="twitter:image" content="' . esc_url($img) . '" />' . "\n";
    echo "\n";
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
