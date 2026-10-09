<?php
// issuance.php
require_once __DIR__ . '/includes/auth.php';
enforce_authenticated();
require_once 'config/db.php';
enforce_page_access('issuance.php');

function issuance_phone_details(string $specs): array {
    $aliases = [
        'mobile_number' => ['mobile number', 'phone number', 'mobile', 'msisdn'],
        'plan_name' => ['plan', 'plan name', 'mobile plan'],
        'monthly_limit' => ['monthly limit', 'max limit', 'maximum limit', 'plan limit'],
        'plan_inclusions' => ['plan inclusions', 'inclusions', 'included services'],
        'other_rates' => ['other rates', 'rates', 'excess rates'],
        'date_activated' => ['date activated', 'activation date', 'activated'],
    ];
    $details = array_fill_keys(array_keys($aliases), '');
    foreach (preg_split('/[\r\n;|]+/', $specs) as $line) {
        if (!preg_match('/^\s*([^:=]+)\s*[:=]\s*(.*?)\s*$/', $line, $parts)) {
            continue;
        }
        foreach ($aliases as $key => $names) {
            if (in_array(strtolower(trim($parts[1])), $names, true)) {
                $details[$key] = trim($parts[2]);
                break;
            }
        }
    }
    return $details;
}

$message = '';
$error = '';

