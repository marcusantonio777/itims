<?php
require_once APP_ROOT . '/includes/auth.php';
require_once APP_ROOT . '/config/db.php';
enforce_page_access('file_manager.php');

$storageDir = APP_ROOT . '/uploads/file_manager';
$canManageFiles = in_array(auth_current_role(), [ROLE_ADMIN, ROLE_STAFF], true);
$allowedExtensions = [
    'pdf' => ['application/pdf'],
    'txt' => ['text/plain'],
    'csv' => ['text/plain', 'text/csv', 'application/csv', 'application/vnd.ms-excel'],
    'doc' => ['application/msword', 'application/vnd.ms-office'],
    'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip'],
    'xls' => ['application/vnd.ms-excel', 'application/x-ole-storage', 'application/vnd.ms-office'],
    'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip'],
    'ppt' => ['application/vnd.ms-powerpoint', 'application/vnd.ms-office'],
    'pptx' => ['application/vnd.openxmlformats-officedocument.presentationml.presentation', 'application/zip'],
    'jpg' => ['image/jpeg'],
    'jpeg' => ['image/jpeg'],
    'png' => ['image/png'],
    'webp' => ['image/webp'],
    'zip' => ['application/zip', 'application/x-zip-compressed'],
];
$maxFileBytes = 25 * 1024 * 1024;
$folderParam = $_REQUEST['folder'] ?? '';
$currentFolderId = is_string($folderParam) && ctype_digit($folderParam)
    ? (int)$folderParam
    : 0;

function file_manager_valid_name(string $name): bool {
    return $name !== ''
        && strlen($name) <= 255
        && !in_array($name, ['.', '..'], true)
        && !preg_match('/[\/\\\\\x00-\x1F]/', $name)
        && !preg_match('/[\. ]$/', $name);
}

function file_manager_folder_exists(PDO $pdo, int $folderId): bool {
    if ($folderId === 0) {
        return true;
    }
    $stmt = $pdo->prepare("SELECT 1 FROM file_manager_items WHERE id = ? AND item_type = 'folder'");
    $stmt->execute([$folderId]);
    return (bool)$stmt->fetchColumn();
}

function file_manager_unique_name(PDO $pdo, int $parentId, string $name, int $excludeId = 0): bool {
    $sql = "SELECT 1 FROM file_manager_items WHERE name = ? AND "
        . ($parentId === 0 ? 'parent_id IS NULL' : 'parent_id = ?');
    $params = [$name];
    if ($parentId !== 0) {
        $params[] = $parentId;
    }
    if ($excludeId > 0) {
        $sql .= ' AND id != ?';
        $params[] = $excludeId;
    }
    $stmt = $pdo->prepare($sql . ' LIMIT 1');
    $stmt->execute($params);
    return !$stmt->fetchColumn();
}

function file_manager_item(PDO $pdo, int $id): ?array {
    $stmt = $pdo->prepare("SELECT * FROM file_manager_items WHERE id = ? LIMIT 1");
    $stmt->execute([$id]);
    $item = $stmt->fetch();
    return $item ?: null;
}

function file_manager_redirect(int $folderId): void {
    header('Location: file_manager.php' . ($folderId > 0 ? '?folder=' . $folderId : ''));
    exit;
}

if (!is_dir($storageDir) && !mkdir($storageDir, 0755, true) && !is_dir($storageDir)) {
    user_error_log('File manager storage directory is unavailable: ' . $storageDir);
    http_response_code(500);
    die('File storage is unavailable. Please contact support.');
}

if (!file_manager_folder_exists($pdo, $currentFolderId)) {
    http_response_code(404);
    die('Folder not found.');
}

if (isset($_GET['preview']) && ctype_digit((string)$_GET['preview'])) {
    $item = file_manager_item($pdo, (int)$_GET['preview']);
    if (!$item || $item['item_type'] !== 'file'
        || !preg_match('/^[a-f0-9]{32}\.[a-z0-9]{1,8}$/', (string)$item['storage_name'])) {
        http_response_code(404);
        die('File not found.');
    }

    $extension = strtolower(pathinfo($item['name'], PATHINFO_EXTENSION));
    $inlineTypes = [
        'pdf' => 'application/pdf',
        'txt' => 'text/plain; charset=UTF-8',
        'csv' => 'text/csv; charset=UTF-8',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'webp' => 'image/webp',
    ];
    if (!isset($inlineTypes[$extension])) {
        http_response_code(415);
        die('This file type cannot be previewed in the browser. Download it to view its contents.');
    }

    $path = $storageDir . '/' . $item['storage_name'];
    if (!is_file($path) || !is_readable($path)) {
        http_response_code(404);
        die('File is unavailable.');
    }

    header('Content-Type: ' . $inlineTypes[$extension]);
    header('Content-Length: ' . filesize($path));
    header('Content-Disposition: inline; filename="' . addcslashes(str_replace(["\r", "\n", '"'], '', (string)$item['name']), '\\') . '"');
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header("Content-Security-Policy: default-src 'none'; style-src 'unsafe-inline'; frame-ancestors 'self'");
    header('Cache-Control: private, no-store');
    readfile($path);
    exit;
}

