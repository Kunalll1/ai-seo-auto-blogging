<?php
if (!defined('ABSPATH')) exit;

// AJAX handler: CSV Upload
add_action('wp_ajax_upload_csv', function() {
    check_ajax_referer('ai_auto_blog_nonce', 'nonce');
    if ( ! current_user_can('manage_options') ) wp_send_json_error('Unauthorized');

    if ( ! isset($_FILES['csv_file']) ) {
        wp_send_json_error('No file uploaded');
    }

    $file = $_FILES['csv_file']['tmp_name'];
    $topics = array();
    if (($handle = fopen($file, 'r')) !== false) {
        while (($data = fgetcsv($handle)) !== false) {
            // Assume first column is topic; second column (optional) indicates type: "post" or "page"
            if (!empty($data[0])) {
                $topics[] = array(
                    'topic' => sanitize_text_field($data[0]),
                    'type'  => isset($data[1]) ? sanitize_text_field($data[1]) : 'post'
                );
            }
        }
        fclose($handle);
    }
    // Save topics in a transient (expires in 1 hour)
    set_transient('ai_auto_blog_topics', $topics, HOUR_IN_SECONDS);
    wp_send_json_success(array('message' => 'CSV uploaded successfully', 'count' => count($topics)));
});
?>