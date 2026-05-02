<?php 
require_once '../actions/auth.php'; 
check_admin(); // Restricts access to administrative personnel[cite: 4]
require_once '../config/db.php'; 

$current_page = basename($_SERVER['PHP_SELF']); 
$flash = null;

// --- Handle POST actions ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Create New Staff Account[cite: 4]
    if ($action === 'create') {
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $role     = $_POST['role'] ?? '';
        $fname    = trim($_POST['first_name'] ?? '');
        $lname    = trim($_POST['last_name'] ?? '');
        $dept     = trim($_POST['department'] ?? '');
        $allowed  = ['Admin', 'Registrar', 'Cashier', 'Faculty'];

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $flash = ['type' => 'error', 'msg' => 'Invalid email address.'];
        } elseif (strlen($password) < 8) {
            $flash = ['type' => 'error', 'msg' => 'Password must be at least 8 characters.'];
        } elseif (!in_array($role, $allowed)) {
            $flash = ['type' => 'error', 'msg' => 'Invalid role selected.'];
        } else {
            try {
                $pdo->beginTransaction(); 
                $chk = $pdo->prepare("SELECT user_id FROM users WHERE email = ? LIMIT 1");
                $chk->execute([$email]);
                
                if ($chk->fetch()) {
                    $flash = ['type' => 'error', 'msg' => "Email {$email} is already registered."];
                    $pdo->rollBack();
                } else {
                    $hash = password_hash($password, PASSWORD_BCRYPT);
                    $stmt = $pdo->prepare("INSERT INTO users (email, password_hash, role, status) VALUES (?, ?, ?, 'Active')");
                    $stmt->execute([$email, $hash, $role]);
                    $user_id = $pdo->lastInsertId();

                    if ($role === 'Faculty') {
                        $stmtFac = $pdo->prepare("INSERT INTO faculty (user_id, first_name, last_name, department) VALUES (?, ?, ?, ?)");
                        $stmtFac->execute([$user_id, $fname, $lname, $dept]);
                    }
                    $pdo->commit();
                    $flash = ['type' => 'success', 'msg' => "Account for {$email} created successfully."];
                }
            } catch (Exception $e) {
                $pdo->rollBack();
                $flash = ['type' => 'error', 'msg' => "System Error: " . $e->getMessage()];
            }
        }
    } 
    // RESTRICTED Update: Status and Role Only[cite: 4]
    elseif ($action === 'update') {
        $user_id = (int)($_POST['user_id'] ?? 0);
        $role    = $_POST['role'] ?? '';
        $status  = $_POST['status'] ?? '';

        if ($user_id === (int)$_SESSION['user_id'] && $status === 'Inactive') {
            $flash = ['type' => 'error', 'msg' => 'You cannot deactivate your own account.'];
        } else {
            try {
                $stmt = $pdo->prepare("UPDATE users SET role = ?, status = ? WHERE user_id = ?");
                $stmt->execute([$role, $status, $user_id]);
                $flash = ['type' => 'success', 'msg' => 'Account permissions updated successfully.'];
            } catch (Exception $e) {
                $flash = ['type' => 'error', 'msg' => 'Update failed: ' . $e->getMessage()];
            }
        }
    }
    // Cascading Delete[cite: 4]
    elseif ($action === 'delete') {
        $user_id = (int)($_POST['user_id'] ?? 0);
        if ($user_id === (int)$_SESSION['user_id']) {
            $flash = ['type' => 'error', 'msg' => 'You cannot delete your own account.'];
        } else {
            try {
                $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
                $stmt->execute([$user_id]);
                $flash = ['type' => 'success', 'msg' => 'Account deleted successfully.'];
            } catch (Exception $e) {
                $flash = ['type' => 'error', 'msg' => 'Delete failed: ' . $e->getMessage()];
            }
        }
    }
    header("Location: users.php" . ($flash ? '?flash=' . urlencode(json_encode($flash)) : ''));
    exit();
}

if (!$flash && isset($_GET['flash'])) {
    $flash = json_decode(urldecode($_GET['flash']), true);
}

