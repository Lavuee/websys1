<?php
// Secures the endpoint to allow only authenticated student sessions.
require_once '../actions/auth.php';
check_student();

// Initializes the database connection.
require_once '../config/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Inserts the new payment record with a default 'Pending' status.
        $stmt = $pdo->prepare("
            INSERT INTO payments (enrollment_id, amount, payment_method, status) 
            VALUES (:eid, :amt, :method, 'Pending')
        ");
        
        $stmt->execute([
            ':eid' => $_SESSION['enrollment_id'],
            ':amt' => (float) $_POST['amount'],
            ':method' => $_POST['payment_method']
        ]);
        
        // Redirects the user back to the assessment portal with a success prompt.
        echo "<script>
                alert('Payment submitted successfully. Awaiting administrative verification.');
                window.location.href = '../student/assessment.php';
              </script>";
        exit();

    } catch (\PDOException $e) {
        // Halts execution and displays an error message upon database failure.
        die("Database Error: " . $e->getMessage());
    }
} else {
    // Redirects unauthorized direct access attempts back to the portal.
    header("Location: ../student/assessment.php");
    exit();
}
?>