<?php
// dashboard.php
require_once __DIR__ . '/includes/auth.php';
enforce_authenticated();
require_once 'config/db.php';
enforce_page_access('dashboard.php');
include 'includes/header.php';

// Fetch summary counts
$total_assets = $pdo->query("SELECT COUNT(*) FROM assets")->fetchColumn();
$deployed_units = $pdo->query("SELECT COUNT(*) FROM assets WHERE status = 'Deployed'")->fetchColumn();
$repair_units = $pdo->query("SELECT COUNT(*) FROM assets WHERE status = 'Repair'")->fetchColumn();
$pending_service = $pdo->query("SELECT COUNT(*) FROM assets WHERE next_service_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)")->fetchColumn();
$warranty_expiring = $pdo->query("SELECT COUNT(*) FROM assets WHERE warranty_expiry BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)")->fetchColumn();
$low_stock_count = $pdo->query("SELECT COUNT(*) FROM supplies WHERE quantity <= min_stock_level")->fetchColumn();
$room_deployments = $pdo->query("SELECT COUNT(*) FROM room_deployments WHERE deployed_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetchColumn();
$license_renewals_due = $pdo->query("SELECT COUNT(*) FROM software_licenses WHERE renewal_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) AND status != 'Cancelled'")->fetchColumn();
$license_updates_due = $pdo->query("SELECT COUNT(*) FROM software_licenses WHERE update_due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) AND status != 'Cancelled'")->fetchColumn();

// Data for Charts
$status_data = $pdo->query("SELECT status, COUNT(*) as count FROM assets GROUP BY status")->fetchAll();
$category_data = $pdo->query("SELECT c.category_name, COUNT(a.id) as count FROM categories c LEFT JOIN assets a ON c.id = a.category_id GROUP BY c.id")->fetchAll();
?>

<!-- Statistics Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-8 gap-6 mb-8">
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex items-center space-x-4">
        <div class="p-3 bg-blue-50 text-blue-600 rounded-xl">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 11m8 4V5"></path></svg>
        </div>
        <div>
            <p class="text-sm text-gray-500 font-medium uppercase tracking-wider text-[9px]">Total Assets</p>
            <p class="text-2xl font-bold text-gray-900"><?php echo $total_assets; ?></p>
        </div>
    </div>
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex items-center space-x-4">
        <div class="p-3 bg-green-50 text-green-600 rounded-xl">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
        </div>
        <div>
            <p class="text-sm text-gray-500 font-medium uppercase tracking-wider text-[9px]">Deployed</p>
            <p class="text-2xl font-bold text-gray-900"><?php echo $deployed_units; ?></p>
        </div>
    </div>
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex items-center space-x-4">
        <div class="p-3 bg-yellow-50 text-yellow-600 rounded-xl">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"></path></svg>
        </div>
        <div>
            <p class="text-sm text-gray-500 font-medium uppercase tracking-wider text-[9px]">Repair</p>
            <p class="text-2xl font-bold text-gray-900"><?php echo $repair_units; ?></p>
        </div>
    </div>
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex items-center space-x-4">
        <div class="p-3 bg-red-50 text-red-600 rounded-xl">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
        </div>
        <div>
            <p class="text-sm text-gray-500 font-medium uppercase tracking-wider text-[9px]">Svc Alerts</p>
            <p class="text-2xl font-bold text-gray-900"><?php echo $pending_service; ?></p>
        </div>
    </div>
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex items-center space-x-4">
        <div class="p-3 bg-orange-50 text-orange-600 rounded-xl">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
        </div>
        <div>
            <p class="text-sm text-gray-500 font-medium uppercase tracking-wider text-[9px]">Warranty</p>
            <p class="text-2xl font-bold text-gray-900"><?php echo $warranty_expiring; ?></p>
        </div>
    </div>
    <!-- Low Stock Card -->
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex items-center space-x-4">
        <div class="p-3 bg-purple-50 text-purple-600 rounded-xl">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 11m8 4V5"></path></svg>
        </div>
        <div>
            <p class="text-sm text-gray-500 font-medium uppercase tracking-wider text-[9px]">Low Stock</p>
            <p class="text-2xl font-bold text-gray-900"><?php echo $low_stock_count; ?></p>
        </div>
    </div>
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex items-center space-x-4">
        <div class="p-3 bg-indigo-50 text-indigo-600 rounded-xl">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10l9-7 9 7v10a1 1 0 01-1 1h-5v-6H9v6H4a1 1 0 01-1-1V10z"></path></svg>
        </div>
        <div>
            <p class="text-sm text-gray-500 font-medium uppercase tracking-wider text-[9px]">Room Deploys</p>
            <p class="text-2xl font-bold text-gray-900"><?php echo $room_deployments; ?></p>
        </div>
    </div>
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex items-center space-x-4">
        <div class="p-3 bg-pink-50 text-pink-600 rounded-xl">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 3v2.25M14.25 3v2.25M4.5 8.25h15M6.75 21h10.5A2.25 2.25 0 0019.5 18.75V8.25A2.25 2.25 0 0017.25 6h-10.5A2.25 2.25 0 004.5 8.25v10.5A2.25 2.25 0 006.75 21z"></path></svg>
        </div>
        <div>
            <p class="text-sm text-gray-500 font-medium uppercase tracking-wider text-[9px]">License Due</p>
            <p class="text-2xl font-bold text-gray-900"><?php echo $license_renewals_due; ?></p>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
    <!-- Visual Analytics -->
    <div class="lg:col-span-2 bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
        <h3 class="text-lg font-bold text-gray-800 mb-6 flex items-center">
            <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
            Asset Distribution by Category
        </h3>
        <div class="h-64">
            <canvas id="categoryChart"></canvas>
        </div>
    </div>

    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
        <h3 class="text-lg font-bold text-gray-800 mb-6 flex items-center">
            <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"></path></svg>
            Status Overview
        </h3>
        <div class="h-64">
            <canvas id="statusChart"></canvas>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-4 gap-8">
    <!-- Service Alerts -->
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-lg font-bold text-gray-800 flex items-center">
                <svg class="w-5 h-5 mr-2 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                Service Alerts
            </h3>
            <a href="maintenance.php" class="text-xs font-bold text-blue-600 hover:underline">View All</a>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-gray-50 text-[10px] uppercase tracking-wider text-gray-500 font-bold">
                    <tr>
                        <th class="px-4 py-3 text-left">Asset Tag</th>
                        <th class="px-4 py-3 text-left">Service Date</th>
                        <th class="px-4 py-3 text-left">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php
                    $stmt = $pdo->prepare("SELECT a.* FROM assets a WHERE next_service_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY) ORDER BY next_service_date ASC LIMIT 5");
                    $stmt->execute();
                    $alerts = $stmt->fetchAll();
                    foreach ($alerts as $asset): ?>
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-4 py-3">
                                <p class="text-sm font-bold text-blue-900"><?php echo htmlspecialchars($asset['asset_tag']); ?></p>
                                <p class="text-[10px] text-gray-500"><?php echo htmlspecialchars($asset['brand'] . ' ' . $asset['model']); ?></p>
                            </td>
                            <td class="px-4 py-3 text-xs font-bold text-red-600"><?php echo date('M d, Y', strtotime($asset['next_service_date'])); ?></td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 text-[10px] font-black uppercase rounded-md bg-yellow-100 text-yellow-700"><?php echo htmlspecialchars($asset['status']); ?></span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$alerts): ?>
                        <tr><td colspan="3" class="px-4 py-8 text-center text-gray-400 text-sm italic">No urgent services found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Low Stock Supplies -->
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-lg font-bold text-gray-800 flex items-center">
                <svg class="w-5 h-5 mr-2 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 11m8 4V5"></path></svg>
                Low Stock Items
            </h3>
            <a href="supplies.php" class="text-xs font-bold text-blue-600 hover:underline">Manage Stock</a>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-gray-50 text-[10px] uppercase tracking-wider text-gray-500 font-bold">
                    <tr>
                        <th class="px-4 py-3 text-left">Item Name</th>
                        <th class="px-4 py-3 text-center">Current</th>
                        <th class="px-4 py-3 text-center">Min</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php
                    $stmt = $pdo->prepare("SELECT * FROM supplies WHERE quantity <= min_stock_level ORDER BY quantity ASC LIMIT 5");
                    $stmt->execute();
                    $low_stocks = $stmt->fetchAll();
                    foreach ($low_stocks as $s): 
                        $is_out = $s['quantity'] <= 0;
                    ?>
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-4 py-3">
                                <p class="text-sm font-bold text-gray-900"><?php echo htmlspecialchars($s['item_name']); ?></p>
                                <p class="text-[10px] text-gray-500 uppercase"><?php echo htmlspecialchars($s['brand']); ?></p>
                            </td>
                            <td class="px-4 py-3 text-center font-black <?php echo $is_out ? 'text-white bg-red-600 rounded-lg animate-pulse' : 'text-red-600'; ?>"><?php echo (int)$s['quantity']; ?></td>
                            <td class="px-4 py-3 text-center text-xs font-medium text-gray-500"><?php echo (int)$s['min_stock_level']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$low_stocks): ?>
                        <tr><td colspan="3" class="px-4 py-8 text-center text-gray-400 text-sm italic">All items are well-stocked.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Warranty Alerts -->
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-lg font-bold text-gray-800 flex items-center">
                <svg class="w-5 h-5 mr-2 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A3.323 3.323 0 0010.605 8.65a3.323 3.323 0 01-4.638-4.638 3.323 3.323 0 00-4.638-4.638 3.323 3.323 0 00-4.638 4.638 3.323 3.323 0 01-4.638 4.638 3.323 3.323 0 00-4.638 4.638 3.323 3.323 0 004.638 4.638 3.323 3.323 0 014.638 4.638 3.323 3.323 0 004.638 4.638 3.323 3.323 0 004.638-4.638 3.323 3.323 0 014.638-4.638 3.323 3.323 0 004.638-4.638z"></path></svg>
                Warranty Alerts
            </h3>
            <a href="reports.php?type=warranty" class="text-xs font-bold text-blue-600 hover:underline">View All</a>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-gray-50 text-[10px] uppercase tracking-wider text-gray-500 font-bold">
                    <tr>
                        <th class="px-4 py-3 text-left">Asset Tag</th>
                        <th class="px-4 py-3 text-left">Expiry Date</th>
                        <th class="px-4 py-3 text-left">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php
                    $stmt = $pdo->prepare("SELECT a.* FROM assets a WHERE warranty_expiry BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) ORDER BY warranty_expiry ASC LIMIT 5");
                    $stmt->execute();
                    $warranties = $stmt->fetchAll();
                    foreach ($warranties as $asset): ?>
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-4 py-3">
                                <p class="text-sm font-bold text-blue-900"><?php echo htmlspecialchars($asset['asset_tag']); ?></p>
                                <p class="text-[10px] text-gray-500"><?php echo htmlspecialchars($asset['brand'] . ' ' . $asset['model']); ?></p>
                            </td>
                            <td class="px-4 py-3 text-xs font-bold text-orange-600"><?php echo date('M d, Y', strtotime($asset['warranty_expiry'])); ?></td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 text-[10px] font-black uppercase rounded-md bg-blue-100 text-blue-700"><?php echo htmlspecialchars($asset['status']); ?></span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$warranties): ?>
                        <tr><td colspan="3" class="px-4 py-8 text-center text-gray-400 text-sm italic">No warranties expiring soon.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-lg font-bold text-gray-800 flex items-center">
                <svg class="w-5 h-5 mr-2 text-pink-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 3v2.25M14.25 3v2.25M4.5 8.25h15M6.75 21h10.5A2.25 2.25 0 0019.5 18.75V8.25A2.25 2.25 0 0017.25 6h-10.5A2.25 2.25 0 004.5 8.25v10.5A2.25 2.25 0 006.75 21z"></path></svg>
                Software Alerts
            </h3>
            <a href="software_licenses.php" class="text-xs font-bold text-blue-600 hover:underline">Manage</a>
        </div>
        <div class="space-y-4">
            <div class="rounded-2xl border border-orange-100 bg-orange-50 p-4">
                <p class="text-[10px] font-black uppercase tracking-widest text-orange-500">Renewals in 30 Days</p>
                <p class="text-3xl font-black text-orange-700 mt-2"><?php echo $license_renewals_due; ?></p>
            </div>
            <div class="rounded-2xl border border-purple-100 bg-purple-50 p-4">
                <p class="text-[10px] font-black uppercase tracking-widest text-purple-500">Updates in 30 Days</p>
                <p class="text-3xl font-black text-purple-700 mt-2"><?php echo $license_updates_due; ?></p>
            </div>
            <?php
            $license_rows = $pdo->query("SELECT software_name, renewal_date, update_due_date, status FROM software_licenses WHERE status != 'Cancelled' ORDER BY COALESCE(renewal_date, update_due_date) ASC LIMIT 5")->fetchAll();
            ?>
            <div class="space-y-3">
                <?php foreach ($license_rows as $license): ?>
                    <div class="rounded-xl border border-gray-100 p-3">
                        <p class="text-sm font-bold text-gray-900"><?php echo htmlspecialchars($license['software_name']); ?></p>
                        <p class="text-xs text-gray-500">
                            Renewal: <?php echo $license['renewal_date'] ? htmlspecialchars(date('M d, Y', strtotime($license['renewal_date']))) : '-'; ?>
                            • Update: <?php echo $license['update_due_date'] ? htmlspecialchars(date('M d, Y', strtotime($license['update_due_date']))) : '-'; ?>
                        </p>
                    </div>
                <?php endforeach; ?>
                <?php if (!$license_rows): ?>
                    <p class="text-sm text-gray-400 italic">No software licenses tracked yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
    // Category Chart
    const ctxCategory = document.getElementById('categoryChart').getContext('2d');
    new Chart(ctxCategory, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode(array_column($category_data, 'category_name')); ?>,
            datasets: [{
                label: 'Assets',
                data: <?php echo json_encode(array_column($category_data, 'count')); ?>,
                backgroundColor: '#3b82f6',
                borderRadius: 8,
            }]
        },
        options: {
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, grid: { display: false } }, x: { grid: { display: false } } }
        }
    });

    // Status Chart
    const ctxStatus = document.getElementById('statusChart').getContext('2d');
    new Chart(ctxStatus, {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode(array_column($status_data, 'status')); ?>,
            datasets: [{
                data: <?php echo json_encode(array_column($status_data, 'count')); ?>,
                backgroundColor: ['#10b981', '#3b82f6', '#f59e0b', '#ef4444'],
                borderWidth: 0,
            }]
        },
        options: {
            maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, padding: 20 } } },
            cutout: '70%'
        }
    });
</script>

<?php include 'includes/footer.php'; ?>
