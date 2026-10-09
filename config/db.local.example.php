<?php
// config/db.local.example.php
// Copy this file to db.local.php (NOT committed to source control)
// and fill in with your actual database credentials.

$host    = 'localhost';
$db      = 'itims_db';
$user    = 'itims_user';
$pass    = 'REPLACE_ME_STRONG_PASSWORD';
$charset = 'utf8mb4';

// Generate with: php -r "echo base64_encode(random_bytes(32)), PHP_EOL;"
// Store outside the web root in production; never change it after encrypting keys
// unless you first decrypt/re-encrypt every saved software license key.
$licenseKeyEncryptionKey = 'REPLACE_WITH_BASE64_ENCODED_32_RANDOM_BYTES';