if (isset($_GET['download']) && ctype_digit((string)$_GET['download'])) {
    $item = file_manager_item($pdo, (int)$_GET['download']);
    if (!$item || $item['item_type'] !== 'file'
        || !preg_match('/^[a-f0-9]{32}\.[a-z0-9]{1,8}$/', (string)$item['storage_name'])) {
        http_response_code(404);
        die('File not found.');
    }

    $path = $storageDir . '/' . $item['storage_name'];
    if (!is_file($path) || !is_readable($path)) {
        http_response_code(404);
        die('File is unavailable.');
    }

    $downloadName = str_replace(["\r", "\n", '"'], '', (string)$item['name']);
    header('Content-Type: ' . ($item['mime_type'] ?: 'application/octet-stream'));
    header('Content-Length: ' . filesize($path));
    header('Content-Disposition: attachment; filename="' . addcslashes($downloadName, '\\') . '"; filename*=UTF-8\'\'' . rawurlencode($downloadName));
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: private, no-store');
    readfile($path);
    exit;
}

$message = $_SESSION['_file_manager_message'] ?? '';
$error = '';
unset($_SESSION['_file_manager_message']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify_post();
    $action = (string)($_POST['action'] ?? '');
    if (!$canManageFiles) {
        http_response_code(403);
        die('Access denied. Your account can browse and download files only.');
    }

    try {
        if ($action === 'create_folder') {
            $name = trim((string)($_POST['name'] ?? ''));
            if (!file_manager_valid_name($name)) {
                $error = 'Enter a valid folder name without path separators or control characters.';
            } elseif (!file_manager_unique_name($pdo, $currentFolderId, $name)) {
                $error = 'An item with that name already exists in this folder.';
            } else {
                $stmt = $pdo->prepare("INSERT INTO file_manager_items (parent_id, item_type, name, created_by) VALUES (?, 'folder', ?, ?)");
                $stmt->execute([$currentFolderId ?: null, $name, auth_current_id()]);
                log_activity($pdo, 'Create', 'file_manager_items', (int)$pdo->lastInsertId(), null, 'Created folder: ' . $name);
                $_SESSION['_file_manager_message'] = 'Folder created.';
                file_manager_redirect($currentFolderId);
            }
        } elseif ($action === 'upload_files') {
            if (!isset($_FILES['files']) || !is_array($_FILES['files']['name'] ?? null)) {
                $error = 'Choose one or more files to upload.';
            } else {
                $count = count($_FILES['files']['name']);
                if ($count > 20) {
                    $error = 'Upload no more than 20 files at a time.';
                } else {
                    $pendingUploads = [];
                    $batchNames = [];
                    $finfo = new finfo(FILEINFO_MIME_TYPE);
                    for ($i = 0; $i < $count; $i++) {
                        if ($_FILES['files']['error'][$i] === UPLOAD_ERR_NO_FILE) {
                            continue;
                        }
                        if ($_FILES['files']['error'][$i] !== UPLOAD_ERR_OK) {
                            throw new RuntimeException('A file could not be uploaded. Check its size and try again.');
                        }

                        $originalName = basename((string)$_FILES['files']['name'][$i]);
                        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
                        $tempPath = (string)$_FILES['files']['tmp_name'][$i];
                        if (!file_manager_valid_name($originalName) || !isset($allowedExtensions[$extension])) {
                            throw new RuntimeException('One or more files use an unsupported filename or file type.');
                        }
                        if ((int)$_FILES['files']['size'][$i] > $maxFileBytes || !is_uploaded_file($tempPath)) {
                            throw new RuntimeException('Each file must be a valid upload and no larger than 25 MB.');
                        }

                        $mimeType = $finfo->file($tempPath);
                        if (!in_array($mimeType, $allowedExtensions[$extension], true)) {
                            throw new RuntimeException('A file extension does not match its detected file type.');
                        }
                        if (!file_manager_unique_name($pdo, $currentFolderId, $originalName)) {
                            throw new RuntimeException('An item named "' . $originalName . '" already exists in this folder.');
                        }
                        $normalizedName = strtolower($originalName);
                        if (isset($batchNames[$normalizedName])) {
                            throw new RuntimeException('The selected files contain duplicate names.');
                        }
                        $batchNames[$normalizedName] = true;
                        $pendingUploads[] = [
                            'name' => $originalName,
                            'extension' => $extension,
                            'temp_path' => $tempPath,
                            'mime_type' => $mimeType,
                        ];
                    }

                    if (!$pendingUploads) {
                        $error = 'Choose at least one file to upload.';
                    } else {
                        $savedPaths = [];
                        $pdo->beginTransaction();
                        try {
                            foreach ($pendingUploads as $upload) {
                                $storageName = bin2hex(random_bytes(16)) . '.' . $upload['extension'];
                                $targetPath = $storageDir . '/' . $storageName;
                                if (!move_uploaded_file($upload['temp_path'], $targetPath)) {
                                    throw new RuntimeException('The server could not save an uploaded file.');
                                }
                                $savedPaths[] = $targetPath;
                            $stmt = $pdo->prepare("INSERT INTO file_manager_items (parent_id, item_type, name, storage_name, mime_type, file_size, created_by) VALUES (?, 'file', ?, ?, ?, ?, ?)");
                            $stmt->execute([
                                $currentFolderId ?: null,
                                    $upload['name'],
                                $storageName,
                                    $upload['mime_type'],
                                (int)filesize($targetPath),
                                auth_current_id(),
                            ]);
                                log_activity($pdo, 'Create', 'file_manager_items', (int)$pdo->lastInsertId(), null, 'Uploaded file: ' . $upload['name']);
                            }
                            $pdo->commit();
                        } catch (Throwable $e) {
                            if ($pdo->inTransaction()) {
                                $pdo->rollBack();
                            }
                            foreach ($savedPaths as $savedPath) {
                                if (is_file($savedPath)) {
                                    unlink($savedPath);
                                }
                            }
                            throw $e;
                        }
                        $_SESSION['_file_manager_message'] = count($pendingUploads) . ' file(s) uploaded.';
                        file_manager_redirect($currentFolderId);
                    }
                }
            }
        } elseif ($action === 'rename') {
            $itemId = (int)($_POST['item_id'] ?? 0);
            $name = trim((string)($_POST['name'] ?? ''));
            $item = file_manager_item($pdo, $itemId);
            if (!$item || (int)($item['parent_id'] ?? 0) !== $currentFolderId) {
                $error = 'The selected item is no longer in this folder.';
            } elseif (!file_manager_valid_name($name)) {
                $error = 'Enter a valid name without path separators or control characters.';
            } elseif (!file_manager_unique_name($pdo, $currentFolderId, $name, $itemId)) {
                $error = 'An item with that name already exists in this folder.';
            } elseif ($item['item_type'] === 'file' && strtolower(pathinfo($name, PATHINFO_EXTENSION)) !== strtolower(pathinfo($item['name'], PATHINFO_EXTENSION))) {
                $error = 'Keep the original file extension when renaming a file.';
            } else {
                $stmt = $pdo->prepare('UPDATE file_manager_items SET name = ? WHERE id = ?');
                $stmt->execute([$name, $itemId]);
                log_activity($pdo, 'Update', 'file_manager_items', $itemId, $item['name'], $name);
                $_SESSION['_file_manager_message'] = 'Item renamed.';
                file_manager_redirect($currentFolderId);
            }
        } elseif ($action === 'move') {
            $itemId = (int)($_POST['item_id'] ?? 0);
            $targetFolderId = (int)($_POST['target_folder'] ?? 0);
            $item = file_manager_item($pdo, $itemId);
            if (!$item || (int)($item['parent_id'] ?? 0) !== $currentFolderId) {
                $error = 'The selected item is no longer in this folder.';
            } elseif (!file_manager_folder_exists($pdo, $targetFolderId)) {
                $error = 'Choose an existing destination folder.';
            } elseif ($targetFolderId === $itemId) {
                $error = 'An item cannot be moved into itself.';
            } elseif ($targetFolderId === $currentFolderId) {
                $error = 'Choose a different destination folder.';
            } elseif (!file_manager_unique_name($pdo, $targetFolderId, $item['name'], $itemId)) {
                $error = 'An item with that name already exists in the destination folder.';
            } else {
                $ancestorId = $targetFolderId;
                while ($ancestorId > 0) {
                    if ($ancestorId === $itemId) {
                        throw new RuntimeException('A folder cannot be moved inside itself or one of its subfolders.');
                    }
                    $stmtParent = $pdo->prepare("SELECT COALESCE(parent_id, 0) FROM file_manager_items WHERE id = ? AND item_type = 'folder'");
                    $stmtParent->execute([$ancestorId]);
                    $ancestorId = (int)$stmtParent->fetchColumn();
                }
                $stmt = $pdo->prepare('UPDATE file_manager_items SET parent_id = ? WHERE id = ?');
                $stmt->execute([$targetFolderId ?: null, $itemId]);
                log_activity($pdo, 'Update', 'file_manager_items', $itemId, 'folder:' . $currentFolderId, 'folder:' . $targetFolderId);
                $_SESSION['_file_manager_message'] = 'Item moved.';
                file_manager_redirect($currentFolderId);
            }
        } elseif ($action === 'delete') {
            $itemId = (int)($_POST['item_id'] ?? 0);
            $item = file_manager_item($pdo, $itemId);
            if (!$item || (int)($item['parent_id'] ?? 0) !== $currentFolderId) {
                $error = 'The selected item is no longer in this folder.';
            } elseif ($item['item_type'] === 'folder') {
                $stmt = $pdo->prepare('SELECT COUNT(*) FROM file_manager_items WHERE parent_id = ?');
                $stmt->execute([$itemId]);
                if ((int)$stmt->fetchColumn() > 0) {
                    $error = 'This folder is not empty. Move or delete its contents first.';
                }
            }

            if ($error === '') {
                if ($item['item_type'] === 'file') {
                    $path = $storageDir . '/' . $item['storage_name'];
                    if (is_file($path) && !unlink($path)) {
                        throw new RuntimeException('The server could not remove the file.');
                    }
                }
                $pdo->prepare('DELETE FROM file_manager_items WHERE id = ?')->execute([$itemId]);
                log_activity($pdo, 'Delete', 'file_manager_items', $itemId, $item['name'], null);
                $_SESSION['_file_manager_message'] = 'Item deleted.';
                file_manager_redirect($currentFolderId);
            }
        } else {
            $error = 'Unknown file manager action.';
        }
    } catch (Throwable $e) {
        user_error_log('File manager operation failed', $e);
        $error = $e instanceof RuntimeException ? $e->getMessage() : generic_error();
    }
}

