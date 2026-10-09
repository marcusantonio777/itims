<?php
if (PHP_SAPI !== 'cli' && session_status() === PHP_SESSION_NONE) {
    $secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    if (!headers_sent()) {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
        header("Content-Security-Policy: frame-ancestors 'none'");
        header('Cache-Control: no-store, private');
        if ($secure) {
            header('Strict-Transport-Security: max-age=31536000');
        }
    }
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_trans_sid', '0');
    $sameSite = 'Lax';
    $cookieParams = [
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => $secure,
        'httponly' => true,
        'samesite' => $sameSite,
    ];
    if (PHP_VERSION_ID >= 70300) {
        session_set_cookie_params($cookieParams);
    } else {
        session_set_cookie_params(
            $cookieParams['lifetime'],
            $cookieParams['path'] . '; samesite=' . $sameSite,
            $cookieParams['domain'],
            $cookieParams['secure'],
            $cookieParams['httponly']
        );
    }
    session_start();
}

define('ROLE_ADMIN',  'admin');
define('ROLE_STAFF',  'staff');
define('ROLE_VIEWER', 'viewer');
define('ROLES_ALL',   [ROLE_ADMIN, ROLE_STAFF, ROLE_VIEWER]);

function auth_current_id(): int {
    return (int)($_SESSION['staff_id'] ?? 0);
}

function auth_current_role(): string {
    $role = strtolower(trim((string)($_SESSION['staff_role'] ?? '')));
    if ($role === 'tech') {
        $role = ROLE_STAFF;
    }
    if ($role !== '') {
        $_SESSION['staff_role'] = $role;
    }
    return $role;
}

function auth_access_modules(): array {
    return [
        'dashboard' => ['label' => 'Dashboard', 'pages' => ['dashboard.php']],
        'inventory' => ['label' => 'Inventory', 'pages' => ['inventory.php', 'asset_details.php']],
        'supplies' => ['label' => 'Supplies', 'pages' => ['supplies.php']],
        'employees' => ['label' => 'Employees', 'pages' => ['employees.php', 'employee_details.php']],
        'issuance' => ['label' => 'Issuance', 'pages' => ['issuance.php', 'generate_pdf.php']],
        'keycards' => ['label' => 'Keycards', 'pages' => ['keycards.php']],
        'room_management' => ['label' => 'Item Deployment', 'pages' => ['room_management.php']],
        'maintenance' => ['label' => 'Maintenance', 'pages' => ['maintenance.php']],
        'categories' => ['label' => 'Categories', 'pages' => ['categories.php']],
        'vendors' => ['label' => 'Vendors', 'pages' => ['vendors.php']],
        'reports' => ['label' => 'Reports', 'pages' => ['reports.php']],
        'software_licenses' => ['label' => 'Software Licenses', 'pages' => ['software_licenses.php']],
        'activity_logs' => ['label' => 'Activity Logs', 'pages' => ['activity_logs.php']],
        'disposal' => ['label' => 'Asset Disposal', 'pages' => ['disposal.php']],
        'file_manager' => ['label' => 'File Explorer', 'pages' => ['file_manager.php']],
    ];
}

function auth_default_page_permissions(string $role): array {
    $modules = auth_access_modules();
    if ($role === ROLE_ADMIN || $role === ROLE_STAFF) {
        return array_fill_keys(array_keys($modules), true);
    }
    $permissions = array_fill_keys(array_keys($modules), false);
    $permissions['issuance'] = true;
    return $permissions;
}

