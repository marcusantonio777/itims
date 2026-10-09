<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/pdf_template.php';
enforce_role(ROLE_ADMIN);

$message = '';
$error = '';
$categoryRows = $pdo->query("SELECT id, category_name FROM categories ORDER BY category_name")->fetchAll();
$categoryId = (int)($_POST['category_id'] ?? $_GET['category_id'] ?? 0);
$categoryNames = [];
foreach ($categoryRows as $categoryRow) {
    $categoryNames[(int)$categoryRow['id']] = (string)$categoryRow['category_name'];
}
if ($categoryId > 0 && !isset($categoryNames[$categoryId])) $categoryId = 0;
$categoryName = $categoryNames[$categoryId] ?? '';
if ($categoryId > 0) {
    $stmt = $pdo->prepare("SELECT template_html, page_size, orientation, updated_at FROM pdf_category_templates WHERE category_id = ?");
    $stmt->execute([$categoryId]);
    $saved = $stmt->fetch();
} else {
    $stmt = $pdo->query("SELECT template_html, page_size, orientation, updated_at FROM pdf_templates WHERE id = 1");
    $saved = $stmt->fetch();
}
$template = $saved ? $saved['template_html'] : ($categoryId > 0 ? pdf_template_default_for_category($categoryName) : pdf_template_default_html());
$pageSize = $saved['page_size'] ?? 'A4';
$orientation = $saved['orientation'] ?? 'portrait';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify_post();
    $submitted = (string)($_POST['template_html'] ?? '');
    $pageSize = in_array($_POST['page_size'] ?? '', ['A4', 'Letter', 'Legal'], true) ? $_POST['page_size'] : 'A4';
    $orientation = in_array($_POST['orientation'] ?? '', ['portrait', 'landscape'], true) ? $_POST['orientation'] : 'portrait';
    if (trim($submitted) === '' || strlen($submitted) > 200000) {
        $error = 'Template content must be non-empty and no larger than 200 KB.';
    } else {
        try {
            $template = pdf_template_sanitize_html($submitted);
            if (trim(strip_tags($template)) === '') {
                throw new RuntimeException('The template must contain printable content.');
            }
            if ($categoryId > 0) {
                $save = $pdo->prepare("INSERT INTO pdf_category_templates (category_id, template_html, page_size, orientation, updated_by)
                                       VALUES (?, ?, ?, ?, ?)
                                       ON DUPLICATE KEY UPDATE template_html = VALUES(template_html),
                                           page_size = VALUES(page_size), orientation = VALUES(orientation),
                                           updated_by = VALUES(updated_by), updated_at = CURRENT_TIMESTAMP");
                $save->execute([$categoryId, $template, $pageSize, $orientation, auth_current_id()]);
            } else {
                $save = $pdo->prepare("INSERT INTO pdf_templates (id, template_html, page_size, orientation, updated_by)
                                       VALUES (1, ?, ?, ?, ?)
                                       ON DUPLICATE KEY UPDATE template_html = VALUES(template_html),
                                           page_size = VALUES(page_size), orientation = VALUES(orientation),
                                           updated_by = VALUES(updated_by), updated_at = CURRENT_TIMESTAMP");
                $save->execute([$template, $pageSize, $orientation, auth_current_id()]);
            }
            if (function_exists('log_activity')) {
                log_activity($pdo, 'Update', $categoryId > 0 ? 'pdf_category_templates' : 'pdf_templates', $categoryId ?: 1, null, 'Updated the ' . ($categoryName ?: 'default') . ' PAF print template');
            }
            $message = 'Template saved. New and reprinted issuance forms will use these settings.';
            $saved = ['updated_at' => date('Y-m-d H:i:s')];
        } catch (Throwable $exception) {
            user_error_log('Unable to save the PDF template.', $exception);
            $error = 'The template could not be saved. Review the content and try again.';
        }
    }
}

$sampleFields = [
    'company_logo' => 'assets/img/logo.png',
    'paf_number' => 'ASSET-001-25',
    'date_issued' => date('F d, Y'),
    'employee_name' => 'Alex Morgan',
    'employee_id' => 'EMP-1024',
    'employee_username' => 'amorgan',
    'department' => 'Information Technology',
    'position' => 'IT Specialist',
    'asset_photo_url' => 'assets/img/logo.png',
    'asset_tag' => 'IT-LAP-001',
    'asset_category' => $categoryName ?: 'Laptop',
    'category_details' => 'Sample device, access, or identification details',
    'mobile_number' => '+63 917 123 4567',
    'plan_name' => 'Corporate Mobile Plan',
    'monthly_limit' => 'PHP 1,000.00',
    'plan_inclusions' => 'Calls, texts, and data per company plan',
    'other_rates' => 'Excess usage billed according to the plan',
    'keycard_level' => 'Grandmaster',
    'card_access' => 'All Floors',
    'date_activated' => date('m/d/Y'),
    'expiry_date' => date('m/d/Y', strtotime('+1 year')),
    'serial_number' => 'SN-123456789',
    'brand_model' => 'Example Brand / Model',
    'processor' => 'Intel Core i5',
    'ram' => '16 GB',
    'storage' => '512 GB SSD',
    'other_specs' => 'Sample equipment specification',
    'supplies_section' => '<h3 style="background:#eee;padding:6px">INCLUDED ACCESSORIES / SUPPLIES</h3><table style="width:100%;border-collapse:collapse"><tr><th>Item Name</th><th>Brand/Model</th><th>Qty</th></tr><tr><td>USB-C Adapter</td><td>Example</td><td>1</td></tr></table>',
    'terms' => '1. Use this equipment for authorized work only.<br>2. Return it in good condition when requested.',
    'prepared_by' => 'IT STAFF',
    'noted_by' => 'MARIO GERALDEZ',
    'noted_by_title' => 'ASST. IT MANAGER',
    'received_by' => 'ALEX MORGAN',
    'received_by_title' => 'IT SPECIALIST',
    'generated_at' => date('Y-m-d H:i:s'),
];
if (preg_match('/key\s*card|keycard|access card/i', $categoryName)) {
    $sampleFields['terms'] = nl2br(e((string)pdf_default_terms_for_category($categoryName)));
}
$samplePhotoQuery = $pdo->query("SELECT id, photo_path FROM assets WHERE photo_path IS NOT NULL AND photo_path != '' ORDER BY id DESC LIMIT 1");
$samplePhoto = $samplePhotoQuery->fetch();
if ($samplePhoto && preg_match('~^uploads/assets/[A-Za-z0-9._-]+$~', (string)$samplePhoto['photo_path'])) {
    $sampleFields['asset_photo_url'] = 'generate_pdf.php?asset_photo_id=' . (int)$samplePhoto['id'];
}
$editorImage = is_file(__DIR__ . '/assets/img/logo.png') ? 'assets/img/logo.png' : 'data:image/gif;base64,R0lGODlhAQABAAD/ACwAAAAAAQABAAACADs=';
$editorHtml = str_replace('src="ITIMS_COMPANY_LOGO"', 'src="' . e($editorImage) . '" data-pdf-field="company_logo"', $template);
$editorHtml = str_replace('src="ITIMS_ASSET_PHOTO_URL"', 'src="' . e($sampleFields['asset_photo_url']) . '" data-pdf-field="asset_photo_url"', $editorHtml);
$previewBody = pdf_template_apply_fields(pdf_template_sanitize_html($template), $sampleFields);
$previewDocument = '<!doctype html><html><head><meta charset="utf-8"><style>
body{font-family:Arial,sans-serif;color:#303b49;line-height:1.35;margin:28px}
img{max-width:100%;object-fit:contain}
table{margin:8px 0;max-width:100%;border-collapse:collapse}
th,td{padding:5px;text-align:left}
.pdf-layout.pdf-phone-layout,.pdf-layout.pdf-keycard-layout{box-sizing:border-box;min-height:267mm;display:flex;flex-direction:column;justify-content:flex-start;gap:4mm;padding:3mm 4mm 2mm 7mm;border-left:3mm solid #d9dce1}
.pdf-layout.pdf-phone-layout>.pdf-block:last-child,.pdf-layout.pdf-keycard-layout>.pdf-block:last-child{margin-top:auto}
.pdf-layout.pdf-phone-layout h2,.pdf-layout.pdf-keycard-layout h2{color:#666}
.pdf-layout.pdf-phone-layout h3,.pdf-layout.pdf-keycard-layout h3{padding:5px 8px;color:#fff;background:#293f6f;font-size:10px}
.pdf-layout.pdf-phone-layout th,.pdf-layout.pdf-phone-layout td,.pdf-layout.pdf-keycard-layout th,.pdf-layout.pdf-keycard-layout td{border:1px solid #999;padding:5px;font-size:10px;line-height:1.2}
.pdf-layout.pdf-phone-layout>.pdf-block,.pdf-layout.pdf-keycard-layout>.pdf-block{flex:0 0 auto}
@page{size:' . e($pageSize . ' ' . $orientation) . ';margin:14mm}
</style></head><body>' . $previewBody . '</body></html>';

include __DIR__ . '/includes/header.php';
?>
<div class="w-full space-y-5">
    <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-end">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Issuance PDF Designer</h1>
            <p class="mt-1 max-w-3xl text-sm text-gray-500">Choose a device category, then drag sections into place and format its own issuance form.</p>
        </div>
        <div class="flex gap-2">
            <button type="button" id="toggleSource" class="rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm font-bold text-gray-700 hover:bg-gray-50">Edit HTML</button>
            <button type="button" id="refreshPreview" class="rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm font-bold text-gray-700 hover:bg-gray-50">Refresh preview</button>
        </div>
    </div>
    <p id="workspaceStatus" role="status" aria-live="polite" class="hidden rounded-xl border border-red-200 bg-red-50 p-3 text-sm font-medium text-red-700"></p>
    <?php if ($message): ?><div class="rounded-xl border border-green-200 bg-green-50 p-4 text-sm font-semibold text-green-800"><?php echo e($message); ?></div><?php endif; ?>
    <?php if ($error): ?><div class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm font-semibold text-red-800"><?php echo e($error); ?></div><?php endif; ?>

    <form method="POST" id="templateForm" class="grid items-start gap-5 xl:grid-cols-[minmax(0,1.2fr)_minmax(0,.8fr)]">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="template_html" id="templateHtml">
        <input type="hidden" name="category_id" id="categoryId" value="<?php echo $categoryId; ?>">
        <section id="editorPanel" class="workspace-panel overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 p-4">
                <div>
                    <h2 class="font-bold text-gray-800">Document layout</h2>
                    <p class="text-xs text-gray-500">Edit directly on the page, drag sections to reorder, or focus the editor full-screen.</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <label for="editorHeight" class="flex items-center gap-2 text-xs font-semibold text-gray-600">Editor height
                        <input id="editorHeight" type="range" min="50" max="90" value="72" class="w-24 accent-blue-700" aria-label="Editor height in viewport percentage">
                        <output id="editorHeightValue" for="editorHeight" class="w-10 text-right tabular-nums">72vh</output>
                    </label>
                    <button type="button" id="focusEditor" class="rounded-xl border border-gray-200 bg-white px-3 py-2 text-xs font-bold text-gray-700 hover:bg-gray-50">Focus editor</button>
                    <label class="text-xs font-semibold text-gray-600">Template for
                        <select id="categorySelector" class="ml-1 rounded-lg border border-gray-200 px-2 py-1.5 text-sm">
                            <option value="0" <?php echo $categoryId === 0 ? 'selected' : ''; ?>>Default — all categories</option>
                            <?php foreach ($categoryRows as $categoryRow): ?>
                                <option value="<?php echo (int)$categoryRow['id']; ?>" <?php echo $categoryId === (int)$categoryRow['id'] ? 'selected' : ''; ?>><?php echo e($categoryRow['category_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label class="text-xs font-semibold text-gray-600">Paper
                        <select name="page_size" id="pageSize" class="ml-1 rounded-lg border border-gray-200 px-2 py-1.5 text-sm">
                            <?php foreach (['A4', 'Letter', 'Legal'] as $size): ?><option value="<?php echo e($size); ?>" <?php echo $pageSize === $size ? 'selected' : ''; ?>><?php echo e($size); ?></option><?php endforeach; ?>
                        </select>
                    </label>
                    <label class="text-xs font-semibold text-gray-600">Orientation
                        <select name="orientation" id="orientation" class="ml-1 rounded-lg border border-gray-200 px-2 py-1.5 text-sm">
                            <option value="portrait" <?php echo $orientation === 'portrait' ? 'selected' : ''; ?>>Portrait</option>
                            <option value="landscape" <?php echo $orientation === 'landscape' ? 'selected' : ''; ?>>Landscape</option>
                        </select>
                    </label>
                </div>
            </div>
            <div id="toolbar" class="flex flex-wrap items-center gap-1 border-b border-gray-100 bg-gray-50 p-3">
                <button type="button" data-command="bold" class="editor-command">B</button>
                <button type="button" data-command="italic" class="editor-command italic">I</button>
                <button type="button" data-command="underline" class="editor-command underline">U</button>
                <button type="button" data-command="insertUnorderedList" class="editor-command">• List</button>
                <button type="button" data-command="insertOrderedList" class="editor-command">1. List</button>
                <button type="button" data-command="justifyLeft" class="editor-command">Left</button>
                <button type="button" data-command="justifyCenter" class="editor-command">Center</button>
                <button type="button" data-command="justifyRight" class="editor-command">Right</button>
                <select id="fontFamily" class="rounded-lg border border-gray-200 px-2 py-1.5 text-sm" aria-label="Font family">
                    <option value="Arial">Arial</option><option value="Georgia">Georgia</option><option value="Tahoma">Tahoma</option><option value="Times New Roman">Times New Roman</option><option value="Verdana">Verdana</option>
                </select>
                <select id="fontSize" class="rounded-lg border border-gray-200 px-2 py-1.5 text-sm" aria-label="Font size">
                    <option value="2">10 pt</option><option value="3" selected>12 pt</option><option value="4">14 pt</option><option value="5">18 pt</option><option value="6">24 pt</option>
                </select>
                <label class="flex items-center gap-1 rounded-lg border border-gray-200 bg-white px-2 py-1 text-xs" title="Text color">Text <input type="color" id="textColor" value="#333333" class="h-6 w-7 border-0 p-0"></label>
                <label class="flex items-center gap-1 rounded-lg border border-gray-200 bg-white px-2 py-1 text-xs" title="Highlight color">Highlight <input type="color" id="highlightColor" value="#fff2cc" class="h-6 w-7 border-0 p-0"></label>
                <button type="button" id="insertTable" class="editor-command">Insert table</button>
                <button type="button" id="insertPhoto" class="editor-command">Item photo</button>
                <label class="flex items-center gap-1 rounded-lg border border-gray-200 bg-white px-2 py-1 text-xs" title="Item photo width in pixels">Photo width
                    <input type="number" id="photoWidth" min="40" max="600" step="10" value="140" class="w-16 rounded border border-gray-200 px-1 py-1 text-sm">
                </label>
                <button type="button" id="photoSize" class="editor-command">Apply size</button>
                <button type="button" id="addSection" class="editor-command">Add section</button>
                <button type="button" id="deleteSection" class="editor-command">Delete section</button>
                <details class="relative">
                    <summary class="editor-command cursor-pointer list-none">Insert field ▾</summary>
                    <div id="fieldMenu" class="absolute right-0 z-20 mt-1 grid max-h-80 w-64 grid-cols-1 gap-1 overflow-auto rounded-xl border border-gray-200 bg-white p-2 shadow-xl"></div>
                </details>
            </div>
            <div id="visualEditor" contenteditable="true" spellcheck="true" class="min-h-[420px] w-full overflow-auto bg-white p-6 outline-none"><?php echo $editorHtml; ?></div>
            <textarea id="sourceEditor" class="hidden min-h-[420px] w-full resize-y p-5 font-mono text-xs leading-5 outline-none" spellcheck="false"></textarea>
            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-gray-100 bg-gray-50 p-4">
                <span class="text-xs text-gray-500"><?php echo $categoryId ? 'Saving this category-specific layout will not change other categories.' : 'This default is used when a category has no custom layout.'; ?> Unsafe HTML is removed when saved.</span>
                <button type="submit" class="rounded-xl bg-blue-700 px-5 py-2.5 text-sm font-bold text-white shadow hover:bg-blue-800">Save <?php echo $categoryId ? e($categoryName) : 'default'; ?> layout</button>
            </div>
        </section>

        <section id="previewPanel" class="workspace-panel overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 p-4">
                <div>
                    <h2 class="font-bold text-gray-800">Live sample preview</h2>
                    <p class="text-xs text-gray-500">Sample values show where issuance data and the item photo will appear.</p>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <label for="previewHeight" class="flex items-center gap-2 text-xs font-semibold text-gray-600">Preview height
                        <input id="previewHeight" type="range" min="50" max="90" value="72" class="w-24 accent-blue-700" aria-label="Preview height in viewport percentage">
                        <output id="previewHeightValue" for="previewHeight" class="w-10 text-right tabular-nums">72vh</output>
                    </label>
                    <button type="button" id="focusPreview" class="rounded-xl border border-gray-200 bg-white px-3 py-2 text-xs font-bold text-gray-700 hover:bg-gray-50">Focus preview</button>
                </div>
            </div>
            <div id="previewViewport" class="preview-viewport bg-slate-200 p-3 sm:p-5">
                <iframe id="templatePreview" title="Sample issuance form preview" sandbox="allow-same-origin" class="w-full rounded-lg border border-gray-300 bg-white shadow-lg"></iframe>
            </div>
            <?php if (!empty($saved['updated_at'])): ?><p class="px-4 py-3 text-xs text-gray-500">Last saved: <?php echo e($saved['updated_at']); ?></p><?php endif; ?>
        </section>
    </form>
</div>
<style>
.workspace-panel:fullscreen{width:100vw;height:100vh;overflow:auto;border:0;border-radius:0;padding:16px;background:#f8fafc}
.workspace-panel:fullscreen #visualEditor,.workspace-panel:fullscreen #sourceEditor{height:calc(100vh - 250px)!important;max-height:none}
.workspace-panel:fullscreen #previewViewport{height:calc(100vh - 150px);overflow:auto}
.workspace-panel:fullscreen #templatePreview{height:100%!important}
#visualEditor{height:72vh;max-height:90vh}
#sourceEditor{height:72vh;max-height:90vh}
#templatePreview{height:72vh;min-height:420px;max-height:90vh}
.preview-viewport{background-image:linear-gradient(45deg,#e2e8f0 25%,transparent 25%),linear-gradient(-45deg,#e2e8f0 25%,transparent 25%),linear-gradient(45deg,transparent 75%,#e2e8f0 75%),linear-gradient(-45deg,transparent 75%,#e2e8f0 75%);background-size:20px 20px;background-position:0 0,0 10px,10px -10px,-10px 0}
.editor-command{border:1px solid #e5e7eb;border-radius:.5rem;background:white;padding:.35rem .55rem;font-size:.75rem;font-weight:700;color:#374151}
.editor-command:hover{background:#eff6ff;color:#1d4ed8}
#visualEditor table{border-collapse:collapse;max-width:100%}
#visualEditor td,#visualEditor th{border:1px solid #d1d5db;padding:6px;min-width:45px}
#visualEditor .pdf-block{position:relative;cursor:grab;border:1px dashed transparent;border-radius:4px}
#visualEditor .pdf-block:hover{border-color:#93c5fd}
#visualEditor .pdf-block-selected{outline:2px solid #3b82f6;outline-offset:2px}
#visualEditor .pdf-block-dragging{opacity:.4}
#visualEditor .pdf-block-drop-target{box-shadow:inset 0 3px 0 #2563eb}
@media(max-width:1279px){#visualEditor,#sourceEditor,#templatePreview{height:65vh;min-height:420px}}
@media(max-width:639px){#visualEditor,#sourceEditor,#templatePreview{height:60vh;min-height:360px}.workspace-panel:fullscreen{padding:8px}.workspace-panel:fullscreen #visualEditor,.workspace-panel:fullscreen #sourceEditor{height:calc(100vh - 330px)!important}}
</style>
<script>
(function () {
    const visual = document.getElementById('visualEditor');
    const source = document.getElementById('sourceEditor');
    const preview = document.getElementById('templatePreview');
    const fields = ['paf_number','date_issued','employee_name','employee_id','employee_username','department','position','asset_category','asset_tag','serial_number','brand_model','processor','ram','storage','other_specs','category_details','mobile_number','plan_name','monthly_limit','plan_inclusions','other_rates','keycard_level','card_access','date_activated','expiry_date','terms','prepared_by','noted_by','noted_by_title','received_by','received_by_title','generated_at','supplies_section'];
    const menu = document.getElementById('fieldMenu');
    fields.forEach(function (field) {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'rounded-lg px-3 py-2 text-left text-xs font-semibold text-gray-700 hover:bg-blue-50';
        button.textContent = field.replace(/_/g, ' ');
        button.addEventListener('click', function () {
            if (field === 'supplies_section') insertHtml('{{supplies_section}}');
            else document.execCommand('insertText', false, '{{' + field + '}}');
            menu.closest('details').open = false;
        });
        menu.appendChild(button);
    });
    let savedSelection = null;
    document.addEventListener('selectionchange', function () {
        const selection = window.getSelection();
        if (selection && selection.rangeCount && visual.contains(selection.anchorNode)) {
            savedSelection = selection.getRangeAt(0).cloneRange();
        }
    });
    function restoreSelection() {
        if (!savedSelection) return;
        visual.focus();
        const selection = window.getSelection();
        selection.removeAllRanges();
        selection.addRange(savedSelection);
    }
    function activeEditor() { return source.classList.contains('hidden') ? visual : source; }
    function setSurfaceHeight(input, output, surface) {
        const height = Number(input.value);
        output.value = height + 'vh';
        surface.style.height = height + 'vh';
    }
    const editorHeight = document.getElementById('editorHeight');
    const previewHeight = document.getElementById('previewHeight');
    const editorHeightValue = document.getElementById('editorHeightValue');
    const previewHeightValue = document.getElementById('previewHeightValue');
    const initialSurfaceHeight = window.innerWidth <= 639 ? 60 : (window.innerWidth < 1280 ? 65 : 72);
    editorHeight.value = initialSurfaceHeight;
    previewHeight.value = initialSurfaceHeight;
    setSurfaceHeight(editorHeight, editorHeightValue, visual);
    setSurfaceHeight(previewHeight, previewHeightValue, preview);
    editorHeight.addEventListener('input', function () {
        setSurfaceHeight(editorHeight, editorHeightValue, visual);
        source.style.height = visual.style.height;
    });
    previewHeight.addEventListener('input', function () {
        setSurfaceHeight(previewHeight, previewHeightValue, preview);
    });
    async function focusPanel(panel, button, label) {
        const status = document.getElementById('workspaceStatus');
        try {
            if (document.fullscreenElement === panel) {
                await document.exitFullscreen();
            } else {
                if (!panel.requestFullscreen) throw new Error('Fullscreen mode is not supported by this browser.');
                await panel.requestFullscreen();
            }
            status.textContent = '';
            status.classList.add('hidden');
        } catch (error) {
            status.textContent = error instanceof Error ? error.message : 'Could not open fullscreen mode.';
            status.classList.remove('hidden');
        }
        button.textContent = document.fullscreenElement === panel ? 'Exit focus' : label;
    }
    const editorPanel = document.getElementById('editorPanel');
    const previewPanel = document.getElementById('previewPanel');
    const focusEditorButton = document.getElementById('focusEditor');
    const focusPreviewButton = document.getElementById('focusPreview');
    focusEditorButton.addEventListener('click', function () { focusPanel(editorPanel, focusEditorButton, 'Focus editor'); });
    focusPreviewButton.addEventListener('click', function () { focusPanel(previewPanel, focusPreviewButton, 'Focus preview'); });
    document.addEventListener('fullscreenchange', function () {
        focusEditorButton.textContent = document.fullscreenElement === editorPanel ? 'Exit focus' : 'Focus editor';
        focusPreviewButton.textContent = document.fullscreenElement === previewPanel ? 'Exit focus' : 'Focus preview';
    });
    function insertHtml(html) {
        if (activeEditor() === source) {
            const start = source.selectionStart, end = source.selectionEnd;
            source.setRangeText(html, start, end, 'end');
        } else {
            restoreSelection();
            document.execCommand('insertHTML', false, html);
        }
    }
    document.querySelectorAll('[data-command]').forEach(function (button) {
        button.addEventListener('click', function () {
            if (activeEditor() === source) return;
            restoreSelection();
            document.execCommand(button.dataset.command, false, null);
        });
    });
    document.getElementById('fontFamily').addEventListener('change', function (event) {
        restoreSelection(); document.execCommand('fontName', false, event.target.value);
    });
    document.getElementById('fontSize').addEventListener('change', function (event) {
        restoreSelection(); document.execCommand('styleWithCSS', false, true); document.execCommand('fontSize', false, event.target.value);
    });
    document.getElementById('textColor').addEventListener('input', function (event) {
        restoreSelection(); document.execCommand('foreColor', false, event.target.value);
    });
    document.getElementById('highlightColor').addEventListener('input', function (event) {
        restoreSelection(); document.execCommand('hiliteColor', false, event.target.value);
    });
    document.getElementById('insertTable').addEventListener('click', function () {
        insertHtml('<table style="width:100%;border-collapse:collapse"><tbody><tr><td>Cell 1</td><td>Cell 2</td></tr><tr><td>Cell 3</td><td>Cell 4</td></tr></tbody></table><p><br></p>');
    });
    document.getElementById('addSection').addEventListener('click', function () {
        layoutRoot().insertAdjacentHTML('beforeend', '<div class="pdf-block"><h3 style="background:#eee;padding:6px;border:1px solid #ccc">NEW SECTION</h3><p>Click here to edit this section.</p></div>');
        setupDragBlocks();
    });
    document.getElementById('deleteSection').addEventListener('click', function () {
        if (selectedBlock && selectedBlock.parentElement === layoutRoot()) {
            selectedBlock.remove();
            selectedBlock = null;
            updatePreview();
        }
    });
    document.getElementById('insertPhoto').addEventListener('click', function () {
        insertHtml('<img src="<?php echo e($sampleFields['asset_photo_url']); ?>" data-pdf-field="asset_photo_url" alt="Item photo" style="max-width:140px;max-height:140px">');
    });
    let selectedImage = null;
    let selectedBlock = null;
    visual.addEventListener('click', function (event) {
        selectedImage = event.target && event.target.tagName === 'IMG' ? event.target : null;
    });
    document.getElementById('photoSize').addEventListener('click', function () {
        const selection = window.getSelection();
        const image = selection ? selection.anchorNode : null;
        const selected = selectedImage || (image && (image.nodeType === 1 ? image : image.parentElement).closest('img'));
        if (!selected) { alert('Select the item photo in the editor first.'); return; }
        const width = parseInt(document.getElementById('photoWidth').value, 10);
        if (!Number.isInteger(width) || width < 40 || width > 600) { alert('Enter a width from 40 to 600 pixels.'); return; }
        selected.style.width = width + 'px';
        selected.style.height = 'auto';
        selected.style.maxWidth = width + 'px';
        selected.style.maxHeight = 'none';
    });
    document.getElementById('toggleSource').addEventListener('click', function () {
        if (source.classList.contains('hidden')) {
            source.value = visual.innerHTML;
            source.style.height = visual.style.height;
            visual.classList.add('hidden');
            source.classList.remove('hidden');
            this.textContent = 'Visual editor';
        } else {
            visual.innerHTML = source.value;
            visual.style.height = source.style.height || editorHeight.value + 'vh';
            source.classList.add('hidden');
            visual.classList.remove('hidden');
            this.textContent = 'Edit HTML';
            setupDragBlocks();
        }
        updatePreview();
    });
    document.getElementById('categorySelector').addEventListener('change', function () {
        const currentMarkup = source.classList.contains('hidden') ? visual.innerHTML : source.value;
        if (currentMarkup !== initialMarkup && !window.confirm('Switching templates will discard unsaved changes. Continue?')) {
            this.value = '<?php echo $categoryId; ?>';
            return;
        }
        const target = new URL(window.location.href);
        if (this.value === '0') target.searchParams.delete('category_id');
        else target.searchParams.set('category_id', this.value);
        window.location.href = target.toString();
    });
    const sample = <?php echo json_encode($sampleFields, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;
    function updatePreview() {
        let html = activeEditor() === source ? source.value : visual.innerHTML;
        Object.keys(sample).forEach(function (key) {
            html = html.split('{{' + key + '}}').join(sample[key]);
            html = html.split('ITIMS_' + key.toUpperCase()).join(sample[key]);
        });
        const pageDimensions = { A4: [210, 297], Letter: [216, 279], Legal: [216, 356] };
        const selectedPage = pageDimensions[document.getElementById('pageSize').value] || pageDimensions.A4;
        const pageHeight = document.getElementById('orientation').value === 'landscape' ? selectedPage[0] : selectedPage[1];
        const printableHeight = pageHeight - 28;
        const previewCss = 'html{min-height:100%;background:#fff}' +
            'body{box-sizing:border-box;min-height:100vh;font-family:Arial,sans-serif;color:#303b49;line-height:1.35;margin:0;padding:28px;background:#fff}' +
            'img{max-width:100%;height:auto;object-fit:contain}' +
            'table{margin:8px 0;max-width:100%;border-collapse:collapse}' +
            'th,td{padding:5px;text-align:left}' +
            '.pdf-layout{box-sizing:border-box;min-height:' + printableHeight + 'mm;display:flex;flex-direction:column;justify-content:space-between;gap:4mm}' +
            '.pdf-layout>.pdf-block{flex:0 0 auto}' +
            '.pdf-layout.pdf-phone-layout,.pdf-layout.pdf-keycard-layout{box-sizing:border-box;min-height:' + printableHeight + 'mm;display:flex;flex-direction:column;justify-content:flex-start;gap:4mm;padding:3mm 4mm 2mm 7mm;border-left:3mm solid #d9dce1}' +
            '.pdf-layout.pdf-phone-layout>.pdf-block:last-child,.pdf-layout.pdf-keycard-layout>.pdf-block:last-child{margin-top:auto}' +
            '.pdf-layout.pdf-phone-layout h2,.pdf-layout.pdf-keycard-layout h2{color:#666}' +
            '.pdf-layout.pdf-phone-layout h3,.pdf-layout.pdf-keycard-layout h3{padding:5px 8px;color:#fff;background:#293f6f;font-size:10px}' +
            '.pdf-layout.pdf-phone-layout th,.pdf-layout.pdf-phone-layout td,.pdf-layout.pdf-keycard-layout th,.pdf-layout.pdf-keycard-layout td{border:1px solid #999;padding:5px;font-size:10px;line-height:1.2}' +
            '.pdf-layout.pdf-phone-layout>.pdf-block,.pdf-layout.pdf-keycard-layout>.pdf-block{flex:0 0 auto}' +
            '@page{size:' + document.getElementById('pageSize').value + ' ' + document.getElementById('orientation').value + ';margin:14mm}';
        preview.srcdoc = '<!doctype html><html><head><meta charset="utf-8"><style>' + previewCss + '</style></head><body>' + html + '</body></html>';
    }
    document.getElementById('refreshPreview').addEventListener('click', updatePreview);
    document.getElementById('pageSize').addEventListener('change', updatePreview);
    document.getElementById('orientation').addEventListener('change', updatePreview);
    document.getElementById('templateForm').addEventListener('submit', function () {
        const markup = activeEditor() === source ? source.value : visual.innerHTML;
        const wrapper = document.createElement('div');
        wrapper.innerHTML = markup;
        wrapper.querySelectorAll('img[data-pdf-field]').forEach(function (image) {
            image.setAttribute('src', 'ITIMS_' + image.getAttribute('data-pdf-field').toUpperCase());
            image.removeAttribute('data-pdf-field');
        });
        document.getElementById('templateHtml').value = wrapper.innerHTML;
    });
    const initialMarkup = visual.innerHTML;
    let draggedBlock = null;
    function layoutRoot() {
        return visual.querySelector('.pdf-layout') || visual;
    }
    function setupDragBlocks() {
        Array.from(layoutRoot().children).filter(function (block) {
            return block.classList.contains('pdf-block');
        }).forEach(function (block) {
            if (block.dataset.dragReady === '1') return;
            block.dataset.dragReady = '1';
            block.setAttribute('draggable', 'true');
            block.addEventListener('click', function (event) {
                if (event.target.closest('button, input, select')) return;
                if (selectedBlock) selectedBlock.classList.remove('pdf-block-selected');
                selectedBlock = block;
                selectedBlock.classList.add('pdf-block-selected');
            });
            block.addEventListener('dragstart', function (event) {
                draggedBlock = block;
                block.classList.add('pdf-block-dragging');
                event.dataTransfer.effectAllowed = 'move';
            });
            block.addEventListener('dragend', function () {
                block.classList.remove('pdf-block-dragging');
                layoutRoot().querySelectorAll('.pdf-block-drop-target').forEach(function (item) {
                    item.classList.remove('pdf-block-drop-target');
                });
            });
            block.addEventListener('dragover', function (event) {
                if (!draggedBlock || draggedBlock === block) return;
                event.preventDefault();
                block.classList.add('pdf-block-drop-target');
            });
            block.addEventListener('dragleave', function () {
                block.classList.remove('pdf-block-drop-target');
            });
            block.addEventListener('drop', function (event) {
                if (!draggedBlock || draggedBlock === block) return;
                event.preventDefault();
                const bounds = block.getBoundingClientRect();
                const insertBefore = event.clientY < bounds.top + bounds.height / 2;
                layoutRoot().insertBefore(draggedBlock, insertBefore ? block : block.nextSibling);
                block.classList.remove('pdf-block-drop-target');
                updatePreview();
            });
        });
    }
    visual.addEventListener('input', updatePreview);
    source.addEventListener('input', updatePreview);
    setupDragBlocks();
    updatePreview();
})();
</script>
<?php include __DIR__ . '/includes/footer.php'; ?>
