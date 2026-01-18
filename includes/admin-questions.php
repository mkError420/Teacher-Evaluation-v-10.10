<?php
if (!defined('ABSPATH')) exit;

function tes_questions_page() {
    global $wpdb;

    $surveys_table   = $wpdb->prefix . 'tes_surveys';
    $questions_table = $wpdb->prefix . 'tes_questions';

    /* -------------------------
       ADD QUESTION (AJAX now handles this, but keep for fallback? No, remove old logic since AJAX)
    --------------------------*/
    // Removed old bulk add logic

    /* -------------------------
       DELETE QUESTION
    --------------------------*/
    if (isset($_GET['delete'])) {
        $q_id = intval($_GET['delete']);
        $survey_id = $wpdb->get_var($wpdb->prepare("SELECT survey_id FROM $questions_table WHERE id = %d", $q_id));
        
        $wpdb->delete($questions_table, ['id' => $q_id]);
        
        if ($survey_id) {
            $wpdb->update($surveys_table, ['last_updated' => current_time('mysql')], ['id' => $survey_id]);
        }
        
        echo '<div class="updated notice"><p>Question deleted.</p></div>';
    }

    /* -------------------------
       BULK DELETE QUESTIONS
    --------------------------*/
    if (isset($_POST['tes_bulk_delete_questions']) && !empty($_POST['question_ids'])) {
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'tes_bulk_delete_questions_nonce')) {
            wp_die('Security check failed');
        }
        $ids = array_map('intval', $_POST['question_ids']);
        $ids_string = implode(',', $ids);
        
        // Get survey IDs before deleting
        $survey_ids = $wpdb->get_col("SELECT DISTINCT survey_id FROM $questions_table WHERE id IN ($ids_string)");
        
        $wpdb->query("DELETE FROM $questions_table WHERE id IN ($ids_string)");
        
        if ($survey_ids) {
            $survey_ids_string = implode(',', array_map('intval', $survey_ids));
            $wpdb->query("UPDATE $surveys_table SET last_updated = NOW() WHERE id IN ($survey_ids_string)");
        }
        
        echo '<div class="updated notice"><p>' . count($ids) . ' questions deleted successfully.</p></div>';
    }

    // Handle Search
    $search_term = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';

    $surveys = $wpdb->get_results("SELECT * FROM $surveys_table");
    $questions_query = "
        SELECT q.*, s.title AS survey_title
        FROM $questions_table q
        LEFT JOIN $surveys_table s ON q.survey_id = s.id
    ";

    if (!empty($search_term)) {
        $like = '%' . $wpdb->esc_like($search_term) . '%';
        $questions_query .= $wpdb->prepare(" WHERE q.question_text LIKE %s OR q.sub_question_title LIKE %s OR s.title LIKE %s", $like, $like, $like);
    }

    $questions_query .= " ORDER BY q.id DESC";
    $questions = $wpdb->get_results($questions_query);
    ?>

    <div class="wrap">
        <h1>Survey Question Builder</h1>

        <form id="tes-add-question-form" style="max-width:800px; margin-bottom:20px;">
            <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('tes_add_question'); ?>">

            <select name="survey_id" id="survey_id" required style="width:100%;margin-bottom:10px;">
                <option value="">Select Survey</option>
                <?php foreach ($surveys as $s): ?>
                    <option value="<?php echo esc_attr($s->id); ?>">
                        <?php echo esc_html($s->title); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <select name="question_type" id="question_type" required style="width:100%;margin-bottom:10px;">
                <option value="Explicit Issues">Explicit Issues</option>
                <option value="Implicit Issues">Implicit Issues</option>
            </select>

            <input type="text" name="question_text" id="question_text" placeholder="Question Title" required style="width:100%; margin-bottom:10px; padding:8px;">
            <input type="text" name="sub_question_title" id="sub_question_title" placeholder="Sub Question Title (Optional)" style="width:100%; margin-bottom:10px; padding:8px;">

            <p class="description">Standard Answer Options (Fixed):</p>
            <div id="options-container">
                <input type="text" name="options[]" value="Never" readonly style="width:48%; margin-bottom:5px; padding:8px; background-color: #f9f9f9;">
                <input type="text" name="options[]" value="Once in a while" readonly style="width:48%; margin-bottom:5px; padding:8px; background-color: #f9f9f9;">
                <input type="text" name="options[]" value="Sometimes" readonly style="width:48%; margin-bottom:5px; padding:8px; background-color: #f9f9f9;">
                <input type="text" name="options[]" value="Most of the times" readonly style="width:48%; margin-bottom:5px; padding:8px; background-color: #f9f9f9;">
                <input type="text" name="options[]" value="Almost always" readonly style="width:48%; margin-bottom:5px; padding:8px; background-color: #f9f9f9;">
            </div>

            <button type="submit"
                    class="button button-primary"
                    style="margin-top:10px;">
                Add Question
            </button>
        </form>

        <hr>

        <h2>Existing Questions</h2>

        <form method="get" style="margin-bottom: 15px;">
            <input type="hidden" name="page" value="tes-questions">
            <div style="position: relative; display: inline-block; width: 300px;">
                <input type="text" name="s" id="tes_question_search_input" value="<?php echo esc_attr($search_term); ?>" placeholder="Search by Question or Survey" style="width: 100%;" autocomplete="off">
                <div id="tes_question_search_suggestions" style="display:none; position: absolute; top: 100%; left: 0; right: 0; background: #fff; border: 1px solid #ccd0d4; z-index: 1000; max-height: 300px; overflow-y: auto; box-shadow: 0 4px 5px rgba(0,0,0,0.1);"></div>
            </div>
            <button type="submit" class="button button-secondary">Search</button>
            <?php if (!empty($search_term)): ?>
                <a href="<?php echo admin_url('admin.php?page=tes-questions'); ?>" class="button">Reset</a>
            <?php endif; ?>
        </form>

        <form method="post">
        <?php wp_nonce_field('tes_bulk_delete_questions_nonce', '_wpnonce'); ?>
        <div class="tablenav top" style="padding: 10px 0;">
            <div class="alignleft actions">
                <button type="submit" name="tes_bulk_delete_questions" class="button button-secondary" onclick="return confirm('Are you sure you want to delete selected questions?');">Delete Selected</button>
            </div>
        </div>
        <table class="widefat striped">
            <thead>
                <tr>
                    <td id="cb" class="manage-column column-cb check-column"><input type="checkbox" id="cb-select-all-1"></td>
                    <th>Survey</th>
                    <th>Type</th>
                    <th>Question</th>
                    <th>Sub Question</th>
                    <th>Options</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($questions): foreach ($questions as $q): ?>
                    <tr>
                        <th scope="row" class="check-column"><input type="checkbox" name="question_ids[]" value="<?php echo esc_attr($q->id); ?>"></th>
                        <td><?php echo esc_html($q->survey_title); ?></td>
                        <td><?php echo esc_html($q->question_type); ?></td>
                        <td><?php echo esc_html($q->question_text); ?></td>
                        <td><?php echo esc_html($q->sub_question_title); ?></td>
                        <td><?php echo esc_html($q->options); ?></td>
                        <td>
                            <a class="button button-secondary"
                               href="?page=tes-questions&delete=<?php echo $q->id; ?>">
                               Delete
                            </a>
                        </td>
                    </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="7">No questions found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
        </form>

        <style>
            .tes-suggestion-item {
                padding: 8px 10px;
                cursor: pointer;
                border-bottom: 1px solid #f0f0f0;
                font-size: 13px;
            }
            .tes-suggestion-item:hover {
                background-color: #f0f0f1;
                color: #2271b1;
            }
            .tes-suggestion-item:last-child {
                border-bottom: none;
            }
        </style>
        <script>
        var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
        jQuery(document).ready(function($) {
            $('#tes-add-question-form').on('submit', function(e) {
                e.preventDefault();
                var formData = new FormData(this);
                formData.append('action', 'tes_add_question');

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            var data = response.data;
                            var newRow = '<tr>' +
                                '<th scope="row" class="check-column"><input type="checkbox" name="question_ids[]" value="' + data.id + '"></th>' +
                                '<td>' + data.survey_title + '</td>' +
                                '<td>' + data.question_type + '</td>' +
                                '<td>' + data.question_text + '</td>' +
                                '<td>' + (data.sub_question_title || '') + '</td>' +
                                '<td>' + data.options + '</td>' +
                                '<td><a class="button button-secondary" href="?page=tes-questions&delete=' + data.id + '">Delete</a></td>' +
                                '</tr>';
                            $('tbody').prepend(newRow);
                            $('#sub_question_title').val('');
                            $('#question_type').trigger('change');
                        } else {
                            alert(response.data);
                        }
                    },
                    error: function() {
                        alert('An error occurred.');
                    }
                });
            });

            // Question search autocomplete
            var searchInput = $('#tes_question_search_input');
            var suggestionsBox = $('#tes_question_search_suggestions');
            var timer;

            searchInput.on('input', function() {
                var term = $(this).val();
                clearTimeout(timer);
                
                if (term.length < 1) {
                    suggestionsBox.hide().empty();
                    return;
                }

                timer = setTimeout(function() {
                    $.ajax({
                        url: ajaxurl,
                        data: {
                            action: 'tes_question_search_autocomplete',
                            term: term
                        },
                        success: function(response) {
                            suggestionsBox.empty();
                            if (response.success && response.data.length > 0) {
                                $.each(response.data, function(i, item) {
                                    var escapedQuestion = $('<div>').text(item.question_text).html();
                                    var escapedSurvey = $('<div>').text(item.survey_title || 'N/A').html();

                                    var $itemDiv = $('<div class="tes-suggestion-item"></div>');
                                    $itemDiv.attr('data-value', item.question_text);
                                    $itemDiv.html('<strong>' + escapedQuestion + '</strong><br><span style="color:#666; font-size:12px;">Survey: ' + escapedSurvey + '</span>');
                                    
                                    suggestionsBox.append($itemDiv);
                                });
                                suggestionsBox.show();
                            } else {
                                suggestionsBox.hide();
                            }
                        }
                    });
                }, 300);
            });

            $(document).on('click', '#tes_question_search_suggestions .tes-suggestion-item', function() {
                var value = $(this).data('value');
                searchInput.val(value);
                suggestionsBox.hide();
                searchInput.closest('form').submit();
            });

            $(document).on('click', function(e) {
                if (!$(e.target).closest('#tes_question_search_input').length && !$(e.target).closest('#tes_question_search_suggestions').length) {
                    suggestionsBox.hide();
                }
            });

            // Select All Checkbox
            $('#cb-select-all-1').on('click', function() {
                var checked = this.checked;
                $('input[name="question_ids[]"]').prop('checked', checked);
            });

            // Handle Question Type Change
            $('#question_type').on('change', function() {
                var type = $(this).val();
                var $qText = $('#question_text');
                var $subQ = $('#sub_question_title');

                if (type === 'Explicit Issues') {
                    $qText.val('How well does the teacher teach the core subject?');
                    $subQ.prop('required', true).attr('placeholder', 'Sub Question Title (Required)');
                } else if (type === 'Implicit Issues') {
                    $qText.val('How well does the teacher model the core values through how he/she behaves with students and with other staff persons?');
                    $subQ.prop('required', true).attr('placeholder', 'Sub Question Title (Required)');
                } else {
                    if ($qText.val() === 'How well does the teacher teach the core subject?' || $qText.val() === 'How well does the teacher model the core values through how he/she behaves with students and with other staff persons?') {
                        $qText.val('');
                    }
                    $subQ.prop('required', false).attr('placeholder', 'Sub Question Title (Optional)');
                }
            });

            // Initialize state
            $('#question_type').trigger('change');
        });
        </script>
    </div>

<?php
}