function auth_user_page_permissions(int $userId): array {
    global $pdo;
    if (isset($GLOBALS['AUTH_PERMISSION_CACHE'][$userId])) {
        return $GLOBALS['AUTH_PERMISSION_CACHE'][$userId];
    }

    $stmt = $pdo->prepare("SELECT page_key, allowed FROM user_page_permissions WHERE staff_id = ?");
    $stmt->execute([$userId]);
    $rows = $stmt->fetchAll();
    $modules = auth_access_modules();

    if (!$rows) {
        $stmt = $pdo->prepare("SELECT role FROM it_staff WHERE id = ? LIMIT 1");
        $stmt->execute([$userId]);
        $GLOBALS['AUTH_PERMISSION_CACHE'][$userId] = auth_default_page_permissions(strtolower((string)$stmt->fetchColumn()));
        return $GLOBALS['AUTH_PERMISSION_CACHE'][$userId];
    }

    $permissions = array_fill_keys(array_keys($modules), false);
    foreach ($rows as $row) {
        if (array_key_exists($row['page_key'], $permissions)) {
            $permissions[$row['page_key']] = (bool)$row['allowed'];
        }
    }
    $GLOBALS['AUTH_PERMISSION_CACHE'][$userId] = $permissions;
    return $GLOBALS['AUTH_PERMISSION_CACHE'][$userId];
}

function auth_user_has_custom_page_permissions(int $userId): bool {
    global $pdo;
    $stmt = $pdo->prepare("SELECT 1 FROM user_page_permissions WHERE staff_id = ? LIMIT 1");
    $stmt->execute([$userId]);
    return (bool)$stmt->fetchColumn();
}

function auth_save_user_page_permissions(PDO $pdo, int $userId, $submitted): void {
    $modules = auth_access_modules();
    $selected = is_array($submitted) ? $submitted : [];
    $pdo->prepare("DELETE FROM user_page_permissions WHERE staff_id = ?")->execute([$userId]);
    $stmt = $pdo->prepare("INSERT INTO user_page_permissions (staff_id, page_key, allowed) VALUES (?, ?, ?)");
    foreach ($modules as $key => $module) {
        $stmt->execute([$userId, $key, in_array($key, $selected, true) ? 1 : 0]);
    }
    unset($GLOBALS['AUTH_PERMISSION_CACHE'][$userId]);
}

function auth_can_access_page(string $page): bool {
    $role = auth_current_role();
    if ($role === ROLE_ADMIN || $page === 'profile.php') {
        return true;
    }
    if ($page === 'keycards.php' && $role === ROLE_VIEWER) {
        return false;
    }

    foreach (auth_access_modules() as $moduleKey => $module) {
        if (in_array($page, $module['pages'], true)) {
            $permissions = auth_user_page_permissions(auth_current_id());
            return !empty($permissions[$moduleKey]);
        }
    }
    return false;
}

function auth_landing_page(): string {
    if (auth_current_role() === ROLE_ADMIN) {
        return 'dashboard.php';
    }

    $permissions = auth_user_page_permissions(auth_current_id());
    foreach (auth_access_modules() as $moduleKey => $module) {
        if (!empty($permissions[$moduleKey])) {
            return $module['pages'][0];
        }
    }
    return 'profile.php';
}

function enforce_page_access(string $page): void {
    enforce_authenticated();
    if (!auth_can_access_page($page)) {
        http_response_code(403);
        die('Access denied. Your account is not permitted to access this section.');
    }
}

function auth_current_name(): string {
    return (string)($_SESSION['staff_name'] ?? '');
}

