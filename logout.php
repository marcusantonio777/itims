<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    exit('Method not allowed.');
}
csrf_verify_post();

$uid = auth_current_id();
if ($uid > 0 && $pdo instanceof PDO) {
    try {
        log_activity($pdo, 'Logout', 'it_staff', $uid, null, 'User logout');
    } catch (Throwable $e) {
        user_error_log('Logout audit failure', $e);
    }
}

$params = session_get_cookie_params();
$_SESSION = [];
session_regenerate_id(true);
session_destroy();

if (ini_get("session.use_cookies")) {
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

header("Location: index.php");
exit;
