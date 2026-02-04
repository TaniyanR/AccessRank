<?php
declare(strict_types=1);

require_once __DIR__ . '/admin_lib.php';

$username = ar_admin_get_logged_in_user();
if ($username === null) {
    header('Location: login.php');
    exit;
}

header('Location: dashboard.php');
exit;
