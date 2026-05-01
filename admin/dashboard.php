<?php
// Secures the dashboard to ensure only authorized administrators can access system data.
require_once '../actions/auth.php';
check_admin();

// Establishes a connection to the 'pines_ems' database.
require_once '../config/db.php';

try {
    // 1. Fetch Summary Statistics for the Stat Grid
    $countPending = $pdo->query("SELECT COUNT(*) FROM enrollments WHERE status = 'Pending'")->fetchColumn();
    $countAssessed = $pdo->query("SELECT COUNT(*) FROM enrollments WHERE status = 'Assessed'")->fetchColumn();
    $countEnrolled = $pdo->query("SELECT COUNT(*) FROM enrollments WHERE status = 'Enrolled'")->fetchColumn();
    $countPayments = $pdo->query("SELECT COUNT(*) FROM payments WHERE status = 'Pending'")->fetchColumn();

    // 2. Retrieve the 5 most recent pending student registrations
    $recentRegistrations = $pdo->query("
        SELECT s.first_name, s.last_name, u.email as student_email, e.grade_level, e.created_at, e.status 
        FROM enrollments e
        JOIN students s ON e.student_id = s.student_id
        JOIN users u ON s.user_id = u.user_id
        WHERE e.status = 'Pending' 
        ORDER BY e.created_at DESC LIMIT 5
    ")->fetchAll();

    // 3. Retrieve the 5 most recent pending payments for verification
    $recentPayments = $pdo->query("
        SELECT u.email as student_email, p.payment_method, p.amount, p.payment_date 
        FROM payments p
        JOIN enrollments e ON p.enrollment_id = e.enrollment_id
        JOIN students s ON e.student_id = s.student_id
        JOIN users u ON s.user_id = u.user_id
        WHERE p.status = 'Pending' 
        ORDER BY p.payment_date DESC LIMIT 5
    ")->fetchAll();

} catch (\PDOException $e) {
    // Halts execution if a database error occurs to prevent displaying incomplete data.
    die("Database error: " . $e->getMessage());
}

// Includes the modular header which handles the HTML head, CSS linking, and theme persistence.
include 'includes/admin_header.php'; 
?>
<body>
    <div class="layout">
        
        <?php include 'includes/sidebar.php'; ?>

        <main class="main-content">
            <h2 style="margin-bottom: 5px;">Admin Dashboard</h2>
            <p class="text-muted" style="margin-bottom: 30px; font-size: 0.9rem;">Overview of enrollment and payment activity.</p>

            <div class="stat-grid">
                <div class="glass-panel">
                    <p class="text-muted" style="font-size: 0.85rem;">Pending Registrations</p>
                    <h3 style="font-size: 2rem; margin-top: 10px;"><?= number_format($countPending) ?></h3>
                </div>
                <div class="glass-panel">
                    <p class="text-muted" style="font-size: 0.85rem;">Assessed Students</p>
                    <h3 style="font-size: 2rem; margin-top: 10px;"><?= number_format($countAssessed) ?></h3>
                </div>
                <div class="glass-panel">
                    <p class="text-muted" style="font-size: 0.85rem;">Fully Enrolled</p>
                    <h3 style="font-size: 2rem; margin-top: 10px;"><?= number_format($countEnrolled) ?></h3>
                </div>
                <div class="glass-panel">
                    <p class="text-muted" style="font-size: 0.85rem;">Pending Payments</p>
                    <h3 style="font-size: 2rem; margin-top: 10px;"><?= number_format($countPayments) ?></h3>
                </div>
            </div>

            <div class="glass-panel" style="margin-bottom: 30px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                    <h3 style="font-size: 1.1rem;">Recent Pending Registrations</h3>
                    <a href="students.php" class="text-primary" style="font-size: 0.85rem; font-weight: 500; text-decoration: none;">View All</a>
                </div>
                <table class="table-wrapper">
                    <thead>
                        <tr>
                            <th>Student Name</th>
                            <th>Email Address</th>
                            <th>Grade Level</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recentRegistrations)): ?>
                            <tr><td colspan="4" style="text-align: center; padding: 20px;">No pending registrations found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($recentRegistrations as $row): ?>
                                <tr>
                                    <td style="font-weight: 500;"><?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?></td>
                                    <td class="text-muted"><?= htmlspecialchars($row['student_email']) ?></td>
                                    <td><?= htmlspecialchars($row['grade_level']) ?></td>
                                    <td><span class="badge badge-pending">Pending</span></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="glass-panel">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                    <h3 style="font-size: 1.1rem;">Awaiting Payment Verification</h3>
                    <a href="payments.php" class="text-primary" style="font-size: 0.85rem; font-weight: 500; text-decoration: none;">View All</a>
                </div>
                <table class="table-wrapper">
                    <thead>
                        <tr>
                            <th>Student Email</th>
                            <th>Method</th>
                            <th>Amount</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recentPayments)): ?>
                            <tr><td colspan="4" style="text-align: center; padding: 20px;">All payments have been processed.</td></tr>
                        <?php else: ?>
                            <?php foreach ($recentPayments as $row): ?>
                                <tr>
                                    <td style="font-weight: 500;"><?= htmlspecialchars($row['student_email']) ?></td>
                                    <td><span class="badge badge-gcash"><?= htmlspecialchars($row['payment_method']) ?></span></td>
                                    <td style="font-weight: 700;">₱<?= number_format($row['amount'], 2) ?></td>
                                    <td><?= date('M d, Y', strtotime($row['payment_date'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
    
    <script src="../assets/js/main.js"></script>
</body>
</html>