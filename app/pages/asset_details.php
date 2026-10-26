<?php
// asset_details.php
require_once APP_ROOT . '/includes/auth.php';
enforce_authenticated();
require_once APP_ROOT . '/config/db.php';
enforce_page_access('asset_details.php');

if (!isset($_GET['id'])) {
    header("Location: inventory.php");
    exit;
}

$id = (int)$_GET['id'];
$message = '';
$error = '';

// Fetch Asset Data
$stmt = $pdo->prepare("SELECT a.*, c.category_name, v.vendor_name FROM assets a 
                        LEFT JOIN categories c ON a.category_id = c.id 
                        LEFT JOIN vendors v ON a.vendor_id = v.id 
                        WHERE a.id = ?");
$stmt->execute([$id]);
$asset = $stmt->fetch();

if (!$asset) {
    header("Location: inventory.php");
    exit;
}

// Handle File Upload
if (isset($_POST['upload_file'])) {
    enforce_role(['admin', 'staff']);
    csrf_verify_post();
    $target_dir = "uploads/";
    if (!is_dir($target_dir)) mkdir($target_dir, 0755, true);
    
    $file_type = $_POST['file_type'] ?? '';
    if (!in_array($file_type, ['photo', 'par'], true)) {
        $error = "Invalid upload type.";
    } elseif (!isset($_FILES["file_to_upload"]) || $_FILES["file_to_upload"]["error"] !== 0) {
        $error = "Please choose a file to upload.";
    } else {
        $max_size = 10 * 1024 * 1024;
        if ((int)$_FILES["file_to_upload"]["size"] > $max_size) {
            $error = "File is too large. Max 10MB.";
        } else {
            $extension = strtolower(pathinfo($_FILES["file_to_upload"]["name"], PATHINFO_EXTENSION));
            $allowed_photo = ['jpg', 'jpeg', 'png', 'webp'];
            $allowed_par = ['pdf', 'jpg', 'jpeg', 'png'];

            if ($file_type === 'photo' && !in_array($extension, $allowed_photo, true)) {
                $error = "Invalid photo type. Allowed: JPG, PNG, WEBP.";
            } elseif ($file_type === 'par' && !in_array($extension, $allowed_par, true)) {
                $error = "Invalid PAR file type. Allowed: PDF, JPG, PNG.";
            } else {
                if (in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true) && !getimagesize($_FILES["file_to_upload"]["tmp_name"])) {
                    $error = "Invalid image file.";
                } elseif ($extension === 'pdf' && function_exists('finfo_open')) {
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mime = $finfo ? finfo_file($finfo, $_FILES["file_to_upload"]["tmp_name"]) : null;
                    if ($finfo) finfo_close($finfo);
                    if ($mime && $mime !== 'application/pdf') {
                        $error = "Invalid PDF file.";
                    }
                }
            }
        }
    }

    if (!$error) {
        $filename = time() . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
        $target_file = $target_dir . $filename;

        if (move_uploaded_file($_FILES["file_to_upload"]["tmp_name"], $target_file)) {
            if ($file_type === 'photo') {
                $stmtOld = $pdo->prepare("SELECT photo_path FROM assets WHERE id = ?");
                $stmtOld->execute([$id]);
                $old_photo = $stmtOld->fetchColumn();

                $stmt = $pdo->prepare("UPDATE assets SET photo_path = ? WHERE id = ?");
                $stmt->execute([$target_file, $id]);

                if ($old_photo && $old_photo !== $target_file && file_exists($old_photo)) {
                    unlink($old_photo);
                }
            } else {
                $stmtLog = $pdo->prepare("SELECT id FROM accountability_logs WHERE asset_id = ? AND status = 'Active' ORDER BY date_issued DESC LIMIT 1");
                $stmtLog->execute([$id]);
                $logId = $stmtLog->fetchColumn();
                if (!$logId) {
                    unlink($target_file);
                    $error = "No active issuance record found for this asset.";
                } else {
                    $stmt = $pdo->prepare("UPDATE accountability_logs SET scan_file_path = ? WHERE id = ?");
                    $stmt->execute([$target_file, $logId]);
                }
            }

            if (!$error) {
                $message = "File uploaded successfully.";
            }
        } else {
            $error = "Sorry, there was an error uploading your file.";
        }
    }
}

