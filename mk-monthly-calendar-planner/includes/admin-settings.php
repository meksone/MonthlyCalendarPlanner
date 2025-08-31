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
        ]
    );

    // --- General View Section ---
    add_settings_section(
        'mk_mcp_section_general',
        __('General View', 'mk-monthly-calendar-planner'),
        'mk_mcp_section_general_callback',
        'mk-mcp-settings'
    );

    add_settings_field('main_border', __('Main Border', 'mk-monthly-calendar-planner'), 'mk_mcp_render_border_field', 'mk-mcp-settings', 'mk_mcp_section_general', ['id' => 'main_border', 'label' => 'Main calendar border']);
    add_settings_field('day_bg_color', __('Day Cell Background', 'mk-monthly-calendar-planner'), 'mk_mcp_render_color_field', 'mk-mcp-settings', 'mk_mcp_section_general', ['id' => 'day_bg_color', 'label' => 'Day cell background color']);
    add_settings_field('day_padding', __('Day Cell Padding (px)', 'mk-monthly-calendar-planner'), 'mk_mcp_render_number_field', 'mk-mcp-settings', 'mk_mcp_section_general', ['id' => 'day_padding', 'label' => 'Day cell padding']);
    add_settings_field('day_margin', __('Day Cell Spacing (px)', 'mk-monthly-calendar-planner'), 'mk_mcp_render_number_field', 'mk-mcp-settings', 'mk_mcp_section_general', ['id' => 'day_margin', 'label' => 'Day cell spacing']);

    // --- Table View Section ---
    add_settings_section(
        'mk_mcp_section_table',
        __('Table View Only', 'mk-monthly-calendar-planner'),
        'mk_mcp_section_table_callback',
        'mk-mcp-settings'
    );

    add_settings_field('table_header_bg_color', __('Column Header Background', 'mk-monthly-calendar-planner'), 'mk_mcp_render_color_field', 'mk-mcp-settings', 'mk_mcp_section_table', ['id' => 'table_header_bg_color', 'label' => 'Table header background color']);
    add_settings_field('table_header_padding', __('Column Header Padding (px)', 'mk-monthly-calendar-planner'), 'mk_mcp_render_number_field', 'mk-mcp-settings', 'mk_mcp_section_table', ['id' => 'table_header_padding', 'label' => 'Table header padding']);
    add_settings_field('table_header_margin', __('Column Header Margin (px)', 'mk-monthly-calendar-planner'), 'mk_mcp_render_number_field', 'mk-mcp-settings', 'mk_mcp_section_table', ['id' => 'table_header_margin', 'label' => 'Table header margin']);
    add_settings_field('table_header_border', __('Column Header Border', 'mk-monthly-calendar-planner'), 'mk_mcp_render_border_field', 'mk-mcp-settings', 'mk_mcp_section_table', ['id' => 'table_header_border', 'label' => 'Table header border']);

    // --- Items Section ---
    add_settings_section(
        'mk_mcp_section_items',
        __('Individual Calendar Items', 'mk-monthly-calendar-planner'),
        'mk_mcp_section_items_callback',
        'mk-mcp-settings'
    );

    add_settings_field('item_bg_color', __('Item Background', 'mk-monthly-calendar-planner'), 'mk_mcp_render_color_field', 'mk-mcp-settings', 'mk_mcp_section_items', ['id' => 'item_bg_color', 'label' => 'Item background color']);
    add_settings_field('item_border', __('Item Border', 'mk-monthly-calendar-planner'), 'mk_mcp_render_border_field', 'mk-mcp-settings', 'mk_mcp_section_items', ['id' => 'item_border', 'label' => 'Item border']);
    add_settings_field('item_text_color', __('Item Text Color', 'mk-monthly-calendar-planner'), 'mk_mcp_render_color_field', 'mk-mcp-settings', 'mk_mcp_section_items', ['id' => 'item_text_color', 'label' => 'Item text color']);
    add_settings_field('item_font_family', __('Item Font Family', 'mk-monthly-calendar-planner'), 'mk_mcp_render_text_field', 'mk-mcp-settings', 'mk_mcp_section_items', ['id' => 'item_font_family', 'placeholder' => 'e.g., Arial, sans-serif', 'label' => 'Item font family']);
}
add_action('admin_init', 'mk_mcp_settings_api_init');

/**
 * Sanitize the style settings before saving.
 *
 * @param array $input The input from the form.
 * @return array The sanitized input.
 */
