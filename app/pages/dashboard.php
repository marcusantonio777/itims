<?php
require_once APP_ROOT . '/includes/auth.php';
enforce_authenticated();
require_once APP_ROOT . '/config/db.php';
enforce_page_access('dashboard.php');

$statusRows = $pdo->query('SELECT status, COUNT(*) AS count FROM assets GROUP BY status')->fetchAll(PDO::FETCH_ASSOC);
$assetCounts = [];
$totalAssets = 0;
foreach ($statusRows as $row) {
    $count = (int)$row['count'];
    $assetCounts[(string)$row['status']] = $count;
    $totalAssets += $count;
}

$categoryRows = $pdo->query(
    'SELECT c.category_name, COUNT(a.id) AS count
     FROM categories c LEFT JOIN assets a ON c.id = a.category_id
     GROUP BY c.id, c.category_name ORDER BY count DESC, c.category_name ASC'
)->fetchAll(PDO::FETCH_ASSOC);
$categoryData = array_map(static fn(array $row): array => [
    'name' => (string)$row['category_name'],
    'count' => (int)$row['count'],
], $categoryRows);

$serviceCount = (int)$pdo->query(
    'SELECT COUNT(*) FROM assets WHERE next_service_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)'
)->fetchColumn();
$serviceRows = $pdo->query(
    "SELECT asset_tag, brand, model, next_service_date AS due_date, status
     FROM assets
     WHERE next_service_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)
     ORDER BY next_service_date ASC LIMIT 6"
)->fetchAll(PDO::FETCH_ASSOC);
$warrantyCount = (int)$pdo->query(
    'SELECT COUNT(*) FROM assets WHERE warranty_expiry BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)'
)->fetchColumn();
$warrantyRows = $pdo->query(
    "SELECT asset_tag, brand, model, warranty_expiry AS due_date, status
     FROM assets
     WHERE warranty_expiry BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
     ORDER BY warranty_expiry ASC LIMIT 6"
)->fetchAll(PDO::FETCH_ASSOC);

$lowStockCount = (int)$pdo->query(
    'SELECT COUNT(*) FROM supplies WHERE quantity <= min_stock_level'
)->fetchColumn();
$lowStockRows = $pdo->query(
    'SELECT item_name, brand, quantity, min_stock_level
     FROM supplies WHERE quantity <= min_stock_level
     ORDER BY quantity ASC, item_name ASC LIMIT 6'
)->fetchAll(PDO::FETCH_ASSOC);

$renewalsDue = (int)$pdo->query(
    "SELECT COUNT(*) FROM software_licenses
     WHERE renewal_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
       AND status != 'Cancelled'"
)->fetchColumn();
$updatesDue = (int)$pdo->query(
    "SELECT COUNT(*) FROM software_licenses
     WHERE update_due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
       AND status != 'Cancelled'"
)->fetchColumn();
$activeLicenses = (int)$pdo->query(
    "SELECT COUNT(*) FROM software_licenses WHERE status IN ('Active', 'Expiring Soon')"
)->fetchColumn();
$licenseRows = $pdo->query(
    "SELECT software_name, renewal_date, update_due_date, status
     FROM software_licenses
     WHERE status != 'Cancelled'
       AND (
           renewal_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
           OR update_due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
       )
     ORDER BY LEAST(
         COALESCE(renewal_date, '9999-12-31'),
         COALESCE(update_due_date, '9999-12-31')
     ) ASC LIMIT 6"
)->fetchAll(PDO::FETCH_ASSOC);
$roomDeployments = (int)$pdo->query(
    'SELECT COUNT(*) FROM room_deployments WHERE deployed_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)'
)->fetchColumn();

$dashboardData = [
    'staffName' => auth_current_name(),
    'generatedAt' => date(DATE_ATOM),
    'metrics' => [
        'totalAssets' => $totalAssets,
        'availableAssets' => $assetCounts['Available'] ?? 0,
        'deployedAssets' => $assetCounts['Deployed'] ?? 0,
        'repairAssets' => $assetCounts['Repair'] ?? 0,
        'retiredAssets' => $assetCounts['Retired'] ?? 0,
        'serviceAlerts' => $serviceCount,
        'warrantyAlerts' => $warrantyCount,
        'lowStockItems' => $lowStockCount,
        'renewalsDue' => $renewalsDue,
        'updatesDue' => $updatesDue,
        'activeLicenses' => $activeLicenses,
        'roomDeployments' => $roomDeployments,
    ],
    'categories' => $categoryData,
    'statuses' => array_map(static fn(array $row): array => [
        'name' => (string)$row['status'],
        'count' => (int)$row['count'],
    ], $statusRows),
    'alerts' => [
        'service' => array_map(static fn(array $row): array => [
            'title' => (string)$row['asset_tag'],
            'detail' => trim((string)$row['brand'] . ' ' . (string)$row['model']),
            'date' => (string)$row['due_date'],
            'status' => (string)$row['status'],
        ], $serviceRows),
        'warranty' => array_map(static fn(array $row): array => [
            'title' => (string)$row['asset_tag'],
            'detail' => trim((string)$row['brand'] . ' ' . (string)$row['model']),
            'date' => (string)$row['due_date'],
            'status' => (string)$row['status'],
        ], $warrantyRows),
        'stock' => array_map(static fn(array $row): array => [
            'title' => (string)$row['item_name'],
            'detail' => trim((string)($row['brand'] ?? '')),
            'quantity' => (int)$row['quantity'],
            'minimum' => (int)$row['min_stock_level'],
        ], $lowStockRows),
        'licenses' => array_map(static fn(array $row): array => [
            'title' => (string)$row['software_name'],
            'detail' => (string)$row['status'],
            'renewalDate' => $row['renewal_date'] ? (string)$row['renewal_date'] : null,
            'updateDate' => $row['update_due_date'] ? (string)$row['update_due_date'] : null,
        ], $licenseRows),
    ],
];

include APP_ROOT . '/includes/header.php';
?>
<link rel="stylesheet" href="assets/dashboard/dashboard.css">
<?php include APP_ROOT . '/includes/dashboard.php'; ?>
<?php include APP_ROOT . '/includes/footer.php'; ?>
