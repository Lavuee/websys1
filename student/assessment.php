<?php
// Secures the page for student access only using the centralized authentication script.
require_once '../actions/auth.php';
check_student();

// Establishes the database connection.
require_once '../config/db.php';

try {
    $stmt = $pdo->prepare("SELECT total_assessment, balance FROM enrollments WHERE enrollment_id = :id");
    $stmt->execute([':id' => $_SESSION['enrollment_id']]);
    $finances = $stmt->fetch();

    $payStmt = $pdo->prepare("SELECT SUM(amount) FROM payments WHERE enrollment_id = :id AND status = 'Verified'");
    $payStmt->execute([':id' => $_SESSION['enrollment_id']]);
    $amount_paid = (float) $payStmt->fetchColumn();

} catch (\PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assessment & Payment | Pines NHS</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .layout { display: flex; min-height: 100vh; }
        .sidebar { width: 280px; border-right: 1px solid var(--glass-border); padding: 24px; display: flex; flex-direction: column; background: var(--glass-bg); backdrop-filter: blur(15px); position: sticky; top: 0; height: 100vh; z-index: 50;}
        .main-content { flex: 1; padding: 40px; }
        .nav-link { display: block; padding: 12px 15px; margin-bottom: 5px; border-radius: 8px; color: var(--text-main); font-weight: 500; text-decoration: none; }
        .nav-link:hover, .nav-link.active { background: var(--primary-color); color: white; }
        .breakdown-row { display: flex; justify-content: space-between; padding: 15px 0; border-bottom: 1px solid var(--glass-border); font-size: 0.95rem; }
        .breakdown-row:last-child { border-bottom: none; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; font-size: 0.85rem; font-weight: 500; margin-bottom: 5px; }
        .form-control { width: 100%; padding: 10px; border-radius: 8px; border: 1px solid var(--glass-border); background: transparent; color: var(--text-main); }
        .form-control:focus { outline: 2px solid var(--primary-color); }
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
                <a href="dashboard.php" class="nav-link">Dashboard</a>
                <a href="assessment.php" class="nav-link active">Assessment & Payment</a>
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
            <h2 style="margin-bottom: 5px;">Assessment & Payments</h2>
            <p class="text-muted" style="margin-bottom: 30px; font-size: 0.9rem;">View tuition breakdown and submit payments.</p>
            <div style="display: grid; grid-template-columns: 1.5fr 1fr; gap: 30px;">
                <div class="glass-panel" style="padding: 25px;">
                    <h3 style="font-size: 1.1rem; margin-bottom: 20px;">Tuition Assessment</h3>
                    <div class="breakdown-row" style="font-weight: 600;">
                        <span>Total Assessment</span>
                        <span>₱<?= number_format($finances['total_assessment'], 2) ?></span>
                    </div>
                    <div class="breakdown-row text-primary">
                        <span>Amount Paid</span>
                        <span>₱<?= number_format($amount_paid, 2) ?></span>
                    </div>
                    <div class="breakdown-row" style="font-weight: 700; color: #ea580c; font-size: 1.1rem;">
                        <span>Remaining Balance</span>
                        <span>₱<?= number_format($finances['balance'], 2) ?></span>
                    </div>
                </div>
                <?php if($finances['balance'] > 0): ?>
                <div class="glass-panel" style="padding: 25px;">
                    <h3 style="font-size: 1.1rem; margin-bottom: 20px;">Submit Payment</h3>
                    <form action="../actions/submit_payment.php" method="POST">
                        <div class="form-group">
                            <label>Amount to Pay (₱)</label>
                            <input type="number" step="0.01" name="amount" class="form-control" max="<?= $finances['balance'] ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Payment Method</label>
                            <select name="payment_method" class="form-control" required>
                                <option value="Cash">Cash (In-person)</option>
                                <option value="GCash">GCash</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 10px; padding: 12px;">Submit Payment</button>
                    </form>
                </div>
                <?php else: ?>
                    <div class="glass-panel" style="padding: 25px; display: flex; align-items: center; justify-content: center; text-align: center;">
                        <div>
                            <span style="font-size: 3rem; display: block; margin-bottom: 10px;">🎉</span>
                            <h3 style="color: var(--primary-color);">Fully Paid</h3>
                            <p class="text-muted" style="font-size: 0.9rem;">No outstanding balance remains on this account.</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
    <script src="../assets/js/main.js"></script>
</body>
</html>