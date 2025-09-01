<?php
/**
 * Custom Post Type registration for Monthly Calendar Planner
 * @version 1.1.0
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Register the 'monthly_calendar' Custom Post Type.
 */
function mk_mcp_register_custom_post_type() {

    $labels = array(
        'name'                  => _x( 'Monthly Calendars', 'Post Type General Name', 'mk-monthly-calendar-planner' ),
        'singular_name'         => _x( 'Monthly Calendar', 'Post Type Singular Name', 'mk-monthly-calendar-planner' ),
        'menu_name'             => __( 'Monthly Calendars', 'mk-monthly-calendar-planner' ),
        'name_admin_bar'        => __( 'Monthly Calendar', 'mk-monthly-calendar-planner' ),
        'archives'              => __( 'Calendar Archives', 'mk-monthly-calendar-planner' ),
        'attributes'            => __( 'Calendar Attributes', 'mk-monthly-calendar-planner' ),
        'parent_item_colon'     => __( 'Parent Calendar:', 'mk-monthly-calendar-planner' ),
        'all_items'             => __( 'All Calendars', 'mk-monthly-calendar-planner' ),
        'add_new_item'          => __( 'Add New Calendar', 'mk-monthly-calendar-planner' ),
        'add_new'               => __( 'Add New', 'mk-monthly-calendar-planner' ),
        'new_item'              => __( 'New Calendar', 'mk-monthly-calendar-planner' ),
        'edit_item'             => __( 'Edit Calendar', 'mk-monthly-calendar-planner' ),
        'update_item'           => __( 'Update Calendar', 'mk-monthly-calendar-planner' ),
        'view_item'             => __( 'View Calendar', 'mk-monthly-calendar-planner' ),
        'view_items'            => __( 'View Calendars', 'mk-monthly-calendar-planner' ),
        'search_items'          => __( 'Search Calendar', 'mk-monthly-calendar-planner' ),
        'not_found'             => __( 'Not found', 'mk-monthly-calendar-planner' ),
        'not_found_in_trash'    => __( 'Not found in Trash', 'mk-monthly-calendar-planner' ),
        'featured_image'        => __( 'Featured Image', 'mk-monthly-calendar-planner' ),
        'set_featured_image'    => __( 'Set featured image', 'mk-monthly-calendar-planner' ),
        'remove_featured_image' => __( 'Remove featured image', 'mk-monthly-calendar-planner' ),
        'use_featured_image'    => __( 'Use as featured image', 'mk-monthly-calendar-planner' ),
        'insert_into_item'      => __( 'Insert into calendar', 'mk-monthly-calendar-planner' ),
        'uploaded_to_this_item' => __( 'Uploaded to this calendar', 'mk-monthly-calendar-planner' ),
        'items_list'            => __( 'Calendars list', 'mk-monthly-calendar-planner' ),
        'items_list_navigation' => __( 'Calendars list navigation', 'mk-monthly-calendar-planner' ),
        'filter_items_list'     => __( 'Filter calendars list', 'mk-monthly-calendar-planner' ),
    );
    $args = array(
        'label'                 => __( 'Monthly Calendar', 'mk-monthly-calendar-planner' ),
        'description'           => __( 'A post type for creating monthly calendars.', 'mk-monthly-calendar-planner' ),
        'labels'                => $labels,
        'supports'              => array( 'title', 'revisions' ),
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
add_action( 'init', 'mk_mcp_register_custom_post_type', 0 );

/**
 * Add custom columns to the 'monthly_calendar' post type list table.
 *
 * @param array $columns The existing columns.
 * @return array The modified columns.
 */
function mk_mcp_add_custom_columns($columns) {
    // Add new column after the 'title' column
    $new_columns = [];
    foreach ($columns as $key => $title) {
        $new_columns[$key] = $title;
        if ($key === 'title') {
            $new_columns['shortcode'] = __('Shortcode', 'mk-monthly-calendar-planner');
        }
    }
    return $new_columns;
}
add_filter('manage_monthly_calendar_posts_columns', 'mk_mcp_add_custom_columns');

/**
 * Display content for the custom columns.
 *
 * @param string $column_name The name of the custom column.
 * @param int    $post_id     The ID of the current post.
 */
function mk_mcp_render_custom_columns($column_name, $post_id) {
    if ($column_name === 'shortcode') {
        $shortcode = '[monthly_calendar id="' . $post_id . '"]';
        echo '<input type="text" readonly="readonly" value="' . esc_attr($shortcode) . '" class="mk-mcp-shortcode-input" />';
    }
}
add_action('manage_monthly_calendar_posts_custom_column', 'mk_mcp_render_custom_columns', 10, 2);