<?php
/**
 * Admin Settings for Monthly Calendar Planner
 * @version 1.0.8
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Gets the default style settings.
 */
function mk_mcp_get_default_style_settings() {
    return [
        // General
        'main_border'           => '1px solid #e0e0e0',
        'main_border_top'       => '', 'main_border_right'     => '', 'main_border_bottom'     => '', 'main_border_left'     => '',
        'day_bg_color'          => '',
        'day_padding_top' => '10', 'day_padding_right' => '10', 'day_padding_bottom' => '10', 'day_padding_left' => '10',
        'day_margin'            => '1',
        'day_number_font_size'  => '19',
        'day_name_font_size'    => '14',
        'day_header_font_size'  => '16',
        // Table
        'table_header_bg_color' => '#f5f5f5',
        'table_header_padding_top' => '10', 'table_header_padding_right' => '15', 'table_header_padding_bottom' => '10', 'table_header_padding_left' => '15',
        'table_header_margin'   => '20',
        'table_header_border'   => '',
        'table_header_border_top' => '', 'table_header_border_right' => '', 'table_header_border_bottom' => '', 'table_header_border_left' => '',
        // Items
        'item_bg_color'         => '#ffffff',
        'item_border'           => '1px solid #e0e0e0',
        'item_border_top'       => '', 'item_border_right'     => '', 'item_border_bottom'     => '', 'item_border_left'     => '',
        'item_padding_top' => '12', 'item_padding_right' => '12', 'item_padding_bottom' => '12', 'item_padding_left' => '12',
        'item_margin'           => '10',
        'item_title_font_size'  => '16',
        'item_text_font_size'   => '14',
        'item_text_color'       => '#555555',
        'item_font_family'      => '',
    ];
}


/**
 * Register the settings page submenu.
 */
function mk_mcp_register_settings_page() {
    add_submenu_page('edit.php?post_type=monthly_calendar', __('Calendar Styling', 'mk-monthly-calendar-planner'), __('Styling', 'mk-monthly-calendar-planner'), 'manage_options', 'mk-mcp-settings', 'mk_mcp_render_settings_page');
}
add_action('admin_menu', 'mk_mcp_register_settings_page');

/**
 * Render the settings page.
 */
function mk_mcp_render_settings_page() {
    ?>
    <div class="wrap mk-mcp-settings-wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <form action="options.php" method="post">
            <?php settings_fields('mk_mcp_style_settings_group'); do_settings_sections('mk-mcp-settings'); submit_button(__('Save Settings', 'mk-monthly-calendar-planner')); ?>
        </form>
    </div>
    <?php
}

/**
 * Register settings, sections, and fields.
 */
