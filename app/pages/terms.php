<?php
// terms.php — Admin-only accountability terms editor.
require_once APP_ROOT . '/includes/auth.php';
require_once APP_ROOT . '/config/db.php';
enforce_role(ROLE_ADMIN);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify_post();
}

$current_user_id = (int)auth_current_id();
$message = '';
$error   = '';

// ---------------------------------------------------------------------------
// POST: Save a row (either default or per-category).
// ---------------------------------------------------------------------------
if (isset($_POST['save_terms'])) {
    $category_id = $_POST['category_id'] ?? '';
    $category_id = $category_id === '' ? null : (int)$category_id;
    $terms_text  = trim($_POST['terms_text'] ?? '');

    if ($terms_text === '') {
        $error = 'Terms text cannot be blank. If you do not want category-specific terms, delete the row instead.';
    } else {
        try {
            $pdo->beginTransaction();

            // Check if terms already exist for this category (or default null)
            if ($category_id === null) {
                $stmt = $pdo->prepare("SELECT id, terms_text FROM accountability_terms WHERE category_id IS NULL LIMIT 1");
                $stmt->execute();
            } else {
                $stmt = $pdo->prepare("SELECT id, terms_text FROM accountability_terms WHERE category_id = ? LIMIT 1");
                $stmt->execute([$category_id]);
            }
            $existing = $stmt->fetch();

            if ($existing) {
                // Update existing row
                $upd = $pdo->prepare("UPDATE accountability_terms 
                                      SET terms_text = ?, updated_by = ?, updated_at = NOW() 
                                      WHERE id = ?");
                $upd->execute([$terms_text, $current_user_id, (int)$existing['id']]);
                
                $label = $category_id === null ? 'default' : "category {$category_id}";
                
                if (function_exists('log_activity')) {
                    log_activity($pdo, 'TermsUpdate', 'accountability_terms', (int)$existing['id'], null, "Updated terms for {$label}");
                }
                
                $message = 'Terms updated successfully.';
            } else {
                // Insert new row
                $ins = $pdo->prepare("INSERT INTO accountability_terms (category_id, terms_text, updated_by) VALUES (?, ?, ?)");
                $ins->execute([$category_id, $terms_text, $current_user_id]);
                $newId = (int)$pdo->lastInsertId();
                
                $label = $category_id === null ? 'default' : "category {$category_id}";
                
                if (function_exists('log_activity')) {
                    log_activity($pdo, 'TermsCreate', 'accountability_terms', $newId, null, "Created terms for {$label}");
                }
                
                $message = 'Terms created successfully.';
            }

            $pdo->commit();
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            user_error_log('Save terms failed', $e);
            $error = generic_error();
        }
    }
}

// ---------------------------------------------------------------------------
// POST: Delete a category-specific row (NOT the default row).
// ---------------------------------------------------------------------------
if (isset($_POST['delete_terms'])) {
    $terms_id = (int)($_POST['terms_id'] ?? 0);
    if ($terms_id <= 0) {
        $error = 'Invalid terms row.';
    } else {
        // Protect the default row from deletion.
        $stmt = $pdo->prepare("SELECT category_id FROM accountability_terms WHERE id = ? LIMIT 1");
        $stmt->execute([$terms_id]);
        $row = $stmt->fetch();

        if (!$row) {
            $error = 'Terms row not found.';
        } elseif ($row['category_id'] === null) {
            $error = 'You cannot delete the default terms. Edit them instead.';
        } else {
            try {
                $pdo->beginTransaction();
                
                $del = $pdo->prepare("DELETE FROM accountability_terms WHERE id = ?");
                $del->execute([$terms_id]);

                if (function_exists('log_activity')) {
                    log_activity($pdo, 'TermsDelete', 'accountability_terms', $terms_id, null, "Removed category-specific terms (cat {$row['category_id']})");
                }

                $pdo->commit();
                $message = 'Category-specific terms deleted. Issuances for this category will now fall back to default terms.';
            } catch (Exception $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                user_error_log('Delete terms failed', $e);
                $error = generic_error();
            }
        }
    }
}

// ---------------------------------------------------------------------------
// Read: list existing rows + categories for new-row form.
// ---------------------------------------------------------------------------
$terms_rows = $pdo->query("SELECT at.id, at.category_id, at.terms_text, at.updated_at,
                                   c.category_name,
                                   s.full_name AS updated_by_name
                            FROM accountability_terms at
                            LEFT JOIN categories c ON at.category_id = c.id
                            LEFT JOIN it_staff s ON at.updated_by = s.id
                            ORDER BY at.category_id IS NULL DESC, c.category_name ASC")->fetchAll();

$categories = $pdo->query("SELECT id, category_name FROM categories ORDER BY category_name ASC")->fetchAll();

// Determine which categories don't yet have a custom row (for the "Add" dropdown).
$used_category_ids = [];
foreach ($terms_rows as $r) {
    if ($r['category_id'] !== null) {
        $used_category_ids[(int)$r['category_id']] = true;
    }
}
$available_categories = array_values(array_filter($categories, fn($c) => !isset($used_category_ids[(int)$c['id']])));

// If user clicked "Edit" on an existing row, preload the form with it.
$edit_id = isset($_GET['edit']) && ctype_digit((string)$_GET['edit']) ? (int)$_GET['edit'] : null;
$edit_row = null;
if ($edit_id) {
    $stmt = $pdo->prepare("SELECT id, category_id, terms_text FROM accountability_terms WHERE id = ? LIMIT 1");
    $stmt->execute([$edit_id]);
    $edit_row = $stmt->fetch();
}

include APP_ROOT . '/includes/header.php';
?>

<div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Accountability Terms Editor</h1>
        <p class="text-sm text-gray-500">Manage the terms &amp; conditions printed on PAR forms per item category</p>
    </div>
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

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <!-- Left: Existing rows -->
    <div class="lg:col-span-2">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="p-6 border-b border-gray-100 bg-gray-50/50 flex justify-between items-center">
                <h3 class="text-sm font-black text-gray-400 uppercase tracking-widest">Terms Library</h3>
                <span class="text-[10px] font-black uppercase text-gray-400"><?php echo count($terms_rows); ?> row(s)</span>
            </div>
            <div class="divide-y divide-gray-50">
                <?php foreach ($terms_rows as $r):
                    $is_default = $r['category_id'] === null;
                ?>
                    <div class="p-6 hover:bg-gray-50/40 transition">
                        <div class="flex justify-between items-start mb-3">
                            <div class="flex items-center gap-2">
                                <?php if ($is_default): ?>
                                    <span class="px-2.5 py-1 text-[10px] font-black uppercase rounded-md border border-yellow-200 bg-yellow-50 text-yellow-700">Default (fallback)</span>
                                <?php else: ?>
                                    <span class="px-2.5 py-1 text-[10px] font-black uppercase rounded-md border border-blue-200 bg-blue-50 text-blue-700">
                                        Category: <?php echo e($r['category_name'] ?? 'Unknown'); ?>
                                    </span>
                                <?php endif; ?>
                                <span class="text-[10px] text-gray-400 font-bold uppercase">
                                    Last updated <?php echo $r['updated_at'] ? e(date('M d, Y H:i', strtotime($r['updated_at']))) : '—';
                                    if ($r['updated_by_name']) echo ' by ' . e($r['updated_by_name']); ?>
                                </span>
                            </div>
                            <div class="flex gap-1.5">
                                <a href="terms.php?edit=<?php echo (int)$r['id']; ?>"
                                   class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition" title="Load into editor">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                </a>
                                <?php if (!$is_default): ?>
                                    <form action="" method="POST" class="inline"
                                          onsubmit="return confirm('Delete this category-specific terms row? Issuances for this category will fall back to the default terms.');">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="terms_id" value="<?php echo (int)$r['id']; ?>">
                                        <button type="submit" name="delete_terms"
                                                class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition" title="Remove category-specific terms">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                        <p class="text-xs text-gray-700 whitespace-pre-wrap leading-relaxed bg-gray-50 border border-gray-100 rounded-xl p-4 max-h-48 overflow-y-auto custom-scrollbar">
                            <?php echo e($r['terms_text']); ?>
                        </p>
                    </div>
                <?php endforeach; ?>
                <?php if (!$terms_rows): ?>
                    <div class="p-12 text-center text-gray-400 text-sm italic">
                        No terms rows found in database.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Right: Editor form -->
    <div>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden sticky top-24">
            <div class="bg-[#1e3a8a] p-6 text-white">
                <h2 class="text-lg font-bold uppercase tracking-tight italic text-yellow-400">
                    <?php echo e($edit_row ? 'Edit Row' : 'Create / Edit Terms'); ?>
                </h2>
                <p class="text-blue-200 text-xs">
                    <?php echo e($edit_row ? 'Updating selected row' : 'Select a category or edit the default; then save'); ?>
                </p>
            </div>

            <form action="" method="POST" class="p-6 space-y-5">
                <?php echo csrf_field(); ?>

                <?php if ($edit_row): ?>
                    <input type="hidden" name="category_id" value="<?php echo $edit_row['category_id'] === null ? '' : (int)$edit_row['category_id']; ?>">
                    <div class="bg-gray-50 border border-gray-100 p-3 rounded-xl">
                        <p class="text-[10px] font-black uppercase text-gray-400 mb-1">Editing row for</p>
                        <p class="text-sm font-bold text-gray-800">
                            <?php
                            if ($edit_row['category_id'] === null) {
                                echo '<span class="text-yellow-700">Default (fallback) terms &mdash; cannot change category</span>';
                            } else {
                                $cat_stmt = $pdo->prepare("SELECT category_name FROM categories WHERE id = ? LIMIT 1");
                                $cat_stmt->execute([(int)$edit_row['category_id']]);
                                $cat = $cat_stmt->fetchColumn();
                                echo 'Category: ' . e($cat ?: 'Unknown');
                            }
                            ?>
                        </p>
                    </div>
                <?php else: ?>
                    <div>
                        <label class="block text-gray-700 text-xs font-black uppercase tracking-widest mb-2">Apply To</label>
                        <select name="category_id" id="termsCategorySelect"
                                class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 transition">
                            <option value="">Default Terms (used when no category-specific row exists)</option>
                            <?php foreach ($available_categories as $ac): ?>
                                <option value="<?php echo (int)$ac['id']; ?>">Category: <?php echo e($ac['category_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (!$available_categories): ?>
                            <p class="text-[10px] text-amber-600 italic mt-1">All categories already have custom terms. Edit or delete existing rows above.</p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <div>
                    <label class="block text-gray-700 text-xs font-black uppercase tracking-widest mb-2">
                        Terms Text
                        <span class="text-[10px] font-normal normal-case text-gray-400 ml-1">(use numbered lines; one clause per line is recommended)</span>
                    </label>
                    <textarea name="terms_text" id="termsText" rows="16" required
                        class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 transition text-xs font-mono leading-relaxed custom-scrollbar"
                        placeholder="1. Equipment remains the property of the organization...&#10;2. Change initial credentials immediately..."><?php
                        echo e($edit_row['terms_text'] ?? '');
                    ?></textarea>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <?php if ($edit_row): ?>
                        <a href="terms.php" class="bg-gray-100 hover:bg-gray-200 text-gray-600 font-bold py-3 px-6 rounded-xl transition text-center text-sm">Clear</a>
                    <?php else: ?>
                        <button type="button" onclick="document.getElementById('termsText').value=''" class="bg-gray-100 hover:bg-gray-200 text-gray-600 font-bold py-3 px-6 rounded-xl transition text-sm">Clear</button>
                    <?php endif; ?>
                    <button type="submit" name="save_terms"
                            class="bg-[#1e3a8a] hover:bg-blue-900 text-white font-bold py-3 px-6 rounded-xl shadow-lg shadow-blue-200 transition text-sm">
                        Save Terms
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include APP_ROOT . '/includes/footer.php'; ?>