<?php
require_once APP_ROOT . '/includes/auth.php';
require_once APP_ROOT . '/config/db.php';

$appBranding = app_branding($pdo);
$setupRequired = (int)$pdo->query("SELECT COUNT(*) FROM it_staff")->fetchColumn() === 0;

if (auth_current_id() > 0) {
    header('Location: ' . auth_landing_page());
    exit;
}

$error = '';
$statusCode = 200;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify_post();

    $username = isset($_POST['username']) && is_string($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) && is_string($_POST['password']) ? $_POST['password'] : '';
    $ipAddress = (string)($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    $ipHash = hash('sha256', $ipAddress);

    try {
        $attempts = $pdo->prepare(
            'SELECT COUNT(*) FROM login_attempts WHERE ip_hash = ? AND failed_at >= DATE_SUB(NOW(), INTERVAL 15 MINUTE)'
        );
        $attempts->execute([$ipHash]);
        if ((int)$attempts->fetchColumn() >= 10) {
            $error = 'Too many unsuccessful sign-in attempts. Please wait 15 minutes and try again.';
            $statusCode = 429;
        } elseif ($username !== '' && strlen($username) <= 100 && $password !== '' && strlen($password) <= 1024) {
            $stmt = $pdo->prepare(
                'SELECT id, full_name, username, password_hash, role, is_active FROM it_staff WHERE username = ? LIMIT 1'
            );
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            $passwordMatches = $user
                ? password_verify($password, (string)$user['password_hash'])
                : password_verify($password, '$2y$10$uZtktBDlsxiTLBuTQlqPmOfyyG/Gt8LlLULolx3Na1IioYfkNgO.6');

            if ($user && (int)$user['is_active'] === 1 && $passwordMatches) {
                $_SESSION = [];
                if (!session_regenerate_id(true)) {
                    throw new RuntimeException('Unable to rotate the session identifier after sign-in.');
                }
                $_SESSION['staff_id'] = (int)$user['id'];
                $_SESSION['staff_name'] = (string)$user['full_name'];
                $_SESSION['staff_role'] = (string)$user['role'];

                log_activity($pdo, 'Login', 'it_staff', (int)$user['id'], null, 'User logged in');
                header('Location: ' . auth_landing_page());
                exit;
            }

            $failed = $pdo->prepare('INSERT INTO login_attempts (ip_hash) VALUES (?)');
            $failed->execute([$ipHash]);
            if (random_int(1, 100) === 1) {
                $pdo->exec('DELETE FROM login_attempts WHERE failed_at < DATE_SUB(NOW(), INTERVAL 1 DAY)');
            }
            $error = 'The username or password is incorrect.';
        } else {
            $failed = $pdo->prepare('INSERT INTO login_attempts (ip_hash) VALUES (?)');
            $failed->execute([$ipHash]);
            $error = 'Enter a username and password within the allowed length.';
        }
    } catch (Throwable $exception) {
        user_error_log('Login request could not be completed.', $exception);
        $error = 'Sign-in is temporarily unavailable. Please try again later.';
        $statusCode = 503;
    }
}

