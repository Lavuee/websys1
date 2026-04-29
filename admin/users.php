<?php
require_once '../actions/auth.php';
check_admin();
require_once '../config/db.php';

$current_page = basename($_SERVER['PHP_SELF']);
$flash = null;

// ── Handle POST actions ───────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $role     = $_POST['role'] ?? '';
        $allowed  = ['Admin', 'Registrar', 'Cashier', 'Faculty'];

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $flash = ['type' => 'error', 'msg' => 'Invalid email address.'];
        } elseif (strlen($password) < 8) {
            $flash = ['type' => 'error', 'msg' => 'Password must be at least 8 characters.'];
        } elseif (!in_array($role, $allowed)) {
            $flash = ['type' => 'error', 'msg' => 'Invalid role selected.'];
        } else {
            $chk = $pdo->prepare("SELECT user_id FROM users WHERE email = ? LIMIT 1");
            $chk->execute([$email]);
            if ($chk->fetch()) {
                $flash = ['type' => 'error', 'msg' => "Email {$email} is already registered."];
            } else {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("INSERT INTO users (email, password_hash, role, status) VALUES (?, ?, ?, 'Active')");
                $stmt->execute([$email, $hash, $role]);
                $flash = ['type' => 'success', 'msg' => "Account for {$email} created successfully."];
            }
        }
    }

    elseif ($action === 'update') {
        $user_id      = (int)($_POST['user_id'] ?? 0);
        $email        = trim($_POST['email'] ?? '');
        $role         = $_POST['role'] ?? '';
        $status       = $_POST['status'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $allowed_roles    = ['Admin', 'Registrar', 'Cashier', 'Faculty'];
        $allowed_statuses = ['Active', 'Inactive'];

        if ($user_id === (int)$_SESSION['user_id'] && $status === 'Inactive') {
            $flash = ['type' => 'error', 'msg' => 'You cannot deactivate your own account.'];
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $flash = ['type' => 'error', 'msg' => 'Invalid email address.'];
        } elseif (!in_array($role, $allowed_roles) || !in_array($status, $allowed_statuses)) {
            $flash = ['type' => 'error', 'msg' => 'Invalid role or status.'];
        } else {
            $chk = $pdo->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ? LIMIT 1");
            $chk->execute([$email, $user_id]);
            if ($chk->fetch()) {
                $flash = ['type' => 'error', 'msg' => "Email {$email} is already in use by another account."];
            } else {
                if (!empty($new_password)) {
                    if (strlen($new_password) < 8) {
                        $flash = ['type' => 'error', 'msg' => 'New password must be at least 8 characters.'];
                        goto end;
                    }
                    $hash = password_hash($new_password, PASSWORD_BCRYPT);
                    $stmt = $pdo->prepare("UPDATE users SET email=?, role=?, status=?, password_hash=? WHERE user_id=?");
                    $stmt->execute([$email, $role, $status, $hash, $user_id]);
                } else {
                    $stmt = $pdo->prepare("UPDATE users SET email=?, role=?, status=? WHERE user_id=?");
                    $stmt->execute([$email, $role, $status, $user_id]);
                }
                $flash = ['type' => 'success', 'msg' => 'Account updated successfully.'];
            }
        }
    }

    elseif ($action === 'toggle_status') {
        $user_id = (int)($_POST['user_id'] ?? 0);
        if ($user_id === (int)$_SESSION['user_id']) {
            $flash = ['type' => 'error', 'msg' => 'You cannot change the status of your own account.'];
        } else {
            $stmt = $pdo->prepare("UPDATE users SET status = IF(status='Active','Inactive','Active') WHERE user_id=?");
            $stmt->execute([$user_id]);
            $flash = ['type' => 'success', 'msg' => 'Account status updated.'];
        }
    }

    end:
    header("Location: users.php" . ($flash ? '?flash=' . urlencode(json_encode($flash)) : ''));
    exit();
}

