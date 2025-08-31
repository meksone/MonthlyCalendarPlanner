<?php
/**
 * Plugin Name:       mk-monthly-calendar-planner
 * Description:       A plugin to create and display monthly calendars with events using a shortcode.
 * Version:           1.0.4
 * Author:            meksONE
 * Author URI:        https://meksone.com/
 * Text Domain:       mk-monthly-calendar-planner
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

define( 'MK_MCP_VERSION', '1.0.4' );
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
        // Enqueue jQuery UI for drag and drop
        wp_enqueue_script('jquery-ui-sortable');
        wp_enqueue_script('jquery-ui-draggable');
        wp_enqueue_script('jquery-ui-droppable');

        // Enqueue admin styles
        wp_enqueue_style(
            'mk-mcp-admin-style',
            MK_MCP_PLUGIN_URL . 'assets/css/admin-style.css',
            array(),
            MK_MCP_VERSION,
            'all'
        );

        // Enqueue admin script
        wp_enqueue_script(
            'mk-mcp-admin-script',
            MK_MCP_PLUGIN_URL . 'assets/js/admin-script.js',
            array('jquery', 'jquery-ui-sortable', 'jquery-ui-draggable', 'jquery-ui-droppable'),
            MK_MCP_VERSION,
            true
        );
        
        // Pass data to script
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
    wp_enqueue_style(
        'mk-mcp-frontend-style',
        MK_MCP_PLUGIN_URL . 'assets/css/frontend-style.css',
        array(),
        MK_MCP_VERSION,
        'all'
    );
}
add_action( 'wp_enqueue_scripts', 'mk_mcp_frontend_enqueue_scripts' );