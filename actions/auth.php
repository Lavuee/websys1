<?php
// Pines EMS Security Module
// Enforces session state initialization prior to executing authorization logic.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Validates active authentication status.
 * Redirects unauthorized requests to the primary login portal.
 */
function check_auth() {
    if (!isset($_SESSION['user_id']) || empty($_SESSION['role'])) {
        header("Location: ../login.php?ref=auth");
        exit();
    }
}

/**
 * Restricts module access t    o administrative personnel.
 * Redirects non-compliant requests to prevent privilege escalation.
 */
function check_admin() {
    check_auth();
    if (strtolower($_SESSION['role']) !== 'admin') {
        header("Location: ../login.php?ref=forbidden");
        exit();
    }
}

/**
 * Restricts module access to enrolled students.
 * Prevents administrative accounts from accessing student-specific interfaces.
 */
function check_student() {
    check_auth();
    if (strtolower($_SESSION['role']) !== 'student') {
        header("Location: ../login.php?ref=forbidden");
        exit();
    }
}

function check_faculty() {
    check_auth();
    if (strtolower($_SESSION['role']) !== 'faculty') {
        header("Location: ../login.php?ref=forbidden");
        exit();
    }
}
?>