<?php
// users.php — Admin-only user management.
require_once APP_ROOT . '/includes/auth.php';
require_once APP_ROOT . '/config/db.php';
enforce_role(ROLE_ADMIN);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify_post();
}

$current_user_id = (int)auth_current_id();
$accessModules = auth_access_modules();
$message = '';
$error   = '';

// ---------------------------------------------------------------------------
// Helper: count active admins (excluding a specific user if needed).
// ---------------------------------------------------------------------------
function count_active_admins(PDO $pdo, $exclude_id = null) {
    $sql = "SELECT COUNT(*) FROM it_staff
            WHERE role = 'admin' AND is_active = 1";
    $params = [];
    if ($exclude_id !== null) {
        $sql .= " AND id != ?";
        $params[] = (int)$exclude_id;
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return (int)$stmt->fetchColumn();
}

// ---------------------------------------------------------------------------
// POST: Create user
// ---------------------------------------------------------------------------
if (isset($_POST['create_user'])) {
    $full_name = trim($_POST['full_name'] ?? '');
    $username  = trim($_POST['username']  ?? '');
    $role      = $_POST['role']            ?? 'staff';
    $password  = $_POST['password']        ?? '';

    if ($full_name === '' || $username === '' || $password === '') {
        $error = 'All fields are required to create a user.';
    } elseif (!in_array($role, ['admin','staff','viewer'], true)) {
        $error = 'Invalid role.';
    } elseif (strlen($password) < 8 || !preg_match('/^(?=.*[a-zA-Z])(?=.*[0-9])/', $password)) {
        $error = 'Password must be at least 8 characters and contain both letters and numbers.';
    } else {
        // Username uniqueness check.
        $stmt = $pdo->prepare("SELECT id FROM it_staff WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            $error = 'A user with that username already exists.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            try {
                $pdo->beginTransaction();
                $ins = $pdo->prepare("INSERT INTO it_staff (full_name, username, password_hash, role, is_active, created_at)
                                      VALUES (?, ?, ?, ?, 1, NOW())");
                $ins->execute([$full_name, $username, $hash, $role]);
                $newId = (int)$pdo->lastInsertId();
                $newUserPermissions = $role === ROLE_ADMIN
                    ? array_keys($accessModules)
                    : ($_POST['page_permissions'] ?? []);
                auth_save_user_page_permissions($pdo, $newId, $newUserPermissions);

                log_activity($pdo, 'UserCreate', 'it_staff', $newId, null,
                    "Created user {$username} ({$role})");

                $pdo->commit();
                $message = "User '{$username}' created successfully.";
            } catch (Exception $e) {
                $pdo->rollBack();
                user_error_log('Create user failed', $e);
                $error = generic_error();
            }
        }
    }
}

// ---------------------------------------------------------------------------
// POST: Edit user (name, username, role, active)
// ---------------------------------------------------------------------------
if (isset($_POST['edit_user'])) {
    $user_id   = (int)($_POST['user_id']  ?? 0);
    $full_name = trim($_POST['full_name'] ?? '');
    $username  = trim($_POST['username']  ?? '');
    $role      = $_POST['role']           ?? 'staff';
    $is_active = (int)($_POST['is_active'] ?? 0);

    if ($user_id <= 0) {
        $error = 'Invalid user ID.';
    } elseif ($full_name === '' || $username === '') {
        $error = 'Full name and username are required.';
    } elseif (!in_array($role, ['admin','staff','viewer'], true)) {
        $error = 'Invalid role.';
    } elseif ($user_id === $current_user_id && ($role !== 'admin' || !$is_active)) {
        $error = 'You cannot deactivate or demote your own account.';
    } else {
        // Fetch existing record for logging + last-admin-protection checks.
        $stmt = $pdo->prepare("SELECT * FROM it_staff WHERE id = ? LIMIT 1");
        $stmt->execute([$user_id]);
        $existing = $stmt->fetch();
        if (!$existing) {
            $error = 'User not found.';
        } else {
            $oldPagePermissions = auth_user_page_permissions($user_id);
            // Username uniqueness (ignore self).
            $stmtU = $pdo->prepare("SELECT id FROM it_staff WHERE username = ? AND id != ? LIMIT 1");
            $stmtU->execute([$username, $user_id]);
            if ($stmtU->fetch()) {
                $error = 'Another user already has that username.';
            } else {
                // Last-admin protection:
                // - Demoting the last active admin away from 'admin'
                // - Deactivating the last active admin
                $changingRoleOrActive =
                    $existing['role'] !== $role ||
                    (int)$existing['is_active'] !== $is_active;

                $becomes_non_admin_or_inactive =
                    $existing['role'] === 'admin' &&
                    ($role !== 'admin' || !$is_active);

                if ($becomes_non_admin_or_inactive && count_active_admins($pdo, $user_id) < 1) {
                    $error = 'Cannot demote or deactivate the last active admin.';
                } else {
                    try {
                        $pdo->beginTransaction();

                        $upd = $pdo->prepare("UPDATE it_staff
                                              SET full_name = ?, username = ?, role = ?, is_active = ?
                                              WHERE id = ?");
                        $upd->execute([$full_name, $username, $role, $is_active, $user_id]);
                        $requestedPagePermissions = $role === ROLE_ADMIN
                            ? array_keys($accessModules)
                            : ($_POST['page_permissions'] ?? []);
                        auth_save_user_page_permissions($pdo, $user_id, $requestedPagePermissions);

                        // Audit: if role changed, specific log type.
                        if ($existing['role'] !== $role) {
                            log_activity($pdo, 'UserRoleChange', 'it_staff', $user_id,
                                "role:{$existing['role']}",
                                "role:{$role} — changed for {$username}");
                        }
                        // Audit: activation/deactivation.
                        if ((int)$existing['is_active'] !== $is_active) {
                            log_activity($pdo, $is_active ? 'UserReactivate' : 'UserDeactivate',
                                'it_staff', $user_id, null,
                                ($is_active ? 'Reactivated' : 'Deactivated') . " user {$username}");
                        }
                        // Audit: general profile update.
                        if ($existing['full_name'] !== $full_name || $existing['username'] !== $username) {
                            log_activity($pdo, 'UserUpdate', 'it_staff', $user_id,
                                "name:{$existing['full_name']};user:{$existing['username']}",
                                "name:{$full_name};user:{$username}");
                        }
                        $permissionsChanged = false;
                        foreach ($accessModules as $moduleKey => $module) {
                            $wasAllowed = !empty($oldPagePermissions[$moduleKey]);
                            $isAllowed = is_array($requestedPagePermissions)
                                && in_array($moduleKey, $requestedPagePermissions, true);
                            if ($wasAllowed !== $isAllowed) {
                                $permissionsChanged = true;
                                break;
                            }
                        }
                        if ($permissionsChanged) {
                            log_activity($pdo, 'UserUpdate', 'it_staff', $user_id, null,
                                "Updated section access permissions for {$username}");
                        }

                        $pdo->commit();
                        $message = "User '{$username}' updated.";
                    } catch (Exception $e) {
                        $pdo->rollBack();
                        user_error_log('Update user failed', $e);
                        $error = generic_error();
                    }
                }
            }
        }
    }
}

// ---------------------------------------------------------------------------
// POST: Reset password (admin sets a new one; no old pass required)
// ---------------------------------------------------------------------------
if (isset($_POST['reset_password'])) {
    $user_id      = (int)($_POST['user_id'] ?? 0);
    $new_password = $_POST['new_password'] ?? '';

    if ($user_id <= 0) {
        $error = 'Invalid user ID.';
    } elseif ($new_password === '') {
        $error = 'New password is required.';
    } elseif (strlen($new_password) < 8 || !preg_match('/^(?=.*[a-zA-Z])(?=.*[0-9])/', $new_password)) {
        $error = 'Password must be at least 8 characters and contain both letters and numbers.';
    } else {
        $stmt = $pdo->prepare("SELECT username FROM it_staff WHERE id = ? LIMIT 1");
        $stmt->execute([$user_id]);
        $row = $stmt->fetch();
        if (!$row) {
            $error = 'User not found.';
        } else {
            try {
                $pdo->beginTransaction();
                $hash = password_hash($new_password, PASSWORD_DEFAULT);
                $upd = $pdo->prepare("UPDATE it_staff SET password_hash = ?, last_password_change = NOW() WHERE id = ?");
                $upd->execute([$hash, $user_id]);
                log_activity($pdo, 'UserPasswordReset', 'it_staff', $user_id, null,
                    "Admin reset password for {$row['username']}");
                $pdo->commit();
                $message = "Password for '{$row['username']}' has been reset.";
            } catch (Exception $e) {
                $pdo->rollBack();
                user_error_log('Reset password failed', $e);
                $error = generic_error();
            }
        }
    }
}

// ---------------------------------------------------------------------------
// POST: Deactivate (no delete; preserve audit trail)
// ---------------------------------------------------------------------------
if (isset($_POST['deactivate_user'])) {
    $user_id = (int)($_POST['user_id'] ?? 0);

    if ($user_id <= 0) {
        $error = 'Invalid user ID.';
    } elseif ($user_id === $current_user_id) {
        $error = 'You cannot deactivate your own account.';
    } else {
        $stmt = $pdo->prepare("SELECT username, role, is_active FROM it_staff WHERE id = ? LIMIT 1");
        $stmt->execute([$user_id]);
        $existing = $stmt->fetch();
        if (!$existing) {
            $error = 'User not found.';
        } else {
            if ($existing['role'] === 'admin' && (int)$existing['is_active'] === 1
                && count_active_admins($pdo, $user_id) < 1) {
                $error = 'Cannot deactivate the last active admin.';
            } else {
                try {
                    $pdo->beginTransaction();
                    $upd = $pdo->prepare("UPDATE it_staff SET is_active = 0 WHERE id = ?");
                    $upd->execute([$user_id]);
                    log_activity($pdo, 'UserDeactivate', 'it_staff', $user_id, null,
                        "Deactivated user {$existing['username']}");
                    $pdo->commit();
                    $message = "User '{$existing['username']}' has been deactivated.";
                } catch (Exception $e) {
                    $pdo->rollBack();
                    user_error_log('Deactivate user failed', $e);
                    $error = generic_error();
                }
            }
        }
    }
}

// ---------------------------------------------------------------------------
// POST: Reactivate (convenience; edit form can also do it, but explicit btn)
// ---------------------------------------------------------------------------
if (isset($_POST['reactivate_user'])) {
    $user_id = (int)($_POST['user_id'] ?? 0);
    if ($user_id <= 0) {
        $error = 'Invalid user ID.';
    } else {
        $stmt = $pdo->prepare("SELECT username FROM it_staff WHERE id = ? LIMIT 1");
        $stmt->execute([$user_id]);
        $existing = $stmt->fetch();
        if (!$existing) {
            $error = 'User not found.';
        } else {
            try {
                $pdo->beginTransaction();
                $upd = $pdo->prepare("UPDATE it_staff SET is_active = 1 WHERE id = ?");
                $upd->execute([$user_id]);
                log_activity($pdo, 'UserReactivate', 'it_staff', $user_id, null,
                    "Reactivated user {$existing['username']}");
                $pdo->commit();
                $message = "User '{$existing['username']}' reactivated.";
            } catch (Exception $e) {
                $pdo->rollBack();
                user_error_log('Reactivate user failed', $e);
                $error = generic_error();
            }
        }
    }
}

// ---------------------------------------------------------------------------
// List users (alphabetical)
// ---------------------------------------------------------------------------
$users = $pdo->query("SELECT id, full_name, username, role, is_active, created_at,
                             last_password_change
                      FROM it_staff
                      ORDER BY is_active DESC, full_name ASC")->fetchAll();

// Pre-fetch user record when an edit/reset action is opened via GET ?edit=id.
$editing = null;
$editingPermissions = [];
$editingHasCustomPermissions = false;
if (isset($_GET['edit']) && ctype_digit((string)$_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT id, full_name, username, role, is_active
                           FROM it_staff WHERE id = ? LIMIT 1");
    $stmt->execute([(int)$_GET['edit']]);
    $editing = $stmt->fetch();
    if ($editing) {
        $editing['role'] = strtolower((string)$editing['role']);
        if ($editing['role'] === 'tech') {
            $editing['role'] = ROLE_STAFF;
        }
        $editingPermissions = auth_user_page_permissions((int)$editing['id']);
        $editingHasCustomPermissions = auth_user_has_custom_page_permissions((int)$editing['id']);
    }
}

$resetting = null;
if (isset($_GET['reset']) && ctype_digit((string)$_GET['reset'])) {
    $stmt = $pdo->prepare("SELECT id, username FROM it_staff WHERE id = ? LIMIT 1");
    $stmt->execute([(int)$_GET['reset']]);
    $resetting = $stmt->fetch();
}

include APP_ROOT . '/includes/header.php';
?>

<div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">User Management</h1>
        <p class="text-sm text-gray-500">Create, edit, and disable system users</p>
    </div>
    <button onclick="document.getElementById('createModal').classList.remove('hidden')"
            class="bg-[#1e3a8a] hover:bg-blue-900 text-white font-bold py-2.5 px-6 rounded-xl shadow-sm transition flex items-center">
        <svg class="w-5 h-5 mr-2 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
        </svg>
        Create User
    </button>
</div>

<?php if ($message): ?>
    <div class="bg-green-50 border-l-4 border-green-500 text-green-700 p-4 rounded-lg mb-6 flex items-center shadow-sm">
        <svg class="w-5 h-5 mr-3" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
        </svg>
        <span class="text-sm font-medium"><?php echo e($message); ?></span>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 rounded-lg mb-6 flex items-center shadow-sm">
        <svg class="w-5 h-5 mr-3" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
        </svg>
        <span class="text-sm font-medium"><?php echo e($error); ?></span>
    </div>
<?php endif; ?>

<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="p-6 border-b border-gray-100 bg-gray-50/50 flex justify-between items-center">
        <h3 class="text-sm font-black text-gray-400 uppercase tracking-widest">All Users (<?php echo count($users); ?>)</h3>
        <p class="text-[10px] font-black uppercase text-gray-400">
            Active Admins: <span class="text-blue-700"><?php echo count_active_admins($pdo); ?></span>
        </p>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-100">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-4 text-left text-[10px] font-black text-gray-500 uppercase tracking-widest">User</th>
                    <th class="px-6 py-4 text-left text-[10px] font-black text-gray-500 uppercase tracking-widest">Username</th>
                    <th class="px-6 py-4 text-left text-[10px] font-black text-gray-500 uppercase tracking-widest">Role</th>
                    <th class="px-6 py-4 text-left text-[10px] font-black text-gray-500 uppercase tracking-widest">Status</th>
                    <th class="px-6 py-4 text-left text-[10px] font-black text-gray-500 uppercase tracking-widest">Last PW Change</th>
                    <th class="px-6 py-4 text-right text-[10px] font-black text-gray-500 uppercase tracking-widest">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php foreach ($users as $u):
                    $is_self = (int)$u['id'] === $current_user_id;
                    $badge_class = [
                        'admin'  => 'bg-purple-100 text-purple-700 border-purple-200',
                        'staff'  => 'bg-blue-100   text-blue-700   border-blue-200',
                        'viewer' => 'bg-gray-100   text-gray-700   border-gray-200',
                    ][$u['role']] ?? 'bg-gray-100 text-gray-700 border-gray-200';
                ?>
                    <tr class="hover:bg-gray-50 transition <?php echo (int)$u['is_active'] ? '' : 'opacity-60 bg-red-50/30'; ?>">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-800 font-black border-2 border-blue-200 mr-3">
                                    <?php echo e(substr($u['full_name'], 0, 1)); ?>
                                </div>
                                <div>
                                    <p class="text-sm font-bold text-gray-900">
                                        <?php echo e($u['full_name']); ?>
                                        <?php if ($is_self): ?>
                                            <span class="ml-2 text-[9px] font-black uppercase text-blue-600 bg-blue-50 px-2 py-0.5 rounded-full border border-blue-100">(You)</span>
                                        <?php endif; ?>
                                    </p>
                                    <p class="text-[10px] text-gray-400 font-black uppercase tracking-widest">
                                        Created <?php echo e(date('M d, Y', strtotime($u['created_at']))); ?>
                                    </p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <code class="text-xs font-bold text-gray-700 bg-gray-50 px-2 py-1 rounded-lg border border-gray-100">
                                <?php echo e($u['username']); ?>
                            </code>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2.5 py-1 text-[10px] font-black uppercase rounded-md border <?php echo $badge_class; ?>">
                                <?php echo e($u['role']); ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php if ((int)$u['is_active']): ?>
                                <span class="px-2.5 py-1 text-[10px] font-black uppercase rounded-md border border-green-200 bg-green-50 text-green-700">Active</span>
                            <?php else: ?>
                                <span class="px-2.5 py-1 text-[10px] font-black uppercase rounded-md border border-red-200 bg-red-50 text-red-700">Disabled</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-xs text-gray-500 font-medium">
                            <?php echo $u['last_password_change']
                                ? e(date('M d, Y', strtotime($u['last_password_change'])))
                                : '<span class="italic text-gray-400">Never</span>'; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <div class="flex justify-end space-x-1.5">
                                <a href="?edit=<?php echo (int)$u['id']; ?>"
                                   class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition"
                                   title="Edit user">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path>
                                    </svg>
                                </a>
                                <a href="?reset=<?php echo (int)$u['id']; ?>"
                                   class="p-2 text-amber-600 hover:bg-amber-50 rounded-lg transition"
                                   title="Reset password">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
                                    </svg>
                                </a>
                                <?php if (!$is_self): ?>
                                    <?php if ((int)$u['is_active']): ?>
                                        <form action="" method="POST" class="inline" onsubmit="return confirm('Deactivate this user? They will no longer be able to log in.');">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="user_id" value="<?php echo (int)$u['id']; ?>">
                                            <button type="submit" name="deactivate_user"
                                                    class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition"
                                                    title="Deactivate">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path>
                                                </svg>
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <form action="" method="POST" class="inline" onsubmit="return confirm('Reactivate this user?');">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="user_id" value="<?php echo (int)$u['id']; ?>">
                                            <button type="submit" name="reactivate_user"
                                                    class="p-2 text-green-600 hover:bg-green-50 rounded-lg transition"
                                                    title="Reactivate">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                </svg>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Create Modal -->
<div id="createModal" class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm hidden flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto animate-in fade-in zoom-in duration-200">
        <div class="bg-[#1e3a8a] p-6 text-white flex justify-between items-center">
            <div>
                <h2 class="text-xl font-bold uppercase tracking-tight italic text-yellow-400">Create New User</h2>
                <p class="text-blue-200 text-xs">Create a new operator account for <?php echo e(app_branding($pdo)['product_name']); ?></p>
            </div>
            <button type="button" onclick="document.getElementById('createModal').classList.add('hidden')"
                    class="bg-white/10 hover:bg-white/20 p-2 rounded-full transition">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <form action="" method="POST" class="p-8 space-y-5">
            <?php echo csrf_field(); ?>
            <div class="grid grid-cols-2 gap-4">
                <div class="col-span-2">
                    <label class="block text-gray-700 text-xs font-black uppercase tracking-widest mb-2">Full Name</label>
                    <input type="text" name="full_name" required
                        class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 transition"
                        placeholder="e.g. Juan Dela Cruz">
                </div>
                <div>
                    <label class="block text-gray-700 text-xs font-black uppercase tracking-widest mb-2">Username</label>
                    <input type="text" name="username" required
                        class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 transition"
                        placeholder="juan.cruz">
                </div>
                <div>
                    <label class="block text-gray-700 text-xs font-black uppercase tracking-widest mb-2">Role</label>
                    <select id="newUserRole" name="role" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 transition" required>
                        <option value="staff" selected>Staff</option>
                        <option value="viewer">Viewer (Read Only)</option>
                        <option value="admin">Administrator</option>
                    </select>
                </div>
                <div class="col-span-2">
                    <label class="block text-gray-700 text-xs font-black uppercase tracking-widest mb-2">Initial Password</label>
                    <input type="password" name="password" required minlength="8"
                        class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 transition"
                        placeholder="At least 8 chars, letters + numbers">
                    <p class="text-[10px] text-gray-400 italic mt-1">User will be able to change this on their first login from their profile.</p>
                </div>
            </div>
            <fieldset class="border border-gray-200 rounded-xl p-4">
                <legend class="px-2 text-xs font-black uppercase tracking-widest text-gray-600">Section Access</legend>
                <p class="text-xs text-gray-500 mb-3">Choose the sections this user can open. Staff can add, edit, and remove records in enabled sections; viewers can view enabled sections only. Administrators always retain full access.</p>
                <div class="grid grid-cols-2 gap-2">
                    <?php foreach ($accessModules as $moduleKey => $module): ?>
                        <label class="flex items-center gap-2 text-sm text-gray-700">
                            <input type="checkbox" name="page_permissions[]" value="<?php echo e($moduleKey); ?>"
                                   data-access-module class="rounded border-gray-300 text-blue-600"
                                   checked>
                            <?php echo e($module['label']); ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </fieldset>
            <div class="flex items-center justify-end space-x-4 pt-4 border-t border-gray-100">
                <button type="button" onclick="document.getElementById('createModal').classList.add('hidden')"
                    class="bg-gray-100 hover:bg-gray-200 text-gray-600 font-bold py-3 px-8 rounded-xl transition">Cancel</button>
                <button type="submit" name="create_user"
                    class="bg-[#1e3a8a] hover:bg-blue-900 text-white font-bold py-3 px-10 rounded-xl shadow-lg shadow-blue-200 transition">Create User</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Modal -->
<?php if ($editing): ?>
<div id="editModal" class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto animate-in fade-in zoom-in duration-200">
        <div class="bg-[#1e3a8a] p-6 text-white flex justify-between items-center">
            <div>
                <h2 class="text-xl font-bold uppercase tracking-tight italic text-yellow-400">Edit User</h2>
                <p class="text-blue-200 text-xs">Update account details for <?php echo e($editing['full_name']); ?></p>
            </div>
            <a href="users.php" class="bg-white/10 hover:bg-white/20 p-2 rounded-full transition">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </a>
        </div>
        <form action="" method="POST" class="p-8 space-y-5">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="user_id" value="<?php echo (int)$editing['id']; ?>">
            <div class="grid grid-cols-2 gap-4">
                <div class="col-span-2">
                    <label class="block text-gray-700 text-xs font-black uppercase tracking-widest mb-2">Full Name</label>
                    <input type="text" name="full_name" required value="<?php echo e($editing['full_name']); ?>"
                        class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 transition">
                </div>
                <div>
                    <label class="block text-gray-700 text-xs font-black uppercase tracking-widest mb-2">Username</label>
                    <input type="text" name="username" required value="<?php echo e($editing['username']); ?>"
                        class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 transition">
                </div>
                <div>
                    <label class="block text-gray-700 text-xs font-black uppercase tracking-widest mb-2">Role</label>
                    <select id="editUserRole" data-custom-permissions="<?php echo $editingHasCustomPermissions ? '1' : '0'; ?>"
                        name="role" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 transition" required
                        <?php if ((int)$editing['id'] === $current_user_id) echo 'disabled title="You cannot change your own role"'; ?>>
                        <option value="admin"  <?php echo $editing['role'] === 'admin'  ? 'selected' : ''; ?>>Administrator</option>
                        <option value="staff"  <?php echo $editing['role'] === 'staff'  ? 'selected' : ''; ?>>Staff</option>
                        <option value="viewer" <?php echo $editing['role'] === 'viewer' ? 'selected' : ''; ?>>Viewer (Read Only)</option>
                    </select>
                    <?php if ((int)$editing['id'] === $current_user_id): ?>
                        <input type="hidden" name="role" value="admin">
                    <?php endif; ?>
                </div>
                <div class="col-span-2">
                    <label class="flex items-center space-x-3 cursor-pointer">
                        <input type="checkbox" name="is_active" value="1"
                            class="w-5 h-5 rounded text-blue-600 focus:ring-blue-500 border-gray-300"
                            <?php echo (int)$editing['is_active'] ? 'checked' : ''; ?>
                            <?php if ((int)$editing['id'] === $current_user_id) echo 'disabled'; ?>>
                        <span class="text-sm font-bold text-gray-800">Account is active (can log in)</span>
                    </label>
                    <?php if ((int)$editing['id'] === $current_user_id): ?>
                        <input type="hidden" name="is_active" value="1">
                        <p class="text-[10px] text-amber-600 italic mt-1">You cannot disable your own account.</p>
                    <?php endif; ?>
                </div>
            </div>
            <fieldset class="border border-gray-200 rounded-xl p-4">
                <legend class="px-2 text-xs font-black uppercase tracking-widest text-gray-600">Section Access</legend>
                <p class="text-xs text-gray-500 mb-3">Choose the sections this user can open. Staff can add, edit, and remove records in enabled sections; viewers can view enabled sections only. Administrators always retain full access.</p>
                <div class="grid grid-cols-2 gap-2">
                    <?php foreach ($accessModules as $moduleKey => $module): ?>
                        <label class="flex items-center gap-2 text-sm text-gray-700">
                            <input type="checkbox" name="page_permissions[]" value="<?php echo e($moduleKey); ?>"
                                   class="rounded border-gray-300 text-blue-600"
                                   <?php echo !empty($editingPermissions[$moduleKey]) ? 'checked' : ''; ?>>
                            <?php echo e($module['label']); ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </fieldset>
            <div class="flex items-center justify-end space-x-4 pt-4 border-t border-gray-100">
                <a href="users.php" class="bg-gray-100 hover:bg-gray-200 text-gray-600 font-bold py-3 px-8 rounded-xl transition text-center">Cancel</a>
                <button type="submit" name="edit_user"
                    class="bg-[#1e3a8a] hover:bg-blue-900 text-white font-bold py-3 px-10 rounded-xl shadow-lg shadow-blue-200 transition">Save Changes</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- Reset Password Modal -->
<?php if ($resetting): ?>
<div id="resetModal" class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-md overflow-hidden animate-in fade-in zoom-in duration-200">
        <div class="bg-amber-600 p-6 text-white flex justify-between items-center">
            <div>
                <h2 class="text-xl font-bold uppercase tracking-tight italic">Reset Password</h2>
                <p class="text-amber-100 text-xs">Set a new password for <?php echo e($resetting['username']); ?></p>
            </div>
            <a href="users.php" class="bg-white/10 hover:bg-white/20 p-2 rounded-full transition">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </a>
        </div>
        <form action="" method="POST" class="p-8 space-y-5">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="user_id" value="<?php echo (int)$resetting['id']; ?>">
            <div>
                <label class="block text-gray-700 text-xs font-black uppercase tracking-widest mb-2">New Password</label>
                <input type="password" name="new_password" required minlength="8"
                    class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 transition"
                    placeholder="At least 8 chars, letters + numbers">
                <p class="text-[10px] text-gray-400 italic mt-1">Communicate the new password to the user through a secure channel.</p>
            </div>
            <div class="flex items-center justify-end space-x-4 pt-4 border-t border-gray-100">
                <a href="users.php" class="bg-gray-100 hover:bg-gray-200 text-gray-600 font-bold py-3 px-8 rounded-xl transition text-center">Cancel</a>
                <button type="submit" name="reset_password"
                    class="bg-amber-600 hover:bg-amber-700 text-white font-bold py-3 px-10 rounded-xl shadow-lg shadow-amber-200 transition">Reset Password</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<script>
document.getElementById('newUserRole')?.addEventListener('change', function () {
    document.querySelectorAll('#createModal [data-access-module]').forEach(function (checkbox) {
        checkbox.checked = this.value !== 'viewer' || checkbox.value === 'issuance';
    }, this);
});
document.getElementById('editUserRole')?.addEventListener('change', function () {
    if (this.dataset.customPermissions === '1') return;
    document.querySelectorAll('#editModal input[name="page_permissions[]"]').forEach(function (checkbox) {
        checkbox.checked = this.value !== 'viewer' || checkbox.value === 'issuance';
    }, this);
});
</script>

<?php include APP_ROOT . '/includes/footer.php'; ?>
