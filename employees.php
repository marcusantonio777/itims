<?php
// employees.php
require_once __DIR__ . '/includes/auth.php';
enforce_authenticated();
require_once 'config/db.php';
enforce_page_access('employees.php');

$message = '';
$error = '';

// Department list for dropdowns
$departments = ['IT', 'HR', 'Finance', 'F&B', 'Front Office', 'Housekeeping', 'Sales & Marketing', 'Engineering'];

// Add Employee
if (isset($_POST['add_employee'])) {
    enforce_role(['admin', 'staff']);
    csrf_verify_post();
    $name = trim($_POST['name']);
    $department = trim($_POST['department']);
    $position = trim($_POST['position']);
    $employee_id = trim($_POST['employee_id']);
    $username = trim($_POST['username']);

    if (!empty($name) && !empty($employee_id)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO employees (name, department, position, employee_id, username) VALUES (?, ?, ?, ?, ?)");
            if ($stmt->execute([$name, $department, $position, $employee_id, $username])) {
                $message = "Employee added successfully.";
            }
        } catch (PDOException $e) {
            user_error_log("Failed to add employee", $e);
            $error = generic_error();
        }
    } else {
        $error = "Name and Employee ID are required.";
    }
}

// Edit Employee
if (isset($_POST['edit_employee'])) {
    enforce_role(['admin', 'staff']);
    csrf_verify_post();
    $id = (int)$_POST['id'];
    $name = trim($_POST['name']);
    $department = trim($_POST['department']);
    $position = trim($_POST['position']);
    $employee_id = trim($_POST['employee_id']);
    $username = trim($_POST['username']);

    if (!empty($name) && !empty($employee_id) && $id > 0) {
        try {
            $stmt = $pdo->prepare("UPDATE employees SET name = ?, department = ?, position = ?, employee_id = ?, username = ? WHERE id = ?");
            if ($stmt->execute([$name, $department, $position, $employee_id, $username, $id])) {
                $message = "Employee updated successfully.";
            }
        } catch (PDOException $e) {
            user_error_log("Failed to update employee", $e);
            $error = generic_error();
        }
    } else {
        $error = "Name and Employee ID are required.";
    }
}

// Delete Employee
if (isset($_POST['delete_employee'])) {
    enforce_role(['admin', 'staff']);
    csrf_verify_post();
    $id = (int)$_POST['id'];
    $delete_ok = true;
    try {
        $pdo->beginTransaction();
        
        $stmtCheck = $pdo->prepare("SELECT name FROM employees WHERE id = ?");
        $stmtCheck->execute([$id]);
        $emp = $stmtCheck->fetch();
        
        if (!$emp) {
            $pdo->rollBack();
            $error = "Employee not found.";
            $delete_ok = false;
        }
        
        if ($delete_ok) {
            $stmtActive = $pdo->prepare("SELECT COUNT(*) FROM accountability_logs l
                WHERE l.status = 'Active'
                  AND (l.user_id = ? OR EXISTS (
                      SELECT 1 FROM accountability_log_employees ale
                      WHERE ale.log_id = l.id AND ale.employee_id = ?
                  ))");
            $stmtActive->execute([$id, $id]);
            if ($stmtActive->fetchColumn() > 0) {
                $pdo->rollBack();
                $error = "Cannot delete employee. They currently have active accountability for assets.";
                $delete_ok = false;
            }
        }
        
        if ($delete_ok) {
            $stmt = $pdo->prepare("DELETE FROM employees WHERE id = ?");
            $stmt->execute([$id]);
            
            if (function_exists('log_activity')) {
                log_activity($pdo, 'Delete', 'employees', $id, null, "Deleted employee: " . $emp['name']);
            }
            
            $pdo->commit();
            $message = "Employee deleted successfully.";
        }
    } catch (Exception $e) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        user_error_log("Failed to delete employee", $e);
        $error = generic_error();
    }
}

// Department Filter Logic
$selected_department = isset($_GET['department']) ? trim($_GET['department']) : '';

include 'includes/header.php';
?>

<div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Employee Management</h1>
        <p class="text-sm text-gray-500">Manage hotel staff and their departments</p>
    </div>
    <button onclick="document.getElementById('addEmployeeModal').classList.remove('hidden')" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 px-6 rounded-xl shadow-sm transition flex items-center">
        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path></svg>
        Add Employee
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

