<?php
// reports.php
require_once __DIR__ . '/includes/auth.php';
enforce_authenticated();
require_once 'config/db.php';
enforce_page_access('reports.php');
include 'includes/header.php';

$message = '';
$report_type = $_GET['type'] ?? 'summary';
$date_from = $_GET['date_from'] ?? date('Y-01-01');
$date_to = $_GET['date_to'] ?? date('Y-m-d');

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_from)) {
    $date_from = date('Y-01-01');
}
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_to)) {
    $date_to = date('Y-m-d');
}
?>

<div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Reports & Analytics</h1>
        <p class="text-sm text-gray-500">Generate and print system data summaries</p>
    </div>
    <div class="flex gap-2 no-print">
        <button onclick="window.print()" class="bg-white border border-gray-200 hover:bg-gray-50 text-gray-700 font-bold py-2.5 px-6 rounded-xl shadow-sm transition flex items-center">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
            Print Report
        </button>
    </div>
</div>

<!-- Tabs -->
<div class="flex space-x-1 bg-gray-200/50 p-1 rounded-2xl mb-8 no-print max-w-fit flex-wrap">
    <a href="?type=summary" class="px-6 py-2.5 rounded-xl text-sm font-bold transition <?php echo $report_type === 'summary' ? 'bg-white text-blue-900 shadow-sm' : 'text-gray-500 hover:text-gray-700'; ?>">Summary</a>
    <a href="?type=deployment" class="px-6 py-2.5 rounded-xl text-sm font-bold transition <?php echo $report_type === 'deployment' ? 'bg-white text-blue-900 shadow-sm' : 'text-gray-500 hover:text-gray-700'; ?>">Deployment</a>
    <a href="?type=maintenance" class="px-6 py-2.5 rounded-xl text-sm font-bold transition <?php echo $report_type === 'maintenance' ? 'bg-white text-blue-900 shadow-sm' : 'text-gray-500 hover:text-gray-700'; ?>">Maintenance</a>
    <a href="?type=warranty" class="px-6 py-2.5 rounded-xl text-sm font-bold transition <?php echo $report_type === 'warranty' ? 'bg-white text-blue-900 shadow-sm' : 'text-gray-500 hover:text-gray-700'; ?>">Warranty</a>
    <a href="?type=licenses" class="px-6 py-2.5 rounded-xl text-sm font-bold transition <?php echo $report_type === 'licenses' ? 'bg-white text-blue-900 shadow-sm' : 'text-gray-500 hover:text-gray-700'; ?>">Licenses</a>
</div>

<!-- Filters -->
<div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 mb-8 no-print">
    <form method="GET" class="flex flex-wrap gap-6 items-end">
        <input type="hidden" name="type" value="<?php echo htmlspecialchars($report_type); ?>">
        <div>
            <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Date Range From</label>
            <input type="date" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>" class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 transition">
        </div>
        <div>
            <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Date Range To</label>
            <input type="date" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>" class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 transition">
        </div>
        <button type="submit" class="bg-[#1e3a8a] hover:bg-blue-900 text-white font-bold py-2.5 px-8 rounded-xl shadow-sm transition">Apply Filter</button>
    </form>
</div>

