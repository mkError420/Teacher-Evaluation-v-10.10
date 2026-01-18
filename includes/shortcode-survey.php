<?php
if (!defined('ABSPATH')) exit;

function tes_handle_student_auth() {
    if (is_admin()) return;

    if (isset($_POST['tes_student_login']) && isset($_POST['username']) && isset($_POST['password'])) {
        global $wpdb;
        $username = sanitize_text_field($_POST['username']);
        $password = $_POST['password'];
        $student = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}tes_students WHERE username = %s", $username));

        if ($student && (wp_check_password($password, $student->password) || $password === $student->password)) {
            setcookie('tes_student_id', $student->id, time() + 86400, COOKIEPATH, COOKIE_DOMAIN);
            wp_redirect(remove_query_arg('tes_login_error'));
            exit;
        } else {
            wp_redirect(add_query_arg('tes_login_error', '1'));
            exit;
        }
    }

    if (isset($_GET['tes_action']) && $_GET['tes_action'] === 'logout') {
        setcookie('tes_student_id', '', time() - 3600, COOKIEPATH, COOKIE_DOMAIN);
        wp_redirect(remove_query_arg('tes_action'));
        exit;
    }
}
add_action('init', 'tes_handle_student_auth');

function tes_student_survey_shortcode() {
    global $wpdb;

    $student_id = isset($_COOKIE['tes_student_id']) ? intval($_COOKIE['tes_student_id']) : 0;
    $current_student = null;
    if ($student_id) {
        $current_student = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}tes_students WHERE id = %d", $student_id));
        if (!$current_student) $student_id = 0;
    }

    ob_start();

    if (!$student_id) {
        $error = isset($_GET['tes_login_error']);
        ?>
        <style>
            body {
                background: #000 !important;
            }
            .tes-login-wrapper input::placeholder {
                color: #eee;
                opacity: 0.7;
            }
            .tes-login-wrapper input:-ms-input-placeholder {
                color: #eee;
            }
            .tes-login-wrapper input::-ms-input-placeholder {
                color: #eee;
            }
            .tes-login-btn:hover {
                background-color: #555 !important;
            }
        </style>
        <div class="tes-login-wrapper" style="max-width: 400px; margin: 40px auto; padding: 30px; background: #424242; /* Gray Box */ border-radius: 10px; box-shadow: 0 0 20px rgba(255,255,255,0.1); font-family: sans-serif;">
            <h2 style="text-align: center; margin-bottom: 20px; color: #fff;">Student Login</h2>
            <?php if ($error): ?>
                <div style="background: #ffebee; color: #c62828; padding: 12px; border-radius: 4px; margin-bottom: 20px; text-align: center; border: 1px solid #ef9a9a;">Invalid username or password.</div>
            <?php endif; ?>
            <form method="post">
                <div style="margin-bottom: 20px;">
                    <label for="tes_username" style="display: block; margin-bottom: 8px; font-weight: 600; color: #fff;">Username</label>
                    <input type="text" name="username" id="tes_username" required placeholder="Username" style="width: 100%; padding: 12px; border: 1px solid #757575; border-radius: 4px; box-sizing: border-box; font-size: 16px; background: #616161; color: #fff;">
                </div>
                <div style="margin-bottom: 25px;">
                    <label for="tes_password" style="display: block; margin-bottom: 8px; font-weight: 600; color: #fff;">Password</label>
                    <div style="position: relative;">
                        <input type="password" name="password" id="tes_password" required placeholder="Password" style="width: 100%; padding: 12px 40px 12px 12px; border: 1px solid #757575; border-radius: 4px; box-sizing: border-box; font-size: 16px; background: #616161; color: #fff;">
                        <span id="tes-toggle-password" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #BDBDBD; user-select: none; font-size: 18px;">&#128065;</span>
                    </div>
                </div>
                <button type="submit" name="tes_student_login" value="1" class="tes-login-btn" style="width: 100%; padding: 12px; background: #333; color: white; border: 1px solid #555; border-radius: 6px; font-size: 16px; font-weight: bold; cursor: pointer; transition: all 0.3s;">Login</button>
            </form>
        </div>
        <script>
        (function() {
            const togglePassword = document.getElementById('tes-toggle-password');
            const password = document.getElementById('tes_password');

            if (togglePassword && password) {
                togglePassword.addEventListener('click', function () {
                    const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
                    password.setAttribute('type', type);
                    this.innerHTML = type === 'password' ? '&#128065;' : '&#128584;';
                });
            }
        })();
        </script>
        <?php
        return ob_get_clean();
    }

    ?>

    <div class="tes-survey-container">
        <div style="max-width: 600px; margin: 0 auto 10px; display: flex; justify-content: space-between; align-items: center; background: #f8f9fa; padding: 10px 15px; border-radius: 8px;">
            <div>
                <div>Logged in as: <strong><?php echo esc_html($current_student->student_name); ?></strong></div>
                <div style="font-size: 0.9em; color: #555; margin-top: 2px;"><?php echo esc_html($current_student->class_name); ?><?php if (!empty($current_student->phase)) echo ' - ' . esc_html($current_student->phase); ?></div>
            </div>
            <a href="?tes_action=logout" style="color: #dc3545; text-decoration: none; font-weight: bold; font-size: 14px;">Logout</a>
        </div>

    <div class="tes-survey-form" style="background: #fff; padding: 30px; border: 1px solid #e5e5e5; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); margin: 20px auto; max-width: 800px;">

        <form class="tes-survey-form" style="" novalidate>

            <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('tes_submit_survey'); ?>">

            <input type="hidden" name="student_name" value="<?php echo esc_attr($current_student->student_name); ?>">
            <input type="hidden" name="student_id" value="<?php echo esc_attr($current_student->id); ?>">

            <div style="margin-bottom: 15px; padding: 12px; background: #f8f9fa; border-radius: 8px; border-left: 4px solid #667eea; font-size: 16px;">
                <strong>Class:</strong> <?php echo esc_html($current_student->class_name); ?>
                <br>
                <strong>Phase:</strong> <?php echo esc_html($current_student->phase); ?>
            </div>
            <input type="hidden" name="class_name" class="tes-class-auto" value="<?php echo esc_attr($current_student->class_name); ?>">
            <input type="hidden" name="phase" class="tes-phase-auto" value="<?php echo esc_attr($current_student->phase); ?>">

            <div class="tes-no-survey-msg" style="display:none; color: #721c24; background-color: #f8d7da; border-color: #f5c6cb; padding: 10px; margin-bottom: 15px; border-radius: 8px;">
                No survey available now
            </div>

            <select name="survey_id"
                    class="tes-survey-select"
                    required
                    style="width:100%; margin-bottom:15px; padding: 10px; border: 2px solid #ddd; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); font-size: 16px; transition: all 0.3s ease; background: linear-gradient(145deg, #ffffff, #e6e6e6); display: none;">
                <option value="">Select Teacher Survey</option>
            </select>

            <div class="tes-questions-area" style="margin-bottom: 15px;"></div>

            <div class="tes-comment-section" style="margin-bottom: 15px; display: none;">
                <label style="font-weight: bold; display: block; margin-bottom: 5px; color: #333;">If any other comments please write down below (Optional)</label>
                <textarea name="comment" rows="4" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 8px; font-family: inherit;"></textarea>
            </div>

            <button type="submit"
                    class="button button-primary"
                    style="margin-top:15px; padding: 12px 24px; border: none; border-radius: 8px; background: linear-gradient(135deg, #4CAF50, #45a049); color: white; font-size: 16px; font-weight: bold; box-shadow: 0 6px 12px rgba(0,0,0,0.2); cursor: pointer; transition: all 0.3s ease; transform: translateY(0);">
                Submit Feedback
            </button>

        </form>

        <div class="tes-success-msg" style="display:none; color: #155724; background-color: #d4edda; border-color: #c3e6cb; padding: 15px; margin-top: 20px; border: 1px solid #c3e6cb; border-radius: 4px;">
            ✔ Feedback submitted successfully.
        </div>

    </div>
    </div>

    <?php
    return ob_get_clean();
}

add_shortcode('teacher_survey', 'tes_student_survey_shortcode');
