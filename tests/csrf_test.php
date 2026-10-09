<?php

require_once __DIR__ . '/../includes/auth.php';

$_SESSION = [];
$form = csrf_field();
if (!preg_match('/name="_csrf" value="([a-f0-9]{64})"/', $form, $matches)) {
    throw new RuntimeException('A form must receive a single-use CSRF token.');
}

$token = $matches[1];
$_POST = ['_csrf' => $token];
csrf_verify_post();

if (isset($_SESSION['_csrf_form_tokens'][$token])) {
    throw new RuntimeException('A verified form token must be consumed to reject duplicate submissions.');
}

echo "CSRF single-use token test passed.\n";