// Fetch Log History
$stmtLogs = $pdo->prepare("SELECT l.*,
                            COALESCE(
                                (SELECT GROUP_CONCAT(DISTINCT linked_employee.name ORDER BY linked_employee.name SEPARATOR ', ')
                                 FROM accountability_log_employees ale
                                 JOIN employees linked_employee ON linked_employee.id = ale.employee_id
                                 WHERE ale.log_id = l.id),
                                primary_employee.name
                            ) AS employee_name
                            FROM accountability_logs l
                            LEFT JOIN employees primary_employee ON primary_employee.id = l.user_id
                            WHERE l.asset_id = ? ORDER BY l.date_issued DESC");
$stmtLogs->execute([$id]);
$logs = $stmtLogs->fetchAll();

include APP_ROOT . '/includes/header.php';
?>

<div class="mb-8">
    <a href="inventory.php" class="inline-flex items-center text-sm font-bold text-blue-600 hover:text-blue-800 transition">
        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
        Back to Inventory
    </a>
</div>

<?php if ($message): ?>
    <div class="bg-green-50 border-l-4 border-green-500 text-green-700 p-4 rounded-lg mb-6 flex items-center shadow-sm">
        <svg class="w-5 h-5 mr-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
        <span class="text-sm font-medium"><?php echo $message; ?></span>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 rounded-lg mb-6 flex items-center shadow-sm">
        <svg class="w-5 h-5 mr-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path></svg>
        <span class="text-sm font-medium"><?php echo $error; ?></span>
    </div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <!-- Asset Info -->
    <div class="lg:col-span-2 space-y-8">
        <div class="bg-white p-8 rounded-3xl shadow-sm border border-gray-100">
            <div class="flex justify-between items-start mb-8">
                <div>
                    <h1 class="text-3xl font-black text-gray-900 tracking-tight italic"><?php echo $asset['asset_tag']; ?></h1>
                    <p class="text-sm text-gray-500 font-bold uppercase tracking-widest mt-1"><?php echo $asset['category_name']; ?></p>
                </div>
                <?php 
                    $status_classes = [
                        'Available' => 'bg-green-100 text-green-700 border-green-200',
                        'Deployed' => 'bg-blue-100 text-blue-700 border-blue-200',
                        'Repair' => 'bg-yellow-100 text-yellow-700 border-yellow-200',
                        'Retired' => 'bg-red-100 text-red-700 border-red-200'
                    ];
                    $class = $status_classes[$asset['status']] ?? 'bg-gray-100 text-gray-700 border-gray-200';
                ?>
                <span class="px-4 py-1.5 text-xs font-black uppercase rounded-full border <?php echo $class; ?>">
                    <?php echo $asset['status']; ?>
                </span>
            </div>
            
            <div class="grid grid-cols-2 md:grid-cols-3 gap-8 mb-8">
                <div>
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Brand / Model</p>
                    <p class="text-sm font-bold text-gray-800"><?php echo $asset['brand'] . ' ' . $asset['model']; ?></p>
                </div>
                <div>
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Serial Number</p>
                    <p class="text-sm font-mono font-bold text-gray-800"><?php echo $asset['serial_number']; ?></p>
                </div>
                <div>
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Vendor</p>
                    <p class="text-sm font-bold text-gray-800"><?php echo $asset['vendor_name']; ?></p>
                </div>
                <div>
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Warranty Expiry</p>
                    <p class="text-sm font-bold text-gray-800"><?php echo $asset['warranty_expiry'] ?: '-'; ?></p>
                </div>
                <div>
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Next Service</p>
                    <p class="text-sm font-bold text-red-600"><?php echo $asset['next_service_date'] ?: '-'; ?></p>
                </div>
            </div>
            
            <div class="pt-8 border-t border-gray-50">
                <h3 class="text-xs font-black text-gray-400 uppercase tracking-widest mb-4">Technical Specifications</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 bg-gray-50/50 p-6 rounded-2xl">
                    <div>
                        <p class="text-[10px] font-black text-gray-400 uppercase">Processor</p>
                        <p class="text-sm font-bold text-gray-700"><?php echo $asset['processor'] ?: '-'; ?></p>
                    </div>
                    <div>
                        <p class="text-[10px] font-black text-gray-400 uppercase">RAM</p>
                        <p class="text-sm font-bold text-gray-700"><?php echo $asset['ram'] ?: '-'; ?></p>
                    </div>
                    <div>
                        <p class="text-[10px] font-black text-gray-400 uppercase">Storage</p>
                        <p class="text-sm font-bold text-gray-700"><?php echo $asset['storage'] ?: '-'; ?></p>
                    </div>
                    <?php if ($asset['other_specs']): ?>
                    <div class="col-span-full mt-2">
                        <p class="text-[10px] font-black text-gray-400 uppercase">Notes / Other Specs</p>
                        <p class="text-sm text-gray-600 italic"><?php echo nl2br($asset['other_specs']); ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="p-6 border-b border-gray-100 bg-gray-50/50 flex justify-between items-center">
                <h3 class="text-sm font-black text-gray-400 uppercase tracking-widest">Accountability History</h3>
                <svg class="w-5 h-5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-100">
                    <thead class="bg-gray-50/50">
                        <tr>
                            <th class="px-6 py-4 text-left text-[10px] font-black text-gray-500 uppercase tracking-widest">Employee</th>
                            <th class="px-6 py-4 text-left text-[10px] font-black text-gray-500 uppercase tracking-widest">Date Issued</th>
                            <th class="px-6 py-4 text-left text-[10px] font-black text-gray-500 uppercase tracking-widest">Returned</th>
                            <th class="px-6 py-4 text-left text-[10px] font-black text-gray-500 uppercase tracking-widest">Status</th>
                            <th class="px-6 py-4 text-right text-[10px] font-black text-gray-500 uppercase tracking-widest">Scan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <?php foreach ($logs as $l): ?>
                            <tr class="hover:bg-gray-50/50 transition">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900"><?php echo $l['employee_name']; ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-xs text-gray-500"><?php echo date('M d, Y', strtotime($l['date_issued'])); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-xs text-gray-500"><?php echo $l['return_date'] ? date('M d, Y', strtotime($l['return_date'])) : '-'; ?></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-0.5 text-[10px] font-black uppercase rounded <?php echo $l['status'] === 'Active' ? 'bg-blue-50 text-blue-600' : 'bg-gray-50 text-gray-400'; ?>">
                                        <?php echo $l['status']; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <?php if ($l['scan_file_path']): ?>
                                        <a href="<?php echo $l['scan_file_path']; ?>" target="_blank" class="text-blue-600 hover:text-blue-800 font-black text-[10px] uppercase tracking-tighter">View PAR</a>
                                    <?php else: ?>
                                        <span class="text-gray-300 text-[10px] font-black uppercase">No File</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$logs): ?>
                            <tr><td colspan="5" class="px-6 py-8 text-center text-gray-400 text-sm italic">No history found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Sidebar / Uploads -->
    <div class="space-y-8">
        <div class="bg-white p-8 rounded-3xl shadow-sm border border-gray-100">
            <h2 class="text-sm font-black text-gray-400 uppercase tracking-widest mb-6">Asset Photo</h2>
            <div class="relative group">
                <?php if ($asset['photo_path']): ?>
                    <img src="<?php echo $asset['photo_path']; ?>" class="w-full h-auto rounded-2xl mb-6 shadow-md" alt="Asset Photo">
                <?php else: ?>
                    <div class="bg-gray-50 border-2 border-dashed border-gray-200 rounded-2xl h-48 flex flex-col items-center justify-center text-gray-400 mb-6">
                        <svg class="w-12 h-12 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        <span class="text-[10px] font-black uppercase tracking-widest">No Photo Uploaded</span>
                    </div>
                <?php endif; ?>
            </div>
            
            <form action="" method="POST" enctype="multipart/form-data" class="space-y-4">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="file_type" value="photo">
                <input type="file" name="file_to_upload" class="text-xs text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-[10px] file:font-black file:uppercase file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 w-full" required>
                <button name="upload_file" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-black uppercase text-xs tracking-widest py-3 px-4 rounded-xl shadow-lg shadow-blue-100 transition">Update Photo</button>
            </form>
        </div>

        <div class="bg-white p-8 rounded-3xl shadow-sm border border-gray-100">
            <h2 class="text-sm font-black text-gray-400 uppercase tracking-widest mb-2">Signed PAR</h2>
            <p class="text-[10px] text-gray-400 mb-6 leading-relaxed">Upload the scanned copy of the signed Property Acknowledgement Receipt.</p>
            <form action="" method="POST" enctype="multipart/form-data" class="space-y-4">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="file_type" value="par">
                <input type="file" name="file_to_upload" class="text-xs text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-[10px] file:font-black file:uppercase file:bg-green-50 file:text-green-700 hover:file:bg-green-100 w-full" required>
                <button name="upload_file" class="w-full bg-green-600 hover:bg-green-700 text-white font-black uppercase text-xs tracking-widest py-3 px-4 rounded-xl shadow-lg shadow-green-100 transition">Upload Signed PAR</button>
            </form>
        </div>
    </div>
</div>

<?php include APP_ROOT . '/includes/footer.php'; ?>
