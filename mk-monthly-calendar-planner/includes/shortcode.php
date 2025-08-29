<?php

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
    
    // Check post status
    if (get_post_status($post_id) !== 'publish') {
         if (!current_user_can('edit_post', $post_id)) {
            return ''; // Don't show non-published calendars to public
         }
    }

    $month = get_post_meta( $post_id, '_mk_mcp_month', true );
    $year = get_post_meta( $post_id, '_mk_mcp_year', true );
    $items_json = get_post_meta( $post_id, '_mk_mcp_calendar_items', true );
    $items = json_decode( $items_json, true );

    if ( json_last_error() !== JSON_ERROR_NONE ) {
        $items = [];
    }

    if ( ! $month || ! $year ) {
        return '<p>' . __( 'Calendar is not configured correctly (missing month or year).', 'mk-monthly-calendar-planner' ) . '</p>';
    }

    ob_start();
    ?>
    <div class="mk-mcp-frontend-calendar-wrapper">
        <h2 class="mk-mcp-calendar-title"><?php echo esc_html( get_the_title( $post_id ) ); ?></h2>
        <?php mk_mcp_render_calendar_grid( $month, $year, $items, false ); ?>
    </div>
    <?php

    return ob_get_clean();
}
add_shortcode( 'monthly_calendar', 'mk_mcp_register_shortcode' );
