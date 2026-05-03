<?php
require_once '../config/db.php';

// Get enrollment_id from GET or POST
$enrollment_id = isset($_GET['enrollment_id']) ? trim($_GET['enrollment_id']) : '';
$enrollment    = null;
$subjects      = [];
$error         = null;
$success       = null;

// Subject pricing
$subjectPrices = [
    'Mathematics'       => 350,
    'Science'           => 350,
    'English'           => 300,
    'Filipino'          => 300,
    'Araling Panlipunan'=> 300,
    'MAPEH'             => 400,
    'TLE'               => 450,
    'Values Education'  => 250,
];

// Flat fees
$miscFee         = 500;
$registrationFee = 200;

// ─── Handle Payment Submission ────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $enrollment_id   = trim($_POST['enrollment_id'] ?? '');
    $payment_method  = trim($_POST['payment_method'] ?? '');
    $reference_no    = trim($_POST['reference_no'] ?? '');
    $amount_paid     = floatval($_POST['amount_paid'] ?? 0);

    if (empty($enrollment_id) || empty($payment_method) || empty($reference_no)) {
        $error = "All payment fields are required.";
    } else {
        try {
            // Fetch enrollment + student info
            $stmt = $pdo->prepare("
                SELECT e.enrollment_id, e.tracking_no, e.student_id, e.status,
                       e.subjects, e.section, e.grade_level,
                       s.first_name, s.last_name
                FROM enrollments e
                JOIN students s ON e.student_id = s.student_id
                WHERE e.enrollment_id = :eid
            ");
            $stmt->execute([':eid' => $enrollment_id]);
            $enrollment = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$enrollment) {
                $error = "Enrollment record not found.";
            } elseif ($enrollment['status'] !== 'Assessed') {
                $error = "Payment is only available for Assessed applications.";
            } else {
                // Calculate total
                $selectedSubjects = json_decode($enrollment['subjects'] ?? '[]', true) ?: [];
                $subjectTotal = 0;
                foreach ($selectedSubjects as $subj) {
                    $subjectTotal += $subjectPrices[$subj] ?? 0;
                }
                $totalAmount = $subjectTotal + $miscFee + $registrationFee;

                // Insert payment record
                $pstmt = $pdo->prepare("
                    INSERT INTO payments (enrollment_id, payment_method, reference_no, amount_paid, payment_date, status)
                    VALUES (:eid, :method, :refno, :amount, NOW(), 'Confirmed')
                ");
                $pstmt->execute([
                    ':eid'    => $enrollment_id,
                    ':method' => $payment_method,
                    ':refno'  => $reference_no,
                    ':amount' => $amount_paid,
                ]);

                // Update enrollment status to Enrolled
                $upd = $pdo->prepare("UPDATE enrollments SET status = 'Enrolled' WHERE enrollment_id = :eid");
                $upd->execute([':eid' => $enrollment_id]);

                $success = "Payment confirmed! Your enrollment is now complete.";
                $enrollment['status'] = 'Enrolled';
            }
        } catch (PDOException $e) {
            $error = "Database Error: " . $e->getMessage();
        }
    }
}

// ─── Fetch enrollment on GET ──────────────────────────────────────────────────
if (!$enrollment && !empty($enrollment_id) && $_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $stmt = $pdo->prepare("
            SELECT e.enrollment_id, e.tracking_no, e.student_id, e.status,
                   e.subjects, e.section, e.grade_level,
                   s.first_name, s.last_name
            FROM enrollments e
            JOIN students s ON e.student_id = s.student_id
            WHERE e.enrollment_id = :eid
        ");
        $stmt->execute([':eid' => $enrollment_id]);
        $enrollment = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $error = "Database Error: " . $e->getMessage();
    }
}

