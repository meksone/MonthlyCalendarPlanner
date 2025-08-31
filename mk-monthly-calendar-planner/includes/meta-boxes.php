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
    wp_nonce_field('mk_mcp_save_meta_box_data', 'mk_mcp_meta_box_nonce');

    // Get saved values
    $selected_month = get_post_meta($post->ID, '_mk_mcp_month', true);
    $selected_year = get_post_meta($post->ID, '_mk_mcp_year', true);
    $calendar_items = get_post_meta($post->ID, '_mk_mcp_calendar_items', true);
    $view_mode = get_post_meta($post->ID, '_mk_mcp_view_mode', true) ?: 'calendar';
    $column_count = get_post_meta($post->ID, '_mk_mcp_column_count', true) ?: 4;
    $column_names_json = get_post_meta($post->ID, '_mk_mcp_column_names', true);
    $column_names = !empty($column_names_json) ? json_decode($column_names_json, true) : [__('Morning', 'mk-monthly-calendar-planner'), __('Pause', 'mk-monthly-calendar-planner'), __('Evening', 'mk-monthly-calendar-planner'), __('Night', 'mk-monthly-calendar-planner')];
    
    if (empty($selected_year)) $selected_year = date('Y');

    ?>
    <div class="mk-mcp-meta-box-wrapper">
        <div class="mk-mcp-settings-fields">
            <div class="mk-mcp-field">
                <label for="mk_mcp_month"><?php _e('Month', 'mk-monthly-calendar-planner'); ?></label>
                <select name="mk_mcp_month" id="mk_mcp_month">
                    <?php for ($m = 1; $m <= 12; $m++): $month_name = date_i18n('F', mktime(0, 0, 0, $m, 10)); ?>
                        <option value="<?php echo esc_attr($m); ?>" <?php selected($selected_month, $m); ?>><?php echo esc_html($month_name); ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="mk-mcp-field">
                <label for="mk_mcp_year"><?php _e('Year', 'mk-monthly-calendar-planner'); ?></label>
                <input type="number" id="mk_mcp_year" name="mk_mcp_year" value="<?php echo esc_attr($selected_year); ?>" min="1900" max="2100" />
            </div>
             <div class="mk-mcp-field">
                <label for="mk_mcp_view_mode"><?php _e('View Mode', 'mk-monthly-calendar-planner'); ?></label>
                <select name="mk_mcp_view_mode" id="mk_mcp_view_mode">
                    <option value="calendar" <?php selected($view_mode, 'calendar'); ?>><?php _e('Calendar View', 'mk-monthly-calendar-planner'); ?></option>
                    <option value="table" <?php selected($view_mode, 'table'); ?>><?php _e('Table View', 'mk-monthly-calendar-planner'); ?></option>
                </select>
            </div>
        </div>

        <div id="mk-mcp-table-settings" class="mk-mcp-settings-fields <?php echo $view_mode === 'table' ? '' : 'settings-hidden'; ?>">
            <div class="mk-mcp-field">
                 <label for="mk_mcp_column_count"><?php _e('Number of Columns', 'mk-monthly-calendar-planner'); ?></label>
                 <select name="mk_mcp_column_count" id="mk_mcp_column_count">
                    <?php for ($i = 3; $i <= 6; $i++): ?>
                        <option value="<?php echo $i; ?>" <?php selected($column_count, $i); ?>><?php echo $i; ?></option>
                    <?php endfor; ?>
                 </select>
            </div>
            <div id="mk-mcp-column-names-wrapper" class="mk-mcp-field">
                <label><?php _e('Column Names', 'mk-monthly-calendar-planner'); ?></label>
                <?php for ($i = 0; $i < 6; $i++): ?>
                    <input type="text" name="mk_mcp_column_names[]" class="mk-mcp-column-name-input" value="<?php echo esc_attr($column_names[$i] ?? ''); ?>" <?php echo $i >= $column_count ? 'style="display:none;"' : ''; ?>/>
                <?php endfor; ?>
            </div>
        </div>

        <div id="mk-mcp-builder-container">
             <div id="mk-mcp-builder-wrapper">
                <div class="mk-mcp-loader"><p><?php _e('Loading View...', 'mk-monthly-calendar-planner'); ?></p></div>
             </div>
        </div>
        
        <input type="hidden" name="mk_mcp_calendar_items_json" id="mk_mcp_calendar_items_json" value="<?php echo esc_attr($calendar_items); ?>" />
    </div>
    <?php
}

