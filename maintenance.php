<?php
require_once __DIR__ . '/includes/auth.php';
enforce_authenticated();
require_once 'config/db.php';
enforce_page_access('maintenance.php');

$message = '';
$error = '';
$edit_record = null;

if (isset($_POST['add_maintenance'])) {
    enforce_role(['admin', 'staff']);
    csrf_verify_post();
    $asset_id = (int)$_POST['asset_id'];
    $maintenance_type = $_POST['maintenance_type'];
    $description = trim($_POST['description']);
    $maintenance_date = $_POST['maintenance_date'];
    $cost = $_POST['cost'] ?: null;
    $vendor_id = $_POST['vendor_id'] ?: null;
    $next_maintenance_date = $_POST['next_maintenance_date'] ?: null;
    $status = $_POST['status'];
    $it_staff_id = auth_current_id();

    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("INSERT INTO maintenance_logs (asset_id, it_staff_id, maintenance_type, description, maintenance_date, cost, vendor_id, next_maintenance_date, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$asset_id, $it_staff_id, $maintenance_type, $description, $maintenance_date, $cost, $vendor_id, $next_maintenance_date, $status]);
        $new_id = $pdo->lastInsertId();

        if ($status === 'Completed') {
            $stmtUpdate = $pdo->prepare("UPDATE assets SET status = 'Available', next_service_date = ? WHERE id = ?");
            $stmtUpdate->execute([$next_maintenance_date ?: date('Y-m-d', strtotime('+90 days')), $asset_id]);
        } elseif ($status === 'In Progress' || $status === 'Pending') {
            $stmtUpdate = $pdo->prepare("UPDATE assets SET status = 'Repair' WHERE id = ?");
            $stmtUpdate->execute([$asset_id]);
        }

        log_activity($pdo, 'Create', 'maintenance_logs', $new_id, null, "Log maintenance for asset ID $asset_id. Status: $status");

        $pdo->commit();
        $message = "Maintenance record added and asset status synchronized.";
    } catch (Throwable $e) {
        $pdo->rollBack();
        user_error_log('Maintenance add failed', $e);
        $error = generic_error();
    }
}

if (isset($_POST['edit_maintenance'])) {
    enforce_role(['admin', 'staff']);
    csrf_verify_post();
    $log_id = (int)$_POST['log_id'];
    $asset_id = (int)$_POST['asset_id'];
    $maintenance_type = $_POST['maintenance_type'];
    $description = trim($_POST['description']);
    $maintenance_date = $_POST['maintenance_date'];
    $cost = $_POST['cost'] ?: null;
    $vendor_id = $_POST['vendor_id'] ?: null;
    $next_maintenance_date = $_POST['next_maintenance_date'] ?: null;
    $status = $_POST['status'];

    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("UPDATE maintenance_logs SET asset_id = ?, maintenance_type = ?, description = ?, maintenance_date = ?, cost = ?, vendor_id = ?, next_maintenance_date = ?, status = ? WHERE id = ?");
        $stmt->execute([$asset_id, $maintenance_type, $description, $maintenance_date, $cost, $vendor_id, $next_maintenance_date, $status, $log_id]);

        if ($status === 'Completed') {
            $stmtUpdate = $pdo->prepare("UPDATE assets SET status = 'Available', next_service_date = ? WHERE id = ?");
            $stmtUpdate->execute([$next_maintenance_date ?: date('Y-m-d', strtotime('+90 days')), $asset_id]);
        } elseif ($status === 'In Progress' || $status === 'Pending') {
            $stmtUpdate = $pdo->prepare("UPDATE assets SET status = 'Repair' WHERE id = ?");
            $stmtUpdate->execute([$asset_id]);
        }

        log_activity($pdo, 'Update', 'maintenance_logs', $log_id, null, "Updated maintenance log ID $log_id for asset ID $asset_id. Status: $status");

        $pdo->commit();
        $message = "Maintenance record updated and asset status synchronized.";
    } catch (Throwable $e) {
        $pdo->rollBack();
        user_error_log('Maintenance edit failed', $e);
        $error = generic_error();
    }
}

