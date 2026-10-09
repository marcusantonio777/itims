<?php
$requestPath = rawurldecode((string)(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/'));
$normalizedPath = '/' . ltrim(str_replace('\\', '/', $requestPath), '/');
$licensePrefix = '/uploads/licenses';

if (stripos($normalizedPath, $licensePrefix) === 0
    && ($normalizedPath === $licensePrefix || isset($normalizedPath[strlen($licensePrefix)])
        && $normalizedPath[strlen($licensePrefix)] === '/')) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=UTF-8');
    exit('Direct access to license attachments is not allowed.');
}

$licenseRoot = realpath(__DIR__ . '/uploads/licenses');
$requestedFile = realpath(__DIR__ . DIRECTORY_SEPARATOR . ltrim(str_replace('/', DIRECTORY_SEPARATOR, $normalizedPath), DIRECTORY_SEPARATOR));
if ($licenseRoot !== false && $requestedFile !== false) {
    $root = rtrim($licenseRoot, DIRECTORY_SEPARATOR);
    $rootPrefix = $root . DIRECTORY_SEPARATOR;
    if (strcasecmp($requestedFile, $root) === 0
        || strncasecmp($requestedFile, $rootPrefix, strlen($rootPrefix)) === 0) {
        http_response_code(403);
        header('Content-Type: text/plain; charset=UTF-8');
        exit('Direct access to license attachments is not allowed.');
    }
}

return false;
