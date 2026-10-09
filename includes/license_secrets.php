<?php

const LICENSE_SECRET_PREFIX = 'enc:v1:';

class LicenseSecretException extends RuntimeException {}

function license_secret_key(): string {
    $encodedKey = getenv('ITIMS_LICENSE_KEY_ENCRYPTION_KEY');
    if ((!is_string($encodedKey) || trim($encodedKey) === '')
        && isset($GLOBALS['licenseKeyEncryptionKey'])
        && is_string($GLOBALS['licenseKeyEncryptionKey'])) {
        $encodedKey = $GLOBALS['licenseKeyEncryptionKey'];
    }
    if (!is_string($encodedKey) || trim($encodedKey) === '') {
        throw new LicenseSecretException('ITIMS_LICENSE_KEY_ENCRYPTION_KEY is not configured.');
    }

    $key = base64_decode(trim($encodedKey), true);
    if (!is_string($key) || strlen($key) !== 32) {
        throw new LicenseSecretException('ITIMS_LICENSE_KEY_ENCRYPTION_KEY must be base64 for exactly 32 random bytes.');
    }

    if (!function_exists('openssl_encrypt') || !function_exists('openssl_decrypt')) {
        throw new LicenseSecretException('OpenSSL is required to protect software license keys.');
    }

    return $key;
}

function license_secret_key_is_configured(): bool {
    try {
        license_secret_key();
        return true;
    } catch (RuntimeException $exception) {
        return false;
    }
}

function license_secret_is_encrypted(?string $value): bool {
    return is_string($value) && str_starts_with($value, LICENSE_SECRET_PREFIX);
}

function encrypt_license_secret(string $value): string {
    $key = license_secret_key();
    $nonce = random_bytes(12);
    $tag = '';
    $ciphertext = openssl_encrypt($value, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $nonce, $tag);
    if (!is_string($ciphertext) || strlen($tag) !== 16) {
        throw new LicenseSecretException('Software license key encryption failed.');
    }

    return LICENSE_SECRET_PREFIX . base64_encode($nonce . $tag . $ciphertext);
}

function decrypt_license_secret(string $value): string {
    if (!license_secret_is_encrypted($value)) {
        throw new LicenseSecretException('Software license key has not been encrypted by the database migration.');
    }

    $payload = base64_decode(substr($value, strlen(LICENSE_SECRET_PREFIX)), true);
    if (!is_string($payload) || strlen($payload) < 28) {
        throw new LicenseSecretException('Stored software license key has an invalid encrypted format.');
    }

    $key = license_secret_key();
    $nonce = substr($payload, 0, 12);
    $tag = substr($payload, 12, 16);
    $ciphertext = substr($payload, 28);
    $plaintext = openssl_decrypt($ciphertext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $nonce, $tag);
    if (!is_string($plaintext)) {
        throw new LicenseSecretException('Stored software license key could not be authenticated or decrypted.');
    }

    return $plaintext;
}
