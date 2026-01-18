<?php
if (!defined('ABSPATH')) exit;

function tes_results_page() {
    global $wpdb;

    $surveys_table     = $wpdb->prefix . 'tes_surveys';
    $questions_table   = $wpdb->prefix . 'tes_questions';
    $submissions_table = $wpdb->prefix . 'tes_submissions';

    $selected_survey = isset($_GET['survey_id']) ? intval($_GET['survey_id']) : 0;
    $search_term = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';

    $query = "SELECT * FROM $surveys_table";
    if (!empty($search_term)) {
        $like = '%' . $wpdb->esc_like($search_term) . '%';
        $query .= $wpdb->prepare(" WHERE title LIKE %s", $like);
    }
    $surveys = $wpdb->get_results($query);
    ?>

    <div class="wrap">
        <h1>Survey Results Dashboard</h1>

        <!-- Search Survey -->
        <form method="get" style="margin-bottom: 15px;">
            <input type="hidden" name="page" value="tes-dashboard">
            <div style="position: relative; display: inline-block; width: 300px;">
                <input type="text" name="s" id="tes_survey_search_input" value="<?php echo esc_attr($search_term); ?>" placeholder="Search Survey Title..." style="width: 100%;" autocomplete="off">
                <div id="tes_survey_search_suggestions" style="display:none; position: absolute; top: 100%; left: 0; right: 0; background: #fff; border: 1px solid #ccd0d4; z-index: 1000; max-height: 300px; overflow-y: auto; box-shadow: 0 4px 5px rgba(0,0,0,0.1);"></div>
            </div>
            <button type="submit" class="button button-secondary">Search</button>
            <?php if (!empty($search_term)): ?>
                <a href="<?php echo admin_url('admin.php?page=tes-dashboard'); ?>" class="button">Reset</a>
            <?php endif; ?>
        </form>

        <!-- Select Survey -->
        <form method="get" style="margin-bottom:20px;">
            <input type="hidden" name="page" value="tes-dashboard">
            <?php if (!empty($search_term)): ?>
                <input type="hidden" name="s" value="<?php echo esc_attr($search_term); ?>">
            <?php endif; ?>
            <select name="survey_id" required>
                <option value="">Select Survey</option>
                <?php foreach ($surveys as $s): ?>
                    <option value="<?php echo esc_attr($s->id); ?>"
                        <?php selected($selected_survey, $s->id); ?>>
                        <?php echo esc_html($s->title); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button class="button button-primary">View Results</button>
        </form>

        <?php
        if ($selected_survey):

            // Get questions
            $all_questions = $wpdb->get_results(
                $wpdb->prepare("SELECT * FROM $questions_table WHERE survey_id = %d", $selected_survey)
            );

            // Add fixed question for results display
            $fixed_question = new stdClass();
            $fixed_question->id = 'fixed_implicit_role_model';
            $fixed_question->survey_id = $selected_survey;
            $fixed_question->question_type = 'Implicit Issues';
            $fixed_question->question_text = 'How well does the teacher model the core values through how he/she behaves with students and with other staff persons?';
            $fixed_question->sub_question_title = 'I follow the teacher as my role model ';
            $fixed_question->options = 'To much extent,All Most,Yes  ';
            if (!$all_questions) {
                $all_questions = [];
            }
            $all_questions[] = $fixed_question;

            // Get submissions
            $submissions = $wpdb->get_results(
                $wpdb->prepare("SELECT s.answers, s.student_name, s.comment
                                FROM $submissions_table s
                                WHERE s.survey_id = %d", $selected_survey)
            );

            if (!$all_questions || !$submissions) {
                echo '<p>No submissions found for this survey.</p>';
                return;
            }

            // Index questions by ID for easier lookup and to avoid repeated searches
            $questions_by_id = [];
            foreach ($all_questions as $q) {
                $questions_by_id[$q->id] = $q;
            }

            // Count answers per question and calculate averages
            $answer_counts = [];
            $averages = [];
            $student_averages = [];
            $comments = [];
            $total_sum = 0;
            $total_count = 0;
            foreach ($submissions as $sub) {
                if (!empty($sub->comment)) {
                    $comments[] = [
                        'student' => $sub->student_name,
                        'text' => $sub->comment
                    ];
                }
                $answers = maybe_unserialize($sub->answers);
                if (!is_array($answers)) continue;

                $student_sum = 0;
                $student_count = 0;
                foreach ($answers as $q_id => $answer) {
                    // Ensure the question for this answer still exists
                    if (!isset($questions_by_id[$q_id])) continue;

                    if (!isset($answer_counts[$q_id])) $answer_counts[$q_id] = [];
                    if (!isset($answer_counts[$q_id][$answer])) $answer_counts[$q_id][$answer] = 0;
                    $answer_counts[$q_id][$answer]++;

                    // Assign numerical value based on a 10-point scale.
                    $current_question = $questions_by_id[$q_id];
                    $q_options = array_map('trim', explode(',', $current_question->options));
                    $num_options = count($q_options);
                    $value = 0;
                    $index = array_search($answer, $q_options);
                    if ($index !== false && $num_options > 0) {
                        // Scale score to 5. Best option (index 0) gets 5.
                        // The value is proportional to the rank. (e.g. for 5 options, scores are 5, 4, 3, 2, 1)
                        $value = (($num_options - $index) / $num_options) * 5;
                    }
                    if ($value > 0) {
                        if (!isset($averages[$q_id])) $averages[$q_id] = ['sum' => 0, 'count' => 0];
                        $averages[$q_id]['sum'] += $value;
                        $averages[$q_id]['count']++;
                        $student_sum += $value;
                        $student_count++;
                        $total_sum += $value;
                        $total_count++;
                    }
                }
                if ($student_count > 0 && !empty($sub->student_name)) {
                    $student_key = $sub->student_name;
                    $student_averages[$student_key] = $student_sum / $student_count;
                }
            }

            // Overall average
            $overall_avg = $total_count > 0 ? $total_sum / $total_count : null;

            if ($overall_avg !== null) {
                echo '<div style="background: #f1f1f1; padding: 15px; margin-bottom: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">';
                echo '<h2 style="margin: 0; color: #333;">Overall Average Rating: ' . number_format($overall_avg, 2) . ' / 5</h2>';
                echo '</div>';
            }

            // Group questions by type
            $grouped_questions = [];
            foreach ($all_questions as $q) {
                $type = isset($q->question_type) ? $q->question_type : 'General';
                if (!isset($grouped_questions[$type])) {
                    $grouped_questions[$type] = [];
                }
                $grouped_questions[$type][] = $q;
            }

            // Define order
            $type_order = ['Explicit Issues', 'Implicit Issues'];
            foreach (array_keys($grouped_questions) as $type) {
                if (!in_array($type, $type_order)) {
                    $type_order[] = $type;
                }
            }

            // Display results
            foreach ($type_order as $type):
                if (empty($grouped_questions[$type])) continue;
                ?>
                <h2 style="margin-top: 30px; border-bottom: 2px solid #ccc; padding-bottom: 10px; color: #23282d;"><?php echo esc_html($type); ?></h2>
                <?php
                foreach ($grouped_questions[$type] as $q):
                $options = array_map('trim', explode(',', $q->options));
                $avg = isset($averages[$q->id]) && $averages[$q->id]['count'] > 0 ? $averages[$q->id]['sum'] / $averages[$q->id]['count'] : null;
                $chart_data = [];
                foreach ($options as $opt) {
                    $chart_data[] = isset($answer_counts[$q->id][$opt]) ? intval($answer_counts[$q->id][$opt]) : 0;
                }
                ?>
                <div style="margin-bottom:25px;padding:15px;border:1px solid #ddd;">
                    <strong><?php echo esc_html($q->sub_question_title ? $q->sub_question_title : $q->question_text); ?></strong>
                    <?php if ($q->sub_question_title && $q->question_text && $q->sub_question_title !== $q->question_text): ?>
                        <div style="font-size: 0.9em; color: #666; margin-top: 5px; font-style: italic;"><?php echo esc_html($q->question_text); ?></div>
                    <?php endif; ?>

                    <?php if ($avg !== null): ?>
                        <p><strong>Average Rating: <?php echo number_format($avg, 2); ?> / 5</strong></p>
                    <?php endif; ?>
                    <table class="widefat striped" style="margin-top:10px;">
                        <thead>
                            <tr><th>Option</th><th>Responses</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($options as $opt): ?>
                                <tr>
                                    <td><?php echo esc_html($opt); ?></td>
                                    <td>
                                        <?php echo isset($answer_counts[$q->id][$opt]) ? intval($answer_counts[$q->id][$opt]) : 0; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <div style="max-width: 300px; margin: 0 auto;">
                        <canvas id="chart-<?php echo $q->id; ?>"></canvas>
                    </div>
                    <script>
                    jQuery(document).ready(function($) {
                        var ctx = document.getElementById('chart-<?php echo $q->id; ?>').getContext('2d');
                        var colors = ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF', '#FF9F40', '#FF6384', '#C9CBCF'];
                        new Chart(ctx, {
                            type: 'pie',
                            data: {
                                labels: <?php echo json_encode($options); ?>,
                                datasets: [{
                                    data: <?php echo json_encode($chart_data); ?>,
                                    backgroundColor: colors.slice(0, <?php echo count($options); ?>),
                                    borderColor: colors.slice(0, <?php echo count($options); ?>),
                                    borderWidth: 1
                                }]
                            },
                            options: {
                                responsive: true,
                                plugins: {
                                    legend: {
                                        position: 'top',
                                    }
                                }
                            }
                        });
                    });
                    </script>
                </div>
            <?php endforeach; 
            endforeach; ?>

            <?php if (!empty($student_averages)): ?>
                <h2>Student-wise Average Ratings</h2>
                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th>Student Name</th>
                            <th>Average Rating</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($student_averages as $name => $avg): ?>
                            <tr>
                                <td><?php echo esc_html($name); ?></td>
                                <td><?php echo number_format($avg, 2); ?> / 5</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>

            <?php if (!empty($comments)): ?>
                <h2>Student Comments</h2>
                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th>Student Name</th>
                            <th>Comment</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($comments as $c): ?>
                            <tr>
                                <td><?php echo esc_html($c['student']); ?></td>
                                <td><?php echo nl2br(esc_html($c['text'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>

        <?php endif; ?>
    </div>

    <style>
        .tes-survey-suggestion-item {
            padding: 8px 10px;
            cursor: pointer;
            border-bottom: 1px solid #f0f0f0;
            font-size: 13px;
        }
        .tes-survey-suggestion-item:hover {
            background-color: #f0f0f1;
            color: #2271b1;
        }
        .tes-survey-suggestion-item:last-child {
            border-bottom: none;
        }
    </style>

    <script type="text/javascript">
    jQuery(document).ready(function($) {
        var searchInput = $('#tes_survey_search_input');
        var suggestionsBox = $('#tes_survey_search_suggestions');
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
                        action: 'tes_survey_search_autocomplete',
                        term: term
                    },
                    success: function(response) {
                        suggestionsBox.empty();
                        if (response.success && response.data.length > 0) {
                            var html = '';
                            $.each(response.data, function(i, item) {
                                var escapedItem = $( '<div>' ).text( item.title ).html();
                                html += '<div class="tes-survey-suggestion-item" data-id="' + item.id + '">' + escapedItem + '</div>';
                            });
                            suggestionsBox.html(html).show();
                        } else {
                            suggestionsBox.hide();
                        }
                    }
                });
            }, 300);
        });

        $(document).on('click', '.tes-survey-suggestion-item', function() {
            var surveyId = $( this ).data( 'id' );
            window.location.href = '<?php echo admin_url( 'admin.php?page=tes-dashboard' ); ?>&survey_id=' + surveyId;
        });

        $(document).on('click', function(e) {
            if (!$(e.target).closest('#tes_survey_search_input').length && !$(e.target).closest('#tes_survey_search_suggestions').length) {
                suggestionsBox.hide();
            }
        });
    });
    </script>
<?php
}
