<?php
/**
 * Shortcode for displaying the monthly calendar.
 *
 * @package MK_Monthly_Calendar_Planner
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Registers the shortcode.
 */
function mk_mcp_register_shortcode() {
	add_shortcode( 'mk-calendar', 'mk_mcp_render_calendar_shortcode' );
}
add_action( 'init', 'mk_mcp_register_shortcode' );

/**
 * Renders the calendar for the shortcode.
 *
 * @param array $atts Shortcode attributes.
 * @return string The calendar HTML.
 */
function mk_mcp_render_calendar_shortcode( $atts ) {
	$atts = shortcode_atts(
		array(
			'id' => 0,
		),
		$atts,
		'mk-calendar'
	);

	$post_id = intval( $atts['id'] );

	if ( ! $post_id || 'mk-calendar' !== get_post_type( $post_id ) ) {
		return '<p>' . esc_html__( 'Invalid calendar ID provided.', 'mk-monthly-calendar-planner' ) . '</p>';
	}

	$month      = get_post_meta( $post_id, 'mk_mcp_month', true );
	$year       = get_post_meta( $post_id, 'mk_mcp_year', true );
	$items_json = get_post_meta( $post_id, 'mk_mcp_calendar_items_json', true );
	$items      = json_decode( $items_json, true );

	if ( empty( $month ) || empty( $year ) ) {
		return '<p>' . esc_html__( 'Calendar is not configured correctly.', 'mk-monthly-calendar-planner' ) . '</p>';
	}

	try {
		$first_day_of_month = new DateTime( "$year-$month-01" );
	} catch ( Exception $e ) {
		return '<p>' . esc_html__( 'Invalid date configuration.', 'mk-monthly-calendar-planner' ) . '</p>';
	}

	$days_in_month     = (int) $first_day_of_month->format( 't' );
	$start_day_of_week = (int) $first_day_of_month->format( 'N' ); // 1 (Mon) to 7 (Sun)

	ob_start();
	?>
	<div class="mk-mcp-frontend-calendar-wrapper">
		<h2 class="mk-mcp-calendar-title"><?php echo esc_html( get_the_title( $post_id ) ); ?></h2>
		<div class="mk-mcp-calendar-grid">
			<?php
			$days_of_week = array(
				esc_html__( 'Monday', 'mk-monthly-calendar-planner' ),
				esc_html__( 'Tuesday', 'mk-monthly-calendar-planner' ),
				esc_html__( 'Wednesday', 'mk-monthly-calendar-planner' ),
				esc_html__( 'Thursday', 'mk-monthly-calendar-planner' ),
				esc_html__( 'Friday', 'mk-monthly-calendar-planner' ),
				esc_html__( 'Saturday', 'mk-monthly-calendar-planner' ),
				esc_html__( 'Sunday', 'mk-monthly-calendar-planner' ),
			);
			foreach ( $days_of_week as $day_name ) {
				echo '<div class="mk-mcp-day-header">' . esc_html( $day_name ) . '</div>';
			}

			// Add empty cells for the days before the first day of the month.
			for ( $i = 1; $i < $start_day_of_week; $i++ ) {
				echo '<div class="mk-mcp-day mk-mcp-empty"></div>';
			}

			// Loop through each day of the month.
			for ( $day = 1; $day <= $days_in_month; $day++ ) {
				?>
				<div class="mk-mcp-day">
					<div class="mk-mcp-day-number"><?php echo esc_html( $day ); ?></div>
					<div class="mk-mcp-day-items-wrapper">
						<?php
						if ( ! empty( $items ) && isset( $items[ $day ] ) ) {
							foreach ( $items[ $day ] as $item ) {
								?>
								<div class="mk-mcp-item">
									<h4 class="mk-mcp-item-title"><?php echo esc_html( $item['title'] ); ?></h4>
									<div class="mk-mcp-item-text"><?php echo wp_kses_post( wpautop( $item['text'] ) ); ?></div>
								</div>
								<?php
							}
						}
						?>
					</div>
				</div>
				<?php
			}
			?>
		</div>
	</div>
	<?php
	return ob_get_clean();
}

