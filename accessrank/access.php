<?php
declare(strict_types=1);

require_once __DIR__ . '/lib.php';

header('Content-Type: application/javascript; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

$referrer = (string) ($_GET['referrer'] ?? '');
$referrer = trim($referrer);

if ($referrer === '' || strlen($referrer) > 2048) {
    echo "/* AccessRank */";
    return;
}

$parts = parse_url($referrer);
if (!is_array($parts) || !isset($parts['scheme'], $parts['host'])) {
    echo "/* AccessRank */";
    return;
}

$scheme = strtolower((string) $parts['scheme']);
if (!in_array($scheme, ['http', 'https'], true)) {
    echo "/* AccessRank */";
    return;
}

$host = strtolower((string) $parts['host']);
if ($host === '') {
    echo "/* AccessRank */";
    return;
}

if (AR_EXCLUDE_INTERNAL) {
    $internalHosts = array_map('strtolower', AR_INTERNAL_HOSTS);
    if (in_array($host, $internalHosts, true)) {
        echo "/* AccessRank */";
        return;
    }
}

try {
    $pdo = ar_get_pdo();
    $today = (new DateTimeImmutable('today'))->format('Y-m-d');
    ar_increment_inbound($pdo, $today, $host);
} catch (Throwable $e) {
    ar_log_exception($e);
}

echo "/* AccessRank */";
