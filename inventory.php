<?php
require_once 'includes/auth.php';
require_once 'config/db.php';
enforce_authenticated();
enforce_page_access('inventory.php');

$message = '';
$error = '';

function normalize_optional_text($value) {
    $value = trim((string)$value);
    return $value === '' ? null : $value;
}

function inventory_remove_uploaded_file(?string $path): void {
    if (!$path) {
        return;
    }

    $uploadRoot = realpath(__DIR__ . '/uploads');
    $resolvedPath = realpath(__DIR__ . '/' . ltrim($path, '/\\'));
    if (!$uploadRoot || !$resolvedPath) {
        return;
    }

    $uploadPrefix = $uploadRoot . DIRECTORY_SEPARATOR;
    if (stripos($resolvedPath, $uploadPrefix) === 0 && is_file($resolvedPath) && !unlink($resolvedPath)) {
        error_log('Deleted asset upload could not be removed: ' . $resolvedPath);
    }
}

function inventory_phone_details(string $specs): array {
    $labels = [
        'mobile_number' => ['mobile number', 'phone number', 'mobile', 'msisdn'],
        'plan_name' => ['plan', 'plan name', 'mobile plan'],
        'monthly_limit' => ['monthly limit', 'max limit', 'maximum limit', 'plan limit'],
        'plan_inclusions' => ['plan inclusions', 'inclusions', 'included services'],
        'other_rates' => ['other rates', 'rates', 'excess rates'],
        'date_activated' => ['date activated', 'activation date', 'activated'],
    ];
    $details = array_fill_keys(array_keys($labels), '');
    foreach (preg_split('/[\r\n;|]+/', $specs) as $line) {
        if (!preg_match('/^\s*([^:=]+)\s*[:=]\s*(.*?)\s*$/', $line, $parts)) {
            continue;
        }
        foreach ($labels as $key => $aliases) {
            if (in_array(strtolower(trim($parts[1])), $aliases, true)) {
                $details[$key] = trim($parts[2]);
                break;
            }
        }
    }
    return $details;
}

function inventory_build_phone_specs(array $input, ?string $notes): ?string {
    $fields = [
        'Mobile Number' => ['phone_mobile_number', 40],
        'Plan' => ['phone_plan_name', 120],
        'Monthly Limit' => ['phone_monthly_limit', 80],
        'Plan Inclusions' => ['phone_plan_inclusions', 500],
        'Other Rates' => ['phone_other_rates', 500],
        'Date Activated' => ['phone_date_activated', 40],
    ];
    $lines = [];
    foreach ($fields as $label => [$key, $maxLength]) {
        $value = trim((string)($input[$key] ?? ''));
        if ($value !== '') {
            $lines[] = $label . ': ' . substr($value, 0, $maxLength);
        }
    }
    if ($notes !== null) {
        $lines[] = $notes;
    }
    $result = trim(implode("\n", $lines));
    return $result === '' ? null : $result;
}

function get_category_rules(PDO $pdo, int $category_id): array {
    $stmt = $pdo->prepare("SELECT requires_serial_number, show_technical_specs, show_other_specs FROM categories WHERE id = ?");
    $stmt->execute([$category_id]);
    $rules = $stmt->fetch();

    return [
        'requires_serial_number' => isset($rules['requires_serial_number']) ? (int)$rules['requires_serial_number'] : 1,
        'show_technical_specs' => isset($rules['show_technical_specs']) ? (int)$rules['show_technical_specs'] : 1,
        'show_other_specs' => isset($rules['show_other_specs']) ? (int)$rules['show_other_specs'] : 1,
    ];
}

