<?php
require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../includes/direct_access_guard.php';

$expectedRoutes = [
    'index',
    'dashboard',
    'inventory',
    'employees',
    'departments',
    'brand_logo',
    'generate_pdf',
    'logout',
];

foreach ($expectedRoutes as $route) {
    if (app_resolve_route($route) === null) {
        throw new RuntimeException('Application route is missing or points outside the page directory: ' . $route);
    }
}

foreach ([null, [], '../config/db', 'dashboard.php', 'install/setup'] as $invalidRoute) {
    if (app_resolve_route($invalidRoute) !== null) {
        throw new RuntimeException('An invalid application route was accepted.');
    }
}

foreach ([
    ['/itims/', '/itims', 'index'],
    ['/itims/index.php', '/itims', 'index'],
    ['/itims/dashboard.php', '/itims', 'dashboard'],
    ['/dashboard.php', '', 'dashboard'],
] as [$requestPath, $basePath, $expectedRoute]) {
    if (app_route_from_path($requestPath, $basePath) !== $expectedRoute) {
        throw new RuntimeException('An application URL did not resolve to its expected route.');
    }
}

foreach ([
    ['/other/dashboard.php', '/itims'],
    ['/unknown.php', ''],
    ['/unknown/', ''],
    ['/install/setup.php', '/itims'],
    ['/nested/dashboard.php', ''],
] as [$requestPath, $basePath]) {
    if (app_route_from_path($requestPath, $basePath) !== null) {
        throw new RuntimeException('An invalid application URL was accepted.');
    }
}

if (count(app_routes()) !== 27) {
    throw new RuntimeException('The application route allowlist is incomplete.');
}

foreach (['/app/pages/index.php', '/config/db.php', '/uploads/licenses/sample.pdf', '/uploads/file_manager/example.txt', '/uploads/assets/example.jpg'] as $blockedPath) {
    if (!itims_direct_access_blocked($blockedPath)) {
        throw new RuntimeException('Protected file paths must be rejected before routing: ' . $blockedPath);
    }
}

foreach (['/index.php', '/dashboard.php', '/brand_logo.php', '/uploads-allowed.php'] as $allowedPath) {
    if (itims_direct_access_blocked($allowedPath)) {
        throw new RuntimeException('Allowed public HTTP paths must not be rejected by the direct access guard: ' . $allowedPath);
    }
}

echo "Application route test passed.\n";
