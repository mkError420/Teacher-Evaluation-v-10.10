# Teacher Evaluation Survey

**Develop By:** MK.RABBANI(Website manager at Rangpur Group)  
**Version:** 8.10  
**Requires at least:** WordPress 5.0  
**Tested up to:** WordPress 6.4  

## Description

Teacher Evaluation Survey is a comprehensive WordPress plugin designed to facilitate teacher performance evaluations by students. It provides a complete ecosystem for managing teachers, students, and surveys, featuring an intuitive admin dashboard for data management and analytics, and a secure frontend for student submissions.

## Features

### 🎓 Admin Management
*   **Teacher Management:** Add, edit, and delete teacher profiles. Organize teachers by departments with bulk renaming capabilities.
*   **Student Management:** 
    *   Add and manage student profiles with a streamlined horizontal form.
    *   **Auto-Credentials:** Automatically sets `Username` to Student Name and `Password` to Roll Number for simplified onboarding.
    *   Searchable student database.
*   **Survey Builder:** Create specific surveys linked to individual teachers.
*   **Question Management:** Add customizable multiple-choice questions (up to 10 options) to specific surveys.
*   **Results Dashboard:** 
    *   Visual analytics using Chart.js (Pie charts).
    *   Overall average ratings (calculated on a scale of 5).
    *   Detailed question-wise breakdown.
    *   Student-wise submission averages.

### 🏫 Student Frontend
*   **Secure Login:** Dedicated login interface for students using their assigned credentials.
*   **Smart Survey Loading:** Automatically filters and displays surveys relevant to the student's assigned department.
*   **Submission Control:** Prevents duplicate submissions for the same survey by the same student.
*   **Responsive Interface:** Modern, AJAX-powered interface for a smooth user experience.

## Installation

1.  Upload the `teacher-evaluation-survey-v8` folder to the `/wp-content/plugins/` directory.
2.  Activate the plugin through the 'Plugins' menu in WordPress.
3.  Upon activation, the plugin will automatically create the necessary database tables (`tes_teachers`, `tes_students`, `tes_surveys`, `tes_questions`, `tes_submissions`).

## Usage Guide

### Step 1: Populate Data
1.  Go to **Teacher Survey > Manage Teachers**. Add your departments and teachers.
2.  Go to **Teacher Survey > Manage Students**. Add students.
    *   *Tip:* You only need to provide the Name, Department, and Roll. The system handles the login credentials.

### Step 2: Create Surveys
1.  Go to **Teacher Survey > Manage Surveys**. Create a survey title and assign it to a specific teacher.
2.  Go to **Teacher Survey > Survey Questions**. Select the survey you just created and add questions.

### Step 3: Student Access
1.  Create a new WordPress page (e.g., "Feedback Portal").
2.  Add the shortcode `[teacher_survey]` to the page content.
3.  Publish the page. Students can now log in and submit evaluations.

### Step 4: View Reports
1.  Navigate to the main **Teacher Survey** menu item.
2.  Select a survey from the dropdown to view real-time statistics and charts.

## Shortcodes

*   `[teacher_survey]` - Renders the student login form and survey interface.

## Technical Details
*   **Database:** Uses custom tables for high performance.
*   **Frontend:** Powered by jQuery and AJAX for non-blocking interactions.
*   **Visualization:** Integrates Chart.js for data visualization.