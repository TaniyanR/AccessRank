<?php
declare(strict_types=1);

require_once __DIR__ . '/lib.php';

header('Content-Type: application/javascript; charset=utf-8');
header('Cache-Control: private, max-age=30');
header('X-Content-Type-Options: nosniff');

$width = filter_input(INPUT_GET, 'w', FILTER_VALIDATE_INT);
if ($width === false || $width === null) {
    $width = 320;
}
$width = max(250, min(500, $width));

$id = (string) ($_GET['id'] ?? '');
if ($id !== '' && !preg_match('/^[A-Za-z0-9_-]{1,64}$/', $id)) {
    $id = '';
}

$countFlag = filter_input(INPUT_GET, 'count', FILTER_VALIDATE_INT);
$doCount = $countFlag === null ? true : ($countFlag === 1);

$stats = [
    'today' => 0,
    'yesterday' => 0,
    'total' => 0,
    'series' => [],
];

try {
    $pdo = ar_get_pdo();

    if ($doCount) {
        $userAgent = (string) ($_SERVER['HTTP_USER_AGENT'] ?? '');
        if (!ar_is_bot_user_agent($userAgent)) {
            $ip = ar_get_remote_ip();
            $key = hash('sha256', $ip . '|' . $userAgent);
            if (!ar_rate_limit_hit($pdo, $key, AR_RATE_LIMIT_WINDOW)) {
                $today = (new DateTimeImmutable('today'))->format('Y-m-d');
                ar_increment_pv($pdo, $today);
            }
        }
    }

    $stats = ar_get_widget_stats($pdo);
} catch (Throwable $e) {
    ar_log_exception($e);
}

$series = $stats['series'] ?? [];
$max = 0;
foreach ($series as $item) {
    $count = (int) ($item['count'] ?? 0);
    if ($count > $max) {
        $max = $count;
    }
}
$max = max($max, 1);

$bars = '';
foreach ($series as $item) {
    $count = (int) ($item['count'] ?? 0);
    $date = ar_escape((string) ($item['date'] ?? ''));
    $height = (int) round(($count / $max) * AR_WIDGET_BAR_HEIGHT);
    $bars .= '<div style="flex:1;display:flex;flex-direction:column;align-items:center;gap:4px;">'
        . '<div style="width:100%;background:#4c7cf0;height:' . $height . 'px;border-radius:3px 3px 0 0;" title="' . $date . ' ' . $count . '"></div>'
        . '<div style="font-size:10px;color:#666;">' . substr($date, 5) . '</div>'
        . '</div>';
}

$today = number_format((int) ($stats['today'] ?? 0));
$yesterday = number_format((int) ($stats['yesterday'] ?? 0));
$total = number_format((int) ($stats['total'] ?? 0));

$html = '<div style="width:' . $width . 'px;max-width:100%;border:1px solid #e3e3e3;border-radius:8px;padding:12px;background:#fff;font-family:-apple-system,BlinkMacSystemFont,\"Segoe UI\",sans-serif;box-sizing:border-box;">'
    . '<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">'
    . '<div style="font-size:14px;font-weight:600;color:#222;">AccessRank</div>'
    . '<div style="font-size:12px;color:#666;">今日/昨日/総PV</div>'
    . '</div>'
    . '<div style="display:flex;gap:12px;margin-bottom:10px;">'
    . '<div style="flex:1;background:#f6f7fb;border-radius:6px;padding:8px;text-align:center;">'
    . '<div style="font-size:11px;color:#666;">今日</div>'
    . '<div style="font-size:16px;font-weight:700;color:#222;">' . $today . '</div>'
    . '</div>'
    . '<div style="flex:1;background:#f6f7fb;border-radius:6px;padding:8px;text-align:center;">'
    . '<div style="font-size:11px;color:#666;">昨日</div>'
    . '<div style="font-size:16px;font-weight:700;color:#222;">' . $yesterday . '</div>'
    . '</div>'
    . '<div style="flex:1;background:#f6f7fb;border-radius:6px;padding:8px;text-align:center;">'
    . '<div style="font-size:11px;color:#666;">総PV</div>'
    . '<div style="font-size:16px;font-weight:700;color:#222;">' . $total . '</div>'
    . '</div>'
    . '</div>'
    . '<div style="font-size:12px;color:#666;margin-bottom:6px;">直近14日</div>'
    . '<div style="display:flex;align-items:flex-end;gap:4px;height:' . AR_WIDGET_BAR_HEIGHT . 'px;">' . $bars . '</div>'
    . '</div>';

$payload = json_encode($html, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);

if ($payload === false) {
    $payload = '""';
}

if ($id !== '') {
    echo "(function(){var el=document.getElementById(" . json_encode($id) . ");if(el){el.innerHTML={$payload};}})();";
} else {
    echo "document.write({$payload});";
}
