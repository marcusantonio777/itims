<?php
require_once APP_ROOT . '/includes/auth.php';
enforce_authenticated();
require_once APP_ROOT . '/config/db.php';
enforce_page_access('departments.php');

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    enforce_role(['admin', 'staff']);
    csrf_verify_post();

    $departmentName = trim((string)($_POST['department_name'] ?? ''));
    $departmentId = (int)($_POST['department_id'] ?? 0);
    $isEdit = isset($_POST['edit_department']);

    if ($departmentName === '' || strlen($departmentName) > 100) {
        $error = 'Enter a department name up to 100 characters long.';
    } elseif ($isEdit && $departmentId <= 0) {
        $error = 'Select a valid department to update.';
    } else {
        try {
            $duplicate = $pdo->prepare(
                'SELECT id FROM departments WHERE name = ? AND id <> ? LIMIT 1'
            );
            $duplicate->execute([$departmentName, $isEdit ? $departmentId : 0]);
            if ($duplicate->fetchColumn()) {
                $error = 'A department with that name already exists.';
            } elseif (!$isEdit) {
                $stmt = $pdo->prepare('INSERT INTO departments (name) VALUES (?)');
                $stmt->execute([$departmentName]);
                if (function_exists('log_activity')) {
                    log_activity($pdo, 'Create', 'departments', (int)$pdo->lastInsertId(), null, $departmentName);
                }
                $message = 'Department added successfully.';
            } else {
                $pdo->beginTransaction();
                $find = $pdo->prepare('SELECT name FROM departments WHERE id = ? FOR UPDATE');
                $find->execute([$departmentId]);
                $oldName = $find->fetchColumn();
                if ($oldName === false) {
                    $pdo->rollBack();
                    $error = 'Department not found.';
                } else {
                    $update = $pdo->prepare('UPDATE departments SET name = ? WHERE id = ?');
                    $update->execute([$departmentName, $departmentId]);
                    $moveEmployees = $pdo->prepare('UPDATE employees SET department = ? WHERE department = ?');
                    $moveEmployees->execute([$departmentName, $oldName]);
                    if (function_exists('log_activity')) {
                        log_activity($pdo, 'Update', 'departments', $departmentId, (string)$oldName, $departmentName);
                    }
                    $pdo->commit();
                    $message = 'Department updated successfully.';
                }
            }
        } catch (PDOException $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            user_error_log('Failed to save department.', $exception);
            $error = generic_error();
        }
    }
}

$departments = $pdo->query(
    'SELECT d.id, d.name, COUNT(e.id) AS employee_count
     FROM departments d
     LEFT JOIN employees e ON e.department = d.name
     GROUP BY d.id, d.name
     ORDER BY d.name ASC'
)->fetchAll();

include APP_ROOT . '/includes/header.php';
?>

<div class="mb-8">
    <a href="employees.php" class="mb-3 inline-flex text-sm font-semibold text-blue-700 hover:underline">&larr; Back to employees</a>
    <h1 class="text-2xl font-bold text-gray-800">Department Management</h1>
    <p class="mt-1 text-sm text-gray-500">Add departments or rename existing ones. Renaming a department updates its employees.</p>
</div>

<?php if ($message !== ''): ?>
    <div role="status" class="mb-6 rounded-lg border-l-4 border-green-500 bg-green-50 p-4 text-sm font-medium text-green-700"><?php echo e($message); ?></div>
<?php endif; ?>
<?php if ($error !== ''): ?>
    <div role="alert" class="mb-6 rounded-lg border-l-4 border-red-500 bg-red-50 p-4 text-sm font-medium text-red-700"><?php echo e($error); ?></div>
<?php endif; ?>

<section class="mb-6 rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
    <h2 class="mb-4 text-lg font-bold text-gray-800">Add a department</h2>
    <form method="POST" class="flex flex-col gap-3 sm:flex-row sm:items-end">
        <?php echo csrf_field(); ?>
        <div class="flex-1">
            <label for="new_department_name" class="mb-2 block text-xs font-black uppercase tracking-widest text-gray-500">Department name</label>
            <input id="new_department_name" name="department_name" maxlength="100" required
                class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm outline-none transition focus:border-blue-400 focus:bg-white focus:ring-2 focus:ring-blue-100">
        </div>
        <button type="submit" name="add_department" value="1"
            class="rounded-xl bg-blue-600 px-6 py-3 text-sm font-bold text-white transition hover:bg-blue-700">Add Department</button>
    </form>
</section>

<section class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm">
    <div class="border-b border-gray-100 px-6 py-5">
        <h2 class="text-lg font-bold text-gray-800">Departments</h2>
        <p class="mt-1 text-sm text-gray-500"><?php echo count($departments); ?> departments</p>
    </div>
    <div class="divide-y divide-gray-100">
        <?php foreach ($departments as $department): ?>
            <form method="POST" class="flex flex-col gap-3 px-6 py-4 sm:flex-row sm:items-end">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="department_id" value="<?php echo (int)$department['id']; ?>">
                <div class="min-w-0 flex-1">
                    <label for="department_<?php echo (int)$department['id']; ?>" class="mb-2 block text-xs font-black uppercase tracking-widest text-gray-500">Department name</label>
                    <input id="department_<?php echo (int)$department['id']; ?>" name="department_name"
                        value="<?php echo e($department['name']); ?>" maxlength="100" required
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm outline-none transition focus:border-amber-400 focus:bg-white focus:ring-2 focus:ring-amber-100">
                </div>
                <div class="text-sm text-gray-500 sm:w-36"><?php echo (int)$department['employee_count']; ?> employees</div>
                <button type="submit" name="edit_department" value="1"
                    class="rounded-xl bg-amber-500 px-6 py-3 text-sm font-bold text-white transition hover:bg-amber-600">Save Changes</button>
            </form>
        <?php endforeach; ?>
        <?php if (!$departments): ?>
            <p class="px-6 py-8 text-center text-sm text-gray-500">No departments yet. Add one above to get started.</p>
        <?php endif; ?>
    </div>
</section>

<?php include APP_ROOT . '/includes/footer.php'; ?>