<!-- Filter Bar -->
<div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-100 mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <form method="GET" action="" class="flex items-center space-x-3 w-full sm:w-auto">
        <label for="department" class="text-xs font-black uppercase text-gray-500 whitespace-nowrap">Filter Dept:</label>
        <select name="department" id="department" onchange="this.form.submit()" class="bg-gray-50 border border-gray-200 text-gray-800 text-sm rounded-xl px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none transition w-full sm:w-64">
            <option value="">All Departments</option>
            <?php foreach ($departments as $dept): ?>
                <option value="<?php echo e($dept); ?>" <?php echo $selected_department === $dept ? 'selected' : ''; ?>>
                    <?php echo e($dept); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php if (!empty($selected_department)): ?>
            <a href="employees.php" class="text-xs text-blue-600 hover:underline font-bold whitespace-nowrap">Reset</a>
        <?php endif; ?>
    </form>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-100">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-4 text-left text-[10px] font-black text-gray-500 uppercase tracking-widest">Name</th>
                    <th class="px-6 py-4 text-left text-[10px] font-black text-gray-500 uppercase tracking-widest">Department / Position</th>
                    <th class="px-6 py-4 text-left text-[10px] font-black text-gray-500 uppercase tracking-widest">Employee ID</th>
                    <th class="px-6 py-4 text-left text-[10px] font-black text-gray-500 uppercase tracking-widest">System User</th>
                    <th class="px-6 py-4 text-right text-[10px] font-black text-gray-500 uppercase tracking-widest">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php
                if (!empty($selected_department)) {
                    $stmt = $pdo->prepare("SELECT * FROM employees WHERE department = ? ORDER BY name ASC");
                    $stmt->execute([$selected_department]);
                } else {
                    $stmt = $pdo->query("SELECT * FROM employees ORDER BY name ASC");
                }
                $employees = $stmt->fetchAll();
                
                if ($employees):
                    foreach ($employees as $e):
                ?>
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-800 font-bold border-2 border-blue-200 mr-3">
                                    <?php echo e(strtoupper(substr($e['name'], 0, 1))); ?>
                                </div>
                                <span class="text-sm font-bold text-gray-900"><?php echo e($e['name']); ?></span>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex flex-col">
                                <span class="px-2.5 py-1 text-[10px] font-black uppercase rounded-md bg-gray-100 text-gray-600 border border-gray-200 w-fit mb-1"><?php echo e($e['department']); ?></span>
                                <span class="text-[11px] text-gray-500 font-medium"><?php echo $e['position'] ? e($e['position']) : '<span class="italic text-gray-300">No Position</span>'; ?></span>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-blue-800"><?php echo e($e['employee_id']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $e['username'] ? e($e['username']) : '<span class="italic text-gray-300">N/A</span>'; ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <div class="flex justify-end space-x-2">
                                <a href="employee_details.php?id=<?php echo (int)$e['id']; ?>" class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition" title="View History">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                </a>
                                <button type="button" 
                                        onclick="openEditModal(<?php echo htmlspecialchars(json_encode($e), ENT_QUOTES, 'UTF-8'); ?>)" 
                                        class="p-2 text-amber-600 hover:bg-amber-50 rounded-lg transition" 
                                        title="Edit">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                </button>
                                <form method="POST" action="" class="inline" onsubmit="return confirm('Are you sure you want to delete this employee?');">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="id" value="<?php echo (int)$e['id']; ?>">
                                    <button type="submit" name="delete_employee" class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition" title="Delete">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    </button>
                                </form>
                            </div>
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
                                    <svg class="w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                                </div>
                                <p class="text-gray-500 font-medium italic">No employees found.</p>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Employee Modal -->
