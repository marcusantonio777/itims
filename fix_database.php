<?php
// fix_database.php
$cli = (PHP_SAPI === 'cli');

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/db.php';

if (!$cli && auth_current_role() !== ROLE_ADMIN) {
    http_response_code(403);
    die('Access denied. Admin only.');
}

echo "<pre>";
try {
    echo "Starting database fixes...\n";

    echo "Checking accountability_supplies table...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS accountability_supplies (
        id INT AUTO_INCREMENT PRIMARY KEY,
        log_id INT NOT NULL,
        supply_id INT NOT NULL,
        quantity INT DEFAULT 1,
        FOREIGN KEY (log_id) REFERENCES accountability_logs(id) ON DELETE CASCADE,
        FOREIGN KEY (supply_id) REFERENCES supplies(id)
    )");
    echo "Table 'accountability_supplies' ensured.\n";

    echo "Checking accountability_logs columns...\n";
    try {
        $pdo->exec("ALTER TABLE accountability_logs ADD COLUMN return_condition ENUM('Good', 'Needs Repair', 'Damaged') NULL");
        echo "Column 'return_condition' added.\n";
    } catch (PDOException $e) {
        error_log('[ITIMS] fix_database return_condition check failed: ' . $e->getMessage());
        echo "Column 'return_condition' already exists or could not be added.\n";
    }

    try {
        $pdo->exec("ALTER TABLE accountability_logs ADD COLUMN return_notes TEXT NULL");
        echo "Column 'return_notes' added.\n";
    } catch (PDOException $e) {
        error_log('[ITIMS] fix_database return_notes check failed: ' . $e->getMessage());
        echo "Column 'return_notes' already exists or could not be added.\n";
    }

    echo "\nDatabase fixes completed successfully.\n";
} catch (PDOException $e) {
    user_error_log('Database fix failed', $e);
    echo "\nDatabase repair could not be completed. Please contact support.\n";
}
echo "</pre>";
?>