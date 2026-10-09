<?php
// supplies.php
require_once __DIR__ . '/includes/auth.php';
require_once 'config/db.php';
enforce_authenticated();
enforce_page_access('supplies.php');

// Check for flash messages stored in session
$message = $_SESSION['flash_message'] ?? '';
$error = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash_message'], $_SESSION['flash_error']);

// Add New Supply Item
if (isset($_POST['add_supply'])) {
    enforce_role(['admin','staff']);
    csrf_verify_post();
    $item_name = trim($_POST['item_name']);
    $category_id = $_POST['category_id'] ?? '';
    $brand = trim($_POST['brand']);
    $model = trim($_POST['model']);
    $unit = trim($_POST['unit']);
    $quantity = (int)$_POST['initial_quantity'];
    $min_stock = (int)$_POST['min_stock_level'];
    $vendor_id = $_POST['vendor_id'] ?: null;

    // Validate required fields
    if (empty($item_name)) {
        $_SESSION['flash_error'] = "Item name is required.";
    } elseif (empty($category_id)) {
        $_SESSION['flash_error'] = "Please select a category or add a new one.";
    } elseif ($category_id === 'new' && empty(trim($_POST['new_category_name'] ?? ''))) {
        $_SESSION['flash_error'] = "Please enter a category name for the new category.";
    } else {
        try {
            $pdo->beginTransaction();

            // Logic Engineering: Check if item already exists to prevent duplicates
            $stmtCheck = $pdo->prepare("SELECT id, item_name, quantity FROM supplies WHERE item_name = ? AND brand = ? AND model = ?");
            $stmtCheck->execute([$item_name, $brand, $model]);
            $existing = $stmtCheck->fetch();

            if ($existing) {
                // If it exists, just update the quantity of the existing record instead of creating a new one
                $supply_id = $existing['id'];
                $stmtUpdate = $pdo->prepare("UPDATE supplies SET quantity = quantity + ? WHERE id = ?");
                $stmtUpdate->execute([$quantity, $supply_id]);

                if ($quantity > 0) {
                    $stmtLog = $pdo->prepare("INSERT INTO supply_logs (supply_id, staff_id, action_type, quantity_changed, notes) VALUES (?, ?, 'Stock In', ?, 'Restocked via Add Item form (Duplicate detected)')");
                    $stmtLog->execute([$supply_id, auth_current_id(), $quantity]);
                }

                log_activity($pdo, 'Update', 'supplies', $supply_id, null, "Restocked existing item: $item_name (Quantity added: $quantity)");
                $pdo->commit();
                $_SESSION['flash_message'] = "Item '$item_name' already exists. We've automatically added the quantity to the existing record instead of creating a duplicate.";
            } else {
                // Handle New Category on the fly
                if ($category_id === 'new') {
                    $new_cat = trim($_POST['new_category_name']);
                    if (empty($new_cat)) {
                        throw new Exception("New category name is required.");
                    }
                    // Ensure prefix is unique
                    $new_prefix = strtoupper(substr($new_cat, 0, 3));
                    $checkPrefix = $pdo->prepare("SELECT COUNT(*) FROM categories WHERE prefix = ?");
                    $checkPrefix->execute([$new_prefix]);
                    if ($checkPrefix->fetchColumn() > 0) {
                        $new_prefix = strtoupper(substr($new_cat, 0, 2)) . rand(0, 9);
                    }
                    
                    $stmtCat = $pdo->prepare("INSERT INTO categories (category_name, prefix) VALUES (?, ?)");
                    $stmtCat->execute([$new_cat, $new_prefix]);
                    $category_id = $pdo->lastInsertId();
                } else {
                    // Validate that category_id exists
                    $category_id = (int)$category_id;
                    $stmtCatCheck = $pdo->prepare("SELECT id FROM categories WHERE id = ?");
                    $stmtCatCheck->execute([$category_id]);
                    if (!$stmtCatCheck->fetch()) {
                        throw new Exception("Invalid category selected. Please select a valid category or create a new one.");
                    }
                }

                // Handle New Vendor on the fly
                if ($vendor_id === 'new') {
                    $new_ven = trim($_POST['new_vendor_name']);
                    $stmtVen = $pdo->prepare("INSERT INTO vendors (vendor_name) VALUES (?)");
                    $stmtVen->execute([$new_ven]);
                    $vendor_id = $pdo->lastInsertId();
                }

                $stmt = $pdo->prepare("INSERT INTO supplies (item_name, category_id, brand, model, unit, quantity, min_stock_level, vendor_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$item_name, (int)$category_id, $brand, $model, $unit, $quantity, $min_stock, $vendor_id]);
                $supply_id = $pdo->lastInsertId();

                if ($quantity > 0) {
                    // Log initial stock movement
                    $stmtLog = $pdo->prepare("INSERT INTO supply_logs (supply_id, staff_id, action_type, quantity_changed, notes) VALUES (?, ?, 'Stock In', ?, 'Initial stock setup')");
                    $stmtLog->execute([$supply_id, auth_current_id(), $quantity]);
                }

                log_activity($pdo, 'Create', 'supplies', $supply_id, null, "Added new supply item: $item_name with initial quantity $quantity");
                $pdo->commit();
                $_SESSION['flash_message'] = "Supply item created successfully.";
            }
        } catch (Exception $e) {
            $pdo->rollBack();
            user_error_log('Add supply failed', $e);
            $_SESSION['flash_error'] = generic_error();
        }
    }
    header("Location: supplies.php");
    exit;
}

// Edit Supply Item
if (isset($_POST['edit_supply'])) {
    enforce_role(['admin','staff']);
    csrf_verify_post();
    $id = (int)$_POST['supply_id'];
    $item_name = trim($_POST['item_name']);
    $brand = trim($_POST['brand']);
    $model = trim($_POST['model']);
    $unit = trim($_POST['unit']);
    $min_stock = (int)$_POST['min_stock_level'];

    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("UPDATE supplies SET item_name = ?, brand = ?, model = ?, unit = ?, min_stock_level = ? WHERE id = ?");
        $stmt->execute([$item_name, $brand, $model, $unit, $min_stock, $id]);
        
        log_activity($pdo, 'Update', 'supplies', $id, null, "Updated supply item details: $item_name");
        $pdo->commit();
        $_SESSION['flash_message'] = "Supply item updated successfully.";
    } catch (PDOException $e) {
        $pdo->rollBack();
        user_error_log('Edit supply failed', $e);
        $_SESSION['flash_error'] = generic_error();
    }
    header("Location: supplies.php");
    exit;
}

