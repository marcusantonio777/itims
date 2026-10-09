<?php
require_once __DIR__ . '/includes/auth.php';
enforce_authenticated();
require_once __DIR__ . '/config/db.php';
enforce_page_access('keycards.php');

$message = '';
$error = '';
$canManage = in_array(auth_current_role(), [ROLE_ADMIN, ROLE_STAFF], true);

if (isset($_POST['register_keycard'])) {
    enforce_role(['admin', 'staff']);
    csrf_verify_post();

    $cardId = trim((string)($_POST['card_id'] ?? ''));
    $cardModel = trim((string)($_POST['card_model'] ?? 'Access Card'));
    if ($cardId === '' || strlen($cardId) > 100) {
        $error = 'Enter a card ID no longer than 100 characters.';
    } elseif (strlen($cardModel) > 100) {
        $error = 'Card type must be 100 characters or fewer.';
    } else {
        try {
            $pdo->beginTransaction();

            $existingCategory = $pdo->query("SELECT id, prefix FROM categories WHERE LOWER(category_name) REGEXP 'key[[:space:]]*card|access card' ORDER BY id LIMIT 1 FOR UPDATE")->fetch();
            if (!$existingCategory) {
                $pdo->prepare("INSERT INTO categories (category_name, prefix, requires_serial_number, show_technical_specs, show_other_specs) VALUES ('Keycard', 'KCD', 1, 0, 1)")->execute();
                $categoryId = (int)$pdo->lastInsertId();
                $prefix = 'KCD';
            } else {
                $categoryId = (int)$existingCategory['id'];
                $prefix = strtoupper(trim((string)$existingCategory['prefix']));
            }
            $prefix = substr(preg_replace('/[^A-Z0-9]/', '', $prefix) ?: 'KCD', 0, 10);

            $duplicate = $pdo->prepare("SELECT 1 FROM assets WHERE serial_number = ? LIMIT 1");
            $duplicate->execute([$cardId]);
            if ($duplicate->fetchColumn()) {
                throw new DomainException('That card ID is already registered.');
            }

            $year = date('Y');
            $tagQuery = $pdo->prepare("SELECT COALESCE(MAX(CAST(SUBSTRING_INDEX(asset_tag, '-', -1) AS UNSIGNED)), 0) FROM assets WHERE category_id = ? AND asset_tag LIKE ?");
            $tagQuery->execute([$categoryId, $prefix . '-' . $year . '-%']);
            $nextNumber = (int)$tagQuery->fetchColumn() + 1;
            $assetTag = $prefix . '-' . $year . '-' . str_pad((string)$nextNumber, 3, '0', STR_PAD_LEFT);

            $notes = trim((string)($_POST['notes'] ?? ''));
            $insert = $pdo->prepare("INSERT INTO assets (asset_tag, category_id, brand, model, serial_number, other_specs, purchase_date, status) VALUES (?, ?, 'Keycard', ?, ?, ?, CURDATE(), 'Available')");
            $insert->execute([$assetTag, $categoryId, $cardModel !== '' ? $cardModel : 'Access Card', $cardId, $notes !== '' ? $notes : null]);
            $assetId = (int)$pdo->lastInsertId();
            log_activity($pdo, 'Create', 'assets', $assetId, null, 'Registered keycard ' . $assetTag . ' (Card ID: ' . $cardId . ')');
            $pdo->commit();
            $message = 'Keycard registered and ready to assign: ' . $assetTag;
        } catch (DomainException $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $error = $e->getMessage();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            user_error_log('Register keycard failed', $e);
            $error = generic_error();
        }
    }
}