<div id="addEmployeeModal" class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm hidden flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-lg overflow-hidden transition-all">
        <div class="bg-[#1e3a8a] p-6 text-white flex justify-between items-center">
            <div>
                <h2 class="text-xl font-bold uppercase tracking-tight">Add New Employee</h2>
                <p class="text-blue-200 text-xs">Register hotel staff member</p>
            </div>
            <button onclick="document.getElementById('addEmployeeModal').classList.add('hidden')" class="bg-white/10 hover:bg-white/20 p-2 rounded-full transition">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
        <form action="" method="POST" class="p-8 space-y-5">
            <?php echo csrf_field(); ?>
            <div>
                <label class="block text-gray-700 text-xs font-black uppercase tracking-widest mb-2">Full Name *</label>
                <input type="text" name="name" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 transition" placeholder="John Doe" required>
            </div>
            <div>
                <label class="block text-gray-700 text-xs font-black uppercase tracking-widest mb-2">Department *</label>
                <select name="department" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 transition" required>
                    <option value="">Select Department</option>
                    <?php foreach ($departments as $dept): ?>
                        <option value="<?php echo e($dept); ?>"><?php echo e($dept); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-gray-700 text-xs font-black uppercase tracking-widest mb-2">Position</label>
                <input type="text" name="position" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 transition" placeholder="e.g. IT Specialist">
            </div>
            <div>
                <label class="block text-gray-700 text-xs font-black uppercase tracking-widest mb-2">Employee ID *</label>
                <input type="text" name="employee_id" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 transition" placeholder="e.g. QH-2026-001" required>
            </div>
            <div>
                <label class="block text-gray-700 text-xs font-black uppercase tracking-widest mb-2">System Username</label>
                <input type="text" name="username" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 transition" placeholder="e.g. jdoe">
            </div>
            <div class="flex items-center justify-end space-x-4 pt-6">
                <button type="button" onclick="document.getElementById('addEmployeeModal').classList.add('hidden')" class="bg-gray-100 hover:bg-gray-200 text-gray-600 font-bold py-3 px-8 rounded-xl transition">Cancel</button>
                <button name="add_employee" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-10 rounded-xl shadow-lg shadow-blue-200 transition" type="submit">Save Employee</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Employee Modal -->
<div id="editEmployeeModal" class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm hidden flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-lg overflow-hidden transition-all">
        <div class="bg-amber-600 p-6 text-white flex justify-between items-center">
            <div>
                <h2 class="text-xl font-bold uppercase tracking-tight">Edit Employee</h2>
                <p class="text-amber-100 text-xs">Update employee information</p>
            </div>
            <button onclick="document.getElementById('editEmployeeModal').classList.add('hidden')" class="bg-white/10 hover:bg-white/20 p-2 rounded-full transition">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
        <form action="" method="POST" class="p-8 space-y-5">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="id" id="edit_id">
            <div>
                <label class="block text-gray-700 text-xs font-black uppercase tracking-widest mb-2">Full Name *</label>
                <input type="text" name="name" id="edit_name" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-amber-500 transition" required>
            </div>
            <div>
                <label class="block text-gray-700 text-xs font-black uppercase tracking-widest mb-2">Department *</label>
                <select name="department" id="edit_department" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-amber-500 transition" required>
                    <option value="">Select Department</option>
                    <?php foreach ($departments as $dept): ?>
                        <option value="<?php echo e($dept); ?>"><?php echo e($dept); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-gray-700 text-xs font-black uppercase tracking-widest mb-2">Position</label>
                <input type="text" name="position" id="edit_position" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-amber-500 transition">
            </div>
            <div>
                <label class="block text-gray-700 text-xs font-black uppercase tracking-widest mb-2">Employee ID *</label>
                <input type="text" name="employee_id" id="edit_employee_id" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-amber-500 transition" required>
            </div>
            <div>
                <label class="block text-gray-700 text-xs font-black uppercase tracking-widest mb-2">System Username</label>
                <input type="text" name="username" id="edit_username" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-amber-500 transition">
            </div>
            <div class="flex items-center justify-end space-x-4 pt-6">
                <button type="button" onclick="document.getElementById('editEmployeeModal').classList.add('hidden')" class="bg-gray-100 hover:bg-gray-200 text-gray-600 font-bold py-3 px-8 rounded-xl transition">Cancel</button>
                <button name="edit_employee" class="bg-amber-600 hover:bg-amber-700 text-white font-bold py-3 px-10 rounded-xl shadow-lg shadow-amber-200 transition" type="submit">Update Employee</button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditModal(employee) {
    document.getElementById('edit_id').value = employee.id;
    document.getElementById('edit_name').value = employee.name;
    document.getElementById('edit_department').value = employee.department;
    document.getElementById('edit_position').value = employee.position || '';
    document.getElementById('edit_employee_id').value = employee.employee_id;
    document.getElementById('edit_username').value = employee.username || '';
    
    document.getElementById('editEmployeeModal').classList.remove('hidden');
}
</script>

<?php include 'includes/footer.php'; ?>