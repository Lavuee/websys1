<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pines National High School | Enrollment</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

    <nav class="navbar">
        <div class="logo-container">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-primary"><path d="M8 19h8a4 4 0 0 0 3.8-2.8l2-7A4 4 0 0 0 18 5h-1.5c-.8 0-1.5.3-2 1l-2.5 3.5"/></svg>
            Pines NHS
        </div>
        <div style="display: flex; align-items: center; gap: 15px;">
            <button id="theme-toggle" class="btn btn-outline" style="padding: 5px 10px; border-radius: 50%;">🌙</button>
            <a href="login.php" class="btn btn-primary">Go to Dashboard &rarr;</a>
        </div>
    </nav>

    <main style="flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; padding: 40px 20px;">
        
        <span class="glass-panel text-primary" style="padding: 5px 15px; border-radius: 20px; font-size: 0.85rem; font-weight: 600; margin-bottom: 20px;">
            • S.Y. 2025-2026 Enrollment Open
        </span>

        <h1 style="font-size: 3.5rem; line-height: 1.1; margin-bottom: 20px;">
            Welcome to Pines <br>
            <span class="text-primary">National High School</span>
        </h1>
        
        <p class="text-muted" style="max-width: 500px; margin-bottom: 40px; font-size: 1.1rem;">
            Enroll online, track your payments, and access your academic records — all in one place.
        </p>

        <div style="display: flex; gap: 15px; margin-bottom: 60px;">
            <a href="apply.php" class="btn btn-primary" style="padding: 12px 24px;">Apply for Enrollment &rarr;</a>
            <a href="login.php" class="btn btn-outline glass-panel" style="padding: 12px 24px;">Student Portal</a>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; max-width: 900px; width: 100%;">
            
            <div class="glass-panel" style="padding: 30px 20px;">
                <h3 style="margin-bottom: 10px; font-size: 1.1rem;">🛡️ Secure Registration</h3>
                <p class="text-muted" style="font-size: 0.9rem;">Submit your enrollment application directly through our portal.</p>
            </div>

            <div class="glass-panel" style="padding: 30px 20px;">
                <h3 style="margin-bottom: 10px; font-size: 1.1rem;">💳 Easy Payments</h3>
                <p class="text-muted" style="font-size: 0.9rem;">Pay via Cash or GCash with receipt upload and tracking.</p>
            </div>

            <div class="glass-panel" style="padding: 30px 20px;">
                <h3 style="margin-bottom: 10px; font-size: 1.1rem;">📖 View Records</h3>
                <p class="text-muted" style="font-size: 0.9rem;">Access your grades and enrollment status anytime.</p>
            </div>

        </div>
    </main>

    <footer style="text-align: center; padding: 20px; color: var(--text-muted); font-size: 0.85rem;">
        &copy; 2026 Pines National High School. All rights reserved.
    </footer>

    <script src="assets/js/main.js"></script>
</body>
</html>