if (isset($_POST['update_keycard'])) {
    enforce_role(['admin', 'staff']);
    csrf_verify_post();
    $assetId = (int)($_POST['asset_id'] ?? 0);
    $cardId = trim((string)($_POST['card_id'] ?? ''));
    $cardModel = trim((string)($_POST['card_model'] ?? 'Access Card'));
    if ($assetId <= 0 || $cardId === '' || strlen($cardId) > 100 || strlen($cardModel) > 100) {
        $error = 'Enter a valid card ID and card type.';
    } else {
        try {
            $pdo->beginTransaction();
            $cardQuery = $pdo->prepare("SELECT a.asset_tag, a.serial_number FROM assets a JOIN categories c ON c.id = a.category_id WHERE a.id = ? AND c.category_name REGEXP 'key[[:space:]]*card|access card' FOR UPDATE");
            $cardQuery->execute([$assetId]);
            $existingCard = $cardQuery->fetch();
            if (!$existingCard) {
                throw new DomainException('Keycard not found.');
            }
            $activeCheck = $pdo->prepare("SELECT 1 FROM accountability_logs WHERE asset_id = ? AND status = 'Active' LIMIT 1");
            $activeCheck->execute([$assetId]);
            if ($activeCheck->fetchColumn()) {
                throw new DomainException('Return the currently assigned card before changing its identity.');
            }
            $duplicate = $pdo->prepare("SELECT 1 FROM assets WHERE serial_number = ? AND id <> ? LIMIT 1");
            $duplicate->execute([$cardId, $assetId]);
            if ($duplicate->fetchColumn()) {
                throw new DomainException('That card ID is already registered.');
            }
            $update = $pdo->prepare("UPDATE assets SET serial_number = ?, model = ? WHERE id = ?");
            $update->execute([$cardId, $cardModel !== '' ? $cardModel : 'Access Card', $assetId]);
            log_activity($pdo, 'Update', 'assets', $assetId, null, 'Updated keycard identity for ' . $existingCard['asset_tag']);
            $pdo->commit();
            $message = 'Keycard details updated.';
        } catch (DomainException $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $error = $e->getMessage();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            user_error_log('Update keycard identity failed', $e);
            $error = generic_error();
        }
    }
}

