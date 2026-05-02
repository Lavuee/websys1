<?php
// Initializes session state for the transaction.
session_start();

// Establishes the database connection.
require_once '../config/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Generates a unique tracking identifier for the enrollment application.
    $tracking_no = 'ENR-' . date('Y') . '-' . strtoupper(substr(uniqid(), -5));
    $student_email = filter_var($_POST['student_email'], FILTER_SANITIZE_EMAIL);
    
    // Sets up file upload directory and parsing logic
    $upload_dir = '../uploads/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $processUpload = function($fileKey, $prefix) use ($upload_dir) {
        if (isset($_FILES[$fileKey]) && $_FILES[$fileKey]['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES[$fileKey]['name'], PATHINFO_EXTENSION));
            $allowed = ['pdf', 'jpg', 'jpeg', 'png'];
            if (in_array($ext, $allowed)) {
                $filename = $prefix . '_' . time() . '_' . uniqid() . '.' . $ext;
                if (move_uploaded_file($_FILES[$fileKey]['tmp_name'], $upload_dir . $filename)) {
                    return $filename;
                }
            }
        }
        return null;
    };

    // Process the uploaded requirement documents
    $doc_birth_cert  = $processUpload('doc_birth_cert', 'psa');
    $doc_report_card = $processUpload('doc_report_card', 'f138');
    $doc_good_moral  = $processUpload('doc_good_moral', 'gmoral');

    try {
        // Initiates a database transaction to ensure atomicity across multiple table insertions.
        $pdo->beginTransaction();

        // Provisions a new user account for portal access.
        $password_hash = password_hash($tracking_no, PASSWORD_DEFAULT);
        $stmtUser = $pdo->prepare("INSERT INTO users (email, password_hash, role) VALUES (?, ?, 'Student')");
        $stmtUser->execute([$student_email, $password_hash]);
        $user_id = $pdo->lastInsertId();

        // Records the permanent demographic profile of the student.
        $stmtStudent = $pdo->prepare("
            INSERT INTO students (user_id, lrn, first_name, middle_name, last_name, suffix, date_of_birth, gender, contact_number, address, guardian_name, guardian_contact, psa_birth_cert, form_138, good_moral) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmtStudent->execute([
            $user_id,
            $_POST['lrn'] ?: null,
            $_POST['first_name'],
            $_POST['middle_name'] ?: null,
            $_POST['last_name'],
            $_POST['suffix'] ?: null,
            $_POST['date_of_birth'] ?: null,
            $_POST['gender'] ?: null,
            $_POST['contact_number'] ?: null,
            $_POST['address'] ?: null,
            $_POST['guardian_name'] ?: null,
            $_POST['guardian_contact'] ?: null,
            $doc_birth_cert,
            $doc_report_card,
            $doc_good_moral
        ]);
        $student_id = $pdo->lastInsertId();

        // Registers the specific academic enrollment for the active school year.
        $stmtEnr = $pdo->prepare("
            INSERT INTO enrollments (tracking_no, student_id, school_year_id, grade_level, strand, status) 
            VALUES (?, ?, 1, ?, ?, 'Pending')
        ");
        $stmtEnr->execute([
            $tracking_no,
            $student_id,
            $_POST['grade_level'],
            $_POST['strand']
        ]);

        // Commits the transaction to finalize data storage.
        $pdo->commit();

        // Redirects to the landing page with authentication instructions.
        echo "<script>
                alert('Application submitted! Please select your subjects and section.');
                window.location.href = 'select_subjects.php?enrollment_id=" . $tracking_no . "';
              </script>";
        exit();

    } catch (\PDOException $e) {
        // Reverts all database changes if any insertion fails.
        $pdo->rollBack();

        // Intercepts duplicate email registration attempts.
        if ($e->getCode() == 23000) {
            die("Error: The provided email address is already registered within the system.");
        }
        die("Database Error: " . $e->getMessage());
    }
} else {
    // Redirects anomalous requests back to the application form.
    header("Location: ../apply.php");
    exit();
}
?>