if (!$flash && isset($_GET['flash'])) {
    $flash = json_decode(urldecode($_GET['flash']), true);
}

// ── Filters & pagination ──────────────────────────────────────
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

$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM users {$where_sql}");
$count_stmt->execute($params);
$total_rows  = (int)$count_stmt->fetchColumn();
$total_pages = max(1, ceil($total_rows / $per_page));

$data_stmt = $pdo->prepare("SELECT * FROM users {$where_sql} ORDER BY role, email LIMIT {$per_page} OFFSET {$offset}");
$data_stmt->execute($params);
$users = $data_stmt->fetchAll();

$role_counts = $pdo->query("SELECT role, COUNT(*) as cnt FROM users WHERE role != 'Student' GROUP BY role")->fetchAll(PDO::FETCH_KEY_PAIR);

include 'includes/admin_header.php';
?>
<body>
<div class="layout">

    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">

        <h2 style="margin-bottom: 5px;">User Accounts</h2>
        <p class="text-muted" style="margin-bottom: 30px; font-size: 0.9rem;">Manage staff accounts for Admin, Registrar, Cashier, and Faculty roles.</p>

        <!-- Flash message -->
        <?php if ($flash): ?>
        <div style="
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 24px;
            font-size: 0.875rem;
            font-weight: 500;
            background: <?= $flash['type'] === 'success' ? 'rgba(22,101,52,0.1)' : 'rgba(239,68,68,0.1)' ?>;
            color: <?= $flash['type'] === 'success' ? 'var(--primary-color)' : '#b91c1c' ?>;
            border: 1px solid <?= $flash['type'] === 'success' ? 'rgba(22,101,52,0.2)' : 'rgba(239,68,68,0.2)' ?>;
        "><?= htmlspecialchars($flash['msg']) ?></div>
        <?php endif; ?>

        <!-- Role summary stats -->
        <div class="stat-grid" style="margin-bottom: 30px;">
            <?php
            $role_meta = [
                'Admin'     => ['label' => 'Admins',     'color' => '#ef4444'],
                'Registrar' => ['label' => 'Registrars', 'color' => '#3b82f6'],
                'Cashier'   => ['label' => 'Cashiers',   'color' => '#d97706'],
                'Faculty'   => ['label' => 'Faculty',    'color' => '#0891b2'],
            ];
            foreach ($role_meta as $role => $meta): ?>
            <div class="glass-panel" style="cursor:pointer; transition: box-shadow 0.2s;" onclick="filterByRole('<?= $role ?>')"
                 onmouseover="this.style.boxShadow='0 4px 20px rgba(0,0,0,0.1)'"
                 onmouseout="this.style.boxShadow=''">
                <p class="text-muted" style="font-size: 0.85rem; display:flex; align-items:center; gap:6px;">
                    <span style="width:8px;height:8px;border-radius:50%;background:<?= $meta['color'] ?>;display:inline-block;flex-shrink:0"></span>
                    <?= $meta['label'] ?>
                </p>
                <h3 style="font-size: 2rem; margin-top: 10px;"><?= $role_counts[$role] ?? 0 ?></h3>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Filter bar -->
        <form method="GET" action="users.php" id="filter-form" style="margin-bottom: 20px;">
            <div style="display:flex; gap:10px; flex-wrap:wrap; align-items:center;">
                <input
                    type="text"
                    name="search"
                    placeholder="Search by email..."
                    value="<?= htmlspecialchars($search) ?>"
                    style="padding:8px 12px; border:1px solid var(--glass-border); border-radius:8px; font-size:0.875rem; background:var(--glass-bg); color:var(--text-main); outline:none; min-width:220px;"
                >
                <select name="role" id="role-filter"
                    style="padding:8px 12px; border:1px solid var(--glass-border); border-radius:8px; font-size:0.875rem; background:var(--glass-bg); color:var(--text-main); outline:none;">
                    <option value="">All Roles</option>
                    <?php foreach (array_keys($role_meta) as $r): ?>
                    <option value="<?= $r ?>" <?= $filter_role === $r ? 'selected' : '' ?>><?= $r ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="status"
                    style="padding:8px 12px; border:1px solid var(--glass-border); border-radius:8px; font-size:0.875rem; background:var(--glass-bg); color:var(--text-main); outline:none;">
                    <option value="">All Statuses</option>
                    <option value="Active"   <?= $filter_status === 'Active'   ? 'selected' : '' ?>>Active</option>
                    <option value="Inactive" <?= $filter_status === 'Inactive' ? 'selected' : '' ?>>Inactive</option>
                </select>
                <button type="submit" class="btn btn-primary" style="font-size:0.875rem; padding:8px 16px;">Filter</button>
                <a href="users.php" class="btn btn-outline" style="font-size:0.875rem; padding:8px 16px;">Clear</a>
                <button type="button" class="btn btn-primary" style="font-size:0.875rem; padding:8px 16px; margin-left:auto;" onclick="openModal('createModal')">
                    + New Account
                </button>
            </div>
        </form>

        <!-- Users table -->
        <div class="glass-panel" style="padding: 0; overflow: hidden;">
            <table class="table-wrapper">
                <thead>
                    <tr>
                        <th style="padding: 15px 20px;">Account</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th style="text-align:center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="5" class="text-muted" style="text-align:center; padding:40px;">
                            No accounts found<?= ($search || $filter_role || $filter_status) ? ' matching your filters' : '' ?>.
                        </td>
                    </tr>
                    <?php else: foreach ($users as $u):
                        $is_self  = (int)$u['user_id'] === (int)$_SESSION['user_id'];
                        $role_colors = [
                            'Admin'     => '#ef4444',
                            'Registrar' => '#3b82f6',
                            'Cashier'   => '#d97706',
                            'Faculty'   => '#0891b2',
                        ];
                        $avatar_color = $role_colors[$u['role']] ?? '#64748b';
                    ?>
                    <tr style="<?= $u['status'] === 'Inactive' ? 'opacity:0.45' : '' ?>">
                        <td style="padding: 13px 20px;">
                            <div style="display:flex; align-items:center; gap:12px;">
                                <div style="width:34px;height:34px;border-radius:50%;background:<?= $avatar_color ?>;display:flex;align-items:center;justify-content:center;font-size:0.8rem;font-weight:700;color:#fff;flex-shrink:0;">
                                    <?= strtoupper(substr($u['email'], 0, 1)) ?>
                                </div>
                                <div>
                                    <div style="font-weight:500; font-size:0.9rem;"><?= htmlspecialchars($u['email']) ?></div>
                                    <div class="text-muted" style="font-size:0.75rem;">
                                        ID #<?= $u['user_id'] ?><?= $is_self ? ' &middot; <span style="color:var(--primary-color);font-weight:600;">you</span>' : '' ?>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="badge" style="background:<?= $avatar_color ?>22; color:<?= $avatar_color ?>; border:1px solid <?= $avatar_color ?>44;">
                                <?= htmlspecialchars($u['role']) ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($u['status'] === 'Active'): ?>
                            <span class="badge badge-pending" style="background:rgba(22,101,52,0.12);color:var(--primary-color);">Active</span>
                            <?php else: ?>
                            <span class="badge" style="background:rgba(100,116,139,0.12);color:#64748b;">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-muted" style="font-size:0.875rem;"><?= date('M d, Y', strtotime($u['created_at'])) ?></td>
                        <td style="text-align:center;">
                            <button
                                class="btn btn-outline"
                                style="font-size:0.78rem; padding:5px 12px; margin-right:4px;"
                                onclick='openEditModal(<?= json_encode($u) ?>)'>
                                Edit
                            </button>
                            <?php if (!$is_self): ?>
                            <form method="POST" style="display:inline" onsubmit="return confirm('<?= $u['status'] === 'Active' ? 'Deactivate' : 'Activate' ?> this account?')">
                                <input type="hidden" name="action"  value="toggle_status">
                                <input type="hidden" name="user_id" value="<?= $u['user_id'] ?>">
                                <button type="submit" class="btn btn-outline" style="font-size:0.78rem; padding:5px 12px; color:<?= $u['status'] === 'Active' ? '#ef4444' : 'var(--primary-color)' ?>; border-color:<?= $u['status'] === 'Active' ? '#ef4444' : 'var(--primary-color)' ?>;">
                                    <?= $u['status'] === 'Active' ? 'Deactivate' : 'Activate' ?>
                                </button>
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <div style="padding:14px 20px; border-top:1px solid var(--glass-border); display:flex; justify-content:space-between; align-items:center;">
                <span class="text-muted" style="font-size:0.82rem;">Page <?= $page ?> of <?= $total_pages ?> &middot; <?= number_format($total_rows) ?> total</span>
                <div style="display:flex; gap:4px;">
                    <a class="btn btn-outline" style="padding:4px 10px; font-size:0.82rem; <?= $page<=1?'opacity:0.4;pointer-events:none':'' ?>"
                        href="?<?= http_build_query(array_merge($_GET, ['page'=>$page-1])) ?>">‹</a>
                    <?php for ($i=max(1,$page-2); $i<=min($total_pages,$page+2); $i++): ?>
                    <a class="btn <?= $i===$page?'btn-primary':'btn-outline' ?>" style="padding:4px 10px; font-size:0.82rem;"
                        href="?<?= http_build_query(array_merge($_GET, ['page'=>$i])) ?>"><?= $i ?></a>
                    <?php endfor; ?>
                    <a class="btn btn-outline" style="padding:4px 10px; font-size:0.82rem; <?= $page>=$total_pages?'opacity:0.4;pointer-events:none':'' ?>"
                        href="?<?= http_build_query(array_merge($_GET, ['page'=>$page+1])) ?>">›</a>
                </div>
            </div>
            <?php endif; ?>
        </div>

    </main>
