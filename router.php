<?php
require_once __DIR__ . '/includes/direct_access_guard.php';

$requestPath = rawurldecode((string)(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/'));
$normalizedPath = '/' . ltrim(str_replace('\\', '/', $requestPath), '/');

if (strcasecmp($normalizedPath, '/router.php') === 0) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=UTF-8');
    exit('Page not found.');
}

if (itims_direct_access_blocked($normalizedPath)) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=UTF-8');
    exit('Direct access to protected application files is not allowed.');
}

$requestedRoute = $normalizedPath === '/' || $normalizedPath === '/index.php'
    ? 'index'
    : basename($normalizedPath, '.php');
if (strtolower(pathinfo($normalizedPath, PATHINFO_EXTENSION)) === 'php') {
    require_once __DIR__ . '/app/bootstrap.php';
    if (app_resolve_route($requestedRoute) !== null) {
        app_dispatch();
        return true;
    }
}

return false;
