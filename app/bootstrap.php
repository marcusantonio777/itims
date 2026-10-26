<?php
if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__));
}

function app_routes(): array
{
    return [
        'activity_logs' => 'activity_logs.php',
        'asset_details' => 'asset_details.php',
        'brand_logo' => 'brand_logo.php',
        'categories' => 'categories.php',
        'dashboard' => 'dashboard.php',
        'departments' => 'departments.php',
        'disposal' => 'disposal.php',
        'employee_details' => 'employee_details.php',
        'employees' => 'employees.php',
        'file_manager' => 'file_manager.php',
        'generate_pdf' => 'generate_pdf.php',
        'index' => 'index.php',
        'inventory' => 'inventory.php',
        'issuance' => 'issuance.php',
        'keycards' => 'keycards.php',
        'logout' => 'logout.php',
        'maintenance' => 'maintenance.php',
        'pdf_template_editor' => 'pdf_template_editor.php',
        'profile' => 'profile.php',
        'reports' => 'reports.php',
        'room_management' => 'room_management.php',
        'settings' => 'settings.php',
        'software_licenses' => 'software_licenses.php',
        'supplies' => 'supplies.php',
        'terms' => 'terms.php',
        'users' => 'users.php',
        'vendors' => 'vendors.php',
    ];
}

function app_resolve_route($route): ?string
{
    if (!is_string($route)) {
        return null;
    }

    $routes = app_routes();
    if (!isset($routes[$route])) {
        return null;
    }

    $page = APP_ROOT . '/app/pages/' . $routes[$route];
    return is_file($page) ? $page : null;
}

function app_route_from_path(string $requestPath, string $basePath = ''): ?string
{
    if ($basePath !== '') {
        if ($requestPath === $basePath) {
            $requestPath = '/';
        } elseif (str_starts_with($requestPath, $basePath . '/')) {
            $requestPath = substr($requestPath, strlen($basePath));
        } else {
            return null;
        }
    }

    if ($requestPath === '/') {
        return 'index';
    }

    $relativePath = ltrim($requestPath, '/');
    if ($relativePath === '' || str_contains($relativePath, '/')
        || strtolower(pathinfo($relativePath, PATHINFO_EXTENSION)) !== 'php') {
        return null;
    }

    $route = basename($relativePath, '.php');
    return isset(app_routes()[$route]) ? $route : null;
}

function app_dispatch(): void
{
    global $pdo, $licenseKeyEncryptionKey;

    $requestPath = parse_url((string)($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
    $requestPath = is_string($requestPath) ? $requestPath : '/';
    $scriptDirectory = str_replace('\\', '/', dirname((string)($_SERVER['SCRIPT_NAME'] ?? '/index.php')));
    $basePath = $scriptDirectory === '/' || $scriptDirectory === '.' ? '' : $scriptDirectory;
    $routeName = app_route_from_path($requestPath, $basePath);
    $page = app_resolve_route($routeName);

    if ($page === null) {
        http_response_code(404);
        header('Content-Type: text/plain; charset=UTF-8');
        echo 'Page not found.';
        return;
    }

    $_SERVER['PHP_SELF'] = $requestPath;
    $_SERVER['SCRIPT_NAME'] = $requestPath;
    require_once APP_ROOT . '/config/db.php';
    require $page;
}
