<?php
require_once APP_ROOT . '/includes/auth.php';
require_once APP_ROOT . '/config/db.php';
enforce_authenticated();
enforce_role(['admin']);

$message = '';
$error = '';
$appBranding = app_branding($pdo);
$signatorySettingsQuery = $pdo->prepare(
    'SELECT setting_key, setting_value FROM system_settings WHERE setting_key IN (?, ?, ?, ?)'
);
$signatorySettingsQuery->execute([
    'pdf_prepared_by_name',
    'pdf_prepared_by_title',
    'pdf_noted_by_name',
    'pdf_noted_by_title',
]);
$signatorySettings = $signatorySettingsQuery->fetchAll(PDO::FETCH_KEY_PAIR);
$pdfPreparedByName = (string)($signatorySettings['pdf_prepared_by_name'] ?? '');
$pdfPreparedByTitle = (string)($signatorySettings['pdf_prepared_by_title'] ?? '');
$pdfNotedByName = (string)($signatorySettings['pdf_noted_by_name'] ?? '');
$pdfNotedByTitle = (string)($signatorySettings['pdf_noted_by_title'] ?? '');

if (isset($_POST['save_settings'])) {
    csrf_verify_post();
    $allowHistoryDeletion = isset($_POST['allow_delete_assets_with_history']) ? '1' : '0';
    $productName = trim((string)($_POST['product_name'] ?? ''));
    $organizationName = trim((string)($_POST['organization_name'] ?? ''));
    $departmentLabel = trim((string)($_POST['department_label'] ?? ''));
    $pdfPreparedByName = trim((string)($_POST['pdf_prepared_by_name'] ?? ''));
    $pdfPreparedByTitle = trim((string)($_POST['pdf_prepared_by_title'] ?? ''));
    $pdfNotedByName = trim((string)($_POST['pdf_noted_by_name'] ?? ''));
    $pdfNotedByTitle = trim((string)($_POST['pdf_noted_by_title'] ?? ''));
    $removeLogo = isset($_POST['remove_company_logo']);
    $newLogoPath = null;
    $oldLogoPath = $appBranding['company_logo_path'];

    if ($productName === '' || strlen($productName) > 80
        || $organizationName === '' || strlen($organizationName) > 120
        || $departmentLabel === '' || strlen($departmentLabel) > 100
        || strlen($pdfPreparedByName) > 120 || strlen($pdfNotedByName) > 120
        || strlen($pdfPreparedByTitle) > 100 || strlen($pdfNotedByTitle) > 100) {
        $error = 'Enter a product name (up to 80 characters), organization name (up to 120), and department label (up to 100).';
        if (strlen($pdfPreparedByName) > 120 || strlen($pdfNotedByName) > 120) {
            $error = 'Prepared By and Noted By names must each be 120 characters or fewer.';
        } elseif (strlen($pdfPreparedByTitle) > 100 || strlen($pdfNotedByTitle) > 100) {
            $error = 'Prepared By and Noted By positions must each be 100 characters or fewer.';
        }
    } elseif (isset($_FILES['company_logo']) && $_FILES['company_logo']['error'] !== UPLOAD_ERR_NO_FILE) {
        $logo = $_FILES['company_logo'];
        if ($logo['error'] !== UPLOAD_ERR_OK || (int)$logo['size'] > 2 * 1024 * 1024) {
            $error = 'Choose a valid logo file no larger than 2 MB.';
        } else {
            $imageInfo = @getimagesize($logo['tmp_name']);
            $allowedImageTypes = [
                IMAGETYPE_JPEG => 'jpg',
                IMAGETYPE_PNG => 'png',
                IMAGETYPE_WEBP => 'webp',
            ];
            if (!$imageInfo || !isset($allowedImageTypes[$imageInfo[2]])
                || $imageInfo[0] > 4000 || $imageInfo[1] > 4000) {
                $error = 'Logo must be a JPG, PNG, or WEBP image no larger than 4000 by 4000 pixels.';
            } elseif (function_exists('finfo_open')) {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime = $finfo ? finfo_file($finfo, $logo['tmp_name']) : false;
                if ($finfo) {
                    finfo_close($finfo);
                }
                $expectedMime = [
                    IMAGETYPE_JPEG => 'image/jpeg',
                    IMAGETYPE_PNG => 'image/png',
                    IMAGETYPE_WEBP => 'image/webp',
                ];
                if ($mime !== ($expectedMime[$imageInfo[2]] ?? null)) {
                    $error = 'The selected logo file does not match its image type.';
                }
            }

            if ($error === '') {
                $logoDirectory = APP_ROOT . '/uploads/branding';
                if (!is_dir($logoDirectory) && !mkdir($logoDirectory, 0755, true) && !is_dir($logoDirectory)) {
                    $error = 'The branding upload directory could not be created.';
                } else {
                    $accessFile = $logoDirectory . '/.htaccess';
                    $accessRules = "Options -ExecCGI -Indexes\nSetHandler default-handler\nphp_flag engine off\nRequire all denied\n";
                    if (!is_file($accessFile) && file_put_contents($accessFile, $accessRules, LOCK_EX) === false) {
                        $error = 'Could not secure the branding upload directory.';
                    }
                }
                if ($error === '') {
                    $extension = $allowedImageTypes[$imageInfo[2]];
                    $logoName = bin2hex(random_bytes(16)) . '.' . $extension;
                    $newLogoPath = 'uploads/branding/' . $logoName;
                    if (!move_uploaded_file($logo['tmp_name'], APP_ROOT . '/' . $newLogoPath)) {
                        $newLogoPath = null;
                        $error = 'The logo file could not be saved.';
                    }
                }
            }
        }
    }

    if ($error === '') {
        try {
            $pdo->beginTransaction();
            $upsert = $pdo->prepare(
                "INSERT INTO system_settings (setting_key, setting_value, updated_by)
                 VALUES (?, ?, ?)
                 ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_by = VALUES(updated_by)"
            );
            $values = [
                'allow_delete_assets_with_history' => $allowHistoryDeletion,
                'product_name' => $productName,
                'organization_name' => $organizationName,
                'department_label' => $departmentLabel,
                'pdf_prepared_by_name' => $pdfPreparedByName,
                'pdf_prepared_by_title' => $pdfPreparedByTitle,
                'pdf_noted_by_name' => $pdfNotedByName,
                'pdf_noted_by_title' => $pdfNotedByTitle,
            ];
            if ($newLogoPath !== null) {
                $values['company_logo_path'] = $newLogoPath;
            } elseif ($removeLogo) {
                $values['company_logo_path'] = '';
            }
            foreach ($values as $key => $value) {
                $upsert->execute([$key, $value, auth_current_id()]);
            }
            log_activity(
                $pdo,
                'Update',
                'system_settings',
                null,
                null,
                'Updated system branding, accountability signatories, and asset-history deletion setting'
            );
            $pdo->commit();
            if (($newLogoPath !== null || $removeLogo) && $oldLogoPath !== '') {
                $oldLogo = APP_ROOT . '/' . $oldLogoPath;
                if (is_file($oldLogo) && !unlink($oldLogo)) {
                    error_log('Previous organization logo could not be removed: ' . $oldLogo);
                }
            }
            unset($GLOBALS['ITIMS_APP_BRANDING']);
            $appBranding = app_branding($pdo);
            $signatorySettings = [
                'pdf_prepared_by_name' => $pdfPreparedByName,
                'pdf_prepared_by_title' => $pdfPreparedByTitle,
                'pdf_noted_by_name' => $pdfNotedByName,
                'pdf_noted_by_title' => $pdfNotedByTitle,
            ];
            $message = 'Settings saved.';
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            if ($newLogoPath !== null && is_file(APP_ROOT . '/' . $newLogoPath)) {
                unlink(APP_ROOT . '/' . $newLogoPath);
            }
            user_error_log('Save system settings failed', $e);
            $error = generic_error();
        }
    }
}

