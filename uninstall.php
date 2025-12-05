<?php
// if uninstall.php is not called by WordPress, die
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    die;
}

// 1. Clear the cached transient
delete_transient( 'coh_all_comic_ids' );

// 2. Flush rewrite rules to remove our custom rules
flush_rewrite_rules();

// 3. (Optional) Delete all 'coh_comic' posts
/*
$all_comics = get_posts( array(
    'post_type' => 'coh_comic',
    'numberposts' => -1,
    'post_status' => 'any'
) );
foreach ( $all_comics as $comic ) {
    wp_delete_post( $comic->ID, true ); // true = force delete, bypass trash
}
*/