if (isset($_POST['delete_maintenance'])) {
    enforce_role(['admin', 'staff']);
    csrf_verify_post();
    $log_id = (int)$_POST['log_id'];

    try {
        $stmt = $pdo->prepare("DELETE FROM maintenance_logs WHERE id = ?");
        $stmt->execute([$log_id]);

        log_activity($pdo, 'Delete', 'maintenance_logs', $log_id, null, "Deleted maintenance log ID $log_id");

        $message = "Maintenance record deleted.";
    } catch (Throwable $e) {
        user_error_log('Maintenance delete failed', $e);
        $error = generic_error();
    }
}

if (isset($_GET['edit']) && ctype_digit($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    try {
        $stmt = $pdo->prepare("SELECT * FROM maintenance_logs WHERE id = ? LIMIT 1");
        $stmt->execute([$edit_id]);
        $edit_record = $stmt->fetch() ?: null;
    } catch (Throwable $e) {
        user_error_log('Maintenance edit fetch failed', $e);
        $error = generic_error();
    }
}

include 'includes/header.php';

try {
    $assets = $pdo->query("SELECT a.id, a.asset_tag, a.brand, a.model FROM assets a WHERE a.status != 'Retired' ORDER BY a.asset_tag ASC")->fetchAll();
    $vendors = $pdo->query("SELECT * FROM vendors ORDER BY vendor_name ASC")->fetchAll();
} catch (Throwable $e) {
    user_error_log('Maintenance data fetch failed', $e);
    $error = generic_error();
    $assets = [];
    $vendors = [];
}
?>

<div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Maintenance Tracker</h1>
        <p class="text-sm text-gray-500">Log repairs, services, and scheduled inspections</p>
    </div>
    <button onclick="document.getElementById('addMaintModal').classList.remove('hidden')" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 px-6 rounded-xl shadow-sm transition flex items-center">
        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
        Add Maintenance Record
    </button>
</div>

<?php if ($message): ?>
    <div class="bg-green-50 border-l-4 border-green-500 text-green-700 p-4 rounded-lg mb-6 flex items-center shadow-sm">
        <svg class="w-5 h-5 mr-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
        <span class="text-sm font-medium"><?php echo e($message); ?></span>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 rounded-lg mb-6 flex items-center shadow-sm">
        <svg class="w-5 h-5 mr-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path></svg>
        <span class="text-sm font-medium"><?php echo e($error); ?></span>
    </div>
<?php endif; ?>

<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="p-6 border-b border-gray-100 bg-gray-50/50">
        <h3 class="text-sm font-black text-gray-400 uppercase tracking-widest">Maintenance History</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-100">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-4 text-left text-[10px] font-black text-gray-500 uppercase tracking-widest">Asset</th>
                    <th class="px-6 py-4 text-left text-[10px] font-black text-gray-500 uppercase tracking-widest">Type</th>
                    <th class="px-6 py-4 text-left text-[10px] font-black text-gray-500 uppercase tracking-widest">Work Details</th>
                    <th class="px-6 py-4 text-left text-[10px] font-black text-gray-500 uppercase tracking-widest">Date</th>
                    <th class="px-6 py-4 text-left text-[10px] font-black text-gray-500 uppercase tracking-widest">Cost</th>
                    <th class="px-6 py-4 text-left text-[10px] font-black text-gray-500 uppercase tracking-widest">Status</th>
                    <th class="px-6 py-4 text-left text-[10px] font-black text-gray-500 uppercase tracking-widest">Next Svc</th>
                    <th class="px-6 py-4 text-left text-[10px] font-black text-gray-500 uppercase tracking-widest">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php
                try {
                    $stmt = $pdo->query("SELECT m.*, a.asset_tag, a.brand, a.model, s.full_name as staff_name FROM maintenance_logs m JOIN assets a ON m.asset_id = a.id LEFT JOIN it_staff s ON m.it_staff_id = s.id ORDER BY m.maintenance_date DESC");
                    $logs = $stmt->fetchAll();
                } catch (Throwable $e) {
                    user_error_log('Maintenance logs fetch failed', $e);
                    $logs = [];
                }

                if ($logs):
                    foreach ($logs as $l):
                ?>
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <p class="text-sm font-bold text-blue-900"><?php echo e($l['asset_tag']); ?></p>
                            <p class="text-[10px] text-gray-500 uppercase"><?php echo e($l['brand'].' '.$l['model']); ?></p>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php 
                                $type_classes = [
                                    'Repair' => 'bg-red-100 text-red-700 border-red-200',
                                    'Service' => 'bg-blue-100 text-blue-700 border-blue-200',
                                    'Inspection' => 'bg-purple-100 text-purple-700 border-purple-200',
                                    'Upgrade' => 'bg-green-100 text-green-700 border-green-200'
                                ];
                                $class = $type_classes[$l['maintenance_type']] ?? 'bg-gray-100 text-gray-700 border-gray-200';
                            ?>
                            <span class="px-2.5 py-1 text-[10px] font-black uppercase rounded-md border <?php echo e($class); ?>">
                                <?php echo e($l['maintenance_type']); ?>
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <p class="text-xs text-gray-600 max-w-xs truncate" title="<?php echo e($l['description']); ?>">
                                <?php echo e($l['description']); ?>
                            </p>
                            <p class="text-[10px] font-bold text-gray-400 mt-1 uppercase italic">By: <?php echo e($l['staff_name']); ?></p>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-xs text-gray-500 font-medium"><?php echo e(date('M d, Y', strtotime($l['maintenance_date']))); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900"><?php echo $l['cost'] ? e('$'.number_format($l['cost'], 2)) : '-'; ?></td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php 
                                $status_classes = [
                                    'Completed' => 'bg-green-100 text-green-700',
                                    'In Progress' => 'bg-blue-100 text-blue-700',
                                    'Pending' => 'bg-yellow-100 text-yellow-700',
                                    'Cancelled' => 'bg-red-100 text-red-700'
                                ];
                                $s_class = $status_classes[$l['status']] ?? 'bg-gray-100 text-gray-700';
                            ?>
                            <span class="px-2 py-1 text-[10px] font-black uppercase rounded <?php echo e($s_class); ?>">
                                <?php echo e($l['status']); ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-xs font-bold text-red-600"><?php echo $l['next_maintenance_date'] ? e(date('M d, Y', strtotime($l['next_maintenance_date']))) : '-'; ?></td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center space-x-2">
                                <button onclick="openEditModal(<?php echo (int)$l['id']; ?>)" class="inline-flex items-center px-3 py-1.5 text-xs font-bold text-blue-700 bg-blue-50 rounded-lg hover:bg-blue-100 transition">
                                    <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                    Edit
                                </button>
                                <form action="" method="POST" class="inline-block" onsubmit="return confirm('Are you sure you want to delete this maintenance record? This action cannot be undone.');">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="log_id" value="<?php echo (int)$l['id']; ?>">
                                    <button type="submit" name="delete_maintenance" class="inline-flex items-center px-3 py-1.5 text-xs font-bold text-red-700 bg-red-50 rounded-lg hover:bg-red-100 transition">
                                        <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                        Delete
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php
                    endforeach;
                else:
                ?>
                    <tr>
                        <td colspan="8" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center">
                                <div class="p-4 bg-gray-50 rounded-full mb-4">
                                    <svg class="w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37a1.724 1.724 0 002.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                </div>
                                <p class="text-gray-500 font-medium italic">No maintenance records found.</p>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Maintenance Modal -->
<div id="addMaintModal" class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm hidden flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-2xl overflow-hidden animate-in fade-in zoom-in duration-200">
        <div class="bg-[#1e3a8a] p-6 text-white flex justify-between items-center">
            <div>
                <h2 class="text-xl font-bold uppercase tracking-tight italic text-yellow-400">Add Maintenance Record</h2>
                <p class="text-blue-200 text-xs">Log service or repair for an asset</p>
            </div>
            <button onclick="document.getElementById('addMaintModal').classList.add('hidden')" class="bg-white/10 hover:bg-white/20 p-2 rounded-full transition">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
        <form action="" method="POST" class="p-8 space-y-5">
            <?php echo csrf_field(); ?>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="block text-gray-700 text-xs font-black uppercase tracking-widest mb-2">Select Asset *</label>
                    <select name="asset_id" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 transition" required>
                        <option value="">Select Asset</option>
                        <?php foreach ($assets as $a): ?>
                            <option value="<?php echo (int)$a['id']; ?>"><?php echo e($a['asset_tag']); ?> - <?php echo e($a['brand']); ?> <?php echo e($a['model']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-gray-700 text-xs font-black uppercase tracking-widest mb-2">Maintenance Type *</label>
                    <select name="maintenance_type" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 transition" required>
                        <option value="Service">Routine Service</option>
                        <option value="Repair">Hardware Repair</option>
                        <option value="Inspection">Visual Inspection</option>
                        <option value="Upgrade">Component Upgrade</option>
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-gray-700 text-xs font-black uppercase tracking-widest mb-2">Work Description *</label>
                <textarea name="description" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 transition" rows="3" required placeholder="Describe the maintenance work performed..."></textarea>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                <div>
                    <label class="block text-gray-700 text-xs font-black uppercase tracking-widest mb-2">Service Date *</label>
                    <input type="date" name="maintenance_date" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 transition" value="<?php echo e(date('Y-m-d')); ?>" required>
                </div>
                <div>
                    <label class="block text-gray-700 text-xs font-black uppercase tracking-widest mb-2">Service Cost ($)</label>
                    <input type="number" step="0.01" name="cost" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 transition" placeholder="0.00">
                </div>
                <div>
                    <label class="block text-gray-700 text-xs font-black uppercase tracking-widest mb-2">Provider / Vendor</label>
                    <select name="vendor_id" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 transition">
                        <option value="">Select Vendor</option>
                        <?php foreach ($vendors as $v): ?>
                            <option value="<?php echo (int)$v['id']; ?>"><?php echo e($v['vendor_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="block text-gray-700 text-xs font-black uppercase tracking-widest mb-2">Current Status *</label>
                    <select name="status" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 transition" required>
                        <option value="Pending">Pending</option>
                        <option value="In Progress">In Progress</option>
                        <option value="Completed">Completed</option>
                        <option value="Cancelled">Cancelled</option>
                    </select>
                </div>
                <div>
                    <label class="block text-gray-700 text-xs font-black uppercase tracking-widest mb-2">Schedule Next Svc</label>
                    <input type="date" name="next_maintenance_date" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 transition">
                </div>
            </div>
            <div class="flex items-center justify-end space-x-4 pt-6 border-t border-gray-100">
                <button type="button" onclick="document.getElementById('addMaintModal').classList.add('hidden')" class="bg-gray-100 hover:bg-gray-200 text-gray-600 font-bold py-3 px-8 rounded-xl transition">Cancel</button>
                <button name="add_maintenance" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-10 rounded-xl shadow-lg shadow-blue-200 transition" type="submit">Save Record</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Maintenance Modal -->
<div id="editMaintModal" class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm hidden flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-2xl overflow-hidden animate-in fade-in zoom-in duration-200">
        <div class="bg-[#1e3a8a] p-6 text-white flex justify-between items-center">
            <div>
                <h2 class="text-xl font-bold uppercase tracking-tight italic text-yellow-400">Edit Maintenance Record</h2>
                <p class="text-blue-200 text-xs">Update service or repair details</p>
            </div>
            <button onclick="document.getElementById('editMaintModal').classList.add('hidden')" class="bg-white/10 hover:bg-white/20 p-2 rounded-full transition">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
        <form id="editMaintForm" action="" method="POST" class="p-8 space-y-5">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="log_id" id="edit_log_id" value="">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="block text-gray-700 text-xs font-black uppercase tracking-widest mb-2">Select Asset *</label>
                    <select name="asset_id" id="edit_asset_id" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 transition" required>
                        <option value="">Select Asset</option>
                        <?php foreach ($assets as $a): ?>
                            <option value="<?php echo (int)$a['id']; ?>"><?php echo e($a['asset_tag']); ?> - <?php echo e($a['brand']); ?> <?php echo e($a['model']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-gray-700 text-xs font-black uppercase tracking-widest mb-2">Maintenance Type *</label>
                    <select name="maintenance_type" id="edit_maintenance_type" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 transition" required>
                        <option value="Service">Routine Service</option>
                        <option value="Repair">Hardware Repair</option>
                        <option value="Inspection">Visual Inspection</option>
                        <option value="Upgrade">Component Upgrade</option>
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-gray-700 text-xs font-black uppercase tracking-widest mb-2">Work Description *</label>
                <textarea name="description" id="edit_description" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 transition" rows="3" required placeholder="Describe the maintenance work performed..."></textarea>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                <div>
                    <label class="block text-gray-700 text-xs font-black uppercase tracking-widest mb-2">Service Date *</label>
                    <input type="date" name="maintenance_date" id="edit_maintenance_date" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 transition" required>
                </div>
                <div>
                    <label class="block text-gray-700 text-xs font-black uppercase tracking-widest mb-2">Service Cost ($)</label>
                    <input type="number" step="0.01" name="cost" id="edit_cost" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 transition" placeholder="0.00">
                </div>
                <div>
                    <label class="block text-gray-700 text-xs font-black uppercase tracking-widest mb-2">Provider / Vendor</label>
                    <select name="vendor_id" id="edit_vendor_id" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 transition">
                        <option value="">Select Vendor</option>
                        <?php foreach ($vendors as $v): ?>
                            <option value="<?php echo (int)$v['id']; ?>"><?php echo e($v['vendor_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="block text-gray-700 text-xs font-black uppercase tracking-widest mb-2">Current Status *</label>
                    <select name="status" id="edit_status" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 transition" required>
                        <option value="Pending">Pending</option>
                        <option value="In Progress">In Progress</option>
                        <option value="Completed">Completed</option>
                        <option value="Cancelled">Cancelled</option>
                    </select>
                </div>
                <div>
                    <label class="block text-gray-700 text-xs font-black uppercase tracking-widest mb-2">Schedule Next Svc</label>
                    <input type="date" name="next_maintenance_date" id="edit_next_maintenance_date" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 transition">
                </div>
            </div>
            <div class="flex items-center justify-end space-x-4 pt-6 border-t border-gray-100">
                <button type="button" onclick="document.getElementById('editMaintModal').classList.add('hidden')" class="bg-gray-100 hover:bg-gray-200 text-gray-600 font-bold py-3 px-8 rounded-xl transition">Cancel</button>
                <button name="edit_maintenance" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-10 rounded-xl shadow-lg shadow-blue-200 transition" type="submit">Update Record</button>
            </div>
        </form>
    </div>
</div>

<script>
const maintRecords = <?php
$recordsMap = [];
if (isset($logs) && is_array($logs)) {
    foreach ($logs as $l) {
        $recordsMap[$l['id']] = [
            'id' => (int)$l['id'],
            'asset_id' => (int)$l['asset_id'],
            'maintenance_type' => $l['maintenance_type'],
            'description' => $l['description'],
            'maintenance_date' => $l['maintenance_date'],
            'cost' => $l['cost'] ?? '',
            'vendor_id' => $l['vendor_id'] ?? '',
            'status' => $l['status'],
            'next_maintenance_date' => $l['next_maintenance_date'] ?? ''
        ];
    }
}
echo json_encode($recordsMap);
?>;

function openEditModal(logId) {
    const r = maintRecords[logId];
    if (!r) return;
    document.getElementById('edit_log_id').value = r.id;
    document.getElementById('edit_asset_id').value = r.asset_id;
    document.getElementById('edit_maintenance_type').value = r.maintenance_type;
    document.getElementById('edit_description').value = r.description;
    document.getElementById('edit_maintenance_date').value = r.maintenance_date;
    document.getElementById('edit_cost').value = r.cost;
    document.getElementById('edit_vendor_id').value = r.vendor_id;
    document.getElementById('edit_status').value = r.status;
    document.getElementById('edit_next_maintenance_date').value = r.next_maintenance_date;
    document.getElementById('editMaintModal').classList.remove('hidden');
}

<?php if ($edit_record): ?>
document.addEventListener('DOMContentLoaded', function() {
    openEditModal(<?php echo (int)$edit_record['id']; ?>);
});
<?php endif; ?>
</script>

<?php include 'includes/footer.php'; ?>
