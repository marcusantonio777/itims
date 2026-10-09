<?php

function app_branding(PDO $pdo): array {
    if (isset($GLOBALS['ITIMS_APP_BRANDING'])) {
        return $GLOBALS['ITIMS_APP_BRANDING'];
    }

    $branding = [
        'product_name' => 'IT Asset Manager',
        'organization_name' => 'Your Organization',
        'department_label' => 'IT Department',
        'company_logo_path' => '',
    ];
    $keys = array_keys($branding);
    $placeholders = implode(',', array_fill(0, count($keys), '?'));
    $stmt = $pdo->prepare("SELECT setting_key, setting_value FROM system_settings WHERE setting_key IN ($placeholders)");
    $stmt->execute($keys);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        if (array_key_exists($row['setting_key'], $branding)) {
            $branding[$row['setting_key']] = (string)$row['setting_value'];
        }
    }

    foreach (['product_name' => 80, 'organization_name' => 120, 'department_label' => 100] as $key => $maxLength) {
        $value = trim($branding[$key]);
        if ($value === '' || strlen($value) > $maxLength) {
            $branding[$key] = $key === 'product_name'
                ? 'IT Asset Manager'
                : ($key === 'organization_name' ? 'Your Organization' : 'IT Department');
        } else {
            $branding[$key] = $value;
        }
    }

    $logoPath = $branding['company_logo_path'];
    $branding['company_logo_path'] = preg_match('~^uploads/branding/[a-f0-9]{32}\.(?:jpg|png|webp)$~', $logoPath)
        ? $logoPath
        : '';

    $GLOBALS['ITIMS_APP_BRANDING'] = $branding;
    return $branding;
}
