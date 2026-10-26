<?php
require_once APP_ROOT . '/includes/auth.php';
require_once APP_ROOT . '/config/db.php';
enforce_page_access('disposal.php');
enforce_authenticated();

$message = '';
$error = '';

if (isset($_POST['dispose_asset'])) {
    enforce_role(['admin', 'staff']);
    csrf_verify_post();
    $asset_id = (int)$_POST['asset_id'];
    $disposal_date = $_POST['disposal_date'];
    $reason = trim($_POST['reason']);
    $method = $_POST['method'];
    $notes = trim($_POST['notes']);
    $staff_id = auth_current_id();

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("INSERT INTO asset_disposal (asset_id, disposal_date, reason, method, notes, staff_id) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$asset_id, $disposal_date, $reason, $method, $notes, $staff_id]);

        $stmtUpdate = $pdo->prepare("UPDATE assets SET status = 'Retired' WHERE id = ?");
        $stmtUpdate->execute([$asset_id]);

        $stmtClear = $pdo->prepare("UPDATE accountability_logs SET status = 'Cleared', return_date = ? WHERE asset_id = ? AND status = 'Active'");
        $stmtClear->execute([$disposal_date, $asset_id]);

        log_activity($pdo, 'Update', 'assets', $asset_id, null, "Asset retired/disposed via $method. Reason: $reason");

        $pdo->commit();
        $message = "Asset successfully disposed and moved to retirement.";
    } catch (PDOException $e) {
        $pdo->rollBack();
        user_error_log('Disposal failed', $e);
        $error = generic_error();
    }
}

if (isset($_POST['edit_disposal'])) {
    enforce_role(['admin', 'staff']);
    csrf_verify_post();
    $disposal_id = (int)$_POST['disposal_id'];
    $disposal_date = $_POST['disposal_date'];
    $reason = trim($_POST['reason']);
    $method = $_POST['method'];
    $notes = trim($_POST['notes']);

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("UPDATE asset_disposal SET disposal_date = ?, reason = ?, method = ?, notes = ? WHERE id = ?");
        $stmt->execute([$disposal_date, $reason, $method, $notes, $disposal_id]);

        log_activity($pdo, 'Update', 'asset_disposal', $disposal_id, null, "Updated disposal record: $method / $reason");

        $pdo->commit();
        $message = "Disposal record updated successfully.";
    } catch (PDOException $e) {
        $pdo->rollBack();
        user_error_log('Edit disposal failed', $e);
        $error = generic_error();
    }
}

if (isset($_POST['delete_disposal'])) {
    enforce_role(['admin', 'staff']);
    csrf_verify_post();
    $disposal_id = (int)$_POST['disposal_id'];

    try {
        $pdo->beginTransaction();

        $stmtGet = $pdo->prepare("SELECT asset_id, method, reason FROM asset_disposal WHERE id = ?");
        $stmtGet->execute([$disposal_id]);
        $record = $stmtGet->fetch();

        if ($record) {
            $stmtDel = $pdo->prepare("DELETE FROM asset_disposal WHERE id = ?");
            $stmtDel->execute([$disposal_id]);

            $asset_id = (int)$record['asset_id'];
            $method = $record['method'] ?? '';
            $reason = $record['reason'] ?? '';
            log_activity($pdo, 'Delete', 'asset_disposal', $disposal_id, null, "Deleted disposal record (asset_id=$asset_id): $method / $reason");
        }

        $pdo->commit();
        $message = "Disposal record deleted successfully.";
    } catch (PDOException $e) {
        $pdo->rollBack();
        user_error_log('Delete disposal failed', $e);
        $error = generic_error();
    }
}

