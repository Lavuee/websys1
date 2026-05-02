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

    // Retrieves assessed students who are awaiting payment
    $stmtAssessed = $pdo->query("
        SELECT e.enrollment_id, e.tracking_no, e.total_assessment, e.balance,
               s.first_name, s.last_name, 
               u.email AS student_email 
        FROM enrollments e 
        JOIN students s ON e.student_id = s.student_id
        JOIN users u ON s.user_id = u.user_id
        WHERE e.status = 'Assessed'
        ORDER BY s.last_name ASC
    ");
    $assessedStudents = $stmtAssessed->fetchAll();

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
                                            
                                            <button type="submit" name="action" value="Approve" class="btn btn-primary" style="padding: 6px 12px; font-size: 0.8rem;" onclick="return confirm('Are you sure you want to approve this payment?');">Approve</button>
                                            <button type="submit" name="action" value="Reject" class="btn btn-outline" style="padding: 6px 12px; font-size: 0.8rem; color: #ef4444; border-color: rgba(239, 68, 68, 0.4);" onclick="return confirm('Are you sure you want to reject this payment?');">Reject</button>
                                        </form>
                                    <?php else: ?>
                                        <form action="../actions/verify_payment.php" method="POST" style="margin: 0;" onsubmit="return confirm('Are you sure you want to revert this payment to Pending?');">
                                            <input type="hidden" name="payment_id" value="<?= $row['payment_id'] ?>">
                                            <input type="hidden" name="enrollment_id" value="<?= $row['enrollment_id'] ?>">
                                            <button type="submit" name="action" value="Revert" class="btn btn-outline" style="padding: 6px 12px; font-size: 0.8rem; color: #f59e0b; border-color: rgba(245, 158, 11, 0.4);"><i class="bi bi-arrow-counterclockwise"></i> Revert</button>
                                        </form>
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

            <!-- New Section for Walk-in / Assessed Students -->
            <div class="glass-panel" style="margin-top: 30px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                    <h3 style="font-size: 1.1rem;">Assessed Students (Awaiting Payment)</h3>
                </div>
                <table class="table-wrapper">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Tracking No.</th>
                            <th>Total Assessment</th>
                            <th>Remaining Balance</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($assessedStudents as $row): ?>
                            <tr>
                                <td>
                                    <div style="font-weight: 500;"><?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?></div>
                                    <div class="text-muted" style="font-size: 0.8rem;"><?= htmlspecialchars($row['student_email']) ?></div>
                                </td>
                                <td style="font-family: monospace;"><?= htmlspecialchars($row['tracking_no']) ?></td>
                                <td>₱<?= number_format($row['total_assessment'], 2) ?></td>
                                <td style="font-weight: 600; color: #ef4444;">₱<?= number_format($row['balance'], 2) ?></td>
                                <td>
                                    <button class="btn btn-primary" style="padding: 6px 12px; font-size: 0.8rem;" onclick='openPaymentModal(<?= json_encode($row) ?>)'>Record Payment</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        
                        <?php if (empty($assessedStudents)): ?>
                            <tr>
                                <td colspan="5" style="text-align: center; padding: 30px; color: var(--text-muted);">
                                    No assessed students awaiting payment.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <!-- Record Over-The-Counter Payment Modal -->
    <div id="paymentModal" class="modal-overlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:2000; justify-content:center; align-items:center;">
        <div class="glass-panel" style="width:100%; max-width:400px; padding:30px;">
            <h3 style="margin-bottom:20px;">Record Direct Payment</h3>
            <form action="../actions/record_cashier_payment.php" method="POST">
                <input type="hidden" name="enrollment_id" id="pay_enrollment_id">
                
                <div style="margin-bottom: 15px;">
                    <label style="font-size:0.85rem; font-weight:600; display:block; margin-bottom:5px;">Student</label>
                    <input type="text" id="pay_student_name" class="form-control" disabled style="background: rgba(100, 116, 139, 0.1); color: var(--text-muted); border: 1px solid var(--glass-border);">
                </div>

                <div style="margin-bottom: 15px;">
                    <label style="font-size:0.85rem; font-weight:600; display:block; margin-bottom:5px;">Remaining Balance (₱)</label>
                    <input type="text" id="pay_balance" class="form-control" disabled style="background: rgba(100, 116, 139, 0.1); color: var(--text-muted); border: 1px solid var(--glass-border);">
                </div>

                <div style="margin-bottom: 15px;">
                    <label style="font-size:0.85rem; font-weight:600; display:block; margin-bottom:5px;">Amount Paid (₱)</label>
                    <input type="number" step="0.01" name="amount" id="pay_amount" required class="form-control" placeholder="Enter amount received" style="border: 1px solid var(--glass-border); background: transparent; color: var(--text-main);">
                </div>

                <div style="margin-bottom: 25px;">
                    <label style="font-size:0.85rem; font-weight:600; display:block; margin-bottom:5px;">Payment Method</label>
                    <select name="payment_method" required class="form-control" style="border: 1px solid var(--glass-border); background: var(--bg-color); color: var(--text-main);">
                        <option value="Cash">Cash (Over-the-counter)</option>
                        <option value="GCash">GCash</option>
                    </select>
                </div>
                
                <div style="display:flex; justify-content:flex-end; gap:10px;">
                    <button type="button" class="btn btn-outline" onclick="document.getElementById('paymentModal').style.display='none'">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Payment</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openPaymentModal(data) {
            document.getElementById('pay_enrollment_id').value = data.enrollment_id;
            document.getElementById('pay_student_name').value = data.first_name + ' ' + data.last_name;
            document.getElementById('pay_balance').value = parseFloat(data.balance).toFixed(2);
            document.getElementById('paymentModal').style.display = 'flex';
        }
    </script>
    <script src="../assets/js/main.js"></script>
</body>
</html>