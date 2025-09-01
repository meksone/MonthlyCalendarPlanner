<?php
/**
 * Admin Actions for Monthly Calendar Planner (Duplicate Post, Delete Revisions)
 * @version 1.0.8
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Add custom links to the post row actions.
 *
 * @param array   $actions The existing actions.
 * @param WP_Post $post    The current post object.
 * @return array The modified actions.
 */
function mk_mcp_add_row_actions($actions, $post) {
    if (in_array($post->post_type, ['monthly_calendar', 'mcp_template'])) {
        // Duplicate Link
        $dup_url = wp_nonce_url(admin_url('admin.php?action=mk_mcp_duplicate_post&post=' . $post->ID), 'mk_mcp_duplicate_post_nonce', 'mk_mcp_nonce');
        $actions['duplicate'] = '<a href="' . esc_url($dup_url) . '" title="' . esc_attr__('Duplicate this item', 'mk-monthly-calendar-planner') . '">' . __('Duplicate', 'mk-monthly-calendar-planner') . '</a>';

        // Delete Revisions Link
        if (wp_revisions_enabled($post) && wp_get_post_revisions($post->ID)) {
            $del_rev_url = wp_nonce_url(admin_url('admin.php?action=mk_mcp_delete_revisions&post=' . $post->ID), 'mk_mcp_delete_revisions_nonce', 'mk_mcp_nonce');
            $actions['delete_revisions'] = '<a href="' . esc_url($del_rev_url) . '" title="' . esc_attr__('Delete all revisions for this item', 'mk-monthly-calendar-planner') . '" onclick="return confirm(\'' . esc_js(__('Are you sure you want to permanently delete all revisions for this item?', 'mk-monthly-calendar-planner')) . '\');">' . __('Delete Revisions', 'mk-monthly-calendar-planner') . '</a>';
        }
    }
    return $actions;
}
add_filter('post_row_actions', 'mk_mcp_add_row_actions', 10, 2);
add_filter('page_row_actions', 'mk_mcp_add_row_actions', 10, 2);

/**
 * Handle the duplicate post action.
 */
function mk_mcp_handle_duplicate_post_action() {
    if (!isset($_GET['post']) || !isset($_GET['mk_mcp_nonce'])) { return; }
    if (!wp_verify_nonce($_GET['mk_mcp_nonce'], 'mk_mcp_duplicate_post_nonce')) { wp_die(__('Security check failed.', 'mk-monthly-calendar-planner')); }

    $post_id = absint($_GET['post']);
    if (!current_user_can('edit_post', $post_id)) { wp_die(__('You do not have permission to duplicate this item.', 'mk-monthly-calendar-planner')); }

    $post = get_post($post_id);
    if ($post) {
        $new_post = ['post_author' => get_current_user_id(), 'post_content' => $post->post_content, 'post_title' => sprintf(__('Copy of %s', 'mk-monthly-calendar-planner'), $post->post_title), 'post_excerpt' => $post->post_excerpt, 'post_status' => 'draft', 'post_type' => $post->post_type, 'comment_status' => $post->comment_status, 'ping_status' => $post->ping_status, 'post_password' => $post->post_password, 'post_name' => $post->post_name . '-copy', 'post_parent' => $post->post_parent, 'menu_order' => $post->menu_order];
        $new_post_id = wp_insert_post($new_post);

        if ($new_post_id && !is_wp_error($new_post_id)) {
            $meta_keys = get_post_custom_keys($post_id);
            if ($meta_keys) {
                foreach ($meta_keys as $meta_key) {
                    $meta_values = get_post_custom_values($meta_key, $post_id);
                    foreach ($meta_values as $meta_value) { add_post_meta($new_post_id, $meta_key, $meta_value); }
                }
            }
        }
        wp_redirect(add_query_arg('mk-mcp-duplicated', '1', admin_url('edit.php?post_type=' . $post->post_type)));
        exit;
    }
}
add_action('admin_action_mk_mcp_duplicate_post', 'mk_mcp_handle_duplicate_post_action');


/**
 * Handle the delete revisions action.
 */
function mk_mcp_handle_delete_revisions_action() {
    if (!isset($_GET['post']) || !isset($_GET['mk_mcp_nonce'])) { return; }
    if (!wp_verify_nonce($_GET['mk_mcp_nonce'], 'mk_mcp_delete_revisions_nonce')) { wp_die(__('Security check failed.', 'mk-monthly-calendar-planner')); }

    $post_id = absint($_GET['post']);
    if (!current_user_can('delete_post', $post_id)) { wp_die(__('You do not have permission to delete revisions for this item.', 'mk-monthly-calendar-planner')); }

    $post = get_post($post_id);
    if ($post) {
        $revisions = wp_get_post_revisions($post_id);
        foreach ($revisions as $revision) {
            wp_delete_post_revision($revision->ID);
        }
        wp_redirect(add_query_arg('mk-mcp-revisions-deleted', '1', admin_url('edit.php?post_type=' . $post->post_type)));
        exit;
    }
}
add_action('admin_action_mk_mcp_delete_revisions', 'mk_mcp_handle_delete_revisions_action');


/**
 * Display admin notices for our custom actions.
 */
function mk_mcp_show_admin_notices() {
    if (isset($_GET['mk-mcp-duplicated']) && $_GET['mk-mcp-duplicated'] == '1') {
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Item duplicated successfully.', 'mk-monthly-calendar-planner') . '</p></div>';
    }
    if (isset($_GET['mk-mcp-revisions-deleted']) && $_GET['mk-mcp-revisions-deleted'] == '1') {
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Revisions deleted successfully.', 'mk-monthly-calendar-planner') . '</p></div>';
    }
}
add_action('admin_notices', 'mk_mcp_show_admin_notices');
