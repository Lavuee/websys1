<?php
require_once '../actions/auth.php';
check_registrar();
require_once '../config/db.php';

$searchQuery = trim($_GET['search'] ?? '');
$gradeFilter = $_GET['grade'] ?? '';

try {
    $query = "
        SELECT e.enrollment_id, e.tracking_no, e.status, e.grade_level, e.created_at, e.total_assessment,
               s.*, u.email as student_email 
        FROM enrollments e
        JOIN students s ON e.student_id = s.student_id
        JOIN users u ON s.user_id = u.user_id
        WHERE e.status = 'Pending'
    ";
    
    $params = [];
    if (!empty($gradeFilter)) {
        $query .= " AND e.grade_level = ?";
        $params[] = $gradeFilter;
    }
    if (!empty($searchQuery)) {
        $query .= " AND (s.first_name LIKE ? OR s.last_name LIKE ? OR u.email LIKE ? OR e.tracking_no LIKE ?)";
        $searchTerm = "%" . $searchQuery . "%";
        array_push($params, $searchTerm, $searchTerm, $searchTerm, $searchTerm);
    }
    
    $query .= " ORDER BY e.created_at ASC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $queue = $stmt->fetchAll();
} catch (\PDOException $e) {
    die("Database error: " . $e->getMessage());
}

$badgeColors = [
    'Pending'  => 'background: rgba(234, 179, 8, 0.15); color: #eab308; border: 1px solid rgba(234, 179, 8, 0.3);'
];

include 'includes/registrar_header.php'; 
?>
<body>
    <div class="layout">
        <?php include 'includes/registrar_sidebar.php'; ?>
        <main class="main-content">
            <h2 style="margin-bottom: 5px;">Enrollment Queue</h2>
            <p class="text-muted" style="margin-bottom: 30px; font-size: 0.9rem;">Review pending applications and assess student documents.</p>

            <div class="glass-panel">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 15px;">
                    <h3 style="font-size: 1.1rem; margin: 0; color: var(--text-main);">Pending Applications</h3>
                    <form method="GET" action="enrollment_queue.php" style="display: flex; align-items: center; gap: 15px; flex-wrap: wrap;">
                        <input type="text" name="search" placeholder="Search name, email, or tracking no..." value="<?= htmlspecialchars($searchQuery) ?>" style="padding: 8px 12px; border-radius: 8px; border: 1px solid var(--glass-border); background: var(--bg-color); color: var(--text-main); font-size: 0.85rem; width: 260px; outline: none;">
                        <select name="grade" onchange="this.form.submit()" style="padding: 8px 12px; border-radius: 8px; border: 1px solid var(--glass-border); background: var(--bg-color); color: var(--text-main); font-size: 0.85rem; cursor: pointer;">
                            <option value="">All Grades</option>
                            <option value="Grade 7" <?= $gradeFilter === 'Grade 7' ? 'selected' : '' ?>>Grade 7</option>
                            <option value="Grade 8" <?= $gradeFilter === 'Grade 8' ? 'selected' : '' ?>>Grade 8</option>
                            <option value="Grade 9" <?= $gradeFilter === 'Grade 9' ? 'selected' : '' ?>>Grade 9</option>
                            <option value="Grade 10" <?= $gradeFilter === 'Grade 10' ? 'selected' : '' ?>>Grade 10</option>
                            <option value="Grade 11" <?= $gradeFilter === 'Grade 11' ? 'selected' : '' ?>>Grade 11</option>
                            <option value="Grade 12" <?= $gradeFilter === 'Grade 12' ? 'selected' : '' ?>>Grade 12</option>
                        </select>
                        <button type="submit" style="display: none;"></button>
                    </form>
                </div>
                <div class="table-responsive">
                    <table class="table-wrapper">
                        <thead>
                            <tr>
                                <th>Student Name</th>
                                <th>Email</th>
                                <th>Grade</th>
                                <th>Date Applied</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($queue as $row): ?>
                                <tr>
                                    <td style="font-weight: 500;"><?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?></td>
                                    <td class="text-muted"><?= htmlspecialchars($row['student_email']) ?></td>
                                    <td><?= htmlspecialchars($row['grade_level']) ?></td>
                                    <td><?= date('M d, Y', strtotime($row['created_at'])) ?></td>
                                    <td>
                                        <span class="badge" style="<?= $badgeColors[$row['status']] ?? '' ?>">
                                            <?= htmlspecialchars($row['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-outline" style="padding: 5px 10px; font-size: 0.8rem;" onclick='openStatusModal(<?= json_encode($row) ?>)'>Update Status</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($queue)): ?>
                                <tr><td colspan="6" style="text-align: center; padding: 30px;">The enrollment queue is empty.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- Status Update Modal -->
    <div id="statusModal" class="modal-overlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:2000; justify-content:center; align-items:center;">
        <div class="glass-panel" style="width:100%; max-width:400px; padding:30px;">
            <h3 style="margin-bottom:20px;">Update Status</h3>
            <form action="../actions/update_status.php" method="POST">
                <input type="hidden" name="enrollment_id" id="m_id">
                <input type="hidden" name="return_to" value="enrollment_queue.php">
                
                <label style="font-size:0.85rem; font-weight:600;">Status</label>
                <select name="status" id="m_status" style="width:100%; padding:10px; margin:10px 0 25px; border-radius:8px; border:1px solid var(--glass-border); background:var(--bg-color); color:var(--text-main);">
                    <option value="Pending">Pending</option>
                    <option value="Assessed">Assessed</option>
                    <option value="Enrolled">Enrolled</option>
                    <option value="Rejected">Rejected</option>
                </select>
                
                <label style="font-size:0.85rem; font-weight:600;">Total Tuition Assessment (₱)</label>
                <input type="number" step="0.01" name="total_assessment" id="m_total" style="width:100%; padding:10px; margin:10px 0 25px; border-radius:8px; border:1px solid var(--glass-border); background:transparent; color:var(--text-main);">
                
                <div style="display:flex; justify-content:flex-end; gap:10px;">
                    <button type="button" class="btn btn-outline" onclick="document.getElementById('statusModal').style.display='none'">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Change</button>
                </div>
            </form>
        </div>
    </div>

    <script src="../assets/js/main.js"></script>
    <script>
        function openStatusModal(data) {
            document.getElementById('m_id').value = data.enrollment_id;
            document.getElementById('m_status').value = data.status;
            document.getElementById('m_total').value = data.total_assessment;
            document.getElementById('statusModal').style.display = 'flex';
        }
    </script>
</body>
</html>