$search = trim((string)($_GET['q'] ?? ''));
$where = $currentFolderId > 0 ? 'i.parent_id = ?' : 'i.parent_id IS NULL';
$params = $currentFolderId > 0 ? [$currentFolderId] : [];
if ($search !== '') {
    $where .= ' AND i.name LIKE ?';
    $params[] = '%' . $search . '%';
}
$stmt = $pdo->prepare("SELECT i.*, s.full_name AS owner_name,
                              (SELECT COUNT(*) FROM file_manager_items child WHERE child.parent_id = i.id) AS child_count
                       FROM file_manager_items i
                       LEFT JOIN it_staff s ON s.id = i.created_by
                       WHERE {$where}
                       ORDER BY i.item_type = 'file', i.name ASC");
$stmt->execute($params);
$items = $stmt->fetchAll();

$folders = [];
$cursor = $currentFolderId;
while ($cursor > 0) {
    $folder = file_manager_item($pdo, $cursor);
    if (!$folder) {
        break;
    }
    array_unshift($folders, $folder);
    $cursor = (int)($folder['parent_id'] ?? 0);
}
$stmt = $pdo->prepare("SELECT id, name FROM file_manager_items WHERE item_type = 'folder' AND id != ? ORDER BY name ASC");
$stmt->execute([$currentFolderId]);
$allFolders = $stmt->fetchAll();

include APP_ROOT . '/includes/header.php';
?>

<div class="flex flex-col xl:flex-row xl:items-center xl:justify-between gap-4 mb-8">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">File Explorer</h1>
        <p class="text-sm text-gray-500">Manage shared project files in a protected workspace.</p>
    </div>
    <div class="flex flex-wrap gap-2">
        <?php if ($canManageFiles): ?>
        <button onclick="showModal('uploadModal')" class="bg-blue-700 hover:bg-blue-800 text-white font-bold px-4 py-2.5 rounded-xl">Upload files</button>
        <button onclick="showModal('folderModal')" class="bg-white border border-gray-200 hover:bg-gray-50 text-gray-700 font-bold px-4 py-2.5 rounded-xl">New folder</button>
        <?php endif; ?>
    </div>
</div>

<?php if ($message): ?>
    <div class="mb-5 rounded-xl border border-green-200 bg-green-50 p-4 text-sm font-medium text-green-800"><?php echo e($message); ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="mb-5 rounded-xl border border-red-200 bg-red-50 p-4 text-sm font-medium text-red-800"><?php echo e($error); ?></div>
<?php endif; ?>

<div class="bg-white border border-gray-100 rounded-2xl shadow-sm overflow-hidden">
    <div class="p-5 border-b border-gray-100 flex flex-col lg:flex-row lg:items-center justify-between gap-4">
        <nav aria-label="Breadcrumb" class="flex flex-wrap items-center gap-2 text-sm">
            <a class="font-bold text-blue-700 hover:underline" href="file_manager.php">Shared Files</a>
            <?php foreach ($folders as $folder): ?>
                <span class="text-gray-300">/</span>
                <a class="font-semibold text-gray-600 hover:text-blue-700 hover:underline" href="file_manager.php?folder=<?php echo (int)$folder['id']; ?>"><?php echo e($folder['name']); ?></a>
            <?php endforeach; ?>
        </nav>
        <form method="GET" class="flex gap-2">
            <?php if ($currentFolderId > 0): ?><input type="hidden" name="folder" value="<?php echo $currentFolderId; ?>"><?php endif; ?>
            <input type="search" name="q" value="<?php echo e($search); ?>" placeholder="Search this folder..." class="w-full sm:w-64 rounded-xl border border-gray-200 px-3 py-2 text-sm">
            <button class="rounded-xl bg-gray-100 px-4 py-2 text-sm font-bold text-gray-700 hover:bg-gray-200">Search</button>
            <?php if ($search !== ''): ?><a href="file_manager.php<?php echo $currentFolderId ? '?folder=' . $currentFolderId : ''; ?>" class="rounded-xl px-3 py-2 text-sm text-gray-500 hover:bg-gray-100">Clear</a><?php endif; ?>
        </form>
    </div>

    <div class="px-5 py-3 bg-gray-50/70 border-b border-gray-100 flex justify-between text-xs text-gray-500">
        <span><?php echo count($items); ?> item(s)<?php echo $search !== '' ? ' matching search' : ''; ?></span>
        <span><?php echo $canManageFiles ? 'Manage access: upload, organize, rename, move, delete' : 'Read-only access'; ?></span>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead class="text-[10px] uppercase tracking-widest text-gray-500 bg-white">
                <tr>
                    <th class="px-5 py-3">Name</th>
                    <th class="px-5 py-3">Type</th>
                    <th class="px-5 py-3">Size</th>
                    <th class="px-5 py-3">Modified</th>
                    <th class="px-5 py-3">Owner</th>
                    <th class="px-5 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php foreach ($items as $item):
                    $extension = strtolower(pathinfo($item['name'], PATHINFO_EXTENSION));
                    $previewType = in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true)
                        ? 'image'
                        : ($extension === 'pdf' ? 'pdf' : (in_array($extension, ['txt', 'csv'], true) ? 'text' : 'download'));
                ?>
                <tr class="hover:bg-blue-50/40">
                    <td class="px-5 py-4">
                        <?php if ($item['item_type'] === 'folder'): ?>
                            <a href="file_manager.php?folder=<?php echo (int)$item['id']; ?>" class="font-bold text-gray-800 hover:text-blue-700">📁 <?php echo e($item['name']); ?></a>
                            <span class="ml-2 text-xs text-gray-400"><?php echo (int)$item['child_count']; ?> item(s)</span>
                        <?php else: ?>
                            <button type="button" class="font-semibold text-gray-800 hover:text-blue-700 hover:underline" onclick="openPreview(<?php echo (int)$item['id']; ?>, <?php echo e(json_encode($item['name'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)); ?>, '<?php echo e($previewType); ?>')">
                                <?php echo $previewType === 'image' ? '🖼️' : ($previewType === 'pdf' ? '📕' : '📄'); ?> <?php echo e($item['name']); ?>
                            </button>
                        <?php endif; ?>
                    </td>
                    <td class="px-5 py-4 text-sm text-gray-500"><?php echo $item['item_type'] === 'folder' ? 'Folder' : e(strtoupper(pathinfo($item['name'], PATHINFO_EXTENSION)) . ' · ' . ($item['mime_type'] ?: 'File')); ?></td>
                    <td class="px-5 py-4 text-sm text-gray-500"><?php echo $item['item_type'] === 'folder' ? '—' : e(number_format((int)$item['file_size'] / 1024, 1) . ' KB'); ?></td>
                    <td class="px-5 py-4 text-sm text-gray-500"><?php echo e(date('M d, Y H:i', strtotime($item['updated_at']))); ?></td>
                    <td class="px-5 py-4 text-sm text-gray-500"><?php echo e($item['owner_name'] ?? 'Unknown'); ?></td>
                    <td class="px-5 py-4">
                        <div class="flex justify-end gap-2">
                            <?php if ($item['item_type'] === 'file'): ?>
                                <button type="button" onclick="openPreview(<?php echo (int)$item['id']; ?>, <?php echo e(json_encode($item['name'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)); ?>, '<?php echo e($previewType); ?>')" class="rounded-lg bg-violet-50 px-3 py-1.5 text-xs font-bold text-violet-700 hover:bg-violet-100">Preview</button>
                                <a class="rounded-lg bg-blue-50 px-3 py-1.5 text-xs font-bold text-blue-700 hover:bg-blue-100" href="file_manager.php?download=<?php echo (int)$item['id']; ?>">Download</a>
                            <?php endif; ?>
                            <?php if ($canManageFiles): ?>
                                <button type="button" class="rounded-lg bg-gray-100 px-3 py-1.5 text-xs font-bold text-gray-700 hover:bg-gray-200" onclick="openRename(<?php echo (int)$item['id']; ?>, <?php echo e(json_encode($item['name'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)); ?>)">Rename</button>
                                <button type="button" class="rounded-lg bg-gray-100 px-3 py-1.5 text-xs font-bold text-gray-700 hover:bg-gray-200" onclick="openMove(<?php echo (int)$item['id']; ?>)">Move</button>
                                <form method="POST" onsubmit="return confirm('Delete this <?php echo e($item['item_type']); ?>? Folders must be empty first.');">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="action" value="delete"><input type="hidden" name="folder" value="<?php echo $currentFolderId; ?>"><input type="hidden" name="item_id" value="<?php echo (int)$item['id']; ?>">
                                    <button class="rounded-lg bg-red-50 px-3 py-1.5 text-xs font-bold text-red-700 hover:bg-red-100">Delete</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (!$items): ?>
                <tr><td colspan="6" class="px-5 py-16 text-center text-sm text-gray-400">This folder is empty. Upload files or create a folder to get started.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($canManageFiles): ?>