// --- Filters & Data Retrieval[cite: 4] ---
$search        = trim($_GET['search'] ?? '');
$filter_role   = $_GET['role'] ?? '';
$where         = ["role != 'Student'"];
$params        = [];

if ($search !== '') { $where[] = "email LIKE ?"; $params[] = "%{$search}%"; }
if ($filter_role !== '') { $where[] = "role = ?"; $params[] = $filter_role; }

$where_sql = 'WHERE ' . implode(' AND ', $where);
$users = $pdo->prepare("SELECT * FROM users {$where_sql} ORDER BY role, email");
$users->execute($params);
$users = $users->fetchAll();

$role_counts = $pdo->query("SELECT role, COUNT(*) as cnt FROM users WHERE role != 'Student' GROUP BY role")->fetchAll(PDO::FETCH_KEY_PAIR);

include 'includes/admin_header.php'; 
?>
<body>
<div class="layout">
    <?php include 'includes/sidebar.php'; ?>
    <main class="main-content">
        <h2 style="margin-bottom: 5px;">User Accounts</h2>
        <p class="text-muted" style="margin-bottom: 30px; font-size: 0.9rem;">Manage school personnel roles and authentication status.</p>

        <?php if ($flash): ?>
        <div class="glass-panel" style="padding:12px 16px; margin-bottom:24px; color:<?= $flash['type']==='success'?'var(--primary-color)':'#ef4444'?>; border:1px solid <?= $flash['type']==='success'?'var(--primary-color)':'#ef4444'?>55;">
            <?= htmlspecialchars($flash['msg']) ?>
        </div>
        <?php endif; ?>

        <div class="stat-grid">
            <?php foreach (['Admin' => '#ef4444', 'Registrar' => '#3b82f6', 'Cashier' => '#d97706', 'Faculty' => '#10b981'] as $role => $color): ?>
            <div class="glass-panel" style="border-left: 4px solid <?=$color?>;">
                <p class="text-muted" style="font-size:0.85rem; font-weight:600;"><?= $role ?></p>
                <h3 style="font-size: 2rem; margin-top: 10px;"><?= $role_counts[$role] ?? 0 ?></h3>
            </div>
            <?php endforeach; ?>
        </div>

        <form method="GET" style="display:flex; gap:12px; margin-bottom:24px; flex-wrap:wrap;">
            <input type="text" name="search" placeholder="Search email..." value="<?= htmlspecialchars($search) ?>" class="form-control" style="width:280px;">
            <select name="role" class="form-control" style="width:180px;">
                <option value="">All Roles</option>
                <option value="Admin" <?=$filter_role==='Admin'?'selected':''?>>Admin</option>
                <option value="Faculty" <?=$filter_role==='Faculty'?'selected':''?>>Faculty</option>
            </select>
            <button type="submit" class="btn btn-outline">Filter</button>
            <button type="button" class="btn btn-primary" style="margin-left:auto;" onclick="openModal('createModal')">+ New Staff</button>
        </form>

        <div class="glass-panel" style="padding:0; overflow:hidden;">
            <div class="table-responsive">
                <table class="table-wrapper">
                    <thead>
                        <tr>
                            <th>Email Address</th>
                            <th>Current Role</th>
                            <th>Status</th>
                            <th style="text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                        <tr>
                            <td style="font-weight:600;"><?= htmlspecialchars($u['email']) ?></td>
                            <td><span class="badge" style="border:1px solid var(--glass-border);"><?= $u['role'] ?></span></td>
                            <td>
                                <span class="badge" style="background: <?= $u['status']==='Active'?'rgba(16, 185, 129, 0.1)':'rgba(239, 68, 68, 0.1)' ?>; color: <?= $u['status']==='Active'?'#10b981':'#ef4444' ?>;">
                                    <?= $u['status'] ?>
                                </span>
                            </td>
                            <td style="text-align:right;">
                                <button class="btn btn-outline" style="padding:6px 12px; font-size:0.8rem;" onclick='openEditModal(<?= json_encode($u) ?>)'>
                                    <i class="bi bi-gear-fill"></i> Manage
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<!-- Restricted Edit Modal[cite: 4] -->
<div id="editModal" class="modal-overlay" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center; backdrop-filter: blur(4px);">
    <div class="glass-panel" style="width:100%; max-width:400px; padding:35px;">
        <h3 style="margin-bottom:5px;">Adjust Permissions</h3>
        <p id="display-user-email" class="text-muted" style="font-size: 0.85rem; margin-bottom: 25px; font-weight: 500;"></p>
        
        <form method="POST">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="user_id" id="e-id">
            
            <div class="form-group">
                <label class="form-label">User Role</label>
                <select name="role" id="e-role" class="form-control" required>
                    <option value="Admin">Admin</option>
                    <option value="Registrar">Registrar</option>
                    <option value="Cashier">Cashier</option>
                    <option value="Faculty">Faculty</option>
                </select>
            </div>

            <div class="form-group" style="margin-bottom:30px;">
                <label class="form-label">Account Status</label>
                <select name="status" id="e-status" class="form-control" required>
                    <option value="Active">Active</option>
                    <option value="Inactive">Inactive</option>
                </select>
            </div>

            <div style="display:flex; justify-content:space-between; align-items:center; gap:12px;">
                <button type="button" class="text-muted" style="background:none; border:none; cursor:pointer; font-size:0.85rem; text-decoration:underline;" onclick="confirmDelete()">Delete Account</button>
                <div style="display:flex; gap:8px;">
                    <button type="button" class="btn btn-outline" onclick="closeModal('editModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update</button>
                </div>
            </div>
        </form>
        <form method="POST" id="delete-form" style="display:none;">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="user_id" id="d-id">
        </form>
    </div>