if (isset($_POST['add_asset'])) {
    enforce_role(['admin', 'staff']);
    csrf_verify_post();
    $category_id = $_POST['category_id'];
    $brand = normalize_optional_text($_POST['brand'] ?? '');
    $model = normalize_optional_text($_POST['model'] ?? '');
    $serial_number = normalize_optional_text($_POST['serial_number'] ?? '');
    $processor = normalize_optional_text($_POST['processor'] ?? '');
    $ram = normalize_optional_text($_POST['ram'] ?? '');
    $storage = normalize_optional_text($_POST['storage'] ?? '');
    $other_specs = normalize_optional_text($_POST['other_specs'] ?? '');
    $asset_notes = normalize_optional_text($_POST['asset_notes'] ?? $_POST['other_specs'] ?? '');
    $vendor_id = $_POST['vendor_id'];
    $purchase_date = normalize_optional_text($_POST['purchase_date'] ?? '');
    $warranty_expiry = normalize_optional_text($_POST['warranty_expiry'] ?? '');

    try {
        $pdo->beginTransaction();

        if ($category_id === 'new') {
            $new_cat = trim($_POST['new_category_name']);
            $new_prefix = strtoupper(substr($new_cat, 0, 3));
            $new_requires_serial_number = isset($_POST['new_requires_serial_number']) ? 1 : 0;
            $new_show_technical_specs = isset($_POST['new_show_technical_specs']) ? 1 : 0;
            $new_show_other_specs = isset($_POST['new_show_other_specs']) ? 1 : 0;
            $stmtCat = $pdo->prepare("INSERT INTO categories (category_name, prefix, requires_serial_number, show_technical_specs, show_other_specs) VALUES (?, ?, ?, ?, ?)");
            $stmtCat->execute([$new_cat, $new_prefix, $new_requires_serial_number, $new_show_technical_specs, $new_show_other_specs]);
            $category_id = $pdo->lastInsertId();
        }

        $category_rules = get_category_rules($pdo, (int)$category_id);
        $categoryNameQuery = $pdo->prepare("SELECT category_name FROM categories WHERE id = ?");
        $categoryNameQuery->execute([(int)$category_id]);
        $category_name = (string)$categoryNameQuery->fetchColumn();
        if (preg_match('/phone|mobile/i', $category_name)) {
            $other_specs = inventory_build_phone_specs($_POST, $asset_notes);
        } elseif ($asset_notes !== null) {
            $other_specs = $asset_notes;
        }

        if ($vendor_id === 'new') {
            $new_ven = trim($_POST['new_vendor_name']);
            $stmtVen = $pdo->prepare("INSERT INTO vendors (vendor_name) VALUES (?)");
            $stmtVen->execute([$new_ven]);
            $vendor_id = $pdo->lastInsertId();
        }

        $year = date('Y');

        $stmtPrefix = $pdo->prepare("SELECT prefix FROM categories WHERE id = ? FOR UPDATE");
        $stmtPrefix->execute([(int)$category_id]);
        $prefix = $stmtPrefix->fetchColumn();
        if (!$prefix) {
            throw new Exception("Selected category is missing an asset tag prefix.");
        }

        $stmtCount = $pdo->prepare("SELECT COALESCE(MAX(CAST(SUBSTRING_INDEX(asset_tag, '-', -1) AS UNSIGNED)), 0)
                                    FROM assets WHERE category_id = ? AND asset_tag LIKE ?");
        $tagPattern = "$prefix-$year-%";
        $stmtCount->execute([(int)$category_id, $tagPattern]);
        $count = $stmtCount->fetchColumn();
        $nextId = str_pad((int)$count + 1, 3, '0', STR_PAD_LEFT);
        $asset_tag = "$prefix-$year-$nextId";

        $base_date = !empty($purchase_date) ? $purchase_date : date('Y-m-d');
        $next_service_date = date('Y-m-d', strtotime($base_date . ' + 90 days'));

        $is_keycard_category = (bool)preg_match('/key\s*card|keycard|access card/i', $category_name);
        if (($category_rules['requires_serial_number'] || $is_keycard_category) && empty($serial_number)) {
            throw new Exception($is_keycard_category ? "A keycard needs its card ID." : "This category requires a serial number.");
        }

        if (!$category_rules['show_technical_specs']) {
            $processor = null;
            $ram = null;
            $storage = null;
        }

        if (!$category_rules['show_other_specs'] && !preg_match('/phone|mobile/i', $category_name)) {
            $other_specs = null;
        }

        $photo_path = null;
        if (isset($_FILES['equipment_photo']) && $_FILES['equipment_photo']['error'] === 0) {
            $upload_dir = 'uploads/assets/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            $max_size = 5 * 1024 * 1024;
            if ((int)$_FILES['equipment_photo']['size'] > $max_size) {
                throw new Exception("Equipment photo is too large. Max 5MB.");
            }

            $extension = strtolower(pathinfo($_FILES['equipment_photo']['name'], PATHINFO_EXTENSION));
            $allowed_ext = ['jpg', 'jpeg', 'png', 'webp'];
            if (!in_array($extension, $allowed_ext, true)) {
                throw new Exception("Invalid equipment photo type. Allowed: JPG, PNG, WEBP.");
            }

            if (!getimagesize($_FILES['equipment_photo']['tmp_name'])) {
                throw new Exception("Invalid equipment photo file.");
            }

            $filename = $asset_tag . '_' . time() . '.' . $extension;
            $target_file = $upload_dir . $filename;

            if (move_uploaded_file($_FILES['equipment_photo']['tmp_name'], $target_file)) {
                $photo_path = $target_file;
            }
        }

        $stmt = $pdo->prepare("INSERT INTO assets (asset_tag, category_id, brand, model, serial_number, processor, ram, storage, other_specs, vendor_id, purchase_date, warranty_expiry, next_service_date, status, photo_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Available', ?)");
        $stmt->execute([$asset_tag, (int)$category_id, $brand, $model, $serial_number, $processor, $ram, $storage, $other_specs, (int)$vendor_id, $purchase_date, $warranty_expiry, $next_service_date, $photo_path]);
        $asset_id = $pdo->lastInsertId();

        log_activity($pdo, 'Create', 'assets', $asset_id, null, "Added new asset: $asset_tag ($brand $model)");

        $pdo->commit();
        $message = "Asset added successfully with Tag: $asset_tag";
    } catch (Exception $e) {
        $pdo->rollBack();
        user_error_log('Add asset failed', $e);
        $error = generic_error();
    }
}

if (isset($_POST['edit_asset'])) {
    enforce_role(['admin', 'staff']);
    csrf_verify_post();
    $id = (int)$_POST['asset_id'];
    $brand = normalize_optional_text($_POST['brand'] ?? '');
    $model = normalize_optional_text($_POST['model'] ?? '');
    $serial_number = normalize_optional_text($_POST['serial_number'] ?? '');
    $processor = normalize_optional_text($_POST['processor'] ?? '');
    $ram = normalize_optional_text($_POST['ram'] ?? '');
    $storage = normalize_optional_text($_POST['storage'] ?? '');
    $other_specs = normalize_optional_text($_POST['other_specs'] ?? '');
    $asset_notes = normalize_optional_text($_POST['asset_notes'] ?? $_POST['other_specs'] ?? '');
    $purchase_date = normalize_optional_text($_POST['purchase_date'] ?? '');
    $warranty_expiry = normalize_optional_text($_POST['warranty_expiry'] ?? '');
    $status = $_POST['status'];

    try {
        $pdo->beginTransaction();

        $stmtAssetRules = $pdo->prepare("SELECT c.category_name, c.requires_serial_number, c.show_technical_specs, c.show_other_specs FROM assets a LEFT JOIN categories c ON a.category_id = c.id WHERE a.id = ?");
        $stmtAssetRules->execute([$id]);
        $category_rules = $stmtAssetRules->fetch();
        $category_name = (string)($category_rules['category_name'] ?? '');
        $requires_serial_number = isset($category_rules['requires_serial_number']) ? (int)$category_rules['requires_serial_number'] : 1;
        $show_technical_specs = isset($category_rules['show_technical_specs']) ? (int)$category_rules['show_technical_specs'] : 1;
        $show_other_specs = isset($category_rules['show_other_specs']) ? (int)$category_rules['show_other_specs'] : 1;
        if (preg_match('/phone|mobile/i', $category_name)) {
            $other_specs = inventory_build_phone_specs($_POST, $asset_notes);
        } elseif ($asset_notes !== null) {
            $other_specs = $asset_notes;
        }

        $is_keycard_category = (bool)preg_match('/key\s*card|keycard|access card/i', $category_name);
        if (($requires_serial_number || $is_keycard_category) && empty($serial_number)) {
            throw new Exception($is_keycard_category ? "A keycard needs its card ID." : "This category requires a serial number.");
        }

        if (!$show_technical_specs) {
            $processor = null;
            $ram = null;
            $storage = null;
        }

        if (!$show_other_specs && !preg_match('/phone|mobile/i', $category_name)) {
            $other_specs = null;
        }

        $photo_sql = "";
        $params = [$brand, $model, $serial_number, $processor, $ram, $storage, $other_specs, $purchase_date, $warranty_expiry, $status];

        if (isset($_FILES['equipment_photo']) && $_FILES['equipment_photo']['error'] === 0) {
            $upload_dir = 'uploads/assets/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            $stmtTag = $pdo->prepare("SELECT asset_tag, photo_path FROM assets WHERE id = ?");
            $stmtTag->execute([$id]);
            $asset_row = $stmtTag->fetch();
            $asset_tag = $asset_row['asset_tag'] ?? null;
            $old_photo_path = $asset_row['photo_path'] ?? null;
            if (!$asset_tag) {
                throw new Exception("Asset not found.");
            }

            $max_size = 5 * 1024 * 1024;
            if ((int)$_FILES['equipment_photo']['size'] > $max_size) {
                throw new Exception("Equipment photo is too large. Max 5MB.");
            }

            $extension = strtolower(pathinfo($_FILES['equipment_photo']['name'], PATHINFO_EXTENSION));
            $allowed_ext = ['jpg', 'jpeg', 'png', 'webp'];
            if (!in_array($extension, $allowed_ext, true)) {
                throw new Exception("Invalid equipment photo type. Allowed: JPG, PNG, WEBP.");
            }

            if (!getimagesize($_FILES['equipment_photo']['tmp_name'])) {
                throw new Exception("Invalid equipment photo file.");
            }

            $filename = $asset_tag . '_' . time() . '.' . $extension;
            $target_file = $upload_dir . $filename;

            if (move_uploaded_file($_FILES['equipment_photo']['tmp_name'], $target_file)) {
                $photo_sql = ", photo_path = ?";
                $params[] = $target_file;
                if ($old_photo_path && $old_photo_path !== $target_file && file_exists($old_photo_path)) {
                    unlink($old_photo_path);
                }
            }
        }

        $params[] = $id;
        $stmt = $pdo->prepare("UPDATE assets SET brand = ?, model = ?, serial_number = ?, processor = ?, ram = ?, storage = ?, other_specs = ?, purchase_date = ?, warranty_expiry = ?, status = ? $photo_sql WHERE id = ?");
        $stmt->execute($params);

        log_activity($pdo, 'Update', 'assets', $id, null, "Updated asset details for $serial_number");
        $pdo->commit();
        $message = "Asset updated successfully.";
    } catch (Exception $e) {
        $pdo->rollBack();
        user_error_log('Edit asset failed', $e);
        $error = generic_error();
    }
}

if (isset($_POST['delete_asset'])) {
    enforce_role(['admin', 'staff']);
    csrf_verify_post();
    $id = (int)$_POST['asset_id'];
    try {
        $pdo->beginTransaction();

        $stmtCheck = $pdo->prepare("SELECT asset_tag, brand, model, status, photo_path FROM assets WHERE id = ?");
        $stmtCheck->execute([$id]);
        $asset = $stmtCheck->fetch();

        if (!$asset) {
            throw new Exception("Asset not found.");
        }

        if ($asset['status'] === 'Deployed') {
            throw new Exception("Cannot delete a deployed asset. Please return it first.");
        }

        $activeIssuanceCheck = $pdo->prepare("SELECT COUNT(*) FROM accountability_logs WHERE asset_id = ? AND status = 'Active'");
        $activeIssuanceCheck->execute([$id]);
        if ((int)$activeIssuanceCheck->fetchColumn() > 0) {
            throw new Exception("Cannot delete an asset with an active issuance. Return it first.");
        }

        $referenceChecks = [
            'issuance' => 'SELECT COUNT(*) FROM accountability_logs WHERE asset_id = ?',
            'maintenance' => 'SELECT COUNT(*) FROM maintenance_logs WHERE asset_id = ?',
            'disposal' => 'SELECT COUNT(*) FROM asset_disposal WHERE asset_id = ?',
        ];
        $references = [];
        foreach ($referenceChecks as $label => $sql) {
            $check = $pdo->prepare($sql);
            $check->execute([$id]);
            $references[$label] = (int)$check->fetchColumn();
        }
        $linkedRecords = array_sum($references);
        $allowHistoryDeletion = false;
        if ($linkedRecords > 0) {
            $settingCheck = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = ?");
            $settingCheck->execute(['allow_delete_assets_with_history']);
            $allowHistoryDeletion = $settingCheck->fetchColumn() === '1';
        }
        if ($linkedRecords > 0 && !$allowHistoryDeletion) {
            $reasons = [];
            foreach ($references as $label => $count) {
                if ($count > 0) {
                    $reasons[] = $count . ' ' . $label . ' record(s)';
                }
            }
            $statusExplanation = $asset['status'] === 'Available'
                ? ' Available only means it is not currently deployed.'
                : '';
            $issuanceExplanation = $references['issuance'] > 0
                ? ' Its accountability history is still linked to this asset (MySQL error #1451).'
                : '';
            throw new Exception(
                'This asset cannot be deleted because it has protected history (' . implode(', ', $reasons) . ').'
                . $statusExplanation . $issuanceExplanation
                . ' Retire the asset instead to preserve its records.'
            );
        }

        $removedUploadPaths = [$asset['photo_path']];
        if ($allowHistoryDeletion && $references['issuance'] > 0) {
            $stmtLogFiles = $pdo->prepare("SELECT signature_path, scan_file_path FROM accountability_logs WHERE asset_id = ?");
            $stmtLogFiles->execute([$id]);
            foreach ($stmtLogFiles->fetchAll(PDO::FETCH_ASSOC) as $logFiles) {
                $removedUploadPaths[] = $logFiles['signature_path'];
                $removedUploadPaths[] = $logFiles['scan_file_path'];
            }

            $pdo->prepare("DELETE FROM accountability_log_employees WHERE log_id IN (SELECT id FROM accountability_logs WHERE asset_id = ?)")->execute([$id]);
            $pdo->prepare("DELETE FROM accountability_supplies WHERE log_id IN (SELECT id FROM accountability_logs WHERE asset_id = ?)")->execute([$id]);
            $pdo->prepare("DELETE FROM accountability_logs WHERE asset_id = ?")->execute([$id]);
        }
        if ($allowHistoryDeletion && $references['maintenance'] > 0) {
            $pdo->prepare("DELETE FROM maintenance_logs WHERE asset_id = ?")->execute([$id]);
        }
        if ($allowHistoryDeletion && $references['disposal'] > 0) {
            $pdo->prepare("DELETE FROM asset_disposal WHERE asset_id = ?")->execute([$id]);
        }

        $stmt = $pdo->prepare("DELETE FROM assets WHERE id = ?");
        $stmt->execute([$id]);

        log_activity($pdo, 'Delete', 'assets', $id, null, "Deleted asset: " . $asset['asset_tag'] . " (" . $asset['brand'] . " " . $asset['model'] . ")");

        $pdo->commit();
        foreach (array_unique(array_filter($removedUploadPaths)) as $uploadPath) {
            inventory_remove_uploaded_file((string)$uploadPath);
        }
        $message = "Asset deleted successfully.";
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        user_error_log('Delete asset failed', $e);
        $error = $e instanceof PDOException
            ? 'This asset could not be deleted because MySQL reports a linked record (error #1451). Retire the asset instead to preserve its history.'
            : $e->getMessage();
    }
}

include 'includes/header.php';

$categories = $pdo->query("SELECT * FROM categories ORDER BY category_name ASC")->fetchAll();
$vendors = $pdo->query("SELECT * FROM vendors ORDER BY vendor_name ASC")->fetchAll();
?>

<div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Inventory Management</h1>
        <p class="text-sm text-gray-500">Track and manage hotel IT assets</p>
    </div>
    <div class="flex items-center gap-3">
        <button onclick="document.getElementById('addAssetModal').classList.remove('hidden')" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 px-6 rounded-xl shadow-sm transition flex items-center">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
            Add New Asset
        </button>
    </div>

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

<!-- Filters & Search -->
<div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-100 mb-6 flex flex-col md:flex-row gap-4 items-center justify-between">
    <div class="relative w-full md:w-96">
        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
        </span>
        <input type="text" id="assetSearch" placeholder="Search asset tag, serial, brand..." class="block w-full pl-10 pr-3 py-2 border border-gray-200 rounded-xl leading-5 bg-gray-50 placeholder-gray-400 focus:outline-none focus:bg-white focus:ring-1 focus:ring-blue-500 focus:border-blue-500 sm:text-sm transition">
    </div>
    <div class="flex items-center gap-2 w-full md:w-auto">
        <select id="categoryFilter" class="block w-full md:w-48 px-3 py-2 border border-gray-200 rounded-xl leading-5 bg-gray-50 focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
            <option value="">All Categories</option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?php echo e($cat['category_name']); ?>"><?php echo e($cat['category_name']); ?></option>
            <?php endforeach; ?>
        </select>
        <select id="statusFilter" class="block w-full md:w-40 px-3 py-2 border border-gray-200 rounded-xl leading-5 bg-gray-50 focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
            <option value="">All Status</option>
            <option value="Available">Available</option>
            <option value="Deployed">Deployed</option>
            <option value="Repair">Repair</option>
            <option value="Retired">Retired</option>
        </select>
    </div>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-100" id="inventoryTable">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-4 text-left text-[10px] font-black text-gray-500 uppercase tracking-widest">Asset Tag</th>
                    <th class="px-6 py-4 text-left text-[10px] font-black text-gray-500 uppercase tracking-widest">Category</th>
                    <th class="px-6 py-4 text-left text-[10px] font-black text-gray-500 uppercase tracking-widest">Specs</th>
                    <th class="px-6 py-4 text-left text-[10px] font-black text-gray-500 uppercase tracking-widest">Serial</th>
                    <th class="px-6 py-4 text-left text-[10px] font-black text-gray-500 uppercase tracking-widest">Status</th>
                    <th class="px-6 py-4 text-right text-[10px] font-black text-gray-500 uppercase tracking-widest">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php
                $stmt = $pdo->query("SELECT a.*, c.category_name, c.requires_serial_number, c.show_technical_specs, c.show_other_specs FROM assets a LEFT JOIN categories c ON a.category_id = c.id ORDER BY a.id DESC");
                $assets = $stmt->fetchAll();

                if ($assets):
                    foreach ($assets as $a):
                ?>
                    <tr class="hover:bg-gray-50 transition asset-row">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="p-2 bg-blue-50 rounded-lg mr-3 text-blue-600">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
                                </div>
                                <span class="text-sm font-bold text-gray-900"><?php echo e($a['asset_tag']); ?></span>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-xs font-medium text-gray-600 bg-gray-100 px-2 py-1 rounded-md category-cell"><?php echo e($a['category_name']); ?></span>
                        </td>
                        <td class="px-6 py-4">
                            <p class="text-sm font-semibold text-gray-800 search-target"><?php echo e($a['brand'] . ' ' . $a['model']); ?></p>
                            <p class="text-[10px] text-gray-500 truncate max-w-[200px]"><?php echo e($a['processor'] . ' | ' . $a['ram'] . ' | ' . $a['storage']); ?></p>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 font-mono search-target"><?php echo e($a['serial_number']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap status-cell">
                            <?php
                                $status_classes = [
                                    'Available' => 'bg-green-100 text-green-700 border-green-200',
                                    'Deployed' => 'bg-blue-100 text-blue-700 border-blue-200',
                                    'Repair' => 'bg-yellow-100 text-yellow-700 border-yellow-200',
                                    'Retired' => 'bg-red-100 text-red-700 border-red-200'
                                ];
                                $class = $status_classes[$a['status']] ?? 'bg-gray-100 text-gray-700 border-gray-200';
                            ?>
                            <span class="px-2.5 py-1 text-[10px] font-black uppercase rounded-full border <?php echo e($class); ?>">
                                <?php echo e($a['status']); ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <div class="flex justify-end space-x-2">
                                <button onclick='openQRModal("<?php echo e($a['asset_tag']); ?>")' class="p-2 text-purple-600 hover:bg-purple-50 rounded-lg transition" title="View QR Code">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path></svg>
                                </button>
                                <a href="asset_details.php?id=<?php echo e($a['id']); ?>" class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition" title="View Details">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                </a>
                                <button onclick='openEditModal(<?php echo htmlspecialchars(json_encode($a), ENT_QUOTES, "UTF-8"); ?>)' class="p-2 text-yellow-600 hover:bg-yellow-50 rounded-lg transition" title="Edit">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                </button>
                                <form action="" method="POST" class="inline" onsubmit="return confirm('Delete this asset? If history deletion is enabled in Settings, linked issuance, maintenance, and disposal records will also be permanently deleted.');">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="asset_id" value="<?php echo e($a['id']); ?>">
                                    <button type="submit" name="delete_asset" class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition" title="Delete">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php
                    endforeach;
                else:
                ?>
                    <tr id="noResults">
                        <td colspan="6" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center">
                                <div class="p-4 bg-gray-50 rounded-full mb-4">
                                    <svg class="w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 11m8 4V5"></path></svg>
                                </div>
                                <p class="text-gray-500 font-medium italic">No assets found in inventory.</p>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Asset Modal -->
<div id="addAssetModal" class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm hidden flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-4xl overflow-hidden animate-in fade-in zoom-in duration-200">
        <div class="bg-[#1e3a8a] p-6 text-white flex justify-between items-center">
            <div>
                <h2 class="text-xl font-bold uppercase tracking-tight">Register New Asset</h2>
                <p class="text-blue-200 text-xs">Fill in the details to add to inventory</p>
            </div>
            <button onclick="document.getElementById('addAssetModal').classList.add('hidden')" class="bg-white/10 hover:bg-white/20 p-2 rounded-full transition">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
        <form action="" method="POST" enctype="multipart/form-data" class="p-8 grid grid-cols-1 md:grid-cols-2 gap-6 max-h-[70vh] overflow-y-auto custom-scrollbar">
            <?php echo csrf_field(); ?>
            <div class="space-y-4">
                <div>
                    <label class="block text-gray-700 text-xs font-black uppercase tracking-widest mb-2">Category *</label>
                    <select name="category_id" id="inventory_category_select" onchange="toggleNewInventoryCategory(this)" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 transition" required>
                        <option value="">Select Category</option>
                        <?php foreach ($categories as $cat): ?>
                            <option
                                value="<?php echo e($cat['id']); ?>"
                                data-category-name="<?php echo e($cat['category_name']); ?>"
                                data-requires-serial="<?php echo e((int)$cat['requires_serial_number']); ?>"
                                data-show-tech="<?php echo e((int)$cat['show_technical_specs']); ?>"
                                data-show-notes="<?php echo e((int)$cat['show_other_specs']); ?>"
                            >
                                <?php echo e($cat['category_name']); ?>
                            </option>
                        <?php endforeach; ?>
                        <option value="new" class="font-bold text-blue-600">+ Add New Category</option>
                    </select>
                </div>
                <div id="new_inventory_category_div" class="hidden animate-in fade-in slide-in-from-top-2">
                    <label class="block text-blue-700 text-[10px] font-black uppercase tracking-widest mb-2 italic">New Category Name</label>
                    <input type="text" name="new_category_name" id="new_category_name" class="w-full px-4 py-3 bg-blue-50 border border-blue-200 rounded-xl focus:ring-2 focus:ring-blue-500 transition" placeholder="e.g. Tablets">
                    <div class="mt-3 space-y-2 rounded-2xl border border-blue-100 bg-white/70 p-4">
                        <label class="flex items-center justify-between gap-3 text-sm font-medium text-blue-900">
                            <span>Require serial number</span>
                            <input type="checkbox" name="new_requires_serial_number" id="new_requires_serial_number" checked class="h-4 w-4 rounded border-blue-300 text-blue-600 focus:ring-blue-500">
                        </label>
                        <label class="flex items-center justify-between gap-3 text-sm font-medium text-blue-900">
                            <span>Show processor / RAM / storage</span>
                            <input type="checkbox" name="new_show_technical_specs" id="new_show_technical_specs" checked class="h-4 w-4 rounded border-blue-300 text-blue-600 focus:ring-blue-500">
                        </label>
                        <label class="flex items-center justify-between gap-3 text-sm font-medium text-blue-900">
                            <span>Show notes / other specs</span>
                            <input type="checkbox" name="new_show_other_specs" id="new_show_other_specs" checked class="h-4 w-4 rounded border-blue-300 text-blue-600 focus:ring-blue-500">
                        </label>
                    </div>
                </div>
                <div>
                    <label class="block text-gray-700 text-xs font-black uppercase tracking-widest mb-2">Vendor *</label>
                    <select name="vendor_id" id="inventory_vendor_select" onchange="toggleNewInventoryVendor(this)" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 transition" required>
                        <option value="">Select Vendor</option>
                        <?php foreach ($vendors as $ven): ?>
                            <option value="<?php echo e($ven['id']); ?>"><?php echo e($ven['vendor_name']); ?></option>
                        <?php endforeach; ?>
                        <option value="new" class="font-bold text-blue-600">+ Add New Vendor</option>
                    </select>
                </div>
                <div id="new_inventory_vendor_div" class="hidden animate-in fade-in slide-in-from-top-2">
                    <label class="block text-blue-700 text-[10px] font-black uppercase tracking-widest mb-2 italic">New Vendor Name</label>
                    <input type="text" name="new_vendor_name" class="w-full px-4 py-3 bg-blue-50 border border-blue-200 rounded-xl focus:ring-2 focus:ring-blue-500 transition" placeholder="e.g. Silicon Valley">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-gray-700 text-xs font-black uppercase tracking-widest mb-2">Brand</label>
                        <input type="text" name="brand" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 transition" placeholder="e.g. Dell">
                    </div>
                    <div>
                        <label class="block text-gray-700 text-xs font-black uppercase tracking-widest mb-2">Model</label>
                        <input type="text" name="model" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 transition" placeholder="e.g. Latitude">
                    </div>
                </div>
                <div id="add_serial_group">
                    <label id="add_serial_label" class="block text-gray-700 text-xs font-black uppercase tracking-widest mb-2">Serial Number *</label>
                    <input type="text" name="serial_number" id="add_serial_number" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 transition" required>
                    <p id="add_serial_help" class="text-[10px] text-gray-400 mt-1">This field changes automatically based on the selected category.</p>
                </div>
            </div>
            <div class="space-y-4">
                <div id="add_technical_specs_group" class="grid grid-cols-3 gap-4">
                    <div class="col-span-1">
                        <label class="block text-gray-700 text-xs font-black uppercase tracking-widest mb-2">RAM</label>
                        <input type="text" name="ram" id="add_ram" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 transition" placeholder="16GB">
                    </div>
                    <div class="col-span-2">
                        <label class="block text-gray-700 text-xs font-black uppercase tracking-widest mb-2">Processor</label>
                        <input type="text" name="processor" id="add_processor" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 transition" placeholder="i7 11th Gen">
                    </div>
                </div>
                <div id="add_storage_group">
                    <label class="block text-gray-700 text-xs font-black uppercase tracking-widest mb-2">Storage</label>
                    <input type="text" name="storage" id="add_storage" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 transition" placeholder="512GB SSD">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-gray-700 text-xs font-black uppercase tracking-widest mb-2">Purchase Date</label>
                        <input type="date" name="purchase_date" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 transition">
                    </div>
                    <div>
                        <label class="block text-gray-700 text-xs font-black uppercase tracking-widest mb-2">Warranty Expiry</label>
                        <input type="date" name="warranty_expiry" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 transition">
                    </div>
                </div>
                <div id="add_other_specs_group">
                    <label class="block text-gray-700 text-xs font-black uppercase tracking-widest mb-2">Other Specs / Notes</label>
                    <textarea name="asset_notes" id="add_other_specs" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 transition" rows="2"></textarea>
                </div>
                <div id="add_phone_details" class="hidden rounded-2xl border border-sky-100 bg-sky-50/70 p-4 space-y-3">
                    <div>
                        <h3 class="text-xs font-black uppercase tracking-widest text-sky-900">Mobile phone service details</h3>
                        <p class="mt-1 text-[11px] text-sky-800">These details are saved with the inventory item and carried into its issuance form.</p>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <label class="text-xs font-bold text-gray-700">Mobile number<input type="text" name="phone_mobile_number" maxlength="40" class="mt-1 w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm"></label>
                        <label class="text-xs font-bold text-gray-700">Plan<input type="text" name="phone_plan_name" maxlength="120" class="mt-1 w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm"></label>
                        <label class="text-xs font-bold text-gray-700">Monthly limit<input type="text" name="phone_monthly_limit" maxlength="80" placeholder="e.g. PHP 1,000" class="mt-1 w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm"></label>
                        <label class="text-xs font-bold text-gray-700">Date activated<input type="date" name="phone_date_activated" class="mt-1 w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm"></label>
                        <label class="text-xs font-bold text-gray-700 sm:col-span-2">Plan inclusions<textarea name="phone_plan_inclusions" maxlength="500" rows="2" class="mt-1 w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm"></textarea></label>
                        <label class="text-xs font-bold text-gray-700 sm:col-span-2">Other rates<textarea name="phone_other_rates" maxlength="500" rows="2" class="mt-1 w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm"></textarea></label>
                    </div>
                </div>
                <div>
                    <label class="block text-gray-700 text-xs font-black uppercase tracking-widest mb-2">Equipment Photo</label>
                    <input type="file" name="equipment_photo" accept="image/*" class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 transition text-sm">
                </div>
            </div>
            <div class="md:col-span-2 flex items-center justify-end space-x-4 border-t pt-6">
                <button type="button" onclick="document.getElementById('addAssetModal').classList.add('hidden')" class="bg-gray-100 hover:bg-gray-200 text-gray-600 font-bold py-3 px-8 rounded-xl transition">Cancel</button>
                <button name="add_asset" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-10 rounded-xl shadow-lg shadow-blue-200 transition" type="submit">Save Asset</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Asset Modal -->
<div id="editAssetModal" class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm hidden flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-4xl overflow-hidden">
        <div class="bg-yellow-600 p-6 text-white flex justify-between items-center">
            <div>
                <h2 class="text-xl font-bold uppercase tracking-tight">Edit Asset Details</h2>
                <p class="text-yellow-100 text-xs">Modify equipment information</p>
            </div>
            <button onclick="document.getElementById('editAssetModal').classList.add('hidden')" class="bg-white/10 hover:bg-white/20 p-2 rounded-full transition">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
        <form action="" method="POST" enctype="multipart/form-data" class="p-8 grid grid-cols-1 md:grid-cols-2 gap-6 max-h-[70vh] overflow-y-auto custom-scrollbar">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="asset_id" id="edit_asset_id">
            <div class="space-y-4">
                <div>
                    <label class="block text-gray-700 text-xs font-black uppercase tracking-widest mb-2">Brand *</label>
                    <input type="text" name="brand" id="edit_brand" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-yellow-500 transition">
                </div>
                <div>
                    <label class="block text-gray-700 text-xs font-black uppercase tracking-widest mb-2">Model *</label>
                    <input type="text" name="model" id="edit_model" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-yellow-500 transition">
                </div>
                <div id="edit_serial_group">
                    <label id="edit_serial_label" class="block text-gray-700 text-xs font-black uppercase tracking-widest mb-2">Serial Number *</label>
                    <input type="text" name="serial_number" id="edit_serial" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-yellow-500 transition">
                </div>
                <div>
                    <label class="block text-gray-700 text-xs font-black uppercase tracking-widest mb-2">Status</label>
                    <select name="status" id="edit_status" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-yellow-500 transition">
                        <option value="Available">Available</option>
                        <option value="Deployed">Deployed</option>
                        <option value="Repair">Repair</option>
                        <option value="Retired">Retired</option>
                    </select>
                </div>
            </div>
            <div class="space-y-4">
                <div id="edit_technical_specs_group" class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-gray-700 text-xs font-black uppercase tracking-widest mb-2">Processor</label>
                        <input type="text" name="processor" id="edit_processor" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-yellow-500 transition">
                    </div>
                    <div>
                        <label class="block text-gray-700 text-xs font-black uppercase tracking-widest mb-2">RAM</label>
                        <input type="text" name="ram" id="edit_ram" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-yellow-500 transition">
                    </div>
                </div>
                <div id="edit_storage_group">
                    <label class="block text-gray-700 text-xs font-black uppercase tracking-widest mb-2">Storage</label>
                    <input type="text" name="storage" id="edit_storage" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-yellow-500 transition">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-gray-700 text-xs font-black uppercase tracking-widest mb-2">Purchase Date</label>
                        <input type="date" name="purchase_date" id="edit_purchase" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-yellow-500 transition">
                    </div>
                    <div>
                        <label class="block text-gray-700 text-xs font-black uppercase tracking-widest mb-2">Warranty Expiry</label>
                        <input type="date" name="warranty_expiry" id="edit_warranty" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-yellow-500 transition">
                    </div>
                </div>
            </div>
            <div class="col-span-full" id="edit_other_specs_group">
                <label class="block text-gray-700 text-xs font-black uppercase tracking-widest mb-2">Notes / Other Specs</label>
                <textarea name="asset_notes" id="edit_notes" rows="3" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-yellow-500 transition"></textarea>
            </div>
            <div id="edit_phone_details" class="hidden rounded-2xl border border-sky-100 bg-sky-50/70 p-4 space-y-3">
                <div>
                    <h3 class="text-xs font-black uppercase tracking-widest text-sky-900">Mobile phone service details</h3>
                    <p class="mt-1 text-[11px] text-sky-800">These details appear on this phone's issuance form.</p>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <label class="text-xs font-bold text-gray-700">Mobile number<input type="text" name="phone_mobile_number" id="edit_phone_mobile_number" maxlength="40" class="mt-1 w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm"></label>
                    <label class="text-xs font-bold text-gray-700">Plan<input type="text" name="phone_plan_name" id="edit_phone_plan_name" maxlength="120" class="mt-1 w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm"></label>
                    <label class="text-xs font-bold text-gray-700">Monthly limit<input type="text" name="phone_monthly_limit" id="edit_phone_monthly_limit" maxlength="80" class="mt-1 w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm"></label>
                    <label class="text-xs font-bold text-gray-700">Date activated<input type="date" name="phone_date_activated" id="edit_phone_date_activated" class="mt-1 w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm"></label>
                    <label class="text-xs font-bold text-gray-700 sm:col-span-2">Plan inclusions<textarea name="phone_plan_inclusions" id="edit_phone_plan_inclusions" maxlength="500" rows="2" class="mt-1 w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm"></textarea></label>
                    <label class="text-xs font-bold text-gray-700 sm:col-span-2">Other rates<textarea name="phone_other_rates" id="edit_phone_other_rates" maxlength="500" rows="2" class="mt-1 w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm"></textarea></label>
                </div>
            </div>
            <div class="col-span-full">
                <label class="block text-gray-700 text-xs font-black uppercase tracking-widest mb-2">Update Equipment Photo</label>
                <input type="file" name="equipment_photo" accept="image/*" class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-yellow-500 transition text-sm">
                <p class="text-[10px] text-gray-400 mt-1 italic">Leave empty to keep current photo</p>
            </div>
            <div class="col-span-full flex items-center justify-end space-x-4 pt-6 border-t border-gray-100">
                <button type="button" onclick="document.getElementById('editAssetModal').classList.add('hidden')" class="bg-gray-100 hover:bg-gray-200 text-gray-600 font-bold py-3 px-8 rounded-xl transition">Cancel</button>
                <button name="edit_asset" class="bg-yellow-600 hover:bg-yellow-700 text-white font-bold py-3 px-10 rounded-xl shadow-lg shadow-yellow-100 transition" type="submit">Update Asset</button>
            </div>
        </form>
    </div>
</div>

<!-- QR Code Modal -->
<div id="qrModal" class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm hidden flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-sm overflow-hidden animate-in fade-in zoom-in duration-200">
        <div class="bg-purple-600 p-6 text-white flex justify-between items-center">
            <div>
                <h2 class="text-xl font-bold uppercase tracking-tight italic">Asset QR Tag</h2>
                <p class="text-purple-100 text-xs">Scan for instant lookup</p>
            </div>
            <button onclick="document.getElementById('qrModal').classList.add('hidden')" class="bg-white/10 hover:bg-white/20 p-2 rounded-full transition">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
        <div class="p-8 text-center">
            <div id="qrContainer" class="bg-white p-4 inline-block rounded-2xl border-2 border-purple-100 mb-4 shadow-inner">
            </div>
            <h3 id="qrAssetTag" class="text-2xl font-black text-gray-800 tracking-tighter mb-2"></h3>
            <p class="text-gray-500 text-xs mb-6 px-8">This QR code contains the Asset Tag for fast identification in the ITIMS mobile scanner.</p>
            <button onclick="window.print()" class="w-full bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold py-3 px-6 rounded-xl transition flex items-center justify-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                Print Tag
            </button>
        </div>
    </div>
</div>

<script>
    function openQRModal(assetTag) {
        const qrContainer = document.getElementById('qrContainer');
        const qrAssetTag = document.getElementById('qrAssetTag');
        qrAssetTag.innerText = assetTag;

        const qrUrl = `https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${encodeURIComponent(assetTag)}`;
        qrContainer.innerHTML = `<img src="${qrUrl}" alt="QR Code" class="mx-auto rounded-lg">`;

        document.getElementById('qrModal').classList.remove('hidden');
    }

    function parsePhoneSpecsForEditor(specs) {
        const result = { mobile_number: '', plan_name: '', monthly_limit: '', plan_inclusions: '', other_rates: '', date_activated: '' };
        const aliases = {
            mobile_number: ['mobile number', 'phone number', 'mobile', 'msisdn'],
            plan_name: ['plan', 'plan name', 'mobile plan'],
            monthly_limit: ['monthly limit', 'max limit', 'maximum limit', 'plan limit'],
            plan_inclusions: ['plan inclusions', 'inclusions', 'included services'],
            other_rates: ['other rates', 'rates', 'excess rates'],
            date_activated: ['date activated', 'activation date', 'activated']
        };
        const notes = [];
        String(specs || '').split(/[\r\n;|]+/).forEach(function (line) {
            const match = line.match(/^\s*([^:=]+)\s*[:=]\s*(.*?)\s*$/);
            const key = match && Object.keys(aliases).find(function (name) {
                return aliases[name].includes(match[1].trim().toLowerCase());
            });
            if (key) result[key] = match[2];
            else if (line.trim()) notes.push(line);
        });
        result.notes = notes.join('\n');
        return result;
    }

    function setPhoneEditorFields(prefix, details) {
        Object.keys(details).forEach(function (key) {
            const input = document.getElementById(prefix + key);
            if (input) input.value = details[key] || '';
        });
    }

    function isPhoneCategory(category) {
        return /phone|mobile/i.test(category || '');
    }

    function openEditModal(asset) {
        document.getElementById('edit_asset_id').value = asset.id;
        document.getElementById('edit_brand').value = asset.brand || '';
        document.getElementById('edit_model').value = asset.model || '';
        document.getElementById('edit_serial').value = asset.serial_number || '';
        document.getElementById('edit_processor').value = asset.processor || '';
        document.getElementById('edit_ram').value = asset.ram || '';
        document.getElementById('edit_storage').value = asset.storage || '';
        document.getElementById('edit_purchase').value = asset.purchase_date || '';
        document.getElementById('edit_warranty').value = asset.warranty_expiry || '';
        const phoneDetails = parsePhoneSpecsForEditor(asset.other_specs || '');
        document.getElementById('edit_notes').value = phoneDetails.notes;
        setPhoneEditorFields('edit_phone_', phoneDetails);
        document.getElementById('edit_status').value = asset.status;
        applyAssetRulesToEdit(asset);
        document.getElementById('editAssetModal').classList.remove('hidden');
    }

    function toggleFieldGroup(groupId, inputIds, isVisible) {
        const group = document.getElementById(groupId);
        if (!group) {
            return;
        }

        group.classList.toggle('hidden', !isVisible);
        inputIds.forEach((id) => {
            const input = document.getElementById(id);
            if (!input) {
                return;
            }

            if (!isVisible) {
                input.value = '';
            }
        });
    }

    function applyAssetRulesToAdd() {
        const select = document.getElementById('inventory_category_select');
        let categoryName = '';
        let requiresSerial = true;
        let showTech = true;
        let showNotes = true;

        if (select.value === 'new') {
            requiresSerial = document.getElementById('new_requires_serial_number').checked;
            showTech = document.getElementById('new_show_technical_specs').checked;
            showNotes = document.getElementById('new_show_other_specs').checked;
        } else if (select.selectedOptions.length > 0) {
            const option = select.selectedOptions[0];
            categoryName = option.dataset.categoryName || '';
            requiresSerial = option.dataset.requiresSerial !== '0';
            showTech = option.dataset.showTech !== '0';
            showNotes = option.dataset.showNotes !== '0';
        }
        if (select.value === 'new') {
            categoryName = document.getElementById('new_category_name').value;
        }
        const isKeycard = /key\s*card|keycard|access card/i.test(categoryName);
        if (isKeycard) requiresSerial = true;

        document.getElementById('add_serial_group').classList.toggle('hidden', !requiresSerial);
        document.getElementById('add_serial_number').required = requiresSerial;
        document.getElementById('add_serial_label').textContent = isKeycard ? 'Card ID *' : 'Serial Number *';
        document.getElementById('add_serial_help').textContent = isKeycard ? 'Enter the printed card ID; it will appear on the assignment form.' : 'This field changes automatically based on the selected category.';
        if (!requiresSerial) {
            document.getElementById('add_serial_number').value = '';
        }

        toggleFieldGroup('add_technical_specs_group', ['add_ram', 'add_processor'], showTech);
        toggleFieldGroup('add_storage_group', ['add_storage'], showTech);
        toggleFieldGroup('add_other_specs_group', ['add_other_specs'], showNotes);
        document.getElementById('add_phone_details').classList.toggle('hidden', !isPhoneCategory(categoryName));
    }

    function applyAssetRulesToEdit(asset) {
        const isKeycard = /key\s*card|keycard|access card/i.test(asset.category_name || '');
        const requiresSerial = isKeycard || Number(asset.requires_serial_number) !== 0;
        const showTech = Number(asset.show_technical_specs) !== 0;
        const showNotes = Number(asset.show_other_specs) !== 0;

        document.getElementById('edit_serial_group').classList.toggle('hidden', !requiresSerial);
        document.getElementById('edit_serial').required = requiresSerial;
        document.getElementById('edit_serial_label').textContent = isKeycard ? 'Card ID *' : 'Serial Number *';
        if (!requiresSerial) {
            document.getElementById('edit_serial').value = '';
        }

        toggleFieldGroup('edit_technical_specs_group', ['edit_processor', 'edit_ram'], showTech);
        toggleFieldGroup('edit_storage_group', ['edit_storage'], showTech);
        toggleFieldGroup('edit_other_specs_group', ['edit_notes'], showNotes);
        document.getElementById('edit_phone_details').classList.toggle('hidden', !isPhoneCategory(asset.category_name));
    }

    function toggleNewInventoryCategory(select) {
        const div = document.getElementById('new_inventory_category_div');
        if (select.value === 'new') {
            div.classList.remove('hidden');
        } else {
            div.classList.add('hidden');
        }
        applyAssetRulesToAdd();
    }

    function toggleNewInventoryVendor(select) {
        const div = document.getElementById('new_inventory_vendor_div');
        if (select.value === 'new') {
            div.classList.remove('hidden');
        } else {
            div.classList.add('hidden');
        }
    }

    const searchInput = document.getElementById('assetSearch');
    const categoryFilter = document.getElementById('categoryFilter');
    const statusFilter = document.getElementById('statusFilter');
    const tableRows = document.querySelectorAll('.asset-row');
    const noResults = document.getElementById('noResults');
    const newCategoryRuleInputs = ['new_requires_serial_number', 'new_show_technical_specs', 'new_show_other_specs'];

    function filterTable() {
        const searchTerm = searchInput.value.toLowerCase();
        const categoryTerm = categoryFilter.value;
        const statusTerm = statusFilter.value;
        let visibleCount = 0;

        tableRows.forEach(row => {
            const text = row.innerText.toLowerCase();
            const category = row.querySelector('.category-cell').innerText;
            const status = row.querySelector('.status-cell').innerText.trim();

            const matchesSearch = text.includes(searchTerm);
            const matchesCategory = categoryTerm === "" || category === categoryTerm;
            const matchesStatus = statusTerm === "" || status === statusTerm;

            if (matchesSearch && matchesCategory && matchesStatus) {
                row.style.display = "";
                visibleCount++;
            } else {
                row.style.display = "none";
            }
        });

        if (noResults) {
            noResults.style.display = visibleCount === 0 ? "" : "none";
        }
    }

    searchInput.addEventListener('input', filterTable);
    categoryFilter.addEventListener('change', filterTable);
    statusFilter.addEventListener('change', filterTable);

    newCategoryRuleInputs.forEach((id) => {
        const input = document.getElementById(id);
        if (input) {
            input.addEventListener('change', applyAssetRulesToAdd);
        }
    });

    document.getElementById('new_category_name').addEventListener('input', applyAssetRulesToAdd);
    applyAssetRulesToAdd();
</script>

<?php include 'includes/footer.php'; ?>
