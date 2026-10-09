<?php
require_once __DIR__ . '/includes/auth.php';
enforce_authenticated();
require_once 'config/db.php';
enforce_page_access('room_management.php');

$message = '';
$error = '';

if (isset($_POST['deploy_items'])) {
    enforce_role(['admin', 'staff']);
    csrf_verify_post();
    $target_type = $_POST['target_type'] ?? 'Room';
    $room_number = trim($_POST['room_number'] ?? '');
    $employee_id = !empty($_POST['employee_id']) ? (int)$_POST['employee_id'] : null;
    $target_label = trim($_POST['target_label'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    $selected_supplies = $_POST['supply_qty'] ?? [];

    $deployment_items = [];
    foreach ($selected_supplies as $supply_id => $quantity) {
        $quantity = (int)$quantity;
        if ($quantity > 0) {
            $deployment_items[(int)$supply_id] = $quantity;
        }
    }

    if (empty($deployment_items)) {
        $error = "Select at least one supply item with quantity.";
    } elseif ($target_type === 'Room' && $room_number === '') {
        $error = "Room number is required when deploying to a room.";
    } elseif ($target_type === 'Employee' && empty($employee_id)) {
        $error = "Please choose an employee target.";
    } elseif ($target_type === 'Other' && $target_label === '') {
        $error = "Please describe the custom target.";
    } else {
        try {
            $pdo->beginTransaction();

            $stmtDeployment = $pdo->prepare("INSERT INTO room_deployments (target_type, room_number, employee_id, target_label, notes, deployed_by) VALUES (?, ?, ?, ?, ?, ?)");
            $stmtDeployment->execute([
                $target_type,
                $target_type === 'Room' ? $room_number : null,
                $target_type === 'Employee' ? $employee_id : null,
                $target_type === 'Other' ? $target_label : ($target_type === 'Room' ? $room_number : null),
                $notes !== '' ? $notes : null,
                auth_current_id()
            ]);
            $deployment_id = (int)$pdo->lastInsertId();

            $stockStmt = $pdo->prepare("SELECT item_name, quantity FROM supplies WHERE id = ?");
            $insertItemStmt = $pdo->prepare("INSERT INTO room_deployment_items (deployment_id, supply_id, quantity) VALUES (?, ?, ?)");
            $updateStockStmt = $pdo->prepare("UPDATE supplies SET quantity = quantity - ? WHERE id = ?");
            $logSupplyStmt = $pdo->prepare("INSERT INTO supply_logs (supply_id, staff_id, employee_id, action_type, quantity_changed, notes) VALUES (?, ?, ?, 'Stock Out', ?, ?)");

            $summary_parts = [];
            foreach ($deployment_items as $supply_id => $quantity) {
                $stockStmt->execute([$supply_id]);
                $supply = $stockStmt->fetch();

                if (!$supply) {
                    throw new Exception("One of the selected supplies no longer exists.");
                }

                if ((int)$supply['quantity'] < $quantity) {
                    throw new Exception("Not enough stock for {$supply['item_name']}.");
                }

                $insertItemStmt->execute([$deployment_id, $supply_id, $quantity]);
                $updateStockStmt->execute([$quantity, $supply_id]);
                $logSupplyStmt->execute([
                    $supply_id,
                    auth_current_id(),
                    $target_type === 'Employee' ? $employee_id : null,
                    $quantity,
                    "Deployed via Room Management to {$target_type}" . ($room_number !== '' ? " {$room_number}" : ($target_label !== '' ? " {$target_label}" : ''))
                ]);
                $summary_parts[] = $supply['item_name'] . " x{$quantity}";
            }

            $target_summary = $target_type === 'Room'
                ? "Room {$room_number}"
                : ($target_type === 'Employee'
                    ? "Employee ID {$employee_id}"
                    : $target_label);

            log_activity(
                $pdo,
                'Issue',
                'room_deployments',
                $deployment_id,
                null,
                "Deployed " . implode(', ', $summary_parts) . " to {$target_type}: {$target_summary}"
            );

            $pdo->commit();
            $message = "Items deployed successfully.";
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            user_error_log('Room deploy failed', $e);
            $error = generic_error();
        }
    }
}

$employees = $pdo->query("SELECT id, name, department FROM employees ORDER BY name ASC")->fetchAll();
$supplies = $pdo->query("SELECT id, item_name, brand, model, quantity, unit, min_stock_level FROM supplies ORDER BY item_name ASC")->fetchAll();
$recent_deployments = $pdo->query("
    SELECT
        d.*,
        e.name AS employee_name,
        s.full_name AS deployed_by_name,
        GROUP_CONCAT(CONCAT(sp.item_name, ' x', di.quantity) ORDER BY sp.item_name SEPARATOR ', ') AS deployed_items
    FROM room_deployments d
    LEFT JOIN employees e ON d.employee_id = e.id
    LEFT JOIN it_staff s ON d.deployed_by = s.id
    LEFT JOIN room_deployment_items di ON d.id = di.deployment_id
    LEFT JOIN supplies sp ON di.supply_id = sp.id
    GROUP BY d.id
    ORDER BY d.deployed_at DESC
    LIMIT 50
")->fetchAll();

include 'includes/header.php';
?>

<div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Item Deployment</h1>
        <p class="text-sm text-gray-500">Deploy IT-related room items to a room, employee, or custom target</p>
    </div>
    <button onclick="document.getElementById('deployModal').classList.remove('hidden')" class="bg-[#1e3a8a] hover:bg-blue-900 text-white font-bold py-2.5 px-6 rounded-xl shadow-sm transition flex items-center">
        <svg class="w-5 h-5 mr-2 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
        New Deployment
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

<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
        <p class="text-[10px] uppercase tracking-widest text-gray-400 font-black">Total Deployments</p>
        <p class="text-3xl font-black text-blue-900 mt-2"><?php echo count($recent_deployments); ?></p>
        <p class="text-xs text-gray-500 mt-2">Latest 50 item deployments</p>
    </div>
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
        <p class="text-[10px] uppercase tracking-widest text-gray-400 font-black">Room Targets</p>
        <p class="text-3xl font-black text-indigo-700 mt-2"><?php echo count(array_filter($recent_deployments, fn($row) => $row['target_type'] === 'Room')); ?></p>
        <p class="text-xs text-gray-500 mt-2">Deployments assigned directly to room numbers</p>
    </div>
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
        <p class="text-[10px] uppercase tracking-widest text-gray-400 font-black">Available Supply SKUs</p>
        <p class="text-3xl font-black text-purple-700 mt-2"><?php echo count($supplies); ?></p>
        <p class="text-xs text-gray-500 mt-2">Choose batteries, boards, locksets, RJ45, and more</p>
    </div>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="p-6 border-b border-gray-100 bg-gray-50/50">
        <h3 class="text-sm font-black text-gray-400 uppercase tracking-widest">Recent Deployments</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-100">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-4 text-left text-[10px] font-black text-gray-500 uppercase tracking-widest">When</th>
                    <th class="px-6 py-4 text-left text-[10px] font-black text-gray-500 uppercase tracking-widest">Target</th>
                    <th class="px-6 py-4 text-left text-[10px] font-black text-gray-500 uppercase tracking-widest">Items</th>
                    <th class="px-6 py-4 text-left text-[10px] font-black text-gray-500 uppercase tracking-widest">Notes</th>
                    <th class="px-6 py-4 text-left text-[10px] font-black text-gray-500 uppercase tracking-widest">Deployed By</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php if ($recent_deployments): ?>
                    <?php foreach ($recent_deployments as $deployment): ?>
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo date('M d, Y H:i', strtotime($deployment['deployed_at'])); ?></td>
                            <td class="px-6 py-4">
                                <p class="text-sm font-bold text-gray-900"><?php echo htmlspecialchars($deployment['target_type']); ?></p>
                                <p class="text-xs text-gray-500">
                                    <?php
                                    if ($deployment['target_type'] === 'Room') {
                                        echo 'Room ' . htmlspecialchars($deployment['room_number']);
                                    } elseif ($deployment['target_type'] === 'Employee') {
                                        echo htmlspecialchars($deployment['employee_name'] ?: 'Selected employee');
                                    } else {
                                        echo htmlspecialchars($deployment['target_label']);
                                    }
                                    ?>
                                </p>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600"><?php echo htmlspecialchars($deployment['deployed_items'] ?: '-'); ?></td>
                            <td class="px-6 py-4 text-sm text-gray-500"><?php echo htmlspecialchars($deployment['notes'] ?: '-'); ?></td>
                            <td class="px-6 py-4 text-sm font-medium text-gray-700"><?php echo htmlspecialchars($deployment['deployed_by_name'] ?: '-'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-gray-400 italic">No room deployments yet.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="deployModal" class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm hidden flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-6xl overflow-hidden animate-in fade-in zoom-in duration-200">
        <div class="bg-[#1e3a8a] p-6 text-white flex justify-between items-center">
            <div>
                <h2 class="text-xl font-bold uppercase tracking-tight">Deploy Room Items</h2>
                <p class="text-blue-200 text-xs">Choose where the supply items are being deployed</p>
            </div>
            <button type="button" onclick="document.getElementById('deployModal').classList.add('hidden')" class="bg-white/10 hover:bg-white/20 p-2 rounded-full transition">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
        <form action="" method="POST" class="p-8 grid grid-cols-1 xl:grid-cols-3 gap-8 max-h-[80vh] overflow-y-auto custom-scrollbar">
            <?php echo csrf_field(); ?>
            <div class="space-y-4 xl:col-span-1">
                <div>
                    <label class="block text-gray-700 text-xs font-black uppercase tracking-widest mb-2">Deploy To *</label>
                    <select name="target_type" id="deployment_target_type" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 transition" onchange="toggleDeploymentTargetFields()" required>
                        <option value="Room">Room</option>
                        <option value="Employee">Employee</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div id="room_target_group">
                    <label class="block text-gray-700 text-xs font-black uppercase tracking-widest mb-2">Room Number *</label>
                    <input type="text" name="room_number" id="room_number" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 transition" placeholder="e.g. 1205">
                </div>
                <div id="employee_target_group" class="hidden">
                    <label class="block text-gray-700 text-xs font-black uppercase tracking-widest mb-2">Employee *</label>
                    <select name="employee_id" id="employee_id" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 transition">
                        <option value="">Select Employee</option>
                        <?php foreach ($employees as $employee): ?>
                            <option value="<?php echo e($employee['id']); ?>">
                                <?php echo e($employee['name'] . ($employee['department'] ? ' - ' . $employee['department'] : '')); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div id="other_target_group" class="hidden">
                    <label class="block text-gray-700 text-xs font-black uppercase tracking-widest mb-2">Custom Target *</label>
                    <input type="text" name="target_label" id="target_label" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 transition" placeholder="e.g. Server Rack B / Front Desk">
                </div>
                <div>
                    <label class="block text-gray-700 text-xs font-black uppercase tracking-widest mb-2">Notes</label>
                    <textarea name="notes" rows="4" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 transition" placeholder="Assign who deployed the supplies"></textarea>
                </div>
            </div>
            <div class="xl:col-span-2">
                <div class="rounded-3xl border border-gray-100 overflow-hidden">
                    <div class="p-4 bg-gray-50 border-b border-gray-100 flex justify-between items-center">
                        <h3 class="text-sm font-black text-gray-400 uppercase tracking-widest">Available Supplies</h3>
                        <p class="text-xs text-gray-500">Enter quantity for every item you want to deploy</p>
                    </div>
                    <div class="overflow-x-auto max-h-[420px]">
                        <table class="min-w-full divide-y divide-gray-100">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-[10px] font-black text-gray-500 uppercase tracking-widest">Item</th>
                                    <th class="px-4 py-3 text-center text-[10px] font-black text-gray-500 uppercase tracking-widest">Stock</th>
                                    <th class="px-4 py-3 text-center text-[10px] font-black text-gray-500 uppercase tracking-widest">Min</th>
                                    <th class="px-4 py-3 text-center text-[10px] font-black text-gray-500 uppercase tracking-widest">Deploy Qty</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <?php foreach ($supplies as $supply): ?>
                                    <tr class="hover:bg-gray-50 transition">
                                        <td class="px-4 py-3">
                                            <p class="text-sm font-bold text-gray-900"><?php echo htmlspecialchars($supply['item_name']); ?></p>
                                            <p class="text-[10px] text-gray-500 uppercase"><?php echo htmlspecialchars(trim(($supply['brand'] ?? '') . ' ' . ($supply['model'] ?? '')) ?: ($supply['unit'] ?? '')); ?></p>
                                        </td>
                                        <td class="px-4 py-3 text-center text-sm font-bold <?php echo (int)$supply['quantity'] <= (int)$supply['min_stock_level'] ? 'text-red-600' : 'text-gray-700'; ?>">
                                            <?php echo (int)$supply['quantity']; ?>
                                        </td>
                                        <td class="px-4 py-3 text-center text-xs text-gray-500"><?php echo (int)$supply['min_stock_level']; ?></td>
                                        <td class="px-4 py-3 text-center">
                                            <input
                                                type="number"
                                                min="0"
                                                max="<?php echo e($supply['quantity']); ?>"
                                                value="0"
                                                name="supply_qty[<?php echo e($supply['id']); ?>]"
                                                class="w-24 px-3 py-2 text-center bg-white border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 transition"
                                            >
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (!$supplies): ?>
                                    <tr>
                                        <td colspan="4" class="px-4 py-10 text-center text-gray-400 italic">No supplies available for deployment.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="xl:col-span-3 flex justify-end gap-4 border-t border-gray-100 pt-6">
                <button type="button" onclick="document.getElementById('deployModal').classList.add('hidden')" class="bg-gray-100 hover:bg-gray-200 text-gray-600 font-bold py-3 px-8 rounded-xl transition">Cancel</button>
                <button name="deploy_items" type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-10 rounded-xl shadow-lg shadow-blue-100 transition">Deploy Items</button>
            </div>
        </form>
    </div>
</div>

<script>
    function toggleDeploymentTargetFields() {
        const targetType = document.getElementById('deployment_target_type').value;
        const roomGroup = document.getElementById('room_target_group');
        const employeeGroup = document.getElementById('employee_target_group');
        const otherGroup = document.getElementById('other_target_group');
        const roomInput = document.getElementById('room_number');
        const employeeInput = document.getElementById('employee_id');
        const otherInput = document.getElementById('target_label');

        roomGroup.classList.toggle('hidden', targetType !== 'Room');
        employeeGroup.classList.toggle('hidden', targetType !== 'Employee');
        otherGroup.classList.toggle('hidden', targetType !== 'Other');

        roomInput.required = targetType === 'Room';
        employeeInput.required = targetType === 'Employee';
        otherInput.required = targetType === 'Other';

        if (targetType !== 'Room') {
            roomInput.value = '';
        }
        if (targetType !== 'Employee') {
            employeeInput.value = '';
        }
        if (targetType !== 'Other') {
            otherInput.value = '';
        }
    }

    toggleDeploymentTargetFields();
</script>

<?php include 'includes/footer.php'; ?>
