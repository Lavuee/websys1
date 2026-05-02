<?php
require_once '../actions/auth.php';
check_registrar();
require_once '../config/db.php';

try {
    // 1. Fetch Registrar-specific statistics
    $countStudents = $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();
    $countPending = $pdo->query("SELECT COUNT(*) FROM enrollments WHERE status = 'Pending'")->fetchColumn();
    $countAssessed = $pdo->query("SELECT COUNT(*) FROM enrollments WHERE status = 'Assessed'")->fetchColumn();
    $countEnrolled = $pdo->query("SELECT COUNT(*) FROM enrollments WHERE status = 'Enrolled'")->fetchColumn();

    // 2. Fetch recent enrollments to process
    $recentRegistrations = $pdo->query("
        SELECT s.first_name, s.last_name, u.email as student_email, e.grade_level, e.created_at, e.status 
        FROM enrollments e
        JOIN students s ON e.student_id = s.student_id
        JOIN users u ON s.user_id = u.user_id
        ORDER BY e.created_at DESC LIMIT 6
    ")->fetchAll();

} catch (\PDOException $e) {
    die("Database error: " . $e->getMessage());
}

include 'includes/registrar_header.php'; 
?>
<body>
    <div class="layout">
        
        <?php include 'includes/registrar_sidebar.php'; ?>

        <main class="main-content">
            <h2 style="margin-bottom: 5px;">Registrar Dashboard</h2>
            <p class="text-muted" style="margin-bottom: 30px; font-size: 0.9rem;">Overview of student records and academic enrollments.</p>

            <div class="stat-grid">
                <div class="glass-panel" style="border-top: 4px solid var(--primary-color);">
                    <p class="text-muted" style="font-size: 0.85rem; font-weight: 600; text-transform: uppercase;">Total Students</p>
                    <h3 style="font-size: 2.2rem; margin-top: 10px;"><?= number_format($countStudents) ?></h3>
                </div>
                <div class="glass-panel" style="border-top: 4px solid #eab308;">
                    <p class="text-muted" style="font-size: 0.85rem; font-weight: 600; text-transform: uppercase;">Pending Registrations</p>
                    <h3 style="font-size: 2.2rem; margin-top: 10px;"><?= number_format($countPending) ?></h3>
                </div>
                <div class="glass-panel" style="border-top: 4px solid #3b82f6;">
                    <p class="text-muted" style="font-size: 0.85rem; font-weight: 600; text-transform: uppercase;">Assessed Students</p>
                    <h3 style="font-size: 2.2rem; margin-top: 10px;"><?= number_format($countAssessed) ?></h3>
                </div>
                <div class="glass-panel" style="border-top: 4px solid #22c55e;">
                    <p class="text-muted" style="font-size: 0.85rem; font-weight: 600; text-transform: uppercase;">Officially Enrolled</p>
                    <h3 style="font-size: 2.2rem; margin-top: 10px;"><?= number_format($countEnrolled) ?></h3>
                </div>
            </div>

            <div class="glass-panel" style="margin-bottom: 30px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                    <h3 style="font-size: 1.1rem;">Recent Enrollments</h3>
                    <a href="enrollment_queue.php" class="text-primary" style="font-size: 0.85rem; font-weight: 500; text-decoration: none;">View All Records</a>
                </div>
                <div class="table-responsive">
                    <table class="table-wrapper">
                        <thead>
                            <tr>
                                <th>Student Name</th>
                                <th>Email Address</th>
                                <th>Grade Level</th>
                                <th>Status</th>
                                <th>Date Applied</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recentRegistrations)): ?>
                                <tr><td colspan="5" style="text-align: center; padding: 20px;">No recent enrollments found.</td></tr>
                            <?php else: ?>
                                <?php foreach ($recentRegistrations as $row): ?>
                                    <tr>
                                        <td style="font-weight: 500;"><?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?></td>
                                        <td class="text-muted"><?= htmlspecialchars($row['student_email']) ?></td>
                                        <td><?= htmlspecialchars($row['grade_level']) ?></td>
                                        <td>
                                            <?php
                                                $badgeStyle = '';
                                                switch($row['status']) {
                                                    case 'Pending':  $badgeStyle = 'background: rgba(234, 179, 8, 0.15); color: #eab308; border: 1px solid rgba(234, 179, 8, 0.3);'; break;
                                                    case 'Assessed': $badgeStyle = 'background: rgba(59, 130, 246, 0.15); color: #3b82f6; border: 1px solid rgba(59, 130, 246, 0.3);'; break;
                                                    case 'Enrolled': $badgeStyle = 'background: rgba(34, 197, 94, 0.15); color: #22c55e; border: 1px solid rgba(34, 197, 94, 0.3);'; break;
                                                    case 'Rejected': $badgeStyle = 'background: rgba(239, 68, 68, 0.15); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.3);'; break;
                                                }
                                            ?>
                                            <span class="badge" style="<?= $badgeStyle ?>">
                                                <?= htmlspecialchars($row['status']) ?>
                                            </span>
                                        </td>
                                        <td><?= date('M d, Y', strtotime($row['created_at'])) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </main>
    </div>
    
    <script src="../assets/js/main.js"></script>
</body>
</html>