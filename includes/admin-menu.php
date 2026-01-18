<?php
if (!defined('ABSPATH')) exit;

/**
 * Add Admin Menu for Teacher Evaluation Survey Plugin
 */
function tes_admin_menu() {

    // Main menu
    add_menu_page(
        'Teacher Survey',          // Page title
        'Teacher Survey',          // Menu title
        'manage_options',          // Capability
        'tes-dashboard',           // Menu slug
        'tes_results_page',        // Callback function (must exist)
        'dashicons-welcome-learn-more',
        20
    );

    // Submenu: Teachers
    add_submenu_page(
        'tes-dashboard',
        'Manage Teachers',
        'Teachers',
        'manage_options',
        'tes-teachers',
        'tes_teachers_page'       // Callback must exist
    );

    // Submenu: Surveys
    add_submenu_page(
        'tes-dashboard',
        'Manage Surveys',
        'Surveys',
        'manage_options',
        'tes-surveys',
        'tes_surveys_page'        // Callback must exist
    );

    // Submenu: Survey Questions
    add_submenu_page(
        'tes-dashboard',
        'Survey Questions',
        'Survey Questions',
        'manage_options',
        'tes-questions',
        'tes_questions_page'      // Callback must exist
    );

    // Submenu: Students
    add_submenu_page(
        'tes-dashboard',
        'Manage Students',
        'Students',
        'manage_options',
        'tes-students',
        'tes_students_page'       // Callback must exist
    );

}
add_action('admin_menu', 'tes_admin_menu');