// Build subject list and totals
if ($enrollment) {
    $subjects     = json_decode($enrollment['subjects'] ?? '[]', true) ?: [];
    $subjectTotal = 0;
    foreach ($subjects as $subj) {
        $subjectTotal += $subjectPrices[$subj] ?? 0;
    }
    $totalAmount = $subjectTotal + $miscFee + $registrationFee;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment | Pines NHS Enrollment</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;1,9..40,300&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css?v=<?= time(); ?>">

    <script>
        if (localStorage.getItem('theme') === 'dark' ||
           (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.setAttribute('data-theme', 'dark');
        } else {
            document.documentElement.removeAttribute('data-theme');
        }
    </script>

    <style>
        /* ── Reset & Base ───────────────────────────────── */
        *, *::before, *::after { box-sizing: border-box; }

        body {
            font-family: 'DM Sans', sans-serif;
            display: flex;
            flex-direction: column;
            align-items: center;
            min-height: 100vh;
            padding: 40px 20px 60px;
            background: var(--bg-color);
            color: var(--text-main);
        }

        h1, h2, h3 {
            font-family: 'DM Serif Display', serif;
            font-weight: 400;
        }

        /* ── Layout ─────────────────────────────────────── */
        .pay-wrapper {
            max-width: 700px;
            width: 100%;
            animation: fadeUp 0.5s ease both;
        }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(18px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* ── Page Header ─────────────────────────────────── */
        .pay-header {
            margin-bottom: 32px;
        }

        .pay-header .eyebrow {
            font-size: 0.78rem;
            font-weight: 600;
            letter-spacing: 2.5px;
            text-transform: uppercase;
            color: var(--primary-color);
            margin-bottom: 6px;
        }

        .pay-header h2 {
            font-size: 2.2rem;
            margin: 0 0 6px;
            line-height: 1.1;
        }

        .pay-header p {
            font-size: 0.92rem;
            color: var(--text-muted);
            margin: 0;
        }

        /* ── Glass Panel Override ─────────────────────────── */
        .pay-card {
            background: var(--glass-bg, rgba(255,255,255,0.06));
            border: 1px solid var(--glass-border, rgba(255,255,255,0.12));
            border-radius: 16px;
            padding: 28px 30px;
            margin-bottom: 18px;
            backdrop-filter: blur(10px);
        }

        .section-title {
            font-family: 'DM Serif Display', serif;
            font-size: 1.05rem;
            margin: 0 0 18px;
            display: flex;
            align-items: center;
            gap: 10px;
            padding-bottom: 12px;
            border-bottom: 1px solid var(--glass-border, rgba(0,0,0,0.08));
        }

        .section-title .icon {
            width: 30px;
            height: 30px;
            border-radius: 8px;
            background: rgba(22, 101, 52, 0.15);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
        }

        /* ── Subject Breakdown Table ─────────────────────── */
        .fee-table {
            width: 100%;
            border-collapse: collapse;
        }

        .fee-table tr td {
            padding: 9px 4px;
            font-size: 0.9rem;
            border-bottom: 1px solid var(--glass-border, rgba(0,0,0,0.06));
        }

        .fee-table tr:last-child td { border-bottom: none; }

        .fee-table .subject-badge {
            display: inline-flex;
            align-items: center;
            gap: 7px;
        }

        .fee-table .dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: var(--primary-color);
            flex-shrink: 0;
            opacity: 0.7;
        }

        .fee-table td.price {
            text-align: right;
            font-variant-numeric: tabular-nums;
            font-weight: 500;
            color: var(--text-main);
        }

        .fee-table .subtotal-row td {
            padding-top: 12px;
            font-size: 0.85rem;
            color: var(--text-muted);
        }

        .fee-table .misc-row td {
            font-size: 0.85rem;
            color: var(--text-muted);
        }

        /* ── Total Bar ───────────────────────────────────── */
        .total-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px 20px;
            background: linear-gradient(135deg, rgba(22,101,52,0.18) 0%, rgba(34,197,94,0.08) 100%);
            border: 1px solid rgba(34,197,94,0.25);
            border-radius: 12px;
            margin-top: 4px;
        }

        .total-bar .label {
            font-family: 'DM Serif Display', serif;
            font-size: 1rem;
            color: var(--text-main);
        }

        .total-bar .amount {
            font-family: 'DM Serif Display', serif;
            font-size: 1.7rem;
            color: #22c55e;
            letter-spacing: -0.5px;
        }

        /* ── Payment Method Pills ────────────────────────── */
        .method-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 10px;
            margin-bottom: 22px;
        }

        .method-pill {
            position: relative;
            cursor: pointer;
        }

        .method-pill input[type="radio"] {
            position: absolute;
            opacity: 0;
            width: 0;
            height: 0;
        }

        .method-pill label {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 14px 10px;
            border: 2px solid var(--glass-border, rgba(0,0,0,0.1));
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.2s ease;
            background: var(--bg-color);
            text-align: center;
        }

        .method-pill label:hover {
            border-color: var(--primary-color);
            background: rgba(22,101,52,0.04);
        }

        .method-pill input[type="radio"]:checked + label {
            border-color: var(--primary-color);
            background: rgba(22,101,52,0.1);
            box-shadow: 0 0 0 3px rgba(22,101,52,0.12);
        }

        .method-logo {
            width: 44px;
            height: 28px;
            object-fit: contain;
            border-radius: 4px;
        }

        .method-name {
            font-size: 0.78rem;
            font-weight: 600;
            color: var(--text-main);
            letter-spacing: 0.3px;
        }

        /* Inline SVG logos */
        .logo-gcash   { background: #007aff; color: #fff; border-radius: 6px; display:flex; align-items:center; justify-content:center; font-size:0.65rem; font-weight:800; letter-spacing:-0.5px; padding: 3px 5px; }
        .logo-maya    { background: #00b140; color: #fff; border-radius: 6px; display:flex; align-items:center; justify-content:center; font-size:0.65rem; font-weight:800; letter-spacing:-0.5px; padding: 3px 5px; }
        .logo-online  { background: linear-gradient(135deg,#6366f1,#8b5cf6); color:#fff; border-radius:6px; display:flex; align-items:center; justify-content:center; font-size:0.55rem; font-weight:700; letter-spacing:-0.5px; padding: 3px 5px; }
        .logo-bank    { background: #1e3a5f; color: #fff; border-radius: 6px; display:flex; align-items:center; justify-content:center; font-size:0.6rem; font-weight:700; letter-spacing:-0.5px; padding: 3px 5px; }
        .logo-cash    { background: #d97706; color: #fff; border-radius: 6px; display:flex; align-items:center; justify-content:center; font-size:0.6rem; font-weight:700; letter-spacing:-0.5px; padding: 3px 5px; }

        /* QR Hint Block */
        .qr-hint {
            display: none;
            padding: 14px 16px;
            border-radius: 10px;
            background: rgba(59,130,246,0.08);
            border: 1px solid rgba(59,130,246,0.2);
            font-size: 0.85rem;
            color: var(--text-muted);
            margin-bottom: 16px;
            line-height: 1.6;
        }

        .qr-hint strong { color: var(--text-main); }
        .qr-hint.visible { display: block; }

        /* ── Form Controls ───────────────────────────────── */
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 7px;
            margin-bottom: 14px;
        }

        .form-label {
            font-size: 0.83rem;
            font-weight: 600;
            color: var(--text-main);
            letter-spacing: 0.2px;
        }

        .form-label .req { color: #ef4444; margin-left: 2px; }

        .form-control {
            width: 100%;
            padding: 11px 14px;
            border-radius: 9px;
            border: 1.5px solid var(--glass-border, rgba(0,0,0,0.12));
            background: var(--bg-color);
            color: var(--text-main);
            font-family: 'DM Sans', sans-serif;
            font-size: 0.93rem;
            transition: all 0.25s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(22,101,52,0.13);
        }

        /* ── Submit Button ───────────────────────────────── */
        .btn-pay {
            width: 100%;
            padding: 16px;
            font-size: 1.05rem;
            font-weight: 600;
            font-family: 'DM Sans', sans-serif;
            border: none;
            border-radius: 12px;
            background: linear-gradient(135deg, #166534 0%, #15803d 60%, #22c55e 100%);
            color: #fff;
            cursor: pointer;
            letter-spacing: 0.3px;
            transition: all 0.25s ease;
            box-shadow: 0 4px 20px rgba(22,101,52,0.3);
            position: relative;
            overflow: hidden;
        }

        .btn-pay:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 24px rgba(22,101,52,0.4);
        }

        .btn-pay:active { transform: translateY(0); }

        .btn-pay::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(255,255,255,0.12), transparent);
            pointer-events: none;
        }

        /* ── Alerts ──────────────────────────────────────── */
        .alert {
            padding: 14px 16px;
            border-radius: 10px;
            font-size: 0.9rem;
            margin-bottom: 18px;
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }

        .alert-error {
            background: rgba(239,68,68,0.1);
            border: 1px solid rgba(239,68,68,0.25);
            color: #ef4444;
        }

        .alert-success {
            background: rgba(34,197,94,0.1);
            border: 1px solid rgba(34,197,94,0.3);
            color: #22c55e;
        }

        /* ── Success State ───────────────────────────────── */
        .success-panel {
            text-align: center;
            padding: 40px 30px;
        }

        .success-icon {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            background: rgba(34,197,94,0.15);
            border: 2px solid rgba(34,197,94,0.35);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.9rem;
            margin-bottom: 20px;
            animation: popIn 0.5s cubic-bezier(0.34,1.56,0.64,1) both;
        }

        @keyframes popIn {
            from { transform: scale(0.4); opacity: 0; }
            to   { transform: scale(1); opacity: 1; }
        }

        .success-panel h3 {
            font-size: 1.6rem;
            margin: 0 0 8px;
        }

        .success-panel p {
            color: var(--text-muted);
            font-size: 0.93rem;
            margin: 0 0 28px;
        }

        /* ── Info chips (student info row) ───────────────── */
        .student-chips {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 4px;
        }

        .chip {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
            background: rgba(100,116,139,0.1);
            color: var(--text-muted);
            border: 1px solid var(--glass-border, rgba(0,0,0,0.07));
        }

        .chip strong { color: var(--text-main); font-weight: 600; }

        /* ── Not Found ───────────────────────────────────── */
        .not-found {
            text-align: center;
            padding: 50px 20px;
        }

        .not-found .emoji { font-size: 3rem; margin-bottom: 14px; }

        /* ── Responsive ──────────────────────────────────── */
        @media (max-width: 520px) {
            .method-grid { grid-template-columns: 1fr 1fr; }
            .pay-header h2 { font-size: 1.75rem; }
        }
    </style>
</head>
<body>

    <!-- Theme Toggle -->
    <div class="theme-switch-wrapper" style="position: fixed; top: 20px; right: 20px; z-index: 100;" title="Toggle Theme">
        <label class="theme-switch">
            <input type="checkbox" id="theme-toggle-checkbox" onchange="toggleTheme()">
            <span class="slider"></span>
        </label>
    </div>

    <div class="pay-wrapper">

        <!-- Back -->
        <div style="margin-bottom: 28px;">
            <a href="../index.php" class="btn btn-outline" style="padding: 8px 16px; font-family:'DM Sans',sans-serif;">&larr; Back to Home</a>
        </div>

        <!-- Header -->
        <div class="pay-header">
            <p class="eyebrow">Pines NHS · Enrollment 2025–2026</p>
            <h2>Payment & Fees</h2>
            <p>Review your enrolled subjects and complete payment to finalize enrollment.</p>
        </div>

        <!-- Alerts -->
        <?php if ($error): ?>
            <div class="alert alert-error">
                <span>⚠️</span>
                <span><?= htmlspecialchars($error) ?></span>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="pay-card success-panel">
                <div class="success-icon">✓</div>
                <h3>Payment Received!</h3>
                <p>Your enrollment has been confirmed. You can now track your full enrollment details.</p>
                <a href="track_status.php?tracking_no=<?= htmlspecialchars($enrollment['tracking_no']) ?>" class="btn btn-primary" style="font-family:'DM Sans',sans-serif; padding: 13px 30px; font-size:0.95rem;">
                    View Enrollment Status &rarr;
                </a>
            </div>

        <?php elseif ($enrollment && $enrollment['status'] === 'Assessed'): ?>

            <!-- ── Student Info ───────────────────────── -->
            <div class="pay-card">
                <div class="section-title">
                    <span class="icon">👤</span> Student Information
                </div>
                <div class="student-chips">
                    <span class="chip">Name: <strong><?= htmlspecialchars($enrollment['first_name'] . ' ' . $enrollment['last_name']) ?></strong></span>
                    <span class="chip">Grade: <strong><?= htmlspecialchars($enrollment['grade_level']) ?></strong></span>
                    <span class="chip">Section: <strong><?= htmlspecialchars($enrollment['section'] ?: 'TBA') ?></strong></span>
                    <span class="chip">Ref: <strong style="font-family:monospace;"><?= htmlspecialchars($enrollment['tracking_no']) ?></strong></span>
                </div>
            </div>

            <!-- ── Fee Breakdown ─────────────────────── -->
            <div class="pay-card">
                <div class="section-title">
                    <span class="icon">📋</span> Fee Breakdown
                </div>

                <table class="fee-table">
                    <thead>
                        <tr>
                            <td style="font-size:0.75rem; text-transform:uppercase; letter-spacing:1.5px; color:var(--text-muted); padding-bottom:10px;">Subject</td>
                            <td style="font-size:0.75rem; text-transform:uppercase; letter-spacing:1.5px; color:var(--text-muted); padding-bottom:10px; text-align:right;">Amount</td>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($subjects as $subj): 
                            $price = $subjectPrices[$subj] ?? 0;
                        ?>
                        <tr>
                            <td>
                                <span class="subject-badge">
                                    <span class="dot"></span>
                                    <?= htmlspecialchars($subj) ?>
                                </span>
                            </td>
                            <td class="price">₱<?= number_format($price, 2) ?></td>
                        </tr>
                        <?php endforeach; ?>

                        <tr class="subtotal-row">
                            <td style="padding-top:14px;">Subject Total</td>
                            <td class="price" style="padding-top:14px; color:var(--text-muted);">₱<?= number_format($subjectTotal, 2) ?></td>
                        </tr>
                        <tr class="misc-row">
                            <td>Miscellaneous Fee</td>
                            <td class="price">₱<?= number_format($miscFee, 2) ?></td>
                        </tr>
                        <tr class="misc-row">
                            <td>Registration Fee</td>
                            <td class="price">₱<?= number_format($registrationFee, 2) ?></td>
                        </tr>
                    </tbody>
                </table>

                <div class="total-bar" style="margin-top: 18px;">
                    <span class="label">Total Due</span>
                    <span class="amount">₱<?= number_format($totalAmount, 2) ?></span>
                </div>
            </div>

            <!-- ── Payment Form ───────────────────────── -->
            <div class="pay-card">
                <div class="section-title">
                    <span class="icon">💳</span> Payment Method
                </div>

                <form method="POST" action="payment.php" id="paymentForm">
                    <input type="hidden" name="enrollment_id" value="<?= htmlspecialchars($enrollment['enrollment_id']) ?>">
                    <input type="hidden" name="amount_paid" value="<?= $totalAmount ?>">

                    <!-- Method Selector -->
                    <div class="method-grid" style="margin-bottom:18px;">
                        <div class="method-pill">
                            <input type="radio" name="payment_method" id="m_gcash" value="GCash" required>
                            <label for="m_gcash">
                                <span class="logo-gcash method-logo">GCash</span>
                                <span class="method-name">GCash</span>
                            </label>
                        </div>
                        <div class="method-pill">
                            <input type="radio" name="payment_method" id="m_maya" value="Maya">
                            <label for="m_maya">
                                <span class="logo-maya method-logo">Maya</span>
                                <span class="method-name">Maya</span>
                            </label>
                        </div>
                        <div class="method-pill">
                            <input type="radio" name="payment_method" id="m_online" value="Online Banking">
                            <label for="m_online">
                                <span class="logo-online method-logo">e-Bank</span>
                                <span class="method-name">Online Banking</span>
                            </label>
                        </div>
                        <div class="method-pill">
                            <input type="radio" name="payment_method" id="m_bank" value="Bank Transfer">
                            <label for="m_bank">
                                <span class="logo-bank method-logo">BPI/BDO</span>
                                <span class="method-name">Bank Transfer</span>
                            </label>
                        </div>
                        <div class="method-pill">
                            <input type="radio" name="payment_method" id="m_cash" value="Cash (Cashier)">
                            <label for="m_cash">
                                <span class="logo-cash method-logo">CASH</span>
                                <span class="method-name">Cash – Cashier</span>
                            </label>
                        </div>
                    </div>

                    <!-- QR / Account Hint -->
                    <div class="qr-hint" id="paymentHint"></div>

                    <div class="form-group">
                        <label class="form-label" for="reference_no">
                            Reference / Transaction Number <span class="req">*</span>
                        </label>
                        <input type="text" id="reference_no" name="reference_no" class="form-control"
                               placeholder="e.g., GCash ref, transfer ref, OR number…" required>
                        <span style="font-size:0.78rem; color:var(--text-muted);">Enter the reference number from your payment confirmation or receipt.</span>
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            Amount Paid <span class="req">*</span>
                        </label>
                        <input type="text" class="form-control"
                               value="₱<?= number_format($totalAmount, 2) ?>"
                               readonly
                               style="background:rgba(100,116,139,0.07); cursor:not-allowed; font-weight:600;">
                    </div>

                    <button type="submit" class="btn-pay">
                        Confirm Payment &amp; Complete Enrollment →
                    </button>
                </form>
            </div>

        <?php elseif ($enrollment && $enrollment['status'] === 'Enrolled'): ?>
            <!-- Already Enrolled -->
            <div class="pay-card success-panel">
                <div class="success-icon">🎓</div>
                <h3>Already Enrolled!</h3>
                <p>Payment for this application has already been processed and the enrollment is complete.</p>
                <a href="track_status.php?tracking_no=<?= htmlspecialchars($enrollment['tracking_no']) ?>" class="btn btn-primary" style="font-family:'DM Sans',sans-serif; padding: 13px 30px;">
                    View Enrollment Details &rarr;
                </a>
            </div>

        <?php elseif ($enrollment): ?>
            <!-- Wrong status -->
            <div class="pay-card not-found">
                <div class="emoji">🕐</div>
                <h3 style="font-size:1.3rem; margin:0 0 8px;">Payment Not Yet Available</h3>
                <p style="color:var(--text-muted); font-size:0.9rem; margin:0 0 24px;">
                    Your application is currently <strong><?= htmlspecialchars($enrollment['status']) ?></strong>.<br>
                    Payment will be enabled once your application is marked as <strong>Assessed</strong>.
                </p>
                <a href="track_status.php?tracking_no=<?= htmlspecialchars($enrollment['tracking_no']) ?>" class="btn btn-outline" style="font-family:'DM Sans',sans-serif;">
                    Track Status
                </a>
            </div>

        <?php elseif (!empty($enrollment_id) && !$enrollment): ?>
            <div class="pay-card not-found">
                <div class="emoji">🔍</div>
                <h3 style="font-size:1.3rem; margin:0 0 8px;">Record Not Found</h3>
                <p style="color:var(--text-muted); font-size:0.9rem; margin:0;">No enrollment found for ID <code><?= htmlspecialchars($enrollment_id) ?></code>. Please check and try again.</p>
            </div>

        <?php else: ?>
            <!-- No ID provided — show lookup form -->
            <div class="pay-card">
                <div class="section-title">
                    <span class="icon">🔎</span> Look Up Your Enrollment
                </div>
                <form method="GET" action="payment.php" style="display:flex; gap:10px;">
                    <input type="text" name="enrollment_id" class="form-control" placeholder="Enter your Enrollment ID…" required>
                    <button type="submit" class="btn btn-primary" style="padding: 0 22px; white-space:nowrap; font-family:'DM Sans',sans-serif;">Look Up</button>
                </form>
            </div>
        <?php endif; ?>

    </div><!-- /.pay-wrapper -->

    <script>
        /* ── Theme Toggle ─────────────────────────────── */
        function toggleTheme() {
            const html = document.documentElement;
            const cb   = document.getElementById('theme-toggle-checkbox');
            if (html.hasAttribute('data-theme')) {
                html.removeAttribute('data-theme');
                localStorage.setItem('theme', 'light');
                if (cb) cb.checked = false;
            } else {
                html.setAttribute('data-theme', 'dark');
                localStorage.setItem('theme', 'dark');
                if (cb) cb.checked = true;
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            const cb = document.getElementById('theme-toggle-checkbox');
            if (localStorage.getItem('theme') === 'dark' ||
               (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                if (cb) cb.checked = true;
            }

            /* ── Payment Method Hints ───────────────── */
            const hints = {
                'GCash': `<strong>GCash:</strong> Send to <strong>0917-XXX-XXXX</strong> (Pines NHS Cashier).<br>Screenshot your receipt and note the 13-digit reference number.`,
                'Maya':  `<strong>Maya:</strong> Send to <strong>0998-XXX-XXXX</strong> (Pines NHS Cashier).<br>Copy the reference number from your Maya confirmation.`,
                'Online Banking': `<strong>Online Banking:</strong> Transfer to BDO Account <strong>0024-XXXX-XXXX</strong>.<br>Account Name: <em>Pines National High School</em>. Use your tracking number as the remarks.`,
                'Bank Transfer':  `<strong>Bank Transfer / OTC:</strong> Deposit to BPI Account <strong>1234-5678-90</strong>.<br>Account Name: <em>Pines National High School</em>. Keep your deposit slip.`,
                'Cash (Cashier)': `<strong>Cash Payment:</strong> Visit the school cashier at the Registrar's Office, Mon–Fri 8AM–4PM.<br>Your Official Receipt (OR) number will serve as your reference number.`,
            };

            const hintBox  = document.getElementById('paymentHint');
            const radios   = document.querySelectorAll('input[name="payment_method"]');

            radios.forEach(r => {
                r.addEventListener('change', () => {
                    if (hints[r.value]) {
                        hintBox.innerHTML = hints[r.value];
                        hintBox.classList.add('visible');
                    } else {
                        hintBox.classList.remove('visible');
                    }
                });
            });

            /* ── Subject item highlight on hover ──── */
            document.querySelectorAll('.subject-item').forEach(el => {
                el.addEventListener('mouseenter', () => el.style.transform = 'translateX(3px)');
                el.addEventListener('mouseleave', () => el.style.transform = '');
            });
        });
    </script>
</body>
</html>
