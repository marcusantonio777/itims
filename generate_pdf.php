<?php
require_once __DIR__ . '/includes/auth.php';
enforce_authenticated();
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/pdf_template.php';
enforce_page_access('generate_pdf.php');

if (isset($_GET['asset_photo_id'])) {
    $assetPhotoId = (int)$_GET['asset_photo_id'];
    $photoQuery = $pdo->prepare("SELECT photo_path FROM assets WHERE id = ? LIMIT 1");
    $photoQuery->execute([$assetPhotoId]);
    $storedPath = (string)$photoQuery->fetchColumn();
    if ($assetPhotoId <= 0 || !preg_match('~^uploads/assets/[A-Za-z0-9._-]+$~', $storedPath)) {
        http_response_code(404);
        exit;
    }
    $assetDirectory = realpath(__DIR__ . '/uploads/assets');
    $resolvedPhoto = realpath(__DIR__ . '/' . $storedPath);
    if (!$assetDirectory || !$resolvedPhoto || strpos($resolvedPhoto, $assetDirectory . DIRECTORY_SEPARATOR) !== 0 || !is_file($resolvedPhoto)) {
        http_response_code(404);
        exit;
    }
    $imageInfo = @getimagesize($resolvedPhoto);
    $allowedImageTypes = [
        IMAGETYPE_JPEG => 'image/jpeg',
        IMAGETYPE_PNG => 'image/png',
        IMAGETYPE_WEBP => 'image/webp',
    ];
    if (!$imageInfo || !isset($allowedImageTypes[$imageInfo[2]])) {
        http_response_code(415);
        exit;
    }
    header('Content-Type: ' . $allowedImageTypes[$imageInfo[2]]);
    header('Content-Length: ' . filesize($resolvedPhoto));
    header('Content-Disposition: inline; filename="asset-photo.' . image_type_to_extension($imageInfo[2], false) . '"');
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: private, no-store, max-age=0');
    readfile($resolvedPhoto);
    exit;
}

$logId = isset($_GET['log_id']) ? (int)$_GET['log_id'] : 0;
if ($logId <= 0) {
    http_response_code(400);
    die('Issuance record ID is required.');
}

