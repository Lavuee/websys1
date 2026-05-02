<?php
require_once '../actions/auth.php';
check_faculty(); 
require_once '../config/db.php';

try {
    $faculty_id = $_SESSION['faculty_id'] ?? 0;
    
    // Capture filters
    $filter_subject_id = $_GET['subject_id'] ?? '';
    $searchQuery = trim($_GET['search'] ?? '');

    // Fetch subjects assigned to this teacher for the filter dropdown
    $subjStmt = $pdo->prepare("SELECT subject_id, subject_name FROM subjects WHERE faculty_id = ? ORDER BY subject_name ASC");
    $subjStmt->execute([$faculty_id]);
    $teacherSubjects = $subjStmt->fetchAll();

    // 1. Fetch the Teacher's Roster using an advanced join strategy
    // We find subjects assigned to the teacher, find students who checked that subject during enrollment, 
    // and LEFT JOIN the grades table so we can see existing grades or insert new ones.
    $query = "
        SELECT s.first_name, s.last_name, s.lrn, 
               e.enrollment_id, e.section, e.grade_level,
               sub.subject_id, sub.subject_name,
               g.grade_id, g.q1, g.q2, g.q3, g.q4, g.final_grade, g.remarks
        FROM subjects sub
        JOIN enrollment_subjects es ON sub.subject_name = es.subject_name
        JOIN enrollments e ON es.enrollment_id = e.enrollment_id
        JOIN students s ON e.student_id = s.student_id
        LEFT JOIN grades g ON (e.enrollment_id = g.enrollment_id AND sub.subject_id = g.subject_id)
        WHERE sub.faculty_id = :faculty_id AND e.status IN ('Enrolled', 'Assessed')
    ";
    
    $params = [':faculty_id' => $faculty_id];
    
    if (!empty($filter_subject_id)) {
        $query .= " AND sub.subject_id = :subject_id";
        $params[':subject_id'] = $filter_subject_id;
    }

    if (!empty($searchQuery)) {
        $query .= " AND (s.first_name LIKE :search1 OR s.last_name LIKE :search2 OR s.lrn LIKE :search3)";
        $searchTerm = "%" . $searchQuery . "%";
        $params[':search1'] = $searchTerm;
        $params[':search2'] = $searchTerm;
        $params[':search3'] = $searchTerm;
    }
    
    $query .= " ORDER BY sub.subject_name ASC, e.section ASC, s.last_name ASC";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $roster = $stmt->fetchAll();

} catch (\PDOException $e) {
    die("Database error: " . $e->getMessage());
}

$remarksColors = [
    'Passed' => 'background: rgba(16, 185, 129, 0.15); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.3);',
    'Failed' => 'background: rgba(239, 68, 68, 0.15); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.3);'
];

