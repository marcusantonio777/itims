<?php

require_once __DIR__ . '/../../includes/license_secrets.php';

return static function (PDO $pdo): void {
    $plaintextKeys = $pdo->query(
        "SELECT id, license_key FROM software_licenses
         WHERE license_key IS NOT NULL AND license_key <> ''"
    )->fetchAll(PDO::FETCH_ASSOC);

    $hasPlaintext = false;
    foreach ($plaintextKeys as $row) {
        if (!license_secret_is_encrypted((string)$row['license_key'])) {
            $hasPlaintext = true;
            break;
        }
    }

    if ($hasPlaintext) {
        license_secret_key();
    }

    $pdo->exec('ALTER TABLE software_licenses MODIFY license_key TEXT NULL');

    if (!$hasPlaintext) {
        return;
    }

    $update = $pdo->prepare('UPDATE software_licenses SET license_key = ? WHERE id = ?');
    $pdo->beginTransaction();
    try {
        foreach ($plaintextKeys as $row) {
            $plainKey = (string)$row['license_key'];
            if (license_secret_is_encrypted($plainKey)) {
                continue;
            }
            $update->execute([encrypt_license_secret($plainKey), (int)$row['id']]);
        }
        $pdo->commit();
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $exception;
    }
};