// Update Stock (In/Out)
if (isset($_POST['update_stock'])) {
    enforce_role(['admin','staff']);
    csrf_verify_post();
    $supply_id = (int)$_POST['supply_id'];
    $action = $_POST['action_type']; // 'Stock In', 'Stock Out'
    $qty = (int)$_POST['qty'];
    $employee_id = $_POST['employee_id'] ?: null;
    $notes = trim($_POST['notes']);
    $staff_id = auth_current_id();

    try {
        $pdo->beginTransaction();
        
        // Update quantity in supplies table
        if ($action == 'Stock In') {
            $stmt = $pdo->prepare("UPDATE supplies SET quantity = quantity + ? WHERE id = ?");
        } else {
            // Check if enough stock
            $stmtCheck = $pdo->prepare("SELECT quantity FROM supplies WHERE id = ?");
            $stmtCheck->execute([$supply_id]);
            $current_qty = $stmtCheck->fetchColumn();
            
            if ($current_qty < $qty) {
                throw new Exception("Not enough stock available!");
            }
            $stmt = $pdo->prepare("UPDATE supplies SET quantity = quantity - ? WHERE id = ?");
        }
        
        $stmt->execute([$qty, $supply_id]);
        
        // Log the movement
        $stmtLog = $pdo->prepare("INSERT INTO supply_logs (supply_id, staff_id, employee_id, action_type, quantity_changed, notes) VALUES (?, ?, ?, ?, ?, ?)");
        $stmtLog->execute([$supply_id, $staff_id, $employee_id, $action, $qty, $notes]);
        
        log_activity($pdo, 'Update', 'supplies', $supply_id, null, "$action of $qty units");
        
        $pdo->commit();
        $_SESSION['flash_message'] = "Stock updated successfully.";
    } catch (Exception $e) {
        $pdo->rollBack();
        user_error_log('Stock update failed', $e);
        $_SESSION['flash_error'] = generic_error();
    }
    header("Location: supplies.php");
    exit;
}

