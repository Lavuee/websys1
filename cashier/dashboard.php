<?php
require_once '../actions/auth.php';
check_cashier();

require_once '../config/db.php';

try {
    // Fetch Cashier-specific statistics
    $countPending = $pdo->query("SELECT COUNT(*) FROM payments WHERE status = 'Pending'")->fetchColumn();
    $countVerified = $pdo->query("SELECT COUNT(*) FROM payments WHERE status = 'Verified'")->fetchColumn();
    $totalVerifiedAmount = $pdo->query("SELECT SUM(amount) FROM payments WHERE status = 'Verified'")->fetchColumn();

    // Fetch assessed students awaiting payment
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

    // Fetch pending payments
    $stmt = $pdo->query("
        SELECT p.payment_id, p.enrollment_id, p.amount, p.payment_method, p.status, p.payment_date,
               s.first_name, s.last_name, 
               u.email AS student_email 
        FROM payments p 
        JOIN enrollments e ON p.enrollment_id = e.enrollment_id 
        JOIN students s ON e.student_id = s.student_id
        JOIN users u ON s.user_id = u.user_id
        WHERE p.status = 'Pending'
        ORDER BY p.payment_date ASC
    ");
    $payments = $stmt->fetchAll();

} catch (\PDOException $e) {
    die("Database error: " . $e->getMessage());
}

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
            <h2 style="margin-bottom: 5px;">Cashier Dashboard</h2>
            <p class="text-muted" style="margin-bottom: 30px; font-size: 0.9rem;">Review and process pending student payments.</p>

            <div class="stat-grid">
                <div class="glass-panel" style="border-top: 4px solid #eab308;">
                    <p class="text-muted" style="font-size: 0.85rem; font-weight: 600; text-transform: uppercase;">Pending Verifications</p>
                    <h3 style="font-size: 2.2rem; margin-top: 10px;"><?= number_format($countPending) ?></h3>
                </div>
                <div class="glass-panel" style="border-top: 4px solid #22c55e;">
                    <p class="text-muted" style="font-size: 0.85rem; font-weight: 600; text-transform: uppercase;">Verified Transactions</p>
                    <h3 style="font-size: 2.2rem; margin-top: 10px;"><?= number_format($countVerified) ?></h3>
                </div>
                <div class="glass-panel" style="border-top: 4px solid var(--primary-color);">
                    <p class="text-muted" style="font-size: 0.85rem; font-weight: 600; text-transform: uppercase;">Total Collected</p>
                    <h3 style="font-size: 2.2rem; margin-top: 10px;">₱<?= number_format((float)$totalVerifiedAmount, 2) ?></h3>
                </div>
            </div>

            <div class="glass-panel">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                    <h3 style="font-size: 1.1rem;">Awaiting Verification</h3>
                </div>
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
                                    <span class="badge" style="background: rgba(234, 179, 8, 0.15); color: #eab308; border: 1px solid rgba(234, 179, 8, 0.3);">
                                        <?= htmlspecialchars($row['status']) ?>
                                    </span>
                                </td>
                                <td><?= date('m/d/Y', strtotime($row['payment_date'])) ?></td>
                                <td>
                                    <form action="../actions/verify_payment.php" method="POST" style="display: flex; gap: 8px;">
                                        <input type="hidden" name="payment_id" value="<?= $row['payment_id'] ?>">
                                        <input type="hidden" name="enrollment_id" value="<?= $row['enrollment_id'] ?>">
                                        
                                        <button type="submit" name="action" value="Approve" class="btn btn-primary" style="padding: 6px 12px; font-size: 0.8rem;" onclick="return confirm('Are you sure you want to approve this payment?');">Approve</button>
                                        <button type="submit" name="action" value="Reject" class="btn btn-outline" style="padding: 6px 12px; font-size: 0.8rem; color: #ef4444; border-color: rgba(239, 68, 68, 0.4);" onclick="return confirm('Are you sure you want to reject this payment?');">Reject</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        
                        <?php if (empty($payments)): ?>
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 30px; color: var(--text-muted);">
                                    No pending payments at this time.
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

        function toggleTheme() {
            const html = document.documentElement;
            const checkbox = document.getElementById('theme-toggle-checkbox');
            if (html.hasAttribute('data-theme')) {
                html.removeAttribute('data-theme');
                localStorage.setItem('theme', 'light');
                if (checkbox) checkbox.checked = false;
            } else {
                html.setAttribute('data-theme', 'dark');
                localStorage.setItem('theme', 'dark');
                if (checkbox) checkbox.checked = true;
            }
        }
    </script>
    <script src="../assets/js/main.js"></script>
</body>
</html>