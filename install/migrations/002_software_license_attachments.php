<?php

return static function (PDO $pdo): void {
    $columns = [
        'attachment_name' => 'VARCHAR(255) NULL DEFAULT NULL',
        'attachment_path' => 'VARCHAR(255) NULL DEFAULT NULL',
    ];
    $columnExists = $pdo->prepare(
        'SELECT 1 FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ? LIMIT 1'
    );

    foreach ($columns as $columnName => $definition) {
        $columnExists->execute(['software_licenses', $columnName]);
        if (!$columnExists->fetchColumn()) {
            $pdo->exec('ALTER TABLE software_licenses ADD COLUMN ' . $columnName . ' ' . $definition);
        }
    }
};
