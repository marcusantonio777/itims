<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/db.php';
enforce_authenticated();
enforce_role(['admin']);

$message = '';
$error = '';

if (isset($_POST['save_settings'])) {
    csrf_verify_post();
    $allowHistoryDeletion = isset($_POST['allow_delete_assets_with_history']) ? '1' : '0';
    try {
        $stmt = $pdo->prepare(
            "INSERT INTO system_settings (setting_key, setting_value, updated_by)
             VALUES ('allow_delete_assets_with_history', ?, ?)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_by = VALUES(updated_by)"
        );
        $stmt->execute([$allowHistoryDeletion, auth_current_id()]);
        log_activity(
            $pdo,
            'Update',
            'system_settings',
            null,
            null,
            'Protected asset-history deletion ' . ($allowHistoryDeletion === '1' ? 'enabled' : 'disabled')
        );
        $message = 'Settings saved.';
    } catch (Throwable $e) {
        user_error_log('Save system settings failed', $e);
        $error = generic_error();
    }
}

$setting = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = ?");
$setting->execute(['allow_delete_assets_with_history']);
$allowHistoryDeletion = $setting->fetchColumn() === '1';

include __DIR__ . '/includes/header.php';
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
            <p class="mt-1 text-sm text-gray-500">Manage safeguards for destructive inventory actions.</p>
        </div>

        <form method="POST" class="space-y-5 p-6">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="save_settings" value="1">
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

            <div class="flex justify-end border-t border-gray-100 pt-5">
                <button type="submit" class="rounded-xl bg-blue-700 px-5 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    Save Settings
                </button>
            </div>
        </form>
    </section>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
