<?php
if (!defined('ABSPATH')) exit;

/**
 * Students Management Page
 */
function tes_students_page() {
    global $wpdb;
    $students_table = $wpdb->prefix . 'tes_students';

    // Add new student
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_student') {
        if (!isset($_POST['tes_nonce']) || !wp_verify_nonce($_POST['tes_nonce'], 'tes_add_student')) {
            wp_die('Security check failed');
        }

        if (isset($_POST['first_name'])) {
            $first_name = sanitize_text_field($_POST['first_name']);
            $last_name = isset($_POST['last_name']) ? sanitize_text_field($_POST['last_name']) : '';
            $student_name = trim($first_name . ' ' . $last_name);
            if (!empty($last_name)) {
                $username = strtolower($last_name);
            } else {
                $username = strtolower($first_name);
            }
        } else {
            $student_name = sanitize_text_field($_POST['student_name']);
            $username = $student_name;
        }

        $class_name = sanitize_text_field($_POST['class_name']);
        if ($class_name === 'new_class') {
            $class_name = sanitize_text_field($_POST['new_class']);
        }
        $session = sanitize_text_field($_POST['session']);
        $batch_name = sanitize_text_field($_POST['batch_name']);
        $phase = sanitize_text_field($_POST['phase']);
        $roll = sanitize_text_field($_POST['roll']);

        // Auto-set Username and Password from Name and Roll
        $password = $roll;

        // Validate
        if (empty($student_name) || empty($class_name) || empty($roll)) {
            echo '<div class="notice notice-error"><p>Student Name, Class and Roll are required.</p></div>';
        } else {
            // Check if username exists
            $existing = $wpdb->get_row($wpdb->prepare("SELECT id FROM $students_table WHERE username = %s", $username));
            if ($existing) {
                echo '<div class="notice notice-error"><p>Username already exists. Please choose another.</p></div>';
            } else {
                // Load WordPress security functions
                require_once(ABSPATH . 'wp-includes/pluggable.php');
                
                // Hash password securely
                // $hashed_password = wp_hash_password($password);
                $hashed_password = $password; // Store plain text per request
                
                // Insert student into database
                $result = $wpdb->insert(
                    $students_table,
                    [
                        'student_name' => $student_name,
                        'username' => $username,
                        'password' => $hashed_password,
                        'class_name' => $class_name,
                        'session' => $session,
                        'batch_name' => $batch_name,
                        'phase' => $phase,
                        'roll' => $roll
                    ],
                    ['%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s']
                );

                if ($result) {
                    echo '<div class="notice notice-success"><p>Student added successfully!</p></div>';
                } else {
                    echo '<div class="notice notice-error"><p>Error adding student: ' . esc_html($wpdb->last_error) . '</p></div>';
                }
            }
        }
    }

    // Update student
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_student') {
        if (!isset($_POST['tes_nonce']) || !wp_verify_nonce($_POST['tes_nonce'], 'tes_add_student')) {
            wp_die('Security check failed');
        }

        $student_id = intval($_POST['student_id']);
        $student_name = sanitize_text_field($_POST['student_name']);
        $username = sanitize_text_field($_POST['username']);
        $password = sanitize_text_field($_POST['password']);
        $class_name = sanitize_text_field($_POST['class_name']);
        if ($class_name === 'new_class') {
            $class_name = sanitize_text_field($_POST['new_class']);
        }
        $session = sanitize_text_field($_POST['session']);
        $batch_name = sanitize_text_field($_POST['batch_name']);
        $phase = sanitize_text_field($_POST['phase']);
        $roll = sanitize_text_field($_POST['roll']);

        if (empty($student_name) || empty($username) || empty($class_name)) {
            echo '<div class="notice notice-error"><p>Name, Username and Class are required.</p></div>';
        } else {
            // Check if username exists for other students
            $existing = $wpdb->get_row($wpdb->prepare("SELECT id FROM $students_table WHERE username = %s AND id != %d", $username, $student_id));
            if ($existing) {
                echo '<div class="notice notice-error"><p>Username already exists. Please choose another.</p></div>';
            } else {
                $data = [
                    'student_name' => $student_name,
                    'username' => $username,
                    'class_name' => $class_name,
                    'session' => $session,
                    'batch_name' => $batch_name,
                    'phase' => $phase,
                    'roll' => $roll
                ];

                // Only update password if provided
                if (!empty($password)) {
                    // require_once(ABSPATH . 'wp-includes/pluggable.php');
                    // $data['password'] = wp_hash_password($password);
                    $data['password'] = $password; // Store plain text per request
                }

                $wpdb->update($students_table, $data, ['id' => $student_id]);
                echo '<div class="notice notice-success"><p>Student updated successfully!</p></div>';
                $_GET['action'] = ''; // Reset action to clear edit form
            }
        }
    }

    // Delete student
    if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['student_id'])) {
        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'tes_delete_student')) {
            wp_die('Security check failed');
        }

        $student_id = intval($_GET['student_id']);
        $wpdb->delete($students_table, ['id' => $student_id], ['%d']);
        echo '<div class="notice notice-success"><p>Student deleted successfully!</p></div>';
    }

    // Bulk Delete Students
    if (isset($_POST['tes_bulk_action']) && $_POST['tes_bulk_action'] === 'delete' && !empty($_POST['student_ids'])) {
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'tes_bulk_delete_students')) {
            wp_die('Security check failed');
        }
        $ids = array_map('intval', $_POST['student_ids']);
        if (!empty($ids)) {
            $ids_string = implode(',', $ids);
            $wpdb->query("DELETE FROM $students_table WHERE id IN ($ids_string)");
            echo '<div class="notice notice-success"><p>' . count($ids) . ' students deleted successfully!</p></div>';
        }
    }

    // Get student for editing
    $edit_student = null;
    if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['student_id'])) {
        $edit_student = $wpdb->get_row($wpdb->prepare("SELECT * FROM $students_table WHERE id = %d", intval($_GET['student_id'])));
    }

    // Handle Search and Get Students
    $search_term = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
    $sql = "SELECT * FROM $students_table";

    if (!empty($search_term)) {
        $like = '%' . $wpdb->esc_like($search_term) . '%';
        $sql .= $wpdb->prepare(" WHERE student_name LIKE %s OR username LIKE %s OR class_name LIKE %s", $like, $like, $like);
    }

    $sql .= " ORDER BY created_at DESC";
    $students = $wpdb->get_results($sql);

    ?>

    <div class="wrap">
        <h1>Manage Students</h1>

        <!-- Add Student Form -->
        <div class="card" style="margin-bottom: 20px;">
            <h2><?php echo $edit_student ? 'Edit Student' : 'Add New Student'; ?></h2>
            <form method="POST" class="tes-add-student-form">
                <?php wp_nonce_field('tes_add_student', 'tes_nonce'); ?>
                <input type="hidden" name="action" value="<?php echo $edit_student ? 'update_student' : 'add_student'; ?>">
                <?php if ($edit_student): ?>
                    <input type="hidden" name="student_id" value="<?php echo esc_attr($edit_student->id); ?>">
                <?php endif; ?>

                <style>
                    .tes-horizontal-form {
                        display: flex;
                        flex-wrap: wrap;
                        gap: 15px;
                        align-items: flex-start;
                    }
                    .tes-form-group {
                        display: flex;
                        flex-direction: column;
                        margin-bottom: 10px;
                    }
                    .tes-form-group label {
                        font-weight: 600;
                        margin-bottom: 5px;
                        font-size: 12px;
                    }
                    .tes-form-group input[type="text"], 
                    .tes-form-group select {
                        width: 180px !important;
                        padding: 6px;
                        border: 1px solid #ccc;
                        border-radius: 4px;
                    }
                    .tes-form-group .description {
                        font-size: 11px;
                        color: #666;
                        margin: 2px 0 0;
                        max-width: 180px;
                    }
                    .tes-form-actions {
                        display: flex;
                        align-items: flex-end;
                        margin-bottom: 10px;
                        height: 60px; /* Align with inputs */
                        
                    }
                </style>

                <div class="tes-horizontal-form">
                    <?php if ($edit_student): ?>
                    <div class="tes-form-group">
                        <label for="student_name">Student Name *</label>
                        <input type="text" id="student_name" name="student_name" required
                               value="<?php echo esc_attr($edit_student->student_name); ?>">
                    </div>
                    <?php else: ?>
                    <div class="tes-form-group">
                        <label for="first_name">First Name *</label>
                        <input type="text" id="first_name" name="first_name" required>
                    </div>
                    <div class="tes-form-group">
                        <label for="last_name">Last Name</label>
                        <input type="text" id="last_name" name="last_name">
                    </div>
                    <?php endif; ?>

                    <?php if ($edit_student): ?>
                    <div class="tes-form-group">
                        <label for="username">Username *</label>
                        <input type="text" id="username" name="username" required
                               value="<?php echo $edit_student ? esc_attr($edit_student->username) : ''; ?>">
                        <p class="description">Must be unique</p>
                    </div>

                    <div class="tes-form-group">
                        <label for="password">Password <?php echo $edit_student ? '(Blank to keep)' : '*'; ?></label>
                        <?php
                        $pass_val = '';
                        if ($edit_student && strpos($edit_student->password, '$P$') !== 0 && strpos($edit_student->password, '$H$') !== 0) {
                            $pass_val = $edit_student->password;
                        }
                        ?>
                        <input type="text" id="password" name="password" <?php echo $edit_student ? '' : 'required'; ?> 
                               value="<?php echo esc_attr($pass_val); ?>">
                    </div>
                    <?php endif; ?>

                    <div class="tes-form-group">
                        <label for="class_name">Class *</label>
                        <select id="class_name" name="class_name" required onchange="toggleNewClass(this)">
                            <option value="">Select Class</option>
                            <?php 
                                $classes = $wpdb->get_col("
                                    SELECT DISTINCT class_name FROM (
                                        SELECT class_name FROM {$wpdb->prefix}tes_teachers WHERE class_name != ''
                                        UNION
                                        SELECT class_name FROM {$wpdb->prefix}tes_students WHERE class_name != ''
                                    ) AS combined_classes ORDER BY class_name
                                ");
                                foreach ($classes as $cls) {
                                    $selected = ($edit_student && $edit_student->class_name === $cls) ? 'selected' : '';
                                    echo '<option value="' . esc_attr($cls) . '" ' . $selected . '>' . esc_html($cls) . '</option>';
                                }
                            ?>
                            <option value="new_class">+ Add New Class</option>
                        </select>
                        <input type="text" id="new_class" name="new_class" placeholder="New Class Name"
                               style="display: none; margin-top: 5px;">
                        <script>
                            function toggleNewClass(select) {
                                var input = document.getElementById('new_class');
                                if (select.value === 'new_class') {
                                    input.style.display = 'block';
                                    input.required = true;
                                } else {
                                    input.style.display = 'none';
                                    input.required = false;
                                }
                            }
                        </script>
                    </div>

                    <div class="tes-form-group">
                        <label for="session">Session</label>
                        <input type="text" id="session" name="session" value="<?php echo $edit_student ? esc_attr($edit_student->session) : ''; ?>">
                    </div>

                    <div class="tes-form-group">
                        <label for="batch_name">Batch Name</label>
                        <input type="text" id="batch_name" name="batch_name" value="<?php echo $edit_student ? esc_attr($edit_student->batch_name) : ''; ?>">
                    </div>

                    <div class="tes-form-group">
                        <label for="phase">Phase</label>
                        <input type="text" id="phase" name="phase" value="<?php echo $edit_student ? esc_attr($edit_student->phase) : ''; ?>">
                    </div>

                    <div class="tes-form-group">
                        <label for="roll">Roll</label>
                        <input type="text" id="roll" name="roll" required value="<?php echo $edit_student ? esc_attr($edit_student->roll) : ''; ?>">
                    </div>

                    <div class="tes-form-actions">
                        <button type="submit" class="button button-primary"><?php echo $edit_student ? 'Update' : 'Add New Student'; ?></button>
                        <?php if ($edit_student): ?>
                            <a href="<?php echo admin_url('admin.php?page=tes-students'); ?>" class="button" style="margin-left: 5px;">Cancel</a>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>

        <!-- Import Students Form -->
        <div class="card" style="margin-bottom: 20px;">
            <h3>Import Students from CSV</h3>
            <p class="description">
                Upload a CSV file with headers matching the database columns.<br>
                <strong>Allowed Columns:</strong> first_name, last_name, class_name, session, batch_name, phase, roll.
            </p>
            
            <form id="tes-import-form" enctype="multipart/form-data">
                <input type="file" name="import_file" accept=".csv" required>
                <button type="submit" class="button button-primary">Import Students</button>
                <span class="spinner" style="float: none; margin-top: 0;"></span>
            </form>
            
            <div id="tes-import-result" style="margin-top: 10px; font-weight: bold;"></div>
        </div>

        <!-- Students List -->
        <div class="card">
            <h2>Students List</h2>

            <form method="get" style="margin-bottom: 15px;">
                <input type="hidden" name="page" value="tes-students">
                <div style="position: relative; display: inline-block; width: 300px;">
                    <input type="text" name="s" id="tes_student_search_input" value="<?php echo esc_attr($search_term); ?>" placeholder="Search by Name, Username or Class" style="width: 100%;" autocomplete="off">
                    <div id="tes_search_suggestions" style="display:none; position: absolute; top: 100%; left: 0; right: 0; background: #fff; border: 1px solid #ccd0d4; z-index: 1000; max-height: 300px; overflow-y: auto; box-shadow: 0 4px 5px rgba(0,0,0,0.1);"></div>
                </div>
                <button type="submit" class="button button-secondary">Search</button>
                <?php if (!empty($search_term)): ?>
                    <a href="<?php echo admin_url('admin.php?page=tes-students'); ?>" class="button">Reset</a>
                <?php endif; ?>
            </form>

            <?php if ($students): ?>
                <form method="post">
                <?php wp_nonce_field('tes_bulk_delete_students'); ?>
                <div class="tablenav top" style="padding: 10px 0;">
                    <div class="alignleft actions">
                        <button type="submit" name="tes_bulk_action" value="delete" class="button button-secondary" onclick="return confirm('Are you sure you want to delete the selected students?');">Delete Selected</button>
                    </div>
                </div>

                <table class="widefat striped">
                    <thead>
                        <tr>
                            <td id="cb" class="manage-column column-cb check-column"><input type="checkbox" id="cb-select-all-1"></td>
                            <th>Student Name</th>
                            <th>Username</th>
                            <th>Password</th>
                            <th>Class</th>
                            <th>Session</th>
                            <th>Batch</th>
                            <th>Phase</th>
                            <th>Roll</th>
                            <th>Created Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $student): ?>
                            <tr>
                                <th scope="row" class="check-column"><input type="checkbox" name="student_ids[]" value="<?php echo esc_attr($student->id); ?>"></th>
                                <td><?php echo esc_html($student->student_name); ?></td>
                                <td><code><?php echo esc_html($student->username); ?></code></td>
                                <td><code><?php echo (strpos($student->password, '$P$') === 0 || strpos($student->password, '$H$') === 0) ? '(Hidden/Hashed)' : esc_html($student->password); ?></code></td>
                                <td><?php echo esc_html($student->class_name); ?></td>
                                <td><?php echo esc_html($student->session); ?></td>
                                <td><?php echo esc_html($student->batch_name); ?></td>
                                <td><?php echo esc_html($student->phase); ?></td>
                                <td><?php echo esc_html($student->roll); ?></td>
                                <td><?php echo esc_html($student->created_at); ?></td>
                                <td>
                                    <a href="<?php echo admin_url('admin.php?page=tes-students&action=edit&student_id=' . $student->id); ?>" 
                                       class="button button-secondary" style="margin-right: 5px;">
                                        Edit
                                    </a>
                                    <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=tes-students&action=delete&student_id=' . $student->id), 'tes_delete_student'); ?>" 
                                       class="button button-danger" 
                                       onclick="return confirm('Are you sure you want to delete this student?');">
                                        Delete
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                </form>
            <?php else: ?>
                <p>No students found. Add one to get started!</p>
            <?php endif; ?>
        </div>
    </div>

    <style>
        .tes-add-student-form table {
            margin-bottom: 20px;
        }
        .button-danger {
            background-color: #dc3545;
            color: white;
            text-decoration: none;
            border: none;
        }
        .button-danger:hover {
            background-color: #c82333;
            color: white;
        }
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
        var searchInput = $('#tes_student_search_input');
        var suggestionsBox = $('#tes_search_suggestions');
        var timer;
                    
        searchInput.on('input', function() {
            var term = $(this).val();
            clearTimeout(timer);
            
            if (term.length < 1) {
                suggestionsBox.hide();
                return;
            }

            timer = setTimeout(function() {
                $.ajax({
                    url: ajaxurl,
                    data: {
                        action: 'tes_student_search_autocomplete',
                        term: term
                    },
                    success: function(response) {
                        if (response.success && response.data.length > 0) {
                            var html = '';
                            $.each(response.data, function(i, item) {
                                html += '<div class="tes-suggestion-item" data-value="' + item.username + '">';
                                html += '<strong>' + item.student_name + '</strong> (' + item.username + ')<br>';
                                html += '<span style="color:#666; font-size:12px;">' + item.class_name + '</span>';
                                html += '</div>';
                            });
                            suggestionsBox.html(html).show();
                        } else {
                            suggestionsBox.hide();
                        }
                    }
                });
            }, 300);
        });

        $(document).on('click', '.tes-suggestion-item', function() {
            var value = $(this).data('value');
            searchInput.val(value);
            suggestionsBox.hide();
            searchInput.closest('form').submit();
        });

        $(document).on('click', function(e) {
            if (!$(e.target).closest('#tes_student_search_input').length && !$(e.target).closest('#tes_search_suggestions').length) {
                suggestionsBox.hide();
            }
        });

        // Import Form Handler
        $('#tes-import-form').on('submit', function(e) {
            e.preventDefault();
            var form = $(this);
            var formData = new FormData(this);
            formData.append('action', 'tes_import_students');
            
            form.find('.spinner').addClass('is-active');
            $('#tes-import-result').html('');

            $.ajax({
                url: ajaxurl, 
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    form.find('.spinner').removeClass('is-active');
                    if (response.success) {
                        $('#tes-import-result').html('<span style="color:green;">' + response.data + '</span>');
                        form[0].reset();
                        setTimeout(function() { location.reload(); }, 2000);
                    } else {
                        $('#tes-import-result').html('<span style="color:red;">' + response.data + '</span>');
                    }
                },
                error: function() {
                    form.find('.spinner').removeClass('is-active');
                    $('#tes-import-result').html('<span style="color:red;">Server error. Please try again.</span>');
                }
            });
        });

        // Select All Checkbox
        $('#cb-select-all-1').on('click', function() {
            var checked = this.checked;
            $('input[name="student_ids[]"]').prop('checked', checked);
        });
    });
    </script>

    <?php
}