function mk_mcp_settings_api_init() {
    register_setting('mk_mcp_style_settings_group', 'mk_mcp_style_settings', ['sanitize_callback' => 'mk_mcp_sanitize_style_settings', 'default' => mk_mcp_get_default_style_settings()]);

    // --- General View Section ---
    add_settings_section('mk_mcp_section_general', __('General View', 'mk-monthly-calendar-planner'), 'mk_mcp_section_general_callback', 'mk-mcp-settings');
    add_settings_field('main_border', __('Main Border', 'mk-monthly-calendar-planner'), 'mk_mcp_render_border_field', 'mk-mcp-settings', 'mk_mcp_section_general', ['id' => 'main_border']);
    add_settings_field('day_bg_color', __('Day Cell Background', 'mk-monthly-calendar-planner'), 'mk_mcp_render_color_field', 'mk-mcp-settings', 'mk_mcp_section_general', ['id' => 'day_bg_color']);
    add_settings_field('day_padding', __('Day Cell Padding (px)', 'mk-monthly-calendar-planner'), 'mk_mcp_render_padding_field', 'mk-mcp-settings', 'mk_mcp_section_general', ['id' => 'day_padding']);
    add_settings_field('day_margin', __('Day Cell Spacing (px)', 'mk-monthly-calendar-planner'), 'mk_mcp_render_number_field', 'mk-mcp-settings', 'mk_mcp_section_general', ['id' => 'day_margin', 'label' => 'Day Cell Spacing (px)']);
    add_settings_field('day_header_font_size', __('Day Header Font Size (px)', 'mk-monthly-calendar-planner'), 'mk_mcp_render_number_field', 'mk-mcp-settings', 'mk_mcp_section_general', ['id' => 'day_header_font_size']);
    add_settings_field('day_number_font_size', __('Day Number Font Size (px)', 'mk-monthly-calendar-planner'), 'mk_mcp_render_number_field', 'mk-mcp-settings', 'mk_mcp_section_general', ['id' => 'day_number_font_size']);
    add_settings_field('day_name_font_size', __('Day Name Font Size (px)', 'mk-monthly-calendar-planner'), 'mk_mcp_render_number_field', 'mk-mcp-settings', 'mk_mcp_section_general', ['id' => 'day_name_font_size']);

    // --- Table View Section ---
    add_settings_section('mk_mcp_section_table', __('Table View Only', 'mk-monthly-calendar-planner'), 'mk_mcp_section_table_callback', 'mk-mcp-settings');
    add_settings_field('table_header_bg_color', __('Column Header Background', 'mk-monthly-calendar-planner'), 'mk_mcp_render_color_field', 'mk-mcp-settings', 'mk_mcp_section_table', ['id' => 'table_header_bg_color']);
    add_settings_field('table_header_padding', __('Column Header Padding (px)', 'mk-monthly-calendar-planner'), 'mk_mcp_render_padding_field', 'mk-mcp-settings', 'mk_mcp_section_table', ['id' => 'table_header_padding']);
    add_settings_field('table_header_border', __('Column Header Border', 'mk-monthly-calendar-planner'), 'mk_mcp_render_border_field', 'mk-mcp-settings', 'mk_mcp_section_table', ['id' => 'table_header_border']);

    // --- Items Section ---
    add_settings_section('mk_mcp_section_items', __('Individual Calendar Items', 'mk-monthly-calendar-planner'), 'mk_mcp_section_items_callback', 'mk-mcp-settings');
    add_settings_field('item_bg_color', __('Item Background', 'mk-monthly-calendar-planner'), 'mk_mcp_render_color_field', 'mk-mcp-settings', 'mk_mcp_section_items', ['id' => 'item_bg_color']);
    add_settings_field('item_border', __('Item Border', 'mk-monthly-calendar-planner'), 'mk_mcp_render_border_field', 'mk-mcp-settings', 'mk_mcp_section_items', ['id' => 'item_border']);
    add_settings_field('item_padding', __('Item Padding (px)', 'mk-monthly-calendar-planner'), 'mk_mcp_render_padding_field', 'mk-mcp-settings', 'mk_mcp_section_items', ['id' => 'item_padding']);
    add_settings_field('item_margin', __('Item Margin (px)', 'mk-monthly-calendar-planner'), 'mk_mcp_render_number_field', 'mk-mcp-settings', 'mk_mcp_section_items', ['id' => 'item_margin']);
    add_settings_field('item_title_font_size', __('Item Title Font Size (px)', 'mk-monthly-calendar-planner'), 'mk_mcp_render_number_field', 'mk-mcp-settings', 'mk_mcp_section_items', ['id' => 'item_title_font_size']);
    add_settings_field('item_text_font_size', __('Item Text Font Size (px)', 'mk-monthly-calendar-planner'), 'mk_mcp_render_number_field', 'mk-mcp-settings', 'mk_mcp_section_items', ['id' => 'item_text_font_size']);
    add_settings_field('item_text_color', __('Item Text Color', 'mk-monthly-calendar-planner'), 'mk_mcp_render_color_field', 'mk-mcp-settings', 'mk_mcp_section_items', ['id' => 'item_text_color']);
    add_settings_field('item_font_family', __('Item Font Family', 'mk-monthly-calendar-planner'), 'mk_mcp_render_text_field', 'mk-mcp-settings', 'mk_mcp_section_items', ['id' => 'item_font_family', 'placeholder' => 'e.g., Arial, sans-serif']);
}
add_action('admin_init', 'mk_mcp_settings_api_init');

