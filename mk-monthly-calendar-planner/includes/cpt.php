<?php

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Register the 'monthly_calendar' Custom Post Type.
 */
function mcp_register_custom_post_type() {

    $labels = array(
        'name'                  => _x( 'Monthly Calendars', 'Post Type General Name', 'monthly-calendar-planner' ),
        'singular_name'         => _x( 'Monthly Calendar', 'Post Type Singular Name', 'monthly-calendar-planner' ),
        'menu_name'             => __( 'Monthly Calendars', 'monthly-calendar-planner' ),
        'name_admin_bar'        => __( 'Monthly Calendar', 'monthly-calendar-planner' ),
        'archives'              => __( 'Calendar Archives', 'monthly-calendar-planner' ),
        'attributes'            => __( 'Calendar Attributes', 'monthly-calendar-planner' ),
        'parent_item_colon'     => __( 'Parent Calendar:', 'monthly-calendar-planner' ),
        'all_items'             => __( 'All Calendars', 'monthly-calendar-planner' ),
        'add_new_item'          => __( 'Add New Calendar', 'monthly-calendar-planner' ),
        'add_new'               => __( 'Add New', 'monthly-calendar-planner' ),
        'new_item'              => __( 'New Calendar', 'monthly-calendar-planner' ),
        'edit_item'             => __( 'Edit Calendar', 'monthly-calendar-planner' ),
        'update_item'           => __( 'Update Calendar', 'monthly-calendar-planner' ),
        'view_item'             => __( 'View Calendar', 'monthly-calendar-planner' ),
        'view_items'            => __( 'View Calendars', 'monthly-calendar-planner' ),
        'search_items'          => __( 'Search Calendar', 'monthly-calendar-planner' ),
        'not_found'             => __( 'Not found', 'monthly-calendar-planner' ),
        'not_found_in_trash'    => __( 'Not found in Trash', 'monthly-calendar-planner' ),
        'featured_image'        => __( 'Featured Image', 'monthly-calendar-planner' ),
        'set_featured_image'    => __( 'Set featured image', 'monthly-calendar-planner' ),
        'remove_featured_image' => __( 'Remove featured image', 'monthly-calendar-planner' ),
        'use_featured_image'    => __( 'Use as featured image', 'monthly-calendar-planner' ),
        'insert_into_item'      => __( 'Insert into calendar', 'monthly-calendar-planner' ),
        'uploaded_to_this_item' => __( 'Uploaded to this calendar', 'monthly-calendar-planner' ),
        'items_list'            => __( 'Calendars list', 'monthly-calendar-planner' ),
        'items_list_navigation' => __( 'Calendars list navigation', 'monthly-calendar-planner' ),
        'filter_items_list'     => __( 'Filter calendars list', 'monthly-calendar-planner' ),
    );
    $args = array(
        'label'                 => __( 'Monthly Calendar', 'monthly-calendar-planner' ),
        'description'           => __( 'A post type for creating monthly calendars.', 'monthly-calendar-planner' ),
        'labels'                => $labels,
        'supports'              => array( 'title' ),
        'hierarchical'          => false,
        'public'                => true,
        'show_ui'               => true,
        'show_in_menu'          => true,
        'menu_position'         => 5,
        'menu_icon'             => 'dashicons-calendar-alt',
        'show_in_admin_bar'     => true,
        'show_in_nav_menus'     => true,
        'can_export'            => true,
        'has_archive'           => false,
        'exclude_from_search'   => true,
        'publicly_queryable'    => true,
        'capability_type'       => 'post',
        'show_in_rest'          => true,
    );
    register_post_type( 'monthly_calendar', $args );

}
add_action( 'init', 'mcp_register_custom_post_type', 0 );
