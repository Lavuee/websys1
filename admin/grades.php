<?php
// Secures the module strictly for administrative access.
require_once '../actions/auth.php';
check_admin();

// Establishes the database connection.
require_once '../config/db.php';

try {
    // Constructs a comprehensive query to join grades with student and subject data.
    $stmt = $pdo->query("
        SELECT g.grade_id, g.enrollment_id, g.subject_id, g.q1, g.q2, g.q3, g.q4, g.final_grade, g.remarks,
               s.first_name, s.last_name, 
               u.email AS student_email,
               sub.subject_name
        FROM grades g 
        JOIN enrollments e ON g.enrollment_id = e.enrollment_id 
        JOIN students s ON e.student_id = s.student_id
        JOIN users u ON s.user_id = u.user_id
        JOIN subjects sub ON g.subject_id = sub.subject_id
        ORDER BY s.last_name ASC, sub.subject_name ASC
    ");
    $grades = $stmt->fetchAll();

    // Retrieves active enrollments to populate the student selection dropdown.
    $studentStmt = $pdo->query("
        SELECT e.enrollment_id, s.first_name, s.last_name, e.grade_level 
        FROM enrollments e 
        JOIN students s ON e.student_id = s.student_id 
        WHERE e.status IN ('Enrolled', 'Assessed')
    ");
    $enrolled_students = $studentStmt->fetchAll();

    // Retrieves the academic catalog to populate the subject selection dropdown.
    $subjectStmt = $pdo->query("SELECT subject_id, subject_name, grade_level FROM subjects ORDER BY subject_name ASC");
    $subjects = $subjectStmt->fetchAll();

} catch (\PDOException $e) {
    // Halts execution upon database failure to maintain system stability.
    die("Database error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grade Management | Pines NHS</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        /* Core administrative layout elements */
        .layout { display: flex; min-height: 100vh; }
        .sidebar { width: 260px; border-right: 1px solid var(--glass-border); padding: 20px; display: flex; flex-direction: column; background: var(--glass-bg); backdrop-filter: blur(12px); }
        .main-content { flex: 1; padding: 40px; position: relative; }
        
        .nav-link { display: block; padding: 12px 15px; margin-bottom: 5px; border-radius: 8px; color: var(--text-main); font-weight: 500; text-decoration: none; }
        .nav-link:hover, .nav-link.active { background: var(--primary-color); color: white; }
        
        .table-wrapper { width: 100%; border-collapse: collapse; margin-top: 15px; text-align: left; }
        .table-wrapper th, .table-wrapper td { padding: 15px 10px; border-bottom: 1px solid var(--glass-border); font-size: 0.9rem; }
        
        .badge { padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; }
        .badge-passed { border: 1px solid var(--glass-border); color: var(--text-main); }
        .badge-failed { background: rgba(239, 68, 68, 0.2); color: #ef4444; }

        /* Modal interface styling */
        .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center; }
        .modal { background: var(--bg-color); padding: 30px; border-radius: 12px; width: 100%; max-width: 500px; border: 1px solid var(--glass-border); box-shadow: 0 10px 25px rgba(0,0,0,0.2); }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; font-size: 0.85rem; font-weight: 500; margin-bottom: 5px; }
        .form-control { width: 100%; padding: 10px; border-radius: 8px; border: 1px solid var(--glass-border); background: transparent; color: var(--text-main); }
        .form-control:focus { outline: 2px solid var(--primary-color); }
        .form-row { display: flex; gap: 10px; }
        .form-row > div { flex: 1; }
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
                <a href="payments.php" class="nav-link">Payment Verification</a>
                <a href="grades.php" class="nav-link active">Grade Management</a>
            </nav>
            <div style="border-top: 1px solid var(--glass-border); padding-top: 20px; margin-top: auto;">
                <p style="font-size: 0.85rem; margin-bottom: 10px; font-weight: 600;"><?= htmlspecialchars($_SESSION['user_email']) ?></p>
                <a href="../logout.php" class="text-muted" style="font-size: 0.85rem;">Sign Out</a>
            </div>
        </aside>

        <main class="main-content">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
                <div>
                    <h2 style="margin-bottom: 5px;">Grade Management</h2>
                    <p class="text-muted" style="font-size: 0.9rem;">Maintain academic records and input quarterly assessments.</p>
                </div>
                <button class="btn btn-primary" onclick="openModal('add')">+ Record Grade</button>
            </div>

            <div class="glass-panel" style="padding: 25px;">
                <table class="table-wrapper">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Subject</th>
                            <th>Q1</th>
                            <th>Q2</th>
                            <th>Q3</th>
                            <th>Q4</th>
                            <th>Final</th>
                            <th>Remarks</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($grades as $row): ?>
                            <tr>
                                <td>
                                    <div style="font-weight: 500;"><?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?></div>
                                    <div class="text-muted" style="font-size: 0.8rem;"><?= htmlspecialchars($row['student_email']) ?></div>
                                </td>
                                <td style="font-weight: 500;"><?= htmlspecialchars($row['subject_name']) ?></td>
                                <td><?= $row['q1'] ?? '-' ?></td>
                                <td><?= $row['q2'] ?? '-' ?></td>
                                <td><?= $row['q3'] ?? '-' ?></td>
                                <td><?= $row['q4'] ?? '-' ?></td>
                                <td style="font-weight: 700;"><?= $row['final_grade'] ?? '-' ?></td>
                                <td>
                                    <?php if($row['remarks']): ?>
                                        <span class="badge badge-<?= strtolower($row['remarks']) ?>"><?= htmlspecialchars($row['remarks']) ?></span>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button class="btn btn-outline" style="padding: 5px 10px; font-size: 0.8rem;" 
                                        onclick='openModal("edit", <?= json_encode($row) ?>)'>
                                        ✎ Edit
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if(empty($grades)): ?>
                            <tr><td colspan="9" style="text-align: center; padding: 20px;">No academic records have been posted.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <div id="gradeModal" class="modal-overlay">
        <div class="modal glass-panel">
            <div style="display: flex; justify-content: space-between; margin-bottom: 20px;">
                <h3 id="modalTitle">Record Grade</h3>
                <button type="button" onclick="closeModal()" style="background: none; border: none; font-size: 1.2rem; cursor: pointer; color: var(--text-main);">&times;</button>
            </div>
            
            <form action="../actions/save_grade.php" method="POST">
                <input type="hidden" name="grade_id" id="modalGradeId">

                <div class="form-group" id="studentSelectGroup">
                    <label>Student Enrollment</label>
                    <select name="enrollment_id" id="modalEnrollmentId" class="form-control" required>
                        <option value="">Select Student...</option>
                        <?php foreach($enrolled_students as $st): ?>
                            <option value="<?= $st['enrollment_id'] ?>">
                                <?= htmlspecialchars($st['first_name'] . ' ' . $st['last_name']) ?> (<?= htmlspecialchars($st['grade_level']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Academic Subject</label>
                    <select name="subject_id" id="modalSubjectId" class="form-control" required>
                        <option value="">Select Subject...</option>
                        <?php foreach($subjects as $sub): ?>
                            <option value="<?= $sub['subject_id'] ?>">
                                <?= htmlspecialchars($sub['subject_name']) ?> (<?= htmlspecialchars($sub['grade_level']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-row">
                    <div class="form-group"><label>Q1</label><input type="number" step="0.01" name="q1" id="modalQ1" class="form-control"></div>
                    <div class="form-group"><label>Q2</label><input type="number" step="0.01" name="q2" id="modalQ2" class="form-control"></div>
                    <div class="form-group"><label>Q3</label><input type="number" step="0.01" name="q3" id="modalQ3" class="form-control"></div>
                    <div class="form-group"><label>Q4</label><input type="number" step="0.01" name="q4" id="modalQ4" class="form-control"></div>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
                    <button type="button" class="btn btn-outline" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Record</button>
                </div>
            </form>
        </div>
    </div>

    <script src="../assets/js/main.js"></script>
    <script>
        const modal = document.getElementById('gradeModal');
        
        // Populates the modal fields dynamically based on the requested operation type.
        function openModal(type, data = null) {
            document.getElementById('modalTitle').innerText = type === 'add' ? 'Record Grade' : 'Modify Grade';
            
            if (type === 'edit') {
                document.getElementById('modalGradeId').value = data.grade_id;
                document.getElementById('modalEnrollmentId').value = data.enrollment_id;
                document.getElementById('modalEnrollmentId').style.pointerEvents = 'none'; 
                document.getElementById('modalEnrollmentId').style.opacity = '0.6';
                document.getElementById('modalSubjectId').value = data.subject_id;
                document.getElementById('modalQ1').value = data.q1;
                document.getElementById('modalQ2').value = data.q2;
                document.getElementById('modalQ3').value = data.q3;
                document.getElementById('modalQ4').value = data.q4;
            } else {
                document.getElementById('modalGradeId').value = '';
                document.getElementById('modalEnrollmentId').value = '';
                document.getElementById('modalEnrollmentId').style.pointerEvents = 'auto';
                document.getElementById('modalEnrollmentId').style.opacity = '1';
                document.getElementById('modalSubjectId').value = '';
                document.getElementById('modalQ1').value = '';
                document.getElementById('modalQ2').value = '';
                document.getElementById('modalQ3').value = '';
                document.getElementById('modalQ4').value = '';
            }
            modal.style.display = 'flex';
        }

        function closeModal() {
            modal.style.display = 'none';
        }
    </script>
</body>
</html>