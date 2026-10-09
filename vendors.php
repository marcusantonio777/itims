<?php
require_once __DIR__ . '/includes/auth.php';
enforce_authenticated();
require_once 'config/db.php';
enforce_page_access('vendors.php');

$message = '';
$error = '';

// Add Vendor
if (isset($_POST['add_vendor'])) {
    enforce_role(['admin', 'staff']);
    csrf_verify_post();
    $vendor_name = trim($_POST['vendor_name']);
    $contact_person = trim($_POST['contact_person']);
    $support_email = trim($_POST['support_email']);
    $hotline = trim($_POST['hotline']);

    if (!empty($vendor_name)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO vendors (vendor_name, contact_person, support_email, hotline) VALUES (?, ?, ?, ?)");
            if ($stmt->execute([$vendor_name, $contact_person, $support_email, $hotline])) {
                log_activity($pdo, 'Create', 'vendors', $pdo->lastInsertId(), null, "Added vendor: $vendor_name");
                $message = "Vendor added successfully.";
            } else {
                $error = "Failed to add vendor.";
            }
        } catch (PDOException $e) {
            user_error_log('Failed to add vendor', $e);
            $error = generic_error();
        }
    } else {
        $error = "Vendor name is required.";
    }
}

// Delete Vendor
if (isset($_POST['delete_vendor'])) {
    enforce_role(['admin', 'staff']);
    csrf_verify_post();
    $id = (int)$_POST['delete_vendor'];
    try {
        $stmt = $pdo->prepare("DELETE FROM vendors WHERE id = ?");
        if ($stmt->execute([$id])) {
            log_activity($pdo, 'Delete', 'vendors', $id, null, "Deleted vendor ID: $id");
            $message = "Vendor deleted successfully.";
        } else {
            $error = "Failed to delete vendor. It might be linked to an asset.";
        }
    } catch (PDOException $e) {
        user_error_log('Failed to delete vendor', $e);
        $error = generic_error();
    }
}

include 'includes/header.php';
?>

<div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Vendor Management</h1>
        <p class="text-sm text-gray-500">Manage external suppliers and support contacts</p>
    </div>
    <button onclick="document.getElementById('addVendorModal').classList.remove('hidden')" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 px-6 rounded-xl shadow-sm transition flex items-center">
        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
        Add Vendor
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

<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-100">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-4 text-left text-[10px] font-black text-gray-500 uppercase tracking-widest">Vendor Name</th>
                    <th class="px-6 py-4 text-left text-[10px] font-black text-gray-500 uppercase tracking-widest">Contact Person</th>
                    <th class="px-6 py-4 text-left text-[10px] font-black text-gray-500 uppercase tracking-widest">Support Email</th>
                    <th class="px-6 py-4 text-left text-[10px] font-black text-gray-500 uppercase tracking-widest">Hotline</th>
                    <th class="px-6 py-4 text-right text-[10px] font-black text-gray-500 uppercase tracking-widest">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php
                $stmt = $pdo->query("SELECT * FROM vendors ORDER BY vendor_name ASC");
                $vendors = $stmt->fetchAll();
                
                if ($vendors):
                    foreach ($vendors as $v):
                ?>
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="p-2 bg-blue-50 text-blue-600 rounded-lg mr-3">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                                </div>
                                <span class="text-sm font-bold text-gray-900"><?php echo e($v['vendor_name']); ?></span>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600"><?php echo e($v['contact_person'] ?: '-'); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-blue-600 font-medium"><?php echo e($v['support_email'] ?: '-'); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-mono text-gray-500"><?php echo e($v['hotline'] ?: '-'); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <form method="POST" class="inline-block" onsubmit="return confirm('Are you sure you want to delete this vendor?')">
                                <?php echo csrf_field(); ?>
                                <button type="submit" name="delete_vendor" value="<?php echo e($v['id']); ?>" class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                            </form>
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
                                    <svg class="w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                                </div>
                                <p class="text-gray-500 font-medium italic">No vendors found.</p>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Vendor Modal -->
<div id="addVendorModal" class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm hidden flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-lg overflow-hidden animate-in fade-in zoom-in duration-200">
        <div class="bg-[#1e3a8a] p-6 text-white flex justify-between items-center">
            <div>
                <h2 class="text-xl font-bold uppercase tracking-tight italic text-yellow-400">Add New Vendor</h2>
                <p class="text-blue-200 text-xs">Add supplier or service provider details</p>
            </div>
            <button onclick="document.getElementById('addVendorModal').classList.add('hidden')" class="bg-white/10 hover:bg-white/20 p-2 rounded-full transition">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
        <form action="" method="POST" class="p-8 space-y-5">
            <?php echo csrf_field(); ?>
            <div>
                <label class="block text-gray-700 text-xs font-black uppercase tracking-widest mb-2">Vendor Name *</label>
                <input type="text" name="vendor_name" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 transition" placeholder="e.g. PC Express" required>
            </div>
            <div>
                <label class="block text-gray-700 text-xs font-black uppercase tracking-widest mb-2">Contact Person</label>
                <input type="text" name="contact_person" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 transition" placeholder="Full Name">
            </div>
            <div>
                <label class="block text-gray-700 text-xs font-black uppercase tracking-widest mb-2">Support Email</label>
                <input type="email" name="support_email" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 transition" placeholder="support@vendor.com">
            </div>
            <div>
                <label class="block text-gray-700 text-xs font-black uppercase tracking-widest mb-2">Hotline / Phone</label>
                <input type="text" name="hotline" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 transition" placeholder="e.g. (032) 123-4567">
            </div>
            <div class="flex items-center justify-end space-x-4 pt-6 border-t border-gray-100">
                <button type="button" onclick="document.getElementById('addVendorModal').classList.add('hidden')" class="bg-gray-100 hover:bg-gray-200 text-gray-600 font-bold py-3 px-8 rounded-xl transition">Cancel</button>
                <button name="add_vendor" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-10 rounded-xl shadow-lg shadow-blue-200 transition" type="submit">Save Vendor</button>
            </div>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
