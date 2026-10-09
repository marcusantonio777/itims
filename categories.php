<?php
require_once __DIR__ . '/includes/auth.php';
enforce_authenticated();
require_once 'config/db.php';
enforce_page_access('categories.php');

$message = '';
$error = '';

// Add Category
if (isset($_POST['add_category'])) {
    enforce_role(['admin', 'staff']);
    csrf_verify_post();
    $name = trim($_POST['category_name']);
    $prefix = strtoupper(trim($_POST['prefix']));
    $requires_serial_number = isset($_POST['requires_serial_number']) ? 1 : 0;
    $show_technical_specs = isset($_POST['show_technical_specs']) ? 1 : 0;
    $show_other_specs = isset($_POST['show_other_specs']) ? 1 : 0;

    if (!empty($name) && !empty($prefix)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO categories (category_name, prefix, requires_serial_number, show_technical_specs, show_other_specs) VALUES (?, ?, ?, ?, ?)");
            if ($stmt->execute([$name, $prefix, $requires_serial_number, $show_technical_specs, $show_other_specs])) {
                log_activity($pdo, 'Create', 'categories', $pdo->lastInsertId(), null, "Added category: $name ($prefix)");
                $message = "Category added successfully.";
            } else {
                $error = "Failed to add category.";
            }
        } catch (PDOException $e) {
            user_error_log('Failed to add category', $e);
            $error = generic_error();
        }
    } else {
        $error = "Name and Prefix are required.";
    }
}

// Edit Category
if (isset($_POST['edit_category'])) {
    enforce_role(['admin', 'staff']);
    csrf_verify_post();
    $id = (int)$_POST['category_id'];
    $name = trim($_POST['category_name']);
    $prefix = strtoupper(trim($_POST['prefix']));
    $requires_serial_number = isset($_POST['requires_serial_number']) ? 1 : 0;
    $show_technical_specs = isset($_POST['show_technical_specs']) ? 1 : 0;
    $show_other_specs = isset($_POST['show_other_specs']) ? 1 : 0;

    if (!empty($id) && !empty($name) && !empty($prefix)) {
        try {
            $stmt = $pdo->prepare("UPDATE categories SET category_name = ?, prefix = ?, requires_serial_number = ?, show_technical_specs = ?, show_other_specs = ? WHERE id = ?");
            if ($stmt->execute([$name, $prefix, $requires_serial_number, $show_technical_specs, $show_other_specs, $id])) {
                log_activity($pdo, 'Update', 'categories', $id, null, "Updated category: $name ($prefix)");
                $message = "Category updated successfully.";
            } else {
                $error = "Failed to update category.";
            }
        } catch (PDOException $e) {
            user_error_log('Failed to update category', $e);
            $error = generic_error();
        }
    } else {
        $error = "Name and Prefix are required.";
    }
}

// Delete Category
if (isset($_POST['delete_category'])) {
    enforce_role(['admin', 'staff']);
    csrf_verify_post();
    $id = (int)$_POST['delete_category'];
    try {
        $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->execute([$id]);
        log_activity($pdo, 'Delete', 'categories', $id, null, "Deleted category ID: $id");
        $message = "Category deleted successfully.";
    } catch (PDOException $e) {
        user_error_log('Failed to delete category', $e);
        $error = "Failed to delete category. It might be linked to assets.";
    }
}

include 'includes/header.php';
?>