$keycards = $pdo->query("
    SELECT a.id, a.asset_tag, a.serial_number, a.model, a.status, a.purchase_date,
           active.id AS active_log_id, active.keycard_level, active.keycard_access,
           active.keycard_issued_on, active.keycard_expires_on,
           (SELECT e.name
              FROM accountability_logs l
              LEFT JOIN accountability_log_employees ale ON ale.log_id = l.id
              JOIN employees e ON e.id = COALESCE(ale.employee_id, l.user_id)
             WHERE l.id = active.id
             ORDER BY e.name
             LIMIT 1) AS assigned_employee
      FROM assets a
      JOIN categories c ON c.id = a.category_id
      LEFT JOIN accountability_logs active
        ON active.id = (
            SELECT l.id FROM accountability_logs l
             WHERE l.asset_id = a.id AND l.status = 'Active'
             ORDER BY l.date_issued DESC, l.id DESC
             LIMIT 1
        )
     WHERE c.category_name REGEXP 'key[[:space:]]*card|access card'
     ORDER BY (active.id IS NOT NULL) DESC, a.asset_tag ASC
")->fetchAll();

$availableCount = 0;
$assignedCount = 0;
$expiredCount = 0;
$today = date('Y-m-d');
foreach ($keycards as $card) {
    if ($card['active_log_id']) {
        $assignedCount++;
        if ($card['keycard_expires_on'] && $card['keycard_expires_on'] < $today) $expiredCount++;
    } elseif ($card['status'] === 'Available' && trim((string)$card['serial_number']) !== '') {
        $availableCount++;
    }
}

include __DIR__ . '/includes/header.php';
?>
<div class="mb-8 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
    <div>
        <p class="text-xs font-bold uppercase tracking-[0.18em] text-indigo-600">Access control</p>
        <h1 class="mt-1 text-2xl font-bold text-gray-900">Keycard Management</h1>
        <p class="mt-1 text-sm text-gray-500">Register cards, assign one card to one employee, and monitor yearly expiry.</p>
    </div>
    <?php if ($canManage): ?>
    <button type="button" onclick="document.getElementById('registerKeycardModal').classList.remove('hidden'); document.getElementById('registerKeycardModal').style.display='flex'" class="inline-flex items-center justify-center rounded-xl bg-indigo-700 px-5 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-indigo-800">
        <svg class="mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
        Register Keycard
    </button>
    <?php endif; ?>
</div>

<?php if ($message): ?><div class="mb-6 rounded-xl border border-green-200 bg-green-50 p-4 text-sm font-medium text-green-800"><?php echo e($message); ?></div><?php endif; ?>
<?php if ($error): ?><div class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4 text-sm font-medium text-red-800"><?php echo e($error); ?></div><?php endif; ?>

<section class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
    <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm"><p class="text-xs font-bold uppercase tracking-wider text-gray-500">Available to assign</p><p class="mt-2 text-3xl font-black text-emerald-700"><?php echo $availableCount; ?></p></div>
    <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm"><p class="text-xs font-bold uppercase tracking-wider text-gray-500">Assigned</p><p class="mt-2 text-3xl font-black text-indigo-700"><?php echo $assignedCount; ?></p></div>
    <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm"><p class="text-xs font-bold uppercase tracking-wider text-gray-500">Expired assignments</p><p class="mt-2 text-3xl font-black text-amber-700"><?php echo $expiredCount; ?></p></div>
</section>

<section class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm">
    <div class="flex flex-col gap-3 border-b border-gray-100 p-5 sm:flex-row sm:items-center sm:justify-between">
        <div><h2 class="font-bold text-gray-900">Card register</h2><p class="mt-1 text-xs text-gray-500">Assignment history stays attached to the card for audit purposes.</p></div>
        <div class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row">
            <label class="sr-only" for="keycardSearch">Search cards</label>
            <input id="keycardSearch" type="search" placeholder="Search card ID, employee, level..." class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 sm:w-72">
            <label class="sr-only" for="keycardStatusFilter">Filter card status</label>
            <select id="keycardStatusFilter" class="rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100">
                <option value="">All statuses</option><option value="available">Available</option><option value="assigned">Assigned</option><option value="expired">Expired</option><option value="unavailable">Unavailable</option>
            </select>
        </div>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-100">
            <thead class="bg-gray-50"><tr>
                <th class="px-5 py-3 text-left text-[10px] font-black uppercase tracking-wider text-gray-500">Card / ID</th>
                <th class="px-5 py-3 text-left text-[10px] font-black uppercase tracking-wider text-gray-500">Assigned Employee</th>
                <th class="px-5 py-3 text-left text-[10px] font-black uppercase tracking-wider text-gray-500">Level / Access</th>
                <th class="px-5 py-3 text-left text-[10px] font-black uppercase tracking-wider text-gray-500">Validity</th>
                <th class="px-5 py-3 text-left text-[10px] font-black uppercase tracking-wider text-gray-500">Status</th>
                <th class="px-5 py-3 text-right text-[10px] font-black uppercase tracking-wider text-gray-500">Action</th>
            </tr></thead>
            <tbody id="keycardRows" class="divide-y divide-gray-100">
            <?php foreach ($keycards as $card):
                $isAssigned = !empty($card['active_log_id']);
                $isExpired = $isAssigned && !empty($card['keycard_expires_on']) && $card['keycard_expires_on'] < $today;
                $statusLabel = $isAssigned ? ($isExpired ? 'Assigned · Expired' : 'Assigned') : $card['status'];
                $statusFilter = $isExpired ? 'expired' : ($isAssigned ? 'assigned' : ($card['status'] === 'Available' ? 'available' : 'unavailable'));
                $searchText = strtolower(implode(' ', array_filter([$card['asset_tag'], $card['serial_number'], $card['model'], $card['assigned_employee'], $card['keycard_level'], $statusLabel])));
            ?>
                <tr class="keycard-row hover:bg-gray-50" data-search="<?php echo e($searchText); ?>" data-status="<?php echo e($statusFilter); ?>">
                    <td class="whitespace-nowrap px-5 py-4"><p class="text-sm font-bold text-gray-900"><?php echo e($card['asset_tag']); ?></p><p class="mt-1 text-xs font-mono text-gray-500"><?php echo e($card['serial_number']); ?> · <?php echo e($card['model']); ?></p></td>
                    <td class="px-5 py-4 text-sm text-gray-700"><?php echo e($card['assigned_employee'] ?: '—'); ?></td>
                    <td class="px-5 py-4 text-sm"><p class="font-semibold text-gray-800"><?php echo e($card['keycard_level'] ?: '—'); ?></p><p class="text-xs text-gray-500"><?php echo e($card['keycard_access'] ?: '—'); ?></p></td>
                    <td class="whitespace-nowrap px-5 py-4 text-xs text-gray-600"><?php echo e($card['keycard_issued_on'] ?: 'Not assigned'); ?><br><?php echo e($card['keycard_expires_on'] ? 'Expires ' . $card['keycard_expires_on'] : ''); ?></td>
                    <td class="whitespace-nowrap px-5 py-4"><span class="rounded-full px-2.5 py-1 text-[10px] font-black uppercase <?php echo $isExpired ? 'bg-amber-100 text-amber-800' : ($isAssigned ? 'bg-indigo-100 text-indigo-800' : ($card['status'] === 'Available' ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-100 text-gray-700')); ?>"><?php echo e($statusLabel); ?></span></td>
                    <td class="whitespace-nowrap px-5 py-4 text-right">
                        <?php if ($canManage && !$card['serial_number'] && !$isAssigned): ?>
                            <button type="button" onclick='openKeycardEdit(<?php echo (int)$card['id']; ?>, "", <?php echo json_encode((string)$card['model'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>)' class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs font-bold text-amber-800 hover:bg-amber-100">Set Card ID</button>
                        <?php elseif ($canManage && !$isAssigned && $card['status'] === 'Available'): ?>
                            <a href="issuance.php?asset_id=<?php echo (int)$card['id']; ?>" class="inline-flex rounded-lg bg-indigo-700 px-3 py-2 text-xs font-bold text-white hover:bg-indigo-800">Assign card</a>
                        <?php elseif ($isAssigned): ?>
                            <a href="generate_pdf.php?log_id=<?php echo (int)$card['active_log_id']; ?>" class="text-xs font-bold text-indigo-700 hover:underline">View assignment</a>
                        <?php else: ?><span class="text-xs text-gray-400">Unavailable</span><?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$keycards): ?><tr><td colspan="6" class="px-5 py-12 text-center text-sm text-gray-500">No keycards registered yet. Use “Register Keycard” to add the first card.</td></tr><?php endif; ?>
            <?php if ($keycards): ?><tr id="keycardNoResults" class="hidden"><td colspan="6" class="px-5 py-10 text-center text-sm text-gray-500">No cards match the current search and filter.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php if ($canManage): ?>
