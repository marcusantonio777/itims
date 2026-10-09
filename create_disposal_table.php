<?php
// create_disposal_table.php
$cli = (PHP_SAPI === 'cli');

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/db.php';

if (!$cli && auth_current_role() !== ROLE_ADMIN) {
    http_response_code(403);
    die('Access denied. Admin only.');
}

try {
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
    )");
    echo "Asset disposal table ensured successfully.";
} catch (PDOException $e) {
    user_error_log('create_disposal_table failed', $e);
    echo generic_error();
}
?>