// Delete Supply Item
if (isset($_POST['delete_supply'])) {
    enforce_role(['admin','staff']);
    csrf_verify_post();
    $id = (int)$_POST['supply_id'];
    try {
        $pdo->beginTransaction();
        
        $stmtCheck = $pdo->prepare("SELECT item_name, quantity FROM supplies WHERE id = ?");
        $stmtCheck->execute([$id]);
        $item = $stmtCheck->fetch();
        
        if (!$item) {
            throw new Exception("Item not found.");
        }
        
        // Check if there's any active issuance (accountability_supplies)
        $stmtActive = $pdo->prepare("SELECT COUNT(*) FROM accountability_supplies ars JOIN accountability_logs l ON ars.log_id = l.id WHERE ars.supply_id = ? AND l.status = 'Active'");
        $stmtActive->execute([$id]);
        if ($stmtActive->fetchColumn() > 0) {
            throw new Exception("Cannot delete item. It is currently issued to one or more employees.");
        }
        
        $stmt = $pdo->prepare("DELETE FROM supplies WHERE id = ?");
        $stmt->execute([$id]);
        
        log_activity($pdo, 'Delete', 'supplies', $id, null, "Deleted supply item: " . $item['item_name']);
        
        $pdo->commit();
        $_SESSION['flash_message'] = "Supply item deleted successfully.";
    } catch (Exception $e) {
        $pdo->rollBack();
        user_error_log('Delete supply failed', $e);
        $_SESSION['flash_error'] = generic_error();
    }
    header("Location: supplies.php");
    exit;
}

include 'includes/header.php';

$categories = $pdo->query("SELECT * FROM categories ORDER BY category_name ASC")->fetchAll();
$vendors = $pdo->query("SELECT * FROM vendors ORDER BY vendor_name ASC")->fetchAll();
$employees = $pdo->query("SELECT * FROM employees ORDER BY name ASC")->fetchAll();
$supplies = $pdo->query("SELECT s.*, c.category_name, v.vendor_name FROM supplies s LEFT JOIN categories c ON s.category_id = c.id LEFT JOIN vendors v ON s.vendor_id = v.id ORDER BY s.item_name ASC")->fetchAll();
?>

<div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Supplies & Inventory</h1>
        <p class="text-sm text-gray-500">Manage non-serialized items and stock levels</p>
    </div>
    <div class="flex flex-col md:flex-row gap-4 w-full md:w-auto">
        <div class="relative group">
            <span class="absolute inset-y-0 left-0 flex items-center pl-3">
                <svg class="w-5 h-5 text-gray-400 group-focus-within:text-blue-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            </span>
            <input type="text" id="supplySearch" placeholder="Search supplies..." class="pl-10 pr-4 py-2.5 bg-white border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all w-full md:w-64 text-sm shadow-sm">
        </div>
        <button onclick="document.getElementById('addSupplyModal').classList.remove('hidden')" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 px-6 rounded-xl shadow-sm transition flex items-center justify-center">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
            New Item
        </button>
    </div>
</div>

<?php if ($message): ?>
    <div class="bg-green-50 border-l-4 border-green-500 text-green-700 p-4 rounded-lg mb-6 flex items-center shadow-sm">
        <svg class="w-5 h-5 mr-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
        <span class="text-sm font-medium"><?php echo htmlspecialchars($message); ?></span>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 rounded-lg mb-6 flex items-center shadow-sm">
        <svg class="w-5 h-5 mr-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path></svg>
        <span class="text-sm font-medium"><?php echo htmlspecialchars($error); ?></span>
    </div>
<?php endif; ?>