// Issue Asset & Supplies
if (isset($_POST['issue_asset'])) {
    enforce_role(['admin', 'staff']);
    csrf_verify_post();
    $asset_id = (int)$_POST['asset_id'];
    $employee_ids = $_POST['employee_ids'] ?? [];
    $supply_ids = $_POST['supply_ids'] ?? [];
    $it_staff_id = auth_current_id();
    $date_issued = date('Y-m-d H:i:s');

    // Normalize + validate the employee list
    $employee_ids = is_array($employee_ids) ? array_values(array_unique(array_map('intval', $employee_ids))) : [];
    $supply_ids = is_array($supply_ids) ? array_values(array_unique(array_map('intval', $supply_ids))) : [];

    if (empty($employee_ids)) {
        $error = "Please select at least one employee.";
    } else {
        $assetCheck = $pdo->prepare("SELECT c.category_name
                                     FROM assets a JOIN categories c ON c.id = a.category_id
                                     WHERE a.id = ? AND a.status = 'Available' LIMIT 1");
        $assetCheck->execute([$asset_id]);
        $selectedCategory = (string)($assetCheck->fetchColumn() ?: '');
        $isKeycard = (bool)preg_match('/key\s*card|keycard|access card/i', $selectedCategory);
        if ($selectedCategory === '') {
            $error = "The selected asset is no longer available. Refresh the page and try again.";
        } elseif ($isKeycard && count($employee_ids) !== 1) {
            $error = "A keycard must be assigned to exactly one employee.";
        } else {
            $keycardLevels = ['Grandmaster', 'Master', 'Emergency', 'Floor Master', 'Room Master'];
            $keycardLevel = '';
            $keycardIssuedOn = null;
            $keycardExpiresOn = null;
            $keycardAccess = null;
            if ($isKeycard) {
                $selectedLevel = trim((string)($_POST['keycard_level'] ?? ''));
                $otherLevel = trim((string)($_POST['keycard_level_other'] ?? ''));
                if ($selectedLevel === 'Other') {
                    $keycardLevel = $otherLevel;
                } elseif (in_array($selectedLevel, $keycardLevels, true)) {
                    $keycardLevel = $selectedLevel;
                }
                if ($keycardLevel === '' || strlen($keycardLevel) > 80) {
                    $error = 'Choose a keycard level or enter a valid custom level.';
                } else {
                    $keycardIssuedOn = date('Y-m-d');
                    $keycardExpiresOn = (new DateTimeImmutable($keycardIssuedOn))->modify('+1 year')->format('Y-m-d');
                    $keycardAccess = 'All Floors';
                }
            }
        }
        if ($error === '') {
        try {
            $pdo->beginTransaction();

            // 1. Insert into accountability_logs
            // user_id stores the primary (first-selected) employee for
            // backward compatibility with anything that still reads it directly.
            $primary_employee_id = $employee_ids[0];
            $stmt = $pdo->prepare("INSERT INTO accountability_logs
                                      (asset_id, user_id, it_staff_id, date_issued, status,
                                       keycard_level, keycard_access, keycard_issued_on, keycard_expires_on)
                                   VALUES (?, ?, ?, ?, 'Active', ?, ?, ?, ?)");
            $stmt->execute([
                $asset_id,
                $primary_employee_id,
                $it_staff_id,
                $date_issued,
                $keycardLevel !== '' ? $keycardLevel : null,
                $keycardAccess,
                $keycardIssuedOn,
                $keycardExpiresOn,
            ]);
            $log_id = $pdo->lastInsertId();

            // 1b. Link every selected employee to this issuance
            $stmtEmp = $pdo->prepare("INSERT INTO accountability_log_employees (log_id, employee_id) VALUES (?, ?)");
            foreach ($employee_ids as $eid) {
                $stmtEmp->execute([$log_id, $eid]);
            }

            // 2. Link Supplies and deduct stock
            if (!empty($supply_ids)) {
                $stmtSupply = $pdo->prepare("INSERT INTO accountability_supplies (log_id, supply_id, quantity) VALUES (?, ?, 1)");
                $stmtStockOut = $pdo->prepare("UPDATE supplies SET quantity = quantity - 1 WHERE id = ?");
                $stmtLogSupply = $pdo->prepare("INSERT INTO supply_logs (supply_id, staff_id, employee_id, action_type, quantity_changed, notes) VALUES (?, ?, ?, 'Stock Out', 1, 'Issued with Asset PAR')");

                foreach ($supply_ids as $sid) {
                    // Check if enough stock
                    $checkStmt = $pdo->prepare("SELECT quantity FROM supplies WHERE id = ?");
                    $checkStmt->execute([$sid]);
                    if ($checkStmt->fetchColumn() <= 0) continue;

                    $stmtSupply->execute([$log_id, $sid]);
                    $stmtStockOut->execute([$sid]);
                    // logged against the primary employee, since supply_logs has one employee_id column
                    $stmtLogSupply->execute([$sid, $it_staff_id, $primary_employee_id]);
                }
            }

            // 3. Update asset status and next service date (current + 90 days)
            $next_service_date = date('Y-m-d', strtotime('+90 days'));
            $stmtUpdate = $pdo->prepare("UPDATE assets SET status = 'Deployed', next_service_date = ? WHERE id = ?");
            $stmtUpdate->execute([$next_service_date, $asset_id]);

            // Log Issuance
            $employee_count = count($employee_ids);
            log_activity($pdo, 'Issue', 'accountability_logs', $log_id, null, "Issued asset ID $asset_id to $employee_count employee(s) (IDs: " . implode(', ', $employee_ids) . ") and selected supplies");

            $pdo->commit();
            $message = "Equipment issued successfully! <a href='generate_pdf.php?log_id=" . urlencode($log_id) . "' target='_blank' class='underline font-black text-blue-700'>Download PDF PAR</a>";
        } catch (PDOException $e) {
            $pdo->rollBack();
            user_error_log('Issue asset failed', $e);
            $error = generic_error();
        }
        }
    }
}

// Clear Accountability (Enhanced with condition check)
if (isset($_POST['clear_accountability'])) {
    enforce_role(['admin', 'staff']);
    csrf_verify_post();
    $log_id = (int)$_POST['log_id'];
    $condition = $_POST['return_condition'];
    $notes = trim($_POST['return_notes']);

    try {
        $pdo->beginTransaction();

        // Get asset_id first
        $stmtLog = $pdo->prepare("SELECT asset_id FROM accountability_logs WHERE id = ?");
        $stmtLog->execute([$log_id]);
        $asset_id = $stmtLog->fetchColumn();

        // Update log
        $stmtClear = $pdo->prepare("UPDATE accountability_logs SET status = 'Cleared', return_date = CURDATE(), return_condition = ?, return_notes = ? WHERE id = ?");
        $stmtClear->execute([$condition, $notes, $log_id]);

        // Determine new asset status
        $new_status = ($condition == 'Good') ? 'Available' : 'Repair';

        // Update asset status
        $stmtAsset = $pdo->prepare("UPDATE assets SET status = ? WHERE id = ?");
        $stmtAsset->execute([$new_status, $asset_id]);

        // Log Return
        log_activity($pdo, 'Return', 'accountability_logs', $log_id, null, "Returned Asset ID $asset_id. Condition: $condition. Notes: $notes");

        $pdo->commit();
        $message = "Accountability cleared. Asset status set to: $new_status.";
    } catch (PDOException $e) {
        $pdo->rollBack();
        user_error_log('Clear accountability failed', $e);
        $error = generic_error();
    }
}

include 'includes/header.php';

// Fetch active assets, employees, and available supplies
$available_assets = $pdo->query("SELECT a.*, c.category_name
                                 FROM assets a
                                 JOIN categories c ON a.category_id = c.id
                                 WHERE a.status = 'Available'
                                   AND (
                                       c.category_name NOT REGEXP 'key[[:space:]]*card|access card'
                                       OR NULLIF(TRIM(a.serial_number), '') IS NOT NULL
                                   )
                                   AND NOT EXISTS (
                                       SELECT 1 FROM accountability_logs l
                                       WHERE l.asset_id = a.id AND l.status = 'Active'
                                   )
                                 ORDER BY a.asset_tag ASC")->fetchAll();
$availableKeycardCount = count(array_filter($available_assets, static function (array $asset): bool {
    return (bool)preg_match('/key\s*card|keycard|access card/i', (string)$asset['category_name']);
}));
$requestedAssetId = isset($_GET['asset_id']) ? (int)$_GET['asset_id'] : 0;
$preselectedKeycard = false;
foreach ($available_assets as $availableAsset) {
    if ((int)$availableAsset['id'] === $requestedAssetId && preg_match('/key\s*card|keycard|access card/i', (string)$availableAsset['category_name'])) {
        $preselectedKeycard = true;
        break;
    }
}
$employees = $pdo->query("SELECT * FROM employees ORDER BY name ASC")->fetchAll();
$available_supplies = $pdo->query("SELECT * FROM supplies WHERE quantity > 0 ORDER BY item_name ASC")->fetchAll();
?>

<div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Accountability & Issuance</h1>
        <p class="text-sm text-gray-500">Manage property acknowledgement receipts (PAR)</p>
    </div>

    <?php if ($availableKeycardCount === 0 && in_array(auth_current_role(), [ROLE_ADMIN, ROLE_STAFF], true)): ?>
    <div class="mb-6 flex flex-col gap-3 rounded-2xl border border-indigo-100 bg-indigo-50/70 p-4 text-sm text-indigo-950 md:flex-row md:items-center md:justify-between">
        <p>No available keycard is registered. Add the physical card to inventory first, then return here to assign it to an employee.</p>
        <a href="keycards.php" class="shrink-0 font-bold text-indigo-800 underline">Open Keycard Management</a>
    </div>
    <?php endif; ?>
    <?php if (in_array(auth_current_role(), [ROLE_ADMIN, ROLE_STAFF], true)): ?>
    <button onclick="document.getElementById('issueModal').classList.remove('hidden')" class="bg-[#1e3a8a] hover:bg-blue-900 text-white font-bold py-2.5 px-6 rounded-xl shadow-sm transition flex items-center">
        <svg class="w-5 h-5 mr-2 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
        New Issuance
    </button>
    <?php endif; ?>
</div>

<?php if ($message): ?>
    <div class="bg-green-50 border-l-4 border-green-500 text-green-700 p-4 rounded-lg mb-6 flex items-center shadow-sm">
        <svg class="w-5 h-5 mr-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
        <span class="text-sm font-medium"><?php echo $message; ?></span>
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
        <h3 class="text-sm font-black text-gray-400 uppercase tracking-widest">Active Accountability Logs</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-100">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-4 text-left text-[10px] font-black text-gray-500 uppercase tracking-widest">Asset</th>
                    <th class="px-6 py-4 text-left text-[10px] font-black text-gray-500 uppercase tracking-widest">Accountable Person(s)</th>
                    <th class="px-6 py-4 text-left text-[10px] font-black text-gray-500 uppercase tracking-widest">Date Issued</th>
                    <th class="px-6 py-4 text-left text-[10px] font-black text-gray-500 uppercase tracking-widest">Status</th>
                    <th class="px-6 py-4 text-right text-[10px] font-black text-gray-500 uppercase tracking-widest">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php
                // Aggregate every employee linked to each log into one row
                $stmt = $pdo->query("SELECT l.*, a.asset_tag, a.brand, a.model,
                                            GROUP_CONCAT(DISTINCT e.name ORDER BY e.name SEPARATOR ', ') AS employee_names,
                                            GROUP_CONCAT(DISTINCT e.department ORDER BY e.department SEPARATOR ', ') AS departments
                                     FROM accountability_logs l
                                     JOIN assets a ON l.asset_id = a.id
                                     JOIN accountability_log_employees ale ON ale.log_id = l.id
                                     JOIN employees e ON ale.employee_id = e.id
                                     WHERE l.status = 'Active'
                                     GROUP BY l.id
                                     ORDER BY l.date_issued DESC");
                $logs = $stmt->fetchAll();

                if ($logs):
                    foreach ($logs as $l):
                ?>
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="p-2 bg-blue-50 text-blue-600 rounded-lg mr-3">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
                                </div>
                                <div>
                                    <p class="text-sm font-bold text-blue-900"><?php echo e($l['asset_tag']); ?></p>
                                    <p class="text-[10px] text-gray-500 uppercase"><?php echo e($l['brand']) . ' ' . e($l['model']); ?></p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <p class="text-sm font-bold text-gray-900"><?php echo e($l['employee_names']); ?></p>
                            <p class="text-[10px] font-black text-gray-400 uppercase tracking-tighter"><?php echo e($l['departments']); ?></p>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 font-medium">
                            <?php echo e(date('M d, Y', strtotime($l['date_issued']))); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2.5 py-1 text-[10px] font-black uppercase rounded-full border border-blue-200 bg-blue-50 text-blue-700">Active</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <div class="flex justify-end space-x-2">
                                <a href="generate_pdf.php?log_id=<?php echo urlencode($l['id']); ?>" target="_blank" class="p-2 text-green-600 hover:bg-green-50 rounded-lg transition" title="Print PAR">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                                </a>
                                <?php if (in_array(auth_current_role(), [ROLE_ADMIN, ROLE_STAFF], true)): ?>
                                <button onclick="openReturnModal(<?php echo e($l['id']); ?>, '<?php echo e($l['asset_tag']); ?>')" class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition" title="Clear Accountability">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php
                    endforeach;
                else:
                ?>
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center">
                                <div class="p-4 bg-gray-50 rounded-full mb-4">
                                    <svg class="w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                </div>
                                <p class="text-gray-500 font-medium italic">No active accountability records found.</p>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Issue Modal -->
<?php if (in_array(auth_current_role(), [ROLE_ADMIN, ROLE_STAFF], true)): ?>
<div id="issueModal" class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm hidden flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-4xl overflow-hidden animate-in fade-in zoom-in duration-200">
        <div class="bg-[#1e3a8a] p-6 text-white flex justify-between items-center">
            <div>
                <h2 class="text-xl font-bold uppercase tracking-tight italic text-yellow-400">New Asset Issuance</h2>
                <p class="text-blue-200 text-xs">Property Acknowledgement Receipt (PAR) &mdash; assign equipment to one or more hotel staff members</p>
            </div>
            <button type="button" onclick="closeIssueModal()" class="bg-white/10 hover:bg-white/20 p-2 rounded-full transition">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>

        <form action="" method="POST" id="issueForm">
            <?php echo csrf_field(); ?>
            <div class="grid grid-cols-1 md:grid-cols-5">
                <!-- Left: selection controls -->
                <div class="md:col-span-3 p-8 space-y-6 max-h-[65vh] overflow-y-auto">

                    <!-- Employees -->
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="block text-gray-700 text-xs font-black uppercase tracking-widest">Select Employee(s) *</label>
                            <span class="text-[10px] font-black uppercase text-blue-600 bg-blue-50 px-2 py-0.5 rounded-full"><span id="empCount">0</span> selected</span>
                        </div>
                        <div class="relative mb-2">
                            <svg class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"></path></svg>
                            <input type="text" oninput="filterList(this,'employeeList')" placeholder="Search employee or department..." class="w-full pl-9 pr-3 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 transition">
                        </div>
                        <div id="employeeList" class="max-h-44 overflow-y-auto p-3 bg-gray-50 border border-gray-100 rounded-xl space-y-1 custom-scrollbar">
                            <?php foreach ($employees as $emp): ?>
                                <label class="flex items-center space-x-3 cursor-pointer group px-2 py-1.5 rounded-lg hover:bg-white transition" data-search="<?php echo e(strtolower($emp['name'] . ' ' . $emp['department'])); ?>">
                                    <input type="checkbox" name="employee_ids[]" value="<?php echo e($emp['id']); ?>" data-name="<?php echo e($emp['name']); ?>" onchange="handleEmployeeSelection(this)" class="w-5 h-5 rounded text-blue-600 focus:ring-blue-500 border-gray-300">
                                    <span class="text-sm text-gray-700 group-hover:text-blue-600 transition">
                                        <?php echo e($emp['name']); ?>
                                        <span class="text-[10px] text-gray-400 font-bold uppercase">(<?php echo e($emp['department']); ?>)</span>
                                    </span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Asset -->
                    <div>
                        <label class="block text-gray-700 text-xs font-black uppercase tracking-widest mb-2">Select Asset *</label>
                        <select name="asset_id" id="assetSelect" onchange="updateSummary()" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 transition" required>
                            <option value="">-- Select Available Asset --</option>
                            <?php foreach ($available_assets as $asset): ?>
                                <?php $phoneDetails = issuance_phone_details((string)($asset['other_specs'] ?? '')); ?>
                                <option value="<?php echo e($asset['id']); ?>" data-category="<?php echo e(strtolower($asset['category_name'])); ?>" <?php echo $preselectedKeycard && (int)$asset['id'] === $requestedAssetId ? 'selected' : ''; ?>
                                    data-phone-number="<?php echo e($phoneDetails['mobile_number']); ?>"
                                    data-phone-plan="<?php echo e($phoneDetails['plan_name']); ?>"
                                    data-phone-limit="<?php echo e($phoneDetails['monthly_limit']); ?>"
                                    data-phone-inclusions="<?php echo e($phoneDetails['plan_inclusions']); ?>"
                                    data-phone-rates="<?php echo e($phoneDetails['other_rates']); ?>"
                                    data-phone-activated="<?php echo e($phoneDetails['date_activated']); ?>">
                                    <?php echo e($asset['asset_tag']); ?> &mdash; <?php echo e($asset['brand']); ?> <?php echo e($asset['model']); ?> (<?php echo e($asset['category_name']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div id="phoneAssetDetails" class="hidden rounded-xl border border-sky-100 bg-sky-50/70 p-4">
                        <h4 class="text-xs font-black uppercase tracking-widest text-sky-900">Phone issuance details from inventory</h4>
                        <dl class="mt-3 grid grid-cols-1 gap-x-4 gap-y-2 text-xs sm:grid-cols-2">
                            <div><dt class="font-bold text-sky-800">Mobile number</dt><dd id="phoneSummaryNumber" class="text-gray-700">Not set</dd></div>
                            <div><dt class="font-bold text-sky-800">Plan</dt><dd id="phoneSummaryPlan" class="text-gray-700">Not set</dd></div>
                            <div><dt class="font-bold text-sky-800">Monthly limit</dt><dd id="phoneSummaryLimit" class="text-gray-700">Not set</dd></div>
                            <div><dt class="font-bold text-sky-800">Date activated</dt><dd id="phoneSummaryActivated" class="text-gray-700">Not set</dd></div>
                            <div><dt class="font-bold text-sky-800">Plan inclusions</dt><dd id="phoneSummaryInclusions" class="text-gray-700">Not set</dd></div>
                            <div><dt class="font-bold text-sky-800">Other rates</dt><dd id="phoneSummaryRates" class="text-gray-700">Not set</dd></div>
                        </dl>
                    </div>

                    <div id="keycardFields" class="hidden rounded-xl border border-indigo-100 bg-indigo-50/70 p-4 space-y-3">
                        <div>
                            <h4 class="text-xs font-black uppercase tracking-widest text-indigo-900">Keycard assignment</h4>
                            <p class="mt-1 text-xs text-indigo-800">One card is assigned to one employee. Access is set to all floors and expires one year after assignment.</p>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label for="keycardLevel" class="mb-1 block text-xs font-bold text-gray-700">Keycard level *</label>
                                <select name="keycard_level" id="keycardLevel" class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm">
                                    <option value="">Select level</option>
                                    <option>Grandmaster</option>
                                    <option>Master</option>
                                    <option>Emergency</option>
                                    <option>Floor Master</option>
                                    <option>Room Master</option>
                                    <option value="Other">Other (enter level)</option>
                                </select>
                            </div>
                            <div id="keycardOtherLevelWrap" class="hidden">
                                <label for="keycardLevelOther" class="mb-1 block text-xs font-bold text-gray-700">Custom level *</label>
                                <input type="text" name="keycard_level_other" id="keycardLevelOther" maxlength="80" class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm" placeholder="Enter keycard level">
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-bold text-gray-700">Access</label>
                                <input id="keycardAccess" type="text" value="All Floors" readonly class="w-full rounded-lg border border-gray-200 bg-gray-100 px-3 py-2 text-sm font-semibold text-gray-700">
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-bold text-gray-700">Assignment date</label>
                                <input id="keycardIssuedDate" type="text" readonly class="w-full rounded-lg border border-gray-200 bg-gray-100 px-3 py-2 text-sm text-gray-600">
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-bold text-gray-700">Expiry date (one year)</label>
                                <input id="keycardExpiryDate" type="text" readonly class="w-full rounded-lg border border-gray-200 bg-gray-100 px-3 py-2 text-sm text-gray-600">
                            </div>
                        </div>
                    </div>

                    <!-- Supplies -->
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="block text-gray-700 text-xs font-black uppercase tracking-widest">Include Supplies (Optional)</label>
                            <span class="text-[10px] font-black uppercase text-gray-500 bg-gray-100 px-2 py-0.5 rounded-full"><span id="supCount">0</span> added</span>
                        </div>
                        <div class="relative mb-2">
                            <svg class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"></path></svg>
                            <input type="text" oninput="filterList(this,'supplyList')" placeholder="Search supplies..." class="w-full pl-9 pr-3 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 transition">
                        </div>
                        <div id="supplyList" class="max-h-40 overflow-y-auto p-3 bg-gray-50 border border-gray-100 rounded-xl space-y-1 custom-scrollbar">
                            <?php foreach ($available_supplies as $sup): ?>
                                <label class="flex items-center space-x-3 cursor-pointer group px-2 py-1.5 rounded-lg hover:bg-white transition" data-search="<?php echo e(strtolower($sup['item_name'])); ?>">
                                    <input type="checkbox" name="supply_ids[]" value="<?php echo e($sup['id']); ?>" data-name="<?php echo e($sup['item_name']); ?>" onchange="updateSummary()" class="w-5 h-5 rounded text-blue-600 focus:ring-blue-500 border-gray-300">
                                    <span class="text-sm text-gray-700 group-hover:text-blue-600 transition">
                                        <?php echo e($sup['item_name']); ?>
                                        <span class="text-[10px] text-gray-400 font-bold uppercase">(Stock: <?php echo e($sup['quantity']); ?>)</span>
                                    </span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Right: live summary -->
                <div class="md:col-span-2 bg-gray-50 border-t md:border-t-0 md:border-l border-gray-100 p-8 flex flex-col">
                    <h3 class="text-xs font-black text-gray-400 uppercase tracking-widest mb-4">Issuance Summary</h3>

                    <div class="mb-5">
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Accountable To</p>
                        <div id="empChips" class="flex flex-wrap gap-1.5">
                            <span class="text-xs text-gray-400 italic">No employees selected yet</span>
                        </div>
                    </div>

                    <div class="mb-5">
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Asset</p>
                        <p id="assetSummary" class="text-sm font-bold text-gray-400 italic">None selected</p>
                    </div>

                    <div class="mb-5">
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Supplies Included</p>
                        <ul id="supList" class="text-sm text-gray-700 space-y-1">
                            <li class="text-xs text-gray-400 italic list-none">None added</li>
                        </ul>
                    </div>

                    <div class="mt-auto pt-4 border-t border-gray-200 flex items-start space-x-2">
                        <svg class="w-4 h-4 text-yellow-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l6.518 11.598c.75 1.335-.213 2.987-1.743 2.987H3.482c-1.53 0-2.493-1.652-1.743-2.987L8.257 3.1zM11 14a1 1 0 11-2 0 1 1 0 012 0zm-1-3a1 1 0 01-1-1V7a1 1 0 112 0v3a1 1 0 01-1 1z" clip-rule="evenodd"></path></svg>
                        <p class="text-[11px] text-gray-500 leading-snug">A single PAR will be generated listing every employee selected as jointly accountable for this asset.</p>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end space-x-4 px-8 py-6 border-t border-gray-100 bg-white">
                <button type="button" onclick="closeIssueModal()" class="bg-gray-100 hover:bg-gray-200 text-gray-600 font-bold py-3 px-8 rounded-xl transition">Cancel</button>
                <button name="issue_asset" id="submitIssueBtn" class="bg-[#1e3a8a] hover:bg-blue-900 text-white font-bold py-3 px-10 rounded-xl shadow-lg shadow-blue-200 transition disabled:opacity-40 disabled:cursor-not-allowed disabled:shadow-none" type="submit" disabled>Complete Issuance</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- Return Asset Modal -->
<?php if (in_array(auth_current_role(), [ROLE_ADMIN, ROLE_STAFF], true)): ?>
<div id="returnModal" class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm hidden flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-md overflow-hidden animate-in fade-in zoom-in duration-200">
        <div class="bg-red-600 p-6 text-white flex justify-between items-center">
            <div>
                <h2 class="text-xl font-bold uppercase tracking-tight italic" id="returnTitle">Return Equipment</h2>
                <p class="text-red-100 text-xs">Complete return inspection</p>
            </div>
            <button onclick="document.getElementById('returnModal').classList.add('hidden')" class="bg-white/10 hover:bg-white/20 p-2 rounded-full transition">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
        <form action="" method="POST" class="p-8 space-y-4">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="log_id" id="return_log_id">
            <div>
                <label class="block text-gray-700 text-xs font-black uppercase tracking-widest mb-2">Return Condition *</label>
                <select name="return_condition" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 transition" required>
                    <option value="Good">Good (Ready for Re-issue)</option>
                    <option value="Needs Repair">Needs Repair (Move to Maintenance)</option>
                    <option value="Damaged">Damaged / Beyond Repair</option>
                </select>
            </div>
            <div>
                <label class="block text-gray-700 text-xs font-black uppercase tracking-widest mb-2">Inspection Notes</label>
                <textarea name="return_notes" rows="3" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 transition" placeholder="Describe any physical damage or software issues..."></textarea>
            </div>
            <div class="flex items-center justify-end space-x-4 pt-6 border-t border-gray-100">
                <button type="button" onclick="document.getElementById('returnModal').classList.add('hidden')" class="bg-gray-100 hover:bg-gray-200 text-gray-600 font-bold py-3 px-8 rounded-xl transition">Cancel</button>
                <button name="clear_accountability" class="bg-red-600 hover:bg-red-700 text-white font-bold py-3 px-10 rounded-xl shadow-lg shadow-red-200 transition" type="submit">Complete Return</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<script>
    function openReturnModal(logId, assetTag) {
        document.getElementById('return_log_id').value = logId;
        document.getElementById('returnTitle').innerText = 'Return: ' + assetTag;
        document.getElementById('returnModal').classList.remove('hidden');
    }

    function closeIssueModal() {
        document.getElementById('issueModal').classList.add('hidden');
    }

    // Live filter for the employee / supply search boxes
    function filterList(inputEl, listId) {
        const q = inputEl.value.trim().toLowerCase();
        document.querySelectorAll('#' + listId + ' > label').forEach(function (label) {
            label.style.display = label.dataset.search.includes(q) ? 'flex' : 'none';
        });
    }

    // Uncheck an employee from the chip's "x" button
    function uncheckEmployee(id) {
        const box = document.querySelector('input[name="employee_ids[]"][value="' + id + '"]');
        if (box) { box.checked = false; updateSummary(); }
    }

    function isKeycardAssetSelected() {
        const assetSelect = document.getElementById('assetSelect');
        const category = assetSelect.options[assetSelect.selectedIndex]?.dataset.category || '';
        return /key\s*card|keycard|access card/i.test(category);
    }

    function isPhoneAssetSelected() {
        const assetSelect = document.getElementById('assetSelect');
        const category = assetSelect.options[assetSelect.selectedIndex]?.dataset.category || '';
        return /phone|mobile/i.test(category);
    }

    function handleEmployeeSelection(selectedBox) {
        if (isKeycardAssetSelected() && selectedBox.checked) {
            document.querySelectorAll('input[name="employee_ids[]"]:checked').forEach(function (box) {
                if (box !== selectedBox) box.checked = false;
            });
        }
        updateSummary();
    }

    function updateKeycardDates() {
        const today = new Date('<?php echo date('Y-m-d'); ?>T00:00:00');
        const dateText = function (date) {
            return String(date.getMonth() + 1).padStart(2, '0') + '/' +
                String(date.getDate()).padStart(2, '0') + '/' + date.getFullYear();
        };
        document.getElementById('keycardIssuedDate').value = dateText(today);
        document.getElementById('keycardExpiryDate').value = dateText(
            new Date(today.getFullYear() + 1, today.getMonth(), today.getDate())
        );
    }

    // Rebuild the live "Issuance Summary" panel and toggle submit availability
    function updateSummary() {
        const isKeycard = isKeycardAssetSelected();
        const keycardFields = document.getElementById('keycardFields');
        const keycardLevel = document.getElementById('keycardLevel');
        const otherLevelWrap = document.getElementById('keycardOtherLevelWrap');
        const otherLevel = document.getElementById('keycardLevelOther');
        keycardFields.classList.toggle('hidden', !isKeycard);
        keycardLevel.required = isKeycard;
        otherLevelWrap.classList.toggle('hidden', !isKeycard || keycardLevel.value !== 'Other');
        otherLevel.required = isKeycard && keycardLevel.value === 'Other';
        if (isKeycard) updateKeycardDates();

        // Employees
        const allEmployeeBoxes = Array.from(document.querySelectorAll('input[name="employee_ids[]"]'));
        let empBoxes = allEmployeeBoxes.filter(function (box) { return box.checked; });
        if (isKeycard && empBoxes.length > 1) {
            empBoxes.slice(1).forEach(function (box) { box.checked = false; });
            empBoxes = empBoxes.slice(0, 1);
        }
        allEmployeeBoxes.forEach(function (box) {
            box.disabled = isKeycard && empBoxes.length === 1 && !box.checked;
        });
        document.getElementById('empCount').textContent = empBoxes.length;
        const chipsEl = document.getElementById('empChips');
        chipsEl.innerHTML = '';
        if (empBoxes.length === 0) {
            chipsEl.innerHTML = '<span class="text-xs text-gray-400 italic">No employees selected yet</span>';
        } else {
            empBoxes.forEach(function (box) {
                const chip = document.createElement('span');
                chip.className = 'inline-flex items-center bg-blue-50 text-blue-800 text-[11px] font-bold pl-2.5 pr-1.5 py-1 rounded-full border border-blue-100';
                chip.textContent = box.dataset.name;
                const removeBtn = document.createElement('button');
                removeBtn.type = 'button';
                removeBtn.className = 'ml-1.5 text-blue-400 hover:text-blue-800 font-black leading-none';
                removeBtn.textContent = '\u00d7';
                removeBtn.onclick = function () { uncheckEmployee(box.value); };
                chip.appendChild(removeBtn);
                chipsEl.appendChild(chip);
            });
        }

        // Asset
        const assetSel = document.getElementById('assetSelect');
        const assetSummaryEl = document.getElementById('assetSummary');
        const phoneDetailsPanel = document.getElementById('phoneAssetDetails');
        const selectedAssetOption = assetSel.options[assetSel.selectedIndex];
        phoneDetailsPanel.classList.toggle('hidden', !isPhoneAssetSelected());
        if (isPhoneAssetSelected()) {
            const phoneSummary = {
                phoneSummaryNumber: selectedAssetOption.dataset.phoneNumber,
                phoneSummaryPlan: selectedAssetOption.dataset.phonePlan,
                phoneSummaryLimit: selectedAssetOption.dataset.phoneLimit,
                phoneSummaryActivated: selectedAssetOption.dataset.phoneActivated,
                phoneSummaryInclusions: selectedAssetOption.dataset.phoneInclusions,
                phoneSummaryRates: selectedAssetOption.dataset.phoneRates
            };
            Object.keys(phoneSummary).forEach(function (id) {
                document.getElementById(id).textContent = phoneSummary[id] || 'Not set';
            });
        }
        if (assetSel.value) {
            assetSummaryEl.textContent = assetSel.options[assetSel.selectedIndex].text;
            assetSummaryEl.classList.remove('italic', 'text-gray-400');
            assetSummaryEl.classList.add('text-gray-800');
        } else {
            assetSummaryEl.textContent = 'None selected';
            assetSummaryEl.classList.add('italic', 'text-gray-400');
            assetSummaryEl.classList.remove('text-gray-800');
        }

        // Supplies
        const supBoxes = Array.from(document.querySelectorAll('input[name="supply_ids[]"]:checked'));
        document.getElementById('supCount').textContent = supBoxes.length;
        const supListEl = document.getElementById('supList');
        supListEl.innerHTML = '';
        if (supBoxes.length === 0) {
            supListEl.innerHTML = '<li class="text-xs text-gray-400 italic list-none">None added</li>';
        } else {
            supBoxes.forEach(function (box) {
                const li = document.createElement('li');
                li.className = 'flex items-center text-sm text-gray-700';
                li.innerHTML = '<span class="w-1.5 h-1.5 rounded-full bg-blue-400 mr-2 flex-shrink-0"></span>' + box.dataset.name;
                supListEl.appendChild(li);
            });
        }

        // A keycard requires one recipient and a selected access level.
        const cardDetailsValid = !isKeycard ||
            (empBoxes.length === 1 && keycardLevel.value !== '' &&
                (keycardLevel.value !== 'Other' || otherLevel.value.trim() !== ''));
        document.getElementById('submitIssueBtn').disabled = !(empBoxes.length > 0 && assetSel.value && cardDetailsValid);
    }

    document.getElementById('keycardLevel').addEventListener('change', updateSummary);
    document.getElementById('keycardLevelOther').addEventListener('input', updateSummary);
    document.getElementById('assetSelect').addEventListener('change', updateSummary);
    updateSummary();
    <?php if ($preselectedKeycard && in_array(auth_current_role(), [ROLE_ADMIN, ROLE_STAFF], true)): ?>
    document.getElementById('issueModal').classList.remove('hidden');
    <?php endif; ?>
</script>

<?php include 'includes/footer.php'; ?>