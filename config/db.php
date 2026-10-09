<?php
// config/db.php

$localConfig = __DIR__ . '/db.local.php';
if (is_file($localConfig)) {
    require $localConfig;
}

$host    = $host    ?? getenv('ITIMS_DB_HOST')    ?: '';
$db      = $db      ?? getenv('ITIMS_DB_NAME')    ?: '';
$user    = $user    ?? getenv('ITIMS_DB_USER')    ?: '';
$pass    = $pass    ?? getenv('ITIMS_DB_PASS')    ?: '';
$charset = $charset ?? 'utf8mb4';

if ($host === '' || $db === '' || $user === '') {
    http_response_code(500);
    die('Database configuration is missing. Copy config/db.local.example.php to config/db.local.php and configure credentials, or set the ITIMS_DB_* environment variables.');
}

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    http_response_code(500);
    error_log('[ITIMS] DB connection failed: ' . $e->getMessage());
    die('Unable to connect to the database. Please check configuration and try again.');
}

$GLOBALS['AUDIT_ACTION_TYPES'] = [
    'Create', 'Update', 'Delete', 'Login', 'Logout', 'Issue', 'Return',
    'UserCreate', 'UserUpdate', 'UserRoleChange', 'UserDeactivate',
    'UserReactivate', 'UserPasswordReset', 'TermsCreate', 'TermsUpdate', 'TermsDelete',
];

/**
 * Log activity to the audit_logs table.
 * Action type is validated against the allowed ENUM set.
 */
function log_activity($pdo, $action_type, $table_affected, $record_id = null, $old_value = null, $new_value = null) {
    if (!isset($_SESSION['staff_id'])) return;
    if (!($pdo instanceof PDO)) return;

    $allowed = $GLOBALS['AUDIT_ACTION_TYPES'] ?? [];
    if (!is_string($action_type) || !in_array($action_type, $allowed, true)) {
        error_log('[ITIMS] log_activity invalid action_type: ' . var_export($action_type, true));
        $action_type = 'Update';
    }

    $user_id = (int)$_SESSION['staff_id'];
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';

    try {
        $stmt = $pdo->prepare("INSERT INTO audit_logs (user_id, action_type, table_affected, record_id, old_value, new_value, ip_address) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $action_type, $table_affected, $record_id, $old_value, $new_value, $ip_address]);
    } catch (Throwable $e) {
        error_log('[ITIMS] log_activity insert failed: ' . $e->getMessage());
    }
}