<div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Asset Categories</h1>
        <p class="text-sm text-gray-500">Manage equipment categories and their ID prefixes</p>
    </div>
    <button onclick="document.getElementById('addCategoryModal').classList.remove('hidden')" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 px-6 rounded-xl shadow-sm transition flex items-center">
        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
        Add Category
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
                    <th class="px-6 py-4 text-left text-[10px] font-black text-gray-500 uppercase tracking-widest">Category Name</th>
                    <th class="px-6 py-4 text-left text-[10px] font-black text-gray-500 uppercase tracking-widest">Prefix</th>
                    <th class="px-6 py-4 text-left text-[10px] font-black text-gray-500 uppercase tracking-widest">Field Rules</th>
                    <th class="px-6 py-4 text-left text-[10px] font-black text-gray-500 uppercase tracking-widest">Asset Count</th>
                    <th class="px-6 py-4 text-right text-[10px] font-black text-gray-500 uppercase tracking-widest">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php
                $stmt = $pdo->query("SELECT c.*, (SELECT COUNT(*) FROM assets WHERE category_id = c.id) as asset_count FROM categories c ORDER BY category_name ASC");
                $categories = $stmt->fetchAll();
                
                if ($categories):
                    foreach ($categories as $c):
                ?>
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm font-bold text-gray-900"><?php echo e($c['category_name']); ?></span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2.5 py-1 text-[10px] font-black uppercase rounded-md bg-blue-50 text-blue-700 border border-blue-100"><?php echo e($c['prefix']); ?></span>
                        </td>
                        <td class="px-6 py-4 text-xs text-gray-500">
                            <div class="flex flex-wrap gap-2">
                                <span class="px-2 py-1 rounded-md <?php echo $c['requires_serial_number'] ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-500'; ?>">
                                    <?php echo e($c['requires_serial_number'] ? 'Serial Required' : 'Serial Optional'); ?>
                                </span>
                                <span class="px-2 py-1 rounded-md <?php echo $c['show_technical_specs'] ? 'bg-blue-50 text-blue-700' : 'bg-gray-100 text-gray-500'; ?>">
                                    <?php echo e($c['show_technical_specs'] ? 'Tech Specs On' : 'Tech Specs Off'); ?>
                                </span>
                                <span class="px-2 py-1 rounded-md <?php echo $c['show_other_specs'] ? 'bg-purple-50 text-purple-700' : 'bg-gray-100 text-gray-500'; ?>">
                                    <?php echo e($c['show_other_specs'] ? 'Notes On' : 'Notes Off'); ?>
                                </span>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php echo e($c['asset_count']); ?> Items
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <button
                                type="button"
                                onclick='openEditCategoryModal(<?php echo e(json_encode($c)); ?>)'
                                class="p-2 text-yellow-600 hover:bg-yellow-50 rounded-lg transition inline-block"
                                title="Edit category"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                            </button>
                            <form method="POST" class="inline-block" onsubmit="return confirm('Are you sure you want to delete this category?')">
                                <?php echo csrf_field(); ?>
                                <button type="submit" name="delete_category" value="<?php echo e($c['id']); ?>" class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php 
                    endforeach; 
                else:
                ?>
                    <tr><td colspan="5" class="px-6 py-12 text-center text-gray-400 italic">No categories found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Category Modal -->
<div id="addCategoryModal" class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm hidden flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-md overflow-hidden animate-in fade-in zoom-in duration-200">
        <div class="bg-[#1e3a8a] p-6 text-white flex justify-between items-center">
            <h2 class="text-xl font-bold uppercase tracking-tight italic text-yellow-400">Add New Category</h2>
            <button onclick="document.getElementById('addCategoryModal').classList.add('hidden')" class="bg-white/10 hover:bg-white/20 p-2 rounded-full transition">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
        <form action="" method="POST" class="p-8 space-y-5">
            <?php echo csrf_field(); ?>
            <div>
                <label class="block text-gray-700 text-[10px] font-black uppercase tracking-widest mb-2">Category Name *</label>
                <input type="text" name="category_name" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 transition outline-none" placeholder="e.g. Laptop" required>
            </div>
            <div>
                <label class="block text-gray-700 text-[10px] font-black uppercase tracking-widest mb-2">Prefix (3 letters) *</label>
                <input type="text" name="prefix" maxlength="3" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 transition outline-none" placeholder="e.g. LPT" required>
                <p class="text-[9px] text-gray-400 mt-1 uppercase italic">Used for auto-tagging: PREFIX-YYYY-001</p>
            </div>
            <div class="space-y-3 rounded-2xl border border-gray-100 bg-gray-50 p-4">
                <label class="flex items-center justify-between gap-3 text-sm font-medium text-gray-700">
                    <span>Require serial number</span>
                    <input type="checkbox" name="requires_serial_number" checked class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                </label>
                <label class="flex items-center justify-between gap-3 text-sm font-medium text-gray-700">
                    <span>Show processor / RAM / storage</span>
                    <input type="checkbox" name="show_technical_specs" checked class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                </label>
                <label class="flex items-center justify-between gap-3 text-sm font-medium text-gray-700">
                    <span>Show notes / other specs</span>
                    <input type="checkbox" name="show_other_specs" checked class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                </label>
            </div>
            <div class="flex items-center justify-end space-x-4 pt-6 border-t border-gray-100">
                <button type="button" onclick="document.getElementById('addCategoryModal').classList.add('hidden')" class="bg-gray-100 hover:bg-gray-200 text-gray-600 font-bold py-3 px-8 rounded-xl transition">Cancel</button>
                <button name="add_category" class="bg-[#1e3a8a] hover:bg-blue-900 text-white font-bold py-3 px-10 rounded-xl shadow-lg shadow-blue-200 transition" type="submit">Save Category</button>
            </div>
        </form>
    </div>
