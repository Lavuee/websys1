<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pines National High School | Enrollment</title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?= time(); ?>">
    
    <script>
        if (localStorage.getItem('theme') === 'dark' || 
           (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.setAttribute('data-theme', 'dark');
        } else {
            document.documentElement.removeAttribute('data-theme');
        }
    </script>
</head>
<body style="display: flex; flex-direction: column; min-height: 100vh;">

    <nav style="display: flex; justify-content: space-between; align-items: center; padding: 15px 40px; background: var(--glass-bg); backdrop-filter: blur(12px); border-bottom: 1px solid var(--glass-border); position: sticky; top: 0; z-index: 100;">
        <div style="display: flex; align-items: center; gap: 10px; font-weight: 700; font-size: 1.2rem; color: var(--text-main);">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="var(--primary-color)" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M8 19h8a4 4 0 0 0 3.8-2.8l2-7A4 4 0 0 0 18 5h-1.5c-.8 0-1.5.3-2 1l-2.5 3.5"/></svg>
            Pines NHS
        </div>
        <div class="theme-switch-wrapper" style="margin-bottom: 0; margin-right: 10px;" title="Toggle Theme">
            <label class="theme-switch">
                <input type="checkbox" id="theme-toggle-checkbox" onchange="toggleTheme()">
                <span class="slider"></span>
            </label>
        </div>
    </nav>

    <main style="flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; padding: 80px 20px;">
        
        <span class="badge" style="background: rgba(22, 101, 52, 0.15); color: var(--primary-color); border: 1px solid var(--primary-color); padding: 6px 16px; margin-bottom: 25px; font-size: 0.85rem;">
            • S.Y. 2026-2027 Enrollment Open
        </span>

        <h1 style="font-size: 3.5rem; line-height: 1.2; font-weight: 700; margin-bottom: 20px; color: var(--text-main);">
            Welcome to Pines <br>
            <span style="color: var(--primary-color);">National High School</span>
        </h1>
        
        <p style="color: var(--text-muted); max-width: 550px; margin-bottom: 40px; font-size: 1.1rem; line-height: 1.6;">
            Enroll online, track your payments, and access your academic records — all in one secure place.
        </p>

        <div style="display: flex; gap: 15px; margin-bottom: 60px; flex-wrap: wrap; justify-content: center;">
            <a href="apply.php" class="btn btn-primary" style="padding: 12px 24px; font-size: 1rem;">Registration &rarr;</a>
            <a href="actions/track_status.php" class="btn btn-outline" style="padding: 12px 24px; font-size: 1rem;">Track Application</a>
            <a href="login.php" class="btn btn-outline" style="padding: 12px 24px; font-size: 1rem;">Student Portal</a>
        </div>

        <div class="stat-grid" style="max-width: 1000px; width: 100%; margin: 0 auto;">
            
            <div class="glass-panel" style="text-align: left;">
                <div style="font-size: 1.5rem; margin-bottom: 15px;"></div>
                <h3 style="margin-bottom: 10px; font-size: 1.1rem; color: var(--text-main);">Secure Registration</h3>
                <p style="color: var(--text-muted); font-size: 0.9rem; line-height: 1.5;">Submit your enrollment application directly through our encrypted portal.</p>
            </div>

            <div class="glass-panel" style="text-align: left;">
                <div style="font-size: 1.5rem; margin-bottom: 15px;"></div>
                <h3 style="margin-bottom: 10px; font-size: 1.1rem; color: var(--text-main);">Easy Payments</h3>
                <p style="color: var(--text-muted); font-size: 0.9rem; line-height: 1.5;">Pay via Cash or GCash with instant receipt upload and status tracking.</p>
            </div>

            <div class="glass-panel" style="text-align: left;">
                <div style="font-size: 1.5rem; margin-bottom: 15px;"></div>
                <h3 style="margin-bottom: 10px; font-size: 1.1rem; color: var(--text-main);">View Records</h3>
                <p style="color: var(--text-muted); font-size: 0.9rem; line-height: 1.5;">Access your academic grades and enrollment status anytime, anywhere.</p>
            </div>

        </div>
    </main>

    <footer style="text-align: center; padding: 30px; color: var(--text-muted); font-size: 0.85rem; border-top: 1px solid var(--glass-border); background: var(--glass-bg);">
        &copy; <?= date('Y') ?> Pines National High School. All rights reserved.
    </footer>

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
    <script src="assets/js/main.js"></script>
</body>
</html>
