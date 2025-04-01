<?php
if (!defined('ABSPATH')) exit;

require_once plugin_dir_path(__FILE__) . '../utility/encryption.php';

// Save API Key Function (Moved Outside)
function ai_seo_save_api_key() {
    if (isset($_POST['save_api_key']) && check_admin_referer('ai_seo_save_key')) {
        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized', 'ai-seo'));
        }

        $api_key = sanitize_text_field($_POST['api_key']);
        if (!empty($api_key)) {
            $encrypted_key = ai_seo_encrypt($api_key);
            update_option('ai_seo_api_key', $encrypted_key);
            echo '<div class="updated"><p>API Key saved successfully!</p></div>';
        }
    }
}

// Render the admin page
function render_ai_seo_auto_blog_blogging_page() {
    ai_seo_save_api_key(); // Handle form submission
    $saved_key = ai_seo_get_api_key(); // Get decrypted API key 
    ?>
    <div class="wrap">
        <h1>AI SEO - Auto Blogging</h1>
        <div>
            <!-- API Key Saving -->
            <form method="POST">
                <?php wp_nonce_field('ai_seo_save_key'); ?>
                <label for="api_key">Enter OpenAI API Key:</label>
                <input type="password" name="api_key" id="api_key" value="<?php echo esc_attr($saved_key); ?>" style="width: 100%; max-width: 400px;">
                <p><input type="submit" name="save_api_key" value="Save Key" class="button button-primary"></p>
            </form>
        </div>

        <!-- CSV Upload & Content Sections -->
        <div id="csv-section">
            <h2>1. Upload CSV of Topics</h2>
            <form id="csv-upload-form" method="post" enctype="multipart/form-data">
                <input type="file" name="csv_file" id="csv_file" accept=".csv" required />
                <button type="submit" class="button button-primary">Upload CSV</button>
            </form>
            <div id="csv-upload-response"></div>
        </div>

        <div id="topics-classification-section" style="display:none;">
            <h2>2. Classify Topics</h2>
            <button id="classify-topics" class="button">Classify Topics</button>
            <div id="classification-result"></div>
        </div>

        <div id="custom-prompts-section" style="display:none;">
            <h2>3. Set Custom Prompts</h2>
            <form id="custom-prompts-form">
                <div id="prompts-container"></div>
                <button type="submit" class="button button-primary">Save Custom Prompts</button>
            </form>
        </div>

        <div id="generate-content-section" style="display:none;">
            <h2>4. Generate Content</h2>
            <button id="generate-content" class="button button-primary">Generate Content</button>
            <div id="content-preview"></div>
        </div>
    </div>

    <style>
        #csv-section, #topics-classification-section, #custom-prompts-section, #generate-content-section {
            margin-top: 20px;
        }
    </style>

    <script>
        // JavaScript to Show Hidden Sections When Needed
        document.addEventListener("DOMContentLoaded", function() {
            document.getElementById("csv-upload-form").addEventListener("submit", function(e) {
                e.preventDefault();
                document.getElementById("topics-classification-section").style.display = "block";
            });

            document.getElementById("classify-topics").addEventListener("click", function() {
                document.getElementById("custom-prompts-section").style.display = "block";
            });

            document.getElementById("custom-prompts-form").addEventListener("submit", function(e) {
                e.preventDefault();
                document.getElementById("generate-content-section").style.display = "block";
            });
        });
    </script>
    <?php
}
