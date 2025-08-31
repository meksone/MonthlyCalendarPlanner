<?php
/**
 * Item Templates for Monthly Calendar Planner
 * @version 1.0.3
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Register the 'mcp_template' Custom Post Type.
 */
function mk_mcp_register_template_cpt() {
    $labels = [
        'name'          => _x('Templates', 'Post Type General Name', 'mk-monthly-calendar-planner'),
        'singular_name' => _x('Template', 'Post Type Singular Name', 'mk-monthly-calendar-planner'),
        'menu_name'     => __('Templates', 'mk-monthly-calendar-planner'),
        'add_new_item'  => __('Add New Template', 'mk-monthly-calendar-planner'),
        'edit_item'     => __('Edit Template', 'mk-monthly-calendar-planner'),
        'new_item'      => __('New Template', 'mk-monthly-calendar-planner'),
        'view_item'     => __('View Template', 'mk-monthly-calendar-planner'),
        'search_items'  => __('Search Templates', 'mk-monthly-calendar-planner'),
        'not_found'     => __('No templates found', 'mk-monthly-calendar-planner'),
    ];
    $args = [
        'label'         => __('Template', 'mk-monthly-calendar-planner'),
        'labels'        => $labels,
        'supports'      => ['title'],
        'hierarchical'  => false,
        'public'        => false,
        'show_ui'       => true,
        'show_in_menu'  => 'edit.php?post_type=monthly_calendar', // Add as submenu
        'menu_icon'     => 'dashicons-plus-alt',
        'can_export'    => true,
    ];
    register_post_type('mcp_template', $args);
}
add_action('init', 'mk_mcp_register_template_cpt', 0);


/**
 * Add meta box to the 'mcp_template' post type.
 */
function mk_mcp_add_template_meta_boxes() {
    add_meta_box(
        'mk_mcp_template_data',
        __('Template Item Data', 'mk-monthly-calendar-planner'),
        'mk_mcp_render_template_meta_box_content',
        'mcp_template',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes_mcp_template', 'mk_mcp_add_template_meta_boxes');

/**
 * Render the content of the template meta box.
 */
function mk_mcp_render_template_meta_box_content($post) {
    wp_nonce_field('mk_mcp_save_template_meta_box_data', 'mk_mcp_template_meta_box_nonce');
    $item_title = get_post_meta($post->ID, '_mk_mcp_item_title', true);
    $item_text = get_post_meta($post->ID, '_mk_mcp_item_text', true);
    ?>
    <p>
        <em><?php _e('The main title above is for backend identification only.', 'mk-monthly-calendar-planner'); ?></em>
    </p>
    <p>
        <label for="mk_mcp_item_title"><strong><?php _e('Item Title', 'mk-monthly-calendar-planner'); ?></strong></label><br>
        <input type="text" id="mk_mcp_item_title" name="mk_mcp_item_title" value="<?php echo esc_attr($item_title); ?>" style="width:100%;" />
        <span class="description"><?php _e('This is the title that will be pre-populated when you use the template.', 'mk-monthly-calendar-planner'); ?></span>
    </p>
    <p>
        <label for="mk_mcp_item_text"><strong><?php _e('Item Text', 'mk-monthly-calendar-planner'); ?></strong></label><br>
        <textarea id="mk_mcp_item_text" name="mk_mcp_item_text" rows="5" style="width:100%;"><?php echo esc_textarea($item_text); ?></textarea>
        <span class="description"><?php _e('This is the text content that will be pre-populated.', 'mk-monthly-calendar-planner'); ?></span>
    </p>
    <?php
}

/**
 * Save template meta box data.
 */
function mk_mcp_save_template_meta_box_data($post_id) {
    if (!isset($_POST['mk_mcp_template_meta_box_nonce']) || !wp_verify_nonce($_POST['mk_mcp_template_meta_box_nonce'], 'mk_mcp_save_template_meta_box_data')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (isset($_POST['post_type']) && 'mcp_template' == $_POST['post_type'] && !current_user_can('edit_post', $post_id)) return;

    if (isset($_POST['mk_mcp_item_title'])) {
        update_post_meta($post_id, '_mk_mcp_item_title', sanitize_text_field($_POST['mk_mcp_item_title']));
    }
    if (isset($_POST['mk_mcp_item_text'])) {
        update_post_meta($post_id, '_mk_mcp_item_text', sanitize_textarea_field($_POST['mk_mcp_item_text']));
    }
}
add_action('save_post', 'mk_mcp_save_template_meta_box_data');

