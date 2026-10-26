<?php
require_once APP_ROOT . '/includes/auth.php';
enforce_authenticated();
require_once APP_ROOT . '/config/db.php';
require_once APP_ROOT . '/includes/license_secrets.php';
enforce_page_access('software_licenses.php');

$message = '';
$error = '';

class LicenseUploadException extends RuntimeException {}

// Directory configuration for uploaded files
$upload_dir = APP_ROOT . '/uploads/licenses';
if (!is_dir($upload_dir)) {
    if (!mkdir($upload_dir, 0755, true) && !is_dir($upload_dir)) {
        throw new RuntimeException('The license attachment directory could not be created.');
    }
}

if (isset($_POST['reveal_license_key'])) {
    enforce_role(['admin']);
    csrf_verify_post();
    header('Content-Type: application/json; charset=UTF-8');
    header('Cache-Control: private, no-store');
    header('X-Content-Type-Options: nosniff');

    try {
        $licenseId = filter_var($_POST['license_id'] ?? null, FILTER_VALIDATE_INT);
        if (!$licenseId || $licenseId < 1) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid software license record.']);
            exit;
        }
        $secretQuery = $pdo->prepare('SELECT license_key FROM software_licenses WHERE id = ? LIMIT 1');
        $secretQuery->execute([$licenseId]);
        $storedSecret = $secretQuery->fetchColumn();
        if (!is_string($storedSecret) || $storedSecret === '') {
            http_response_code(404);
            echo json_encode(['error' => 'No software license key is stored for this record.']);
            exit;
        }
        $licenseKey = decrypt_license_secret($storedSecret);
        log_activity($pdo, 'LicenseKeyReveal', 'software_licenses', (int)$licenseId, null, 'Software license key revealed');
        echo json_encode(['license_key' => $licenseKey], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
        exit;
    } catch (Throwable $exception) {
        user_error_log('Software license key reveal failed', $exception);
        http_response_code(503);
        echo json_encode(['error' => 'The key is unavailable. Configure encryption and run pending database migrations.']);
        exit;
    }
}

