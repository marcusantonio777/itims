<?php

require_once __DIR__ . '/../includes/license_secrets.php';

$testsPassed = 0;
$assert = static function (bool $condition, string $message) use (&$testsPassed): void {
    if (!$condition) {
        throw new RuntimeException($message);
    }
    $testsPassed++;
};

$key = random_bytes(32);
putenv('ITIMS_LICENSE_KEY_ENCRYPTION_KEY=' . base64_encode($key));
$secret = 'sample-license-key-1234';
$encrypted = encrypt_license_secret($secret);
$assert(license_secret_is_encrypted($encrypted), 'Encrypted values must carry the version marker.');
$assert(!str_contains($encrypted, $secret), 'Ciphertext must not contain the original secret.');
$assert(decrypt_license_secret($encrypted) === $secret, 'The encrypted key must decrypt to its original value.');
$assert(encrypt_license_secret($secret) !== $encrypted, 'Encryption must use a fresh nonce for each value.');
$assert(decrypt_license_secret(encrypt_license_secret('')) === '', 'Empty values must round-trip safely.');

$tamperedPayload = base64_decode(substr($encrypted, strlen(LICENSE_SECRET_PREFIX)), true);
$tamperedPayload[strlen($tamperedPayload) - 1] = chr(ord($tamperedPayload[strlen($tamperedPayload) - 1]) ^ 1);
$tampered = LICENSE_SECRET_PREFIX . base64_encode($tamperedPayload);
$tamperRejected = false;
try {
    decrypt_license_secret($tampered);
} catch (RuntimeException $exception) {
    $tamperRejected = true;
}
$assert($tamperRejected, 'Modified ciphertext must fail authentication.');

putenv('ITIMS_LICENSE_KEY_ENCRYPTION_KEY=' . base64_encode(random_bytes(32)));
$wrongKeyRejected = false;
try {
    decrypt_license_secret($encrypted);
} catch (RuntimeException $exception) {
    $wrongKeyRejected = true;
}
$assert($wrongKeyRejected, 'A different encryption key must not decrypt the ciphertext.');

$legacyPlaintextRejected = false;
try {
    decrypt_license_secret($secret);
} catch (RuntimeException $exception) {
    $legacyPlaintextRejected = true;
}
$assert($legacyPlaintextRejected, 'Legacy plaintext must never be mistaken for encrypted data.');

putenv('ITIMS_LICENSE_KEY_ENCRYPTION_KEY=invalid');
$invalidKeyRejected = false;
try {
    encrypt_license_secret($secret);
} catch (RuntimeException $exception) {
    $invalidKeyRejected = true;
}
$assert($invalidKeyRejected, 'An invalid encryption key must be rejected.');

putenv('ITIMS_LICENSE_KEY_ENCRYPTION_KEY');
$missingKeyRejected = false;
try {
    encrypt_license_secret($secret);
} catch (RuntimeException $exception) {
    $missingKeyRejected = true;
}
$assert($missingKeyRejected, 'A missing encryption key must be rejected.');

echo "License secret tests passed: {$testsPassed}\n";
