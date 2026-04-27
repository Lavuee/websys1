<?php
// auth/auth_check.php
// ─────────────────────────────────────────────────────────────
// Include this at the very top of every protected page.
// Call check_auth() for any logged-in user.
// Call check_admin() for admin-only pages.
//
// Usage:
//   require_once '../auth/auth_check.php';
//   check_auth();          // any logged-in user
//   check_admin();         // admin only
// ─────────────────────────────────────────────────────────────

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Ensures the user is logged in.
 * Redirects to login.php if not authenticated.
 */
function check_auth() {
    if (!isset($_SESSION['user_id']) || empty($_SESSION['role'])) {
        header("Location: " . _login_path() . "?ref=auth");
        exit();
    }
}

/**
 * Ensures the user is logged in AND is an admin.
 * Redirects to login.php if not authenticated or not admin.
 */
function check_admin() {
    check_auth();
    if ($_SESSION['role'] !== 'admin') {
        header("Location: " . _login_path() . "?ref=forbidden");
        exit();
    }
}

function check_student() {
    check_auth();
    if ($_SESSION['role'] !== 'student') {
        $login = _login_path();
        header("Location: {$login}?ref=forbidden");
        exit();
    }
}
/**
 * Returns the path to login.php relative to the calling file's depth.
 * Adjust if your folder structure differs.
 */
function _login_path() {
    // Works from /admin/ and /student/ subdirectories
    $depth = substr_count($_SERVER['PHP_SELF'], '/') - 1;
    return str_repeat('../', max(0, $depth - 1)) . 'login.php';
}