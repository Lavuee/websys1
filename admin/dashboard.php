<?php
// We are making sure only admins can access this page using our teammate's function
require_once '../actions/auth.php';
check_admin(); 

// Let's pull in our database connection
require_once '../config/db.php';

try {
    $stmtPending = $pdo->query("SELECT COUNT(*) FROM enrollments WHERE status = 'Pending'");
    $countPending = $stmtPending->fetchColumn();

    $stmtAssessed = $pdo->query("SELECT COUNT(*) FROM enrollments WHERE status = 'Assessed'");
    $countAssessed = $stmtAssessed->fetchColumn();

    $stmtEnrolled = $pdo->query("SELECT COUNT(*) FROM enrollments WHERE status = 'Enrolled'");
    $countEnrolled = $stmtEnrolled->fetchColumn();

    $stmtPayments = $pdo->query("SELECT COUNT(*) FROM payments WHERE status = 'Pending'");
    $countPayments = $stmtPayments->fetchColumn();

    $recentRegistrations = $pdo->query("
        SELECT s.first_name, s.last_name, u.email as student_email, e.grade_level, e.created_at, e.status 
        FROM enrollments e
        JOIN students s ON e.student_id = s.student_id
        JOIN users u ON s.user_id = u.user_id
        WHERE e.status = 'Pending' 
        ORDER BY e.created_at DESC LIMIT 5
    ")->fetchAll();

    $recentPayments = $pdo->query("
        SELECT u.email as student_email, p.payment_method, p.amount, p.payment_date as created_at 
        FROM payments p
        JOIN enrollments e ON p.enrollment_id = e.enrollment_id
        JOIN students s ON e.student_id = s.student_id
        JOIN users u ON s.user_id = u.user_id
        WHERE p.status = 'Pending' 
        ORDER BY p.payment_date DESC LIMIT 5
    ")->fetchAll();

} catch (\PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | Pines NHS</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .layout { display: flex; min-height: 100vh; }
        .sidebar { width: 260px; border-right: 1px solid var(--glass-border); padding: 20px; display: flex; flex-direction: column; background: var(--glass-bg); backdrop-filter: blur(12px); }
        .main-content { flex: 1; padding: 40px; }
        .nav-link { display: block; padding: 12px 15px; margin-bottom: 5px; border-radius: 8px; color: var(--text-main); font-weight: 500; text-decoration: none; }
        .nav-link:hover, .nav-link.active { background: var(--primary-color); color: white; }
        .stat-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 40px; }
        .stat-card { padding: 25px 20px; display: flex; justify-content: space-between; align-items: flex-start; }
        .stat-card h3 { font-size: 2rem; margin-top: 10px; }
        .table-wrapper { width: 100%; border-collapse: collapse; margin-top: 15px; text-align: left; }
        .table-wrapper th, .table-wrapper td { padding: 15px 10px; border-bottom: 1px solid var(--glass-border); font-size: 0.9rem; }
        .badge { padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; }
        .badge-pending { background: rgba(234, 179, 8, 0.2); color: #eab308; }
        .badge-gcash { background: rgba(59, 130, 246, 0.2); color: #3b82f6; }
    </style>
</head>
<body>
    <div class="layout">
        <aside class="sidebar">
            <div class="logo-container" style="margin-bottom: 40px;">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-primary"><path d="M8 19h8a4 4 0 0 0 3.8-2.8l2-7A4 4 0 0 0 18 5h-1.5c-.8 0-1.5.3-2 1l-2.5 3.5"/></svg>
                Pines NHS
            </div>
            <nav style="flex: 1;">
                <a href="dashboard.php" class="nav-link active">Dashboard</a>
                <a href="students.php" class="nav-link">Student Management</a>
                <a href="payments.php" class="nav-link">Payment Verification</a>
                <a href="grades.php" class="nav-link">Grade Management</a>
            </nav>
            <div style="border-top: 1px solid var(--glass-border); padding-top: 20px; margin-top: auto;">
                <p style="font-size: 0.85rem; margin-bottom: 10px; font-weight: 600;"><?= htmlspecialchars($_SESSION['user_email']) ?></p>
                <a href="../logout.php" class="text-muted" style="font-size: 0.85rem;">Sign Out</a>
            </div>
        </aside>

        <main class="main-content">
            <h2 style="margin-bottom: 5px;">Admin Dashboard</h2>
            <p class="text-muted" style="margin-bottom: 30px; font-size: 0.9rem;">Overview of enrollment and payment activity.</p>

            <div class="stat-grid">
                <div class="glass-panel stat-card">
                    <div>
                        <p class="text-muted" style="font-size: 0.85rem;">Pending Registrations</p>
                        <h3><?= number_format($countPending) ?></h3>
                    </div>
                </div>
                <div class="glass-panel stat-card">
                    <div>
                        <p class="text-muted" style="font-size: 0.85rem;">Assessed</p>
                        <h3><?= number_format($countAssessed) ?></h3>
                    </div>
                </div>
                <div class="glass-panel stat-card">
                    <div>
                        <p class="text-muted" style="font-size: 0.85rem;">Enrolled</p>
                        <h3><?= number_format($countEnrolled) ?></h3>
                    </div>
                </div>
                <div class="glass-panel stat-card">
                    <div>
                        <p class="text-muted" style="font-size: 0.85rem;">Pending Payments</p>
                        <h3><?= number_format($countPayments) ?></h3>
                    </div>
                </div>
            </div>

            <div class="glass-panel" style="padding: 25px; margin-bottom: 30px;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <h3 style="font-size: 1.1rem;">Recent Pending Registrations</h3>
                    <a href="students.php" class="text-primary" style="font-size: 0.85rem; font-weight: 500; text-decoration: none;">View All</a>
                </div>
                <table class="table-wrapper">
                    <thead>
                        <tr><th>Name</th><th>Email</th><th>Grade</th><th>Date</th><th>Status</th></tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recentRegistrations)): ?>
                            <tr><td colspan="5" class="text-muted" style="text-align: center;">No pending registrations right now.</td></tr>
                        <?php else: ?>
                            <?php foreach ($recentRegistrations as $row): ?>
                                <tr>
                                    <td style="font-weight: 500;"><?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?></td>
                                    <td class="text-muted"><?= htmlspecialchars($row['student_email']) ?></td>
                                    <td><?= htmlspecialchars($row['grade_level']) ?></td>
                                    <td><?= date('n/j/Y', strtotime($row['created_at'])) ?></td>
                                    <td><span class="badge badge-pending">Pending</span></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="glass-panel" style="padding: 25px;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <h3 style="font-size: 1.1rem;">Pending Payment Verifications</h3>
                    <a href="payments.php" class="text-primary" style="font-size: 0.85rem; font-weight: 500; text-decoration: none;">View All</a>
                </div>
                <table class="table-wrapper">
                    <thead>
                        <tr><th>Student Email</th><th>Method</th><th>Amount</th><th>Date</th></tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recentPayments)): ?>
                            <tr><td colspan="4" class="text-muted" style="text-align: center;">No pending payments right now.</td></tr>
                        <?php else: ?>
                            <?php foreach ($recentPayments as $row): ?>
                                <tr>
                                    <td style="font-weight: 500;"><?= htmlspecialchars($row['student_email']) ?></td>
                                    <td><span class="badge badge-gcash"><?= htmlspecialchars($row['payment_method']) ?></span></td>
                                    <td style="font-weight: 600;">₱<?= number_format($row['amount'], 2) ?></td>
                                    <td><?= date('n/j/Y', strtotime($row['created_at'])) ?></td>
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