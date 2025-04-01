<?php
/*
Plugin Name: AI SEO - Auto Blogging
Description: Automatically generate SEO-optimized content for topics using OpenAI. Upload a CSV of topics, classify them into themes, set custom prompts per theme, and generate content as posts or pages.
Version: 1.0
Author: Design Omate
*/

if ( ! defined( 'ABSPATH' ) ) exit;

// Add admin menu
add_action('admin_menu', function() {
    add_menu_page('AI SEO - Auto Blogging', 'AI SEO - Auto Blogging', 'manage_options', 'ai-seo-auto-blogging', 'render_ai_seo_auto_blog_blogging_page');
});

// Enqueue scripts
add_action('admin_enqueue_scripts', function($hook) {
    if ($hook === 'toplevel_page_ai-seo-auto-blogging') {
        wp_enqueue_script('ai-seo-auto-blogging-js', plugin_dir_url(__FILE__) . 'ai-seo-auto-blogging.js', array('jquery'), null, true);
        wp_localize_script('ai-seo-auto-blogging-js', 'AIAutoBlogData', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('ai_auto_blog_nonce')
        ));
    }
});

// adding File's
if (is_admin()) {
    require_once plugin_dir_path(__FILE__) . 'admin/dash.php';
    require_once plugin_dir_path(__FILE__) . 'utility/ai.php';
    require_once plugin_dir_path(__FILE__) . 'utility/csv.php';
    require_once plugin_dir_path(__FILE__) . 'utility/content.php';
}

?>
