<?php
if (!defined('ABSPATH')) exit;

function tes_create_tables() {
    global $wpdb;
    $charset = $wpdb->get_charset_collate();

    $teachers = $wpdb->prefix . 'tes_teachers';
    $surveys = $wpdb->prefix . 'tes_surveys';
    $questions = $wpdb->prefix . 'tes_questions';
    $submissions = $wpdb->prefix . 'tes_submissions';
    $students = $wpdb->prefix . 'tes_students';

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    // Teachers table
    $sql1 = "CREATE TABLE IF NOT EXISTS $teachers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        department VARCHAR(100)
    ) $charset;";

    // Surveys table
    $sql2 = "CREATE TABLE IF NOT EXISTS $surveys (
        id INT AUTO_INCREMENT PRIMARY KEY,
        teacher_id INT NOT NULL,
        title VARCHAR(255) NOT NULL
    ) $charset;";

    // Questions table
    $sql3 = "CREATE TABLE IF NOT EXISTS $questions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        survey_id INT NOT NULL,
        question_text TEXT NOT NULL,
        options TEXT NOT NULL
    ) $charset;";

    // Students table
    $sql5 = "CREATE TABLE IF NOT EXISTS $students (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_name VARCHAR(100) NOT NULL,
        username VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        department VARCHAR(100),
        session VARCHAR(100),
        batch_name VARCHAR(100),
        phase VARCHAR(100),
        roll VARCHAR(100),
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) $charset;";

    // Submissions table
    $sql4 = "CREATE TABLE IF NOT EXISTS $submissions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        survey_id INT NOT NULL,
        student_id INT,
        student_name VARCHAR(100),
        answers TEXT NOT NULL,
        submitted_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) $charset;";

    // Execute all queries directly
    $wpdb->query($sql1);
    $wpdb->query($sql2);
    $wpdb->query($sql3);
    $wpdb->query($sql5);
    $wpdb->query($sql4);
}

// Run table creation on admin init to ensure tables exist
add_action('admin_init', 'tes_create_tables');
