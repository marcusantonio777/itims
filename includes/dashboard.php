<?php
$metrics = $dashboardData['metrics'];
$alerts = $dashboardData['alerts'];
$generatedAt = new DateTimeImmutable($dashboardData['generatedAt']);
$utilization = $metrics['totalAssets'] > 0
    ? (int)round($metrics['deployedAssets'] / $metrics['totalAssets'] * 100)
    : 0;
$alertTabs = [
    'service' => ['label' => 'Service', 'metric' => 'serviceAlerts', 'href' => 'maintenance.php'],
    'warranty' => ['label' => 'Warranty', 'metric' => 'warrantyAlerts', 'href' => 'reports.php?type=warranty'],
    'stock' => ['label' => 'Low stock', 'metric' => 'lowStockItems', 'href' => 'supplies.php'],
    'licenses' => ['label' => 'Licenses', 'metric' => 'renewalsDue', 'href' => 'software_licenses.php'],
];
$requestedAlertTab = $_GET['alert'] ?? null;
$activeAlertTab = is_string($requestedAlertTab) && isset($alertTabs[$requestedAlertTab])
    ? $requestedAlertTab
    : 'service';
$showAllCategories = ($_GET['categories'] ?? null) === 'all';
$visibleCategories = $showAllCategories
    ? $dashboardData['categories']
    : array_slice($dashboardData['categories'], 0, 7);
