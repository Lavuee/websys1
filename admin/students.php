<?php
// We use our teammate's auth script to lock this down for Admins only
require_once '../actions/auth.php';
check_admin();

// Connect to the database
require_once '../config/db.php';

// Define current page for navigation highlighting
$current_page = basename($_SERVER['PHP_SELF']);

try {
    // We are pulling all enrollments and joining them with the students and users tables
    $stmt = $pdo->query("
        SELECT e.enrollment_id, e.tracking_no, e.status, e.balance, e.total_assessment,
               s.first_name, s.last_name, 
               u.email as student_email 
        FROM enrollments e
        JOIN students s ON e.student_id = s.student_id
        JOIN users u ON s.user_id = u.user_id
        ORDER BY e.created_at DESC
    ");
    $students = $stmt->fetchAll();

} catch (\PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Management | Pines NHS</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        /* Keeping our UI layout exactly as we built it */
        .layout { display: flex; min-height: 100vh; }
        .sidebar { width: 260px; border-right: 1px solid var(--glass-border); padding: 20px; display: flex; flex-direction: column; background: var(--glass-bg); backdrop-filter: blur(12px); }
        .main-content { flex: 1; padding: 40px; position: relative; }
        
        .nav-link { display: block; padding: 12px; margin-bottom: 5px; border-radius: 8px; color: var(--text-main); text-decoration: none; font-weight: 500; transition: 0.2s; }
        .nav-link:hover, .nav-link.active { background: var(--primary-color); color: white; }
        
        .table-wrapper { width: 100%; border-collapse: collapse; text-align: left; margin-top: 15px; } 
        .table-wrapper th, .table-wrapper td { padding: 15px 10px; border-bottom: 1px solid var(--glass-border); font-size: 0.9rem; }
        
        .badge { padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; }
        .badge-pending { background: rgba(234, 179, 8, 0.2); color: #eab308; } 
        .badge-assessed { background: rgba(59, 130, 246, 0.2); color: #3b82f6; } 
        .badge-enrolled { background: rgba(34, 197, 94, 0.2); color: #22c55e; }
        .badge-rejected { background: rgba(239, 68, 68, 0.2); color: #ef4444; }

        .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); justify-content: center; align-items: center; z-index: 1000; }
        .modal { background: var(--bg-color); padding: 30px; border-radius: 12px; width: 400px; border: 1px solid var(--glass-border); }
        .form-control { width: 100%; padding: 10px; margin-top: 5px; margin-bottom: 15px; border-radius: 8px; border: 1px solid var(--glass-border); background: transparent; color: var(--text-main); }
    </style>
</head>
<body>
    <div class="layout">
        <aside class="sidebar">
            <div class="logo-container" style="margin-bottom: 40px; font-weight: 700; font-size: 1.2rem;">
                Pines NHS
            </div>
            <nav style="flex: 1;">
                <a href="dashboard.php" class="nav-link <?= $current_page == 'dashboard.php' ? 'active' : '' ?>">Dashboard</a>
                <a href="enrollment_period.php" class="nav-link <?= $current_page == 'enrollment_period.php' ? 'active' : '' ?>">Enrollment Periods</a>
                <a href="students.php" class="nav-link <?= $current_page == 'students.php' ? 'active' : '' ?>">Student Management</a>
                <a href="payments.php" class="nav-link <?= $current_page == 'payments.php' ? 'active' : '' ?>">Payment Verification</a>
                <a href="grades.php" class="nav-link <?= $current_page == 'grades.php' ? 'active' : '' ?>">Grade Management</a>
            </nav>
            <div style="border-top: 1px solid var(--glass-border); padding-top: 20px; margin-top: auto;">
                <p style="font-size: 0.85rem; margin-bottom: 10px; font-weight: 600;"><?= htmlspecialchars($_SESSION['user_email']) ?></p>
                <a href="../logout.php" class="text-muted" style="font-size: 0.85rem;">Sign Out</a>
            </div>
        </aside>

        <main class="main-content">
            <h2 style="margin-bottom: 5px;">Student Management</h2>
            <p class="text-muted" style="margin-bottom: 30px; font-size: 0.9rem;">Manage student enrollments and records.</p>

            <div class="glass-panel" style="padding: 25px;">
                <table class="table-wrapper">
                    <thead>
                        <tr><th>Name</th><th>Email</th><th>Tracking No.</th><th>Status</th><th>Balance</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $row): ?>
                            <tr>
                                <td style="font-weight: 500;"><?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?></td>
                                <td class="text-muted"><?= htmlspecialchars($row['student_email']) ?></td>
                                <td><?= htmlspecialchars($row['tracking_no']) ?></td>
                                <td><span class="badge badge-<?= strtolower($row['status']) ?>"><?= $row['status'] ?></span></td>
                                <td style="font-weight: 600;">₱<?= number_format($row['balance'], 2) ?></td>
                                <td>
                                    <button class="btn btn-outline" style="padding: 5px 10px; font-size: 0.8rem;" 
                                            onclick='openModal(<?= json_encode($row) ?>)'>
                                        ✎ Edit
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if(empty($students)): ?>
                            <tr><td colspan="6" style="text-align: center; padding: 20px;">No students found in the database.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <div id="editModal" class="modal-overlay">
        <div class="modal glass-panel">
            <h3 style="margin-bottom: 20px;">Edit Student Enrollment</h3>
            <form action="../actions/update_student.php" method="POST">
                <input type="hidden" name="enrollment_id" id="m_id">
                <label style="font-size: 0.85rem; font-weight: 500;">Status</label>
                <select name="status" id="m_status" class="form-control">
                    <option value="Pending">Pending</option>
                    <option value="Assessed">Assessed</option>
                    <option value="Enrolled">Enrolled</option>
                    <option value="Rejected">Rejected</option>
                </select>
                <label style="font-size: 0.85rem; font-weight: 500;">Total Tuition Assessment (₱)</label>
                <input type="number" step="0.01" name="total_assessment" id="m_total" class="form-control">
                <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 10px;">
                    <button type="button" class="btn btn-outline" onclick="document.getElementById('editModal').style.display='none'">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openModal(data) {
            document.getElementById('m_id').value = data.enrollment_id;
            document.getElementById('m_status').value = data.status;
            document.getElementById('m_total').value = data.total_assessment;
            document.getElementById('editModal').style.display = 'flex';
        }
    </script>
</body>
</html>