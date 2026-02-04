<?php
declare(strict_types=1);

require_once __DIR__ . '/admin_lib.php';

ar_admin_start_session();
$_SESSION = [];
if (session_id() !== '') {
    session_destroy();
}

header('Location: login.php');
exit;
