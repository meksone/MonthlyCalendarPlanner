<?php
/**
 * Frontend rendering functions for Monthly Calendar Planner
 * @version 1.1.0
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Converts pre-v1.1 data structure to the new format with column support.
 * If the old structure is detected (numeric array of items for a day),
 * it wraps it into a new structure under column index 0.
 *
 * @param array $items The raw item data from post meta.
 * @return array The item data in the new v2 format.
 */
function mk_mcp_convert_data_to_v2_format($items) {
    if (empty($items) || !is_array($items)) return [];
    $first_day_key = array_key_first($items);
    // Simple check: if the first item for a day is an array with a 'title' key, it's the old format.
    if (is_numeric($first_day_key) && isset($items[$first_day_key][0]['title'])) {
        $new_data = [];
        foreach ($items as $day => $day_items) {
            $new_data[$day][0] = $day_items; // Place all old items into the first column (index 0)
        }
        return $new_data;
    }
    // Otherwise, assume it's already the new format
    return $items;
}

/**
 * Renders the calendar grid view for both admin and frontend.
 *
 * @param int   $month      The month to display.
 * @param int   $year       The year to display.
 * @param array $items      The calendar items.
 * @param bool  $is_admin   Whether it's for the admin area or frontend.
 */
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
            // In calendar view, merge all columns into one for display
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

/**
 * Renders the frontend table view.
 *
 * @param int   $month           The month to display.
 * @param int   $year            The year to display.
 * @param array $items           The calendar items.
 * @param int   $column_count    The number of columns.
 * @param array $column_names    The names of the columns.
 */
function mk_mcp_render_frontend_table($month, $year, $items, $column_count, $column_names){
    $num_days_in_month = date('t', mktime(0,0,0,$month,1,$year));
    echo '<div class="mk-mcp-table-view">';
    for($day_num=1; $day_num<=$num_days_in_month; $day_num++){
        $day_name=date_i18n('l', mktime(0,0,0,$month,$day_num,$year));
        $has_content=false;
        // Check if there is any content for this day across all columns
        if(isset($items[$day_num])&&is_array($items[$day_num])){ for($col_idx=0; $col_idx<$column_count; $col_idx++){ if(!empty($items[$day_num][$col_idx])){ $has_content=true; break; } } }
        if(!$has_content) continue; // Skip rendering the day if it has no items
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
