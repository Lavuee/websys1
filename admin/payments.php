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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Verification | Pines NHS</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        /* Core administrative layout styling */
        .layout { display: flex; min-height: 100vh; }
        .sidebar { width: 260px; border-right: 1px solid var(--glass-border); padding: 20px; display: flex; flex-direction: column; background: var(--glass-bg); backdrop-filter: blur(12px); }
        .main-content { flex: 1; padding: 40px; position: relative; }
        
        /* Sidebar navigation styling */
        .nav-link { display: block; padding: 12px 15px; margin-bottom: 5px; border-radius: 8px; color: var(--text-main); font-weight: 500; text-decoration: none; }
        .nav-link:hover, .nav-link.active { background: var(--primary-color); color: white; }
        
        /* Table layout and typography */
        .table-wrapper { width: 100%; border-collapse: collapse; margin-top: 15px; text-align: left; }
        .table-wrapper th, .table-wrapper td { padding: 15px 10px; border-bottom: 1px solid var(--glass-border); font-size: 0.9rem; }
        
        /* Status badge indicators */
        .badge { padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; }
        .badge-pending { background: rgba(234, 179, 8, 0.2); color: #eab308; }
        .badge-verified { background: rgba(34, 197, 94, 0.2); color: #22c55e; }
        .badge-rejected { background: rgba(239, 68, 68, 0.2); color: #ef4444; }
        .badge-gcash { background: rgba(59, 130, 246, 0.2); color: #3b82f6; }
        .badge-cash { background: rgba(100, 116, 139, 0.2); color: #64748b; }
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
                <a href="dashboard.php" class="nav-link">Dashboard</a>
                <a href="students.php" class="nav-link">Student Management</a>
                <a href="payments.php" class="nav-link active">Payment Verification</a>
                <a href="grades.php" class="nav-link">Grade Management</a>
            </nav>
            
            <div style="border-top: 1px solid var(--glass-border); padding-top: 20px; margin-top: auto;">
                <p style="font-size: 0.85rem; margin-bottom: 10px; font-weight: 600;"><?= htmlspecialchars($_SESSION['user_email']) ?></p>
                <a href="../logout.php" class="text-muted" style="font-size: 0.85rem;">Sign Out</a>
            </div>
        </aside>

        <main class="main-content">
            <h2 style="margin-bottom: 5px;">Payment Verification</h2>
            <p class="text-muted" style="margin-bottom: 30px; font-size: 0.9rem;">Review, approve, or reject student financial transactions.</p>

            <div class="glass-panel" style="padding: 25px;">
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
                                <td><span class="badge badge-<?= strtolower($row['payment_method']) ?>"><?= htmlspecialchars($row['payment_method']) ?></span></td>
                                <td style="font-weight: 600;">₱<?= number_format($row['amount'], 2) ?></td>
                                <td><span class="badge badge-<?= strtolower($row['status']) ?>"><?= htmlspecialchars($row['status']) ?></span></td>
                                <td><?= date('m/d/Y', strtotime($row['payment_date'])) ?></td>
                                <td>
                                    <?php if ($row['status'] === 'Pending'): ?>
                                        <form action="../actions/verify_payment.php" method="POST" style="display:inline;">
                                            <input type="hidden" name="payment_id" value="<?= $row['payment_id'] ?>">
                                            <input type="hidden" name="enrollment_id" value="<?= $row['enrollment_id'] ?>">
                                            
                                            <button type="submit" name="action" value="Approve" class="btn btn-primary" style="padding: 6px 12px; font-size: 0.8rem; margin-right: 5px;">Approve</button>
                                            <button type="submit" name="action" value="Reject" class="btn btn-outline" style="padding: 6px 12px; font-size: 0.8rem; color: #ef4444; border-color: #ef4444;">Reject</button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-muted" style="font-size: 0.85rem;">Processed</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if(empty($payments)): ?>
                            <tr><td colspan="6" style="text-align: center; padding: 20px;">No financial transactions found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <script src="../assets/js/main.js"></script>
</body>
</html>