function auth_user_record(): ?array {
    global $pdo;
    $id = auth_current_id();
    if ($id <= 0) return null;
    static $cache = null;
    static $cacheId = 0;
    if ($cacheId === $id && $cache !== null) return $cache;
    try {
        $stmt = $pdo->prepare("SELECT id, full_name, username, role, is_active, last_password_change, created_at
                               FROM it_staff WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $cache = $stmt->fetch() ?: null;
        $cacheId = $id;
    } catch (Throwable $e) {
        $cache = null;
    }
    return $cache;
}

function _auth_flash_redirect(string $location, string $errorMsg): void {
    $_SESSION['_flash_error'] = $errorMsg;
    header("Location: $location");
    exit;
}

function enforce_authenticated(): void {
    if (auth_current_id() <= 0) {
        header("Location: index.php");
        exit;
    }
    global $pdo;
    if (!($pdo instanceof PDO)) {
        require_once dirname(__DIR__) . '/config/db.php';
        global $pdo;
    }
    try {
        $stmt = $pdo->prepare("SELECT is_active FROM it_staff WHERE id = ? LIMIT 1");
        $stmt->execute([auth_current_id()]);
        $row = $stmt->fetch();
        if (!$row || !(int)$row['is_active']) {
            $_SESSION = [];
            if (ini_get("session.use_cookies")) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000,
                    $params["path"], $params["domain"],
                    $params["secure"], $params["httponly"]
                );
            }
            session_destroy();
            header("Location: index.php");
            exit;
        }
    } catch (Throwable $e) {
        user_error_log('Unable to verify the authenticated account status.', $e);
        http_response_code(503);
        die('Authentication service is temporarily unavailable. Please try again later.');
    }
}

function enforce_role($allowed): void {
    enforce_authenticated();
    if (is_string($allowed)) $allowed = [$allowed];
    $allowed = array_values(array_map('strval', $allowed));
    $role = auth_current_role();
    if (!in_array($role, $allowed, true)) {
        http_response_code(403);
        die('Access denied: your role is not permitted to perform this action.');
    }
}

function csrf_token(): string {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_form_token(): string {
    if (!isset($_SESSION['_csrf_form_tokens']) || !is_array($_SESSION['_csrf_form_tokens'])) {
        $_SESSION['_csrf_form_tokens'] = [];
    }

    $now = time();
    if ((int)($_SESSION['_csrf_form_tokens_cleanup_at'] ?? 0) < $now - 900) {
        foreach ($_SESSION['_csrf_form_tokens'] as $token => $createdAt) {
            if (!is_int($createdAt) || $createdAt < $now - 14400) {
                unset($_SESSION['_csrf_form_tokens'][$token]);
            }
        }
        $_SESSION['_csrf_form_tokens_cleanup_at'] = $now;
    }

    $token = bin2hex(random_bytes(32));
    $_SESSION['_csrf_form_tokens'][$token] = $now;
    if (count($_SESSION['_csrf_form_tokens']) > 10000) {
        $_SESSION['_csrf_form_tokens'] = array_slice($_SESSION['_csrf_form_tokens'], -10000, null, true);
    }
    return $token;
}

function csrf_field(): string {
    $t = htmlspecialchars(csrf_form_token(), ENT_QUOTES, 'UTF-8');
    return '<input type="hidden" name="_csrf" value="' . $t . '">';
}

function csrf_verify_post(): void {
    $post = $_POST['_csrf'] ?? '';
    $formTokens = $_SESSION['_csrf_form_tokens'] ?? [];
    if (is_string($post) && is_array($formTokens) && isset($formTokens[$post])
        && is_int($formTokens[$post]) && $formTokens[$post] >= time() - 14400) {
        unset($_SESSION['_csrf_form_tokens'][$post]);
        return;
    }

    $isLoginRequest = basename((string)($_SERVER['SCRIPT_NAME'] ?? '')) === 'index.php';
    $session = $_SESSION['csrf'] ?? '';
    if (!$isLoginRequest || !is_string($post) || !is_string($session) || $session === '' || !hash_equals($session, $post)) {
        http_response_code(400);
        die("This form was already submitted or has expired. Reload the page and try again.");
    }
}

function e($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function user_error_log(string $msg, ?Throwable $ex = null): void {
    $prefix = '[ITIMS] ';
    if ($ex instanceof Throwable) {
        $msg .= ' | Exception: ' . get_class($ex) . ' :: ' . $ex->getMessage()
              . ' in ' . $ex->getFile() . ':' . $ex->getLine();
    }
    error_log($prefix . $msg);
}

function generic_error(): string {
    return 'Operation failed. Please try again or contact support.';
}