<div class="grid grid-cols-1 gap-6">
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-4 text-left text-[10px] font-black text-gray-500 uppercase tracking-widest">Item Details</th>
                        <th class="px-6 py-4 text-left text-[10px] font-black text-gray-500 uppercase tracking-widest">Category</th>
                        <th class="px-6 py-4 text-center text-[10px] font-black text-gray-500 uppercase tracking-widest">Stock Level</th>
                        <th class="px-6 py-4 text-left text-[10px] font-black text-gray-500 uppercase tracking-widest">Status</th>
                        <th class="px-6 py-4 text-right text-[10px] font-black text-gray-500 uppercase tracking-widest">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php if ($supplies): foreach ($supplies as $s): 
                        $is_low = $s['quantity'] <= $s['min_stock_level'];
                    ?>
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="p-2 bg-indigo-50 text-indigo-600 rounded-lg mr-3">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 11m8 4V5"></path></svg>
                                    </div>
                                    <div>
                                        <p class="text-sm font-bold text-gray-900"><?php echo htmlspecialchars($s['item_name']); ?></p>
                                        <p class="text-[10px] text-gray-500 uppercase"><?php echo htmlspecialchars($s['brand'] . ' ' . $s['model']); ?></p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-xs font-medium text-gray-600 bg-gray-100 px-2 py-1 rounded-md"><?php echo htmlspecialchars($s['category_name'] ?? ''); ?></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <p class="text-lg font-black <?php echo $is_low ? 'text-red-600' : 'text-gray-900'; ?>"><?php echo htmlspecialchars($s['quantity']); ?></p>
                                <p class="text-[10px] text-gray-400 font-bold uppercase"><?php echo htmlspecialchars($s['unit']); ?></p>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php if ($s['quantity'] <= 0): ?>
                                    <span class="px-2.5 py-1 text-[10px] font-black uppercase rounded-full bg-red-600 text-white border border-red-700 animate-bounce block text-center">Out of Stock</span>
                                <?php elseif ($is_low): ?>
                                    <span class="px-2.5 py-1 text-[10px] font-black uppercase rounded-full bg-red-100 text-red-700 border border-red-200 animate-pulse block text-center">Low Stock</span>
                                <?php else: ?>
                                    <span class="px-2.5 py-1 text-[10px] font-black uppercase rounded-full bg-green-100 text-green-700 border border-green-200 block text-center">Good</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex justify-end space-x-2">
                                    <button onclick="openStockModal(<?php echo (int)$s['id']; ?>, '<?php echo addslashes(htmlspecialchars($s['item_name'])); ?>')" class="bg-blue-50 text-blue-600 hover:bg-blue-100 px-3 py-1.5 rounded-lg transition font-bold text-xs uppercase tracking-tighter" title="Update Stock">
                                        Update Stock
                                    </button>
                                    <button onclick='openEditSupplyModal(<?php echo json_encode($s); ?>)' class="p-2 text-yellow-600 hover:bg-yellow-50 rounded-lg transition" title="Edit">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                    </button>
                                    <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this supply item?')">
                                        <input type="hidden" name="supply_id" value="<?php echo htmlspecialchars($s['id']); ?>">
                                        <?php echo csrf_field(); ?>
                                        <button type="submit" name="delete_supply" class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition" title="Delete">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; else: ?>
                        <tr><td colspan="5" class="px-6 py-12 text-center text-gray-400 italic">No supply items found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Supply Modal -->
