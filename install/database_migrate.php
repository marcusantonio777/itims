`<?php
// install/database_migrate.php
// Run this script ONCE (via web as admin, or via CLI) to bootstrap
// the ITIMS schema, expand ENUMs, and create missing tables.
// Safe to re-run; all statements are idempotent.

$cli = (PHP_SAPI === 'cli');

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';

if (!$cli) {
    if (auth_current_role() !== ROLE_ADMIN) {
        http_response_code(403);
        die('Access denied. Admin only.');
    }
}

$isCli = $cli;
function out(string $msg, string $status = ''): void {
    global $isCli;
    $wrap = static function (string $label, string $color, string $text) use ($isCli) {
        if ($isCli) return "[$label] $text\n";
        return '<div style="padding:6px 10px;border-left:3px solid ' . $color . ';background:#f8f9fa;margin:2px 0;font-family:monospace;font-size:13px;">
            <strong style="color:' . $color . ';">[' . $label . ']</strong> ' . htmlspecialchars($text) . '</div>';
    };
    switch ($status) {
        case 'ok':    echo $wrap('OK',    '#22c55e', $msg); break;
        case 'skip':  echo $wrap('SKIP',  '#a3a3a3', $msg); break;
        case 'warn':  echo $wrap('WARN',  '#f59e0b', $msg); break;
        case 'error': echo $wrap('ERROR', '#ef4444', $msg); break;
        default:      echo $wrap('INFO',  '#3b82f6', $msg); break;
    }
    if ($isCli) flush(); else @ob_flush(); flush();
}

if (!$isCli) {
    echo '<!doctype html><html><head><meta charset="utf-8"><title>Application Database Migration</title>
    <style>body{font-family:system-ui,sans-serif;background:#0f172a;color:#e2e8f0;padding:24px;}
    h1{color:#fbbf24;} .wrap{max-width:900px;margin:0 auto;background:#1e293b;border:1px solid #334155;border-radius:12px;padding:20px;}</style>
    </head><body><div class="wrap"><h1>Application Database Migration</h1><p style="color:#94a3b8;">Running all idempotent migrations...</p><hr>';
}

out('Starting migrations...', 'info');

$migrations = [];

$migrations['supplies_table'] = static function (PDO $pdo): string {
    $pdo->exec("CREATE TABLE IF NOT EXISTS supplies (
        id INT AUTO_INCREMENT PRIMARY KEY,
        item_name VARCHAR(255) NOT NULL,
        category_id INT,
        brand VARCHAR(100),
        model VARCHAR(100),
        quantity INT DEFAULT 0,
        unit VARCHAR(20) DEFAULT 'pcs',
        min_stock_level INT DEFAULT 5,
        vendor_id INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (category_id) REFERENCES categories(id),
        FOREIGN KEY (vendor_id) REFERENCES vendors(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    return 'created or exists';
};

$migrations['supply_logs_table'] = static function (PDO $pdo): string {
    $pdo->exec("CREATE TABLE IF NOT EXISTS supply_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        supply_id INT NOT NULL,
        staff_id INT NOT NULL,
        employee_id INT NULL,
        action_type ENUM('Stock In', 'Stock Out', 'Adjustment') NOT NULL,
        quantity_changed INT NOT NULL,
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (supply_id) REFERENCES supplies(id),
        FOREIGN KEY (staff_id) REFERENCES it_staff(id),
        FOREIGN KEY (employee_id) REFERENCES employees(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    return 'created or exists';
};

$migrations['maintenance_logs_table'] = static function (PDO $pdo): string {
    $pdo->exec("CREATE TABLE IF NOT EXISTS maintenance_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        asset_id INT NOT NULL,
        it_staff_id INT,
        maintenance_type ENUM('Repair', 'Service', 'Inspection', 'Upgrade') NOT NULL,
        description TEXT,
        maintenance_date DATE NOT NULL,
        cost DECIMAL(10,2),
        vendor_id INT,
        next_maintenance_date DATE,
        status ENUM('Pending', 'In Progress', 'Completed', 'Cancelled') DEFAULT 'Pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (asset_id) REFERENCES assets(id),
        FOREIGN KEY (it_staff_id) REFERENCES it_staff(id),
        FOREIGN KEY (vendor_id) REFERENCES vendors(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    return 'created or exists';
};

$migrations['audit_logs_table'] = static function (PDO $pdo): string {
    $pdo->exec("CREATE TABLE IF NOT EXISTS audit_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        action_type ENUM('Create','Update','Delete','Login','Logout','Issue','Return','LicenseKeyReveal',
                         'UserCreate','UserUpdate','UserRoleChange','UserDeactivate',
                         'UserReactivate','UserPasswordReset','TermsCreate','TermsUpdate','TermsDelete')
                    NOT NULL DEFAULT 'Update',
        table_affected VARCHAR(50),
        record_id INT,
        old_value TEXT,
        new_value TEXT,
        ip_address VARCHAR(45),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES it_staff(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    return 'created or exists';
};

$migrations['system_settings_table'] = static function (PDO $pdo): string {
    $pdo->exec("CREATE TABLE IF NOT EXISTS system_settings (
        setting_key VARCHAR(100) PRIMARY KEY,
        setting_value VARCHAR(255) NOT NULL,
        updated_by INT NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (updated_by) REFERENCES it_staff(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    $defaults = [
        'allow_delete_assets_with_history' => '0',
        'product_name' => 'IT Asset Manager',
        'organization_name' => 'Your Organization',
        'department_label' => 'IT Department',
        'company_logo_path' => '',
    ];
    $stmt = $pdo->prepare("INSERT IGNORE INTO system_settings (setting_key, setting_value) VALUES (?, ?)");
    foreach ($defaults as $key => $value) {
        $stmt->execute([$key, $value]);
    }
    return 'created or exists; product branding defaults ensured';
};

$migrations['expand_audit_action_enum'] = static function (PDO $pdo): string {
    $newEnum = "'Create','Update','Delete','Login','Logout','Issue','Return','LicenseKeyReveal',
                 'UserCreate','UserUpdate','UserRoleChange','UserDeactivate',
                 'UserReactivate','UserPasswordReset','TermsCreate','TermsUpdate','TermsDelete'";
    $pdo->exec("ALTER TABLE audit_logs
                MODIFY COLUMN action_type ENUM($newEnum) NOT NULL DEFAULT 'Update'");
    return 'expanded ENUM';
};

$migrations['accountability_supplies_table'] = static function (PDO $pdo): string {
    $pdo->exec("CREATE TABLE IF NOT EXISTS accountability_supplies (
        id INT AUTO_INCREMENT PRIMARY KEY,
        log_id INT NOT NULL,
        supply_id INT NOT NULL,
        quantity INT DEFAULT 1,
        FOREIGN KEY (log_id) REFERENCES accountability_logs(id) ON DELETE CASCADE,
        FOREIGN KEY (supply_id) REFERENCES supplies(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    return 'created or exists';
};

$migrations['accountability_log_employees_table'] = static function (PDO $pdo): string {
    $pdo->exec("CREATE TABLE IF NOT EXISTS accountability_log_employees (
        id INT AUTO_INCREMENT PRIMARY KEY,
        log_id INT NOT NULL,
        employee_id INT NOT NULL,
        UNIQUE KEY uniq_log_employee (log_id, employee_id),
        KEY fk_ale_employee (employee_id),
        FOREIGN KEY (log_id) REFERENCES accountability_logs(id) ON DELETE CASCADE,
        FOREIGN KEY (employee_id) REFERENCES employees(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    return 'created or exists';
};

$migrations['accountability_terms_table'] = static function (PDO $pdo): string {
    $pdo->exec("CREATE TABLE IF NOT EXISTS accountability_terms (
        id INT AUTO_INCREMENT PRIMARY KEY,
        category_id INT NULL,
        terms_text TEXT NOT NULL,
        updated_by INT NULL,
        updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
        FOREIGN KEY (updated_by) REFERENCES it_staff(id) ON DELETE SET NULL,
        UNIQUE KEY uk_category (category_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    return 'created or exists';
};

$migrations['add_columns_accountability_logs'] = static function (PDO $pdo): string {
    $done = 0;
    try { $pdo->exec("ALTER TABLE accountability_logs ADD COLUMN return_condition ENUM('Good', 'Needs Repair', 'Damaged') NULL;"); $done++; } catch (Throwable $e) { if (strpos($e->getMessage(), 'Duplicate column name') === false) throw $e; }
    try { $pdo->exec("ALTER TABLE accountability_logs ADD COLUMN return_notes TEXT NULL;"); $done++; } catch (Throwable $e) { if (strpos($e->getMessage(), 'Duplicate column name') === false) throw $e; }
    try { $pdo->exec("ALTER TABLE accountability_logs ADD COLUMN keycard_level VARCHAR(80) NULL;"); $done++; } catch (Throwable $e) { if (strpos($e->getMessage(), 'Duplicate column name') === false) throw $e; }
    try { $pdo->exec("ALTER TABLE accountability_logs ADD COLUMN keycard_access VARCHAR(120) NULL;"); $done++; } catch (Throwable $e) { if (strpos($e->getMessage(), 'Duplicate column name') === false) throw $e; }
    try { $pdo->exec("ALTER TABLE accountability_logs ADD COLUMN keycard_issued_on DATE NULL;"); $done++; } catch (Throwable $e) { if (strpos($e->getMessage(), 'Duplicate column name') === false) throw $e; }
    try { $pdo->exec("ALTER TABLE accountability_logs ADD COLUMN keycard_expires_on DATE NULL;"); $done++; } catch (Throwable $e) { if (strpos($e->getMessage(), 'Duplicate column name') === false) throw $e; }
    return $done ? "added $done column(s)" : 'skipped (exist)';
};

$migrations['add_columns_employees'] = static function (PDO $pdo): string {
    try { $pdo->exec("ALTER TABLE employees ADD COLUMN position VARCHAR(100) AFTER department;"); return 'added'; } catch (Throwable $e) { if (strpos($e->getMessage(), 'Duplicate column name') === false) throw $e; return 'skipped (exists)'; }
};

$migrations['add_columns_assets'] = static function (PDO $pdo): string {
    try { $pdo->exec("ALTER TABLE assets ADD COLUMN photo_path VARCHAR(255) NULL;"); return 'added'; } catch (Throwable $e) { if (strpos($e->getMessage(), 'Duplicate column name') === false) throw $e; return 'skipped (exists)'; }
};

$migrations['add_columns_it_staff'] = static function (PDO $pdo): string {
    try { $pdo->exec("ALTER TABLE it_staff ADD COLUMN last_password_change TIMESTAMP NULL;"); return 'added'; } catch (Throwable $e) { if (strpos($e->getMessage(), 'Duplicate column name') === false) throw $e; return 'skipped (exists)'; }
};

$migrations['add_columns_categories'] = static function (PDO $pdo): string {
    $done = 0;
    try { $pdo->exec("ALTER TABLE categories ADD COLUMN requires_serial_number TINYINT(1) NOT NULL DEFAULT 1;"); $done++; } catch (Throwable $e) { if (strpos($e->getMessage(), 'Duplicate column name') === false) throw $e; }
    try { $pdo->exec("ALTER TABLE categories ADD COLUMN show_technical_specs TINYINT(1) NOT NULL DEFAULT 1;"); $done++; } catch (Throwable $e) { if (strpos($e->getMessage(), 'Duplicate column name') === false) throw $e; }
    try { $pdo->exec("ALTER TABLE categories ADD COLUMN show_other_specs TINYINT(1) NOT NULL DEFAULT 1;"); $done++; } catch (Throwable $e) { if (strpos($e->getMessage(), 'Duplicate column name') === false) throw $e; }
    return $done ? "added $done column(s)" : 'skipped (exist)';
};

$migrations['asset_disposal_table'] = static function (PDO $pdo): string {
    $pdo->exec("CREATE TABLE IF NOT EXISTS asset_disposal (
        id INT AUTO_INCREMENT PRIMARY KEY,
        asset_id INT NOT NULL,
        disposal_date DATE NOT NULL,
        reason VARCHAR(255) NOT NULL,
        method ENUM('Scrap', 'Sold', 'Donated', 'Destroyed') NOT NULL,
        notes TEXT,
        staff_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (asset_id) REFERENCES assets(id) ON DELETE CASCADE,
        FOREIGN KEY (staff_id) REFERENCES it_staff(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    return 'created or exists';
};

$migrations['room_deployments_table'] = static function (PDO $pdo): string {
    $pdo->exec("CREATE TABLE IF NOT EXISTS room_deployments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        target_type ENUM('Room', 'Employee', 'Other') NOT NULL DEFAULT 'Room',
        room_number VARCHAR(50) NULL,
        employee_id INT NULL,
        target_label VARCHAR(255) NULL,
        notes TEXT,
        deployed_by INT NOT NULL,
        deployed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (employee_id) REFERENCES employees(id),
        FOREIGN KEY (deployed_by) REFERENCES it_staff(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    return 'created or exists';
};

$migrations['room_deployment_items_table'] = static function (PDO $pdo): string {
    $pdo->exec("CREATE TABLE IF NOT EXISTS room_deployment_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        deployment_id INT NOT NULL,
        supply_id INT NOT NULL,
        quantity INT NOT NULL DEFAULT 1,
        FOREIGN KEY (deployment_id) REFERENCES room_deployments(id) ON DELETE CASCADE,
        FOREIGN KEY (supply_id) REFERENCES supplies(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    return 'created or exists';
};

$migrations['software_licenses_table'] = static function (PDO $pdo): string {
    $pdo->exec("CREATE TABLE IF NOT EXISTS software_licenses (
        id INT AUTO_INCREMENT PRIMARY KEY,
        software_name VARCHAR(255) NOT NULL,
        vendor_name VARCHAR(255) DEFAULT NULL,
        license_key TEXT DEFAULT NULL,
        license_type VARCHAR(100) DEFAULT NULL,
        seats INT NOT NULL DEFAULT 1,
        cost DECIMAL(10,2) DEFAULT 0.00,
        billing_cycle ENUM('Monthly', 'Quarterly', 'Semi-Annual', 'Annual', 'One-Time', 'Custom') NOT NULL DEFAULT 'Annual',
        start_date DATE DEFAULT NULL,
        renewal_date DATE DEFAULT NULL,
        update_due_date DATE DEFAULT NULL,
        status ENUM('Active', 'Expiring Soon', 'Expired', 'Cancelled') NOT NULL DEFAULT 'Active',
        owner_name VARCHAR(255) DEFAULT NULL,
        notes TEXT,
        attachment_name VARCHAR(255) DEFAULT NULL,
        attachment_path VARCHAR(255) DEFAULT NULL,
        created_by INT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (created_by) REFERENCES it_staff(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    return 'created or exists';
};

$migrations['ensure_it_staff_is_active'] = static function (PDO $pdo): string {
    try { $pdo->exec("ALTER TABLE it_staff ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1 AFTER role;"); return 'added'; } catch (Throwable $e) { if (strpos($e->getMessage(), 'Duplicate column name') === false) throw $e; return 'skipped (exists)'; }
};

$migrations['normalize_staff_roles'] = static function (PDO $pdo): string {
    $pdo->exec("ALTER TABLE it_staff MODIFY COLUMN role VARCHAR(20) NULL DEFAULT 'staff'");
    $pdo->exec("UPDATE it_staff
                SET role = CASE
                    WHEN LOWER(TRIM(COALESCE(role, ''))) IN ('admin', 'administrator') THEN 'admin'
                    WHEN LOWER(TRIM(COALESCE(role, ''))) IN ('viewer', 'read-only', 'readonly') THEN 'viewer'
                    ELSE 'staff'
                END");
    $pdo->exec("ALTER TABLE it_staff MODIFY COLUMN role ENUM('admin','staff','viewer') NOT NULL DEFAULT 'staff'");
    return 'legacy Admin/Tech roles mapped to admin/staff/viewer';
};

$migrations['user_page_permissions_table'] = static function (PDO $pdo): string {
    $pdo->exec("CREATE TABLE IF NOT EXISTS user_page_permissions (
        staff_id INT NOT NULL,
        page_key VARCHAR(50) NOT NULL,
        allowed TINYINT(1) NOT NULL DEFAULT 0,
        PRIMARY KEY (staff_id, page_key),
        FOREIGN KEY (staff_id) REFERENCES it_staff(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    return 'created or exists';
};

$migrations['keycards_page_permissions'] = static function (PDO $pdo): string {
    $pdo->exec("INSERT INTO user_page_permissions (staff_id, page_key, allowed)
        SELECT s.id, 'keycards',
               CASE WHEN s.role = 'viewer' THEN 0 ELSE COALESCE(issuance.allowed, 1) END
        FROM it_staff s
        LEFT JOIN user_page_permissions issuance
            ON issuance.staff_id = s.id AND issuance.page_key = 'issuance'
        ON DUPLICATE KEY UPDATE page_key = VALUES(page_key)");
    return 'created keycard permissions; viewers remain denied';
};

$migrations['login_attempts_table'] = static function (PDO $pdo): string {
    $pdo->exec("CREATE TABLE IF NOT EXISTS login_attempts (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        ip_hash CHAR(64) NOT NULL,
        failed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_login_attempts_ip_time (ip_hash, failed_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    return 'created or exists';
};

$migrations['file_manager_items_table'] = static function (PDO $pdo): string {
    $pdo->exec("CREATE TABLE IF NOT EXISTS file_manager_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        parent_id INT NULL,
        item_type ENUM('folder','file') NOT NULL,
        name VARCHAR(255) NOT NULL,
        storage_name VARCHAR(80) NULL,
        mime_type VARCHAR(127) NULL,
        file_size BIGINT UNSIGNED NULL,
        created_by INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_file_manager_parent_type (parent_id, item_type),
        INDEX idx_file_manager_name (name),
        FOREIGN KEY (parent_id) REFERENCES file_manager_items(id) ON DELETE CASCADE,
        FOREIGN KEY (created_by) REFERENCES it_staff(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    return 'created or exists';
};

$migrations['pdf_templates_table'] = static function (PDO $pdo): string {
    $pdo->exec("CREATE TABLE IF NOT EXISTS pdf_templates (
        id TINYINT UNSIGNED NOT NULL PRIMARY KEY,
        template_html MEDIUMTEXT NOT NULL,
        page_size VARCHAR(10) NOT NULL DEFAULT 'A4',
        orientation ENUM('portrait','landscape') NOT NULL DEFAULT 'portrait',
        updated_by INT NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (updated_by) REFERENCES it_staff(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    return 'created or exists';
};

$migrations['pdf_category_templates_table'] = static function (PDO $pdo): string {
    $pdo->exec("CREATE TABLE IF NOT EXISTS pdf_category_templates (
        category_id INT NOT NULL PRIMARY KEY,
        template_html MEDIUMTEXT NOT NULL,
        page_size VARCHAR(10) NOT NULL DEFAULT 'A4',
        orientation ENUM('portrait','landscape') NOT NULL DEFAULT 'portrait',
        updated_by INT NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
        FOREIGN KEY (updated_by) REFERENCES it_staff(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    return 'created or exists';
};

$migrations['uploads_dirs'] = static function (): string {
    $dirs = [
        __DIR__ . '/../uploads',
        __DIR__ . '/../uploads/assets',
        __DIR__ . '/../uploads/licenses',
        __DIR__ . '/../uploads/file_manager',
        __DIR__ . '/../uploads/branding',
    ];
    $created = 0;
    foreach ($dirs as $d) {
        if (!is_dir($d)) {
            mkdir($d, 0755, true);
            $created++;
        }
    }
    return $created ? "created $created directories" : 'exist (ok)';
};

$migrations['default_admin_account'] = static function (PDO $pdo): string {
    return 'no default credentials created; use install/setup.php to create the first administrator';
};

$migrations['uploads_htaccess'] = static function (): string {
    $rules = "Options -ExecCGI -Indexes\nSetHandler default-handler\nphp_flag engine off\nRequire all denied\n";
    $targets = [
        __DIR__ . '/../uploads/.htaccess',
        __DIR__ . '/../uploads/assets/.htaccess',
        __DIR__ . '/../uploads/licenses/.htaccess',
        __DIR__ . '/../uploads/file_manager/.htaccess',
        __DIR__ . '/../uploads/branding/.htaccess',
    ];
    $updated = 0;
    foreach ($targets as $target) {
        $dir = dirname($target);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        if (!is_file($target)) {
            file_put_contents($target, $rules, LOCK_EX);
            $updated++;
        }
    }
    $licenseRules = "Require all denied\n";
    $licenseAccessFile = __DIR__ . '/../uploads/licenses/.htaccess';
    if (file_get_contents($licenseAccessFile) !== $licenseRules) {
        if (file_put_contents($licenseAccessFile, $licenseRules, LOCK_EX) === false) {
            throw new RuntimeException('Could not restrict direct access to software license attachments.');
        }
        $updated++;
    }
    return $updated ? "created $updated .htaccess file(s)" : 'htaccess already present';
};

$pass = 0;
$fail = 0;
foreach ($migrations as $name => $fn) {
    try {
        $result = $fn($pdo);
        out($name . ' -> ' . $result, 'ok');
        $pass++;
    } catch (Throwable $e) {
        out($name . ' -> ' . $e->getMessage(), 'error');
        $fail++;
    }
}

$versionedPass = 0;
$versionedFail = 0;
if ($fail === 0) {
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS schema_migrations (
            version VARCHAR(120) NOT NULL PRIMARY KEY,
            applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $migrationFiles = glob(__DIR__ . '/migrations/[0-9][0-9][0-9]_*.php') ?: [];
        sort($migrationFiles, SORT_STRING);
        $isApplied = $pdo->prepare('SELECT 1 FROM schema_migrations WHERE version = ? LIMIT 1');
        $recordApplied = $pdo->prepare('INSERT INTO schema_migrations (version) VALUES (?)');

        foreach ($migrationFiles as $migrationFile) {
            $version = basename($migrationFile, '.php');
            $isApplied->execute([$version]);
            if ($isApplied->fetchColumn()) {
                out($version . ' -> already applied', 'skip');
                continue;
            }

            try {
                $migration = require $migrationFile;
                if (!is_callable($migration)) {
                    throw new RuntimeException('Migration must return a callable.');
                }
                $migration($pdo);
                $recordApplied->execute([$version]);
                out($version . ' -> applied', 'ok');
                $versionedPass++;
            } catch (Throwable $e) {
                out($version . ' -> ' . $e->getMessage(), 'error');
                $versionedFail++;
                break;
            }
        }
    } catch (Throwable $e) {
        out('Versioned migration runner -> ' . $e->getMessage(), 'error');
        $versionedFail++;
    }
} else {
    out('Versioned migrations skipped because a prerequisite migration failed.', 'warn');
}

$pass += $versionedPass;
$fail += $versionedFail;
out("Migration summary: $pass passed, $fail failed.", $fail ? 'warn' : 'ok');

if (!$isCli) {
    echo '<hr><p style="color:#94a3b8;">This script may be re-run safely at any time. You may now delete or leave this file in place (admin-only via web).</p>
    <p><a href="../dashboard.php" style="color:#38bdf8;">← Back to Dashboard</a></p></div></body></html>';
}

if ($isCli && $fail > 0) {
    exit(1);
}
