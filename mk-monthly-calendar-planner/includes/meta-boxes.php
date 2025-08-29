<?php

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Add meta box to the 'monthly_calendar' post type.
 */
function mk_mcp_add_meta_boxes() {
    add_meta_box(
        'mk_mcp_calendar_settings',
        __('Calendar Settings & Items', 'mk-monthly-calendar-planner'),
        'mk_mcp_render_meta_box_content',
        'monthly_calendar',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes_monthly_calendar', 'mk_mcp_add_meta_boxes');

/**
 * Render the content of the meta box.
 *
 * @param WP_Post $post The post object.
 */
function mk_mcp_render_meta_box_content($post) {
    // Add a nonce field so we can check for it later.
    wp_nonce_field('mk_mcp_save_meta_box_data', 'mk_mcp_meta_box_nonce');

    // Get saved values
    $selected_month = get_post_meta($post->ID, '_mk_mcp_month', true);
    $selected_year = get_post_meta($post->ID, '_mk_mcp_year', true);
    $calendar_items = get_post_meta($post->ID, '_mk_mcp_calendar_items', true);
    
    // Set default year to current year if not set
    if (empty($selected_year)) {
        $selected_year = date('Y');
    }

    ?>
    <div class="mk-mcp-meta-box-wrapper">
        <div class="mk-mcp-settings-fields">
            <div class="mk-mcp-field">
                <label for="mk_mcp_month"><?php _e('Month', 'mk-monthly-calendar-planner'); ?></label>
                <select name="mk_mcp_month" id="mk_mcp_month">
                    <?php
                    for ($m = 1; $m <= 12; $m++) {
                        $month_name = date_i18n('F', mktime(0, 0, 0, $m, 10));
                        echo '<option value="' . esc_attr($m) . '"' . selected($selected_month, $m, false) . '>' . esc_html($month_name) . '</option>';
                    }
                    ?>
                </select>
            </div>
            <div class="mk-mcp-field">
                <label for="mk_mcp_year"><?php _e('Year', 'mk-monthly-calendar-planner'); ?></label>
                <input type="number" id="mk_mcp_year" name="mk_mcp_year" value="<?php echo esc_attr($selected_year); ?>" min="1900" max="2100" />
            </div>
        </div>

        <div id="mk-mcp-calendar-builder-container">
             <div class="mk-mcp-loader"><p><?php _e('Loading Calendar...', 'mk-monthly-calendar-planner'); ?></p></div>
             <div id="mk-mcp-calendar-grid-wrapper">
                <!-- Calendar grid will be loaded here via JavaScript -->
             </div>
        </div>
        
        <!-- Hidden field to store the calendar items as JSON -->
        <input type="hidden" name="mk_mcp_calendar_items_json" id="mk_mcp_calendar_items_json" value="<?php echo esc_attr($calendar_items); ?>" />
    </div>
    <?php
}

/**
 * Save meta box data.
 *
 * @param int $post_id The ID of the post being saved.
 */
function mk_mcp_save_meta_box_data($post_id) {
    // Check if our nonce is set.
    if (!isset($_POST['mk_mcp_meta_box_nonce'])) {
        return;
    }
    // Verify that the nonce is valid.
    if (!wp_verify_nonce($_POST['mk_mcp_meta_box_nonce'], 'mk_mcp_save_meta_box_data')) {
        return;
    }
    // If this is an autosave, our form has not been submitted, so we don't want to do anything.
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    // Check the user's permissions.
    if (isset($_POST['post_type']) && 'monthly_calendar' == $_POST['post_type']) {
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
    }

    // Sanitize and save the month
    if (isset($_POST['mk_mcp_month'])) {
        update_post_meta($post_id, '_mk_mcp_month', sanitize_text_field($_POST['mk_mcp_month']));
    }
    // Sanitize and save the year
    if (isset($_POST['mk_mcp_year'])) {
        update_post_meta($post_id, '_mk_mcp_year', sanitize_text_field($_POST['mk_mcp_year']));
    }
    // Sanitize and save the calendar items
    if (isset($_POST['mk_mcp_calendar_items_json'])) {
        // The data is JSON, so we can't use sanitize_text_field.
        // We'll decode and re-encode to ensure it's valid JSON.
        $json_data = stripslashes($_POST['mk_mcp_calendar_items_json']);
        $data = json_decode($json_data, true);
        if (json_last_error() === JSON_ERROR_NONE) {
             // Basic sanitization on decoded data
            foreach ($data as $day => &$items) {
                foreach ($items as &$item) {
                    $item['title'] = sanitize_text_field($item['title']);
                    $item['text'] = wp_kses_post($item['text']); // Allows basic HTML
                }
            }
            $sanitized_json = wp_json_encode($data);
            update_post_meta($post_id, '_mk_mcp_calendar_items', $sanitized_json);
        }
    }
}
add_action('save_post', 'mk_mcp_save_meta_box_data');


/**
 * AJAX handler to get the calendar grid for the admin editor.
 */
function mk_mcp_get_admin_calendar_grid() {
    check_ajax_referer('mk-mcp-nonce', 'nonce');

    $month = isset($_POST['month']) ? intval($_POST['month']) : 0;
    $year = isset($_POST['year']) ? intval($_POST['year']) : 0;
    $items_json = isset($_POST['items']) ? stripslashes($_POST['items']) : '[]';
    $items_data = json_decode($items_json, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        $items_data = [];
    }

    if (!$month || !$year) {
        wp_send_json_error('Invalid month or year.');
    }
    
    ob_start();
    mk_mcp_render_calendar_grid($month, $year, $items_data, true);
    $calendar_html = ob_get_clean();

    wp_send_json_success($calendar_html);
}
add_action('wp_ajax_mk_mcp_get_admin_calendar_grid', 'mk_mcp_get_admin_calendar_grid');

/**
 * Re-usable function to render the calendar grid.
 *
 * @param int $month The month number.
 * @param int $year The year.
 * @param array $items The items to display.
 * @param bool $is_admin Whether this is for the admin area.
 */
function mk_mcp_render_calendar_grid($month, $year, $items = [], $is_admin = false) {
    $first_day_of_month = mktime(0, 0, 0, $month, 1, $year);
    $num_days_in_month = date('t', $first_day_of_month);
    $day_of_week = date('N', $first_day_of_month); // 1 (for Monday) through 7 (for Sunday)

    echo '<div class="mk-mcp-calendar-grid">';

    // Day headers
    $days = array(
        __('Mon', 'mk-monthly-calendar-planner'),
        __('Tue', 'mk-monthly-calendar-planner'),
        __('Wed', 'mk-monthly-calendar-planner'),
        __('Thu', 'mk-monthly-calendar-planner'),
        __('Fri', 'mk-monthly-calendar-planner'),
        __('Sat', 'mk-monthly-calendar-planner'),
        __('Sun', 'mk-monthly-calendar-planner')
    );
    foreach ($days as $day) {
        echo '<div class="mk-mcp-day-header">' . esc_html($day) . '</div>';
    }

    // Blank days before the start of the month
    for ($i = 1; $i < $day_of_week; $i++) {
        echo '<div class="mk-mcp-day mk-mcp-day-empty"></div>';
    }

    // Days of the month
    for ($day_num = 1; $day_num <= $num_days_in_month; $day_num++) {
        echo '<div class="mk-mcp-day" data-day="' . esc_attr($day_num) . '">';
        echo '<div class="mk-mcp-day-number">' . esc_html($day_num) . '</div>';
        
        echo '<div class="mk-mcp-day-items-wrapper">'; // This wrapper will be the sortable container
        
        // Render items for this day
        if (!empty($items[$day_num])) {
            foreach ($items[$day_num] as $item) {
                 if ($is_admin) {
                    // Admin view with controls
                    echo '<div class="mk-mcp-item" data-id="' . uniqid() .'">';
                    echo '<div class="mk-mcp-item-header"><span class="mk-mcp-item-title-preview">' . esc_html($item['title']) . '</span> <div class="mk-mcp-item-actions"><button type="button" class="mk-mcp-duplicate-item" title="' . __('Duplicate Item', 'mk-monthly-calendar-planner') . '">D</button><button type="button" class="mk-mcp-delete-item" title="' . __('Delete Item', 'mk-monthly-calendar-planner') . '">X</button></div></div>';
                    echo '<div class="mk-mcp-item-content">';
                    echo '<input type="text" class="mk-mcp-item-title" placeholder="' . __('Title', 'mk-monthly-calendar-planner') . '" value="' . esc_attr($item['title']) . '">';
                    echo '<textarea class="mk-mcp-item-text" placeholder="' . __('Text', 'mk-monthly-calendar-planner') . '">' . esc_textarea($item['text']) . '</textarea>';
                    echo '</div>'; // .mk-mcp-item-content
                    echo '</div>'; // .mk-mcp-item
                 } else {
                    // Frontend view
                    echo '<div class="mk-mcp-item">';
                    echo '<h4 class="mk-mcp-item-title">' . esc_html($item['title']) . '</h4>';
                    echo '<div class="mk-mcp-item-text">' . wp_kses_post($item['text']) . '</div>';
                    echo '</div>';
                 }
            }
        }

        echo '</div>'; // .mk-mcp-day-items-wrapper
        if ($is_admin) {
            echo '<button type="button" class="button mk-mcp-add-item-btn">' . __('Add Item', 'mk-monthly-calendar-planner') . '</button>';
        }
        echo '</div>'; // .mk-mcp-day
    }

    // Blank days after the end of the month
    $total_cells = ($day_of_week - 1) + $num_days_in_month;
    $remaining_cells = 7 - ($total_cells % 7);
    if ($remaining_cells < 7) {
        for ($i = 0; $i < $remaining_cells; $i++) {
            echo '<div class="mk-mcp-day mk-mcp-day-empty"></div>';
        }
    }

    echo '</div>'; // .mk-mcp-calendar-grid
}
