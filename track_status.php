<?php
require_once 'config/db.php';

$enrollment_id = isset($_GET['enrollment_id']) ? trim($_GET['enrollment_id']) : '';
$student = null;
$subjects = [];
$error = null;

if ($enrollment_id) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM Enrollments WHERE enrollment_id = :id");
        $stmt->execute([':id' => $enrollment_id]);
        $student = $stmt->fetch();

        if ($student) {
            $stmt_subj = $pdo->prepare("SELECT subject_name FROM Enrollment_Subjects WHERE enrollment_id = :id");
            $stmt_subj->execute([':id' => $enrollment_id]);
            $subjects = $stmt_subj->fetchAll(PDO::FETCH_COLUMN);
        }
    } catch (PDOException $e) {
        $error = "Database Error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track Enrollment Status | Pines NHS</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .tracker-container { max-width: 600px; width: 100%; margin: 40px auto; padding: 20px; }
        .status-badge {
            display: inline-block; padding: 8px 16px; border-radius: 20px; font-weight: bold; font-size: 1.1rem;
        }
        .status-Pending { background: #fef08a; color: #854d0e; }
        .status-Approved, .status-Verified { background: #bbf7d0; color: #166534; }
        .status-Rejected { background: #fecaca; color: #991b1b; }
        .status-Enrolled { background: #bfdbfe; color: #1e40af; }
        .info-grid { display: grid; grid-template-columns: 1fr 2fr; gap: 10px; margin-top: 15px; }
        .info-label { color: var(--text-muted); font-size: 0.9rem; }
        .info-value { font-weight: 500; }
    </style>
</head>
<body style="align-items: center;">

    <div class="tracker-container">
        <div style="margin-bottom: 20px;">
            <a href="index.php" class="btn btn-outline" style="padding: 5px 10px;">&larr; Home</a>
        </div>

        <h2 style="margin-bottom: 20px;">Track Enrollment Status</h2>

        <?php if ($error): ?>
            <div class="glass-panel" style="padding: 20px; color: #dc2626; text-align: center; margin-bottom: 20px;">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="GET" action="track_status.php" style="display: flex; gap: 10px; margin-bottom: 30px;">
            <input type="text" name="enrollment_id" placeholder="Enter Tracking ID (e.g., ENR-2025-...)" value="<?php echo htmlspecialchars($enrollment_id); ?>" required style="flex: 1; padding: 10px; border-radius: 8px; border: 1px solid var(--glass-border); background: transparent; color: var(--text-main);">
            <button type="submit" class="btn btn-primary">Check</button>
        </form>

        <?php if ($enrollment_id && $student): ?>
            <div class="glass-panel" style="padding: 25px;">
                <h3 style="margin-bottom: 15px;">Application Details</h3>
                
                <div style="text-align: center; margin: 20px 0;">
                    <span class="status-badge status-<?php echo htmlspecialchars($student['status']); ?>">
                        Status: <?php echo htmlspecialchars($student['status']); ?>
                    </span>
                </div>

                <div class="info-grid">
                    <div class="info-label">Name:</div>
                    <div class="info-value"><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></div>
                    
                    <div class="info-label">Grade Level:</div>
                    <div class="info-value"><?php echo htmlspecialchars($student['grade_level']); ?></div>
                    
                    <div class="info-label">Section:</div>
                    <div class="info-value"><?php echo htmlspecialchars($student['section'] ?? 'Not assigned'); ?></div>
                </div>

                <?php if (!empty($subjects)): ?>
                    <div style="margin-top: 20px;">
                        <h4 style="margin-bottom: 10px;">Selected Subjects:</h4>
                        <ul style="list-style-position: inside; color: var(--text-main);">
                            <?php foreach ($subjects as $subj): ?>
                                <li><?php echo htmlspecialchars($subj); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>
        <?php elseif ($enrollment_id): ?>
            <div class="glass-panel" style="padding: 20px; color: #dc2626; text-align: center;">
                Application not found. Please check your Tracking ID.
            </div>
        <?php endif; ?>
    </div>

    <script src="assets/js/main.js"></script>
</body>
</html>
