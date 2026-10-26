<?php

function itims_direct_access_blocked(string $requestPath): bool
{
    $normalized = '/' . ltrim(str_replace('\\', '/', $requestPath), '/');
    $blockedPrefixes = [
        '/app',
        '/config',
        '/database',
        '/includes',
        '/tests',
        '/install/migrations',
        '/uploads',
    ];

    foreach ($blockedPrefixes as $prefix) {
        if ($normalized === $prefix) {
            return true;
        }

        if (stripos($normalized, $prefix . '/') === 0) {
            return true;
        }
    }

    return false;
}