<div id="folderModal" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-gray-900/60 p-4">
    <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl">
        <h2 class="mb-4 text-lg font-bold text-gray-800">Create folder</h2>
        <form method="POST" class="space-y-4">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="action" value="create_folder"><input type="hidden" name="folder" value="<?php echo $currentFolderId; ?>">
            <label class="block text-sm font-semibold text-gray-700">Folder name
                <input autofocus name="name" maxlength="255" required class="mt-2 w-full rounded-xl border border-gray-200 px-4 py-3" placeholder="e.g. Policies">
            </label>
            <div class="flex justify-end gap-2"><button type="button" onclick="closeModal('folderModal')" class="rounded-xl bg-gray-100 px-4 py-2 font-bold text-gray-600">Cancel</button><button class="rounded-xl bg-blue-700 px-4 py-2 font-bold text-white">Create folder</button></div>
        </form>
    </div>
</div>

<div id="uploadModal" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-gray-900/60 p-4">
    <div class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-2xl">
        <h2 class="mb-1 text-lg font-bold text-gray-800">Upload files</h2>
        <p class="mb-4 text-sm text-gray-500">Up to 20 files at a time, 25 MB each. Supported: PDF, text/CSV, Office documents, ZIP, JPG, PNG, WEBP.</p>
        <form method="POST" enctype="multipart/form-data" class="space-y-4">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="action" value="upload_files"><input type="hidden" name="folder" value="<?php echo $currentFolderId; ?>">
            <input type="file" name="files[]" multiple required accept=".pdf,.txt,.csv,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.zip,.jpg,.jpeg,.png,.webp" class="block w-full rounded-xl border border-gray-200 p-3 text-sm">
            <div class="flex justify-end gap-2"><button type="button" onclick="closeModal('uploadModal')" class="rounded-xl bg-gray-100 px-4 py-2 font-bold text-gray-600">Cancel</button><button class="rounded-xl bg-blue-700 px-4 py-2 font-bold text-white">Upload</button></div>
        </form>
    </div>
