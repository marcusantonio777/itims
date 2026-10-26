<?php
require_once __DIR__ . '/../includes/auth.php';

$dashboardData = [
    'staffName' => '<img src=x onerror=alert(1)>',
    'generatedAt' => '2026-10-23T09:30:00+08:00',
    'metrics' => [
        'totalAssets' => 2,
        'availableAssets' => 1,
        'deployedAssets' => 1,
        'repairAssets' => 0,
        'retiredAssets' => 0,
        'serviceAlerts' => 0,
        'warrantyAlerts' => 0,
        'lowStockItems' => 1,
        'renewalsDue' => 0,
        'updatesDue' => 0,
        'activeLicenses' => 0,
        'roomDeployments' => 0,
    ],
    'categories' => array_merge(
        [['name' => '<script>alert(1)</script>', 'count' => 2]],
        array_map(
            static fn(int $index): array => ['name' => 'Category ' . $index, 'count' => 1],
            range(2, 8)
        )
    ),
    'statuses' => [
        ['name' => 'Available', 'count' => 1],
        ['name' => 'Deployed', 'count' => 1],
    ],
    'alerts' => [
        'service' => [],
        'warranty' => [],
        'stock' => [
            [
                'title' => '<img src=x onerror=alert(1)>',
                'detail' => 'Supply item',
                'quantity' => 0,
                'minimum' => 2,
            ],
        ],
        'licenses' => [],
    ],
];

$renderDashboard = static function (array $request, array $data): string {
    $_GET = $request;
    $dashboardData = $data;
    ob_start();
    include __DIR__ . '/../includes/dashboard.php';
    return (string)ob_get_clean();
};

$stockView = $renderDashboard(['alert' => 'stock'], $dashboardData);
if (strpos($stockView, 'value="stock"') === false
    || strpos($stockView, 'class="is-active"') === false
    || strpos($stockView, '0 on hand') === false
) {
    throw new RuntimeException('The selected dashboard alert tab did not render correctly.');
}
if (strpos($stockView, '<img src=x onerror=alert(1)>') !== false
    || strpos($stockView, '<script>alert(1)</script>') !== false
    || strpos($stockView, '&lt;img src=x onerror=alert(1)&gt;') === false
) {
    throw new RuntimeException('Dashboard data was not safely escaped in the rendered HTML.');
}
if (strpos($stockView, 'View all 8 categories') === false
    || strpos($stockView, 'Category 8') !== false
) {
    throw new RuntimeException('The dashboard category preview limit did not render correctly.');
}
if (strpos($stockView, 'id="dashboard-root"') !== false
    || strpos($stockView, 'dashboard.js') !== false
) {
    throw new RuntimeException('The dashboard still depends on its removed JavaScript renderer.');
}

$invalidTabView = $renderDashboard(['alert' => ['stock']], $dashboardData);
if (!preg_match('/name="alert" value="service"\s+class="is-active"\s+aria-pressed="true"/', $invalidTabView)) {
    throw new RuntimeException('An invalid alert query value did not fall back to the service tab.');
}

$allCategoriesView = $renderDashboard(['categories' => 'all'], $dashboardData);
if (strpos($allCategoriesView, 'Category 8') === false
    || strpos($allCategoriesView, 'Show fewer') === false
) {
    throw new RuntimeException('The dashboard all-categories view did not render correctly.');
}

echo "Server-rendered dashboard test passed.\n";
