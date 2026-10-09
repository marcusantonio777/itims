<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';

$branding = app_branding($pdo);
$message = '';
$error = '';
$hasUsers = (int)$pdo->query("SELECT COUNT(*) FROM it_staff")->fetchColumn() > 0;

if (!$hasUsers && $_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify_post();
    $fullName = trim((string)($_POST['full_name'] ?? ''));
    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $passwordConfirm = (string)($_POST['password_confirm'] ?? '');

    if ($fullName === '' || strlen($fullName) > 255) {
        $error = 'Enter your name (up to 255 characters).';
    } elseif (!preg_match('/^[A-Za-z0-9._-]{3,50}$/', $username)) {
        $error = 'Choose a username using 3-50 letters, numbers, dots, dashes, or underscores.';
    } elseif (strlen($password) < 12 || strlen($password) > 1024) {
        $error = 'Choose a password between 12 and 1024 characters.';
    } elseif (!hash_equals($password, $passwordConfirm)) {
        $error = 'The password confirmation does not match.';
    } else {
        $lockAcquired = false;
        try {
            $lockAcquired = (int)$pdo->query("SELECT GET_LOCK('itims_initial_admin_setup', 10)")->fetchColumn() === 1;
            if (!$lockAcquired) {
                throw new RuntimeException('Initial administrator setup is already in progress. Try again shortly.');
            }

            $pdo->beginTransaction();
            if ((int)$pdo->query("SELECT COUNT(*) FROM it_staff")->fetchColumn() > 0) {
                $pdo->rollBack();
                $hasUsers = true;
                $message = 'An administrator account already exists. Sign in to continue.';
            } else {
                $create = $pdo->prepare(
                    "INSERT INTO it_staff (full_name, username, password_hash, role, is_active, last_password_change)
                     VALUES (?, ?, ?, 'admin', 1, CURRENT_TIMESTAMP)"
                );
                $create->execute([$fullName, $username, password_hash($password, PASSWORD_DEFAULT)]);
                $adminId = (int)$pdo->lastInsertId();
                auth_save_user_page_permissions($pdo, $adminId, array_keys(auth_access_modules()));
                $pdo->commit();
                header('Location: ../index.php?setup=complete');
                exit;
            }
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            user_error_log('Initial administrator setup failed.', $exception);
            $error = $exception instanceof RuntimeException
                ? $exception->getMessage()
                : 'The administrator account could not be created. Review the server log and try again.';
        } finally {
            if ($lockAcquired) {
                $pdo->query("SELECT RELEASE_LOCK('itims_initial_admin_setup')");
            }
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title>Initial setup | <?php echo e($branding['product_name']); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-50 px-4 py-12 text-slate-800">
    <main class="mx-auto max-w-xl overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl">
        <header class="bg-gradient-to-r from-slate-900 to-blue-800 px-7 py-6 text-white">
            <p class="text-xs font-bold uppercase tracking-[.18em] text-blue-200"><?php echo e($branding['organization_name']); ?></p>
            <h1 class="mt-2 text-2xl font-black"><?php echo e($branding['product_name']); ?></h1>
            <p class="mt-2 text-sm text-blue-100">Secure first-run setup</p>
        </header>
        <section class="space-y-5 p-7">
            <?php if ($message !== ''): ?>
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-semibold text-emerald-800"><?php echo e($message); ?></div>
                <a href="../index.php" class="inline-flex rounded-xl bg-blue-700 px-5 py-3 text-sm font-bold text-white hover:bg-blue-800">Go to sign in</a>
            <?php elseif ($hasUsers): ?>
                <div class="rounded-xl border border-blue-200 bg-blue-50 p-4 text-sm leading-6 text-blue-900">
                    This installation is already initialized. For security, first-run setup is disabled once an account exists.
                </div>
                <a href="../index.php" class="inline-flex rounded-xl bg-blue-700 px-5 py-3 text-sm font-bold text-white hover:bg-blue-800">Go to sign in</a>
            <?php else: ?>
                <p class="text-sm leading-6 text-slate-600">Create the first administrator account. Use a unique password with at least 12 characters. No shared/default administrator password is created.</p>
                <?php if ($error !== ''): ?>
                    <div role="alert" class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm font-semibold text-red-800"><?php echo e($error); ?></div>
                <?php endif; ?>
                <form method="POST" class="space-y-4" autocomplete="off">
                    <?php echo csrf_field(); ?>
                    <div>
                        <label for="full_name" class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-slate-600">Administrator name</label>
                        <input id="full_name" name="full_name" maxlength="255" required value="<?php echo e($_POST['full_name'] ?? ''); ?>" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                    </div>
                    <div>
                        <label for="username" class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-slate-600">Username</label>
                        <input id="username" name="username" maxlength="50" pattern="[A-Za-z0-9._-]{3,50}" required value="<?php echo e($_POST['username'] ?? ''); ?>" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                    </div>
                    <div>
                        <label for="password" class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-slate-600">Password</label>
                        <input id="password" name="password" type="password" minlength="12" maxlength="1024" autocomplete="new-password" required class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                    </div>
                    <div>
                        <label for="password_confirm" class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-slate-600">Confirm password</label>
                        <input id="password_confirm" name="password_confirm" type="password" minlength="12" maxlength="1024" autocomplete="new-password" required class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                    </div>
                    <button type="submit" class="w-full rounded-xl bg-blue-700 px-5 py-3 text-sm font-bold text-white shadow-sm hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">Create administrator</button>
                </form>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>