/**
 * Save meta box data.
 */
function mk_mcp_save_meta_box_data($post_id) {
    if (!isset($_POST['mk_mcp_meta_box_nonce']) || !wp_verify_nonce($_POST['mk_mcp_meta_box_nonce'], 'mk_mcp_save_meta_box_data')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (isset($_POST['post_type']) && 'monthly_calendar' == $_POST['post_type'] && !current_user_can('edit_post', $post_id)) return;

    // Sanitize and save fields
    $fields = ['_mk_mcp_month', '_mk_mcp_year', '_mk_mcp_view_mode', '_mk_mcp_column_count'];
    foreach ($fields as $field_key) {
        $post_key = str_replace('_mk_mcp_', 'mk_mcp_', $field_key); 
        if (isset($_POST[$post_key])) {
            update_post_meta($post_id, $field_key, sanitize_text_field($_POST[$post_key]));
        }
    }

    if (isset($_POST['mk_mcp_column_names']) && is_array($_POST['mk_mcp_column_names'])) {
        $sanitized_names = array_map('sanitize_text_field', $_POST['mk_mcp_column_names']);
        update_post_meta($post_id, '_mk_mcp_column_names', wp_json_encode($sanitized_names));
    }

    if (isset($_POST['mk_mcp_calendar_items_json'])) {
        $json_data = stripslashes($_POST['mk_mcp_calendar_items_json']);
        $data = json_decode($json_data, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            // Data is already structured correctly by JS, just re-encode and save.
            update_post_meta($post_id, '_mk_mcp_calendar_items', wp_json_encode($data));
        }
    }
}
add_action('save_post', 'mk_mcp_save_meta_box_data');

/**
 * Converts old data format { day: [items] } to new format { day: { 0: [items] } }
 */
function mk_mcp_convert_data_to_v2_format($items) {
    if (empty($items) || !is_array($items)) {
        return [];
    }
    // Check if the first day's value is an array of items (old) or an array of columns (new)
    $first_day_key = array_key_first($items);
    if (isset($items[$first_day_key][0]['title'])) { // Heuristic: if first element has a title, it's an item array.
        $new_data = [];
        foreach ($items as $day => $day_items) {
            $new_data[$day][0] = $day_items; // Put all items in column 0
        }
        return $new_data;
    }
    return $items; // Already in new format
}

/**
 * AJAX handler to get the builder grid/table.
 */
function mk_mcp_get_admin_builder_view() {
    check_ajax_referer('mk-mcp-nonce', 'nonce');

    $month = isset($_POST['month']) ? intval($_POST['month']) : 0;
    $year = isset($_POST['year']) ? intval($_POST['year']) : 0;
    $view_mode = isset($_POST['view_mode']) ? sanitize_text_field($_POST['view_mode']) : 'calendar';
    $items_json = isset($_POST['items']) ? stripslashes($_POST['items']) : '[]';
    $items_data = json_decode($items_json, true);
    $items_data = mk_mcp_convert_data_to_v2_format($items_data);

    if (!$month || !$year) wp_send_json_error('Invalid month or year.');
    
    ob_start();
    if ($view_mode === 'table') {
        $column_count = isset($_POST['column_count']) ? intval($_POST['column_count']) : 4;
        $column_names_json = isset($_POST['column_names']) ? stripslashes($_POST['column_names']) : '[]';
        $column_names = json_decode($column_names_json, true);
        mk_mcp_render_table_grid_admin($month, $year, $items_data, $column_count, $column_names);
    } else {
        mk_mcp_render_calendar_grid($month, $year, $items_data, true);
    }
    $html = ob_get_clean();

    wp_send_json_success($html);
}
add_action('wp_ajax_mk_mcp_get_admin_builder_view', 'mk_mcp_get_admin_builder_view');

/**
 * Renders the calendar grid (admin and frontend).
 */
function mk_mcp_render_calendar_grid($month, $year, $items = [], $is_admin = false) {
    $first_day_of_month = mktime(0, 0, 0, $month, 1, $year);
    $num_days_in_month = date('t', $first_day_of_month);
    $day_of_week = date('N', $first_day_of_month);

    echo '<div class="mk-mcp-calendar-grid">';
    $days = [__('Mon', 'mk-monthly-calendar-planner'), __('Tue', 'mk-monthly-calendar-planner'), __('Wed', 'mk-monthly-calendar-planner'), __('Thu', 'mk-monthly-calendar-planner'), __('Fri', 'mk-monthly-calendar-planner'), __('Sat', 'mk-monthly-calendar-planner'), __('Sun', 'mk-monthly-calendar-planner')];
    foreach ($days as $day) echo '<div class="mk-mcp-day-header">' . esc_html($day) . '</div>';
    for ($i = 1; $i < $day_of_week; $i++) echo '<div class="mk-mcp-day mk-mcp-day-empty"></div>';

    for ($day_num = 1; $day_num <= $num_days_in_month; $day_num++) {
        $timestamp = mktime(0, 0, 0, $month, $day_num, $year);
        $day_name = date_i18n('l', $timestamp);
        echo '<div class="mk-mcp-day" data-day="' . esc_attr($day_num) . '">';
        echo '<div class="mk-mcp-day-number">' . esc_html($day_num) . '<span class="mk-mcp-day-name">' . esc_html($day_name) . '</span></div>';
        echo '<div class="mk-mcp-day-items-wrapper" data-day="' . esc_attr($day_num) . '" data-col="0">'; // Default to col 0 for calendar view drops
        
        if (!empty($items[$day_num])) {
            // Flatten items from all columns for calendar view
            $all_day_items = [];
            foreach ($items[$day_num] as $col_items) {
                if(is_array($col_items)) $all_day_items = array_merge($all_day_items, $col_items);
            }

            foreach ($all_day_items as $item) {
                if ($is_admin) {
                    echo '<div class="mk-mcp-item" data-id="' . uniqid() .'">';
                    echo '<div class="mk-mcp-item-header"><span class="mk-mcp-item-title-preview">' . esc_html($item['title']) . '</span> <div class="mk-mcp-item-actions"><button type="button" class="mk-mcp-duplicate-item" title="' . __('Duplicate', 'mk-monthly-calendar-planner') . '">D</button><button type="button" class="mk-mcp-delete-item" title="' . __('Delete', 'mk-monthly-calendar-planner') . '">X</button></div></div>';
                    echo '<div class="mk-mcp-item-content"><input type="text" class="mk-mcp-item-title" placeholder="' . __('Title', 'mk-monthly-calendar-planner') . '" value="' . esc_attr($item['title']) . '"><textarea class="mk-mcp-item-text" placeholder="' . __('Text', 'mk-monthly-calendar-planner') . '">' . esc_textarea($item['text']) . '</textarea></div>';
                    echo '</div>';
                } else {
                    echo '<div class="mk-mcp-item"><h4 class="mk-mcp-item-title">' . esc_html($item['title']) . '</h4><div class="mk-mcp-item-text">' . wp_kses_post($item['text']) . '</div></div>';
                }
            }
        }

        echo '</div>'; // .mk-mcp-day-items-wrapper
        if ($is_admin) echo '<button type="button" class="button mk-mcp-add-item-btn">' . __('Add Item', 'mk-monthly-calendar-planner') . '</button>';
        echo '</div>'; // .mk-mcp-day
    }

    $total_cells = ($day_of_week - 1) + $num_days_in_month;
    $remaining_cells = (7 - ($total_cells % 7)) % 7;
    for ($i = 0; $i < $remaining_cells; $i++) echo '<div class="mk-mcp-day mk-mcp-day-empty"></div>';
    echo '</div>'; // .mk-mcp-calendar-grid
}

/**
 * Renders the table grid for the admin area.
 */
function mk_mcp_render_table_grid_admin($month, $year, $items, $column_count, $column_names) {
    $num_days_in_month = date('t', mktime(0, 0, 0, $month, 1, $year));
    echo '<table class="wp-list-table widefat fixed striped mk-mcp-admin-table">';
    echo '<thead><tr>';
    echo '<th class="mk-mcp-table-day-header">' . __('Day', 'mk-monthly-calendar-planner') . '</th>';
    for ($i = 0; $i < $column_count; $i++) {
        echo '<th>' . esc_html($column_names[$i] ?? 'Column ' . ($i + 1)) . '</th>';
    }
    echo '</tr></thead>';
    echo '<tbody>';
    for ($day_num = 1; $day_num <= $num_days_in_month; $day_num++) {
        $timestamp = mktime(0, 0, 0, $month, $day_num, $year);
        $day_name = date_i18n('D', $timestamp);
        echo '<tr data-day="' . esc_attr($day_num) . '">';
        echo '<th class="mk-mcp-table-day-label"><strong>' . esc_html($day_num) . '</strong> ' . esc_html($day_name) . '</th>';
        for ($col_idx = 0; $col_idx < $column_count; $col_idx++) {
            echo '<td class="mk-mcp-day-items-wrapper" data-day="' . esc_attr($day_num) . '" data-col="' . esc_attr($col_idx) . '">';
            if (!empty($items[$day_num][$col_idx])) {
                foreach ($items[$day_num][$col_idx] as $item) {
                    echo '<div class="mk-mcp-item" data-id="' . uniqid() .'">';
                    echo '<div class="mk-mcp-item-header"><span class="mk-mcp-item-title-preview">' . esc_html($item['title']) . '</span> <div class="mk-mcp-item-actions"><button type="button" class="mk-mcp-duplicate-item" title="' . __('Duplicate', 'mk-monthly-calendar-planner') . '">D</button><button type="button" class="mk-mcp-delete-item" title="' . __('Delete', 'mk-monthly-calendar-planner') . '">X</button></div></div>';
                    echo '<div class="mk-mcp-item-content"><input type="text" class="mk-mcp-item-title" placeholder="' . __('Title', 'mk-monthly-calendar-planner') . '" value="' . esc_attr($item['title']) . '"><textarea class="mk-mcp-item-text" placeholder="' . __('Text', 'mk-monthly-calendar-planner') . '">' . esc_textarea($item['text']) . '</textarea></div>';
                    echo '</div>';
                }
            }
             echo '<button type="button" class="button button-small mk-mcp-add-item-btn-table">' . __('+', 'mk-monthly-calendar-planner') . '</button>';
            echo '</td>';
        }
        echo '</tr>';
    }
    echo '</tbody></table>';
}

/**
 * Renders the table view for the frontend.
 */
function mk_mcp_render_frontend_table($month, $year, $items, $column_count, $column_names) {
    $num_days_in_month = date('t', mktime(0, 0, 0, $month, 1, $year));
    echo '<div class="mk-mcp-table-view">';
    for ($day_num = 1; $day_num <= $num_days_in_month; $day_num++) {
        $timestamp = mktime(0, 0, 0, $month, $day_num, $year);
        $day_name = date_i18n('l', $timestamp);
        $has_content = false;
        if(isset($items[$day_num]) && is_array($items[$day_num])){
            for ($col_idx = 0; $col_idx < $column_count; $col_idx++) {
                if (!empty($items[$day_num][$col_idx])) {
                    $has_content = true;
                    break;
                }
            }
        }
        if (!$has_content) continue; // Skip days with no items

        echo '<div class="mk-mcp-table-day-row">';
        echo '<div class="mk-mcp-table-day-header"><h3>' . esc_html($day_num) . ' ' . esc_html($day_name) . '</h3></div>';
        echo '<div class="mk-mcp-table-cols-wrapper">';
        for ($col_idx = 0; $col_idx < $column_count; $col_idx++) {
            if (!empty($items[$day_num][$col_idx])) {
                echo '<div class="mk-mcp-table-col">';
                echo '<h4 class="mk-mcp-table-col-header">' . esc_html($column_names[$col_idx] ?? '') . '</h4>';
                foreach ($items[$day_num][$col_idx] as $item) {
                    echo '<div class="mk-mcp-item"><h5 class="mk-mcp-item-title">' . esc_html($item['title']) . '</h5><div class="mk-mcp-item-text">' . wp_kses_post($item['text']) . '</div></div>';
                }
                echo '</div>';
            }
        }
        echo '</div></div>';
    }
    echo '</div>';
}