</div>

<!-- ── Create Modal ───────────────────────────────────────── -->
<div id="createModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.45); z-index:1000; align-items:center; justify-content:center;">
    <div class="glass-panel" style="width:100%; max-width:420px; padding:28px; margin:20px; box-shadow:0 20px 60px rgba(0,0,0,0.2);">
        <h3 style="font-size:1rem; font-weight:600; margin-bottom:20px;">New Staff Account</h3>
        <form method="POST" action="users.php" novalidate>
            <input type="hidden" name="action" value="create">
            <div style="margin-bottom:14px;">
                <label style="display:block; font-size:0.78rem; font-weight:600; color:var(--text-muted); text-transform:uppercase; letter-spacing:0.04em; margin-bottom:6px;">Email Address</label>
                <input type="email" name="email" placeholder="user@pines.edu.ph" required
                    style="width:100%; padding:9px 12px; border:1px solid var(--glass-border); border-radius:8px; font-size:0.875rem; background:var(--glass-bg); color:var(--text-main); outline:none; box-sizing:border-box;">
            </div>
            <div style="margin-bottom:14px;">
                <label style="display:block; font-size:0.78rem; font-weight:600; color:var(--text-muted); text-transform:uppercase; letter-spacing:0.04em; margin-bottom:6px;">Role</label>
                <select name="role" required
                    style="width:100%; padding:9px 12px; border:1px solid var(--glass-border); border-radius:8px; font-size:0.875rem; background:var(--glass-bg); color:var(--text-main); outline:none; box-sizing:border-box;">
                    <option value="" disabled selected>Select a role</option>
                    <option value="Admin">Admin</option>
                    <option value="Registrar">Registrar</option>
                    <option value="Cashier">Cashier</option>
                    <option value="Faculty">Faculty</option>
                </select>
            </div>
            <div style="margin-bottom:6px;">
                <label style="display:block; font-size:0.78rem; font-weight:600; color:var(--text-muted); text-transform:uppercase; letter-spacing:0.04em; margin-bottom:6px;">Password</label>
                <div style="position:relative;">
                    <input type="password" name="password" id="c-pw" placeholder="Min. 8 characters" oninput="checkStrength(this,'c-bar','c-hint')" required
                        style="width:100%; padding:9px 40px 9px 12px; border:1px solid var(--glass-border); border-radius:8px; font-size:0.875rem; background:var(--glass-bg); color:var(--text-main); outline:none; box-sizing:border-box;">
                    <button type="button" onclick="togglePw('c-pw')"
                        style="position:absolute; right:10px; top:50%; transform:translateY(-50%); background:none; border:none; cursor:pointer; color:var(--text-muted); font-size:1rem;">👁</button>
                </div>
                <div style="height:4px; background:var(--glass-border); border-radius:2px; margin-top:6px;">
                    <div id="c-bar" style="height:4px; border-radius:2px; width:0; transition:width 0.3s,background 0.3s;"></div>
                </div>
                <p id="c-hint" style="font-size:0.75rem; color:var(--text-muted); margin-top:4px;">Enter a password</p>
            </div>
            <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:22px;">
                <button type="button" class="btn btn-outline" onclick="closeModal('createModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Create Account</button>
            </div>
        </form>
    </div>
