<?php
session_start();
require_once '../config/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $enrollment_id = $_POST['enrollment_id'];
    $section = $_POST['section'];
    $subjects = isset($_POST['subjects']) ? $_POST['subjects'] : [];

    if (empty($enrollment_id) || empty($section) || empty($subjects)) {
        die("Error: Section and at least one subject are required.");
    }

    try {
        // Update section in Enrollments
        $stmt = $pdo->prepare("UPDATE Enrollments SET section = :section WHERE enrollment_id = :enrollment_id");
        $stmt->execute([':section' => $section, ':enrollment_id' => $enrollment_id]);

        // Insert subjects
        $stmt_subj = $pdo->prepare("INSERT INTO Enrollment_Subjects (enrollment_id, subject_name) VALUES (:enrollment_id, :subject_name)");
        foreach ($subjects as $subject) {
            $stmt_subj->execute([
                ':enrollment_id' => $enrollment_id,
                ':subject_name' => $subject
            ]);
        }

        echo "<script>
                alert('Subjects and Section saved successfully!');
                window.location.href = '../track_status.php?enrollment_id=" . $enrollment_id . "';
              </script>";
        exit();
    } catch (\PDOException $e) {
        die("Database Error: " . $e->getMessage());
    }
} else {
    header("Location: ../index.php");
    exit();
}
?>
