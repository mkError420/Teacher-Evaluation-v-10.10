<?php
// Test AJAX endpoint directly
define('WP_USE_THEMES', false);
require('wp-load.php');

// Simulate AJAX request
$_POST['action'] = 'tes_load_surveys_by_department';
$_POST['department'] = 'Orthoped.';

global $wpdb;

$department = sanitize_text_field($_POST['department']);

$surveys = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT s.id, s.title, t.name as teacher_name
         FROM {$wpdb->prefix}tes_surveys s
         LEFT JOIN {$wpdb->prefix}tes_teachers t ON s.teacher_id = t.id
         WHERE t.department = %s
         ORDER BY t.name, s.title",
        $department
    )
);

echo "<h3>Testing AJAX Endpoint</h3>";
echo "<p><strong>Department:</strong> " . $department . "</p>";
echo "<p><strong>Surveys Found:</strong> " . count($surveys) . "</p>";

if ($surveys) {
    echo "<h4>Surveys:</h4>";
    echo "<pre>";
    print_r($surveys);
    echo "</pre>";
} else {
    echo "<p style='color: red;'>No surveys found!</p>";
}
?>