</div>

<!-- ── Edit Modal ─────────────────────────────────────────── -->
<div id="editModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.45); z-index:1000; align-items:center; justify-content:center;">
    <div class="glass-panel" style="width:100%; max-width:420px; padding:28px; margin:20px; box-shadow:0 20px 60px rgba(0,0,0,0.2);">
        <h3 style="font-size:1rem; font-weight:600; margin-bottom:20px;">Edit Account</h3>
        <form method="POST" action="users.php" novalidate>
            <input type="hidden" name="action"  value="update">
            <input type="hidden" name="user_id" id="e-id">
            <div style="margin-bottom:14px;">
                <label style="display:block; font-size:0.78rem; font-weight:600; color:var(--text-muted); text-transform:uppercase; letter-spacing:0.04em; margin-bottom:6px;">Email Address</label>
                <input type="email" name="email" id="e-email" required
                    style="width:100%; padding:9px 12px; border:1px solid var(--glass-border); border-radius:8px; font-size:0.875rem; background:var(--glass-bg); color:var(--text-main); outline:none; box-sizing:border-box;">
            </div>
            <div style="margin-bottom:14px;">
                <label style="display:block; font-size:0.78rem; font-weight:600; color:var(--text-muted); text-transform:uppercase; letter-spacing:0.04em; margin-bottom:6px;">Role</label>
                <select name="role" id="e-role" required
                    style="width:100%; padding:9px 12px; border:1px solid var(--glass-border); border-radius:8px; font-size:0.875rem; background:var(--glass-bg); color:var(--text-main); outline:none; box-sizing:border-box;">
                    <option value="Admin">Admin</option>
                    <option value="Registrar">Registrar</option>
                    <option value="Cashier">Cashier</option>
                    <option value="Faculty">Faculty</option>
                </select>
            </div>
            <div style="margin-bottom:14px;">
                <label style="display:block; font-size:0.78rem; font-weight:600; color:var(--text-muted); text-transform:uppercase; letter-spacing:0.04em; margin-bottom:6px;">Status</label>
                <select name="status" id="e-status" required
                    style="width:100%; padding:9px 12px; border:1px solid var(--glass-border); border-radius:8px; font-size:0.875rem; background:var(--glass-bg); color:var(--text-main); outline:none; box-sizing:border-box;">
                    <option value="Active">Active</option>
                    <option value="Inactive">Inactive</option>
                </select>
            </div>
            <hr style="border:none; border-top:1px solid var(--glass-border); margin:16px 0;">
            <div style="margin-bottom:6px;">
                <label style="display:block; font-size:0.78rem; font-weight:600; color:var(--text-muted); text-transform:uppercase; letter-spacing:0.04em; margin-bottom:6px;">
                    New Password <span style="font-weight:400; text-transform:none;">(leave blank to keep current)</span>
                </label>
                <div style="position:relative;">
                    <input type="password" name="new_password" id="e-pw" placeholder="Min. 8 characters" oninput="checkStrength(this,'e-bar','e-hint')"
                        style="width:100%; padding:9px 40px 9px 12px; border:1px solid var(--glass-border); border-radius:8px; font-size:0.875rem; background:var(--glass-bg); color:var(--text-main); outline:none; box-sizing:border-box;">
                    <button type="button" onclick="togglePw('e-pw')"
                        style="position:absolute; right:10px; top:50%; transform:translateY(-50%); background:none; border:none; cursor:pointer; color:var(--text-muted); font-size:1rem;">👁</button>
                </div>
                <div style="height:4px; background:var(--glass-border); border-radius:2px; margin-top:6px;">
                    <div id="e-bar" style="height:4px; border-radius:2px; width:0; transition:width 0.3s,background 0.3s;"></div>
                </div>
                <p id="e-hint" style="font-size:0.75rem; color:var(--text-muted); margin-top:4px;">Leave blank to keep current password</p>
            </div>
            <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:22px;">
                <button type="button" class="btn btn-outline" onclick="closeModal('editModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script src="../assets/js/main.js"></script>