$activeTab = $alertTabs[$activeAlertTab];
$activeAlerts = $alerts[$activeAlertTab];
$statusColors = [
    'Available' => '#3b82f6',
    'Deployed' => '#10b981',
    'Repair' => '#f59e0b',
    'Retired' => '#94a3b8',
];
$icons = [
    'assets' => '<path d="m12 3 9 4.5v9L12 21l-9-4.5v-9L12 3Z"/><path d="m3.5 7.5 8.5 4.3 8.5-4.3M12 12v9"/>',
    'deployed' => '<path d="m5 12 4 4L19 6"/><circle cx="12" cy="12" r="9"/>',
    'service' => '<path d="M12 8v4l3 2"/><circle cx="12" cy="12" r="9"/>',
    'stock' => '<path d="M4 7h16v13H4zM8 7V4h8v3M8 12h8M8 16h5"/>',
    'warranty' => '<path d="M12 3 5 6v5c0 4.5 2.8 7.8 7 10 4.2-2.2 7-5.5 7-10V6l-7-3Z"/><path d="m9 12 2 2 4-4"/>',
    'license' => '<rect x="4" y="3" width="16" height="18" rx="2"/><path d="M8 8h8M8 12h5M8 16h8"/>',
    'refresh' => '<path d="M20 7v5h-5M4 17v-5h5"/><path d="M5.6 9a7 7 0 0 1 11.6-2L20 12M4 12l2.8 5a7 7 0 0 0 11.6-2"/>',
    'arrow' => '<path d="M7 17 17 7M7 7h10v10"/>',
];
$icon = static function (string $name, int $size = 20) use ($icons): string {
    $paths = $icons[$name] ?? $icons['assets'];
    return '<svg width="' . $size . '" height="' . $size
        . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"'
        . ' stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $paths . '</svg>';
};
$formatDate = static function (?string $value): string {
    if ($value === null || $value === '') {
        return 'Not scheduled';
    }

    $timestamp = strtotime(substr($value, 0, 10));
    return $timestamp === false ? 'Date unavailable' : date('M j, Y', $timestamp);
};
$number = static fn(int $value): string => number_format($value);
$today = $generatedAt->format('l, F j');
$updatedTime = $generatedAt->format('g:i A');
$hour = (int)$generatedAt->format('G');
$greeting = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');
?>
<div class="dash-app">
    <section class="dash-hero">
        <div class="dash-hero-content">
            <span class="dash-hero-label"><span></span> LIVE OPERATIONS OVERVIEW</span>
            <h1><?php echo e($greeting); ?>, <?php echo e($dashboardData['staffName']); ?>.</h1>
            <p>Your IT estate at a glance. Here is what needs your attention today.</p>
            <div class="dash-hero-meta">
                <span><?php echo e($today); ?></span><i></i>
                <span>Updated <?php echo e($updatedTime); ?></span>
            </div>
        </div>
        <div class="dash-hero-actions">
            <a class="dash-primary-action" href="inventory.php">View inventory <?php echo $icon('arrow', 16); ?></a>
            <a class="dash-secondary-action" href="issuance.php">New issuance <span>+</span></a>
            <a class="dash-refresh-action" href="dashboard.php" aria-label="Refresh dashboard">
                <?php echo $icon('refresh', 16); ?> Refresh data
            </a>
        </div>
        <div class="dash-hero-orbit dash-orbit-one"></div>
        <div class="dash-hero-orbit dash-orbit-two"></div>
    </section>

    <div class="dash-section-heading">
        <div><p class="dash-eyebrow">ASSET HEALTH</p><h2>Inventory snapshot</h2></div>
        <a href="reports.php" class="dash-muted-link">View reports <?php echo $icon('arrow', 14); ?></a>
    </div>

    <section class="dash-metrics-grid" aria-label="Inventory metrics">
        <?php
        $metricCards = [
            ['Total assets', 'totalAssets', 'Tracked in inventory', 'assets', 'blue', 'inventory.php'],
            ['Deployed', 'deployedAssets', $utilization . '% of all assets', 'deployed', 'green', 'inventory.php'],
            ['Service due', 'serviceAlerts', 'Overdue or due within 7 days', 'service', 'amber', 'maintenance.php'],
            ['Low stock', 'lowStockItems', 'At or below minimum', 'stock', 'violet', 'supplies.php'],
        ];
        foreach ($metricCards as [$label, $metric, $hint, $metricIcon, $tone, $href]):
        ?>
            <a class="dash-metric" href="<?php echo e($href); ?>">
                <div class="dash-metric-icon <?php echo e($tone); ?>"><?php echo $icon($metricIcon, 19); ?></div>
                <div class="dash-metric-copy">
                    <span><?php echo e($label); ?></span>
                    <strong><?php echo e($number($metrics[$metric])); ?></strong>
                    <small><?php echo e($hint); ?></small>
                </div>
                <span class="dash-metric-link" aria-hidden="true"><?php echo $icon('arrow', 16); ?></span>
            </a>
        <?php endforeach; ?>
    </section>

    <section class="dash-secondary-metrics" aria-label="Additional operational metrics">
        <div><span class="dash-secondary-icon warranty"><?php echo $icon('warranty', 16); ?></span><span>Warranty due in 30 days</span><strong><?php echo e($number($metrics['warrantyAlerts'])); ?></strong></div>
        <div><span class="dash-secondary-icon license"><?php echo $icon('license', 16); ?></span><span>License renewals due</span><strong><?php echo e($number($metrics['renewalsDue'])); ?></strong></div>
        <div><span class="dash-secondary-icon room"><?php echo $icon('assets', 16); ?></span><span>Room deployments · 30 days</span><strong><?php echo e($number($metrics['roomDeployments'])); ?></strong></div>
    </section>

    <section class="dash-analytics-grid">
        <div class="dash-panel dash-category-panel">
            <div class="dash-panel-heading">
                <div><p class="dash-eyebrow">INVENTORY MIX</p><h2>Assets by category</h2></div>
                <span class="dash-panel-caption"><?php echo e($number(count($dashboardData['categories']))); ?> categories</span>
            </div>
            <?php if ($dashboardData['categories'] === []): ?>
                <div class="dash-empty">Add asset categories and inventory to see the distribution.</div>
            <?php else: ?>
                <?php $maxCategoryCount = max(1, ...array_column($visibleCategories, 'count')); ?>
                <div class="dash-category-chart">
                    <?php foreach ($visibleCategories as $index => $category): ?>
                        <?php $barWidth = $category['count'] > 0 ? max(3, $category['count'] / $maxCategoryCount * 100) : 0; ?>
                        <div class="dash-bar-row">
                            <span class="dash-bar-label" title="<?php echo e($category['name']); ?>"><?php echo e($category['name']); ?></span>
                            <div class="dash-bar-track" role="img" aria-label="<?php echo e($category['name'] . ': ' . $number($category['count']) . ' assets'); ?>">
                                <div class="dash-bar-fill" style="width:<?php echo e((string)$barWidth); ?>%;opacity:<?php echo e((string)(1 - $index * 0.045)); ?>"></div>
                            </div>
                            <span class="dash-bar-value"><?php echo e($number($category['count'])); ?></span>
                        </div>
                    <?php endforeach; ?>
                    <?php if (count($dashboardData['categories']) > 7): ?>
                        <a class="dash-text-button" href="dashboard.php?alert=<?php echo e($activeAlertTab); ?><?php echo $showAllCategories ? '' : '&amp;categories=all'; ?>">
                            <?php echo $showAllCategories ? 'Show fewer' : 'View all ' . $number(count($dashboardData['categories'])) . ' categories'; ?>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
        <div class="dash-panel dash-status-panel">
            <div class="dash-panel-heading">
                <div><p class="dash-eyebrow">CURRENT STATE</p><h2>Asset status</h2></div>
                <a href="inventory.php" class="dash-icon-link" aria-label="Open inventory"><?php echo $icon('arrow', 16); ?></a>
            </div>
            <?php if ($dashboardData['statuses'] === []): ?>
                <p class="dash-empty">No asset statuses to show yet.</p>
            <?php else: ?>
                <?php
                $circumference = 2 * M_PI * 43;
                $offset = 0;
                ?>
                <div class="dash-status-wrap">
                    <div class="dash-donut">
                        <svg viewBox="0 0 112 112" role="img" aria-label="Asset status distribution across <?php echo e($number($metrics['totalAssets'])); ?> assets">
                            <circle cx="56" cy="56" r="43" fill="none" stroke="#eef2f7" stroke-width="12"></circle>
                            <g transform="rotate(-90 56 56)">
                                <?php foreach ($dashboardData['statuses'] as $status): ?>
                                    <?php
                                    $segmentLength = $metrics['totalAssets'] > 0
                                        ? $status['count'] / $metrics['totalAssets'] * $circumference
                                        : 0;
                                    $statusColor = $statusColors[$status['name']] ?? '#64748b';
                                    ?>
                                    <circle cx="56" cy="56" r="43" fill="none" stroke="<?php echo e($statusColor); ?>"
                                        stroke-width="12" stroke-dasharray="<?php echo e($segmentLength . ' ' . ($circumference - $segmentLength)); ?>"
                                        stroke-dashoffset="<?php echo e((string)-$offset); ?>"></circle>
                                    <?php $offset += $segmentLength; ?>
                                <?php endforeach; ?>
                            </g>
                        </svg>
                        <div class="dash-donut-center"><strong><?php echo e($number($metrics['totalAssets'])); ?></strong><span>assets</span></div>
                    </div>
                    <div class="dash-legend">
                        <?php foreach ($dashboardData['statuses'] as $status): ?>
                            <?php $statusColor = $statusColors[$status['name']] ?? '#64748b'; ?>
                            <div class="dash-legend-item">
                                <span class="dash-legend-dot" style="background-color:<?php echo e($statusColor); ?>"></span>
                                <span><?php echo e($status['name']); ?></span>
                                <strong><?php echo e($number($status['count'])); ?></strong>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
            <div class="dash-utilization">
                <div class="dash-utilization-copy"><span>Deployment ratio</span><strong><?php echo e((string)$utilization); ?>%</strong></div>
                <div class="dash-progress"><span style="width:<?php echo e((string)$utilization); ?>%"></span></div>
                <p><?php echo e($number($metrics['availableAssets'])); ?> assets currently available</p>
            </div>
        </div>
    </section>

    <section class="dash-bottom-grid">
        <div class="dash-panel dash-alert-panel">
            <div class="dash-panel-heading">
                <div><p class="dash-eyebrow">Needs attention</p><h2>Operations watchlist</h2></div>
                <a class="dash-outline-link" href="<?php echo e($activeTab['href']); ?>">Open module <?php echo $icon('arrow', 14); ?></a>
            </div>
            <form method="get" action="dashboard.php" class="dash-tabs" aria-label="Filter dashboard alerts">
                <?php if ($showAllCategories): ?><input type="hidden" name="categories" value="all"><?php endif; ?>
                <?php foreach ($alertTabs as $key => $tab): ?>
                    <button type="submit" name="alert" value="<?php echo e($key); ?>"
                        class="<?php echo $activeAlertTab === $key ? 'is-active' : ''; ?>"
                        <?php echo $activeAlertTab === $key ? 'aria-pressed="true"' : 'aria-pressed="false"'; ?>>
                        <?php echo e($tab['label']); ?>
                        <span><?php echo e($number($metrics[$tab['metric']])); ?></span>
                    </button>
                <?php endforeach; ?>
            </form>
            <div class="dash-alert-list">
                <?php if ($activeAlerts === []): ?>
                    <div class="dash-clear-state">
                        <span><?php echo $icon('deployed', 20); ?></span>
                        <strong>All clear</strong>
                        <p>No <?php echo e(strtolower($activeTab['label'])); ?> alerts to show right now.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($activeAlerts as $record): ?>
                        <?php
                        $alertIcon = $activeAlertTab === 'stock'
                            ? 'stock'
                            : ($activeAlertTab === 'licenses' ? 'license' : $activeAlertTab);
                        $recordDetail = $record['detail'] !== '' ? $record['detail'] : 'Details not provided';
                        ?>
                        <div class="dash-alert-row">
                            <span class="dash-alert-icon <?php echo e($activeAlertTab); ?>"><?php echo $icon($alertIcon, 17); ?></span>
                            <div class="dash-alert-main">
                                <strong><?php echo e($record['title']); ?></strong>
                                <span><?php echo e($recordDetail); ?></span>
                            </div>
                            <div class="dash-alert-meta">
                                <?php if ($activeAlertTab === 'stock'): ?>
                                    <strong class="<?php echo $record['quantity'] <= 0 ? 'is-critical' : ''; ?>"><?php echo e($number($record['quantity'])); ?> on hand</strong>
                                    <span>Min. <?php echo e($number($record['minimum'])); ?></span>
                                <?php elseif ($activeAlertTab === 'licenses'): ?>
                                    <strong><?php echo e($record['renewalDate'] ? 'Renewal ' . $formatDate($record['renewalDate']) : 'Update ' . $formatDate($record['updateDate'])); ?></strong>
                                    <span><?php echo e($record['detail']); ?></span>
                                <?php else: ?>
                                    <strong><?php echo e($formatDate($record['date'])); ?></strong>
                                    <span><?php echo e($activeAlertTab === 'service' ? $record['status'] : 'Warranty expiry'); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <aside class="dash-panel dash-action-panel">
            <div class="dash-panel-heading">
                <div><p class="dash-eyebrow">WORKSPACE</p><h2>At a glance</h2></div>
            </div>
            <div class="dash-glance-card">
                <span class="dash-glance-icon repair"><?php echo $icon('service', 18); ?></span>
                <div><strong><?php echo e($number($metrics['repairAssets'])); ?> assets</strong><span>Currently in repair</span></div>
                <a href="maintenance.php" aria-label="View maintenance"><?php echo $icon('arrow', 15); ?></a>
            </div>
            <div class="dash-glance-card">
                <span class="dash-glance-icon license"><?php echo $icon('license', 18); ?></span>
                <div><strong><?php echo e($number($metrics['activeLicenses'])); ?> licenses</strong><span><?php echo e($number($metrics['updatesDue'])); ?> updates due soon</span></div>
                <a href="software_licenses.php" aria-label="View software licenses"><?php echo $icon('arrow', 15); ?></a>
            </div>
            <div class="dash-glance-card">
                <span class="dash-glance-icon retired"><?php echo $icon('assets', 18); ?></span>
                <div><strong><?php echo e($number($metrics['retiredAssets'])); ?> assets</strong><span>Retired from service</span></div>
                <a href="inventory.php" aria-label="View inventory"><?php echo $icon('arrow', 15); ?></a>
            </div>
            <a class="dash-full-report" href="reports.php">Explore all reports <?php echo $icon('arrow', 15); ?></a>
        </aside>
    </section>
    <p class="dash-footnote">Figures are based on records available in this installation at the time shown above.</p>
</div>
