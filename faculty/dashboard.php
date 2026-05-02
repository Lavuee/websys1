<?php
require_once '../actions/auth.php';
check_faculty(); 
require_once '../config/db.php';

try {
    // We rely on the session data set in the sidebar to get their faculty_id
    $faculty_id = $_SESSION['faculty_id'] ?? 0;

    // Fetch Assigned Subjects
    $subjectStmt = $pdo->prepare("
        SELECT subject_id, subject_name, grade_level 
        FROM subjects 
        WHERE faculty_id = ?
        ORDER BY grade_level ASC
    ");
    $subjectStmt->execute([$faculty_id]);
    $mySubjects = $subjectStmt->fetchAll();

    // Count Total Unique Students handled by this teacher
    $studentCountStmt = $pdo->prepare("
        SELECT COUNT(DISTINCT e.student_id) 
        FROM enrollment_subjects es
        JOIN enrollments e ON es.enrollment_id = e.enrollment_id
        JOIN subjects sub ON es.subject_name = sub.subject_name
        WHERE sub.faculty_id = ? AND e.status IN ('Enrolled', 'Assessed')
    ");
    $studentCountStmt->execute([$faculty_id]);
    $totalStudents = $studentCountStmt->fetchColumn();

} catch (\PDOException $e) {
    die("Database error: " . $e->getMessage());
}

include 'includes/faculty_header.php';
?>
<body>
    <div class="layout">
        <?php include 'includes/faculty_sidebar.php'; ?>
        
        <main class="main-content">
            <h2 style="margin-bottom: 5px;">Welcome, <?= htmlspecialchars($_SESSION['faculty_name']) ?>!</h2>
            <p class="text-muted" style="margin-bottom: 30px; font-size: 0.9rem;">Faculty Portal Dashboard</p>

            <div class="stat-grid" style="grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));">
                <div class="glass-panel" style="border-top: 4px solid var(--primary-color);">
                    <p class="text-muted" style="font-size: 0.85rem; font-weight: 600; text-transform: uppercase;">Assigned Subjects</p>
                    <h3 style="font-size: 2.5rem; margin-top: 10px; color: var(--text-main);"><?= count($mySubjects) ?></h3>
                </div>
                <div class="glass-panel" style="border-top: 4px solid #3b82f6;">
                    <p class="text-muted" style="font-size: 0.85rem; font-weight: 600; text-transform: uppercase;">Total Students Handled</p>
                    <h3 style="font-size: 2.5rem; margin-top: 10px; color: var(--text-main);"><?= number_format($totalStudents) ?></h3>
                </div>
            </div>

            <div class="glass-panel">
                <h3 style="font-size: 1.1rem; margin-bottom: 20px;">My Teaching Load</h3>
                <div class="table-responsive">
                    <table class="table-wrapper">
                        <thead>
                            <tr>
                                <th>Subject Name</th>
                                <th>Grade Level</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($mySubjects as $sub): ?>
                                <tr>
                                    <td style="font-weight: 600;"><?= htmlspecialchars($sub['subject_name']) ?></td>
                                    <td>
                                        <span class="badge" style="border: 1px solid var(--glass-border); color: var(--text-main);">
                                            <?= htmlspecialchars($sub['grade_level']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="manage_grades.php?subject_id=<?= $sub['subject_id'] ?>" class="btn btn-outline" style="padding: 6px 12px; font-size: 0.8rem;">
                                            <i class="bi bi-people-fill"></i> View Class
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            
                            <?php if (empty($mySubjects)): ?>
                                <tr>
                                    <td colspan="3" style="text-align: center; padding: 40px; color: var(--text-muted);">
                                        <i class="bi bi-folder-x" style="font-size: 2rem; display: block; margin-bottom: 10px;"></i>
                                        No subjects assigned for this semester. Contact the administrator.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</body>
</html>