function mk_mcp_sanitize_style_settings($input) {
    $sanitized_input = [];
    $allowed_border_styles = ['none', 'solid', 'dotted', 'dashed', 'double', 'groove', 'ridge', 'inset', 'outset'];

    // General Settings
    $sanitized_input['main_border_width'] = isset($input['main_border_width']) ? absint($input['main_border_width']) : '';
    $sanitized_input['main_border_color'] = isset($input['main_border_color']) ? sanitize_hex_color($input['main_border_color']) : '';
    if (isset($input['main_border_style']) && in_array($input['main_border_style'], $allowed_border_styles, true)) {
        $sanitized_input['main_border_style'] = $input['main_border_style'];
    }

    $sanitized_input['day_bg_color'] = isset($input['day_bg_color']) ? sanitize_hex_color($input['day_bg_color']) : '';
    $sanitized_input['day_padding'] = isset($input['day_padding']) ? absint($input['day_padding']) : '';
    $sanitized_input['day_margin'] = isset($input['day_margin']) ? absint($input['day_margin']) : '';

    // Table Header Settings
    $sanitized_input['table_header_bg_color'] = isset($input['table_header_bg_color']) ? sanitize_hex_color($input['table_header_bg_color']) : '';
    $sanitized_input['table_header_padding'] = isset($input['table_header_padding']) ? absint($input['table_header_padding']) : '';
    $sanitized_input['table_header_margin'] = isset($input['table_header_margin']) ? absint($input['table_header_margin']) : '';
    $sanitized_input['table_header_border_width'] = isset($input['table_header_border_width']) ? absint($input['table_header_border_width']) : '';
    $sanitized_input['table_header_border_color'] = isset($input['table_header_border_color']) ? sanitize_hex_color($input['table_header_border_color']) : '';
    if (isset($input['table_header_border_style']) && in_array($input['table_header_border_style'], $allowed_border_styles, true)) {
        $sanitized_input['table_header_border_style'] = $input['table_header_border_style'];
    }

    // Item Settings
    $sanitized_input['item_bg_color'] = isset($input['item_bg_color']) ? sanitize_hex_color($input['item_bg_color']) : '';
    $sanitized_input['item_border_width'] = isset($input['item_border_width']) ? absint($input['item_border_width']) : '';
    $sanitized_input['item_border_color'] = isset($input['item_border_color']) ? sanitize_hex_color($input['item_border_color']) : '';
    if (isset($input['item_border_style']) && in_array($input['item_border_style'], $allowed_border_styles, true)) {
        $sanitized_input['item_border_style'] = $input['item_border_style'];
    }
    $sanitized_input['item_text_color'] = isset($input['item_text_color']) ? sanitize_hex_color($input['item_text_color']) : '';
    $sanitized_input['item_font_family'] = isset($input['item_font_family']) ? sanitize_text_field($input['item_font_family']) : '';

    return $sanitized_input;
}

/* --- Section Callbacks --- */
function mk_mcp_section_general_callback() { echo '<p>' . __('Customize the main look of the calendar grid.', 'mk-monthly-calendar-planner') . '</p>'; }
function mk_mcp_section_table_callback() { echo '<p>' . __('Customize the headers for the table view.', 'mk-monthly-calendar-planner') . '</p>'; }
function mk_mcp_section_items_callback() { echo '<p>' . __('Customize the individual event items.', 'mk-monthly-calendar-planner') . '</p>'; }

/* --- Field Render Callbacks --- */
function mk_mcp_render_text_field($args) {
    $options = get_option('mk_mcp_style_settings');
    $id = $args['id'];
    $value = isset($options[$id]) ? esc_attr($options[$id]) : '';
    $placeholder = isset($args['placeholder']) ? esc_attr($args['placeholder']) : '';
    echo "<input type='text' id='$id' name='mk_mcp_style_settings[$id]' value='$value' placeholder='$placeholder' class='regular-text' />";
}

function mk_mcp_render_number_field($args) {
    $options = get_option('mk_mcp_style_settings');
    $id = $args['id'];
    $value = isset($options[$id]) ? esc_attr($options[$id]) : '';
    echo "<input type='number' id='$id' name='mk_mcp_style_settings[$id]' value='$value' class='small-text' />";
}

function mk_mcp_render_color_field($args) {
    $options = get_option('mk_mcp_style_settings');
    $id = $args['id'];
    $value = isset($options[$id]) ? esc_attr($options[$id]) : '';
    echo "<input type='text' id='$id' name='mk_mcp_style_settings[$id]' value='$value' class='mk-mcp-color-picker' />";
}

function mk_mcp_render_border_field($args) {
    $options = get_option('mk_mcp_style_settings');
    $id = $args['id'];

    $width = isset($options[$id.'_width']) ? esc_attr($options[$id.'_width']) : '';
    $style = isset($options[$id.'_style']) ? $options[$id.'_style'] : 'solid';
    $color = isset($options[$id.'_color']) ? esc_attr($options[$id.'_color']) : '';

    $styles = ['none', 'solid', 'dotted', 'dashed', 'double', 'groove', 'ridge', 'inset', 'outset'];

    echo "<input type='number' name='mk_mcp_style_settings[{$id}_width]' value='{$width}' class='small-text' placeholder='width (px)' />";
    echo "<select name='mk_mcp_style_settings[{$id}_style]'>";
    foreach ($styles as $s) {
        echo "<option value='{$s}' " . selected($style, $s, false) . ">" . ucfirst($s) . "</option>";
    }
    echo "</select>";
    echo "<input type='text' name='mk_mcp_style_settings[{$id}_color]' value='{$color}' class='mk-mcp-color-picker' placeholder='color' />";
}
