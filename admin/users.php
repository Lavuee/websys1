<?php
require_once '../actions/auth.php';
check_admin(); // Restricts access to administrative personnel
require_once '../config/db.php';

$current_page = basename($_SERVER['PHP_SELF']);
$flash = null;

// ── Handle POST actions ───────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Create New Staff Account
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
        } elseif ($role === 'Faculty' && (empty($fname) || empty($lname))) {
            $flash = ['type' => 'error', 'msg' => 'First and Last names are required for Faculty accounts.'];
        } else {
            try {
                $pdo->beginTransaction(); // Ensures atomic insertion across users and faculty tables

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
                        // Automatically provisions the faculty profile to prevent "Profile not found" errors
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
    
    // Update Existing Account
    elseif ($action === 'update') {
        $user_id      = (int)($_POST['user_id'] ?? 0);
        $email        = trim($_POST['email'] ?? '');
        $role         = $_POST['role'] ?? '';
        $status       = $_POST['status'] ?? '';
        $new_password = $_POST['new_password'] ?? '';

        if ($user_id === (int)$_SESSION['user_id'] && $status === 'Inactive') {
            $flash = ['type' => 'error', 'msg' => 'You cannot deactivate your own account.'];
        } else {
            try {
                if (!empty($new_password)) {
                    $hash = password_hash($new_password, PASSWORD_BCRYPT);
                    $stmt = $pdo->prepare("UPDATE users SET email=?, role=?, status=?, password_hash=? WHERE user_id=?");
                    $stmt->execute([$email, $role, $status, $hash, $user_id]);
                } else {
                    $stmt = $pdo->prepare("UPDATE users SET email=?, role=?, status=? WHERE user_id=?");
                    $stmt->execute([$email, $role, $status, $user_id]);
                }
                $flash = ['type' => 'success', 'msg' => 'Account updated successfully.'];
            } catch (Exception $e) {
                $flash = ['type' => 'error', 'msg' => 'Update failed: ' . $e->getMessage()];
            }
        }
    }

    elseif ($action === 'toggle_status') {
        $user_id = (int)($_POST['user_id'] ?? 0);
        if ($user_id !== (int)$_SESSION['user_id']) {
            $stmt = $pdo->prepare("UPDATE users SET status = IF(status='Active','Inactive','Active') WHERE user_id=?");
            $stmt->execute([$user_id]);
            $flash = ['type' => 'success', 'msg' => 'Status updated.'];
        }
    }
    elseif ($action === 'delete') {
        $user_id = (int)($_POST['user_id'] ?? 0);
        
        // Safety: Prevent deleting yourself
        if ($user_id === (int)$_SESSION['user_id']) {
            $flash = ['type' => 'error', 'msg' => 'You cannot delete your own account while logged in.'];
        } else {
            try {
                // Cascading delete: This will also remove the faculty profile due to our SQL constraints
                $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
                $stmt->execute([$user_id]);
                $flash = ['type' => 'success', 'msg' => 'Account and associated profile data deleted successfully.'];
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

// ── Filters & Pagination ──────────────────────────────────────
$search        = trim($_GET['search'] ?? '');
$filter_role   = $_GET['role'] ?? '';
$filter_status = $_GET['status'] ?? '';
$page          = max(1, (int)($_GET['page'] ?? 1));
$per_page      = 15;
$offset        = ($page - 1) * $per_page;

$where  = ["role != 'Student'"];
$params = [];

if ($search !== '')        { $where[] = "email LIKE ?"; $params[] = "%{$search}%"; }
if ($filter_role   !== '') { $where[] = "role = ?";     $params[] = $filter_role; }
if ($filter_status !== '') { $where[] = "status = ?";   $params[] = $filter_status; }

$where_sql = 'WHERE ' . implode(' AND ', $where);
$data_stmt = $pdo->prepare("SELECT * FROM users {$where_sql} ORDER BY role, email LIMIT {$per_page} OFFSET {$offset}");
$data_stmt->execute($params);
$users = $data_stmt->fetchAll();

$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM users {$where_sql}");
$count_stmt->execute($params);
$total_rows  = (int)$count_stmt->fetchColumn();
$total_pages = max(1, ceil($total_rows / $per_page));

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

        <div class="stat-grid" style="margin-bottom: 30px;">
            <?php foreach (['Admin' => '#ef4444', 'Registrar' => '#3b82f6', 'Cashier' => '#d97706', 'Faculty' => '#0891b2'] as $role => $color): ?>
            <div class="glass-panel" onclick="filterByRole('<?= $role ?>')" style="cursor:pointer;">
                <p class="text-muted" style="font-size:0.85rem;"><span style="width:8px;height:8px;background:<?=$color?>;border-radius:50%;display:inline-block;margin-right:6px;"></span><?= $role ?></p>
                <h3 style="font-size: 2rem; margin-top: 10px;"><?= $role_counts[$role] ?? 0 ?></h3>
            </div>
            <?php endforeach; ?>
        </div>

        <form method="GET" style="display:flex; gap:10px; margin-bottom:20px; flex-wrap:wrap;">
            <input type="text" name="search" placeholder="Search email..." value="<?= htmlspecialchars($search) ?>" class="form-control" style="width:250px;">
            <select name="role" id="role-filter" class="form-control" style="width:150px;">
                <option value="">All Roles</option>
                <option value="Admin" <?=$filter_role==='Admin'?'selected':''?>>Admin</option>
                <option value="Faculty" <?=$filter_role==='Faculty'?'selected':''?>>Faculty</option>
            </select>
            <button type="submit" class="btn btn-primary">Filter</button>
            <button type="button" class="btn btn-primary" style="margin-left:auto;" onclick="openModal('createModal')">+ New Account</button>
        </form>

        <div class="glass-panel" style="padding:0; overflow:hidden;">
            <table class="table-wrapper">
                <thead>
                    <tr>
                        <th style="padding:15px 20px;">Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th style="text-align:center;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                    <tr style="<?= $u['status']==='Inactive'?'opacity:0.5':'' ?>">
                        <td style="padding:13px 20px; font-weight:500;"><?= htmlspecialchars($u['email']) ?></td>
                        <td><span class="badge" style="border:1px solid var(--glass-border);"><?= $u['role'] ?></span></td>
                        <td><?= $u['status'] ?></td>
                        <td style="text-align:center;">
                            <button class="btn btn-outline" style="font-size:0.75rem; padding:4px 10px;" onclick='openEditModal(<?= json_encode($u) ?>)'>Edit</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

<div id="createModal" class="modal-overlay" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center;">
    <div class="glass-panel" style="width:100%; max-width:400px; padding:30px;">
        <h3 style="margin-bottom:20px;">Create Account</h3>
        <form method="POST">
            <input type="hidden" name="action" value="create">
            
            <div style="margin-bottom:15px;">
                <label style="display:block; font-size:0.8rem; margin-bottom:5px;">Email Address</label>
                <input type="email" name="email" class="form-control" required>
            </div>

            <div style="margin-bottom:15px;">
                <label style="display:block; font-size:0.8rem; margin-bottom:5px;">User Role</label>
                <select name="role" class="form-control" required onchange="toggleFacultyFields(this.value)">
                    <option value="" disabled selected>Choose role</option>
                    <option value="Admin">Admin</option>
                    <option value="Registrar">Registrar</option>
                    <option value="Cashier">Cashier</option>
                    <option value="Faculty">Faculty</option>
                </select>
            </div>

            <div id="faculty-fields" style="display:none; margin-bottom:15px; border-left:3px solid var(--primary-color); padding-left:15px;">
                <input type="text" name="first_name" placeholder="First Name" class="form-control" style="margin-bottom:10px;">
                <input type="text" name="last_name" placeholder="Last Name" class="form-control" style="margin-bottom:10px;">
                <input type="text" name="department" placeholder="Department" class="form-control">
            </div>

            <div style="margin-bottom:20px;">
                <label style="display:block; font-size:0.8rem; margin-bottom:5px;">Password</label>
                <input type="password" name="password" class="form-control" required minlength="8">
            </div>

            <div style="display:flex; justify-content:flex-end; gap:10px;">
                <button type="button" class="btn btn-outline" onclick="closeModal('createModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Create Account</button>
            </div>
        </form>
    </div>
</div>

<div id="editModal" class="modal-overlay" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center;">
    <div class="glass-panel" style="width:100%; max-width:400px; padding:30px;">
        <h3 style="margin-bottom:20px;">Edit Account</h3>
        <form method="POST">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="user_id" id="e-id">
            
            <div style="margin-bottom:15px;">
                <label style="display:block; font-size:0.8rem; margin-bottom:5px;">Email Address</label>
                <input type="email" name="email" id="e-email" class="form-control" required>
            </div>

            <div style="margin-bottom:15px;">
                <label style="display:block; font-size:0.8rem; margin-bottom:5px;">User Role</label>
                <select name="role" id="e-role" class="form-control" required>
                    <option value="Admin">Admin</option>
                    <option value="Registrar">Registrar</option>
                    <option value="Cashier">Cashier</option>
                    <option value="Faculty">Faculty</option>
                </select>
            </div>

            <div style="margin-bottom:15px;">
                <label style="display:block; font-size:0.8rem; margin-bottom:5px;">Status</label>
                <select name="status" id="e-status" class="form-control" required>
                    <option value="Active">Active</option>
                    <option value="Inactive">Inactive</option>
                </select>
            </div>

            <div style="margin-bottom:20px;">
                <label style="display:block; font-size:0.8rem; margin-bottom:5px;">New Password (Optional)</label>
                <input type="password" name="new_password" class="form-control" placeholder="Leave blank to keep current">
            </div>

            <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:22px;">
                <form method="POST" id="delete-form" style="margin-right:auto;">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="user_id" id="d-id">
                    <button type="button" class="btn btn-outline" 
                            style="color: #ef4444; border-color: #ef4444;" 
                            onclick="confirmDelete()">
                        Delete Account
                    </button>
                </form>

                <button type="button" class="btn btn-outline" onclick="closeModal('editModal')">Cancel</button>
                <button type="submit" form="edit-account-form" class="btn btn-primary">Save Changes</button>
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
function filterByRole(role) {
    document.getElementById('role-filter').value = role;
    document.querySelector('form').submit();
}
function openEditModal(u) {
    document.getElementById('e-id').value = u.user_id;
    document.getElementById('d-id').value = u.user_id; // Set ID for delete form
    document.getElementById('e-email').value = u.email;
    document.getElementById('e-role').value = u.role;
    document.getElementById('e-status').value = u.status;
    
    openModal('editModal');
}
function confirmDelete() {
    const userEmail = document.getElementById('e-email').value; // Grabs the email from the edit field
    const confirmation = confirm("Are you sure you want to delete " + userEmail + "?\n\nThis action cannot be undone and will remove all associated profile data.");
    
    if (confirmation) {
        document.getElementById('delete-form').submit();
    }
}
</script>
</body>
</html>