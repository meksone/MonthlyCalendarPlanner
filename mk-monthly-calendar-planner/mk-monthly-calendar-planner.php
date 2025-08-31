<?php
/**
 * Plugin Name:       mk-monthly-calendar-planner
 * Description:       A plugin to create and display monthly calendars with events using a shortcode.
 * Version:           1.0.8
 * Author:            meksONE
 * Author URI:        https://meksone.com/
 * Text Domain:       mk-monthly-calendar-planner
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

define( 'MK_MCP_VERSION', '1.0.8' );
define( 'MK_MCP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'MK_MCP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Load plugin textdomain for translation.
 */
function mk_mcp_load_textdomain() {
    load_plugin_textdomain( 'mk-monthly-calendar-planner', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}
add_action( 'plugins_loaded', 'mk_mcp_load_textdomain' );


// Include required files
require_once MK_MCP_PLUGIN_DIR . 'includes/cpt.php';
require_once MK_MCP_PLUGIN_DIR . 'includes/meta-boxes.php';
require_once MK_MCP_PLUGIN_DIR . 'includes/shortcode.php';
require_once MK_MCP_PLUGIN_DIR . 'includes/templates.php';
require_once MK_MCP_PLUGIN_DIR . 'includes/admin-settings.php';

/**
 * Enqueue scripts and styles for the admin area.
 */
function mk_mcp_admin_enqueue_scripts($hook) {
    global $post_type;
    if ( ('post.php' == $hook || 'post-new.php' == $hook) && in_array($post_type, ['monthly_calendar', 'mcp_template']) ) {
        wp_enqueue_script('jquery-ui-sortable');
        wp_enqueue_script('jquery-ui-draggable');
        wp_enqueue_script('jquery-ui-droppable');

        wp_enqueue_style('mk-mcp-admin-style', MK_MCP_PLUGIN_URL . 'assets/css/admin-style.css', array(), MK_MCP_VERSION, 'all');
        wp_enqueue_script('mk-mcp-admin-script', MK_MCP_PLUGIN_URL . 'assets/js/admin-script.js', array('jquery', 'jquery-ui-sortable', 'jquery-ui-draggable', 'jquery-ui-droppable'), MK_MCP_VERSION, true);
        
        wp_localize_script('mk-mcp-admin-script', 'mk_mcp_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('mk-mcp-nonce'),
            'i18n'     => array(
                'view_change_warning' => __('Switching views may alter your item layout, as items from multiple columns will be merged into one. Do you want to proceed?', 'mk-monthly-calendar-planner'),
                'proceed' => __('Proceed', 'mk-monthly-calendar-planner'),
                'cancel' => __('Cancel', 'mk-monthly-calendar-planner'),
                'delete_confirm' => __('Are you sure you want to delete this item?', 'mk-monthly-calendar-planner'),
            )
        ));
    }

    // Enqueue color picker for our settings page
    if ('monthly_calendar_page_mk-mcp-settings' == $hook) {
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('mk-mcp-settings-script', MK_MCP_PLUGIN_URL . 'assets/js/admin-settings.js', ['wp-color-picker'], MK_MCP_VERSION, true);
    }
}
add_action( 'admin_enqueue_scripts', 'mk_mcp_admin_enqueue_scripts' );

/**
 * Generates dynamic CSS based on user settings.
 *
 * @return string The generated CSS.
 */
