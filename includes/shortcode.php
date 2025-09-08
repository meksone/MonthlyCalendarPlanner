<?php
/**
 * Shortcode registration for Monthly Calendar Planner
 * @version 1.1.0
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Register the [monthly_calendar] shortcode.
 *
 * @param array $atts Shortcode attributes.
 * @return string The shortcode output.
 */
function mk_mcp_register_shortcode( $atts ) {
    $atts = shortcode_atts(
        array(
            'id' => 0,
        ),
        $atts,
        'monthly_calendar'
    );

    $post_id = intval( $atts['id'] );

    if ( ! $post_id || get_post_type( $post_id ) !== 'monthly_calendar' ) {
        return '<p>' . __( 'Invalid calendar ID provided.', 'mk-monthly-calendar-planner' ) . '</p>';
    }
    
    // Enqueue the script only when the shortcode is used
    wp_enqueue_script('mk-mcp-frontend-script', MK_MCP_PLUGIN_URL . 'assets/js/frontend-script.js', array(), MK_MCP_VERSION, true);

    // Check post status
    if (get_post_status($post_id) !== 'publish' && !current_user_can('edit_post', $post_id)) {
        return ''; // Don't show non-published calendars to public
    }

    $month = get_post_meta( $post_id, '_mk_mcp_month', true );
    $year = get_post_meta( $post_id, '_mk_mcp_year', true );
    $items_json = get_post_meta( $post_id, '_mk_mcp_calendar_items', true );
    $items = json_decode( $items_json, true );
    $items = mk_mcp_convert_data_to_v2_format($items); // Ensure data is in new format

    $view_mode = get_post_meta( $post_id, '_mk_mcp_view_mode', true ) ?: 'calendar';
    
    if ( ! $month || ! $year ) {
        return '<p>' . __( 'Calendar is not configured correctly (missing month or year).', 'mk-monthly-calendar-planner' ) . '</p>';
    }

    ob_start();
    ?>
    <div class="mk-mcp-frontend-calendar-wrapper" id="mk-mcp-calendar-<?php echo esc_attr($post_id); ?>">
        <div class="mk-mcp-search-box-wrapper">
            <input type="search" id="mk-mcp-search-input-<?php echo esc_attr($post_id); ?>" class="mk-mcp-search-input" placeholder="<?php _e('Search events...', 'mk-monthly-calendar-planner'); ?>">
        </div>
        <h2 class="mk-mcp-calendar-title"><?php echo esc_html( get_the_title( $post_id ) ); ?></h2>
        <?php 
        if ($view_mode === 'table') {
            $column_count = get_post_meta( $post_id, '_mk_mcp_column_count', true ) ?: 4;
            $column_names_json = get_post_meta( $post_id, '_mk_mcp_column_names', true );
            $column_names = !empty($column_names_json) ? json_decode($column_names_json, true) : [];
            mk_mcp_render_frontend_table($month, $year, $items, $column_count, $column_names);
        } else {
            mk_mcp_render_calendar_grid( $month, $year, $items, false ); 
        }
        ?>
    </div>
    <?php

    return ob_get_clean();
}
add_shortcode( 'monthly_calendar', 'mk_mcp_register_shortcode' );