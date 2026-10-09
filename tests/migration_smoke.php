<?php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/license_secrets.php';

$migrationCount = $pdo->query(
    "SELECT COUNT(*) FROM schema_migrations
     WHERE version IN ('001_encrypt_license_keys', '002_software_license_attachments')"
)->fetchColumn();
if ((int)$migrationCount !== 2) {
    throw new RuntimeException('Expected versioned software-license migrations were not recorded.');
}

$licenseKeyType = $pdo->query(
    "SELECT DATA_TYPE FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'software_licenses' AND COLUMN_NAME = 'license_key'"
)->fetchColumn();
if (strtolower((string)$licenseKeyType) !== 'text') {
    throw new RuntimeException('The software license key column must use TEXT storage for authenticated ciphertext.');
}

$attachmentColumns = $pdo->query(
    "SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'software_licenses'
       AND COLUMN_NAME IN ('attachment_name', 'attachment_path')"
)->fetchColumn();
if ((int)$attachmentColumns !== 2) {
    throw new RuntimeException('Fresh and upgraded databases must include software license attachment fields.');
}

$auditType = $pdo->query(
    "SELECT COLUMN_TYPE FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'audit_logs' AND COLUMN_NAME = 'action_type'"
)->fetchColumn();
if (!is_string($auditType) || strpos($auditType, 'LicenseKeyReveal') === false) {
    throw new RuntimeException('License-key reveal events must be supported by the audit log.');
}

$legacyTestKey = $pdo->query(
    "SELECT license_key FROM software_licenses WHERE software_name = 'migration-test' LIMIT 1"
)->fetchColumn();
if (!is_string($legacyTestKey) || !license_secret_is_encrypted($legacyTestKey)
    || decrypt_license_secret($legacyTestKey) !== 'ci-legacy-key') {
    throw new RuntimeException('Legacy plaintext software license keys must be encrypted without data loss.');
}

echo "Database migration smoke tests passed.\n";
