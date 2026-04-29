<aside class="sidebar">
    <div class="logo-container" style="margin-bottom: 40px;">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-primary"><path d="M8 19h8a4 4 0 0 0 3.8-2.8l2-7A4 4 0 0 0 18 5h-1.5c-.8 0-1.5.3-2 1l-2.5 3.5"/></svg>
        Pines NHS
    </div>
    
    <nav style="flex: 1;">
        <a href="dashboard.php"         class="nav-link <?= ($current_page == 'dashboard.php')         ? 'active' : '' ?>">Dashboard</a>
        <a href="enrollment_period.php" class="nav-link <?= ($current_page == 'enrollment_period.php') ? 'active' : '' ?>">Enrollment Periods</a>
        <a href="students.php"          class="nav-link <?= ($current_page == 'students.php')          ? 'active' : '' ?>">Student Management</a>
        <a href="payments.php"          class="nav-link <?= ($current_page == 'payments.php')          ? 'active' : '' ?>">Payment Verification</a>
        <a href="grades.php"            class="nav-link <?= ($current_page == 'grades.php')            ? 'active' : '' ?>">Grade Management</a>
        <a href="users.php"             class="nav-link <?= ($current_page == 'users.php')             ? 'active' : '' ?>">User Accounts</a>
    </nav>
    
    <div style="border-top: 1px solid var(--glass-border); padding-top: 20px; margin-top: auto;">
        <button onclick="toggleTheme()" class="btn btn-outline" style="width: 100%; margin-bottom: 15px; font-size: 0.8rem;">
            Toggle Theme
        </button>
        <p style="font-size: 0.85rem; margin-bottom: 10px; font-weight: 600;"><?= htmlspecialchars($_SESSION['user_email']) ?></p>
        <a href="../logout.php" class="text-muted" style="font-size: 0.85rem;">Sign Out</a>
    </div>
</aside>

<script>
function toggleTheme() {
    const html = document.documentElement;
    if (html.hasAttribute('data-theme')) {
        html.removeAttribute('data-theme');
        localStorage.setItem('theme', 'light');
    } else {
        html.setAttribute('data-theme', 'dark');
        localStorage.setItem('theme', 'dark');
    }
}
</script>