<div id="registerKeycardModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/60 p-4 backdrop-blur-sm">
    <div class="w-full max-w-xl overflow-hidden rounded-2xl bg-white shadow-2xl">
        <div class="flex items-start justify-between bg-indigo-800 px-6 py-5 text-white">
            <div><h2 class="text-lg font-bold">Register a physical keycard</h2><p class="mt-1 text-xs text-indigo-200">The card becomes available for employee assignment.</p></div>
            <button type="button" onclick="document.getElementById('registerKeycardModal').classList.add('hidden'); document.getElementById('registerKeycardModal').style.display='none'" class="rounded-lg p-2 hover:bg-white/10" aria-label="Close">×</button>
        </div>
        <form method="POST" class="space-y-4 p-6">
            <?php echo csrf_field(); ?>
            <div><label for="card_id" class="mb-1 block text-xs font-bold uppercase tracking-wider text-gray-700">Card ID *</label><input id="card_id" name="card_id" required maxlength="100" class="w-full rounded-xl border border-gray-200 px-4 py-3 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100" placeholder="Scan or enter the printed card ID"></div>
            <div><label for="card_model" class="mb-1 block text-xs font-bold uppercase tracking-wider text-gray-700">Card type / technology</label><input id="card_model" name="card_model" maxlength="100" value="Access Card" class="w-full rounded-xl border border-gray-200 px-4 py-3 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100" placeholder="e.g. RFID, MIFARE"></div>
            <div><label for="notes" class="mb-1 block text-xs font-bold uppercase tracking-wider text-gray-700">Notes</label><textarea id="notes" name="notes" rows="2" class="w-full rounded-xl border border-gray-200 px-4 py-3 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100" placeholder="Optional card notes"></textarea></div>
            <div class="flex justify-end gap-3 border-t border-gray-100 pt-4"><button type="button" onclick="document.getElementById('registerKeycardModal').classList.add('hidden'); document.getElementById('registerKeycardModal').style.display='none'" class="rounded-xl bg-gray-100 px-5 py-2.5 text-sm font-bold text-gray-700">Cancel</button><button type="submit" name="register_keycard" class="rounded-xl bg-indigo-700 px-5 py-2.5 text-sm font-bold text-white hover:bg-indigo-800">Register card</button></div>
        </form>
    </div>