function mk_mcp_generate_dynamic_css() {
    $options = get_option('mk_mcp_style_settings');
    if (empty($options)) {
        return '';
    }

    $css = '';

    // Helper for border styles
    $border_css = function($prefix, $selector) use ($options, &$css) {
        if (!empty($options[$prefix.'_width']) || !empty($options[$prefix.'_style']) || !empty($options[$prefix.'_color'])) {
            $width = !empty($options[$prefix.'_width']) ? absint($options[$prefix.'_width']) . 'px' : '0';
            $style = !empty($options[$prefix.'_style']) ? esc_attr($options[$prefix.'_style']) : 'none';
            $color = !empty($options[$prefix.'_color']) ? esc_attr($options[$prefix.'_color']) : 'transparent';
            $css .= "$selector { border: $width $style $color; }\n";
        }
    };

    // General View
    $border_css('main_border', '.mk-mcp-calendar-grid');
    if (!empty($options['day_bg_color'])) { $css .= ".mk-mcp-day { background-color: " . esc_attr($options['day_bg_color']) . "; }\n"; }
    if (!empty($options['day_padding'])) { $css .= ".mk-mcp-day { padding: " . absint($options['day_padding']) . "px; }\n"; }
    if (isset($options['day_margin']) && $options['day_margin'] !== '') { $css .= ".mk-mcp-calendar-grid { gap: " . absint($options['day_margin']) . "px; }\n"; }

    // Table View
    if (!empty($options['table_header_bg_color'])) { $css .= ".mk-mcp-table-day-header { background-color: " . esc_attr($options['table_header_bg_color']) . "; }\n"; }
    if (!empty($options['table_header_padding'])) { $css .= ".mk-mcp-table-day-header { padding: " . absint($options['table_header_padding']) . "px; }\n"; }
    if (isset($options['table_header_margin']) && $options['table_header_margin'] !== '') { $css .= ".mk-mcp-table-day-row { margin-bottom: " . absint($options['table_header_margin']) . "px; }\n"; }
    $border_css('table_header_border', '.mk-mcp-table-day-header');

    // Items
    if (!empty($options['item_bg_color'])) { $css .= ".mk-mcp-item { background-color: " . esc_attr($options['item_bg_color']) . "; }\n"; }
    if (!empty($options['item_border'])) { $css .= ".mk-mcp-item { border: " . esc_attr($options['item_border']) . "; }\n"; }
    if (!empty($options['item_text_color'])) { $css .= ".mk-mcp-item, .mk-mcp-item .mk-mcp-item-title { color: " . esc_attr($options['item_text_color']) . "; }\n"; }
    if (!empty($options['item_font_family'])) { $css .= ".mk-mcp-frontend-calendar-wrapper { font-family: " . esc_attr($options['item_font_family']) . "; }\n"; }

    return $css;
}

/**
 * Enqueue scripts and styles for the frontend.
 */
function mk_mcp_frontend_enqueue_scripts() {
    wp_enqueue_style('mk-mcp-frontend-style', MK_MCP_PLUGIN_URL . 'assets/css/frontend-style.css', array(), MK_MCP_VERSION, 'all');

    $dynamic_css = mk_mcp_generate_dynamic_css();
    if (!empty($dynamic_css)) {
        wp_add_inline_style('mk-mcp-frontend-style', $dynamic_css);
    }
}
add_action( 'wp_enqueue_scripts', 'mk_mcp_frontend_enqueue_scripts' );


/* --- Revision System Integration --- */

/**
 * Get a list of meta keys to save with revisions.
 * @return array List of meta keys.
 */
function mk_mcp_get_revisioned_meta_keys() {
    return [
        '_mk_mcp_month',
        '_mk_mcp_year',
        '_mk_mcp_calendar_items',
        '_mk_mcp_view_mode',
        '_mk_mcp_column_count',
        '_mk_mcp_column_names',
        '_mk_mcp_item_title',
        '_mk_mcp_item_text',
    ];
}

/**
 * Restore meta data when a revision is restored.
 *
 * @param int $post_id     The ID of the main post.
 * @param int $revision_id The ID of the revision being restored.
 */
function mk_mcp_restore_revision_meta_data( $post_id, $revision_id ) {
    $post = get_post( $post_id );
     if ( ! in_array( $post->post_type, ['monthly_calendar', 'mcp_template'], true ) ) {
        return;
    }

    $meta_keys = mk_mcp_get_revisioned_meta_keys();
    foreach ( $meta_keys as $meta_key ) {
        $meta_value = get_metadata( 'post', $revision_id, $meta_key, true );
        if ( false !== $meta_value ) {
            update_post_meta( $post_id, $meta_key, $meta_value );
        } else {
            delete_post_meta( $post_id, $meta_key );
        }
    }
}
add_action( 'wp_restore_post_revision', 'mk_mcp_restore_revision_meta_data', 10, 2 );

