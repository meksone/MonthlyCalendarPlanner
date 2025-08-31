<?php
/**
 * Main Meta Box for Monthly Calendar Planner
 * @version 1.0.7
 */

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

    $selected_month = get_post_meta($post->ID, '_mk_mcp_month', true) ?: date('n');
    $selected_year = get_post_meta($post->ID, '_mk_mcp_year', true) ?: date('Y');
    $calendar_items = get_post_meta($post->ID, '_mk_mcp_calendar_items', true);
    $view_mode = get_post_meta($post->ID, '_mk_mcp_view_mode', true) ?: 'calendar';
    $column_count = get_post_meta($post->ID, '_mk_mcp_column_count', true) ?: 4;
    $column_names_json = get_post_meta($post->ID, '_mk_mcp_column_names', true);
    $column_names = !empty($column_names_json) ? json_decode($column_names_json, true) : [__('Morning', 'mk-monthly-calendar-planner'), __('Pause', 'mk-monthly-calendar-planner'), __('Evening', 'mk-monthly-calendar-planner'), __('Night', 'mk-monthly-calendar-planner')];
    
    // Fetch templates
    $templates = get_posts([
        'post_type' => 'mcp_template',
        'numberposts' => -1,
        'post_status' => 'publish',
    ]);
    ?>
    <div class="mk-mcp-meta-box-wrapper">
        <div class="mk-mcp-settings-fields">
            <div class="mk-mcp-field"><label for="mk_mcp_month"><?php _e('Month', 'mk-monthly-calendar-planner'); ?></label><select name="mk_mcp_month" id="mk_mcp_month"><?php for ($m=1; $m<=12; $m++){ echo '<option value="'.esc_attr($m).'"'.selected($selected_month, $m, false).'>'.esc_html(date_i18n('F', mktime(0,0,0,$m,10))).'</option>'; } ?></select></div>
            <div class="mk-mcp-field"><label for="mk_mcp_year"><?php _e('Year', 'mk-monthly-calendar-planner'); ?></label><input type="number" id="mk_mcp_year" name="mk_mcp_year" value="<?php echo esc_attr($selected_year); ?>" min="1900" max="2100" /></div>
            <div class="mk-mcp-field"><label for="mk_mcp_view_mode"><?php _e('View Mode', 'mk-monthly-calendar-planner'); ?></label><select name="mk_mcp_view_mode" id="mk_mcp_view_mode"><option value="calendar" <?php selected($view_mode, 'calendar'); ?>><?php _e('Calendar View', 'mk-monthly-calendar-planner'); ?></option><option value="table" <?php selected($view_mode, 'table'); ?>><?php _e('Table View', 'mk-monthly-calendar-planner'); ?></option></select></div>
        </div>
        <div id="mk-mcp-table-settings" class="mk-mcp-settings-fields <?php echo $view_mode==='table'?'':'settings-hidden'; ?>"><div class="mk-mcp-field"><label for="mk_mcp_column_count"><?php _e('Number of Columns', 'mk-monthly-calendar-planner'); ?></label><select name="mk_mcp_column_count" id="mk_mcp_column_count"><?php for ($i=3; $i<=6; $i++){ echo '<option value="'.$i.'"'.selected($column_count, $i, false).'>'.$i.'</option>'; } ?></select></div><div id="mk-mcp-column-names-wrapper" class="mk-mcp-field"><label><?php _e('Column Names', 'mk-monthly-calendar-planner'); ?></label><?php for($i=0; $i<6; $i++){ echo '<input type="text" name="mk_mcp_column_names[]" class="mk-mcp-column-name-input" value="'.esc_attr($column_names[$i]??'').'" '.($i>=$column_count?'style="display:none;"':'').'/>'; } ?></div></div>
        
        <div class="mk-mcp-editor-container">
            <div class="mk-mcp-main-content">
                <div id="mk-mcp-builder-container">
                     <div id="mk-mcp-builder-wrapper">
                        <div class="mk-mcp-loader"><p><?php _e('Loading View...', 'mk-monthly-calendar-planner'); ?></p></div>
                     </div>
                </div>
            </div>
            <aside id="mk-mcp-templates-sidebar">
                <h3><?php _e('Item Templates', 'mk-monthly-calendar-planner'); ?></h3>
                <?php if (empty($templates)): ?>
                    <p><?php _e('No templates found.', 'mk-monthly-calendar-planner'); ?> <a href="<?php echo admin_url('post-new.php?post_type=mcp_template'); ?>"><?php _e('Add one?', 'mk-monthly-calendar-planner'); ?></a></p>
                <?php else: foreach ($templates as $template): 
                    $item_title = get_post_meta($template->ID, '_mk_mcp_item_title', true);
                    $item_text = get_post_meta($template->ID, '_mk_mcp_item_text', true);
                    ?>
                    <div class="mk-mcp-template-item" data-item-title="<?php echo esc_attr($item_title); ?>" data-item-text="<?php echo esc_attr($item_text); ?>">
                        <?php echo esc_html($template->post_title); ?>
                    </div>
                <?php endforeach; endif; ?>
            </aside>
        </div>
        
        <input type="hidden" name="mk_mcp_calendar_items_json" id="mk_mcp_calendar_items_json" value="<?php echo esc_attr($calendar_items); ?>" />

        <!-- View Switcher Modal -->
        <div id="mk-mcp-view-switcher-modal-overlay" class="mk-mcp-modal-overlay">
            <div class="mk-mcp-modal">
                <div class="mk-mcp-modal-content">
                    <h3><?php _e('Layout Change Warning', 'mk-monthly-calendar-planner'); ?></h3>
                    <p><?php _e('Switching views may alter your item layout, as items from multiple columns will be merged into one. Do you want to proceed?', 'mk-monthly-calendar-planner'); ?></p>
                </div>
                <div class="mk-mcp-modal-buttons">
                    <button type="button" class="button" id="mk-mcp-cancel-switch"><?php _e('Cancel', 'mk-monthly-calendar-planner'); ?></button>
                    <button type="button" class="button button-primary" id="mk-mcp-confirm-switch"><?php _e('Proceed', 'mk-monthly-calendar-planner'); ?></button>
                </div>
            </div>
        </div>

    </div>
    <?php
}

