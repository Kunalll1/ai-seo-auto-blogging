jQuery(document).ready(function($) {
    // CSV Upload Handler
    $('#csv-upload-form').on('submit', function(e) {
        e.preventDefault();
        var formData = new FormData(this);
        formData.append('action', 'upload_csv');
        formData.append('nonce', AIAutoBlogData.nonce);
        $.ajax({
            url: AIAutoBlogData.ajax_url,
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if(response.success) {
                    $('#csv-upload-response').html('<p>' + response.data.message + ' (Topics count: ' + response.data.count + ')</p>');
                    $('#topics-classification-section').show();
                } else {
                    $('#csv-upload-response').html('<p style="color:red;">Error: ' + response.data + '</p>');
                }
            },
            error: function(xhr, status, error) {
                $('#csv-upload-response').html('<p style="color:red;">Error: ' + error + '</p>');
            }
        });
    });
    
    // Classify Topics Handler
    $('#classify-topics').on('click', function() {
        $('#classification-result').html('<p>Classifying topics, please wait...</p>');
        $.post(AIAutoBlogData.ajax_url, {
            action: 'classify_topics',
            nonce: AIAutoBlogData.nonce
        }, function(response) {
            if(response.success) {
                var classification = response.data;
                // Display classification result (for debugging / confirmation)
                var html = '<pre>' + JSON.stringify(classification, null, 2) + '</pre>';
                $('#classification-result').html(html);
                
                // Dynamically generate custom prompt boxes based on themes
                var promptsHtml = '';
                for(var theme in classification) {
                    promptsHtml += '<h3>Custom Prompt for "' + theme + '"</h3>';
                    promptsHtml += '<textarea name="prompt[' + theme + ']" rows="3" style="width:100%;"></textarea>';
                }
                $('#prompts-container').html(promptsHtml);
                $('#custom-prompts-section').show();
            } else {
                $('#classification-result').html('<p style="color:red;">Error: ' + response.data + '</p>');
            }
        });
    });
    
    // Save Custom Prompts Handler
    $('#custom-prompts-form').on('submit', function(e) {
        e.preventDefault();
        var customPrompts = {};
        $('#prompts-container textarea').each(function() {
            // Extract theme from name attribute: e.g., prompt[What-based] returns What-based
            var theme = $(this).attr('name').match(/\[(.*?)\]/)[1];
            var prompt = $(this).val();
            customPrompts[theme] = prompt;
        });
        $.post(AIAutoBlogData.ajax_url, {
            action: 'save_custom_prompts',
            nonce: AIAutoBlogData.nonce,
            custom_prompts: customPrompts
        }, function(response) {
            if(response.success) {
                alert('Custom prompts saved successfully.');
                $('#generate-content-section').show();
            } else {
                alert('Error: ' + response.data);
            }
        });
    });
    
    // Generate Content Handler
    $('#generate-content').on('click', function() {
        $('#content-preview').html('<p>Generating content for topics, please wait...</p>');
        $.post(AIAutoBlogData.ajax_url, {
            action: 'generate_content',
            nonce: AIAutoBlogData.nonce
        }, function(response) {
            if(response.success) {
                var results = response.data;
                var html = '';
                results.forEach(function(result) {
                    html += '<div class="topic-content" data-topic="'+result.topic+'">';
                    html += '<h3>' + result.topic + ' (' + result.type + ')</h3>';
                    if(result.error) {
                        html += '<p style="color:red;">Error: ' + result.error + '</p>';
                    } else {
                        html += result.content;
                        html += '<br><button class="button approve-content" data-topic="'+result.topic+'" data-type="'+result.type+'">Approve & Publish</button>';
                    }
                    html += '</div><hr>';
                });
                $('#content-preview').html(html);
            } else {
                $('#content-preview').html('<p style="color:red;">Error: ' + response.data + '</p>');
            }
        });
    });
    
    // Approve Content Handler
    $('#content-preview').on('click', '.approve-content', function() {
        var button = $(this);
        var topic = button.data('topic');
        var type = button.data('type');
        // For approval, we'll extract the content from the parent container
        var content = button.closest('.topic-content').html();
        $.post(AIAutoBlogData.ajax_url, {
            action: 'approve_content',
            nonce: AIAutoBlogData.nonce,
            topic: topic,
            type: type,
            content: content
        }, function(response) {
            if(response.success) {
                alert('Content for "' + topic + '" published successfully.');
                button.prop('disabled', true).text('Published');
            } else {
                alert('Error: ' + response.data);
            }
        });
    });
});
