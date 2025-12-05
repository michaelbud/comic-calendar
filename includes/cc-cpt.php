<?php
if ( ! defined( 'ABSPATH' ) ) exit;

function cc_register_cpt() {
    $labels = array(
        'name'          => 'Comics',
        'singular_name' => 'Comic',
        'menu_name'     => 'Comics',
        'add_new_item'  => 'Add New Comic',
        'edit_item'     => 'Edit Comic',
        'view_item'     => 'View Comic',
        'all_items'     => 'All Comics',
        'search_items'  => 'Search Comics',
        'not_found'     => 'No comics found.',
    );

    $args = array(
        'labels'             => $labels,
        'public'             => true,
        'show_in_menu'       => true,
        'query_var'          => true,
        'rewrite'            => array( 'slug' => 'comic-strip' ), // Default slug for the individual post (canonical)
        'capability_type'    => 'post',
        'has_archive'        => true,
        'menu_position'      => 5,
        'menu_icon'          => 'dashicons-calendar-alt',
        'supports'           => array( 'title', 'editor', 'thumbnail', 'excerpt', 'comments' ),
    );

    register_post_type( 'cc_comic', $args );
}
add_action( 'init', 'cc_register_cpt' );