</div>

<div id="renameModal" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-gray-900/60 p-4">
    <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl">
        <h2 class="mb-4 text-lg font-bold text-gray-800">Rename item</h2>
        <form method="POST" class="space-y-4">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="action" value="rename"><input type="hidden" name="folder" value="<?php echo $currentFolderId; ?>"><input type="hidden" name="item_id" id="renameItemId">
            <input name="name" id="renameItemName" maxlength="255" required class="w-full rounded-xl border border-gray-200 px-4 py-3">
            <div class="flex justify-end gap-2"><button type="button" onclick="closeModal('renameModal')" class="rounded-xl bg-gray-100 px-4 py-2 font-bold text-gray-600">Cancel</button><button class="rounded-xl bg-blue-700 px-4 py-2 font-bold text-white">Save name</button></div>
        </form>
    </div>
</div>

<div id="moveModal" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-gray-900/60 p-4">
    <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl">
        <h2 class="mb-4 text-lg font-bold text-gray-800">Move item</h2>
        <form method="POST" class="space-y-4">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="action" value="move"><input type="hidden" name="folder" value="<?php echo $currentFolderId; ?>"><input type="hidden" name="item_id" id="moveItemId">
            <label class="block text-sm font-semibold text-gray-700">Destination
                <select name="target_folder" required class="mt-2 w-full rounded-xl border border-gray-200 px-4 py-3">
                    <option value="0">Shared Files (root)</option>
                    <?php foreach ($allFolders as $folder): ?>
                        <option value="<?php echo (int)$folder['id']; ?>"><?php echo e($folder['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <div class="flex justify-end gap-2"><button type="button" onclick="closeModal('moveModal')" class="rounded-xl bg-gray-100 px-4 py-2 font-bold text-gray-600">Cancel</button><button class="rounded-xl bg-blue-700 px-4 py-2 font-bold text-white">Move item</button></div>
        </form>
    </div>
</div>
<?php endif; ?>

<div id="previewModal" class="fixed inset-0 z-[60] hidden items-center justify-center bg-slate-950/80 p-2 sm:p-6" role="dialog" aria-modal="true" aria-labelledby="previewTitle" onclick="if(event.target===this)closePreview()">
    <section class="flex h-[94vh] w-full max-w-7xl flex-col overflow-hidden rounded-2xl border border-white/10 bg-slate-900 shadow-2xl">
        <header class="flex items-center justify-between gap-4 border-b border-white/10 bg-slate-950 px-4 py-3 sm:px-6">
            <div class="min-w-0">
                <div class="flex items-center gap-2 text-[10px] font-bold uppercase tracking-[0.2em] text-blue-300">
                    <span id="previewBadge" class="rounded-full bg-blue-500/15 px-2.5 py-1">FILE PREVIEW</span>
                    <span id="previewDetails" class="truncate text-slate-400"></span>
                </div>
                <h2 id="previewTitle" class="mt-1 truncate text-base font-bold text-white sm:text-lg">Preview</h2>
            </div>
            <div class="flex flex-shrink-0 items-center gap-2">
                <a id="previewDownload" class="rounded-lg bg-blue-600 px-3 py-2 text-xs font-bold text-white transition hover:bg-blue-500 sm:text-sm" href="#">Download</a>
                <button type="button" aria-label="Close preview" onclick="closePreview()" class="rounded-lg border border-white/10 bg-white/5 px-3 py-2 text-lg text-slate-300 transition hover:bg-white/10 hover:text-white">&times;</button>
            </div>
        </header>
        <div class="flex min-h-0 flex-1 flex-col bg-slate-800">
            <div id="imageToolbar" class="hidden items-center justify-center gap-2 border-b border-white/10 bg-slate-900 px-3 py-2">
                <button type="button" onclick="zoomPreview(-0.2)" class="rounded-lg bg-white/5 px-3 py-1.5 text-sm font-bold text-white hover:bg-white/10" aria-label="Zoom out">−</button>
                <button type="button" onclick="resetPreviewZoom()" class="rounded-lg bg-white/5 px-3 py-1.5 text-xs font-semibold text-slate-200 hover:bg-white/10">Reset zoom</button>
                <button type="button" onclick="zoomPreview(0.2)" class="rounded-lg bg-white/5 px-3 py-1.5 text-sm font-bold text-white hover:bg-white/10" aria-label="Zoom in">+</button>
                <button type="button" onclick="rotatePreview()" class="rounded-lg bg-white/5 px-3 py-1.5 text-xs font-semibold text-slate-200 hover:bg-white/10">Rotate</button>
                <button type="button" onclick="togglePreviewFullscreen()" class="rounded-lg bg-white/5 px-3 py-1.5 text-xs font-semibold text-slate-200 hover:bg-white/10">Fullscreen</button>
            </div>
            <div id="previewStage" class="flex min-h-0 flex-1 items-center justify-center overflow-auto p-3 sm:p-6">
                <div class="text-sm font-semibold text-slate-300">Select a file to preview it.</div>
            </div>
        </div>
        <footer class="flex items-center justify-between gap-4 border-t border-white/10 bg-slate-950 px-4 py-2 text-[11px] text-slate-400 sm:px-6">
            <span id="previewHint">Private preview — file contents are served only to authenticated users.</span>
            <span>Press Esc to close</span>
        </footer>
    </section>
</div>

<script>
function showModal(id) {
    document.getElementById(id).classList.remove('hidden');
    document.getElementById(id).classList.add('flex');
}
function closeModal(id) {
    document.getElementById(id).classList.add('hidden');
    document.getElementById(id).classList.remove('flex');
}
function openRename(id, name) {
    document.getElementById('renameItemId').value = id;
    document.getElementById('renameItemName').value = name;
    showModal('renameModal');
}
function openMove(id) {
    document.getElementById('moveItemId').value = id;
    showModal('moveModal');
}

let previewScale = 1;
let previewRotation = 0;
let previewRequest = 0;
function openPreview(id, name, type) {
    const modal = document.getElementById('previewModal');
    const stage = document.getElementById('previewStage');
    const toolbar = document.getElementById('imageToolbar');
    const requestId = ++previewRequest;
    previewScale = 1;
    previewRotation = 0;
    document.getElementById('previewTitle').textContent = name;
    document.getElementById('previewDownload').href = 'file_manager.php?download=' + encodeURIComponent(id);
    document.getElementById('previewDetails').textContent = type.toUpperCase() + ' · PRIVATE FILE';
    document.getElementById('previewHint').textContent = 'Private preview — file contents are served only to authenticated users.';
    toolbar.classList.add('hidden');
    toolbar.classList.remove('flex');
    stage.replaceChildren();
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    document.body.classList.add('overflow-hidden');

    if (type === 'image') {
        toolbar.classList.remove('hidden');
        toolbar.classList.add('flex');
        const image = document.createElement('img');
        image.alt = name;
        image.className = 'max-h-full max-w-full rounded-lg object-contain shadow-2xl transition-transform duration-150';
        image.onload = function () {
            if (requestId === previewRequest) {
                document.getElementById('previewDetails').textContent =
                    image.naturalWidth + ' × ' + image.naturalHeight + ' · IMAGE';
            }
        };
        image.onerror = function () {
            if (requestId === previewRequest) showPreviewMessage('This image could not be displayed. The file may be damaged.');
        };
        image.src = 'file_manager.php?preview=' + encodeURIComponent(id);
        stage.appendChild(image);
        return;
    }
    if (type === 'pdf') {
        const frame = document.createElement('iframe');
        frame.title = 'PDF preview: ' + name;
        frame.className = 'h-full min-h-[60vh] w-full rounded-lg bg-white';
        frame.src = 'file_manager.php?preview=' + encodeURIComponent(id);
        stage.appendChild(frame);
        document.getElementById('previewHint').textContent = 'Use the PDF viewer controls to search, zoom, and navigate pages.';
        return;
    }
    if (type === 'text') {
        const loading = document.createElement('div');
        loading.className = 'text-sm font-semibold text-slate-300';
        loading.textContent = 'Loading preview…';
        stage.appendChild(loading);
        fetch('file_manager.php?preview=' + encodeURIComponent(id), {credentials: 'same-origin'})
            .then(function (response) {
                if (!response.ok) throw new Error('Preview request failed');
                return response.text();
            })
            .then(function (contents) {
                if (requestId !== previewRequest) return;
                const pre = document.createElement('pre');
                pre.className = 'm-0 min-h-full w-full whitespace-pre-wrap break-words rounded-xl border border-white/10 bg-slate-950 p-4 text-sm leading-6 text-slate-100 shadow-inner sm:p-6';
                pre.textContent = contents;
                stage.replaceChildren(pre);
                document.getElementById('previewDetails').textContent =
                    new Blob([contents]).size.toLocaleString() + ' bytes · TEXT';
            })
            .catch(function () {
                if (requestId === previewRequest) showPreviewMessage('Preview could not be loaded. Download the file to view it.');
            });
        return;
    }
    showPreviewMessage('This file format cannot be displayed safely in the browser. Download it to open it in the appropriate application.');
}
function showPreviewMessage(message) {
    const panel = document.createElement('div');
    panel.className = 'max-w-xl rounded-2xl border border-white/10 bg-slate-900 p-8 text-center shadow-xl';
    const icon = document.createElement('div');
    icon.className = 'mb-4 text-5xl';
    icon.textContent = '▤';
    const title = document.createElement('h3');
    title.className = 'mb-2 text-lg font-bold text-white';
    title.textContent = 'Preview unavailable';
    const text = document.createElement('p');
    text.className = 'text-sm leading-6 text-slate-300';
    text.textContent = message;
    panel.append(icon, title, text);
    document.getElementById('previewStage').replaceChildren(panel);
}
function zoomPreview(amount) {
    const image = document.querySelector('#previewStage img');
    if (!image) return;
    previewScale = Math.max(0.2, Math.min(5, previewScale + amount));
    image.style.transform = 'scale(' + previewScale + ') rotate(' + previewRotation + 'deg)';
}
function resetPreviewZoom() {
    previewScale = 1;
    previewRotation = 0;
    const image = document.querySelector('#previewStage img');
    if (image) image.style.transform = '';
}
function rotatePreview() {
    const image = document.querySelector('#previewStage img');
    if (!image) return;
    previewRotation = (previewRotation + 90) % 360;
    image.style.transform = 'scale(' + previewScale + ') rotate(' + previewRotation + 'deg)';
}
function togglePreviewFullscreen() {
    const stage = document.getElementById('previewStage');
    if (document.fullscreenElement) {
        document.exitFullscreen();
    } else if (stage.requestFullscreen) {
        stage.requestFullscreen();
    }
}
function closePreview() {
    const modal = document.getElementById('previewModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    document.body.classList.remove('overflow-hidden');
    previewRequest++;
    document.getElementById('previewStage').replaceChildren();
}
document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') closePreview();
});
</script>

<?php include APP_ROOT . '/includes/footer.php'; ?>