$edit_disposal = null;
if (isset($_GET['edit']) && ctype_digit($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $stmtEdit = $pdo->prepare("SELECT d.*, a.asset_tag, a.brand, a.model FROM asset_disposal d JOIN assets a ON d.asset_id = a.id WHERE d.id = ? LIMIT 1");
    $stmtEdit->execute([$edit_id]);
    $edit_disposal = $stmtEdit->fetch() ?: null;
}

include APP_ROOT . '/includes/header.php';

$disposable_assets = $pdo->query("SELECT * FROM assets WHERE status IN ('Available', 'Repair') ORDER BY asset_tag ASC")->fetchAll();

$history = $pdo->query("SELECT d.*, a.asset_tag, a.brand, a.model, s.full_name as staff_name 
                        FROM asset_disposal d 
                        JOIN assets a ON d.asset_id = a.id 
                        JOIN it_staff s ON d.staff_id = s.id 
                        ORDER BY d.disposal_date DESC")->fetchAll();
?>

<div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Asset Disposal &amp; Retirement</h1>
        <p class="text-sm text-gray-500">Manage equipment lifecycle termination and disposal records</p>
    </div>
    <button onclick="document.getElementById('disposalModal').classList.remove('hidden')" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2.5 px-6 rounded-xl shadow-sm transition flex items-center">
        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
        Dispose Asset
    </button>
</div>

<?php if ($message): ?>
    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-r-xl animate-in fade-in slide-in-from-left-4">
        <?php echo e($message); ?>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-r-xl animate-in fade-in slide-in-from-left-4">
        <?php echo e($error); ?>
    </div>
<?php endif; ?>

<div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="p-6 border-b border-gray-100 bg-gray-50/50">
        <h3 class="text-sm font-black text-gray-400 uppercase tracking-widest">Disposal History</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-100">
            <thead class="bg-gray-50/50">
                <tr>
                    <th class="px-6 py-4 text-left text-[10px] font-black text-gray-500 uppercase tracking-widest">Date</th>
                    <th class="px-6 py-4 text-left text-[10px] font-black text-gray-500 uppercase tracking-widest">Asset</th>
                    <th class="px-6 py-4 text-left text-[10px] font-black text-gray-500 uppercase tracking-widest">Method</th>
                    <th class="px-6 py-4 text-left text-[10px] font-black text-gray-500 uppercase tracking-widest">Reason</th>
                    <th class="px-6 py-4 text-left text-[10px] font-black text-gray-500 uppercase tracking-widest">Handled By</th>
                    <th class="px-6 py-4 text-right text-[10px] font-black text-gray-500 uppercase tracking-widest">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                <?php if ($history): foreach ($history as $h): ?>
                    <tr class="hover:bg-gray-50/50 transition">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 font-medium">
                            <?php echo e(date('M d, Y', strtotime($h['disposal_date']))); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="p-2 bg-red-50 text-red-600 rounded-lg mr-3">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </div>
                                <div>
                                    <p class="text-sm font-bold text-gray-900"><?php echo e($h['asset_tag']); ?></p>
                                    <p class="text-[10px] text-gray-500 uppercase"><?php echo e($h['brand'] . ' ' . $h['model']); ?></p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2.5 py-1 text-[10px] font-black uppercase rounded-full bg-gray-100 text-gray-700 border border-gray-200"><?php echo e($h['method']); ?></span>
                        </td>
                        <td class="px-6 py-4">
                            <p class="text-xs text-gray-600 italic"><?php echo e($h['reason']); ?></p>
                            <?php if ($h['notes']): ?>
                                <p class="text-[10px] text-gray-400 mt-1"><?php echo e($h['notes']); ?></p>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-xs text-gray-500">
                            <?php echo e($h['staff_name']); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <div class="flex justify-end space-x-2">
                                <a href="?edit=<?php echo e($h['id']); ?>" class="p-2 text-yellow-600 hover:bg-yellow-50 rounded-lg transition" title="Edit">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                </a>
                                <form action="" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this disposal record? Asset status will remain Retired.');">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="disposal_id" value="<?php echo e($h['id']); ?>">
                                    <button type="submit" name="delete_disposal" class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition" title="Delete">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="6" class="px-6 py-8 text-center text-gray-400 text-sm italic">No disposal records found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Disposal Modal -->
<div id="disposalModal" class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm hidden flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-lg overflow-hidden animate-in fade-in zoom-in duration-200">
        <div class="bg-red-600 p-6 text-white flex justify-between items-center">
            <div>
                <h2 class="text-xl font-bold uppercase tracking-tight italic">Asset Disposal</h2>
                <p class="text-red-100 text-xs">Retire equipment from active inventory</p>
            </div>
            <button onclick="document.getElementById('disposalModal').classList.add('hidden')" class="bg-white/10 hover:bg-white/20 p-2 rounded-full transition">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
        <form action="" method="POST" class="p-8 space-y-4">
            <?php echo csrf_field(); ?>
            <div>
                <label class="block text-gray-700 text-xs font-black uppercase tracking-widest mb-2">Select Asset *</label>
                <select name="asset_id" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-red-500 transition" required>
                    <option value="">-- Choose Asset to Retire --</option>
                    <?php foreach ($disposable_assets as $a): ?>
                        <option value="<?php echo e($a['id']); ?>"><?php echo e($a['asset_tag']); ?> - <?php echo e($a['brand'] . ' ' . $a['model']); ?> (<?php echo e($a['status']); ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-gray-700 text-xs font-black uppercase tracking-widest mb-2">Disposal Date *</label>
                    <input type="date" name="disposal_date" value="<?php echo e(date('Y-m-d')); ?>" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-red-500 transition" required>
                </div>
                <div>
                    <label class="block text-gray-700 text-xs font-black uppercase tracking-widest mb-2">Method *</label>
                    <select name="method" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-red-500 transition" required>
                        <option value="Scrap">Scrap / Junk</option>
                        <option value="Sold">Sold</option>
                        <option value="Donated">Donated</option>
                        <option value="Destroyed">Destroyed</option>
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-gray-700 text-xs font-black uppercase tracking-widest mb-2">Primary Reason *</label>
                <input type="text" name="reason" placeholder="e.g. Beyond economical repair, Obsolete" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-red-500 transition" required>
            </div>
            <div>
                <label class="block text-gray-700 text-xs font-black uppercase tracking-widest mb-2">Additional Notes</label>
                <textarea name="notes" rows="3" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-red-500 transition" placeholder="Any extra details about the disposal..."></textarea>
            </div>
            <div class="flex items-center justify-end space-x-4 pt-6 border-t border-gray-100">
                <button type="button" onclick="document.getElementById('disposalModal').classList.add('hidden')" class="bg-gray-100 hover:bg-gray-200 text-gray-600 font-bold py-3 px-8 rounded-xl transition">Cancel</button>
                <button name="dispose_asset" class="bg-red-600 hover:bg-red-700 text-white font-bold py-3 px-10 rounded-xl shadow-lg shadow-red-100 transition" type="submit" onclick="return confirm('Are you sure? This will retire the asset permanently.')">Complete Disposal</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Disposal Modal -->
<?php if ($edit_disposal): ?>
<div id="editDisposalModal" class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm flex items-center justify-center z-50 p-4">
<?php else: ?>
<div id="editDisposalModal" class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm hidden flex items-center justify-center z-50 p-4">
<?php endif; ?>
    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-lg overflow-hidden animate-in fade-in zoom-in duration-200">
        <div class="bg-yellow-600 p-6 text-white flex justify-between items-center">
            <div>
                <h2 class="text-xl font-bold uppercase tracking-tight italic">Edit Disposal Record</h2>
                <p class="text-yellow-100 text-xs">Update disposal details (asset status stays Retired)</p>
            </div>
            <button onclick="document.getElementById('editDisposalModal').classList.add('hidden'); history.pushState({}, '', location.pathname);" class="bg-white/10 hover:bg-white/20 p-2 rounded-full transition">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
        <form action="" method="POST" class="p-8 space-y-4">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="disposal_id" value="<?php echo e($edit_disposal['id'] ?? ''); ?>">
            <div>
                <label class="block text-gray-700 text-xs font-black uppercase tracking-widest mb-2">Asset</label>
                <input type="text" value="<?php echo e(($edit_disposal['asset_tag'] ?? '') . ' - ' . ($edit_disposal['brand'] ?? '') . ' ' . ($edit_disposal['model'] ?? '')); ?>" class="w-full px-4 py-3 bg-gray-100 border border-gray-200 rounded-xl text-gray-600" readonly>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-gray-700 text-xs font-black uppercase tracking-widest mb-2">Disposal Date *</label>
                    <input type="date" name="disposal_date" value="<?php echo e($edit_disposal['disposal_date'] ?? date('Y-m-d')); ?>" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-yellow-500 transition" required>
                </div>
                <div>
                    <label class="block text-gray-700 text-xs font-black uppercase tracking-widest mb-2">Method *</label>
                    <select name="method" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-yellow-500 transition" required>
                        <?php
                        $cur_method = $edit_disposal['method'] ?? '';
                        $methods = ['Scrap' => 'Scrap / Junk', 'Sold' => 'Sold', 'Donated' => 'Donated', 'Destroyed' => 'Destroyed'];
                        foreach ($methods as $val => $label):
                            $sel = ($val === $cur_method) ? ' selected' : '';
                        ?>
                            <option value="<?php echo e($val); ?>"<?php echo $sel; ?>><?php echo e($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-gray-700 text-xs font-black uppercase tracking-widest mb-2">Primary Reason *</label>
                <input type="text" name="reason" value="<?php echo e($edit_disposal['reason'] ?? ''); ?>" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-yellow-500 transition" required>
            </div>
            <div>
                <label class="block text-gray-700 text-xs font-black uppercase tracking-widest mb-2">Additional Notes</label>
                <textarea name="notes" rows="3" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-yellow-500 transition" placeholder="Any extra details about the disposal..."><?php echo e($edit_disposal['notes'] ?? ''); ?></textarea>
            </div>
            <div class="flex items-center justify-end space-x-4 pt-6 border-t border-gray-100">
                <button type="button" onclick="document.getElementById('editDisposalModal').classList.add('hidden'); history.pushState({}, '', location.pathname);" class="bg-gray-100 hover:bg-gray-200 text-gray-600 font-bold py-3 px-8 rounded-xl transition">Cancel</button>
                <button name="edit_disposal" class="bg-yellow-600 hover:bg-yellow-700 text-white font-bold py-3 px-10 rounded-xl shadow-lg shadow-yellow-100 transition" type="submit">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<?php include APP_ROOT . '/includes/footer.php'; ?>
