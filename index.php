<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/db.php';

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
    <title>Sign in | Quest ITIMS</title>
    <style>
        :root {
            color-scheme: light;
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            color: #172033;
            background: #f3f6fb;
            font-synthesis: none;
            text-rendering: optimizeLegibility;
        }
        * { box-sizing: border-box; }
        body {
            min-height: 100vh;
            margin: 0;
            display: grid;
            place-items: center;
            padding: 28px 18px;
            background:
                radial-gradient(ellipse at 16% 10%, rgba(59, 130, 246, .12), transparent 34rem),
                radial-gradient(ellipse at 90% 88%, rgba(14, 165, 233, .10), transparent 28rem),
                #f3f6fb;
        }
        .layout { width: min(100%, 1040px); display: grid; grid-template-columns: 1.05fr .95fr; overflow: hidden; border: 1px solid #e2e8f0; border-radius: 26px; background: #fff; box-shadow: 0 28px 80px rgba(15, 23, 42, .12); }
        .brand-panel { display: flex; flex-direction: column; justify-content: space-between; min-height: 620px; padding: 48px; color: #fff; background: linear-gradient(145deg, #102d55 0%, #164e83 56%, #087ca0 100%); }
        .brand { display: flex; align-items: center; gap: 13px; }
        .brand-mark { display: grid; width: 48px; height: 48px; place-items: center; border: 1px solid rgba(255,255,255,.25); border-radius: 15px; background: rgba(255,255,255,.13); }
        .brand-mark svg { width: 26px; height: 26px; }
        .brand-name { margin: 0; font-size: 15px; font-weight: 750; letter-spacing: .04em; }
        .brand-caption { margin: 4px 0 0; color: #c9e4f4; font-size: 11px; font-weight: 600; letter-spacing: .15em; text-transform: uppercase; }
        .welcome { max-width: 390px; margin: 64px 0; }
        .eyebrow { color: #a9e6f4; font-size: 11px; font-weight: 750; letter-spacing: .19em; text-transform: uppercase; }
        .welcome h1 { margin: 16px 0 14px; font-size: clamp(32px, 4vw, 48px); line-height: 1.08; letter-spacing: -.045em; }
        .welcome p { max-width: 360px; margin: 0; color: #d2e7f5; font-size: 15px; line-height: 1.75; }
        .secure-note { display: flex; align-items: center; gap: 10px; color: #d1e5f2; font-size: 12px; }
        .secure-note svg { width: 18px; height: 18px; color: #93e2d0; flex: none; }
        .form-panel { display: flex; align-items: center; justify-content: center; padding: 48px; }
        .form-inner { width: min(100%, 360px); }
        .mobile-brand { display: none; }
        .form-heading { margin-bottom: 30px; }
        .form-heading h2 { margin: 0; font-size: 27px; letter-spacing: -.035em; }
        .form-heading p { margin: 9px 0 0; color: #64748b; font-size: 14px; line-height: 1.6; }
        .field { margin-top: 20px; }
        label { display: block; margin-bottom: 8px; color: #334155; font-size: 12px; font-weight: 700; }
        .input-wrap { position: relative; }
        input { width: 100%; height: 48px; border: 1px solid #d7deea; border-radius: 11px; outline: 0; background: #fff; padding: 0 14px; color: #172033; font: inherit; font-size: 14px; transition: border-color .16s, box-shadow .16s; }
        input::placeholder { color: #9aa6b8; }
        input:focus { border-color: #2584bb; box-shadow: 0 0 0 4px rgba(14, 145, 190, .12); }
        input[type=password], input.password-visible { padding-right: 72px; }
        .toggle-password { position: absolute; top: 0; right: 0; height: 48px; padding: 0 13px; border: 0; background: transparent; color: #53718f; font: inherit; font-size: 12px; font-weight: 700; cursor: pointer; }
        .toggle-password:hover { color: #086b91; }
        .error { display: flex; gap: 10px; align-items: flex-start; margin: 0 0 20px; padding: 12px 13px; border: 1px solid #fecaca; border-radius: 11px; background: #fff5f5; color: #a52828; font-size: 13px; line-height: 1.5; }
        .error svg { width: 18px; height: 18px; flex: none; margin-top: 1px; }
        .submit { width: 100%; min-height: 49px; margin-top: 27px; border: 0; border-radius: 11px; background: linear-gradient(100deg, #126e9a, #145c90); color: #fff; font: inherit; font-size: 14px; font-weight: 750; box-shadow: 0 8px 18px rgba(20, 92, 144, .18); cursor: pointer; transition: filter .16s, transform .16s; }
        .submit:hover { filter: brightness(1.08); transform: translateY(-1px); }
        .submit:focus-visible, .toggle-password:focus-visible { outline: 3px solid #79c9e2; outline-offset: 3px; }
        .form-foot { margin-top: 25px; color: #8491a4; font-size: 11px; line-height: 1.6; text-align: center; }
        @media (max-width: 760px) {
            body { padding: 16px; }
            .layout { max-width: 480px; grid-template-columns: 1fr; border-radius: 20px; }
            .brand-panel { display: none; }
            .form-panel { padding: 32px 26px 26px; }
            .mobile-brand { display: flex; align-items: center; gap: 11px; margin-bottom: 34px; }
            .mobile-brand .brand-mark { color: #126e9a; border-color: #dceaf1; background: #eff8fb; }
            .mobile-brand .brand-name { color: #172033; }
            .mobile-brand .brand-caption { color: #64748b; }
            .form-heading { margin-bottom: 24px; }
        }
        @media (prefers-reduced-motion: reduce) { *, *::before, *::after { scroll-behavior: auto !important; transition-duration: .01ms !important; } }
    </style>
</head>
<body>
    <main class="layout">
        <section class="brand-panel" aria-label="Quest Hotel ITIMS">
            <div class="brand">
                <span class="brand-mark" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 7.5 12 3l8 4.5v9L12 21l-8-4.5v-9Z"></path><path d="m8 10 4-2.2 4 2.2v4l-4 2.2L8 14v-4Z"></path></svg>
                </span>
                <div><p class="brand-name">QUEST ITIMS</p><p class="brand-caption">Inventory workspace</p></div>
            </div>
            <div class="welcome">
                <span class="eyebrow">IT operations</span>
                <h1>Everything in its right place.</h1>
                <p>Manage equipment, supplies, deployments, and accountability from one secure workspace.</p>
            </div>
            <div class="secure-note">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 3 5 6v5c0 4.5 2.8 7.8 7 10 4.2-2.2 7-5.5 7-10V6l-7-3Z"></path><path d="m9 12 2 2 4-4"></path></svg>
                <span>Protected access for authorized team members</span>
            </div>
        </section>
        <section class="form-panel">
            <div class="form-inner">
                <div class="mobile-brand">
                    <span class="brand-mark" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 7.5 12 3l8 4.5v9L12 21l-8-4.5v-9Z"></path><path d="m8 10 4-2.2 4 2.2v4l-4 2.2L8 14v-4Z"></path></svg></span>
                    <div><p class="brand-name">QUEST ITIMS</p><p class="brand-caption">Inventory workspace</p></div>
                </div>
                <div class="form-heading">
                    <h2>Welcome back</h2>
                    <p>Sign in with your assigned account to continue.</p>
                </div>
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
                        <input id="username" name="username" type="text" maxlength="100" autocomplete="username" placeholder="Enter your username" required autofocus>
                    </div>
                    <div class="field">
                        <label for="password">Password</label>
                        <div class="input-wrap">
                            <input id="password" name="password" type="password" maxlength="1024" autocomplete="current-password" placeholder="Enter your password" required>
                            <button class="toggle-password" type="button" id="togglePassword" aria-controls="password" aria-pressed="false">Show</button>
                        </div>
                    </div>
                    <button class="submit" type="submit">Sign in securely</button>
                </form>
                <p class="form-foot">Access is monitored and limited to authorized users.<br>Quest Hotel IT Inventory Management System</p>
            </div>
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
