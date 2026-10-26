<?php

return static function (PDO $pdo): void {
    $columns = [
        'credential_username' => 'VARCHAR(255) NULL DEFAULT NULL',
        'credential_password' => 'VARCHAR(512) NULL DEFAULT NULL',
    ];
    $columnExists = $pdo->prepare(
        'SELECT 1 FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ? LIMIT 1'
    );

    foreach ($columns as $columnName => $definition) {
        $columnExists->execute(['accountability_logs', $columnName]);
        if (!$columnExists->fetchColumn()) {
            $pdo->exec('ALTER TABLE accountability_logs ADD COLUMN ' . $columnName . ' ' . $definition);
        }
    }
};
