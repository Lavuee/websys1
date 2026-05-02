<?php
session_start();
require_once '../config/db.php';


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $enrollment_id = $_POST['enrollment_id'];
    $status = $_POST['status']; // e.g., 'Approved', 'Rejected'

    try {
        // Fetch student details to get the email
        $stmt = $pdo->prepare("SELECT student_email, first_name FROM Enrollments WHERE enrollment_id = :id");
        $stmt->execute([':id' => $enrollment_id]);
        $student = $stmt->fetch();

        if ($student) {
            // Update the status in the database
            $stmtUpdate = $pdo->prepare("UPDATE Enrollments SET status = :status WHERE enrollment_id = :id");
            $stmtUpdate->execute([':status' => $status, ':id' => $enrollment_id]);

            echo "<script>
                    alert('Status updated to {$status} successfully!');
                    window.history.back();
                  </script>";
        } else {
            echo "Student not found.";
        }
    } catch (\PDOException $e) {
        die("Database Error: " . $e->getMessage());
    }
} else {
    header("Location: ../index.php");
    exit();
}
?>
