<?php
require_once 'auth.php';
check_auth();

// Only Admin or Cashier can log direct payments
if (!in_array(strtolower($_SESSION['role']), ['admin', 'cashier'])) {
    header("Location: ../login.php?ref=forbidden");
    exit();
}

require_once '../config/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $enrollment_id = $_POST['enrollment_id'];
    $amount = (float) $_POST['amount'];
    $method = $_POST['payment_method'];
    $cashier_id = $_SESSION['user_id'];

    try {
        $pdo->beginTransaction();

        // 1. Insert as 'Verified' payment immediately since the Cashier received it directly
        $stmt = $pdo->prepare("
            INSERT INTO payments (enrollment_id, amount, payment_method, status, cashier_id) 
            VALUES (:eid, :amt, :method, 'Verified', :cashier)
        ");
        $stmt->execute([
            ':eid' => $enrollment_id,
            ':amt' => $amount,
            ':method' => $method,
            ':cashier' => $cashier_id
        ]);

        // 2. Recalculate balance
        $fetchStmt = $pdo->prepare("SELECT total_assessment FROM enrollments WHERE enrollment_id = :eid");
        $fetchStmt->execute([':eid' => $enrollment_id]);
        $total = (float) $fetchStmt->fetchColumn();

        $payStmt = $pdo->prepare("SELECT SUM(amount) FROM payments WHERE enrollment_id = :eid AND status = 'Verified'");
        $payStmt->execute([':eid' => $enrollment_id]);
        $paid = (float) $payStmt->fetchColumn();

        $new_balance = max(0, $total - $paid);
        $new_status = ($new_balance == 0) ? 'Enrolled' : 'Assessed';

        // 3. Update the student's Enrollment record
        $updateEnr = $pdo->prepare("UPDATE enrollments SET balance = :bal, status = :st WHERE enrollment_id = :eid");
        $updateEnr->execute([':bal' => $new_balance, ':st' => $new_status, ':eid' => $enrollment_id]);

        $pdo->commit();
        if (strtolower($_SESSION['role']) === 'admin') {
            header("Location: ../admin/payments.php");
        } else {
            header("Location: ../cashier/dashboard.php");
        }
        exit();
    } catch (\PDOException $e) {
        $pdo->rollBack();
        die("Database Error: " . $e->getMessage());
    }
} else {
    if (isset($_SESSION['role']) && strtolower($_SESSION['role']) === 'admin') {
        header("Location: ../admin/payments.php");
    } else {
        header("Location: ../cashier/dashboard.php");
    }
    exit();
}
?>