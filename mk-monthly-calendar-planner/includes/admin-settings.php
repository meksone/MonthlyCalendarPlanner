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
 *
 * @return array
 */
function mk_mcp_get_default_style_settings() {
    return [
        'main_border_width'         => '1',
        'main_border_style'         => 'solid',
        'main_border_color'         => '#e0e0e0',
        'day_bg_color'              => '',
        'day_padding'               => '10',
        'day_margin'                => '1',
        'table_header_bg_color'     => '#f5f5f5',
        'table_header_padding'      => '10',
        'table_header_margin'       => '20',
        'table_header_border_width' => '0',
        'table_header_border_style' => 'none',
        'table_header_border_color' => '',
        'item_bg_color'             => '#ffffff',
        'item_border'               => '1px solid #e0e0e0', // Changed
        'item_text_color'           => '#555555',
        'item_font_family'          => '',
    ];
}


/**
 * Register the settings page submenu.
 */
function mk_mcp_register_settings_page() {
    add_submenu_page(
        'edit.php?post_type=monthly_calendar',
        __('Calendar Styling', 'mk-monthly-calendar-planner'),
        __('Styling', 'mk-monthly-calendar-planner'),
        'manage_options',
        'mk-mcp-settings',
        'mk_mcp_render_settings_page'
    );
}
add_action('admin_menu', 'mk_mcp_register_settings_page');

/**
 * Render the settings page.
 */
function mk_mcp_render_settings_page() {
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <form action="options.php" method="post">
            <?php
            settings_fields('mk_mcp_style_settings_group');
            do_settings_sections('mk-mcp-settings');
            submit_button(__('Save Settings', 'mk-monthly-calendar-planner'));
            ?>
        </form>
    </div>
    <?php
}

/**
 * Register settings, sections, and fields.
 */
function mk_mcp_settings_api_init() {
    register_setting(
        'mk_mcp_style_settings_group',
        'mk_mcp_style_settings',
        [
            'sanitize_callback' => 'mk_mcp_sanitize_style_settings',
            'default'           => mk_mcp_get_default_style_settings(),
        ]
    );

    // --- General View Section ---
    add_settings_section('mk_mcp_section_general', __('General View', 'mk-monthly-calendar-planner'), 'mk_mcp_section_general_callback', 'mk-mcp-settings');
    add_settings_field('main_border', __('Main Border', 'mk-monthly-calendar-planner'), 'mk_mcp_render_border_field', 'mk-mcp-settings', 'mk_mcp_section_general', ['id' => 'main_border']);
    add_settings_field('day_bg_color', __('Day Cell Background', 'mk-monthly-calendar-planner'), 'mk_mcp_render_color_field', 'mk-mcp-settings', 'mk_mcp_section_general', ['id' => 'day_bg_color']);
    add_settings_field('day_padding', __('Day Cell Padding (px)', 'mk-monthly-calendar-planner'), 'mk_mcp_render_number_field', 'mk-mcp-settings', 'mk_mcp_section_general', ['id' => 'day_padding']);
    add_settings_field('day_margin', __('Day Cell Spacing (px)', 'mk-monthly-calendar-planner'), 'mk_mcp_render_number_field', 'mk-mcp-settings', 'mk_mcp_section_general', ['id' => 'day_margin']);

    // --- Table View Section ---
    add_settings_section('mk_mcp_section_table', __('Table View Only', 'mk-monthly-calendar-planner'), 'mk_mcp_section_table_callback', 'mk-mcp-settings');
    add_settings_field('table_header_bg_color', __('Column Header Background', 'mk-monthly-calendar-planner'), 'mk_mcp_render_color_field', 'mk-mcp-settings', 'mk_mcp_section_table', ['id' => 'table_header_bg_color']);
    add_settings_field('table_header_padding', __('Column Header Padding (px)', 'mk-monthly-calendar-planner'), 'mk_mcp_render_number_field', 'mk-mcp-settings', 'mk_mcp_section_table', ['id' => 'table_header_padding']);
    add_settings_field('table_header_margin', __('Column Header Margin (px)', 'mk-monthly-calendar-planner'), 'mk_mcp_render_number_field', 'mk-mcp-settings', 'mk_mcp_section_table', ['id' => 'table_header_margin']);
    add_settings_field('table_header_border', __('Column Header Border', 'mk-monthly-calendar-planner'), 'mk_mcp_render_border_field', 'mk-mcp-settings', 'mk_mcp_section_table', ['id' => 'table_header_border']);

    // --- Items Section ---
    add_settings_section('mk_mcp_section_items', __('Individual Calendar Items', 'mk-monthly-calendar-planner'), 'mk_mcp_section_items_callback', 'mk-mcp-settings');
    add_settings_field('item_bg_color', __('Item Background', 'mk-monthly-calendar-planner'), 'mk_mcp_render_color_field', 'mk-mcp-settings', 'mk_mcp_section_items', ['id' => 'item_bg_color']);
    add_settings_field('item_border', __('Item Border CSS', 'mk-monthly-calendar-planner'), 'mk_mcp_render_text_field', 'mk-mcp-settings', 'mk_mcp_section_items', ['id' => 'item_border', 'placeholder' => 'e.g., 1px solid #000']); // Changed
    add_settings_field('item_text_color', __('Item Text Color', 'mk-monthly-calendar-planner'), 'mk_mcp_render_color_field', 'mk-mcp-settings', 'mk_mcp_section_items', ['id' => 'item_text_color']);
    add_settings_field('item_font_family', __('Item Font Family', 'mk-monthly-calendar-planner'), 'mk_mcp_render_text_field', 'mk-mcp-settings', 'mk_mcp_section_items', ['id' => 'item_font_family', 'placeholder' => 'e.g., Arial, sans-serif']);
}
add_action('admin_init', 'mk_mcp_settings_api_init');

