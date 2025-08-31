<?php
/**
 * Plugin Name:       mk-monthly-calendar-planner
 * Description:       A plugin to create and display monthly calendars with events using a shortcode.
 * Version:           1.0.7
 * Author:            meksONE
 * Author URI:        https://meksone.com/
 * Text Domain:       mk-monthly-calendar-planner
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

define( 'MK_MCP_VERSION', '1.0.7' );
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
            )
        ));
    }
}
add_action( 'admin_enqueue_scripts', 'mk_mcp_admin_enqueue_scripts' );

/**
 * Enqueue scripts and styles for the frontend.
 */
function mk_mcp_frontend_enqueue_scripts() {
    wp_enqueue_style('mk-mcp-frontend-style', MK_MCP_PLUGIN_URL . 'assets/css/frontend-style.css', array(), MK_MCP_VERSION, 'all');
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
 * Force a revision to be created if meta data has changed.
 *
 * @param array $data    An array of slashed post data.
 * @param array $postarr An array of sanitized, but otherwise unmodified post data.
 * @return array The filtered post data.
 */
function mk_mcp_force_revision_on_meta_change( $data, $postarr ) {
    if ( !isset($postarr['ID']) || !in_array( $data['post_type'], ['monthly_calendar', 'mcp_template'] ) || (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) ) {
        return $data;
    }
    
    $post_id = $postarr['ID'];
    $post = get_post($post_id);
    if ( ! wp_revisions_enabled( $post ) ) {
        return $data;
    }

    $nonce_action = 'mcp_template' === $data['post_type'] ? 'mk_mcp_save_template_meta_box_data' : 'mk_mcp_save_meta_box_data';
    $nonce_name = 'mcp_template' === $data['post_type'] ? 'mk_mcp_template_meta_box_nonce' : 'mk_mcp_meta_box_nonce';
    if ( !isset($_POST[$nonce_name]) || !wp_verify_nonce($_POST[$nonce_name], $nonce_action) ) {
        return $data;
    }

    $meta_has_changed = false;
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
                 $new_value_raw = stripslashes($new_value_raw);
            } elseif ($meta_key === '_mk_mcp_column_names' && is_array($new_value_raw)) {
                 $new_value_raw = wp_json_encode(array_map('sanitize_text_field', $new_value_raw));
            }
        }
        
        if ( (string) $old_value !== (string) $new_value_raw ) {
            $meta_has_changed = true;
            break;
        }
    }

    if ( $meta_has_changed ) {
        $data['post_content'] = $data['post_content'] . '<!-- ' . rand() . ' -->';
    }

    return $data;
}
add_filter( 'wp_insert_post_data', 'mk_mcp_force_revision_on_meta_change', 99, 2 );

