<?php
require_once __DIR__ . '/config/db.php';

$logoPath = app_branding($pdo)['company_logo_path'];
if ($logoPath === '') {
    http_response_code(404);
    exit;
}

$brandingDirectory = realpath(__DIR__ . '/uploads/branding');
$resolvedLogo = realpath(__DIR__ . '/' . $logoPath);
if (!$brandingDirectory || !$resolvedLogo
    || strpos($resolvedLogo, $brandingDirectory . DIRECTORY_SEPARATOR) !== 0
    || !is_file($resolvedLogo)) {
    http_response_code(404);
    exit;
}

$imageInfo = @getimagesize($resolvedLogo);
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
header('Content-Length: ' . filesize($resolvedLogo));
header('Content-Disposition: inline; filename="organization-logo.' . image_type_to_extension($imageInfo[2], false) . '"');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, max-age=3600');
readfile($resolvedLogo);
