<?php
// Pines EMS Authentication Controller
// Initializes session management and verifies active authentication tokens.
session_start();

// Redirects authenticated users to their designated dashboards to prevent redundant logins.
if (isset($_SESSION['user_id'])) {
    if (strtolower($_SESSION['role']) === 'admin') {
        header("Location: admin/dashboard.php");
    } else {
        header("Location: student/dashboard.php");
    }
    exit();
}

// Establishes the database connection parameters.
require_once 'config/db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitizes incoming authentication payload.
    $login_id  = trim(htmlspecialchars($_POST['login_id'] ?? ''));
    $password  = $_POST['password'] ?? '';
    $role      = $_POST['role'] ?? 'student';

    if (empty($login_id) || empty($password)) {
        $error = "Missing required authentication parameters.";

    } elseif ($role === 'admin') {
        // Authenticates administrative personnel against the centralized users table.
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role = 'Admin' LIMIT 1");
        $stmt->execute([$login_id]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($admin && password_verify($password, $admin['password_hash'])) {
            $_SESSION['user_id']    = $admin['user_id'];
            $_SESSION['role']       = 'Admin';
            $_SESSION['user_email'] = $admin['email'];
            header("Location: admin/dashboard.php");
            exit();
        } else {
            $error = "Invalid administrative credentials. Verify registered email and password.";
        }

    } else {
        // Authenticates student access using the enrollment tracking number logic.
        $stmt = $pdo->prepare("
            SELECT u.*, e.enrollment_id, s.first_name, s.last_name 
            FROM enrollments e
            JOIN students s ON e.student_id = s.student_id
            JOIN users u ON s.user_id = u.user_id
            WHERE e.tracking_no = ? LIMIT 1
        ");
        $stmt->execute([$login_id]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($student && password_verify($password, $student['password_hash'])) {
            $_SESSION['user_id']       = $student['user_id'];
            $_SESSION['role']          = 'Student';
            $_SESSION['enrollment_id'] = $student['enrollment_id'];
            $_SESSION['user_email']    = $student['email'];
            $_SESSION['student_name']  = trim($student['first_name'] . ' ' . $student['last_name']);
            header("Location: student/dashboard.php");
            exit();
        } else {
            $error = "Invalid Student ID or password combination.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Pines EMS</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        /* UI Polish and Branding Adjustments */
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #166534 100%);
            min-height: 100vh;
        }

        .login-card {
            border: none;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.25);
        }

        .brand-icon {
            width: 68px;
            height: 68px;
            background: #166534;
            border-radius: 16px;
            box-shadow: 0 6px 20px rgba(22, 101, 52, 0.4);
        }

        .nav-pills .nav-link {
            color: #6c757d;
            font-weight: 500;
            font-size: 0.875rem;
            border-radius: 8px;
            padding: 0.45rem 1rem;
        }

        .nav-pills .nav-link.active {
            background-color: #166534;
            color: #fff;
        }

        .nav-pills-wrap {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 4px;
        }

        .form-control:focus {
            border-color: #166534;
            box-shadow: 0 0 0 3px rgba(22, 101, 52, 0.12);
        }

        .btn-login {
            background-color: #166534;
            border-color: #166534;
            font-weight: 600;
            letter-spacing: 0.01em;
        }

        .btn-login:hover, .btn-login:focus {
            background-color: #14532d;
            border-color: #14532d;
        }

        .toggle-pw {
            cursor: pointer;
            color: #6c757d;
            border-left: 1px solid #dee2e6;
        }

        .toggle-pw:hover { color: #166534; }

        .form-hint { font-size: 0.75rem; color: #6c757d; }
        .text-green { color: #166534 !important; }
        .text-green:hover { color: #14532d !important; }
    </style>
</head>
<body class="d-flex align-items-center justify-content-center py-5">

<div class="container">
    <div class="row justify-content-center">
        <div class="col-12 col-sm-9 col-md-7 col-lg-5 col-xl-4">

            <div class="text-center mb-4">
                <div class="brand-icon d-flex align-items-center justify-content-center mx-auto mb-3">
                    <i class="bi bi-mortarboard-fill text-white fs-2"></i>
                </div>
                <h1 class="fs-5 fw-bold text-white mb-1">Pines Enrollment System</h1>
                <p class="text-white-50 small mb-0">School Year 2025–2026</p>
            </div>

            <div class="card login-card">
                <div class="card-body p-4">

                    <h2 class="fs-6 fw-semibold text-center text-dark mb-4">Account Access Portal</h2>

                    <div class="nav-pills-wrap mb-4">
                        <ul class="nav nav-pills nav-fill" id="roleTabs">
                            <li class="nav-item">
                                <button class="nav-link active w-100" id="tab-student" type="button" onclick="switchRole('student')">
                                    <i class="bi bi-person me-1"></i> Student
                                </button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link w-100" id="tab-admin" type="button" onclick="switchRole('admin')">
                                    <i class="bi bi-shield-lock me-1"></i> Admin
                                </button>
                            </li>
                        </ul>
                    </div>

                    <?php if (isset($_GET['logged_out'])): ?>
                    <div class="alert alert-success d-flex align-items-center gap-2 py-2 small" role="alert">
                        <i class="bi bi-check-circle-fill"></i>
                        Secure logout completed successfully.
                    </div>
                    <?php endif; ?>

                    <?php if ($error): ?>
                    <div class="alert alert-danger d-flex align-items-center gap-2 py-2 small" role="alert">
                        <i class="bi bi-exclamation-circle-fill"></i>
                        <?= htmlspecialchars($error) ?>
                    </div>
                    <?php endif; ?>

                    <form method="POST" action="login.php" novalidate>
                        <input type="hidden" name="role" id="role-input" value="student">

                        <div class="mb-3">
                            <label for="login_id" class="form-label fw-semibold small" id="login-label">
                                Enrollment ID
                            </label>
                            <input
                                type="text"
                                class="form-control"
                                id="login_id"
                                name="login_id"
                                placeholder="e.g. ENR-2026-XXXXX"
                                value="<?= htmlspecialchars($_POST['login_id'] ?? '') ?>"
                                autocomplete="username"
                                required
                            >
                            <div class="form-hint mt-1" id="login-hint">
                                Submit registered Enrollment ID (e.g. ENR-2026-A2645)
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="password" class="form-label fw-semibold small">Password</label>
                            <div class="input-group">
                                <input
                                    type="password"
                                    class="form-control"
                                    id="password"
                                    name="password"
                                    placeholder="Enter access password"
                                    autocomplete="current-password"
                                    required
                                >
                                <button
                                    class="btn btn-outline-secondary toggle-pw"
                                    type="button"
                                    onclick="togglePassword()"
                                    title="Toggle password visibility"
                                >
                                    <i class="bi bi-eye" id="eye-icon"></i>
                                </button>
                            </div>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-login btn-primary" id="submit-btn">
                                <i class="bi bi-box-arrow-in-right me-1"></i>
                                Authenticate as Student
                            </button>
                        </div>
                    </form>

                    <hr class="my-3 text-muted opacity-25">

                    <p class="text-center small text-muted mb-0">
                        Authentication issues? <a href="mailto:registrar@pines.edu.ph" class="text-green fw-medium text-decoration-none">Contact system administration</a>
                    </p>
                    <p class="text-center small mt-2 mb-0">
                        <a href="index.php" class="text-green fw-medium text-decoration-none">
                            <i class="bi bi-arrow-left me-1"></i>Return to Landing Page
                        </a>
                    </p>

                </div>
            </div>

            <p class="text-center text-white-50 small mt-3 mb-0">
                &copy; <?= date('Y') ?> Pines EMS. Internal system property.
            </p>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Manages dynamic UI rendering based on the selected authorization role.
    function switchRole(role) {
        const isAdmin = role === 'admin';

        document.getElementById('role-input').value = role;
        document.getElementById('tab-student').classList.toggle('active', !isAdmin);
        document.getElementById('tab-admin').classList.toggle('active', isAdmin);

        if (isAdmin) {
            document.getElementById('login-label').textContent = 'Admin Email';
            document.getElementById('login_id').placeholder    = 'Admin email address';
            document.getElementById('login-hint').textContent  = 'Submit authorized administrative email.';
            document.getElementById('submit-btn').innerHTML    = '<i class="bi bi-shield-lock me-1"></i> Authenticate as Admin';
        } else {
            document.getElementById('login-label').textContent = 'Student ID';
            document.getElementById('login_id').placeholder    = 'e.g. ENR-2026-XXXXX';
            document.getElementById('login-hint').textContent  = 'Submit registered Enrollment ID (e.g. ENR-2026-A2645)';
            document.getElementById('submit-btn').innerHTML    = '<i class="bi bi-box-arrow-in-right me-1"></i> Authenticate as Student';
        }
    }

    // Handles password field masking operations.
    function togglePassword() {
        const pw   = document.getElementById('password');
        const icon = document.getElementById('eye-icon');
        const isHidden = pw.type === 'password';
        pw.type = isHidden ? 'text' : 'password';
        icon.className = isHidden ? 'bi bi-eye-slash' : 'bi bi-eye';
    }

    // Restores the correct interface tab if authentication fails and reloads the page.
    const savedRole = "<?= htmlspecialchars($_POST['role'] ?? 'student') ?>";
    switchRole(savedRole);
</script>

</body>
</html>