<div id="addSupplyModal" class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm hidden flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-lg overflow-hidden">
        <div class="bg-[#1e3a8a] p-6 text-white flex justify-between items-center">
            <h2 class="text-xl font-bold uppercase tracking-tight italic text-yellow-400">Add Supply Item</h2>
            <button onclick="document.getElementById('addSupplyModal').classList.add('hidden')" class="bg-white/10 hover:bg-white/20 p-2 rounded-full transition">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
        <form action="" method="POST" class="p-8 space-y-4">
            <?php echo csrf_field(); ?>
            <div>
                <label class="block text-gray-700 text-xs font-black uppercase tracking-widest mb-2">Item Name *</label>
                <input type="text" name="item_name" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 transition" required>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-gray-700 text-xs font-black uppercase tracking-widest mb-2">Category *</label>
                    <select name="category_id" id="supply_category_select" onchange="toggleNewSupplyCategory(this)" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 transition" required>
                        <option value="">Select Category</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo htmlspecialchars($cat['id']); ?>"><?php echo htmlspecialchars($cat['category_name']); ?></option>
                        <?php endforeach; ?>
                        <option value="new" class="font-bold text-blue-600">+ Add New Category</option>
                    </select>
                </div>
                <div>
                    <label class="block text-gray-700 text-xs font-black uppercase tracking-widest mb-2">Unit</label>
                    <input type="text" name="unit" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 transition" placeholder="pcs, boxes, etc.">
                </div>
            </div>
            
            <div id="new_supply_category_div" class="hidden animate-in fade-in slide-in-from-top-2">
                <label class="block text-blue-700 text-[10px] font-black uppercase tracking-widest mb-2 italic">New Category Name</label>
                <input type="text" name="new_category_name" class="w-full px-4 py-3 bg-blue-50 border border-blue-200 rounded-xl focus:ring-2 focus:ring-blue-500 transition" placeholder="e.g. Storage Devices">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-gray-700 text-xs font-black uppercase tracking-widest mb-2">Brand</label>
                    <input type="text" name="brand" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 transition">
                </div>
                <div>
                    <label class="block text-gray-700 text-xs font-black uppercase tracking-widest mb-2">Model</label>
                    <input type="text" name="model" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 transition">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-gray-700 text-xs font-black uppercase tracking-widest mb-2">Initial Quantity</label>
                    <input type="number" name="initial_quantity" value="0" min="0" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 transition">
                </div>
                <div>
                    <label class="block text-gray-700 text-xs font-black uppercase tracking-widest mb-2">Min. Stock Level</label>
                    <input type="number" name="min_stock_level" value="5" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 transition">
                </div>
            </div>
            <div>
                <label class="block text-gray-700 text-xs font-black uppercase tracking-widest mb-2">Vendor</label>
                <select name="vendor_id" id="supply_vendor_select" onchange="toggleNewSupplyVendor(this)" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 transition">
                    <option value="">Select Vendor</option>
                    <?php foreach ($vendors as $ven): ?>
                        <option value="<?php echo htmlspecialchars($ven['id']); ?>"><?php echo htmlspecialchars($ven['vendor_name']); ?></option>
                    <?php endforeach; ?>
                    <option value="new" class="font-bold text-blue-600">+ Add New Vendor</option>
                </select>
            </div>
            
            <div id="new_supply_vendor_div" class="hidden animate-in fade-in slide-in-from-top-2">
                <label class="block text-blue-700 text-[10px] font-black uppercase tracking-widest mb-2 italic">New Vendor Name</label>
                <input type="text" name="new_vendor_name" class="w-full px-4 py-3 bg-blue-50 border border-blue-200 rounded-xl focus:ring-2 focus:ring-blue-500 transition" placeholder="e.g. PC Express">
            </div>
            <div class="flex items-center justify-end space-x-4 pt-6 border-t">
                <button type="button" onclick="document.getElementById('addSupplyModal').classList.add('hidden')" class="bg-gray-100 hover:bg-gray-200 text-gray-600 font-bold py-3 px-8 rounded-xl transition">Cancel</button>
                <button name="add_supply" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-10 rounded-xl shadow-lg shadow-blue-200 transition" type="submit">Save Item</button>
            </div>
        </form>
    </div>
</div>

