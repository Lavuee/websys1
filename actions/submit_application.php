<?php
session_start();
require_once '../config/db.php'; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $enrollment_id = 'ENR-' . date('Y') . '-' . strtoupper(substr(uniqid(), -5));

    $student_email = filter_var($_POST['student_email'], FILTER_SANITIZE_EMAIL);
    $first_name = htmlspecialchars($_POST['first_name']);
    $middle_name = htmlspecialchars($_POST['middle_name']);
    $last_name = htmlspecialchars($_POST['last_name']);
    $suffix = htmlspecialchars($_POST['suffix']);
    $date_of_birth = $_POST['date_of_birth'] ? $_POST['date_of_birth'] : NULL;
    $gender = $_POST['gender'] ?: NULL;
    $contact_number = htmlspecialchars($_POST['contact_number']);
    $address = htmlspecialchars($_POST['address']);
    $guardian_name = htmlspecialchars($_POST['guardian_name']);
    $guardian_contact = htmlspecialchars($_POST['guardian_contact']);
    $grade_level = $_POST['grade_level'];
    $strand = $_POST['strand'];
    $lrn = htmlspecialchars($_POST['lrn']);
    $previous_school = htmlspecialchars($_POST['previous_school']);
    $school_year = '2025-2026'; 

    if (empty($first_name) || empty($last_name) || empty($student_email) || empty($grade_level)) {
        die("Error: Required fields are missing. Please go back and try again.");
    }

    try {
        $stmt = $pdo->prepare("
            INSERT INTO Enrollments (
                enrollment_id, student_email, first_name, middle_name, last_name, 
                suffix, date_of_birth, gender, contact_number, address, 
                guardian_name, guardian_contact, grade_level, strand, 
                school_year, lrn, previous_school, status
            ) VALUES (
                :enrollment_id, :student_email, :first_name, :middle_name, :last_name, 
                :suffix, :date_of_birth, :gender, :contact_number, :address, 
                :guardian_name, :guardian_contact, :grade_level, :strand, 
                :school_year, :lrn, :previous_school, 'Pending'
            )
        ");

        $stmt->execute([
            ':enrollment_id' => $enrollment_id,
            ':student_email' => $student_email,
            ':first_name' => $first_name,
            ':middle_name' => $middle_name,
            ':last_name' => $last_name,
            ':suffix' => $suffix,
            ':date_of_birth' => $date_of_birth,
            ':gender' => $gender,
            ':contact_number' => $contact_number,
            ':address' => $address,
            ':guardian_name' => $guardian_name,
            ':guardian_contact' => $guardian_contact,
            ':grade_level' => $grade_level,
            ':strand' => $strand,
            ':school_year' => $school_year,
            ':lrn' => $lrn,
            ':previous_school' => $previous_school
        ]);

        echo "<script>
                alert('Application submitted successfully! Your tracking ID is: " . $enrollment_id . "');
                window.location.href = '../index.php';
              </script>";
        exit();

    } catch (\PDOException $e) {
        die("Database Error: " . $e->getMessage());
    }
} else {
    header("Location: ../apply.php");
    exit();
}
?>