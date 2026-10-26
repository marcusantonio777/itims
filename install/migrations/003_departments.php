<?php

return static function (PDO $pdo): void {
    $pdo->exec("CREATE TABLE IF NOT EXISTS departments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL UNIQUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $insert = $pdo->prepare('INSERT IGNORE INTO departments (name) VALUES (?)');
    $departmentNames = [
        'IT',
        'HR',
        'Finance',
        'F&B',
        'Front Office',
        'Housekeeping',
        'Sales & Marketing',
        'Engineering',
    ];
    $existingNames = $pdo->query(
        "SELECT DISTINCT TRIM(department) FROM employees
         WHERE department IS NOT NULL AND TRIM(department) <> ''"
    )->fetchAll(PDO::FETCH_COLUMN);

    foreach (array_unique(array_merge($departmentNames, $existingNames)) as $name) {
        $insert->execute([trim((string)$name)]);
    }
};
