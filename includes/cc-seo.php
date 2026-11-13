<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Returns true if the current request is a comic page — either the main
 * calendar page or a /page-path/POST_ID/ URL. Checked early enough to work
 * during template_redirect AND wp_head.
 */
function cc_is_comic_request() {
    // Already resolved by WP rewrite
    if ( get_query_var( 'cc_comic_id' ) ) return true;

    // Main calendar page (no specific comic in URL — shows latest)
    if ( is_page( cc_get_page_id() ) ) return true;

    // Fallback: inspect the raw URL for /page-path/DIGITS/ pattern
    // Needed when template_redirect fires before query vars are populated
    $page_id = (int) get_option( 'cc_comic_page_id' );
    if ( $page_id ) {
        $path       = get_page_uri( $page_id );
        $request_uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
        if ( $path && preg_match( '#/' . preg_quote( $path, '#' ) . '/([0-9]+)/?$#', $request_uri ) ) {
            return true;
        }
    }

    return false;
}


/**
 * Prevent Jetpack and Yoast from injecting their own social tags on comic pages,
 * which would serve the FEJ site logo instead of the comic image.
 */
add_action( 'template_redirect', function() {
    if ( ! cc_is_comic_request() ) return;

    // Disable Jetpack Open Graph and Twitter card tags
    add_filter( 'jetpack_enable_open_graph',   '__return_false', 99 );
    add_filter( 'jetpack_enable_opengraph',    '__return_false', 99 );
    add_filter( 'jetpack_disable_twitter_cards', '__return_true', 99 );

    if ( function_exists( 'jetpack_og_tags' ) ) {
        remove_action( 'wp_head', 'jetpack_og_tags', 3 );
    }
    if ( function_exists( 'jetpack_twitter_cards_tag' ) ) {
        remove_action( 'wp_head', 'jetpack_twitter_cards_tag', 3 );
    }

    // Prevent Yoast from injecting fallback logo images
    add_filter( 'wpseo_add_opengraph_images', '__return_empty_array', 99 );
    add_filter( 'wpseo_opengraph_image',      '__return_false', 99 );
    add_filter( 'wpseo_twitter_image',        '__return_false', 99 );

    // Also suppress RankMath if installed
    add_filter( 'rank_math/opengraph/facebook/image', '__return_false', 99 );
    add_filter( 'rank_math/opengraph/twitter/image',  '__return_false', 99 );
}, 0 );


/**
 * Output correct OG / Twitter Card meta tags for the current comic.
 * Runs at priority 1 so it fires before most other plugins.
 */
add_action( 'wp_head', function() {
    if ( ! cc_is_comic_request() ) return;

    $id = cc_resolve_current_comic_id();
    if ( ! $id ) return;

    // Bail if no featured image — better to output nothing than the wrong thing
    $img = get_the_post_thumbnail_url( $id, 'full' );
    if ( ! $img ) return;

    $page_title  = get_the_title( cc_get_page_id() );
    $comic_title = get_the_title( $id );
    $title       = ( $page_title && $comic_title )
                   ? sprintf( '%s | %s', $page_title, $comic_title )
                   : $comic_title;

    $url  = cc_get_comic_pretty_url( $id );
    $desc = get_the_excerpt( $id ) ?: 'Comic for ' . get_the_date( '', $id );

    $width = $height = null;
    $thumb_id = get_post_thumbnail_id( $id );
    if ( $thumb_id ) {
        $meta = wp_get_attachment_metadata( $thumb_id );
        if ( $meta && isset( $meta['width'], $meta['height'] ) ) {
            $width  = (int) $meta['width'];
            $height = (int) $meta['height'];
        }
    }

    echo "\n\n<!-- Comic Calendar OG tags -->\n";
    echo '<meta property="og:type"        content="article" />' . "\n";
    echo '<meta property="og:title"       content="' . esc_attr( $title ) . '" />' . "\n";
    echo '<meta property="og:description" content="' . esc_attr( $desc )  . '" />' . "\n";
    echo '<meta property="og:image"       content="' . esc_url( $img )    . '" />' . "\n";
    if ( $width && $height ) {
        echo '<meta property="og:image:width"  content="' . esc_attr( $width )  . '" />' . "\n";
        echo '<meta property="og:image:height" content="' . esc_attr( $height ) . '" />' . "\n";
    }
    echo '<meta property="og:image:type"  content="image/jpeg" />' . "\n";
    echo '<meta property="og:url"         content="' . esc_url( $url )    . '" />' . "\n";
    echo '<meta name="twitter:card"       content="summary_large_image" />' . "\n";
    echo '<meta name="twitter:title"      content="' . esc_attr( $title ) . '" />' . "\n";
    echo '<meta name="twitter:image"      content="' . esc_url( $img )    . '" />' . "\n";
    echo '<meta name="twitter:description" content="' . esc_attr( $desc ) . '" />' . "\n";
    echo "<!-- / Comic Calendar OG tags -->\n\n";
}, 1 );


/**
 * Filter the browser tab / document title to show the comic name.
 */
add_filter( 'document_title_parts', function( $title ) {
    if ( ! cc_is_comic_request() ) return $title;

    $id = cc_resolve_current_comic_id();
    if ( ! $id ) return $title;

    $page_title  = get_the_title( cc_get_page_id() );
    $comic_title = get_the_title( $id );

    if ( $page_title && $comic_title ) {
        $title['title'] = sprintf( '%s | %s', $page_title, $comic_title );
    }

    return $title;
}, 20 );


/**
 * Yoast outputs a separate OG block for the page itself (not the post),
 * using wp_head priority ~1-2. We need to suppress the entire Yoast
 * presenter stack on comic pages so only our tags appear.
 *
 * wpseo_frontend_presenters filters the array of presenter objects Yoast
 * uses to render its <head> output — we strip the OG and Twitter ones.
 */
add_filter( 'wpseo_frontend_presenters', function( $presenters ) {
    if ( ! cc_is_comic_request() ) return $presenters;

    $remove = [
        'Yoast\WP\SEO\Presenters\Open_Graph\Image_Presenter',
        'Yoast\WP\SEO\Presenters\Open_Graph\Title_Presenter',
        'Yoast\WP\SEO\Presenters\Open_Graph\Description_Presenter',
        'Yoast\WP\SEO\Presenters\Open_Graph\Url_Presenter',
        'Yoast\WP\SEO\Presenters\Open_Graph\Type_Presenter',
        'Yoast\WP\SEO\Presenters\Open_Graph\Site_Name_Presenter',
        'Yoast\WP\SEO\Presenters\Open_Graph\Article_Modified_Time_Presenter',
        'Yoast\WP\SEO\Presenters\Open_Graph\Locale_Presenter',
        'Yoast\WP\SEO\Presenters\Twitter\Card_Presenter',
        'Yoast\WP\SEO\Presenters\Twitter\Creator_Presenter',
        'Yoast\WP\SEO\Presenters\Twitter\Description_Presenter',
        'Yoast\WP\SEO\Presenters\Twitter\Image_Presenter',
        'Yoast\WP\SEO\Presenters\Twitter\Site_Presenter',
        'Yoast\WP\SEO\Presenters\Twitter\Title_Presenter',
        'Yoast\WP\SEO\Presenters\Twitter\Data_Presenter',
    ];

    return array_filter( $presenters, function( $presenter ) use ( $remove ) {
        return ! in_array( get_class( $presenter ), $remove, true );
    } );
}, 99 );
