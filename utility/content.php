<?php
if (!defined('ABSPATH')) exit;
// AJAX handler: Approve Content and Create Post/Page
add_action('wp_ajax_approve_content', function() {
    check_ajax_referer('ai_auto_blog_nonce', 'nonce');
    if ( ! current_user_can('manage_options') ) wp_send_json_error('Unauthorized');

    $topic = sanitize_text_field($_POST['topic']);
    $content = wp_kses_post($_POST['content']);
    $type = sanitize_text_field($_POST['type']); // Expected to be 'post' or 'page'

    $post_data = array(
        'post_title'   => $topic,
        'post_name'    => sanitize_title($topic),
        'post_content' => $content,
        'post_status'  => 'publish',
        'post_type'    => ($type === 'page') ? 'page' : 'post'
    );

    $post_id = wp_insert_post($post_data);
    if ( is_wp_error($post_id) ) {
        wp_send_json_error('Failed to create content for topic: ' . $topic);
    }
    wp_send_json_success('Content for topic "' . $topic . '" published successfully.');
});