</div>
<div id="editKeycardModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/60 p-4 backdrop-blur-sm">
    <div class="w-full max-w-lg overflow-hidden rounded-2xl bg-white shadow-2xl">
        <div class="flex items-start justify-between bg-amber-600 px-6 py-5 text-white"><div><h2 class="text-lg font-bold">Update card identity</h2><p class="mt-1 text-xs text-amber-100">Card ID must be unique and cannot be changed while assigned.</p></div><button type="button" onclick="closeKeycardEdit()" class="rounded-lg p-2 hover:bg-white/10" aria-label="Close">×</button></div>
        <form method="POST" class="space-y-4 p-6">
            <?php echo csrf_field(); ?><input type="hidden" name="asset_id" id="edit_card_asset_id">
            <div><label for="edit_card_id" class="mb-1 block text-xs font-bold uppercase tracking-wider text-gray-700">Card ID *</label><input id="edit_card_id" name="card_id" required maxlength="100" class="w-full rounded-xl border border-gray-200 px-4 py-3 text-sm"></div>
            <div><label for="edit_card_model" class="mb-1 block text-xs font-bold uppercase tracking-wider text-gray-700">Card type / technology</label><input id="edit_card_model" name="card_model" maxlength="100" class="w-full rounded-xl border border-gray-200 px-4 py-3 text-sm"></div>
            <div class="flex justify-end gap-3 border-t border-gray-100 pt-4"><button type="button" onclick="closeKeycardEdit()" class="rounded-xl bg-gray-100 px-5 py-2.5 text-sm font-bold text-gray-700">Cancel</button><button type="submit" name="update_keycard" class="rounded-xl bg-amber-600 px-5 py-2.5 text-sm font-bold text-white hover:bg-amber-700">Save identity</button></div>
        </form>
    </div>
</div>
<?php endif; ?>

<script>
    function openKeycardEdit(id, cardId, model) {
        document.getElementById('edit_card_asset_id').value = id;
        document.getElementById('edit_card_id').value = cardId;
        document.getElementById('edit_card_model').value = model;
        const modal = document.getElementById('editKeycardModal');
        modal.classList.remove('hidden');
        modal.style.display = 'flex';
    }
    function closeKeycardEdit() {
        const modal = document.getElementById('editKeycardModal');
        modal.classList.add('hidden');
        modal.style.display = 'none';
    }
    function filterKeycards() {
        const query = document.getElementById('keycardSearch').value.trim().toLowerCase();
        const status = document.getElementById('keycardStatusFilter').value;
        let visible = 0;
        document.querySelectorAll('.keycard-row').forEach(function (row) {
            const matches = row.dataset.search.includes(query) && (!status || row.dataset.status === status);
            row.classList.toggle('hidden', !matches);
            if (matches) visible++;
        });
        const noResults = document.getElementById('keycardNoResults');
        if (noResults) noResults.classList.toggle('hidden', visible > 0);
    }
    document.getElementById('keycardSearch').addEventListener('input', filterKeycards);
    document.getElementById('keycardStatusFilter').addEventListener('change', filterKeycards);
    <?php if ($error && isset($_POST['register_keycard'])): ?>document.getElementById('registerKeycardModal').classList.remove('hidden'); document.getElementById('registerKeycardModal').style.display = 'flex';<?php endif; ?>
    <?php if ($error && isset($_POST['update_keycard'])): ?>document.getElementById('editKeycardModal').classList.remove('hidden'); document.getElementById('editKeycardModal').style.display = 'flex';<?php endif; ?>
</script>
<?php include __DIR__ . '/includes/footer.php'; ?>
