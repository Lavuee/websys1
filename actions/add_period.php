<?php
// 1. Secure the route
require_once 'auth.php';
check_admin();

// 2. Connect to database
require_once '../config/db.php';

// 3. Process the form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $year_string = trim($_POST['year_string'] ?? '');
    $semester = $_POST['semester'] ?? 'Full Year';

    if (!empty($year_string)) {
        try {
            // Insert the new period. We set is_active to 0 by default so it doesn't interrupt current enrollments
            $stmt = $pdo->prepare("INSERT INTO school_years (year_string, semester, is_active) VALUES (?, ?, 0)");
            $stmt->execute([$year_string, $semester]);
            
            // Redirect back to the periods page
            header("Location: ../admin/enrollment_period.php?msg=Period Created Successfully");
            exit();
            
        } catch (\PDOException $e) {
            die("Database error: " . $e->getMessage());
        }
    } else {
        header("Location: ../admin/enrollment_period.php?error=Missing Information");
        exit();
    }
} else {
    header("Location: ../admin/enrollment_period.php");
    exit();
}