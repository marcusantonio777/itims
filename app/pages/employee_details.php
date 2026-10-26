<?php
// employee_details.php
require_once APP_ROOT . '/includes/auth.php';
enforce_authenticated();
require_once APP_ROOT . '/config/db.php';
enforce_page_access('employee_details.php');

if (!isset($_GET['id'])) {
    header("Location: employees.php");
    exit;
}

$id = (int)$_GET['id'];

// Fetch Employee Data
$stmt = $pdo->prepare("SELECT * FROM employees WHERE id = ?");
$stmt->execute([$id]);
$employee = $stmt->fetch();

if (!$employee) {
    header("Location: employees.php");
    exit;
}

// Fetch Currently Held Assets
$stmtCurrent = $pdo->prepare("SELECT a.*, l.date_issued, l.id as log_id FROM accountability_logs l 
                               JOIN assets a ON l.asset_id = a.id 
                               WHERE (l.user_id = ? OR EXISTS (
                                   SELECT 1 FROM accountability_log_employees ale
                                   WHERE ale.log_id = l.id AND ale.employee_id = ?
                               )) AND l.status = 'Active'");
$stmtCurrent->execute([$id, $id]);
$current_assets = $stmtCurrent->fetchAll();

// Fetch Currently Held Supplies (Linked to Assets)
$stmtSupplies = $pdo->prepare("SELECT s.*, ars.quantity, l.date_issued FROM accountability_supplies ars 
                                JOIN supplies s ON ars.supply_id = s.id 
                                JOIN accountability_logs l ON ars.log_id = l.id 
                                WHERE (l.user_id = ? OR EXISTS (
                                    SELECT 1 FROM accountability_log_employees ale
                                    WHERE ale.log_id = l.id AND ale.employee_id = ?
                                )) AND l.status = 'Active'");
$stmtSupplies->execute([$id, $id]);
$current_supplies = $stmtSupplies->fetchAll();

// Fetch History
$stmtHistory = $pdo->prepare("SELECT a.asset_tag, a.brand, a.model, l.* FROM accountability_logs l 
                               JOIN assets a ON l.asset_id = a.id 
                               WHERE (l.user_id = ? OR EXISTS (
                                   SELECT 1 FROM accountability_log_employees ale
                                   WHERE ale.log_id = l.id AND ale.employee_id = ?
                               )) AND l.status = 'Cleared'
                               ORDER BY l.return_date DESC");
$stmtHistory->execute([$id, $id]);
$history = $stmtHistory->fetchAll();

include APP_ROOT . '/includes/header.php';
?>

<div class="mb-8">
    <a href="employees.php" class="inline-flex items-center text-sm font-bold text-blue-600 hover:text-blue-800 transition">
        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
        Back to Employees
    </a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <!-- Employee Profile Card -->
    <div class="md:col-span-1">
        <div class="bg-white p-8 rounded-3xl shadow-sm border border-gray-100 text-center">
            <div class="h-24 w-24 rounded-full bg-blue-100 flex items-center justify-center text-blue-800 text-3xl font-black border-4 border-white shadow-lg mx-auto mb-4">
                <?php echo e(strtoupper(substr($employee['name'], 0, 1))); ?>
            </div>
            <h3 class="text-xl font-bold text-gray-900"><?php echo e($employee['name']); ?></h3>
            <div class="mt-8 pt-8 border-t border-gray-50 text-left space-y-4">
                <div>
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Department</p>
                    <p class="text-sm font-bold text-gray-800"><?php echo e($employee['department']); ?></p>
                </div>
                <div>
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Position</p>
                    <p class="text-sm font-bold text-gray-800"><?php echo e($employee['position'] ?: 'N/A'); ?></p>
                </div>
                <div>
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Employee ID</p>
                    <p class="text-sm font-bold text-gray-800"><?php echo e($employee['employee_id']); ?></p>
                </div>
                <div>
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">System Username</p>
                    <p class="text-sm font-bold text-gray-800"><?php echo e($employee['username'] ?: 'N/A'); ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Assets Column -->
    <div class="lg:col-span-2 space-y-8">
        <!-- Currently Held Assets -->
        <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="p-6 border-b border-gray-100 bg-gray-50/50 flex justify-between items-center">
                <h3 class="text-sm font-black text-gray-400 uppercase tracking-widest">Currently Held Equipment</h3>
                <span class="px-2.5 py-1 text-[10px] font-black bg-blue-100 text-blue-700 rounded-full"><?php echo count($current_assets) + count($current_supplies); ?> Items Total</span>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-100">
                    <thead class="bg-gray-50/50">
                        <tr>
                            <th class="px-6 py-4 text-left text-[10px] font-black text-gray-500 uppercase tracking-widest">Equipment</th>
                            <th class="px-6 py-4 text-left text-[10px] font-black text-gray-500 uppercase tracking-widest">Type</th>
                            <th class="px-6 py-4 text-left text-[10px] font-black text-gray-500 uppercase tracking-widest">Issued Date</th>
                            <th class="px-6 py-4 text-right text-[10px] font-black text-gray-500 uppercase tracking-widest">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <!-- Assets -->
                        <?php foreach ($current_assets as $a): ?>
                            <tr class="hover:bg-gray-50/50 transition border-l-4 border-blue-500">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="p-2 bg-blue-50 text-blue-600 rounded-lg mr-3">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
                                        </div>
                                        <div>
                                            <p class="text-sm font-bold text-blue-900"><?php echo e($a['asset_tag']); ?></p>
                                            <p class="text-[10px] text-gray-500 uppercase"><?php echo e($a['brand'] . ' ' . $a['model']); ?></p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-0.5 text-[10px] font-black uppercase rounded bg-blue-100 text-blue-700">Main Asset</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 font-medium">
                                    <?php echo e(date('M d, Y', strtotime($a['date_issued']))); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <a href="generate_pdf.php?log_id=<?php echo (int)$a['log_id']; ?>" target="_blank" class="text-green-600 hover:text-green-800 font-black text-[10px] uppercase tracking-tighter">View PAR</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                        <!-- Supplies -->
                        <?php foreach ($current_supplies as $s): ?>
                            <tr class="hover:bg-gray-50/50 transition border-l-4 border-purple-500">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="p-2 bg-purple-50 text-purple-600 rounded-lg mr-3">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 11m8 4V5"></path></svg>
                                        </div>
                                        <div>
                                            <p class="text-sm font-bold text-purple-900"><?php echo e($s['item_name']); ?></p>
                                            <p class="text-[10px] text-gray-500 uppercase"><?php echo e($s['brand'] . ' ' . $s['model']); ?> (x<?php echo e($s['quantity']); ?>)</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-0.5 text-[10px] font-black uppercase rounded bg-purple-100 text-purple-700">Supply</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 font-medium">
                                    <?php echo e(date('M d, Y', strtotime($s['date_issued']))); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-gray-400 italic text-xs">
                                    On PAR
                                </td>
                            </tr>
                        <?php endforeach; ?>

                        <?php if (!$current_assets && !$current_supplies): ?>
                            <tr><td colspan="4" class="px-6 py-8 text-center text-gray-400 text-sm italic">No equipment currently assigned.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- History -->
        <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="p-6 border-b border-gray-100 bg-gray-50/50">
                <h3 class="text-sm font-black text-gray-400 uppercase tracking-widest">Accountability History</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-100">
                    <thead class="bg-gray-50/50">
                        <tr>
                            <th class="px-6 py-4 text-left text-[10px] font-black text-gray-500 uppercase tracking-widest">Asset</th>
                            <th class="px-6 py-4 text-left text-[10px] font-black text-gray-500 uppercase tracking-widest">Period</th>
                            <th class="px-6 py-4 text-left text-[10px] font-black text-gray-500 uppercase tracking-widest">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <?php if ($history): foreach ($history as $h): ?>
                            <tr class="hover:bg-gray-50/50 transition">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <p class="text-sm font-bold text-gray-800"><?php echo e($h['asset_tag']); ?></p>
                                    <p class="text-[10px] text-gray-400 uppercase"><?php echo e($h['brand'] . ' ' . $h['model']); ?></p>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-xs text-gray-500">
                                    <?php echo e(date('M d, Y', strtotime($h['date_issued']))); ?> - <?php echo e(date('M d, Y', strtotime($h['return_date']))); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-0.5 text-[10px] font-black uppercase rounded bg-gray-100 text-gray-400">Cleared</span>
                                </td>
                            </tr>
                        <?php endforeach; else: ?>
                            <tr><td colspan="3" class="px-6 py-8 text-center text-gray-400 text-sm italic">No previous history.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include APP_ROOT . '/includes/footer.php'; ?>
