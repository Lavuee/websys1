<?php
// Secures the dashboard for Faculty access only
require_once '../actions/auth.php';
check_faculty(); // Assuming you have a check_faculty function in auth.php

require_once '../config/db.php';

$user_id = $_SESSION['user_id'];

try {
    // 1. Fetch Faculty Profile Details
    $facultyStmt = $pdo->prepare("SELECT * FROM faculty WHERE user_id = ?");
    $facultyStmt->execute([$user_id]);
    $faculty = $facultyStmt->fetch();

    if (!$faculty) {
        die("Faculty profile not found. Please contact the administrator.");
    }

    $faculty_id = $faculty['faculty_id'];

    // 2. Fetch Assigned Subjects
    $subjectStmt = $pdo->prepare("
        SELECT subject_id, subject_name, grade_level 
        FROM subjects 
        WHERE faculty_id = ?
        ORDER BY grade_level ASC
    ");
    $subjectStmt->execute([$faculty_id]);
    $mySubjects = $subjectStmt->fetchAll();

    // 3. Count Total Students handled across all assigned subjects
    // This looks at unique enrollments linked to the teacher's subjects in the grades table
    $studentCountStmt = $pdo->prepare("
        SELECT COUNT(DISTINCT enrollment_id) 
        FROM grades 
        WHERE faculty_id = ?
    ");
    $studentCountStmt->execute([$faculty_id]);
    $totalStudents = $studentCountStmt->fetchColumn();

} catch (\PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// Include modular header
include 'includes/faculty_header.php'; 
?>
<body>
    <div class="layout">
        
        <?php include 'includes/faculty_sidebar.php'; ?>

        <main class="main-content">
            <h2 style="margin-bottom: 5px;">Welcome, <?= htmlspecialchars($faculty['first_name']) ?>!</h2>
            <p class="text-muted" style="margin-bottom: 30px; font-size: 0.9rem;">Faculty Portal | <?= htmlspecialchars($faculty['department'] ?? 'General Education') ?></p>

            <div class="stat-grid" style="grid-template-columns: 1fr 1fr;">
                <div class="glass-panel">
                    <p class="text-muted" style="font-size: 0.85rem;">Assigned Subjects</p>
                    <h3 style="font-size: 2rem; margin-top: 10px;"><?= count($mySubjects) ?></h3>
                </div>
                <div class="glass-panel">
                    <p class="text-muted" style="font-size: 0.85rem;">Total Students Handled</p>
                    <h3 style="font-size: 2rem; margin-top: 10px;"><?= number_format($totalStudents) ?></h3>
                </div>
            </div>

            <div class="glass-panel">
                <h3 style="font-size: 1.1rem; margin-bottom: 20px;">My Teaching Load</h3>
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
                                    <span class="badge" style="border: 1px solid var(--glass-border);">
                                        <?= htmlspecialchars($sub['grade_level']) ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="class_list.php?subject_id=<?= $sub['subject_id'] ?>" class="btn btn-primary" style="padding: 6px 12px; font-size: 0.8rem; text-decoration: none;">
                                        Manage Grades
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        
                        <?php if (empty($mySubjects)): ?>
                            <tr>
                                <td colspan="3" style="text-align: center; padding: 30px; color: var(--text-muted);">
                                    No subjects assigned for this semester.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <script src="../assets/js/main.js"></script>
</body>
</html>