<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/pdf_template.php';

$recipientNames = pdf_template_issuance_recipient_names([
    ['name' => 'Alex Morgan'],
    ['name' => 'Jamie Lee'],
]);
if ($recipientNames !== 'ALEX MORGAN / JAMIE LEE') {
    throw new RuntimeException('Every accountable employee must appear in the same recipient order and format.');
}
$recipientIds = pdf_template_issuance_recipient_ids([
    ['name' => 'Alex Morgan', 'employee_id' => 'EMP-1024'],
    ['name' => 'Jamie Lee', 'employee_id' => 'EMP-2048'],
]);
if ($recipientIds !== 'EMP-1024 / EMP-2048') {
    throw new RuntimeException('Every accountable employee ID must appear in recipient order and format.');
}

$signatories = pdf_template_issuance_signatories('  Jordan IT Manager  ', 'Fallback Name');
if ($signatories['prepared_by'] !== 'JORDAN IT MANAGER'
    || $signatories['noted_by'] !== 'JORDAN IT MANAGER'
    || $signatories['prepared_by_title'] !== ''
    || $signatories['noted_by_title'] !== 'IT Manager'
) {
    throw new RuntimeException('The issuing IT manager must be selected as the Noted By signatory.');
}

$fallbackSignatories = pdf_template_issuance_signatories(null, 'Current IT Manager');
if ($fallbackSignatories['noted_by'] !== 'CURRENT IT MANAGER') {
    throw new RuntimeException('The signed-in IT manager name must be used when the issuance name is missing.');
}

$fallbackName = pdf_template_issuance_signatories('', '');
if ($fallbackName['noted_by'] !== 'IT STAFF') {
    throw new RuntimeException('A missing IT staff name must use a safe display fallback.');
}

$configuredSignatories = pdf_template_issuance_signatories(
    'Issuing Staff',
    'Fallback Name',
    ' Prepared Person ',
    'Approving Manager',
    'IT Coordinator',
    'General Manager'
);
if ($configuredSignatories['prepared_by'] !== 'PREPARED PERSON'
    || $configuredSignatories['noted_by'] !== 'APPROVING MANAGER'
    || $configuredSignatories['prepared_by_title'] !== 'IT Coordinator'
    || $configuredSignatories['noted_by_title'] !== 'General Manager'
) {
    throw new RuntimeException('Configured accountability signatory names and positions must override the defaults.');
}

$partialConfiguredSignatories = pdf_template_issuance_signatories(
    'Issuing Staff',
    'Fallback Name',
    'Prepared Person',
    '',
    '',
    '',
    'IT Department Representative'
);
if ($partialConfiguredSignatories['prepared_by'] !== 'PREPARED PERSON'
    || $partialConfiguredSignatories['noted_by'] !== 'ISSUING STAFF'
    || $partialConfiguredSignatories['prepared_by_title'] !== 'IT Department Representative'
    || $partialConfiguredSignatories['noted_by_title'] !== 'IT Manager'
) {
    throw new RuntimeException('Blank configured signatory values must retain the applicable defaults.');
}

$template = pdf_template_default_for_category('Keycard');
$rendered = pdf_template_apply_fields($template, [
    'noted_by' => $signatories['noted_by'],
    'noted_by_title' => $signatories['noted_by_title'],
    'prepared_by' => $configuredSignatories['prepared_by'],
    'prepared_by_title' => $configuredSignatories['prepared_by_title'],
]);
if (strpos($rendered, 'JORDAN IT MANAGER') === false
    || strpos($rendered, '<small>IT Manager</small>') === false
    || strpos($rendered, 'PREPARED PERSON') === false
    || strpos($rendered, '<small>IT Coordinator</small>') === false
) {
    throw new RuntimeException('The generated keycard form must show configured signatory names and positions.');
}

echo "PDF issuance signatory test passed.\n";
