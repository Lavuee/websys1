<?php
require_once 'auth.php';
check_auth();

// Only allow Admin or Registrar to update status
if (!in_array(strtolower($_SESSION['role']), ['admin', 'registrar'])) {
    header("Location: ../login.php?ref=forbidden");
    exit();
}

require_once '../config/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $enrollment_id = $_POST['enrollment_id'];
    $status = $_POST['status']; // e.g., 'Approved', 'Rejected'
    $total_assessment = (isset($_POST['total_assessment']) && trim($_POST['total_assessment']) !== '') ? (float)$_POST['total_assessment'] : null;
    $return_to = $_POST['return_to'] ?? '';

    try {
        if ($total_assessment !== null) {
            // Calculate balance if assessment is updated
            $payStmt = $pdo->prepare("SELECT SUM(amount) as paid FROM payments WHERE enrollment_id = :id AND status = 'Verified'");
            $payStmt->execute([':id' => $enrollment_id]);
            $paid = (float)$payStmt->fetchColumn();
            
            $balance = max(0, $total_assessment - $paid);
            
            $stmtUpdate = $pdo->prepare("UPDATE enrollments SET status = :status, total_assessment = :tot, balance = :bal WHERE enrollment_id = :id");
            $stmtUpdate->execute([':status' => $status, ':tot' => $total_assessment, ':bal' => $balance, ':id' => $enrollment_id]);
        } else {
            $stmtUpdate = $pdo->prepare("UPDATE enrollments SET status = :status WHERE enrollment_id = :id");
            $stmtUpdate->execute([':status' => $status, ':id' => $enrollment_id]);
        }
        
        $redirect = !empty($return_to) ? '../registrar/' . $return_to : '../registrar/enrollment_queue.php';
        if (strtolower($_SESSION['role']) === 'admin') {
            $redirect = '../admin/dashboard.php';
        }
        
        header("Location: {$redirect}");
        exit();
    } catch (\PDOException $e) {
        die("Database Error: " . $e->getMessage());
    }
} else {
    header("Location: ../index.php");
    exit();
}
?>
