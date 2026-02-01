<?php
declare(strict_types=1);

require_once __DIR__ . '/lib.php';

header('Content-Type: image/gif');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

$gif = "GIF89a\x01\x00\x01\x00\x80\x00\x00\x00\x00\x00\xFF\xFF\xFF\x21\xF9\x04\x01\x00\x00\x00\x00\x2C\x00\x00\x00\x00\x01\x00\x01\x00\x00\x02\x02\x4C\x01\x00\x3B";

echo $gif;

$userAgent = (string) ($_SERVER['HTTP_USER_AGENT'] ?? '');
if (ar_is_bot_user_agent($userAgent)) {
    return;
}

$ip = ar_get_remote_ip();
$key = hash('sha256', $ip . '|' . $userAgent);

try {
    $pdo = ar_get_pdo();

    if (ar_rate_limit_hit($pdo, $key, AR_RATE_LIMIT_WINDOW)) {
        return;
    }

    $today = (new DateTimeImmutable('today'))->format('Y-m-d');
    ar_increment_pv($pdo, $today);
} catch (Throwable $e) {
    ar_log_exception($e);
}