<!-- Stock Update Modal -->
<div id="stockModal" class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm hidden flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-md overflow-hidden">
        <div class="bg-blue-600 p-6 text-white flex justify-between items-center">
            <h2 class="text-xl font-bold uppercase tracking-tight italic" id="stockModalTitle">Update Stock</h2>
            <button onclick="document.getElementById('stockModal').classList.add('hidden')" class="bg-white/10 hover:bg-white/20 p-2 rounded-full transition">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
        <form action="" method="POST" class="p-8 space-y-4">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="supply_id" id="modal_supply_id">
            <div>
                <label class="block text-gray-700 text-xs font-black uppercase tracking-widest mb-2">Action Type *</label>
                <select name="action_type" id="action_type" onchange="toggleEmployeeSelect()" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 transition" required>
                    <option value="Stock In">Stock In (Restock)</option>
                    <option value="Stock Out">Stock Out (Issue/Use)</option>
                </select>
            </div>
            <div>
                <label class="block text-gray-700 text-xs font-black uppercase tracking-widest mb-2">Quantity *</label>
                <input type="number" name="qty" min="1" value="1" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 transition" required>
            </div>
            <div id="employeeSelectDiv" class="hidden">
                <label class="block text-gray-700 text-xs font-black uppercase tracking-widest mb-2">Issue to Employee</label>
                <select name="employee_id" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 transition">
                    <option value="">-- Select Employee --</option>
                    <?php foreach ($employees as $e): ?>
                        <option value="<?php echo htmlspecialchars($e['id']); ?>"><?php echo htmlspecialchars($e['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-gray-700 text-xs font-black uppercase tracking-widest mb-2">Notes / Remarks</label>
                <textarea name="notes" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 transition" rows="2"></textarea>
            </div>
            <div class="flex items-center justify-end space-x-4 pt-6 border-t">
                <button type="button" onclick="document.getElementById('stockModal').classList.add('hidden')" class="bg-gray-100 hover:bg-gray-200 text-gray-600 font-bold py-3 px-8 rounded-xl transition">Cancel</button>
                <button name="update_stock" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-10 rounded-xl shadow-lg shadow-blue-200 transition" type="submit">Update Stock</button>
            </div>
        </form>
    </div>
</div>

<?php 
$recent_logs = $pdo->query("SELECT l.*, s.item_name, st.full_name as staff_name, e.name as employee_name 
                            FROM supply_logs l 
                            JOIN supplies s ON l.supply_id = s.id 
                            JOIN it_staff st ON l.staff_id = st.id 
                            LEFT JOIN employees e ON l.employee_id = e.id 
                            ORDER BY l.created_at DESC LIMIT 10")->fetchAll();
?>

<div class="mt-12">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-xl font-bold text-gray-800">Recent Stock Movements</h2>
            <p class="text-sm text-gray-500">Track the latest changes in supply levels</p>
        </div>
    </div>
    
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-4 text-left text-[10px] font-black text-gray-500 uppercase tracking-widest">Date & Time</th>
                        <th class="px-6 py-4 text-left text-[10px] font-black text-gray-500 uppercase tracking-widest">Item</th>
                        <th class="px-6 py-4 text-left text-[10px] font-black text-gray-500 uppercase tracking-widest">Action</th>
                        <th class="px-6 py-4 text-center text-[10px] font-black text-gray-500 uppercase tracking-widest">Qty</th>
                        <th class="px-6 py-4 text-left text-[10px] font-black text-gray-500 uppercase tracking-widest">Staff / Recipient</th>
                        <th class="px-6 py-4 text-left text-[10px] font-black text-gray-500 uppercase tracking-widest">Notes</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php if ($recent_logs): foreach ($recent_logs as $log): ?>
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-6 py-4 whitespace-nowrap text-xs text-gray-600">
                                <?php echo htmlspecialchars(date('M d, Y h:i A', strtotime($log['created_at']))); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm font-bold text-gray-900"><?php echo htmlspecialchars($log['item_name']); ?></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php if ($log['action_type'] == 'Stock In'): ?>
                                    <span class="px-2 py-1 text-[10px] font-black uppercase rounded bg-green-50 text-green-600 border border-green-100">Stock In</span>
                                <?php else: ?>
                                    <span class="px-2 py-1 text-[10px] font-black uppercase rounded bg-orange-50 text-orange-600 border border-orange-100">Stock Out</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <span class="text-sm font-black <?php echo $log['action_type'] == 'Stock In' ? 'text-green-600' : 'text-orange-600'; ?>">
                                    <?php echo $log['action_type'] == 'Stock In' ? '+' : '-'; ?><?php echo htmlspecialchars($log['quantity_changed']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-xs font-medium text-gray-900"><?php echo htmlspecialchars($log['staff_name']); ?></div>
                                <?php if ($log['employee_name']): ?>
                                    <div class="text-[10px] text-blue-600 font-bold uppercase mt-0.5">To: <?php echo htmlspecialchars($log['employee_name']); ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-xs text-gray-500 italic">
                                <?php echo htmlspecialchars($log['notes'] ?: '-'); ?>
                            </td>
                        </tr>
                    <?php endforeach; else: ?>
                        <tr><td colspan="6" class="px-6 py-8 text-center text-gray-400 italic">No movements recorded yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Edit Supply Modal -->
<div id="editSupplyModal" class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm hidden flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-md overflow-hidden animate-in fade-in zoom-in duration-200">
        <div class="bg-yellow-600 p-6 text-white flex justify-between items-center">
            <div>
                <h2 class="text-xl font-bold uppercase tracking-tight italic">Edit Supply Item</h2>
                <p class="text-yellow-100 text-xs">Update item details</p>
            </div>
            <button onclick="document.getElementById('editSupplyModal').classList.add('hidden')" class="bg-white/10 hover:bg-white/20 p-2 rounded-full transition">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
        <form action="" method="POST" class="p-8 space-y-4">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="supply_id" id="edit_supply_id">
            <div>
                <label class="block text-gray-700 text-xs font-black uppercase tracking-widest mb-2">Item Name *</label>
                <input type="text" name="item_name" id="edit_item_name" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-yellow-500 transition" required>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-gray-700 text-xs font-black uppercase tracking-widest mb-2">Brand</label>
                    <input type="text" name="brand" id="edit_supply_brand" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-yellow-500 transition">
                </div>
                <div>
                    <label class="block text-gray-700 text-xs font-black uppercase tracking-widest mb-2">Model</label>
                    <input type="text" name="model" id="edit_supply_model" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-yellow-500 transition">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-gray-700 text-xs font-black uppercase tracking-widest mb-2">Unit</label>
                    <input type="text" name="unit" id="edit_supply_unit" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-yellow-500 transition">
                </div>
                <div>
                    <label class="block text-gray-700 text-xs font-black uppercase tracking-widest mb-2">Min. Stock Level</label>
                    <input type="number" name="min_stock_level" id="edit_min_stock" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-yellow-500 transition">
                </div>
            </div>
            <div class="flex items-center justify-end space-x-4 pt-6 border-t border-gray-100">
                <button type="button" onclick="document.getElementById('editSupplyModal').classList.add('hidden')" class="bg-gray-100 hover:bg-gray-200 text-gray-600 font-bold py-3 px-8 rounded-xl transition">Cancel</button>
                <button name="edit_supply" class="bg-yellow-600 hover:bg-yellow-700 text-white font-bold py-3 px-10 rounded-xl shadow-lg shadow-yellow-100 transition" type="submit">Update Item</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openEditSupplyModal(supply) {
        document.getElementById('edit_supply_id').value = supply.id;
        document.getElementById('edit_item_name').value = supply.item_name;
        document.getElementById('edit_supply_brand').value = supply.brand;
        document.getElementById('edit_supply_model').value = supply.model;
        document.getElementById('edit_supply_unit').value = supply.unit;
        document.getElementById('edit_min_stock').value = supply.min_stock_level;
        document.getElementById('editSupplyModal').classList.remove('hidden');
    }

    function toggleNewSupplyCategory(select) {
        const div = document.getElementById('new_supply_category_div');
        if (select.value === 'new') {
            div.classList.remove('hidden');
        } else {
            div.classList.add('hidden');
        }
    }

    function toggleNewSupplyVendor(select) {
        const div = document.getElementById('new_supply_vendor_div');
        if (select.value === 'new') {
            div.classList.remove('hidden');
        } else {
            div.classList.add('hidden');
        }
    }

    function openStockModal(id, name) {
        document.getElementById('modal_supply_id').value = id;
        document.getElementById('stockModalTitle').innerText = 'Update Stock: ' + name;
        document.getElementById('stockModal').classList.remove('hidden');
    }

    function toggleEmployeeSelect() {
        const action = document.getElementById('action_type').value;
        const div = document.getElementById('employeeSelectDiv');
        if (action === 'Stock Out') {
            div.classList.remove('hidden');
        } else {
            div.classList.add('hidden');
        }
    }

    // Supply Search Functionality
    document.getElementById('supplySearch').addEventListener('keyup', function() {
        const searchTerm = this.value.toLowerCase();
        const rows = document.querySelectorAll('tbody tr');
        
        rows.forEach(row => {
            const text = row.innerText.toLowerCase();
            if (text.includes(searchTerm)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });
</script>

<?php include 'includes/footer.php'; ?>