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
        <a href="subjects.php"          class="nav-link <?= ($current_page == 'subjects.php')          ? 'active' : '' ?>">Subjects Management</a>
    </nav>
    
    <div style="border-top: 1px solid var(--glass-border); padding-top: 20px; margin-top: auto;">
        
        <div class="theme-switch-wrapper">
            <span style="font-size: 0.85rem; font-weight: 600; color: var(--text-muted);">Dark Mode</span>
            <label class="theme-switch">
                <input type="checkbox" id="theme-toggle-checkbox" onchange="toggleTheme()">
                <span class="slider"></span>
            </label>
        </div>
        
                <p style="font-size: 0.85rem; margin-bottom: 10px; font-weight: 600;"><?= htmlspecialchars($_SESSION['user_email']) ?></p>
                <a href="../logout.php" class="text-muted" style="font-size: 0.85rem; text-decoration: none;">Sign Out &rarr;</a>
            </div>
        </aside>

    <script>
    // Toggle function triggered when the slider is clicked
    function toggleTheme() {
        const html = document.documentElement;
        const checkbox = document.getElementById('theme-toggle-checkbox');
        
        if (html.hasAttribute('data-theme')) {
            // Switch to Light Mode
            html.removeAttribute('data-theme');
            localStorage.setItem('theme', 'light');
            if (checkbox) checkbox.checked = false;
        } else {
            // Switch to Dark Mode
            html.setAttribute('data-theme', 'dark');
            localStorage.setItem('theme', 'dark');
            if (checkbox) checkbox.checked = true;
        }
    }

    // Run immediately on page load to ensure the slider matches the saved theme
    document.addEventListener('DOMContentLoaded', () => {
        const checkbox = document.getElementById('theme-toggle-checkbox');
        if (localStorage.getItem('theme') === 'dark' || 
        (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            if (checkbox) checkbox.checked = true; // Slide to the right if dark mode is active
        }
    });
</script>