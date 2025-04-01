<?php
if (!defined('ABSPATH')) exit;

// AJAX handler: Classify Topics using OpenAI
add_action('wp_ajax_classify_topics', function() {
    check_ajax_referer('ai_auto_blog_nonce', 'nonce');
    if ( ! current_user_can('manage_options') ) wp_send_json_error('Unauthorized');

    $topics = get_transient('ai_auto_blog_topics');
    if ( ! $topics ) {
        wp_send_json_error('No topics found. Please upload CSV first.');
    }

    $topic_names = array();
    foreach ($topics as $t) {
        $topic_names[] = $t['topic'];
    }
    $topics_text = implode("\n", $topic_names);

    // Prepare prompt for classification
    $prompt = "Classify the following topics into categories such as 'What-based', 'How-based', 'Where-based', 'When-based', 'Brief Information-based', 'List-based', etc. Return a JSON object where keys are category names and values are arrays of topics that fall under that category.\n\nTopics:\n" . $topics_text;

    $response = wp_remote_post('https://api.openai.com/v1/chat/completions', [
        'headers' => [
            'Authorization' => $saved_key,
            'Content-Type'  => 'application/json'
        ],
        'body' => json_encode([
            'model' => 'gpt-4o',
            'messages' => [
                ['role' => 'system', 'content' => 'You are a content classification assistant.'],
                ['role' => 'user', 'content' => $prompt]
            ],
            'max_tokens' => 500
        ]),
        'timeout' => 20
    ]);

    if ( is_wp_error($response) ) {
        wp_send_json_error('API error: ' . $response->get_error_message());
    }
    $http_code = wp_remote_retrieve_response_code($response);
    $body_raw = wp_remote_retrieve_body($response);
    $body = json_decode($body_raw, true);

    if ($http_code !== 200 || empty($body['choices'][0]['message']['content'])) {
        wp_send_json_error('API error: ' . $body_raw);
    }

    $classification_json = trim($body['choices'][0]['message']['content']);

    // Remove markdown formatting if present (e.g., triple backticks and optional "json")
    if (strpos($classification_json, '```') !== false) {
        $classification_json = preg_replace('/^```(?:json)?\s*/', '', $classification_json);
        $classification_json = preg_replace('/\s*```$/', '', $classification_json);
    }

    $classification = json_decode($classification_json, true);
    if (!$classification) {
        wp_send_json_error('Failed to parse classification. Response: ' . $classification_json);
    }

    // Save classification in a transient for later use
    set_transient('ai_auto_blog_classification', $classification, HOUR_IN_SECONDS);
    wp_send_json_success($classification);
});

// AJAX handler: Save Custom Prompts
add_action('wp_ajax_save_custom_prompts', function() {
    check_ajax_referer('ai_auto_blog_nonce', 'nonce');
    if ( ! current_user_can('manage_options') ) wp_send_json_error('Unauthorized');

    $custom_prompts = isset($_POST['custom_prompts']) ? $_POST['custom_prompts'] : array();
    update_option('ai_auto_blog_custom_prompts', $custom_prompts);
    wp_send_json_success('Custom prompts saved.');
});

// AJAX handler: Generate Content for Topics using corresponding custom prompts
add_action('wp_ajax_generate_content', function() {
    check_ajax_referer('ai_auto_blog_nonce', 'nonce');
    if ( ! current_user_can('manage_options') ) wp_send_json_error('Unauthorized');

    $topics = get_transient('ai_auto_blog_topics');
    $classification = get_transient('ai_auto_blog_classification');
    $custom_prompts = get_option('ai_auto_blog_custom_prompts', array());

    if ( ! $topics || ! $classification ) {
        wp_send_json_error('Topics or classification not found.');
    }

    $results = array();

    // Loop through each theme and its topics
    foreach ($classification as $theme => $theme_topics) {
        $custom_prompt = isset($custom_prompts[$theme]) ? $custom_prompts[$theme] : '';
        foreach ($theme_topics as $topic) {
            // Determine topic type (post or page) from original topics
            $type = 'post';
            foreach ($topics as $t) {
                if ($t['topic'] === $topic) {
                    $type = $t['type'];
                    break;
                }
            }
            // Build full prompt by combining custom prompt and the topic
            $full_prompt = $custom_prompt . "\n\nTopic: " . $topic;

            // Generate SEO-optimized content using OpenAI
            $response = wp_remote_post('https://api.openai.com/v1/chat/completions', [
                'headers' => [
                    'Authorization' => $saved_key,
                    'Content-Type'  => 'application/json'
                ],
                'body' => json_encode([
                    'model' => 'gpt-4o',
                    'messages' => [
                        ['role' => 'system', 'content' => 'You are an expert content writer specializing in SEO-optimized content. Format the response in valid HTML using <h1>, <h2>, <h3>, <p>, <ul>, <li>. Use <a href="mailto:"> for emails and <a href="tel:"> for phone numbers. Ensure the content is directly about the given topic.'],
                        ['role' => 'user', 'content' => $full_prompt]
                    ],
                    'max_tokens' => 1000
                ]),
                'timeout' => 20
            ]);

            if ( is_wp_error($response) ) {
                $results[] = array('topic' => $topic, 'error' => $response->get_error_message());
                continue;
            }
            $http_code = wp_remote_retrieve_response_code($response);
            $body_raw = wp_remote_retrieve_body($response);
            $body = json_decode($body_raw, true);
            if ($http_code !== 200 || empty($body['choices'][0]['message']['content'])) {
                $results[] = array('topic' => $topic, 'error' => 'API error: ' . $body_raw);
                continue;
            }
            $generated = trim($body['choices'][0]['message']['content']);
            $results[] = array(
                'topic'   => $topic,
                'type'    => $type,
                'content' => $generated
            );
        }
    }
    wp_send_json_success($results);
});