</div>

<div id="editCategoryModal" class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm hidden flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-md overflow-hidden animate-in fade-in zoom-in duration-200">
        <div class="bg-yellow-600 p-6 text-white flex justify-between items-center">
            <h2 class="text-xl font-bold uppercase tracking-tight">Edit Category</h2>
            <button type="button" onclick="document.getElementById('editCategoryModal').classList.add('hidden')" class="bg-white/10 hover:bg-white/20 p-2 rounded-full transition">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
        <form action="" method="POST" class="p-8 space-y-5">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="category_id" id="edit_category_id">
            <div>
                <label class="block text-gray-700 text-[10px] font-black uppercase tracking-widest mb-2">Category Name *</label>
                <input type="text" name="category_name" id="edit_category_name" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-yellow-500 transition outline-none" required>
            </div>
            <div>
                <label class="block text-gray-700 text-[10px] font-black uppercase tracking-widest mb-2">Prefix *</label>
                <input type="text" name="prefix" id="edit_category_prefix" maxlength="3" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-yellow-500 transition outline-none" required>
            </div>
            <div class="space-y-3 rounded-2xl border border-gray-100 bg-gray-50 p-4">
                <label class="flex items-center justify-between gap-3 text-sm font-medium text-gray-700">
                    <span>Require serial number</span>
                    <input type="checkbox" name="requires_serial_number" id="edit_requires_serial_number" class="h-4 w-4 rounded border-gray-300 text-yellow-600 focus:ring-yellow-500">
                </label>
                <label class="flex items-center justify-between gap-3 text-sm font-medium text-gray-700">
                    <span>Show processor / RAM / storage</span>
                    <input type="checkbox" name="show_technical_specs" id="edit_show_technical_specs" class="h-4 w-4 rounded border-gray-300 text-yellow-600 focus:ring-yellow-500">
                </label>
                <label class="flex items-center justify-between gap-3 text-sm font-medium text-gray-700">
                    <span>Show notes / other specs</span>
                    <input type="checkbox" name="show_other_specs" id="edit_show_other_specs" class="h-4 w-4 rounded border-gray-300 text-yellow-600 focus:ring-yellow-500">
                </label>
            </div>
            <div class="flex items-center justify-end space-x-4 pt-6 border-t border-gray-100">
                <button type="button" onclick="document.getElementById('editCategoryModal').classList.add('hidden')" class="bg-gray-100 hover:bg-gray-200 text-gray-600 font-bold py-3 px-8 rounded-xl transition">Cancel</button>
                <button name="edit_category" class="bg-yellow-600 hover:bg-yellow-700 text-white font-bold py-3 px-10 rounded-xl shadow-lg shadow-yellow-100 transition" type="submit">Update Category</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openEditCategoryModal(category) {
        document.getElementById('edit_category_id').value = category.id;
        document.getElementById('edit_category_name').value = category.category_name;
        document.getElementById('edit_category_prefix').value = category.prefix;
        document.getElementById('edit_requires_serial_number').checked = Number(category.requires_serial_number) === 1;
        document.getElementById('edit_show_technical_specs').checked = Number(category.show_technical_specs) === 1;
        document.getElementById('edit_show_other_specs').checked = Number(category.show_other_specs) === 1;
        document.getElementById('editCategoryModal').classList.remove('hidden');
    }
</script>

<?php include 'includes/footer.php'; ?>
