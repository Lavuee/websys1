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
        .sidebar { width: 280px; border-right: 1px solid var(--glass-border); padding: 24px; display: flex; flex-direction: column; background: var(--glass-bg); backdrop-filter: blur(15px); position: sticky; top: 0; height: 100vh; z-index: 50;}
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
            <div class="logo-container" style="margin-bottom: 40px; display: flex; align-items: center; gap: 12px; font-weight: 800; font-size: 1.25rem; color: var(--text-main); letter-spacing: -0.5px;">
                <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" fill="var(--primary-color)" viewBox="0 0 16 16">
                    <path d="M8.211 2.047a.5.5 0 0 0-.422 0l-7.5 3.5a.5.5 0 0 0 .025.917l7.5 3a.5.5 0 0 0 .372 0L14 7.14V13a1 1 0 0 0-1 1v2h3v-2a1 1 0 0 0-1-1V6.739l.686-.275a.5.5 0 0 0 .025-.917z"/>
                    <path d="M4.176 9.032a.5.5 0 0 0-.656.327l-.5 1.7a.5.5 0 0 0 .294.605l4.5 1.8a.5.5 0 0 0 .372 0l4.5-1.8a.5.5 0 0 0 .294-.605l-.5-1.7a.5.5 0 0 0-.656-.327L8 10.466z"/>
                </svg>
                Pines NHS
            </div>
            <nav style="flex: 1;">
                <a href="dashboard.php" class="nav-link active">Dashboard</a>
                <a href="assessment.php" class="nav-link">Assessment & Payment</a>
                <a href="records.php" class="nav-link">My Records</a>
            </nav>
            <div style="border-top: 1px solid var(--glass-border); padding-top: 20px; margin-top: auto;">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <?php
                        $displayName = $_SESSION['student_name'] ?? 'Student';
                        $initial = strtoupper(substr(trim($displayName), 0, 1));
                    ?>
                    <div style="width: 42px; height: 42px; border-radius: 50%; background: var(--primary-color); color: #ffffff; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; font-weight: 700; flex-shrink: 0; box-shadow: 0 4px 10px rgba(16, 185, 129, 0.2);">
                        <?= $initial ?>
                    </div>
                    <div style="overflow: hidden;">
                        <p style="font-size: 0.85rem; margin: 0 0 3px 0; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: var(--text-main);">
                            <?= htmlspecialchars($displayName) ?>
                        </p>
                        <a href="../logout.php" class="text-muted" style="font-size: 0.8rem; text-decoration: none; transition: color 0.3s;" onmouseover="this.style.color='var(--primary-color)'" onmouseout="this.style.color='var(--text-muted)'">
                            Sign Out &rarr;
                        </a>
                    </div>
                </div>
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