<script>
function openModal(id) {
    const m = document.getElementById(id);
    m.style.display = 'flex';
}
function closeModal(id) {
    document.getElementById(id).style.display = 'none';
}
// Close on backdrop click
['createModal','editModal'].forEach(id => {
    document.getElementById(id).addEventListener('click', function(e) {
        if (e.target === this) closeModal(id);
    });
});
function openEditModal(u) {
    document.getElementById('e-id').value     = u.user_id;
    document.getElementById('e-email').value  = u.email;
    document.getElementById('e-role').value   = u.role;
    document.getElementById('e-status').value = u.status;
    document.getElementById('e-pw').value     = '';
    document.getElementById('e-bar').style.width = '0';
    document.getElementById('e-hint').textContent = 'Leave blank to keep current password';
    openModal('editModal');
}
function togglePw(id) {
    const i = document.getElementById(id);
    i.type = i.type === 'password' ? 'text' : 'password';
}
function checkStrength(input, barId, hintId) {
    const val  = input.value;
    const bar  = document.getElementById(barId);
    const hint = document.getElementById(hintId);
    let score  = 0;
    if (val.length >= 8)           score++;
    if (/[A-Z]/.test(val))         score++;
    if (/[0-9]/.test(val))         score++;
    if (/[^A-Za-z0-9]/.test(val))  score++;
    const levels = [
        { w:'0%',   c:'#ef4444', t:'Too short' },
        { w:'25%',  c:'#ef4444', t:'Weak'      },
        { w:'50%',  c:'#d97706', t:'Fair'      },
        { w:'75%',  c:'#2563eb', t:'Good'      },
        { w:'100%', c:'#166534', t:'Strong'    },
    ];
    const lvl = val.length === 0 ? levels[0] : levels[Math.min(score,4)];
    bar.style.width      = lvl.w;
    bar.style.background = lvl.c;
    hint.textContent     = val.length === 0
        ? (hintId === 'e-hint' ? 'Leave blank to keep current password' : 'Enter a password')
        : lvl.t;
}
function filterByRole(role) {
    document.getElementById('role-filter').value = role;
    document.getElementById('filter-form').submit();
}
</script>
</body>
</html>