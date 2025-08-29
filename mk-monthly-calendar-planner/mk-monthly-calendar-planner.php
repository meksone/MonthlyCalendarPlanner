<?php
/**
 * Plugin Name:       MK Monthly Calendar Planner
 * Description:       A plugin to create and display monthly calendars with events using a shortcode.
 * Version:           1.0.0
 * Author:            meksONE
 * Author URI:        https://meksone.com/
 * Text Domain:       mk-monthly-calendar-planner
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

define( 'MCP_VERSION', '1.0.0' );
define( 'MCP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'MCP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Include required files
require_once MCP_PLUGIN_DIR . 'includes/cpt.php';
require_once MCP_PLUGIN_DIR . 'includes/meta-boxes.php';
require_once MCP_PLUGIN_DIR . 'includes/shortcode.php';

/**
 * Enqueue scripts and styles for the admin area.
 */
function mcp_admin_enqueue_scripts($hook) {
    global $post_type;
    if ( ('post.php' == $hook || 'post-new.php' == $hook) && 'monthly_calendar' == $post_type ) {
        // Enqueue jQuery UI for drag and drop
        wp_enqueue_script('jquery-ui-sortable');
        wp_enqueue_script('jquery-ui-draggable');
        wp_enqueue_script('jquery-ui-droppable');

        // Enqueue admin styles
        wp_enqueue_style(
            'mcp-admin-style',
            MCP_PLUGIN_URL . 'assets/css/admin-style.css',
            array(),
            MCP_VERSION,
            'all'
        );

        // Enqueue admin script
        wp_enqueue_script(
            'mcp-admin-script',
            MCP_PLUGIN_URL . 'assets/js/admin-script.js',
            array('jquery', 'jquery-ui-sortable', 'jquery-ui-droppable'),
            MCP_VERSION,
            true
        );
        
        // Pass data to script
        wp_localize_script('mcp-admin-script', 'mcp_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('mcp-nonce')
        ));
    }
}
add_action( 'admin_enqueue_scripts', 'mcp_admin_enqueue_scripts' );

/**
 * Enqueue scripts and styles for the frontend.
 */
function mcp_frontend_enqueue_scripts() {
    wp_enqueue_style(
        'mcp-frontend-style',
        MCP_PLUGIN_URL . 'assets/css/frontend-style.css',
        array(),
        MCP_VERSION,
        'all'
    );
}
add_action( 'wp_enqueue_scripts', 'mcp_frontend_enqueue_scripts' );