$stmt = $pdo->prepare("SELECT l.*, a.*, e.name AS employee_name, e.department, e.position,
                              e.employee_id AS e_id, e.username AS emp_username,
                              s.full_name AS staff_name
                       FROM accountability_logs l
                       JOIN assets a ON l.asset_id = a.id
                       JOIN employees e ON l.user_id = e.id
                       JOIN it_staff s ON l.it_staff_id = s.id
                       WHERE l.id = ?");
$stmt->execute([$logId]);
$data = $stmt->fetch();
if (!$data) {
    http_response_code(404);
    die('Issuance record not found.');
}

$employeeQuery = $pdo->prepare("SELECT e.* FROM accountability_log_employees ale
                                JOIN employees e ON ale.employee_id = e.id
                                WHERE ale.log_id = ? ORDER BY e.name ASC");
$employeeQuery->execute([$logId]);
$employees = $employeeQuery->fetchAll();
if (!$employees) {
    $employees = [[
        'name' => $data['employee_name'],
        'position' => $data['position'],
    ]];
}

$suppliesQuery = $pdo->prepare("SELECT s.item_name, s.brand, s.model, ars.quantity
                                FROM accountability_supplies ars
                                JOIN supplies s ON ars.supply_id = s.id
                                WHERE ars.log_id = ?");
$suppliesQuery->execute([$logId]);
$supplies = $suppliesQuery->fetchAll();
$suppliesHtml = '';
if ($supplies) {
    $suppliesHtml = '<h3 class="section-title">INCLUDED ACCESSORIES / SUPPLIES</h3><table><thead><tr><th>Item Name</th><th>Brand/Model</th><th>Qty</th></tr></thead><tbody>';
    foreach ($supplies as $supply) {
        $suppliesHtml .= '<tr><td>' . e($supply['item_name']) . '</td><td>' .
            e(trim(($supply['brand'] ?? '') . ' ' . ($supply['model'] ?? ''))) .
            '</td><td style="text-align:center">' . (int)$supply['quantity'] . '</td></tr>';
    }
    $suppliesHtml .= '</tbody></table>';
}

$photoUrl = 'data:image/gif;base64,R0lGODlhAQABAAD/ACwAAAAAAQABAAACADs=';
$photoPath = (string)($data['photo_path'] ?? '');
if (preg_match('~^uploads/assets/[A-Za-z0-9._-]+$~', $photoPath)) {
    $assetDirectory = realpath(__DIR__ . '/uploads/assets');
    $resolvedPhoto = realpath(__DIR__ . '/' . $photoPath);
    if ($assetDirectory && $resolvedPhoto && strpos($resolvedPhoto, $assetDirectory . DIRECTORY_SEPARATOR) === 0 && is_file($resolvedPhoto)) {
        $photoUrl = 'generate_pdf.php?asset_photo_id=' . (int)$data['asset_id'];
    }
}
$appBranding = app_branding($pdo);
$logoPath = $appBranding['company_logo_path'] !== ''
    ? 'brand_logo.php?v=' . rawurlencode(basename($appBranding['company_logo_path']))
    : 'data:image/gif;base64,R0lGODlhAQABAAD/ACwAAAAAAQABAAACADs=';

$positionList = array_values(array_unique(array_filter(array_map(static function (array $employee): string {
    return (string)($employee['position'] ?? '');
}, $employees))));
$receiverNames = array_map(static function (array $employee): string {
    return e(strtoupper((string)$employee['name']));
}, $employees);
$receivedBy = implode(' / ', $receiverNames);
$receivedTitle = count($positionList) === 1 ? e(strtoupper($positionList[0])) : '';

$categoryQuery = $pdo->prepare("SELECT category_name FROM categories WHERE id = ? LIMIT 1");
$categoryQuery->execute([(int)$data['category_id']]);
$categoryName = (string)($categoryQuery->fetchColumn() ?: 'Equipment');
$categoryTermsQuery = $pdo->prepare("SELECT terms_text FROM accountability_terms WHERE category_id = ? LIMIT 1");
$categoryTermsQuery->execute([(int)$data['category_id']]);
$termsText = $categoryTermsQuery->fetchColumn();
if ($termsText === false || trim((string)$termsText) === '') {
    $termsText = pdf_default_terms_for_category($categoryName);
}
if ($termsText === null || $termsText === false || trim((string)$termsText) === '') {
    $defaultTermsQuery = $pdo->query("SELECT terms_text FROM accountability_terms WHERE category_id IS NULL LIMIT 1");
    $termsText = $defaultTermsQuery->fetchColumn();
}
if ($termsText === false || trim((string)$termsText) === '') {
    $termsText = 'No terms and conditions have been configured. Please contact the IT department.';
}
$isComputerCategory = (bool)preg_match('/laptop|notebook|desktop|computer|\bpc\b/i', $categoryName);
$isPhoneCategory = (bool)preg_match('/phone|mobile/i', $categoryName);
if ($isComputerCategory) {
    $employeeUsername = trim((string)($data['emp_username'] ?? ''));
    $usernameForForm = $employeeUsername !== '' ? $employeeUsername : 'Not set in employee record';
    $termsText = preg_replace_callback(
        '/^(\s*Username\s*:\s*).*$/mi',
        static function (array $matches) use ($usernameForForm): string {
            return $matches[1] . $usernameForForm;
        },
        (string)$termsText
    );
    $termsText = preg_replace_callback(
        '/^(\s*Password\s*:\s*).*$/mi',
        static function (array $matches): string {
            return $matches[1] . 'Set individually by the system administrator';
        },
        (string)$termsText
    );
}
$categoryDetails = (string)($data['other_specs'] ?? '');
$readCategoryDetail = static function (array $labels) use ($categoryDetails): string {
    foreach (preg_split('/[\r\n;|]+/', $categoryDetails) as $line) {
        if (preg_match('/^\s*([^:=]+)\s*[:=]\s*(.*?)\s*$/', $line, $parts)) {
            foreach ($labels as $label) {
                if (strcasecmp(trim($parts[1]), $label) === 0 && $parts[2] !== '') {
                    return $parts[2];
                }
            }
        }
    }
    return '';
};
$mobileNumber = $readCategoryDetail(['mobile number', 'phone number', 'mobile', 'msisdn']);
if ($mobileNumber === '' && preg_match('/(?<!\d)(?:\+?63[\s-]?)?0?9(?:[\s-]?\d){9}(?!\d)/', $categoryDetails, $mobileMatch)) {
    $mobileNumber = trim($mobileMatch[0]);
}
$dateActivated = $readCategoryDetail(['date activated', 'activation date', 'activated']);
if ($dateActivated === '') {
    $dateActivated = date('m/d/Y', strtotime((string)$data['date_issued']));
} elseif (($activationTimestamp = strtotime($dateActivated)) !== false) {
    $dateActivated = date('m/d/Y', $activationTimestamp);
}
$phoneDeviceDetails = (string)($data['other_specs'] ?? '');
if ($isPhoneCategory) {
    $phoneServiceLabels = [
        'mobile number', 'phone number', 'mobile', 'msisdn',
        'plan', 'plan name', 'mobile plan',
        'monthly limit', 'max limit', 'maximum limit', 'plan limit',
        'plan inclusions', 'inclusions', 'included services',
        'other rates', 'rates', 'excess rates',
        'date activated', 'activation date', 'activated',
    ];
    $phoneDeviceDetails = implode("\n", array_filter(
        preg_split('/\r\n|\r|\n/', $phoneDeviceDetails),
        static function (string $line) use ($phoneServiceLabels): bool {
            if (!preg_match('/^\s*([^:=]+)\s*[:=]/', $line, $parts)) {
                return trim($line) !== '';
            }
            return !in_array(strtolower(trim($parts[1])), $phoneServiceLabels, true);
        }
    ));
}
$isKeycardCategory = (bool)preg_match('/key\s*card|keycard|access card/i', $categoryName);
$keycardStartDate = trim((string)($data['keycard_issued_on'] ?? ''));
if ($keycardStartDate === '') {
    $keycardStartDate = date('Y-m-d', strtotime((string)$data['date_issued']));
}
$keycardExpiryDate = trim((string)($data['keycard_expires_on'] ?? ''));
if ($keycardExpiryDate === '') {
    $keycardExpiryDate = (new DateTimeImmutable($keycardStartDate))->modify('+1 year')->format('Y-m-d');
}
$keycardLevel = trim((string)($data['keycard_level'] ?? ''));
if ($keycardLevel === '') {
    $keycardLevel = $readCategoryDetail(['keycard level', 'key card level', 'card level', 'level']) ?: 'Grandmaster';
}
$keycardAccess = trim((string)($data['keycard_access'] ?? ''));
if ($keycardAccess === '') {
    $keycardAccess = $readCategoryDetail(['access', 'access level', 'access areas', 'floors']) ?: 'All Floors';
}
$categoryTemplateQuery = $pdo->prepare("SELECT template_html, page_size, orientation FROM pdf_category_templates WHERE category_id = ?");
$categoryTemplateQuery->execute([(int)$data['category_id']]);
$templateRecord = $categoryTemplateQuery->fetch();
$hasBuiltInCategoryLayout = (bool)preg_match('/phone|mobile|key\s*card|keycard|access card/i', $categoryName);
$templateQuery = $pdo->query("SELECT template_html, page_size, orientation FROM pdf_templates WHERE id = 1");
$defaultTemplate = $templateQuery->fetch();
if (!$templateRecord && !$hasBuiltInCategoryLayout && $defaultTemplate) {
    $templateRecord = $defaultTemplate;
}
$template = pdf_template_sanitize_html($templateRecord ? $templateRecord['template_html'] : pdf_template_default_for_category($categoryName));
$pageSizeValue = is_array($templateRecord) ? ($templateRecord['page_size'] ?? 'A4') : 'A4';
$orientationValue = is_array($templateRecord) ? ($templateRecord['orientation'] ?? 'portrait') : 'portrait';
$pageSize = in_array($pageSizeValue, ['A4', 'Letter', 'Legal'], true) ? $pageSizeValue : 'A4';
$orientation = in_array($orientationValue, ['portrait', 'landscape'], true) ? $orientationValue : 'portrait';
$paperDimensions = [
    'A4' => ['width' => 210, 'height' => 297],
    'Letter' => ['width' => 216, 'height' => 279],
    'Legal' => ['width' => 216, 'height' => 356],
];
$paperWidth = $paperDimensions[$pageSize]['width'];
$paperHeight = $paperDimensions[$pageSize]['height'];
if ($orientation === 'landscape') {
    [$paperWidth, $paperHeight] = [$paperHeight, $paperWidth];
}
$printWidthMm = $paperWidth - 12;
$printHeightMm = $paperHeight - 12;
$fields = [
    'company_logo' => e($logoPath),
    'product_name' => e($appBranding['product_name']),
    'organization_name' => e($appBranding['organization_name']),
    'department_label' => e($appBranding['department_label']),
    'paf_number' => e(str_replace('-', '', (string)$data['asset_tag']) . '-' . $logId),
    'date_issued' => e(date('F d, Y', strtotime((string)$data['date_issued']))),
    'employee_name' => e($data['employee_name']),
    'employee_id' => e($data['e_id']),
    'employee_username' => e(trim((string)($data['emp_username'] ?? '')) ?: 'Not set in employee record'),
    'department' => e($data['department']),
    'position' => e($data['position'] ?: 'N/A'),
    'asset_category' => e($categoryName),
    'category_details' => e($isPhoneCategory && trim($phoneDeviceDetails) !== '' ? $phoneDeviceDetails : ($isPhoneCategory ? 'N/A' : ($categoryDetails !== '' ? $categoryDetails : 'N/A'))),
    'mobile_number' => e($mobileNumber !== '' ? $mobileNumber : 'N/A'),
    'plan_name' => e($readCategoryDetail(['plan', 'plan name', 'mobile plan']) ?: 'N/A'),
    'monthly_limit' => e($readCategoryDetail(['monthly limit', 'max limit', 'maximum limit', 'plan limit']) ?: 'N/A'),
    'plan_inclusions' => e($readCategoryDetail(['plan inclusions', 'inclusions', 'included services']) ?: 'N/A'),
    'other_rates' => e($readCategoryDetail(['other rates', 'rates', 'excess rates']) ?: ''),
    'keycard_level' => e($keycardLevel),
    'card_access' => e($keycardAccess),
    'date_activated' => e($isKeycardCategory ? date('m/d/Y', strtotime($keycardStartDate)) : $dateActivated),
    'expiry_date' => e($isKeycardCategory ? date('m/d/Y', strtotime($keycardExpiryDate)) : ($readCategoryDetail(['expiry date', 'expiration date', 'expires']) ?: 'N/A')),
    'asset_photo_url' => e($photoUrl),
    'asset_tag' => e($data['asset_tag']),
    'serial_number' => e($data['serial_number']),
    'brand_model' => e(trim((string)$data['brand'] . ' ' . (string)$data['model'])),
    'processor' => e($data['processor']),
    'ram' => e($data['ram']),
    'storage' => e($data['storage']),
    'other_specs' => e($isPhoneCategory ? (trim($phoneDeviceDetails) !== '' ? $phoneDeviceDetails : 'N/A') : $data['other_specs']),
    'supplies_section' => $suppliesHtml,
    'terms' => nl2br(e((string)$termsText)),
    'prepared_by' => e(strtoupper(trim((string)($data['staff_name'] ?? '')) ?: auth_current_name() ?: 'IT STAFF')),
    'noted_by' => '',
    'noted_by_title' => 'Authorized Approver',
    'received_by' => $receivedBy,
    'received_by_title' => $receivedTitle,
    'generated_at' => e(date('Y-m-d H:i:s')),
];
$body = pdf_template_apply_fields($template, $fields);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo e($appBranding['product_name']); ?> Accountability Form - <?php echo e($data['asset_tag']); ?></title>
    <style>
        @page { size: <?php echo e($pageSize . ' ' . $orientation); ?>; margin: 6mm; }
        body { font-family: Arial, sans-serif; line-height: 1.4; color: #333; margin: 0; }
        table { border-collapse: collapse; max-width: 100%; }
        th, td { border: 1px solid #ccc; padding: 7px; text-align: left; font-size: 12px; }
        .section-title { font-weight: bold; background: #eee; padding: 6px; margin-top: 18px; border: 1px solid #ccc; }
        img { max-width: 100%; }
        .print-bar { background: #fdf6e3; padding: 10px; border: 1px solid #eee; margin-bottom: 16px; text-align: center; }
        .print-bar button { padding: 10px 20px; cursor: pointer; background: #1e3a8a; color: white; border: 0; border-radius: 5px; font-weight: bold; }
        body.print-page { margin: 0; }
        body.print-page #printPage { width: <?php echo $printWidthMm; ?>mm; height: <?php echo $printHeightMm; ?>mm; margin: 0 auto; overflow: hidden; }
        body.print-page #printContent { transform-origin: top left; }
        @media print {
            .no-print { display: none !important; }
            body { margin: 0; line-height: 1.3; color: #243447; }
            #printPage { width: <?php echo $printWidthMm; ?>mm; height: <?php echo $printHeightMm; ?>mm; margin: 0 auto; overflow: hidden; break-after: avoid-page; page-break-after: avoid; }
            #printContent { width: 100%; box-sizing: border-box; transform-origin: top left; font-size: 10px; line-height: 1.3; }
            #printContent > .pdf-layout { box-sizing: border-box; min-height: 100%; display: flex; flex-direction: column; justify-content: space-between; gap: 4mm; }
            #printContent > .pdf-layout > .pdf-block { flex: 0 0 auto; break-inside: avoid; page-break-inside: avoid; }
            #printContent .pdf-layout.pdf-phone-layout,
            #printContent .pdf-layout.pdf-keycard-layout { box-sizing: border-box; min-height: 100%; display: flex; flex-direction: column; justify-content: flex-start; gap: 3mm; padding: 2mm 3mm 1mm 6mm; border-left: 3mm solid #d9dce1; print-color-adjust: exact; -webkit-print-color-adjust: exact; }
            #printContent .pdf-layout.pdf-phone-layout > .pdf-block:last-child,
            #printContent .pdf-layout.pdf-keycard-layout > .pdf-block:last-child { margin-top: auto; }
            #printContent .pdf-layout.pdf-phone-layout h2,
            #printContent .pdf-layout.pdf-keycard-layout h2 { color: #666 !important; }
            #printContent .pdf-layout.pdf-phone-layout h3,
            #printContent .pdf-layout.pdf-keycard-layout h3 { padding: 4px 7px !important; color: #fff !important; background: #293f6f !important; border: 0 !important; font-size: 9px !important; letter-spacing: .3px; }
            #printContent .pdf-layout.pdf-phone-layout th,
            #printContent .pdf-layout.pdf-keycard-layout th { color: #333; font-weight: 700; }
            #printContent .pdf-layout.pdf-phone-layout th,
            #printContent .pdf-layout.pdf-phone-layout td,
            #printContent .pdf-layout.pdf-keycard-layout th,
            #printContent .pdf-layout.pdf-keycard-layout td { padding: 4px !important; font-size: 9px !important; line-height: 1.15; }
            #printContent .pdf-layout.pdf-phone-layout img,
            #printContent .pdf-layout.pdf-keycard-layout img { max-height: 38mm; object-fit: contain; }
            #printContent .pdf-layout.pdf-phone-layout .phone-terms,
            #printContent .pdf-layout.pdf-keycard-layout .keycard-terms { font-size: 8px; line-height: 1.15; }
            #printContent h1, #printContent h2, #printContent h3, #printContent h4, #printContent p { margin-top: 3px !important; margin-bottom: 3px !important; }
            #printContent .pdf-block { break-inside: avoid; page-break-inside: avoid; }
        }
    </style>
</head>
<body>
    <div class="print-bar no-print"><button onclick="window.print()">Print / Save as PDF</button> <?php if (auth_current_role() === ROLE_ADMIN): ?><a href="pdf_template_editor.php" style="margin-left:12px">Edit form layout</a><?php endif; ?></div>
    <main id="printPage"><div id="printContent"><?php echo $body; ?></div></main>
    <script>
        (function () {
            const page = document.getElementById('printPage');
            const content = document.getElementById('printContent');
            const pageWidthMm = <?php echo $printWidthMm; ?>;
            const pageHeightMm = <?php echo $printHeightMm; ?>;
            const mmToPx = 96 / 25.4;

            function preparePageForPrint() {
                document.body.classList.add('print-page');
                const width = pageWidthMm * mmToPx;
                const height = pageHeightMm * mmToPx;
                page.style.width = width + 'px';
                page.style.height = height + 'px';
                page.style.overflow = 'hidden';
                content.style.boxSizing = 'border-box';
                content.style.width = width + 'px';
                content.style.height = height + 'px';
                content.style.transform = 'none';
            }

            function resetPageAfterPrint() {
                document.body.classList.remove('print-page');
                page.removeAttribute('style');
                content.removeAttribute('style');
            }

            window.addEventListener('beforeprint', preparePageForPrint);
            window.addEventListener('afterprint', resetPageAfterPrint);
            document.querySelectorAll('#printContent img').forEach(function (image) {
                image.addEventListener('load', function () {
                    if (document.body.classList.contains('print-page')) preparePageForPrint();
                });
            });
        })();
    </script>
</body>
</html>