/**
 * Sync meta data for a post to its latest revision.
 *
 * @param int $post_id The ID of the post.
 */
function mk_mcp_sync_meta_to_latest_revision( $post_id ) {
    $revisions = wp_get_post_revisions( $post_id );
    if ( ! empty( $revisions ) ) {
        $latest_revision = array_shift( $revisions );
        $revision_id     = $latest_revision->ID;

        $meta_keys = mk_mcp_get_revisioned_meta_keys();
        foreach ( $meta_keys as $meta_key ) {
            $meta_value = get_post_meta( $post_id, $meta_key, true );
            if ( false !== $meta_value ) {
                update_metadata( 'post', $revision_id, $meta_key, $meta_value );
            } else {
                // Also sync deleted meta fields
                delete_metadata( 'post', $revision_id, $meta_key );
            }
        }
    }
}

/**
 * Checks if any of the revisioned meta fields have changed.
 *
 * @param int $post_id The ID of the post being saved.
 * @return bool True if meta has changed, false otherwise.
 */
function mk_mcp_meta_has_changed( $post_id ) {
    // Nonce must be verified, as this check runs inside a filter triggered by WP core.
    $nonce_action = 'mcp_template' === get_post_type($post_id) ? 'mk_mcp_save_template_meta_box_data' : 'mk_mcp_save_meta_box_data';
    $nonce_name   = 'mcp_template' === get_post_type($post_id) ? 'mk_mcp_template_meta_box_nonce' : 'mk_mcp_meta_box_nonce';
    if ( !isset($_POST[$nonce_name]) || !wp_verify_nonce($_POST[$nonce_name], $nonce_action) ) {
        return false;
    }

    $meta_keys = mk_mcp_get_revisioned_meta_keys();
    foreach ($meta_keys as $meta_key) {
        $old_value = get_post_meta($post_id, $meta_key, true);
        $new_value_raw = null;

        $post_key_map = [
            '_mk_mcp_month'          => 'mk_mcp_month',
            '_mk_mcp_year'           => 'mk_mcp_year',
            '_mk_mcp_calendar_items' => 'mk_mcp_calendar_items_json',
            '_mk_mcp_view_mode'      => 'mk_mcp_view_mode',
            '_mk_mcp_column_count'   => 'mk_mcp_column_count',
            '_mk_mcp_column_names'   => 'mk_mcp_column_names',
            '_mk_mcp_item_title'     => 'mk_mcp_item_title',
            '_mk_mcp_item_text'      => 'mk_mcp_item_text',
        ];

        if (!isset($post_key_map[$meta_key])) continue;

        $post_key = $post_key_map[$meta_key];

        if (isset($_POST[$post_key])) {
            $new_value_raw = $_POST[$post_key];
            if ($meta_key === '_mk_mcp_calendar_items') {
                 $new_value_raw = wp_unslash($new_value_raw);
            } elseif ($meta_key === '_mk_mcp_column_names' && is_array($new_value_raw)) {
                 $new_value_raw = wp_json_encode(array_map('sanitize_text_field', $new_value_raw));
            }
        }

        if ( (string) $old_value !== (string) $new_value_raw ) {
            return true; // Found a change
        }
    }

    return false; // No changes found
}

/**
 * Filters whether a post has changed to force a revision on meta change.
 *
 * @param bool    $post_has_changed Whether the post has changed.
 * @param WP_Post $last_revision  The last revision post object.
 * @param WP_Post $post           The post object.
 * @return bool
 */
function mk_mcp_filter_revision_has_changed($post_has_changed, $last_revision, $post) {
    if ( !in_array( $post->post_type, ['monthly_calendar', 'mcp_template'] ) ) {
        return $post_has_changed;
    }

    if ( $post_has_changed ) {
        return true;
    }

    return mk_mcp_meta_has_changed($post->ID);
}
add_filter('wp_save_post_revision_post_has_changed', 'mk_mcp_filter_revision_has_changed', 10, 3);