$setting = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = ?");
$setting->execute(['allow_delete_assets_with_history']);
$allowHistoryDeletion = $setting->fetchColumn() === '1';

include APP_ROOT . '/includes/header.php';
?>

<div class="mx-auto w-full max-w-4xl space-y-6">
    <?php if ($message): ?>
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800"><?php echo e($message); ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-800"><?php echo e($error); ?></div>
    <?php endif; ?>

    <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-gray-100 px-6 py-5">
            <p class="text-xs font-bold uppercase tracking-widest text-blue-600">Administration</p>
            <h1 class="mt-1 text-xl font-black text-gray-900">System Settings</h1>
            <p class="mt-1 text-sm text-gray-500">Configure this installation’s product identity and inventory safeguards.</p>
        </div>

        <form method="POST" enctype="multipart/form-data" class="space-y-5 p-6">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="save_settings" value="1">
            <div class="grid gap-5 md:grid-cols-2">
                <div>
                    <label for="product_name" class="mb-2 block text-xs font-black uppercase tracking-widest text-gray-600">Product name</label>
                    <input id="product_name" name="product_name" maxlength="80" required value="<?php echo e($appBranding['product_name']); ?>" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm outline-none transition focus:border-blue-400 focus:bg-white focus:ring-2 focus:ring-blue-100">
                </div>
                <div>
                    <label for="organization_name" class="mb-2 block text-xs font-black uppercase tracking-widest text-gray-600">Organization name</label>
                    <input id="organization_name" name="organization_name" maxlength="120" required value="<?php echo e($appBranding['organization_name']); ?>" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm outline-none transition focus:border-blue-400 focus:bg-white focus:ring-2 focus:ring-blue-100">
                </div>
                <div>
                    <label for="department_label" class="mb-2 block text-xs font-black uppercase tracking-widest text-gray-600">Department label</label>
                    <input id="department_label" name="department_label" maxlength="100" required value="<?php echo e($appBranding['department_label']); ?>" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm outline-none transition focus:border-blue-400 focus:bg-white focus:ring-2 focus:ring-blue-100">
                </div>
                <div>
                    <label for="company_logo" class="mb-2 block text-xs font-black uppercase tracking-widest text-gray-600">Organization logo</label>
                    <input id="company_logo" name="company_logo" type="file" accept="image/jpeg,image/png,image/webp" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-blue-50 file:px-3 file:py-2 file:text-xs file:font-bold file:text-blue-700">
                    <p class="mt-1 text-xs text-gray-500">JPG, PNG, or WEBP; up to 2 MB. Used in navigation and printed forms.</p>
                    <?php if ($appBranding['company_logo_path'] !== ''): ?>
                        <label class="mt-3 flex items-center gap-2 text-xs font-semibold text-gray-700">
                            <input type="checkbox" name="remove_company_logo" value="1" class="rounded border-gray-300 text-red-600 focus:ring-red-500">
                            Remove current logo
                        </label>
                    <?php endif; ?>
                </div>
            </div>

            <div class="border-t border-gray-100 pt-5">
                <h2 class="mb-1 text-sm font-black text-gray-900">Accountability form signatories</h2>
                <p class="mb-4 text-sm text-gray-500">Set the names and positions printed on generated accountability forms. Leave a name blank to use the issuing staff member’s name; leave a position blank to use the form’s default.</p>
                <div class="grid gap-5 md:grid-cols-2">
                    <div>
                        <label for="pdf_prepared_by_name" class="mb-2 block text-xs font-black uppercase tracking-widest text-gray-600">Prepared By</label>
                        <input id="pdf_prepared_by_name" name="pdf_prepared_by_name" maxlength="120" value="<?php echo e($pdfPreparedByName); ?>" placeholder="Use issuing staff member" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm outline-none transition focus:border-blue-400 focus:bg-white focus:ring-2 focus:ring-blue-100">
                    </div>
                    <div>
                        <label for="pdf_prepared_by_title" class="mb-2 block text-xs font-black uppercase tracking-widest text-gray-600">Prepared By position/title</label>
                        <input id="pdf_prepared_by_title" name="pdf_prepared_by_title" maxlength="100" value="<?php echo e($pdfPreparedByTitle); ?>" placeholder="Use form default" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm outline-none transition focus:border-blue-400 focus:bg-white focus:ring-2 focus:ring-blue-100">
                    </div>
                    <div>
                        <label for="pdf_noted_by_name" class="mb-2 block text-xs font-black uppercase tracking-widest text-gray-600">Noted By</label>
                        <input id="pdf_noted_by_name" name="pdf_noted_by_name" maxlength="120" value="<?php echo e($pdfNotedByName); ?>" placeholder="Use issuing staff member" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm outline-none transition focus:border-blue-400 focus:bg-white focus:ring-2 focus:ring-blue-100">
                    </div>
                    <div>
                        <label for="pdf_noted_by_title" class="mb-2 block text-xs font-black uppercase tracking-widest text-gray-600">Noted By position/title</label>
                        <input id="pdf_noted_by_title" name="pdf_noted_by_title" maxlength="100" value="<?php echo e($pdfNotedByTitle); ?>" placeholder="IT Manager" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm outline-none transition focus:border-blue-400 focus:bg-white focus:ring-2 focus:ring-blue-100">
                    </div>
                </div>
            </div>

            <div class="border-t border-gray-100 pt-5">
                <h2 class="mb-3 text-sm font-black text-gray-900">Inventory safeguards</h2>
                <label for="allow_delete_assets_with_history" class="flex cursor-pointer items-start gap-4 rounded-xl border border-gray-200 p-5 transition hover:border-blue-300">
                <input
                    id="allow_delete_assets_with_history"
                    name="allow_delete_assets_with_history"
                    type="checkbox"
                    value="1"
                    <?php echo $allowHistoryDeletion ? 'checked' : ''; ?>
                    class="mt-1 h-5 w-5 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                >
                <span class="flex-1">
                    <span class="block font-bold text-gray-900">Allow deleting assets with history</span>
                    <span class="mt-1 block text-sm leading-6 text-gray-600">
                        When enabled, deleting an asset permanently removes its linked issuance, maintenance, and disposal records. Use this for test or sample data only. Assets with an active assignment cannot be deleted.
                    </span>
                    <span class="mt-3 inline-flex rounded-full px-3 py-1 text-xs font-bold <?php echo $allowHistoryDeletion ? 'bg-red-100 text-red-700' : 'bg-emerald-100 text-emerald-700'; ?>">
                        <?php echo $allowHistoryDeletion ? 'Permanent history deletion is enabled' : 'History protection is enabled'; ?>
                    </span>
                </span>
            </label>
            </div>

            <div class="flex justify-end border-t border-gray-100 pt-5">
                <button type="submit" class="rounded-xl bg-blue-700 px-5 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    Save Settings
                </button>
            </div>
        </form>
    </section>
</div>

<?php include APP_ROOT . '/includes/footer.php'; ?>