include 'includes/faculty_header.php';
?>
<body>
    <div class="layout">
        <?php include 'includes/faculty_sidebar.php'; ?>
        
        <main class="main-content">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 15px;">
                <div>
                    <h2 style="margin-bottom: 5px;">My Classes & Roster</h2>
                    <p class="text-muted" style="font-size: 0.9rem; margin: 0;">View your students and manage their quarterly assessments.</p>
                </div>
                
                <form method="GET" action="manage_grades.php" style="display: flex; align-items: center; gap: 15px; flex-wrap: wrap;">
                    <div style="display: flex; align-items: center; position: relative;">
                        <input type="text" name="search" placeholder="Search student name or LRN..." value="<?= htmlspecialchars($searchQuery) ?>" style="padding: 8px 12px; border-radius: 8px; border: 1px solid var(--glass-border); background: var(--bg-color); color: var(--text-main); font-size: 0.85rem; width: 260px; outline: none; transition: 0.3s;">
                    </div>
                    
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <select name="subject_id" onchange="this.form.submit()" style="padding: 8px 12px; border-radius: 8px; border: 1px solid var(--glass-border); background: var(--bg-color); color: var(--text-main); font-size: 0.85rem; cursor: pointer;">
                            <option value="">All Subjects</option>
                            <?php foreach ($teacherSubjects as $ts): ?>
                                <option value="<?= $ts['subject_id'] ?>" <?= $filter_subject_id == $ts['subject_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($ts['subject_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" style="display: none;"></button>
                </form>
            </div>

            <div class="glass-panel">
                <div class="table-responsive">
                    <table class="table-wrapper">
                        <thead>
                            <tr>
                                <th>Student Name</th>
                                <th>Section</th>
                                <th>Subject</th>
                                <th>Q1</th>
                                <th>Q2</th>
                                <th>Q3</th>
                                <th>Q4</th>
                                <th>Final</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($roster as $row): ?>
                                <tr>
                                    <td>
                                        <div style="font-weight: 600;"><?= htmlspecialchars($row['last_name'] . ', ' . $row['first_name']) ?></div>
                                        <div class="text-muted" style="font-size: 0.8rem;">LRN: <?= htmlspecialchars($row['lrn'] ?: 'N/A') ?></div>
                                    </td>
                                    <td><?= htmlspecialchars($row['section'] ?: 'Unassigned') ?></td>
                                    <td style="font-weight: 500; color: var(--primary-color);"><?= htmlspecialchars($row['subject_name']) ?></td>
                                    
                                    <td><?= $row['q1'] ?? '-' ?></td>
                                    <td><?= $row['q2'] ?? '-' ?></td>
                                    <td><?= $row['q3'] ?? '-' ?></td>
                                    <td><?= $row['q4'] ?? '-' ?></td>
                                    <td style="font-weight: 700;"><?= $row['final_grade'] ?? '-' ?></td>
                                    
                                    <td>
                                        <?php if($row['remarks']): ?>
                                            <span class="badge" style="<?= $remarksColors[$row['remarks']] ?? '' ?>">
                                                <?= htmlspecialchars($row['remarks']) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted" style="font-size: 0.85rem;">Pending</span>
                                        <?php endif; ?>
                                    </td>
                                    
                                    <td>
                                        <button class="btn btn-outline" style="padding: 6px 12px; font-size: 0.8rem;" onclick='openGradeModal(<?= json_encode($row) ?>)'>
                                            <i class="bi bi-pencil-square"></i> Grade
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            
                            <?php if (empty($roster)): ?>
                                <tr>
                                    <td colspan="10" style="text-align: center; padding: 40px; color: var(--text-muted);">
                                        <i class="bi bi-people" style="font-size: 2rem; display: block; margin-bottom: 10px;"></i>
                                        No enrolled students found for your assigned subjects.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- Grade Input Modal (Reuses the secure logic from the Admin side) -->
    <div id="gradeModal" class="modal-overlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:2000; justify-content:center; align-items:center; backdrop-filter: blur(5px);">
        <div class="glass-panel" style="width:100%; max-width:450px; padding:30px; position:relative;">
            
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 25px;">
                <div>
                    <h3 id="modalStudentName" style="margin: 0 0 5px 0;">Record Grade</h3>
                    <p id="modalSubjectName" class="text-muted" style="font-size: 0.85rem; margin: 0; font-weight: 500;"></p>
                </div>
                <button type="button" onclick="closeGradeModal()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: var(--text-muted); line-height: 1;">&times;</button>
            </div>
            
            <!-- Posts to the unified save_grade.php action script -->
            <form action="../actions/save_grade.php" method="POST">
                <input type="hidden" name="grade_id" id="modalGradeId">
                <input type="hidden" name="enrollment_id" id="modalEnrollmentId">
                <input type="hidden" name="subject_id" id="modalSubjectId">

                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; margin-bottom: 25px;">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label">Quarter 1</label>
                        <input type="number" step="0.01" name="q1" id="modalQ1" class="form-control">
                    </div>
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label">Quarter 2</label>
                        <input type="number" step="0.01" name="q2" id="modalQ2" class="form-control">
                    </div>
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label">Quarter 3</label>
                        <input type="number" step="0.01" name="q3" id="modalQ3" class="form-control">
                    </div>
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label">Quarter 4</label>
                        <input type="number" step="0.01" name="q4" id="modalQ4" class="form-control">
                    </div>
                </div>
                <div style="display: flex; justify-content: flex-end; gap: 10px;">
                    <button type="button" class="btn btn-outline" onclick="closeGradeModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Assessment</button>
                </div>
            </form>
        </div>
    </div>

    <script src="../assets/js/main.js"></script>
    <script>
        function openGradeModal(data) {
            document.getElementById('modalStudentName').innerText = data.last_name + ', ' + data.first_name;
            document.getElementById('modalSubjectName').innerText = data.subject_name;
            
            document.getElementById('modalGradeId').value      = data.grade_id || '';
            document.getElementById('modalEnrollmentId').value = data.enrollment_id;
            document.getElementById('modalSubjectId').value    = data.subject_id;
            
            document.getElementById('modalQ1').value = data.q1 || '';
            document.getElementById('modalQ2').value = data.q2 || '';
            document.getElementById('modalQ3').value = data.q3 || '';
            document.getElementById('modalQ4').value = data.q4 || '';
            
            document.getElementById('gradeModal').style.display = 'flex';
        }

        function closeGradeModal() {
            document.getElementById('gradeModal').style.display = 'none';
        }
    </script>
</body>
</html>