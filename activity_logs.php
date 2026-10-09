<?php
// activity_logs.php
require_once __DIR__ . '/includes/auth.php';
enforce_authenticated();
require_once 'config/db.php';
enforce_page_access('activity_logs.php');
include 'includes/header.php';

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$action_filter = isset($_GET['action_type']) ? $_GET['action_type'] : '';

$query = "SELECT l.*, s.full_name FROM audit_logs l 
          LEFT JOIN it_staff s ON l.user_id = s.id 
          WHERE 1=1";
$params = [];

if ($search) {
    $query .= " AND (l.table_affected LIKE ? OR l.new_value LIKE ? OR s.full_name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($action_filter) {
    $query .= " AND l.action_type = ?";
    $params[] = $action_filter;
}

$query .= " ORDER BY l.created_at DESC LIMIT 100";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$logs = $stmt->fetchAll();
?>

<div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">System Activity Logs</h1>
        <p class="text-sm text-gray-500">Track all changes and actions made in the system</p>
    </div>
</div>

<!-- Filters -->
<div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-100 mb-6 flex flex-col md:flex-row gap-4 items-center justify-between">
    <form method="GET" class="flex flex-col md:flex-row gap-4 w-full">
        <div class="relative flex-grow">
            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            </span>
            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search logs..." class="block w-full pl-10 pr-3 py-2 border border-gray-200 rounded-xl leading-5 bg-gray-50 placeholder-gray-400 focus:outline-none focus:bg-white focus:ring-1 focus:ring-blue-500 transition">
        </div>
        <div class="flex items-center gap-2">
            <select name="action_type" class="block w-full md:w-48 px-3 py-2 border border-gray-200 rounded-xl leading-5 bg-gray-50 focus:outline-none focus:ring-1 focus:ring-blue-500 transition">
                <option value="">All Actions</option>
                <option value="Create" <?php echo $action_filter == 'Create' ? 'selected' : ''; ?>>Create</option>
                <option value="Update" <?php echo $action_filter == 'Update' ? 'selected' : ''; ?>>Update</option>
                <option value="Delete" <?php echo $action_filter == 'Delete' ? 'selected' : ''; ?>>Delete</option>
                <option value="Issue" <?php echo $action_filter == 'Issue' ? 'selected' : ''; ?>>Issue</option>
                <option value="Return" <?php echo $action_filter == 'Return' ? 'selected' : ''; ?>>Return</option>
                <option value="Login" <?php echo $action_filter == 'Login' ? 'selected' : ''; ?>>Login</option>
            </select>
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded-xl transition">Filter</button>
            <a href="activity_logs.php" class="bg-gray-100 hover:bg-gray-200 text-gray-600 font-bold py-2 px-6 rounded-xl transition">Reset</a>
        </div>
    </form>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-100">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-4 text-left text-[10px] font-black text-gray-500 uppercase tracking-widest">Timestamp</th>
                    <th class="px-6 py-4 text-left text-[10px] font-black text-gray-500 uppercase tracking-widest">User</th>
                    <th class="px-6 py-4 text-left text-[10px] font-black text-gray-500 uppercase tracking-widest">Action</th>
                    <th class="px-6 py-4 text-left text-[10px] font-black text-gray-500 uppercase tracking-widest">Module</th>
                    <th class="px-6 py-4 text-left text-[10px] font-black text-gray-500 uppercase tracking-widest">Details</th>
                    <th class="px-6 py-4 text-left text-[10px] font-black text-gray-500 uppercase tracking-widest">IP Address</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php if ($logs): foreach ($logs as $l): ?>
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-6 py-4 whitespace-nowrap text-xs text-gray-500 font-medium">
                            <?php echo date('M d, Y H:i:s', strtotime($l['created_at'])); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="h-8 w-8 rounded-full bg-blue-50 flex items-center justify-center text-blue-700 text-xs font-bold border border-blue-100 mr-2">
                                    <?php echo substr($l['full_name'], 0, 1); ?>
                                </div>
                                <span class="text-sm font-bold text-gray-900"><?php echo $l['full_name']; ?></span>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php 
                                $action_classes = [
                                    'Create' => 'bg-green-100 text-green-700',
                                    'Update' => 'bg-blue-100 text-blue-700',
                                    'Delete' => 'bg-red-100 text-red-700',
                                    'Issue' => 'bg-purple-100 text-purple-700',
                                    'Return' => 'bg-indigo-100 text-indigo-700',
                                    'Login' => 'bg-gray-100 text-gray-700',
                                    'Logout' => 'bg-gray-100 text-gray-700'
                                ];
                                $class = $action_classes[$l['action_type']] ?? 'bg-gray-50 text-gray-500';
                            ?>
                            <span class="px-2.5 py-1 text-[10px] font-black uppercase rounded-md <?php echo $class; ?>">
                                <?php echo $l['action_type']; ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-xs font-bold text-gray-600 uppercase tracking-tighter"><?php echo $l['table_affected']; ?></span>
                        </td>
                        <td class="px-6 py-4">
                            <p class="text-xs text-gray-600 max-w-xs truncate" title="<?php echo htmlspecialchars($l['new_value']); ?>">
                                <?php echo htmlspecialchars($l['new_value']); ?>
                            </p>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-xs font-mono text-gray-400">
                            <?php echo $l['ip_address']; ?>
                        </td>
                    </tr>
                <?php endforeach; else: ?>
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center">
                                <div class="p-4 bg-gray-50 rounded-full mb-4">
                                    <svg class="w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                </div>
                                <p class="text-gray-500 font-medium italic">No activity logs found.</p>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
