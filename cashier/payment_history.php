<?php
require_once '../actions/auth.php';
check_cashier();

require_once '../config/db.php';

try {
    // Fetch non-pending payments
    $stmt = $pdo->query("
        SELECT p.payment_id, p.enrollment_id, p.amount, p.payment_method, p.status, p.payment_date,
               s.first_name, s.last_name, 
               u.email AS student_email 
        FROM payments p 
        JOIN enrollments e ON p.enrollment_id = e.enrollment_id 
        JOIN students s ON e.student_id = s.student_id
        JOIN users u ON s.user_id = u.user_id
        WHERE p.status != 'Pending'
        ORDER BY p.payment_date DESC
    ");
    $payments = $stmt->fetchAll();

} catch (\PDOException $e) {
    die("Database error: " . $e->getMessage());
}

$statusColors = [
    'Verified' => 'background: rgba(34, 197, 94, 0.15); color: #22c55e; border: 1px solid rgba(34, 197, 94, 0.3);',
    'Rejected' => 'background: rgba(239, 68, 68, 0.15); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.3);'
];

$methodColors = [
    'GCash' => 'background: rgba(59, 130, 246, 0.15); color: #3b82f6; border: 1px solid rgba(59, 130, 246, 0.3);',
    'Cash'  => 'background: rgba(100, 116, 139, 0.15); color: #64748b; border: 1px solid rgba(100, 116, 139, 0.3);'
];

include 'includes/cashier_header.php'; 
?>
<body>

    <div class="layout">
        
        <?php include 'includes/cashier_sidebar.php'; ?>

        <main class="main-content">
            <h2 style="margin-bottom: 5px;">Payment History</h2>
            <p class="text-muted" style="margin-bottom: 30px; font-size: 0.9rem;">Log of verified and rejected financial transactions.</p>

            <div class="glass-panel">
                <table class="table-wrapper">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Method</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Date Processed</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payments as $row): ?>
                            <tr>
                                <td>
                                    <div style="font-weight: 500;"><?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?></div>
                                    <div class="text-muted" style="font-size: 0.8rem;"><?= htmlspecialchars($row['student_email']) ?></div>
                                </td>
                                <td>
                                    <span class="badge" style="<?= $methodColors[$row['payment_method']] ?? '' ?>">
                                        <?= htmlspecialchars($row['payment_method']) ?>
                                    </span>
                                </td>
                                <td style="font-weight: 600;">₱<?= number_format($row['amount'], 2) ?></td>
                                <td>
                                    <span class="badge" style="<?= $statusColors[$row['status']] ?? '' ?>">
                                        <?= htmlspecialchars($row['status']) ?>
                                    </span>
                                </td>
                                <td><?= date('m/d/Y h:i A', strtotime($row['payment_date'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        
                        <?php if (empty($payments)): ?>
                            <tr>
                                <td colspan="5" style="text-align: center; padding: 30px; color: var(--text-muted);">
                                    No processed payments found.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
    <script src="../assets/js/main.js"></script>
</body>
</html>