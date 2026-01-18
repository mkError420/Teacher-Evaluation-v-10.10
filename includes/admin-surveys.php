<?php
if (!defined('ABSPATH')) exit;

function tes_surveys_page() {
    global $wpdb;

    $teachers_table = $wpdb->prefix . 'tes_teachers';
    $surveys_table  = $wpdb->prefix . 'tes_surveys';

    // Add/Edit survey
    if (
        isset($_POST['tes_save_survey']) &&
        !empty($_POST['survey_title']) &&
        !empty($_POST['teacher_id'])
    ) {
        $data = [
            'title'      => sanitize_text_field(wp_unslash($_POST['survey_title'])),
            'teacher_id' => intval($_POST['teacher_id'])
        ];
        if (!empty($_POST['survey_id'])) {
            $wpdb->update($surveys_table, $data, ['id' => intval($_POST['survey_id'])]);
            echo '<div class="updated notice"><p>Survey updated successfully.</p></div>';
        } else {
            $wpdb->insert($surveys_table, $data);
            echo '<div class="updated notice"><p>Survey created successfully.</p></div>';
        }
    }

    // Delete survey
    if (isset($_GET['delete_survey'])) {
        $wpdb->delete($surveys_table, ['id' => intval($_GET['delete_survey'])]);
        echo '<div class="updated notice"><p>Survey deleted successfully.</p></div>';
    }

    // Handle Search
    $search_term = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';

    $teachers = $wpdb->get_results("SELECT * FROM $teachers_table");
    $phases = $wpdb->get_col("SELECT DISTINCT phase FROM $teachers_table WHERE phase != '' ORDER BY phase");
    $classes = $wpdb->get_col("SELECT DISTINCT class_name FROM $teachers_table WHERE class_name != '' ORDER BY class_name");
    $surveys_query = "
        SELECT s.*, t.name AS teacher_name, t.department, t.phase, t.class_name
        FROM $surveys_table s
        LEFT JOIN $teachers_table t ON s.teacher_id = t.id
    ";

    if (!empty($search_term)) {
        $like = '%' . $wpdb->esc_like($search_term) . '%';
        $surveys_query .= $wpdb->prepare(" WHERE s.title LIKE %s OR t.name LIKE %s OR t.phase LIKE %s OR t.class_name LIKE %s", $like, $like, $like, $like);
    }

    $surveys_query .= " ORDER BY s.id DESC";
    $surveys = $wpdb->get_results($surveys_query);

    $edit_survey = null;
    if (isset($_GET['edit_survey'])) {
        $edit_survey = $wpdb->get_row($wpdb->prepare("SELECT * FROM $surveys_table WHERE id = %d", intval($_GET['edit_survey'])));
    }
    ?>

    <div class="wrap">
        <h1><?php echo $edit_survey ? 'Edit' : 'Create'; ?> Teacher Survey</h1>

        <form method="post" style="margin-bottom:25px;">
            <?php if ($edit_survey): ?>
                <input type="hidden" name="survey_id" value="<?php echo esc_attr($edit_survey->id); ?>">
            <?php endif; ?>
            <input type="text"
                   name="survey_title"
                   placeholder="Survey Title"
                   required
                   value="<?php echo $edit_survey ? esc_attr($edit_survey->title) : ''; ?>"
                   style="width:300px;">

            <select id="tes_survey_phase_filter" style="margin-right: 5px;">
                <option value="">All Phases</option>
                <?php foreach ($phases as $p): ?>
                    <option value="<?php echo esc_attr($p); ?>"><?php echo esc_html($p); ?></option>
                <?php endforeach; ?>
            </select>

            <select id="tes_survey_class_filter" style="margin-right: 5px;">
                <option value="">All Classes</option>
                <?php foreach ($classes as $c): ?>
                    <option value="<?php echo esc_attr($c); ?>"><?php echo esc_html($c); ?></option>
                <?php endforeach; ?>
            </select>

            <select name="teacher_id" id="tes_survey_teacher_select" required>
                <option value="">Select Teacher</option>
                <?php foreach ($teachers as $t): ?>
                    <option value="<?php echo esc_attr($t->id); ?>"
                        data-phase="<?php echo esc_attr($t->phase); ?>"
                        data-class="<?php echo esc_attr($t->class_name); ?>"
                        <?php selected($edit_survey ? $edit_survey->teacher_id : '', $t->id); ?>>
                        <?php echo esc_html($t->name . ' (' . $t->department . (!empty($t->phase) ? ' - ' . $t->phase : '') . (!empty($t->class_name) ? ' - ' . $t->class_name : '') . ')'); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <button type="submit"
                    name="tes_save_survey"
                    class="button button-primary">
                <?php echo $edit_survey ? 'Update' : 'Add'; ?> Survey
            </button>
            <?php if ($edit_survey): ?>
                <a href="?page=tes-surveys" class="button">Cancel</a>
            <?php endif; ?>
        </form>

        <h2>Existing Surveys</h2>

        <form method="get" style="margin-bottom: 15px;">
            <input type="hidden" name="page" value="tes-surveys">
            <div style="position: relative; display: inline-block; width: 300px;">
                <input type="text" name="s" id="tes_survey_search_input" value="<?php echo esc_attr($search_term); ?>" placeholder="Search by Survey, Teacher, Phase or Class" style="width: 100%;" autocomplete="off">
                <div id="tes_survey_search_suggestions" style="display:none; position: absolute; top: 100%; left: 0; right: 0; background: #fff; border: 1px solid #ccd0d4; z-index: 1000; max-height: 300px; overflow-y: auto; box-shadow: 0 4px 5px rgba(0,0,0,0.1);"></div>
            </div>
            <button type="submit" class="button button-secondary">Search</button>
            <?php if (!empty($search_term)): ?>
                <a href="<?php echo admin_url('admin.php?page=tes-surveys'); ?>" class="button">Reset</a>
            <?php endif; ?>
        </form>

        <table class="widefat striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Survey Title</th>
                    <th>Teacher</th>
                    <th>Phase</th>
                    <th>Class</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($surveys): foreach ($surveys as $s): ?>
                    <tr>
                        <td><?php echo esc_html($s->id); ?></td>
                        <td><?php echo esc_html($s->title); ?></td>
                        <td><?php echo esc_html($s->teacher_name . ' (' . $s->department . ')'); ?></td>
                        <td><?php echo esc_html($s->phase); ?></td>
                        <td><?php echo esc_html($s->class_name); ?></td>
                        <td>
                            <a href="?page=tes-surveys&edit_survey=<?php echo $s->id; ?>" class="button button-secondary">Edit</a>
                            <a href="?page=tes-surveys&delete_survey=<?php echo $s->id; ?>" class="button button-secondary" onclick="return confirm('Are you sure?')">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; else: ?>
                    <tr>
                        <td colspan="4">No surveys created yet.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

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
                            $.each(response.data, function(i, item) {
                                var escapedTitle = $('<div>').text(item.title).html();
                                
                                var $itemDiv = $('<div class="tes-suggestion-item"></div>');
                                $itemDiv.attr('data-value', item.title);
                                $itemDiv.html('<strong>' + escapedTitle + '</strong><br><span style="color:#666; font-size:12px;">' + (item.teacher_name || 'No Teacher') + (item.phase ? ' (' + item.phase + ')' : '') + (item.class_name ? ' - ' + item.class_name : '') + '</span>');
                                
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

        $(document).on('click', '#tes_survey_search_suggestions .tes-suggestion-item', function() {
            var value = $(this).data('value');
            searchInput.val(value);
            suggestionsBox.hide();
            searchInput.closest('form').submit();
        });

        $(document).on('click', function(e) {
            if (!$(e.target).closest('#tes_survey_search_input').length && !$(e.target).closest('#tes_survey_search_suggestions').length) {
                suggestionsBox.hide();
            }
        });

        // Filter teachers by phase
        $('#tes_survey_phase_filter, #tes_survey_class_filter').on('change', function() {
            var phase = $('#tes_survey_phase_filter').val();
            var className = $('#tes_survey_class_filter').val();
            var $teacherSelect = $('#tes_survey_teacher_select');
            
            $teacherSelect.find('option[value!=""]').each(function() {
                var $opt = $(this);
                var optPhase = $opt.data('phase');
                var optClass = $opt.data('class');
                
                var matchPhase = !phase || optPhase == phase;
                var matchClass = !className || optClass == className;
                
                if (matchPhase && matchClass) {
                    $opt.show();
                } else {
                    $opt.hide();
                }
            });
            
            // Reset selection if the currently selected teacher is hidden
            var selectedOption = $teacherSelect.find('option:selected');
            if (selectedOption.css('display') === 'none') {
                $teacherSelect.val('');
            }
        });
    });
    </script>
<?php
}
