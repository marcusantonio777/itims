<?php

return static function (PDO $pdo): void {
    $pdo->exec("CREATE TABLE IF NOT EXISTS accountability_log_employees (
        id INT AUTO_INCREMENT PRIMARY KEY,
        log_id INT NOT NULL,
        employee_id INT NOT NULL,
        UNIQUE KEY uniq_log_employee (log_id, employee_id),
        KEY fk_ale_employee (employee_id),
        FOREIGN KEY (log_id) REFERENCES accountability_logs(id) ON DELETE CASCADE,
        FOREIGN KEY (employee_id) REFERENCES employees(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec(
        'INSERT IGNORE INTO accountability_log_employees (log_id, employee_id)
         SELECT id, user_id FROM accountability_logs WHERE user_id IS NOT NULL'
    );
    $pdo->exec(
        "UPDATE categories
            SET requires_serial_number = 0
          WHERE LOWER(category_name) REGEXP 'key[[:space:]]*card|access card'"
    );
};