/**
 * Sanitize the style settings before saving.
 */
function mk_mcp_sanitize_style_settings($input) {
    $sanitized_input = [];
    $defaults = mk_mcp_get_default_style_settings();
    foreach ($defaults as $key => $default_value) {
        if (!isset($input[$key])) { continue; }
        if (strpos($key, '_color') !== false) { $sanitized_input[$key] = sanitize_hex_color($input[$key]); }
        elseif (strpos($key, 'font_size') !== false || strpos($key, '_padding') !== false || strpos($key, '_margin') !== false) { $sanitized_input[$key] = isset($input[$key]) && $input[$key] !== '' ? absint($input[$key]) : ''; }
        elseif (strpos($key, 'border') !== false) { $sanitized_input[$key] = wp_strip_all_tags($input[$key]); }
        else { $sanitized_input[$key] = sanitize_text_field($input[$key]); }
    }
    return $sanitized_input;
}

/* --- Section Callbacks --- */
function mk_mcp_section_general_callback() { echo '<p>' . __('Customize the main look of the calendar grid and day cells.', 'mk-monthly-calendar-planner') . '</p>'; }
function mk_mcp_section_table_callback() { echo '<p>' . __('Customize the headers for the table view.', 'mk-monthly-calendar-planner') . '</p>'; }
function mk_mcp_section_items_callback() { echo '<p>' . __('Customize the individual event items.', 'mk-monthly-calendar-planner') . '</p>'; }

/* --- Field Render Callbacks --- */
function mk_mcp_get_setting($id) {
    $options = get_option('mk_mcp_style_settings', mk_mcp_get_default_style_settings());
    return isset($options[$id]) ? $options[$id] : '';
}

function mk_mcp_render_text_field($args) {
    $id = $args['id']; $value = mk_mcp_get_setting($id); $placeholder = isset($args['placeholder']) ? esc_attr($args['placeholder']) : '';
    echo "<input type='text' id='$id' name='mk_mcp_style_settings[$id]' value='" . esc_attr($value) . "' placeholder='$placeholder' class='regular-text' />";
}

function mk_mcp_render_number_field($args) {
    $id = $args['id']; $value = mk_mcp_get_setting($id);
    echo "<input type='number' id='$id' name='mk_mcp_style_settings[$id]' value='" . esc_attr($value) . "' class='small-text' />";
}

function mk_mcp_render_color_field($args) {
    $id = $args['id']; $value = mk_mcp_get_setting($id);
    echo "<input type='text' id='$id' name='mk_mcp_style_settings[$id]' value='" . esc_attr($value) . "' class='mk-mcp-color-picker' />";
}

function mk_mcp_render_border_field($args) {
    $id = $args['id'];
    $fields = [$id => 'Border (shorthand)', $id.'_top' => 'Border Top', $id.'_right' => 'Border Right', $id.'_bottom' => 'Border Bottom', $id.'_left' => 'Border Left'];
    echo '<div class="border-controls-wrapper">';
    foreach ($fields as $field_id => $label) {
        $value = mk_mcp_get_setting($field_id);
        echo "<div class='border-control-item'><label for='{$field_id}'>{$label}</label><input type='text' id='{$field_id}' name='mk_mcp_style_settings[{$field_id}]' value='" . esc_attr($value) . "' class='regular-text' placeholder='e.g., 1px solid #000' /></div>";
    }
    echo '</div>';
}

function mk_mcp_render_padding_field($args) {
    $id = $args['id'];
    $sides = ['top', 'right', 'bottom', 'left'];
    echo '<div class="spacing-controls-wrapper">';
    foreach ($sides as $side) {
        $field_id = $id . '_' . $side;
        $value = mk_mcp_get_setting($field_id);
        echo "<div class='spacing-control-item'><label for='{$field_id}'>" . ucfirst($side) . "</label><input type='number' id='{$field_id}' name='mk_mcp_style_settings[{$field_id}]' value='" . esc_attr($value) . "' class='small-text' /></div>";
    }
    echo '</div>';
}