</div>

<!-- Create Account Modal[cite: 4] -->
<div id="createModal" class="modal-overlay" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center; backdrop-filter: blur(4px);">
    <div class="glass-panel" style="width:100%; max-width:450px; padding:35px;">
        <h3 style="margin-bottom:20px;">New Staff Account</h3>
        <form method="POST">
            <input type="hidden" name="action" value="create">
            <div class="form-group">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" class="form-control" required>
            </div>
            <div class="form-group">
                <label class="form-label">Temporary Password</label>
                <input type="password" name="password" class="form-control" required minlength="8">
            </div>
            <div class="form-group">
                <label class="form-label">User Role</label>
                <select name="role" class="form-control" required onchange="toggleFacultyFields(this.value)">
                    <option value="" disabled selected>Select a role...</option>
                    <option value="Admin">Admin</option>
                    <option value="Registrar">Registrar</option>
                    <option value="Cashier">Cashier</option>
                    <option value="Faculty">Faculty</option>
                </select>
            </div>
            <div id="faculty-fields" style="display:none; margin-top:20px; border-top: 1px solid var(--glass-border); padding-top:20px;">
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:12px; margin-bottom:12px;">
                    <input type="text" name="first_name" placeholder="First Name" class="form-control">
                    <input type="text" name="last_name" placeholder="Last Name" class="form-control">
                </div>
                <input type="text" name="department" placeholder="Department" class="form-control">
            </div>
            <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:30px;">
                <button type="button" class="btn btn-outline" onclick="closeModal('createModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Create Account</button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal(id) { document.getElementById(id).style.display = 'flex'; }
function closeModal(id) { document.getElementById(id).style.display = 'none'; }

function toggleFacultyFields(role) {
    document.getElementById('faculty-fields').style.display = (role === 'Faculty') ? 'block' : 'none';
}

function openEditModal(u) {
    document.getElementById('e-id').value = u.user_id;
    document.getElementById('d-id').value = u.user_id;
    document.getElementById('e-role').value = u.role;
    document.getElementById('e-status').value = u.status;
    document.getElementById('display-user-email').textContent = "Editing: " + u.email;
    openModal('editModal');
}

function confirmDelete() {
    if (confirm("Permanently delete this account? This will remove all associated profile data.")) {
        document.getElementById('delete-form').submit();
    }
}
</script>
<script src="../assets/js/main.js"></script>
</body>
</html>