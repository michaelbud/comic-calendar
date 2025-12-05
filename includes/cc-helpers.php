<?php
if ( ! defined( 'ABSPATH' ) ) exit;

function cc_get_page_id() {
    return (int) get_option( 'cc_comic_page_id', 0 );
}

function cc_get_page_url() {
    $id = cc_get_page_id();
    return $id ? get_permalink( $id ) : home_url('/');
}

function cc_resolve_current_comic_id() {
    // 1. Check Query Var (The Pretty URL way: /comic/123/)
    $qv_id = get_query_var( 'cc_comic_id' );
    if ( $qv_id ) return intval( $qv_id );

    // 2. Check $_GET (The Standard way: ?comic=123)
    if ( isset($_GET['cc_comic_id']) && is_numeric($_GET['cc_comic_id']) ) {
        return intval($_GET['cc_comic_id']);
    }
    // Backward compatibility for "cents of humor" old vars
    if ( isset($_GET['comic']) && is_numeric($_GET['comic']) ) {
        return intval($_GET['comic']);
    }

    // 3. Fallback: Parse the URL manually (If rewrite rules failed but URL looks right)
    global $wp;
    $current_url = home_url( add_query_arg( array(), $wp->request ) );
    if ( preg_match( '#/(\d+)/?$#', $current_url, $matches ) ) {
        // Ensure this ID actually belongs to a comic before returning
        $p = get_post( $matches[1] );
        if ( $p && $p->post_type === 'cc_comic' ) {
            return intval( $matches[1] );
        }
    }

    // 4. Check Month Navigation (Get first comic of that month)
    if ( isset($_GET['cc_month']) ) {
        $month_raw = sanitize_text_field( wp_unslash( $_GET['cc_month'] ) );

        if ( preg_match( '/^\d{4}-\d{2}$/', $month_raw ) ) {
            $dt = DateTime::createFromFormat( 'Y-m', $month_raw );
            $valid_month = $dt && $dt->format( 'Y-m' ) === $month_raw;
        } else {
            $valid_month = false;
        }

        if ( $valid_month ) {
            $q = new WP_Query([
                'post_type' => 'cc_comic',
                'posts_per_page' => 1,
                'orderby' => 'date',
                'order' => 'ASC', // Crucial: get the first comic of the selected month
                'date_query' => [[
                    'year'  => $dt->format( 'Y' ),
                    'month' => $dt->format( 'm' ),
                ]]
            ]);
            if ($q->have_posts()) {
                $comic_id = $q->posts[0]->ID;
                wp_reset_postdata();
                return $comic_id;
            }
            wp_reset_postdata();
        }
    }
    
    // 5. Default: Latest Comic
    $latest = new WP_Query([
        'post_type' => 'cc_comic',
        'posts_per_page' => 1,
        'orderby' => 'date',
        'order' => 'DESC'
    ]);
    if ($latest->have_posts()) return $latest->posts[0]->ID;

    return null;
}

// Bust cache on save
add_action( 'save_post_cc_comic', function() { delete_transient( 'cc_all_ids' ); });