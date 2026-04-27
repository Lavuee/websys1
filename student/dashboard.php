<?php
// Secures the page for student access only using the centralized authentication script.
require_once '../actions/auth.php';
check_student();

// Establishes the database connection.
require_once '../config/db.php';

try {
    // Retrieves the student's enrollment and personal data from the DB
    $stmt = $pdo->prepare("
        SELECT s.first_name, s.last_name, s.lrn, 
               e.status, e.total_assessment, e.balance, e.grade_level, e.strand, e.tracking_no,
               sy.year_string as school_year
        FROM enrollments e
        JOIN students s ON e.student_id = s.student_id
        JOIN school_years sy ON e.school_year_id = sy.school_year_id
        WHERE e.enrollment_id = :id
    ");
    $stmt->execute([':id' => $_SESSION['enrollment_id']]);
    $student = $stmt->fetch();

    if (!$student) {
        $student = [
            'first_name' => 'Student', 'last_name' => '', 'lrn' => 'N/A', 'tracking_no' => 'N/A',
            'status' => 'No Enrollment', 'total_assessment' => 0, 'balance' => 0,
            'grade_level' => 'N/A', 'strand' => 'N/A', 'school_year' => 'N/A'
        ];
    }
    
    $full_name = trim($student['first_name'] . ' ' . $student['last_name']);

} catch (\PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard | Pines NHS</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .layout { display: flex; min-height: 100vh; }
        .sidebar { width: 260px; border-right: 1px solid var(--glass-border); padding: 20px; display: flex; flex-direction: column; background: var(--glass-bg); backdrop-filter: blur(12px); }
        .main-content { flex: 1; padding: 40px; }
        .nav-link { display: block; padding: 12px 15px; margin-bottom: 5px; border-radius: 8px; color: var(--text-main); font-weight: 500; text-decoration: none; }
        .nav-link:hover, .nav-link.active { background: var(--primary-color); color: white; }
        .stat-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { padding: 25px 20px; display: flex; justify-content: space-between; align-items: flex-start; }
        .stat-card h3 { font-size: 1.8rem; margin-top: 10px; }
        .detail-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; font-size: 0.9rem; }
        .detail-item { margin-bottom: 15px; }
        .detail-label { color: var(--text-muted); font-size: 0.8rem; margin-bottom: 3px; display: block; }
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
                <a href="assessment.php" class="nav-link">Assessment & Payment</a>
                <a href="records.php" class="nav-link">My Records</a>
            </nav>
            <div style="border-top: 1px solid var(--glass-border); padding-top: 20px; margin-top: auto;">
                <p style="font-size: 0.85rem; margin-bottom: 10px; font-weight: 600;"><?= htmlspecialchars($_SESSION['user_email']) ?></p>
                <a href="../logout.php" class="text-muted" style="font-size: 0.85rem;">Sign Out</a>
            </div>
        </aside>

        <main class="main-content">
            <h2 style="margin-bottom: 5px;">Welcome, <?= htmlspecialchars($full_name) ?></h2>
            <p class="text-muted" style="margin-bottom: 30px; font-size: 0.9rem;">View your enrollment status and manage your account.</p>

            <div class="stat-grid">
                <div class="glass-panel stat-card">
                    <div>
                        <p class="text-muted" style="font-size: 0.85rem;">Enrollment Status</p>
                        <h3><?= htmlspecialchars($student['status']) ?></h3>
                    </div>
                </div>
                <div class="glass-panel stat-card">
                    <div>
                        <p class="text-muted" style="font-size: 0.85rem;">Total Assessment</p>
                        <h3>₱<?= number_format($student['total_assessment'], 2) ?></h3>
                    </div>
                </div>
                <div class="glass-panel stat-card">
                    <div>
                        <p class="text-muted" style="font-size: 0.85rem;">Balance</p>
                        <h3>₱<?= number_format($student['balance'], 2) ?></h3>
                    </div>
                </div>
            </div>

            <div class="glass-panel" style="padding: 25px;">
                <h3 style="font-size: 1.1rem; margin-bottom: 20px;">Enrollment Details</h3>
                <div class="detail-grid">
                    <div>
                        <div class="detail-item"><span class="detail-label">Full Name</span><?= htmlspecialchars($full_name) ?></div>
                        <div class="detail-item"><span class="detail-label">LRN</span><?= htmlspecialchars($student['lrn'] ?: 'N/A') ?></div>
                    </div>
                    <div>
                        <div class="detail-item"><span class="detail-label">Grade Level</span><?= htmlspecialchars($student['grade_level']) ?></div>
                        <div class="detail-item"><span class="detail-label">Status</span><?= htmlspecialchars($student['status']) ?></div>
                    </div>
                    <div>
                        <div class="detail-item"><span class="detail-label">Strand</span><?= htmlspecialchars($student['strand']) ?></div>
                    </div>
                    <div>
                        <div class="detail-item"><span class="detail-label">School Year</span><?= htmlspecialchars($student['school_year']) ?></div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    <script src="../assets/js/main.js"></script>
</body>
</html>