/**
 * Save meta box data and sync with revisions.
 */
function mk_mcp_save_meta_box_data($post_id) {
    if (!isset($_POST['mk_mcp_meta_box_nonce']) || !wp_verify_nonce($_POST['mk_mcp_meta_box_nonce'], 'mk_mcp_save_meta_box_data')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (isset($_POST['post_type']) && 'monthly_calendar' == $_POST['post_type'] && !current_user_can('edit_post', $post_id)) return;

    // Save all the meta fields
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
            update_post_meta($post_id, '_mk_mcp_calendar_items', wp_json_encode($data));
        }
    }

    // Now, sync this saved data with the latest revision.
    $revisions = wp_get_post_revisions($post_id);
    if (!empty($revisions)) {
        $latest_revision = array_shift($revisions);
        $revision_id = $latest_revision->ID;

        $meta_keys = mk_mcp_get_revisioned_meta_keys();
        foreach ($meta_keys as $meta_key) {
            $meta_value = get_post_meta($post_id, $meta_key, true);
            if (false !== $meta_value) {
                update_metadata('post', $revision_id, $meta_key, $meta_value);
            }
        }
    }
}
add_action('save_post_monthly_calendar', 'mk_mcp_save_meta_box_data');


function mk_mcp_convert_data_to_v2_format($items) {
    if (empty($items) || !is_array($items)) return [];
    $first_day_key = array_key_first($items);
    if (is_numeric($first_day_key) && isset($items[$first_day_key][0]['title'])) {
        $new_data = [];
        foreach ($items as $day => $day_items) {
            $new_data[$day][0] = $day_items;
        }
        return $new_data;
    }
    return $items;
}

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

function mk_mcp_render_calendar_grid($month, $year, $items = [], $is_admin = false) {
    $first_day_of_month = mktime(0,0,0,$month,1,$year);
    $num_days_in_month=date('t', $first_day_of_month);
    $day_of_week = date('N', $first_day_of_month);
    echo '<div class="mk-mcp-calendar-grid">';
    $days = [__('Mon', 'mk-monthly-calendar-planner'),__('Tue', 'mk-monthly-calendar-planner'),__('Wed', 'mk-monthly-calendar-planner'),__('Thu', 'mk-monthly-calendar-planner'),__('Fri', 'mk-monthly-calendar-planner'),__('Sat', 'mk-monthly-calendar-planner'),__('Sun', 'mk-monthly-calendar-planner')];
    foreach($days as $day) echo '<div class="mk-mcp-day-header">'.esc_html($day).'</div>';
    for($i=1; $i<$day_of_week; $i++) echo '<div class="mk-mcp-day mk-mcp-day-empty"></div>';
    for($day_num=1; $day_num<=$num_days_in_month; $day_num++){
        $day_name = date_i18n('l', mktime(0,0,0,$month,$day_num,$year));
        echo '<div class="mk-mcp-day" data-day="'.esc_attr($day_num).'"><div class="mk-mcp-day-number">'.esc_html($day_num).'<span class="mk-mcp-day-name">'.esc_html($day_name).'</span></div>';
        echo '<div class="mk-mcp-day-items-wrapper" data-day="'.esc_attr($day_num).'" data-col="0">';
        if(!empty($items[$day_num])){
            $all_day_items = [];
            foreach($items[$day_num] as $col_items) { if(is_array($col_items)) $all_day_items=array_merge($all_day_items, $col_items); }
            foreach($all_day_items as $item){
                if($is_admin){ echo '<div class="mk-mcp-item" data-id="'.uniqid().'"><div class="mk-mcp-item-header"><span class="mk-mcp-item-title-preview">'.esc_html($item['title']).'</span> <div class="mk-mcp-item-actions"><button type="button" class="mk-mcp-duplicate-item" title="'.__('Duplicate', 'mk-monthly-calendar-planner').'">D</button><button type="button" class="mk-mcp-delete-item" title="'.__('Delete', 'mk-monthly-calendar-planner').'">X</button></div></div><div class="mk-mcp-item-content"><input type="text" class="mk-mcp-item-title" placeholder="'.__('Title', 'mk-monthly-calendar-planner').'" value="'.esc_attr($item['title']).'"><textarea class="mk-mcp-item-text" placeholder="'.__('Text', 'mk-monthly-calendar-planner').'">'.esc_textarea($item['text']).'</textarea></div></div>'; }
                else { echo '<div class="mk-mcp-item"><h4 class="mk-mcp-item-title">'.esc_html($item['title']).'</h4><div class="mk-mcp-item-text">'.wp_kses_post($item['text']).'</div></div>'; }
            }
        }
        echo '</div>';
        if($is_admin) echo '<button type="button" class="button mk-mcp-add-item-btn">'.__('Add Item', 'mk-monthly-calendar-planner').'</button>';
        echo '</div>';
    }
    $rem_cells = (7-((($day_of_week-1)+$num_days_in_month)%7))%7;
    for($i=0; $i<$rem_cells; $i++) echo '<div class="mk-mcp-day mk-mcp-day-empty"></div>';
    echo '</div>';
}

