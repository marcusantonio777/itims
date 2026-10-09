<?php
if (PHP_SAPI === 'cli') {
    require_once __DIR__ . '/install/database_migrate.php';
} else {
    header('Location: install/database_migrate.php');
    exit;
}