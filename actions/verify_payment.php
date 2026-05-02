<?php
// Secures the endpoint to prevent unauthorized execution of financial operations.
require_once '../actions/auth.php';
check_auth();
if (!in_array(strtolower($_SESSION['role']), ['admin', 'cashier'])) {
    header("Location: ../login.php?ref=forbidden");
    exit();
}

// Initializes the database connection.
require_once '../config/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $payment_id = $_POST['payment_id'];
    $enrollment_id = $_POST['enrollment_id'];
    $action = $_POST['action']; 
    $cashier_id = $_SESSION['user_id']; 

    try {
        // Initiates a database transaction to ensure data integrity.
        $pdo->beginTransaction();

        $status = ($action === 'Approve') ? 'Verified' : 'Rejected';
        
        // Updates the transaction status and logs the reviewing cashier.
        $stmt = $pdo->prepare("
            UPDATE payments 
            SET status = :st, cashier_id = :cashier 
            WHERE payment_id = :id
        ");
        $stmt->execute([
            ':st' => $status, 
            ':cashier' => $cashier_id, 
            ':id' => $payment_id
        ]);

        // Proceed with financial recalculation only if the payment is approved.
        if ($action === 'Approve') {
            
            // Retrieves the base assessment for the enrollment.
            $fetchStmt = $pdo->prepare("SELECT total_assessment FROM enrollments WHERE enrollment_id = :eid");
            $fetchStmt->execute([':eid' => $enrollment_id]);
            $total = (float) $fetchStmt->fetchColumn();

            // Sums all successfully verified payments for this enrollment.
            $payStmt = $pdo->prepare("SELECT SUM(amount) FROM payments WHERE enrollment_id = :eid AND status = 'Verified'");
            $payStmt->execute([':eid' => $enrollment_id]);
            $paid = (float) $payStmt->fetchColumn();

            // Calculates the remaining balance, ensuring it does not drop below zero.
            $new_balance = $total - $paid;
            if ($new_balance < 0) {
                $new_balance = 0; 
            }
            
            // Automatically elevates the enrollment status to 'Enrolled' upon full payment.
            $new_status = ($new_balance == 0) ? 'Enrolled' : 'Assessed';

            // Applies the recalculated financial data to the enrollment record.
            $updateEnr = $pdo->prepare("
                UPDATE enrollments 
                SET balance = :bal, status = :st 
                WHERE enrollment_id = :eid
            ");
            $updateEnr->execute([
                ':bal' => $new_balance, 
                ':st' => $new_status, 
                ':eid' => $enrollment_id
            ]);
        }

        // Commits the transaction and redirects the administrator.
        $pdo->commit();
        if (strtolower($_SESSION['role']) === 'cashier') {
            header("Location: ../cashier/dashboard.php");
        } else {
            header("Location: ../admin/payments.php");
        }
        exit();

    } catch (\PDOException $e) {
        // Rolls back all changes to prevent partial data updates.
        $pdo->rollBack();
        die("Transaction Error: " . $e->getMessage());
    }
} else {
    // Redirects anomalous direct access attempts.
    if (strtolower($_SESSION['role']) === 'cashier') {
        header("Location: ../cashier/dashboard.php");
    } else {
        header("Location: ../admin/payments.php");
    }
    exit();
}
?>