function mk_mcp_render_table_grid_admin($month, $year, $items, $column_count, $column_names){
    $num_days_in_month = date('t', mktime(0,0,0,$month,1,$year));
    echo '<table class="wp-list-table widefat fixed striped mk-mcp-admin-table"><thead><tr><th class="mk-mcp-table-day-header">'.__('Day', 'mk-monthly-calendar-planner').'</th>';
    for($i=0; $i<$column_count; $i++) echo '<th>'.esc_html($column_names[$i]??'Column '.($i+1)).'</th>';
    echo '</tr></thead><tbody>';
    for($day_num=1; $day_num<=$num_days_in_month; $day_num++){
        $day_name = date_i18n('D', mktime(0,0,0,$month,$day_num,$year));
        echo '<tr data-day="'.esc_attr($day_num).'"><th class="mk-mcp-table-day-label"><strong>'.esc_html($day_num).'</strong> '.esc_html($day_name).'</th>';
        for($col_idx=0; $col_idx<$column_count; $col_idx++){
            echo '<td class="mk-mcp-day-items-wrapper" data-day="'.esc_attr($day_num).'" data-col="'.esc_attr($col_idx).'">';
            if(!empty($items[$day_num][$col_idx])){ foreach($items[$day_num][$col_idx] as $item) { echo '<div class="mk-mcp-item" data-id="'.uniqid().'"><div class="mk-mcp-item-header"><span class="mk-mcp-item-title-preview">'.esc_html($item['title']).'</span> <div class="mk-mcp-item-actions"><button type="button" class="mk-mcp-duplicate-item" title="'.__('Duplicate', 'mk-monthly-calendar-planner').'">D</button><button type="button" class="mk-mcp-delete-item" title="'.__('Delete', 'mk-monthly-calendar-planner').'">X</button></div></div><div class="mk-mcp-item-content"><input type="text" class="mk-mcp-item-title" placeholder="'.__('Title', 'mk-monthly-calendar-planner').'" value="'.esc_attr($item['title']).'"><textarea class="mk-mcp-item-text" placeholder="'.__('Text', 'mk-monthly-calendar-planner').'">'.esc_textarea($item['text']).'</textarea></div></div>'; } }
            echo '<button type="button" class="button button-small mk-mcp-add-item-btn-table">'.__( '+', 'mk-monthly-calendar-planner' ).'</button></td>';
        }
        echo '</tr>';
    }
    echo '</tbody></table>';
}

function mk_mcp_render_frontend_table($month, $year, $items, $column_count, $column_names){
    $num_days_in_month = date('t', mktime(0,0,0,$month,1,$year));
    echo '<div class="mk-mcp-table-view">';
    for($day_num=1; $day_num<=$num_days_in_month; $day_num++){
        $day_name=date_i18n('l', mktime(0,0,0,$month,$day_num,$year));
        $has_content=false;
        if(isset($items[$day_num])&&is_array($items[$day_num])){ for($col_idx=0; $col_idx<$column_count; $col_idx++){ if(!empty($items[$day_num][$col_idx])){ $has_content=true; break; } } }
        if(!$has_content) continue;
        echo '<div class="mk-mcp-table-day-row"><div class="mk-mcp-table-day-header"><h3>'.esc_html($day_num).' '.esc_html($day_name).'</h3></div><div class="mk-mcp-table-cols-wrapper">';
        for($col_idx=0; $col_idx<$column_count; $col_idx++){
            if(!empty($items[$day_num][$col_idx])){
                echo '<div class="mk-mcp-table-col"><h4 class="mk-mcp-table-col-header">'.esc_html($column_names[$col_idx]??'').'</h4>';
                foreach($items[$day_num][$col_idx] as $item){ echo '<div class="mk-mcp-item"><h5 class="mk-mcp-item-title">'.esc_html($item['title']).'</h5><div class="mk-mcp-item-text">'.wp_kses_post($item['text']).'</div></div>'; }
                echo '</div>';
            }
        }
        echo '</div></div>';
    }
    echo '</div>';
}