/**
 * Sanitize the style settings before saving.
 */
function mk_mcp_sanitize_style_settings($input) {
    $sanitized_input = [];
    $allowed_border_styles = ['none', 'solid', 'dotted', 'dashed', 'double', 'groove', 'ridge', 'inset', 'outset'];

    // General Settings
    $sanitized_input['main_border_width'] = isset($input['main_border_width']) ? absint($input['main_border_width']) : '';
    $sanitized_input['main_border_color'] = isset($input['main_border_color']) ? sanitize_hex_color($input['main_border_color']) : '';
    if (isset($input['main_border_style']) && in_array($input['main_border_style'], $allowed_border_styles, true)) { $sanitized_input['main_border_style'] = $input['main_border_style']; }

    $sanitized_input['day_bg_color'] = isset($input['day_bg_color']) ? sanitize_hex_color($input['day_bg_color']) : '';
    $sanitized_input['day_padding'] = isset($input['day_padding']) ? absint($input['day_padding']) : '';
    $sanitized_input['day_margin'] = isset($input['day_margin']) ? absint($input['day_margin']) : '';

    // Table Header Settings
    $sanitized_input['table_header_bg_color'] = isset($input['table_header_bg_color']) ? sanitize_hex_color($input['table_header_bg_color']) : '';
    $sanitized_input['table_header_padding'] = isset($input['table_header_padding']) ? absint($input['table_header_padding']) : '';
    $sanitized_input['table_header_margin'] = isset($input['table_header_margin']) ? absint($input['table_header_margin']) : '';
    $sanitized_input['table_header_border_width'] = isset($input['table_header_border_width']) ? absint($input['table_header_border_width']) : '';
    $sanitized_input['table_header_border_color'] = isset($input['table_header_border_color']) ? sanitize_hex_color($input['table_header_border_color']) : '';
    if (isset($input['table_header_border_style']) && in_array($input['table_header_border_style'], $allowed_border_styles, true)) { $sanitized_input['table_header_border_style'] = $input['table_header_border_style']; }

    // Item Settings
    $sanitized_input['item_bg_color'] = isset($input['item_bg_color']) ? sanitize_hex_color($input['item_bg_color']) : '';
    $sanitized_input['item_border'] = isset($input['item_border']) ? wp_strip_all_tags($input['item_border']) : ''; // Changed
    $sanitized_input['item_text_color'] = isset($input['item_text_color']) ? sanitize_hex_color($input['item_text_color']) : '';
    $sanitized_input['item_font_family'] = isset($input['item_font_family']) ? sanitize_text_field($input['item_font_family']) : '';

    return $sanitized_input;
}

/* --- Section Callbacks --- */
function mk_mcp_section_general_callback() { echo '<p>' . __('Customize the main look of the calendar grid.', 'mk-monthly-calendar-planner') . '</p>'; }
function mk_mcp_section_table_callback() { echo '<p>' . __('Customize the headers for the table view.', 'mk-monthly-calendar-planner') . '</p>'; }
function mk_mcp_section_items_callback() { echo '<p>' . __('Customize the individual event items.', 'mk-monthly-calendar-planner') . '</p>'; }

/* --- Field Render Callbacks --- */
function mk_mcp_get_setting($id) {
    $options = get_option('mk_mcp_style_settings');
    $defaults = mk_mcp_get_default_style_settings();
    return isset($options[$id]) ? $options[$id] : $defaults[$id];
}

function mk_mcp_render_text_field($args) {
    $id = $args['id'];
    $value = mk_mcp_get_setting($id);
    $placeholder = isset($args['placeholder']) ? esc_attr($args['placeholder']) : '';
    echo "<input type='text' id='$id' name='mk_mcp_style_settings[$id]' value='" . esc_attr($value) . "' placeholder='$placeholder' class='regular-text' />";
}

function mk_mcp_render_number_field($args) {
    $id = $args['id'];
    $value = mk_mcp_get_setting($id);
    echo "<input type='number' id='$id' name='mk_mcp_style_settings[$id]' value='" . esc_attr($value) . "' class='small-text' />";
}

function mk_mcp_render_color_field($args) {
    $id = $args['id'];
    $value = mk_mcp_get_setting($id);
    echo "<input type='text' id='$id' name='mk_mcp_style_settings[$id]' value='" . esc_attr($value) . "' class='mk-mcp-color-picker' />";
}

function mk_mcp_render_border_field($args) {
    $id = $args['id'];

    $width = mk_mcp_get_setting($id.'_width');
    $style = mk_mcp_get_setting($id.'_style');
    $color = mk_mcp_get_setting($id.'_color');

    $styles = ['none', 'solid', 'dotted', 'dashed', 'double', 'groove', 'ridge', 'inset', 'outset'];

    echo "<input type='number' name='mk_mcp_style_settings[{$id}_width]' value='" . esc_attr($width) . "' class='small-text' placeholder='width (px)' />";
    echo "<select name='mk_mcp_style_settings[{$id}_style]'>";
    foreach ($styles as $s) {
        echo "<option value='{$s}' " . selected($style, $s, false) . ">" . ucfirst($s) . "</option>";
    }
    echo "</select>";
    echo "<input type='text' name='mk_mcp_style_settings[{$id}_color]' value='" . esc_attr($color) . "' class='mk-mcp-color-picker' placeholder='color' />";
}
