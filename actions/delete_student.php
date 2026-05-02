<?php
require_once '../actions/auth.php';
check_auth();

// Allow Admin or Registrar to delete records
if (!in_array(strtolower($_SESSION['role']), ['admin', 'registrar'])) {
    header("Location: ../login.php?ref=forbidden");
    exit();
}

// Connect to the database
require_once '../config/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $enrollment_id = (int)($_POST['enrollment_id'] ?? 0);
    $return_to = $_POST['return_to'] ?? '';

    if ($enrollment_id > 0) {
        try {
            // Find the associated student_id and user_id to completely remove the student
            $stmt = $pdo->prepare("
                SELECT s.student_id, s.user_id 
                FROM enrollments e 
                JOIN students s ON e.student_id = s.student_id 
                WHERE e.enrollment_id = ?
            ");
            $stmt->execute([$enrollment_id]);
            $student = $stmt->fetch();

            if ($student) {
                $pdo->beginTransaction();
                
                // Deleting the student will cascade to enrollments, grades, payments, etc.
                $delStudent = $pdo->prepare("DELETE FROM students WHERE student_id = ?");
                $delStudent->execute([$student['student_id']]);

                // Delete the user account as well
                if (!empty($student['user_id'])) {
                    $delUser = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
                    $delUser->execute([$student['user_id']]);
                }
                
                $pdo->commit();
            }
        } catch (\PDOException $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            die("Error deleting enrollment: " . $e->getMessage());
        }
    }
    
    if (strtolower($_SESSION['role']) === 'registrar') {
        $redirect = !empty($return_to) ? "../registrar/{$return_to}" : "../registrar/student_records.php";
    } else {
        $redirect = "../admin/students.php";
    }
    
    header("Location: {$redirect}");
    exit();
} else {
    header("Location: ../index.php");
    exit();
}
?>