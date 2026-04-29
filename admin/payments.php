<?php
// Secures the page to ensure only users with administrative privileges can access it.
require_once '../actions/auth.php';
check_admin();

// Establishes the connection to the database.
require_once '../config/db.php';

try {
    // Retrieves all payment records and joins them with student demographic and user account data.
    // Results are ordered with pending verifications appearing first, followed by the most recent payments.
    $stmt = $pdo->query("
        SELECT p.payment_id, p.enrollment_id, p.amount, p.payment_method, p.status, p.payment_date,
               s.first_name, s.last_name, 
               u.email AS student_email 
        FROM payments p 
        JOIN enrollments e ON p.enrollment_id = e.enrollment_id 
        JOIN students s ON e.student_id = s.student_id
        JOIN users u ON s.user_id = u.user_id
        ORDER BY FIELD(p.status, 'Pending', 'Verified', 'Rejected'), p.payment_date DESC
    ");
    $payments = $stmt->fetchAll();

} catch (\PDOException $e) {
    // Halts execution and logs the error to prevent application instability.
    die("Database error: " . $e->getMessage());
}

// Badge color mapping for dynamic status styling (matching your theme variables)
$statusColors = [
    'Pending'  => 'background: rgba(234, 179, 8, 0.15); color: #eab308; border: 1px solid rgba(234, 179, 8, 0.3);',
    'Verified' => 'background: rgba(34, 197, 94, 0.15); color: #22c55e; border: 1px solid rgba(34, 197, 94, 0.3);',
    'Rejected' => 'background: rgba(239, 68, 68, 0.15); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.3);'
];

$methodColors = [
    'GCash' => 'background: rgba(59, 130, 246, 0.15); color: #3b82f6; border: 1px solid rgba(59, 130, 246, 0.3);',
    'Cash'  => 'background: rgba(100, 116, 139, 0.15); color: #64748b; border: 1px solid rgba(100, 116, 139, 0.3);'
];

// Include the modular header (Handles Theme persistence and CSS links)
include 'includes/admin_header.php'; 
?>
<body>

    <div class="layout">
        
        <?php include 'includes/sidebar.php'; ?>

        <main class="main-content">
            <h2 style="margin-bottom: 5px;">Payment Verification</h2>
            <p class="text-muted" style="margin-bottom: 30px; font-size: 0.9rem;">Review, approve, or reject student financial transactions.</p>

            <div class="glass-panel">
                <table class="table-wrapper">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Method</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Actions</th>
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
                                <td><?= date('m/d/Y', strtotime($row['payment_date'])) ?></td>
                                <td>
                                    <?php if ($row['status'] === 'Pending'): ?>
                                        <form action="../actions/verify_payment.php" method="POST" style="display: flex; gap: 8px;">
                                            <input type="hidden" name="payment_id" value="<?= $row['payment_id'] ?>">
                                            <input type="hidden" name="enrollment_id" value="<?= $row['enrollment_id'] ?>">
                                            
                                            <button type="submit" name="action" value="Approve" class="btn btn-primary" style="padding: 6px 12px; font-size: 0.8rem;">Approve</button>
                                            <button type="submit" name="action" value="Reject" class="btn btn-outline" style="padding: 6px 12px; font-size: 0.8rem; color: #ef4444; border-color: rgba(239, 68, 68, 0.4);">Reject</button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-muted" style="font-size: 0.85rem; font-weight: 500;">Processed</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        
                        <?php if (empty($payments)): ?>
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 30px; color: var(--text-muted);">
                                    No financial transactions found.
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