if ($statusCode !== 200) {
    http_response_code($statusCode);
}
$csrf = e(csrf_token());
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title>Sign in | <?php echo e($appBranding['product_name']); ?></title>
    <style>
        :root {
            color-scheme: light;
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            color: #1f2937;
            background: #f9fafb;
            font-synthesis: none;
            text-rendering: optimizeLegibility;
        }
        * { box-sizing: border-box; }
        body {
            min-height: 100vh;
            margin: 0;
            display: grid;
            place-items: center;
            padding: 24px 16px;
            background:
                radial-gradient(circle at 12% 14%, rgba(59, 130, 246, .07), transparent 25rem),
                radial-gradient(circle at 88% 86%, rgba(14, 165, 233, .06), transparent 23rem),
                #f9fafb;
        }
        .login-shell { width: min(100%, 450px); overflow: hidden; border: 1px solid #e5e7eb; border-radius: 20px; background: #fff; box-shadow: 0 24px 64px rgba(15, 23, 42, .12); }
        .login-brand { position: relative; isolation: isolate; overflow: hidden; min-height: 166px; padding: 28px 32px; color: #fff; background: linear-gradient(145deg, #020617, #0f172a 72%, #172554); }
        .login-brand::before { position: absolute; z-index: -1; top: -86px; right: -36px; width: 250px; height: 250px; border: 1px solid rgba(125, 211, 252, .2); border-radius: 50%; content: ""; box-shadow: 0 0 0 24px rgba(125, 211, 252, .035), 0 0 0 50px rgba(125, 211, 252, .025); }
        .login-brand::after { position: absolute; z-index: -1; right: 30px; bottom: -68px; width: 120px; height: 120px; border: 1px solid rgba(96, 165, 250, .4); border-radius: 28px; content: ""; transform: rotate(38deg); }
        .brand { position: relative; display: flex; align-items: center; gap: 13px; }
        .brand-mark { display: grid; width: 48px; height: 48px; flex: none; place-items: center; overflow: hidden; border: 1px solid rgba(255, 255, 255, .18); border-radius: 12px; background: rgba(255, 255, 255, .1); }
        .brand-mark svg { width: 27px; height: 27px; }
        .brand-name { margin: 0; color: #fff; font-size: 15px; font-weight: 760; letter-spacing: .015em; }
        .brand-caption { margin: 4px 0 0; color: #94a3b8; font-size: 10px; font-weight: 650; letter-spacing: .13em; text-transform: uppercase; }
        .access-kicker { display: inline-flex; align-items: center; gap: 8px; margin-top: 26px; color: #bae6fd; font-size: 9px; font-weight: 750; letter-spacing: .2em; text-transform: uppercase; }
        .access-kicker::before { width: 17px; height: 2px; background: #38bdf8; content: ""; }
        .form-panel { padding: 31px 34px 27px; }
        .form-heading { margin-bottom: 25px; }
        .form-heading h1 { margin: 0; color: #111827; font-size: 26px; font-weight: 750; letter-spacing: -.045em; }
        .form-heading p { margin: 8px 0 0; color: #6b7280; font-size: 13px; line-height: 1.6; }
        .field { margin-top: 18px; }
        label { display: block; margin-bottom: 7px; color: #374151; font-size: 11px; font-weight: 750; letter-spacing: .025em; }
        .input-wrap { position: relative; }
        input { width: 100%; height: 47px; border: 1px solid #e5e7eb; border-radius: 10px; outline: 0; background: #f9fafb; padding: 0 13px; color: #111827; font: inherit; font-size: 13px; transition: border-color .16s, box-shadow .16s, background .16s; }
        input::placeholder { color: #9ca3af; }
        input:focus { border-color: #60a5fa; background: #fff; box-shadow: 0 0 0 4px rgba(59, 130, 246, .12); }
        input[type=password], input.password-visible { padding-right: 68px; }
        .toggle-password { position: absolute; top: 0; right: 0; height: 47px; padding: 0 13px; border: 0; background: transparent; color: #64748b; font: inherit; font-size: 11px; font-weight: 750; cursor: pointer; }
        .toggle-password:hover { color: #1d4ed8; }
        .error { display: flex; gap: 10px; align-items: flex-start; margin: 0 0 17px; padding: 11px 12px; border: 1px solid #fecaca; border-radius: 10px; background: #fef2f2; color: #b91c1c; font-size: 12px; line-height: 1.5; }
        .error svg { width: 17px; height: 17px; flex: none; margin-top: 1px; }
        .submit { display: flex; width: 100%; min-height: 48px; align-items: center; justify-content: space-between; margin-top: 23px; padding: 0 15px; border: 0; border-radius: 10px; background: #1d4ed8; color: #fff; font: inherit; font-size: 12px; font-weight: 750; cursor: pointer; transition: background .16s, transform .16s; }
        .submit::after { color: #bfdbfe; content: "→"; font-size: 19px; font-weight: 400; line-height: 1; }
        .submit:hover { background: #1e40af; transform: translateY(-1px); }
        .submit:focus-visible, .toggle-password:focus-visible { outline: 3px solid #93c5fd; outline-offset: 3px; }
        .form-foot { display: flex; justify-content: space-between; gap: 12px; margin: 22px 0 0; padding-top: 14px; border-top: 1px solid #f3f4f6; color: #9ca3af; font-size: 9px; line-height: 1.5; }
        .form-foot span:last-child { text-align: right; }
        @media (max-width: 480px) {
            body { padding: 14px; }
            .login-shell { border-radius: 19px; }
            .login-brand { min-height: 150px; padding: 24px; }
            .form-panel { padding: 27px 24px 23px; }
            .form-foot { font-size: 8px; }
        }
        @media (prefers-reduced-motion: reduce) { *, *::before, *::after { scroll-behavior: auto !important; transition-duration: .01ms !important; } }
    </style>
</head>
<body>
    <main class="login-shell">
        <header class="login-brand" aria-label="<?php echo e($appBranding['product_name']); ?>">
            <div class="brand">
                <?php if ($appBranding['company_logo_path'] !== ''): ?>
                    <span class="brand-mark"><img src="brand_logo.php" alt="<?php echo e($appBranding['organization_name']); ?> logo" style="max-width:40px;max-height:40px;object-fit:contain"></span>
                <?php else: ?>
                <span class="brand-mark" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M4 8.1 12 3.5l8 4.6v7.8l-8 4.6-8-4.6V8.1Z"></path><path d="m8 10.3 4-2.3 4 2.3v3.4l-4 2.3-4-2.3v-3.4Z"></path><path d="M12 3.8v4.1m0 8.1v4.2"></path></svg>
                </span>
                <?php endif; ?>
                <div><p class="brand-name"><?php echo e($appBranding['product_name']); ?></p><p class="brand-caption"><?php echo e($appBranding['organization_name']); ?></p></div>
            </div>
            <div class="access-kicker">01 &nbsp; / &nbsp; Private workspace</div>
        </header>
        <section class="form-panel">
            <div class="form-heading">
                <h1>Sign in</h1>
                <p>Use your assigned account to access your organization’s workspace.</p>
            </div>
                <?php if ($setupRequired): ?>
                    <div class="error" role="status">
                        <span>This installation is not initialized. <a href="install/setup.php" class="font-bold underline">Create the first administrator</a> to get started.</span>
                    </div>
                <?php endif; ?>
                <?php if ($error !== ''): ?>
                    <div class="error" role="alert">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 9v4m0 4h.01M10.3 3.9 2.8 17a2 2 0 0 0 1.7 3h15a2 2 0 0 0 1.7-3l-7.5-13.1a2 2 0 0 0-3.4 0Z"></path></svg>
                        <span><?php echo e($error); ?></span>
                    </div>
                <?php endif; ?>
                <form action="index.php" method="post" autocomplete="on">
                    <input type="hidden" name="_csrf" value="<?php echo $csrf; ?>">
                    <div class="field">
                        <label for="username">Username</label>
                        <input id="username" name="username" type="text" maxlength="100" autocomplete="username" placeholder="Your username" required autofocus>
                    </div>
                    <div class="field">
                        <label for="password">Password</label>
                        <div class="input-wrap">
                            <input id="password" name="password" type="password" maxlength="1024" autocomplete="current-password" placeholder="Your password" required>
                            <button class="toggle-password" type="button" id="togglePassword" aria-controls="password" aria-pressed="false">Show</button>
                        </div>
                    </div>
                    <button class="submit" type="submit"><span>Continue securely</span></button>
                </form>
                <p class="form-foot"><span>Authorized users only<br>Access activity is monitored.</span><span><?php echo e($appBranding['product_name']); ?><br><?php echo e($appBranding['organization_name']); ?></span></p>
        </section>
    </main>
    <script>
        const passwordInput = document.getElementById('password');
        const passwordToggle = document.getElementById('togglePassword');
        passwordToggle.addEventListener('click', function () {
            const reveal = passwordInput.type === 'password';
            passwordInput.type = reveal ? 'text' : 'password';
            passwordToggle.textContent = reveal ? 'Hide' : 'Show';
            passwordToggle.setAttribute('aria-pressed', reveal ? 'true' : 'false');
        });
    </script>
</body>
</html>
