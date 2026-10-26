<?php
require_once APP_ROOT . '/includes/auth.php';
enforce_authenticated();
require_once APP_ROOT . '/config/db.php';

$message = '';
$error = '';

if (isset($_POST['change_password'])) {
    csrf_verify_post();
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = "All password fields are required.";
    } elseif ($new_password !== $confirm_password) {
        $error = "New passwords do not match.";
    } elseif (strlen($new_password) < 8) {
        $error = "New password must be at least 8 characters.";
    } elseif (!preg_match('/^(?=.*[a-zA-Z])(?=.*[0-9])/', $new_password)) {
        $error = "Password must be alphanumeric (letters and numbers).";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT password_hash FROM it_staff WHERE id = ?");
            $stmt->execute([auth_current_id()]);
            $user = $stmt->fetch();

            if ($user && password_verify($current_password, $user['password_hash'])) {
                $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
                $stmtUpdate = $pdo->prepare("UPDATE it_staff SET password_hash = ?, last_password_change = NOW() WHERE id = ?");
                if ($stmtUpdate->execute([$new_hash, auth_current_id()])) {
                    session_regenerate_id(true);
                    log_activity($pdo, 'UserPasswordReset', 'it_staff', auth_current_id(), null, 'Password changed by user');
                    $message = "Password changed successfully!";
                } else {
                    $error = "Failed to update password.";
                }
            } else {
                $error = "Current password is incorrect.";
            }
        } catch (Throwable $e) {
            user_error_log('Profile password change failed', $e);
            $error = generic_error();
        }
    }
}

if (isset($_POST['update_profile'])) {
    csrf_verify_post();
    $full_name = trim($_POST['full_name']);

    if (!empty($full_name)) {
        try {
            $stmt = $pdo->prepare("UPDATE it_staff SET full_name = ? WHERE id = ?");
            if ($stmt->execute([$full_name, auth_current_id()])) {
                $_SESSION['staff_name'] = $full_name;
                log_activity($pdo, 'Update', 'it_staff', auth_current_id(), null, "Profile name updated: $full_name");
                $message = "Profile updated successfully!";
            } else {
                $error = "Failed to update profile.";
            }
        } catch (Throwable $e) {
            user_error_log('Profile update failed', $e);
            $error = generic_error();
        }
    } else {
        $error = "Full name is required.";
    }
}

try {
    $stmt = $pdo->prepare("SELECT * FROM it_staff WHERE id = ?");
    $stmt->execute([auth_current_id()]);
    $staff = $stmt->fetch();
} catch (Throwable $e) {
    user_error_log('Profile data fetch failed', $e);
    $error = generic_error();
    $staff = [];
}

include APP_ROOT . '/includes/header.php';
?>

<div class="max-w-4xl mx-auto">
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-800">Account Settings</h1>
        <p class="text-sm text-gray-500">Manage your profile and security preferences</p>
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

    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        <div class="md:col-span-1">
            <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100 text-center">
                <div class="h-24 w-24 rounded-full bg-blue-100 flex items-center justify-center text-blue-800 text-3xl font-black border-4 border-white shadow-lg mx-auto mb-4">
                    <?php echo e(substr($staff['full_name'] ?? '', 0, 1)); ?>
                </div>
                <h3 class="text-lg font-bold text-gray-900"><?php echo e($staff['full_name'] ?? ''); ?></h3>
                <p class="text-xs font-black text-gray-400 uppercase tracking-widest mt-1"><?php echo e($staff['role'] ?? ''); ?></p>
                <div class="mt-6 pt-6 border-t border-gray-50 text-left space-y-3">
                    <div class="flex items-center text-xs text-gray-500">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                        <span>Username: <b class="text-gray-700"><?php echo e($staff['username'] ?? ''); ?></b></span>
                    </div>
                    <div class="flex items-center text-xs text-gray-500">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        <span>Last Password: <b class="text-gray-700"><?php echo !empty($staff['last_password_change']) ? e(date('M d, Y', strtotime($staff['last_password_change']))) : 'Never'; ?></b></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="md:col-span-2 space-y-6">
            <div class="bg-white p-8 rounded-3xl shadow-sm border border-gray-100">
                <h3 class="text-sm font-black text-gray-400 uppercase tracking-widest mb-6">Basic Information</h3>
                <form action="" method="POST" class="space-y-4">
                    <?php echo csrf_field(); ?>
                    <div>
                        <label class="block text-gray-700 text-xs font-black uppercase tracking-widest mb-2">Full Name</label>
                        <input type="text" name="full_name" value="<?php echo e($staff['full_name'] ?? ''); ?>" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 transition" required>
                    </div>
                    <div class="pt-2">
                        <button name="update_profile" class="bg-[#1e3a8a] hover:bg-blue-900 text-white font-bold py-2.5 px-8 rounded-xl shadow-sm transition" type="submit">Save Profile</button>
                    </div>
                </form>
            </div>

            <div class="bg-white p-8 rounded-3xl shadow-sm border border-gray-100">
                <h3 class="text-sm font-black text-gray-400 uppercase tracking-widest mb-6 text-red-500">Security & Password</h3>
                <form action="" method="POST" class="space-y-4">
                    <?php echo csrf_field(); ?>
                    <div>
                        <label class="block text-gray-700 text-xs font-black uppercase tracking-widest mb-2">Current Password</label>
                        <input type="password" name="current_password" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 transition" required>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-gray-700 text-xs font-black uppercase tracking-widest mb-2">New Password</label>
                            <input type="password" name="new_password" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 transition" required>
                        </div>
                        <div>
                            <label class="block text-gray-700 text-xs font-black uppercase tracking-widest mb-2">Confirm New Password</label>
                            <input type="password" name="confirm_password" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 transition" required>
                        </div>
                    </div>
                    <p class="text-[10px] text-gray-400 italic">Password must be at least 8 characters long and contain both letters and numbers.</p>
                    <div class="pt-2">
                        <button name="change_password" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2.5 px-8 rounded-xl shadow-sm transition" type="submit">Update Password</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include APP_ROOT . '/includes/footer.php'; ?>