<!-- Report Content -->
<div class="bg-white p-8 rounded-3xl shadow-sm border border-gray-100 print:shadow-none print:border-none">
    <div class="flex justify-between items-center mb-8 border-b border-gray-100 pb-6">
        <div>
            <h2 class="text-xl font-black text-blue-900 uppercase italic"><?php echo e(app_branding($pdo)['product_name']); ?></h2>
            <p class="text-xs text-gray-400 font-bold uppercase tracking-tighter">
                <?php 
                    $titles = ['summary' => 'Asset & Inventory Summary', 'deployment' => 'Asset Deployment Status', 'maintenance' => 'Maintenance & Repair History', 'warranty' => 'Warranty Expiry Report', 'licenses' => 'Software Contract & License Report'];
                    echo $titles[$report_type] ?? 'Report';
                ?>
            </p>
        </div>
        <div class="text-right">
            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Generated On</p>
            <p class="text-sm font-bold text-gray-800"><?php echo date('F d, Y'); ?></p>
        </div>
    </div>

    <?php if ($report_type === 'summary'): ?>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mb-10">
            <?php
            $total = $pdo->query("SELECT COUNT(*) FROM assets")->fetchColumn();
            $avail = $pdo->query("SELECT COUNT(*) FROM assets WHERE status = 'Available'")->fetchColumn();
            $depl = $pdo->query("SELECT COUNT(*) FROM assets WHERE status = 'Deployed'")->fetchColumn();
            $rep = $pdo->query("SELECT COUNT(*) FROM assets WHERE status = 'Repair'")->fetchColumn();
            
            $stats = [
                ['Total Assets', $total, 'blue'],
                ['Available', $avail, 'green'],
                ['Deployed', $depl, 'indigo'],
                ['In Repair', $rep, 'yellow']
            ];
            foreach ($stats as $s): ?>
                <div class="bg-<?php echo $s[2]; ?>-50/50 p-6 rounded-2xl border border-<?php echo $s[2]; ?>-100 text-center">
                    <p class="text-3xl font-black text-<?php echo $s[2]; ?>-600"><?php echo $s[1]; ?></p>
                    <p class="text-[10px] font-black text-<?php echo $s[2]; ?>-400 uppercase tracking-widest mt-1"><?php echo $s[0]; ?></p>
                </div>
            <?php endforeach; ?>
        </div>

        <h3 class="text-xs font-black text-gray-400 uppercase tracking-widest mb-4">Breakdown by Category</h3>
        <table class="min-w-full divide-y divide-gray-100">
            <thead class="bg-gray-50/50">
                <tr>
                    <th class="px-6 py-3 text-left text-[10px] font-black text-gray-500 uppercase tracking-widest">Category</th>
                    <th class="px-6 py-3 text-center text-[10px] font-black text-gray-500 uppercase tracking-widest">Total</th>
                    <th class="px-6 py-3 text-center text-[10px] font-black text-gray-500 uppercase tracking-widest">Available</th>
                    <th class="px-6 py-3 text-center text-[10px] font-black text-gray-500 uppercase tracking-widest">Deployed</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                <?php
                $cats = $pdo->query("SELECT c.category_name, COUNT(a.id) as total, SUM(CASE WHEN a.status='Available' THEN 1 ELSE 0 END) as avail, SUM(CASE WHEN a.status='Deployed' THEN 1 ELSE 0 END) as depl FROM categories c LEFT JOIN assets a ON c.id = a.category_id GROUP BY c.id, c.category_name")->fetchAll();
                if ($cats):
                    foreach ($cats as $c): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900"><?php echo htmlspecialchars($c['category_name']); ?></td>
                            <td class="px-6 py-4 text-center text-sm font-medium text-gray-500"><?php echo (int)$c['total']; ?></td>
                            <td class="px-6 py-4 text-center text-sm font-bold text-green-600"><?php echo (int)$c['avail']; ?></td>
                            <td class="px-6 py-4 text-center text-sm font-bold text-blue-600"><?php echo (int)$c['depl']; ?></td>
                        </tr>
                    <?php endforeach;
                else: ?>
                    <tr><td colspan="4" class="px-6 py-4 text-center text-sm text-gray-400">No records found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

    <?php elseif ($report_type === 'deployment'): ?>
        <table class="min-w-full divide-y divide-gray-100">
            <thead class="bg-gray-50/50">
                <tr>
                    <th class="px-6 py-3 text-left text-[10px] font-black text-gray-500 uppercase tracking-widest">Asset Tag</th>
                    <th class="px-6 py-3 text-left text-[10px] font-black text-gray-500 uppercase tracking-widest">Employee</th>
                    <th class="px-6 py-3 text-left text-[10px] font-black text-gray-500 uppercase tracking-widest">Department</th>
                    <th class="px-6 py-3 text-left text-[10px] font-black text-gray-500 uppercase tracking-widest">Days Held</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                <?php
                $stmt = $pdo->query("SELECT a.asset_tag, e.name, e.department, l.date_issued, DATEDIFF(CURDATE(), l.date_issued) as days
                    FROM accountability_logs l
                    JOIN assets a ON l.asset_id = a.id
                    LEFT JOIN accountability_log_employees ale ON ale.log_id = l.id
                    JOIN employees e ON e.id = COALESCE(ale.employee_id, l.user_id)
                    WHERE l.status = 'Active'
                    ORDER BY days DESC, e.name ASC");
                $rows = $stmt->fetchAll();
                if ($rows):
                    foreach ($rows as $r): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-blue-900"><?php echo htmlspecialchars($r['asset_tag']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo htmlspecialchars($r['name']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-xs text-gray-500 font-bold uppercase"><?php echo htmlspecialchars($r['department']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2.5 py-1 rounded-md text-xs font-black <?php echo $r['days'] > 90 ? 'bg-red-50 text-red-600' : 'bg-green-50 text-green-600'; ?>">
                                    <?php echo (int)$r['days']; ?> Days
                                </span>
                            </td>
                        </tr>
                    <?php endforeach;
                else: ?>
                    <tr><td colspan="4" class="px-6 py-4 text-center text-sm text-gray-400">No deployments found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

    <?php elseif ($report_type === 'maintenance'): ?>
        <table class="min-w-full divide-y divide-gray-100">
            <thead class="bg-gray-50/50">
                <tr>
                    <th class="px-6 py-3 text-left text-[10px] font-black text-gray-500 uppercase tracking-widest">Asset</th>
                    <th class="px-6 py-3 text-left text-[10px] font-black text-gray-500 uppercase tracking-widest">Work Type</th>
                    <th class="px-6 py-3 text-left text-[10px] font-black text-gray-500 uppercase tracking-widest">Date</th>
                    <th class="px-6 py-3 text-right text-[10px] font-black text-gray-500 uppercase tracking-widest">Cost</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                <?php
                // Inclusive date filter check
                $stmt = $pdo->prepare("SELECT m.*, a.asset_tag FROM maintenance_logs m JOIN assets a ON m.asset_id = a.id WHERE m.maintenance_date BETWEEN ? AND ? ORDER BY m.maintenance_date DESC");
                $stmt->execute([$date_from, $date_to]);
                $rows = $stmt->fetchAll();
                $total_cost = 0;
                if ($rows):
                    foreach ($rows as $r): $total_cost += $r['cost']; ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900"><?php echo htmlspecialchars($r['asset_tag']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-xs font-black text-gray-500 uppercase tracking-tighter"><?php echo htmlspecialchars($r['maintenance_type']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-xs text-gray-500"><?php echo date('M d, Y', strtotime($r['maintenance_date'])); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-right text-gray-900">$<?php echo number_format($r['cost'], 2); ?></td>
                        </tr>
                    <?php endforeach;
                else: ?>
                    <tr><td colspan="4" class="px-6 py-4 text-center text-sm text-gray-400">No maintenance history in this date range. Expand the date filter.</td></tr>
                <?php endif; ?>
            </tbody>
            <tfoot class="bg-gray-50">
                <tr>
                    <td colspan="3" class="px-6 py-4 text-right text-[10px] font-black uppercase text-gray-400">Total Period Cost:</td>
                    <td class="px-6 py-4 text-right text-lg font-black text-blue-900">$<?php echo number_format($total_cost, 2); ?></td>
                </tr>
            </tfoot>
        </table>

    <?php elseif ($report_type === 'warranty'): ?>
        <table class="min-w-full divide-y divide-gray-100">
            <thead class="bg-gray-50/50">
                <tr>
                    <th class="px-6 py-3 text-left text-[10px] font-black text-gray-500 uppercase tracking-widest">Asset Tag</th>
                    <th class="px-6 py-3 text-left text-[10px] font-black text-gray-500 uppercase tracking-widest">Warranty Expiry</th>
                    <th class="px-6 py-3 text-left text-[10px] font-black text-gray-500 uppercase tracking-widest">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                <?php
                $stmt = $pdo->query("SELECT * FROM assets WHERE warranty_expiry IS NOT NULL ORDER BY warranty_expiry ASC");
                $rows = $stmt->fetchAll();
                if ($rows):
                    foreach ($rows as $r):
                        $days = (strtotime($r['warranty_expiry']) - time()) / 86400;
                    ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900"><?php echo htmlspecialchars($r['asset_tag']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-500"><?php echo date('M d, Y', strtotime($r['warranty_expiry'])); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2.5 py-1 rounded-md text-[10px] font-black uppercase <?php echo $days < 0 ? 'bg-red-50 text-red-600' : ($days < 30 ? 'bg-orange-50 text-orange-600' : 'bg-green-50 text-green-600'); ?>">
                                    <?php echo htmlspecialchars($days < 0 ? 'Expired' : ($days < 30 ? 'Expiring Soon' : 'Active')); ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach;
                else: ?>
                    <tr><td colspan="3" class="px-6 py-4 text-center text-sm text-gray-400">No warranty data recorded.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

    <?php elseif ($report_type === 'licenses'): ?>
        <table class="min-w-full divide-y divide-gray-100">
            <thead class="bg-gray-50/50">
                <tr>
                    <th class="px-6 py-3 text-left text-[10px] font-black text-gray-500 uppercase tracking-widest">Software</th>
                    <th class="px-6 py-3 text-left text-[10px] font-black text-gray-500 uppercase tracking-widest">Vendor</th>
                    <th class="px-6 py-3 text-left text-[10px] font-black text-gray-500 uppercase tracking-widest">Renewal</th>
                    <th class="px-6 py-3 text-left text-[10px] font-black text-gray-500 uppercase tracking-widest">Update Due</th>
                    <th class="px-6 py-3 text-left text-[10px] font-black text-gray-500 uppercase tracking-widest">Status</th>
                    <th class="px-6 py-3 text-right text-[10px] font-black text-gray-500 uppercase tracking-widest">Cost</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                <?php
                $stmt = $pdo->prepare("SELECT * FROM software_licenses WHERE (renewal_date BETWEEN ? AND ?) OR (update_due_date BETWEEN ? AND ?) OR (renewal_date IS NULL AND update_due_date IS NULL) ORDER BY COALESCE(renewal_date, update_due_date, start_date) ASC");
                $stmt->execute([$date_from, $date_to, $date_from, $date_to]);
                $rows = $stmt->fetchAll();
                $total_cost = 0;
                if ($rows):
                    foreach ($rows as $r):
                        $total_cost += (float)$r['cost'];
                    ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900"><?php echo htmlspecialchars($r['software_name']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600"><?php echo htmlspecialchars($r['vendor_name'] ?: '-'); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600"><?php echo $r['renewal_date'] ? htmlspecialchars(date('M d, Y', strtotime($r['renewal_date']))) : '-'; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600"><?php echo $r['update_due_date'] ? htmlspecialchars(date('M d, Y', strtotime($r['update_due_date']))) : '-'; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-xs font-black uppercase text-gray-500"><?php echo htmlspecialchars($r['status']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-right text-gray-900">$<?php echo number_format((float)$r['cost'], 2); ?></td>
                        </tr>
                    <?php endforeach;
                else: ?>
                    <tr><td colspan="6" class="px-6 py-4 text-center text-sm text-gray-400">No software licenses found for this period. Try expanding your date range.</td></tr>
                <?php endif; ?>
            </tbody>
            <tfoot class="bg-gray-50">
                <tr>
                    <td colspan="5" class="px-6 py-4 text-right text-[10px] font-black uppercase text-gray-400">Total Cost:</td>
                    <td class="px-6 py-4 text-right text-lg font-black text-blue-900">$<?php echo number_format($total_cost, 2); ?></td>
                </tr>
            </tfoot>
        </table>
    <?php endif; ?>

    <div class="mt-20 flex justify-between items-end">
        <div class="w-64 border-t-2 border-gray-900 pt-2 text-center">
            <p class="text-sm font-black uppercase"><?php echo $_SESSION['staff_name'] ?? 'Staff Member'; ?></p>
            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Report Prepared By</p>
        </div>
        <div class="w-64 border-t-2 border-gray-900 pt-2 text-center">
            <p class="text-sm font-black uppercase">&nbsp;</p>
            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Approved By / Management</p>
        </div>
    </div>
</div>

<style>
@media print {
    body { background: white; }
    main { padding: 0 !important; }
    .no-print { display: none !important; }
    .print\:shadow-none { box-shadow: none !important; }
    .print\:border-none { border: none !important; }
}
</style>

<?php include 'includes/footer.php'; ?>