if ((isset($_GET['view_attachment']) || isset($_GET['download_attachment']))
    && ctype_digit((string)($_GET['view_attachment'] ?? $_GET['download_attachment']))) {
    $attachmentId = (int)($_GET['view_attachment'] ?? $_GET['download_attachment']);
    $attachmentQuery = $pdo->prepare(
        'SELECT attachment_name, attachment_path FROM software_licenses WHERE id = ? LIMIT 1'
    );
    $attachmentQuery->execute([$attachmentId]);
    $attachment = $attachmentQuery->fetch(PDO::FETCH_ASSOC);
    $storageRoot = realpath(APP_ROOT . '/uploads/licenses');
    $storedPath = (string)($attachment['attachment_path'] ?? '');
    $resolvedPath = $storedPath !== ''
        ? realpath(APP_ROOT . '/' . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $storedPath))
        : false;
    $rootPrefix = $storageRoot !== false
        ? rtrim($storageRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR
        : '';
    $pathIsInsideStorage = $resolvedPath !== false
        && $rootPrefix !== ''
        && strncasecmp($resolvedPath, $rootPrefix, strlen($rootPrefix)) === 0;

    if (!$attachment || !$pathIsInsideStorage || !is_file($resolvedPath) || !is_readable($resolvedPath)) {
        http_response_code(404);
        exit('Attachment not found.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($resolvedPath) ?: 'application/octet-stream';
    $inlineTypes = [
        'application/pdf' => 'pdf',
        'image/jpeg' => 'image',
        'image/png' => 'image',
        'image/webp' => 'image',
        'text/plain' => 'text',
        'text/csv' => 'text',
    ];
    $isDownload = isset($_GET['download_attachment']);
    if (!$isDownload && !isset($inlineTypes[$mimeType])) {
        http_response_code(415);
        exit('This attachment type cannot be previewed in the browser.');
    }

    $downloadName = str_replace(["\r", "\n", '"'], '', basename((string)$attachment['attachment_name']));
    header('Content-Type: ' . ($isDownload ? $mimeType : $mimeType . (str_starts_with($mimeType, 'text/') ? '; charset=UTF-8' : '')));
    header('Content-Length: ' . filesize($resolvedPath));
    header('Content-Disposition: ' . ($isDownload ? 'attachment' : 'inline')
        . '; filename="' . addcslashes($downloadName, '\\') . '"; filename*=UTF-8\'\'' . rawurlencode($downloadName));
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header("Content-Security-Policy: default-src 'none'; style-src 'unsafe-inline'; frame-ancestors 'self'");
    header('Cache-Control: private, no-store');
    readfile($resolvedPath);
    exit;
}

function determine_license_status(?string $renewal_date, string $current_status): string {
    if ($current_status === 'Cancelled') {
        return 'Cancelled';
    }

    if (empty($renewal_date)) {
        return $current_status ?: 'Active';
    }

    $days = (int)floor((strtotime($renewal_date) - strtotime(date('Y-m-d'))) / 86400);
    if ($days < 0) {
        return 'Expired';
    }
    if ($days <= 30) {
        return 'Expiring Soon';
    }

    return 'Active';
}

function handle_file_upload(array $file_input, string $upload_dir): ?array {
    if (!isset($file_input['error']) || (int)$file_input['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ((int)$file_input['error'] !== UPLOAD_ERR_OK) {
        throw new LicenseUploadException('The attachment upload failed. Check the upload size limit and try again.');
    }

    $temporaryPath = (string)($file_input['tmp_name'] ?? '');
    $originalName = basename((string)($file_input['name'] ?? ''));
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    $allowedMimeTypes = [
        'pdf' => ['application/pdf'],
        'jpg' => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png' => ['image/png'],
        'webp' => ['image/webp'],
        'txt' => ['text/plain'],
        'csv' => ['text/plain', 'text/csv', 'application/vnd.ms-excel'],
    ];

    if ($originalName === '' || strlen($originalName) > 255 || preg_match('/[\x00-\x1F\x7F]/', $originalName)
        || !isset($allowedMimeTypes[$extension])) {
        throw new LicenseUploadException('Use a PDF, image, text, or CSV attachment with a valid filename.');
    }
    if ((int)($file_input['size'] ?? 0) > 10 * 1024 * 1024 || !is_uploaded_file($temporaryPath)) {
        throw new LicenseUploadException('The attachment must be a valid upload no larger than 10 MB.');
    }

    $detectedMimeType = (new finfo(FILEINFO_MIME_TYPE))->file($temporaryPath);
    if (!is_string($detectedMimeType) || !in_array($detectedMimeType, $allowedMimeTypes[$extension], true)) {
        throw new LicenseUploadException('The attachment extension does not match its detected file type.');
    }

    $newFilename = bin2hex(random_bytes(16)) . '.' . $extension;
    $targetPath = rtrim($upload_dir, '/\\') . DIRECTORY_SEPARATOR . $newFilename;
    if (!move_uploaded_file($temporaryPath, $targetPath)) {
        throw new LicenseUploadException('The server could not save the uploaded attachment.');
    }

    return [
        'name' => $originalName,
        'path' => 'uploads/licenses/' . $newFilename,
    ];
}

function license_attachment_absolute_path(?string $storedPath): ?string {
    if (!is_string($storedPath) || $storedPath === '') {
        return null;
    }
    $storageRoot = realpath(APP_ROOT . '/uploads/licenses');
    $resolvedPath = realpath(APP_ROOT . '/' . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $storedPath));
    if ($storageRoot === false || $resolvedPath === false) {
        return null;
    }

    $rootPrefix = rtrim($storageRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    if (strncasecmp($resolvedPath, $rootPrefix, strlen($rootPrefix)) !== 0 || !is_file($resolvedPath)) {
        return null;
    }

    return $resolvedPath;
}

if (isset($_POST['add_license'])) {
    enforce_role(['admin', 'staff']);
    csrf_verify_post();
    $software_name   = trim($_POST['software_name'] ?? '');
    $vendor_name     = trim($_POST['vendor_name'] ?? '');
    $license_key     = auth_current_role() === ROLE_ADMIN ? trim($_POST['license_key'] ?? '') : '';
    $license_type    = trim($_POST['license_type'] ?? '');
    $seats           = max(1, (int)($_POST['seats'] ?? 1));
    $cost            = (float)($_POST['cost'] ?? 0);
    $billing_cycle   = $_POST['billing_cycle'] ?? 'Annual';
    $start_date      = trim($_POST['start_date'] ?? '');
    $renewal_date    = trim($_POST['renewal_date'] ?? '');
    $update_due_date = trim($_POST['update_due_date'] ?? '');
    $status          = determine_license_status($renewal_date ?: null, $_POST['status'] ?? 'Active');
    $owner_name      = trim($_POST['owner_name'] ?? '');
    $notes           = trim($_POST['notes'] ?? '');

    if ($software_name === '') {
        $error = "Software name is required.";
    } elseif ($license_key !== '' && !license_secret_key_is_configured()) {
        $error = 'Configure ITIMS_LICENSE_KEY_ENCRYPTION_KEY before saving software license keys.';
    } else {
        $upload_result = null;
        try {
            $upload_result = handle_file_upload($_FILES['attachment'] ?? [], $upload_dir);
            $storedLicenseKey = $license_key !== '' ? encrypt_license_secret($license_key) : null;
            $stmt = $pdo->prepare("INSERT INTO software_licenses (
                software_name, vendor_name, license_key, license_type, seats, cost, billing_cycle, 
                start_date, renewal_date, update_due_date, status, owner_name, notes, attachment_name, attachment_path, created_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

            $stmt->execute([
                $software_name,
                $vendor_name !== '' ? $vendor_name : null,
                $storedLicenseKey,
                $license_type !== '' ? $license_type : null,
                $seats,
                $cost,
                $billing_cycle,
                $start_date !== '' ? $start_date : null,
                $renewal_date !== '' ? $renewal_date : null,
                $update_due_date !== '' ? $update_due_date : null,
                $status,
                $owner_name !== '' ? $owner_name : null,
                $notes !== '' ? $notes : null,
                $upload_result['name'] ?? null,
                $upload_result['path'] ?? null,
                auth_current_id()
            ]);
            $id = (int)$pdo->lastInsertId();
            log_activity($pdo, 'Create', 'software_licenses', $id, null, "Added software license: {$software_name}");
            $message = "Software license added successfully.";
        } catch (Throwable $e) {
            $uploadedPath = license_attachment_absolute_path($upload_result['path'] ?? null);
            if ($uploadedPath !== null && !unlink($uploadedPath)) {
                user_error_log('Could not remove incomplete software license attachment: ' . $uploadedPath);
            }
            user_error_log('Add software license failed', $e);
            $error = ($e instanceof LicenseSecretException || $e instanceof LicenseUploadException)
                ? $e->getMessage()
                : generic_error();
        }
    }
}

if (isset($_POST['edit_license'])) {
    enforce_role(['admin', 'staff']);
    csrf_verify_post();
    $id              = (int)($_POST['license_id'] ?? 0);
    $software_name   = trim($_POST['software_name'] ?? '');
    $vendor_name     = trim($_POST['vendor_name'] ?? '');
    $license_key     = auth_current_role() === ROLE_ADMIN ? trim($_POST['license_key'] ?? '') : '';
    $license_type    = trim($_POST['license_type'] ?? '');
    $seats           = max(1, (int)($_POST['seats'] ?? 1));
    $cost            = (float)($_POST['cost'] ?? 0);
    $billing_cycle   = $_POST['billing_cycle'] ?? 'Annual';
    $start_date      = trim($_POST['start_date'] ?? '');
    $renewal_date    = trim($_POST['renewal_date'] ?? '');
    $update_due_date = trim($_POST['update_due_date'] ?? '');
    $status          = determine_license_status($renewal_date ?: null, $_POST['status'] ?? 'Active');
    $owner_name      = trim($_POST['owner_name'] ?? '');
    $notes           = trim($_POST['notes'] ?? '');

    if ($id <= 0 || $software_name === '') {
        $error = "Software name is required.";
    } else {
        $upload_result = null;
        $oldAttachmentPath = null;
        try {
            $stmt_existing = $pdo->prepare("SELECT attachment_name, attachment_path, license_key FROM software_licenses WHERE id = ?");
            $stmt_existing->execute([$id]);
            $existing = $stmt_existing->fetch();
            if (!$existing) {
                throw new RuntimeException('The selected software license record no longer exists.');
            }

            $attachment_name = $existing['attachment_name'] ?? null;
            $attachment_path = $existing['attachment_path'] ?? null;
            $oldAttachmentPath = $attachment_path;
            $storedLicenseKey = $existing['license_key'] ?? null;
            if (auth_current_role() === ROLE_ADMIN) {
                if ($license_key !== '') {
                    if (!license_secret_key_is_configured()) {
                        throw new LicenseSecretException('Configure ITIMS_LICENSE_KEY_ENCRYPTION_KEY before saving software license keys.');
                    }
                    $storedLicenseKey = encrypt_license_secret($license_key);
                } elseif (!empty($_POST['clear_license_key'])) {
                    $storedLicenseKey = null;
                }
            }

            $upload_result = handle_file_upload($_FILES['attachment'] ?? [], $upload_dir);
            if ($upload_result) {
                $attachment_name = $upload_result['name'];
                $attachment_path = $upload_result['path'];
            }

            $stmt = $pdo->prepare("UPDATE software_licenses SET 
                software_name = ?, vendor_name = ?, license_key = ?, license_type = ?, seats = ?, cost = ?, 
                billing_cycle = ?, start_date = ?, renewal_date = ?, update_due_date = ?, status = ?, 
                owner_name = ?, notes = ?, attachment_name = ?, attachment_path = ? 
                WHERE id = ?");

            $stmt->execute([
                $software_name,
                $vendor_name !== '' ? $vendor_name : null,
                $storedLicenseKey,
                $license_type !== '' ? $license_type : null,
                $seats,
                $cost,
                $billing_cycle,
                $start_date !== '' ? $start_date : null,
                $renewal_date !== '' ? $renewal_date : null,
                $update_due_date !== '' ? $update_due_date : null,
                $status,
                $owner_name !== '' ? $owner_name : null,
                $notes !== '' ? $notes : null,
                $attachment_name,
                $attachment_path,
                $id
            ]);
            if ($upload_result && $oldAttachmentPath !== $attachment_path) {
                $oldFile = license_attachment_absolute_path($oldAttachmentPath);
                if ($oldFile !== null && !unlink($oldFile)) {
                    user_error_log('Could not remove replaced software license attachment: ' . $oldFile);
                }
            }
            log_activity($pdo, 'Update', 'software_licenses', $id, null, "Updated software license: {$software_name}");
            $message = "Software license updated successfully.";
        } catch (Throwable $e) {
            $uploadedFile = license_attachment_absolute_path($upload_result['path'] ?? null);
            if ($uploadedFile !== null && !unlink($uploadedFile)) {
                user_error_log('Could not remove incomplete software license attachment: ' . $uploadedFile);
            }
            user_error_log('Edit software license failed', $e);
            $error = ($e instanceof LicenseSecretException || $e instanceof LicenseUploadException)
                ? $e->getMessage()
                : generic_error();
        }
    }
}

if (isset($_POST['delete_license'])) {
    enforce_role(['admin', 'staff']);
    csrf_verify_post();
    $id = (int)($_POST['license_id'] ?? 0);

    if ($id <= 0) {
        $error = "Invalid license ID.";
    } else {
        try {
            // Clean up attached file on record deletion
            $stmt_file = $pdo->prepare("SELECT attachment_path FROM software_licenses WHERE id = ?");
            $stmt_file->execute([$id]);
            $file_rec = $stmt_file->fetch();

            $stmt = $pdo->prepare("DELETE FROM software_licenses WHERE id = ?");
            $stmt->execute([$id]);

            $attachmentFile = license_attachment_absolute_path($file_rec['attachment_path'] ?? null);
            if ($attachmentFile !== null && !unlink($attachmentFile)) {
                user_error_log('Could not remove deleted software license attachment: ' . $attachmentFile);
            }
            log_activity($pdo, 'Delete', 'software_licenses', $id, null, "Deleted software license ID {$id}");
            $message = "Software license deleted successfully.";
        } catch (Throwable $e) {
            user_error_log('Delete software license failed', $e);
            $error = generic_error();
        }
    }
}

$licenses = $pdo->query(
    "SELECT id, software_name, vendor_name, license_type, seats, cost, billing_cycle,
            start_date, renewal_date, update_due_date, status, owner_name, notes,
            attachment_name, attachment_path,
            (license_key IS NOT NULL AND license_key <> '') AS _has_license_key
     FROM software_licenses
     ORDER BY COALESCE(renewal_date, update_due_date, DATE(created_at)) ASC, software_name ASC"
)->fetchAll();
$hasStoredLicenseKeys = false;
foreach ($licenses as $license) {
    $hasStoredLicenseKeys = $hasStoredLicenseKeys || !empty($license['_has_license_key']);
}
$expiring_count = $pdo->query("SELECT COUNT(*) FROM software_licenses WHERE renewal_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) AND status != 'Cancelled'")->fetchColumn();
$updates_due_count = $pdo->query("SELECT COUNT(*) FROM software_licenses WHERE update_due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) AND status != 'Cancelled'")->fetchColumn();
$active_count = $pdo->query("SELECT COUNT(*) FROM software_licenses WHERE status = 'Active'")->fetchColumn();

include APP_ROOT . '/includes/header.php';
?>

<div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Software Licenses</h1>
        <p class="text-sm text-gray-500">Track contracts, renewals, payments, and update schedules for software used by IT</p>
    </div>
    <button onclick="document.getElementById('addLicenseModal').classList.remove('hidden')" class="bg-[#1e3a8a] hover:bg-blue-900 text-white font-bold py-2.5 px-6 rounded-xl shadow-sm transition flex items-center">
        <svg class="w-5 h-5 mr-2 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
        Add License
    </button>
</div>

<?php if ($hasStoredLicenseKeys && !license_secret_key_is_configured() && auth_current_role() === ROLE_ADMIN): ?>
    <div class="mb-6 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
        Configure <code>ITIMS_LICENSE_KEY_ENCRYPTION_KEY</code> and run the pending database migrations before revealing or saving license keys.
    </div>
<?php endif; ?>

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
        <p class="text-[10px] uppercase tracking-widest text-gray-400 font-black">Active Contracts</p>
        <p class="text-3xl font-black text-blue-900 mt-2"><?php echo (int)$active_count; ?></p>
        <p class="text-xs text-gray-500 mt-2">Currently active licenses and subscriptions</p>
    </div>
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
        <p class="text-[10px] uppercase tracking-widest text-gray-400 font-black">Renewals Due</p>
        <p class="text-3xl font-black text-orange-600 mt-2"><?php echo (int)$expiring_count; ?></p>
        <p class="text-xs text-gray-500 mt-2">Need attention within the next 30 days</p>
    </div>
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
        <p class="text-[10px] uppercase tracking-widest text-gray-400 font-black">Updates Due</p>
        <p class="text-3xl font-black text-purple-700 mt-2"><?php echo (int)$updates_due_count; ?></p>
        <p class="text-xs text-gray-500 mt-2">Scheduled review or update dates approaching</p>
    </div>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-100">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-4 text-left text-[10px] font-black text-gray-500 uppercase tracking-widest">Software</th>
                    <th class="px-6 py-4 text-left text-[10px] font-black text-gray-500 uppercase tracking-widest">Type / Seats</th>
                    <th class="px-6 py-4 text-left text-[10px] font-black text-gray-500 uppercase tracking-widest">Renewal</th>
                    <th class="px-6 py-4 text-left text-[10px] font-black text-gray-500 uppercase tracking-widest">Update Due</th>
                    <th class="px-6 py-4 text-left text-[10px] font-black text-gray-500 uppercase tracking-widest">Attachment</th>
                    <th class="px-6 py-4 text-left text-[10px] font-black text-gray-500 uppercase tracking-widest">Status</th>
                    <th class="px-6 py-4 text-right text-[10px] font-black text-gray-500 uppercase tracking-widest">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php if ($licenses): ?>
                    <?php foreach ($licenses as $license): ?>
                        <?php
                        $statusClasses = [
                            'Active' => 'bg-green-100 text-green-700 border-green-200',
                            'Expiring Soon' => 'bg-orange-100 text-orange-700 border-orange-200',
                            'Expired' => 'bg-red-100 text-red-700 border-red-200',
                            'Cancelled' => 'bg-gray-100 text-gray-600 border-gray-200',
                        ];
                        $statusClass = $statusClasses[$license['status']] ?? 'bg-gray-100 text-gray-700 border-gray-200';
                        ?>
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-6 py-4">
                                <p class="text-sm font-bold text-gray-900"><?php echo htmlspecialchars($license['software_name']); ?></p>
                                <p class="text-xs text-gray-500"><?php echo htmlspecialchars($license['vendor_name'] ?: 'Vendor not set'); ?></p>
                            </td>
                            <td class="px-6 py-4">
                                <p class="text-sm font-medium text-gray-700"><?php echo htmlspecialchars($license['license_type'] ?: 'License'); ?></p>
                                <p class="text-xs text-gray-500"><?php echo (int)$license['seats']; ?> seat(s) • <?php echo htmlspecialchars($license['billing_cycle']); ?></p>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600"><?php echo $license['renewal_date'] ? date('M d, Y', strtotime($license['renewal_date'])) : '-'; ?></td>
                            <td class="px-6 py-4 text-sm text-gray-600"><?php echo $license['update_due_date'] ? date('M d, Y', strtotime($license['update_due_date'])) : '-'; ?></td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                <?php if (!empty($license['attachment_path']) && file_exists(APP_ROOT . '/' . $license['attachment_path'])): ?>
                                    <button type="button" onclick="openLicenseAttachment(<?php echo (int)$license['id']; ?>, <?php echo e(json_encode($license['attachment_name'] ?: 'License attachment', JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)); ?>, <?php echo e(json_encode(strtolower(pathinfo($license['attachment_name'] ?: '', PATHINFO_EXTENSION)))); ?>)" class="inline-flex items-center text-blue-600 hover:text-blue-800 hover:underline text-xs font-semibold">
                                        <svg class="w-4 h-4 mr-1 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path></svg>
                                        <span class="truncate max-w-[120px]"><?php echo htmlspecialchars($license['attachment_name'] ?: 'View attachment'); ?></span>
                                    </button>
                                <?php else: ?>
                                    <span class="text-gray-400 text-xs">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2.5 py-1 text-[10px] font-black uppercase rounded-full border <?php echo $statusClass; ?>">
                                    <?php echo htmlspecialchars($license['status']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex justify-end space-x-2">
                                    <button type="button" onclick='openEditLicenseModal(<?php echo e(json_encode($license)); ?>)' class="p-2 text-yellow-600 hover:bg-yellow-50 rounded-lg transition" title="Edit">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                    </button>
                                    <?php if (auth_current_role() === ROLE_ADMIN && !empty($license['_has_license_key'])): ?>
                                        <button type="button" onclick="revealLicenseKey(<?php echo (int)$license['id']; ?>)" class="rounded-lg p-2 text-blue-700 hover:bg-blue-50" title="Reveal stored license key" aria-label="Reveal stored license key">Key</button>
                                    <?php endif; ?>
                                    <form method="POST" class="inline" onsubmit="return confirm('Delete this software license entry?');">
                                        <input type="hidden" name="license_id" value="<?php echo e($license['id']); ?>">
                                        <?php echo csrf_field(); ?>
                                        <button type="submit" name="delete_license" class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition" title="Delete">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-gray-400 italic">No software contracts or licenses recorded yet.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="licenseAttachmentModal" class="fixed inset-0 z-[80] hidden items-center justify-center bg-slate-950/80 p-3 backdrop-blur-sm sm:p-6" role="dialog" aria-modal="true" aria-labelledby="licenseAttachmentTitle" tabindex="-1" onclick="if (event.target === this) closeLicenseAttachment()">
    <section class="flex h-[min(92vh,900px)] w-full max-w-6xl flex-col overflow-hidden rounded-2xl border border-white/10 bg-slate-900 shadow-2xl">
        <header class="flex items-center justify-between gap-4 border-b border-white/10 bg-slate-950 px-4 py-3 sm:px-6">
            <div class="min-w-0">
                <p class="text-[10px] font-bold uppercase tracking-[.18em] text-blue-300">Software license attachment</p>
                <h2 id="licenseAttachmentTitle" class="truncate text-sm font-bold text-white sm:text-base"></h2>
            </div>
            <div class="flex shrink-0 items-center gap-2">
                <a id="licenseAttachmentDownload" class="rounded-lg border border-white/15 px-3 py-2 text-xs font-bold text-slate-200 hover:bg-white/10" href="#">Download</a>
                <button type="button" class="rounded-lg bg-white/10 px-3 py-2 text-xs font-bold text-white hover:bg-white/20" onclick="closeLicenseAttachment()" aria-label="Close attachment preview">Close</button>
            </div>
        </header>
        <div id="licenseAttachmentStage" class="flex min-h-0 flex-1 items-center justify-center overflow-auto bg-slate-800 p-3 sm:p-6">
            <p class="text-sm text-slate-300">Choose an attachment to preview.</p>
        </div>
        <footer class="border-t border-white/10 bg-slate-950 px-4 py-2 text-[11px] text-slate-400">
            Attachments are available only to users authorized to view Software Licenses.
        </footer>
    </section>
</div>

<?php if (auth_current_role() === ROLE_ADMIN): ?>
    <form id="licenseKeyRevealToken" class="hidden"><?php echo csrf_field(); ?></form>
    <div id="licenseKeyModal" class="fixed inset-0 z-[90] hidden items-center justify-center bg-slate-950/80 p-4" role="dialog" aria-modal="true" aria-labelledby="licenseKeyModalTitle">
        <section class="w-full max-w-xl rounded-2xl bg-white p-6 shadow-2xl">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 id="licenseKeyModalTitle" class="text-lg font-bold text-slate-900">Software license key</h2>
                    <p class="mt-1 text-sm text-slate-500">This reveal is restricted to administrators and recorded in the activity log.</p>
                </div>
                <button type="button" onclick="closeLicenseKeyModal()" class="rounded-lg px-3 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-100">Close</button>
            </div>
            <p id="licenseKeyModalMessage" class="mt-5 text-sm text-slate-600">Loading key...</p>
            <code id="licenseKeyModalValue" class="mt-3 hidden max-h-40 overflow-auto break-all rounded-xl bg-slate-100 p-4 text-sm text-slate-900"></code>
        </section>
    </div>
<?php endif; ?>

<?php
function render_license_form(string $submitName, string $buttonText, string $accentColor, array $license = [], bool $canManageLicenseKey = false): void {
    $license += [
        'id' => '',
        'software_name' => '',
        'vendor_name' => '',
        'license_key' => '',
        'license_type' => '',
        'seats' => 1,
        'cost' => '0.00',
        'billing_cycle' => 'Annual',
        'start_date' => '',
        'renewal_date' => '',
        'update_due_date' => '',
        'status' => 'Active',
        'owner_name' => '',
        'notes' => '',
        'attachment_name' => '',
    ];
    ?>
    <input type="hidden" name="license_id" value="<?php echo htmlspecialchars((string)$license['id']); ?>">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label class="block text-gray-700 text-xs font-black uppercase tracking-widest mb-2">Software Name *</label>
            <input type="text" name="software_name" value="<?php echo htmlspecialchars((string)$license['software_name']); ?>" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-<?php echo $accentColor; ?>-500 transition" required>
        </div>
        <div>
            <label class="block text-gray-700 text-xs font-black uppercase tracking-widest mb-2">Vendor</label>
            <input type="text" name="vendor_name" value="<?php echo htmlspecialchars((string)$license['vendor_name']); ?>" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-<?php echo $accentColor; ?>-500 transition">
        </div>
        <div>
            <label class="block text-gray-700 text-xs font-black uppercase tracking-widest mb-2">License Type</label>
            <input type="text" name="license_type" value="<?php echo htmlspecialchars((string)$license['license_type']); ?>" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-<?php echo $accentColor; ?>-500 transition" placeholder="Subscription / Perpetual / SaaS">
        </div>
        <div>
            <label class="block text-gray-700 text-xs font-black uppercase tracking-widest mb-2">License Key / Ref</label>
            <?php if ($canManageLicenseKey): ?>
                <input type="password" name="license_key" value="" autocomplete="new-password" placeholder="Enter to set or replace; leave blank to keep current" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-<?php echo $accentColor; ?>-500 transition">
                <?php if ($submitName === 'edit_license'): ?>
                    <label class="mt-2 inline-flex items-center gap-2 text-xs text-red-700">
                        <input type="checkbox" name="clear_license_key" value="1">
                        Clear the stored key
                    </label>
                <?php endif; ?>
            <?php else: ?>
                <p class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-xs text-gray-500">Only administrators can set, clear, or reveal software license keys.</p>
            <?php endif; ?>
        </div>
        <div>
            <label class="block text-gray-700 text-xs font-black uppercase tracking-widest mb-2">Seats</label>
            <input type="number" min="1" name="seats" value="<?php echo (int)$license['seats']; ?>" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-<?php echo $accentColor; ?>-500 transition">
        </div>
        <div>
            <label class="block text-gray-700 text-xs font-black uppercase tracking-widest mb-2">Cost</label>
            <input type="number" min="0" step="0.01" name="cost" value="<?php echo htmlspecialchars((string)$license['cost']); ?>" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-<?php echo $accentColor; ?>-500 transition">
        </div>
        <div>
            <label class="block text-gray-700 text-xs font-black uppercase tracking-widest mb-2">Billing Cycle</label>
            <select name="billing_cycle" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-<?php echo $accentColor; ?>-500 transition">
                <?php foreach (['Monthly', 'Quarterly', 'Semi-Annual', 'Annual', 'One-Time', 'Custom'] as $cycle): ?>
                    <option value="<?php echo $cycle; ?>" <?php echo $license['billing_cycle'] === $cycle ? 'selected' : ''; ?>><?php echo $cycle; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="block text-gray-700 text-xs font-black uppercase tracking-widest mb-2">Owner / Assigned To</label>
            <input type="text" name="owner_name" value="<?php echo htmlspecialchars((string)$license['owner_name']); ?>" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-<?php echo $accentColor; ?>-500 transition">
        </div>
        <div>
            <label class="block text-gray-700 text-xs font-black uppercase tracking-widest mb-2">Start Date</label>
            <input type="date" name="start_date" value="<?php echo htmlspecialchars((string)$license['start_date']); ?>" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-<?php echo $accentColor; ?>-500 transition">
        </div>
        <div>
            <label class="block text-gray-700 text-xs font-black uppercase tracking-widest mb-2">Renewal Date</label>
            <input type="date" name="renewal_date" value="<?php echo htmlspecialchars((string)$license['renewal_date']); ?>" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-<?php echo $accentColor; ?>-500 transition">
        </div>
        <div>
            <label class="block text-gray-700 text-xs font-black uppercase tracking-widest mb-2">Update Due Date</label>
            <input type="date" name="update_due_date" value="<?php echo htmlspecialchars((string)$license['update_due_date']); ?>" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-<?php echo $accentColor; ?>-500 transition">
        </div>
        <div>
            <label class="block text-gray-700 text-xs font-black uppercase tracking-widest mb-2">Status</label>
            <select name="status" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-<?php echo $accentColor; ?>-500 transition">
                <?php foreach (['Active', 'Expiring Soon', 'Expired', 'Cancelled'] as $status): ?>
                    <option value="<?php echo $status; ?>" <?php echo $license['status'] === $status ? 'selected' : ''; ?>><?php echo $status; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    
    <div class="mt-4">
        <label class="block text-gray-700 text-xs font-black uppercase tracking-widest mb-2">Attachment (PDF, Contract, Invoice)</label>
        <input type="file" name="attachment" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-gray-200 file:text-gray-700 hover:file:bg-gray-300 cursor-pointer">
        <p class="current-attachment-label text-xs text-gray-500 mt-1 hidden"></p>
    </div>

    <div class="mt-4">
        <label class="block text-gray-700 text-xs font-black uppercase tracking-widest mb-2">Notes</label>
        <textarea name="notes" rows="4" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-<?php echo $accentColor; ?>-500 transition"><?php echo htmlspecialchars((string)$license['notes']); ?></textarea>
    </div>
    <div class="flex justify-end gap-4 border-t border-gray-100 pt-6 mt-6">
        <button name="<?php echo $submitName; ?>" type="submit" class="bg-<?php echo $accentColor; ?>-600 hover:bg-<?php echo $accentColor; ?>-700 text-white font-bold py-3 px-10 rounded-xl shadow-lg transition"><?php echo htmlspecialchars($buttonText); ?></button>
    </div>
<?php } ?>

<div id="addLicenseModal" class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm hidden flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-4xl overflow-hidden animate-in fade-in zoom-in duration-200">
        <div class="bg-[#1e3a8a] p-6 text-white flex justify-between items-center">
            <div>
                <h2 class="text-xl font-bold uppercase tracking-tight">Add Software License</h2>
                <p class="text-blue-200 text-xs">Track payments, renewals, and update schedules</p>
            </div>
            <button type="button" onclick="document.getElementById('addLicenseModal').classList.add('hidden')" class="bg-white/10 hover:bg-white/20 p-2 rounded-full transition">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
        <form action="" method="POST" enctype="multipart/form-data" class="p-8 max-h-[80vh] overflow-y-auto custom-scrollbar">
            <?php echo csrf_field(); ?>
            <?php render_license_form('add_license', 'Save License', 'blue', [], auth_current_role() === ROLE_ADMIN); ?>
        </form>
    </div>
</div>

<div id="editLicenseModal" class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm hidden flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-4xl overflow-hidden animate-in fade-in zoom-in duration-200">
        <div class="bg-yellow-600 p-6 text-white flex justify-between items-center">
            <div>
                <h2 class="text-xl font-bold uppercase tracking-tight">Edit Software License</h2>
                <p class="text-yellow-100 text-xs">Update contract, payment, or maintenance details</p>
            </div>
            <button type="button" onclick="document.getElementById('editLicenseModal').classList.add('hidden')" class="bg-white/10 hover:bg-white/20 p-2 rounded-full transition">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
        <form action="" method="POST" enctype="multipart/form-data" class="p-8 max-h-[80vh] overflow-y-auto custom-scrollbar">
            <?php echo csrf_field(); ?>
            <?php render_license_form('edit_license', 'Update License', 'yellow', [], auth_current_role() === ROLE_ADMIN); ?>
        </form>
    </div>
</div>

<script>
let licenseAttachmentRequest = 0;
function openLicenseAttachment(id, name, extension) {
    const modal = document.getElementById('licenseAttachmentModal');
    const stage = document.getElementById('licenseAttachmentStage');
    const requestId = ++licenseAttachmentRequest;
    const previewUrl = 'software_licenses.php?view_attachment=' + encodeURIComponent(id);
    document.getElementById('licenseAttachmentTitle').textContent = name;
    document.getElementById('licenseAttachmentDownload').href =
        'software_licenses.php?download_attachment=' + encodeURIComponent(id);
    stage.replaceChildren();
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    document.body.classList.add('overflow-hidden');
    modal.focus();

    if (extension === 'pdf') {
        const frame = document.createElement('iframe');
        frame.title = 'PDF preview: ' + name;
        frame.className = 'h-full min-h-[60vh] w-full rounded-lg bg-white';
        frame.src = previewUrl;
        stage.appendChild(frame);
    } else if (['jpg', 'jpeg', 'png', 'webp'].includes(extension)) {
        const image = document.createElement('img');
        image.alt = name;
        image.className = 'max-h-full max-w-full object-contain';
        image.src = previewUrl;
        stage.appendChild(image);
    } else if (['txt', 'csv'].includes(extension)) {
        const loading = document.createElement('p');
        loading.className = 'text-sm text-slate-300';
        loading.textContent = 'Loading preview…';
        stage.appendChild(loading);
        fetch(previewUrl, {credentials: 'same-origin'})
            .then(function (response) {
                if (!response.ok) throw new Error('Preview unavailable');
                return response.text();
            })
            .then(function (contents) {
                if (requestId !== licenseAttachmentRequest) return;
                const text = document.createElement('pre');
                text.className = 'm-0 min-h-full w-full whitespace-pre-wrap break-words rounded-xl bg-slate-950 p-4 text-sm leading-6 text-slate-100';
                text.textContent = contents;
                stage.replaceChildren(text);
            })
            .catch(function () {
                if (requestId === licenseAttachmentRequest) showLicenseAttachmentMessage('This file could not be previewed. You can download the original file.');
            });
    } else {
        showLicenseAttachmentMessage('This file type cannot be displayed safely in the browser. You can download the original file from here.');
    }
}
function showLicenseAttachmentMessage(message) {
    const panel = document.createElement('div');
    panel.className = 'max-w-lg rounded-2xl border border-white/10 bg-slate-900 p-8 text-center';
    const title = document.createElement('h3');
    title.className = 'mb-2 text-lg font-bold text-white';
    title.textContent = 'Preview unavailable';
    const description = document.createElement('p');
    description.className = 'text-sm leading-6 text-slate-300';
    description.textContent = message;
    panel.append(title, description);
    document.getElementById('licenseAttachmentStage').replaceChildren(panel);
}
function closeLicenseAttachment() {
    const modal = document.getElementById('licenseAttachmentModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    document.body.classList.remove('overflow-hidden');
    document.getElementById('licenseAttachmentStage').replaceChildren();
    licenseAttachmentRequest++;
}
<?php if (auth_current_role() === ROLE_ADMIN): ?>
async function revealLicenseKey(id) {
    const modal = document.getElementById('licenseKeyModal');
    const message = document.getElementById('licenseKeyModalMessage');
    const value = document.getElementById('licenseKeyModalValue');
    value.textContent = '';
    value.classList.add('hidden');
    message.textContent = 'Loading key...';
    modal.classList.remove('hidden');
    modal.classList.add('flex');

    const token = document.querySelector('#licenseKeyRevealToken input[name="_csrf"]').value;
    try {
        const response = await fetch('software_licenses.php', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({_csrf: token, reveal_license_key: '1', license_id: String(id)})
        });
        const result = await response.json();
        if (!response.ok) {
            throw new Error(result.error || 'The key could not be revealed.');
        }
        message.textContent = 'Key revealed. Close this dialog when finished.';
        value.textContent = result.license_key;
        value.classList.remove('hidden');
    } catch (error) {
        message.textContent = error instanceof Error ? error.message : 'The key could not be revealed.';
    }
}
function closeLicenseKeyModal() {
    const modal = document.getElementById('licenseKeyModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    document.getElementById('licenseKeyModalValue').textContent = '';
}
<?php endif; ?>
document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') {
        if (!document.getElementById('licenseAttachmentModal').classList.contains('hidden')) {
            closeLicenseAttachment();
        }
        const keyModal = document.getElementById('licenseKeyModal');
        if (keyModal && !keyModal.classList.contains('hidden')) {
            closeLicenseKeyModal();
        }
    }
});

    function openEditLicenseModal(license) {
        const modal = document.getElementById('editLicenseModal');
        const licenseKeyInput = modal.querySelector('[name="license_key"]');
        if (licenseKeyInput) licenseKeyInput.value = '';
        const clearLicenseKey = modal.querySelector('[name="clear_license_key"]');
        if (clearLicenseKey) clearLicenseKey.checked = false;
        modal.querySelector('[name="license_id"]').value = license.id || '';
        modal.querySelector('[name="software_name"]').value = license.software_name || '';
        modal.querySelector('[name="vendor_name"]').value = license.vendor_name || '';
        modal.querySelector('[name="license_type"]').value = license.license_type || '';
        modal.querySelector('[name="seats"]').value = license.seats || 1;
        modal.querySelector('[name="cost"]').value = license.cost || '0.00';
        modal.querySelector('[name="billing_cycle"]').value = license.billing_cycle || 'Annual';
        modal.querySelector('[name="owner_name"]').value = license.owner_name || '';
        modal.querySelector('[name="start_date"]').value = license.start_date || '';
        modal.querySelector('[name="renewal_date"]').value = license.renewal_date || '';
        modal.querySelector('[name="update_due_date"]').value = license.update_due_date || '';
        modal.querySelector('[name="status"]').value = license.status || 'Active';
        modal.querySelector('[name="notes"]').value = license.notes || '';

        // File label handling
        const fileLabel = modal.querySelector('.current-attachment-label');
        if (license.attachment_name) {
            fileLabel.textContent = 'Current file: ' + license.attachment_name + ' (Upload new file to replace)';
            fileLabel.classList.remove('hidden');
        } else {
            fileLabel.textContent = '';
            fileLabel.classList.add('hidden');
        }

        modal.classList.remove('hidden');
    }
</script>

<?php include APP_ROOT . '/includes/footer.php'; ?>