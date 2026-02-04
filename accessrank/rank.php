<?php
declare(strict_types=1);

require_once __DIR__ . '/lib.php';

header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

$rankPath = ar_get_rank_cache_path();

try {
    $pdo = ar_get_pdo();

    if (ar_rank_needs_refresh($rankPath)) {
        $lockPath = $rankPath . '.lock';
        $lockHandle = fopen($lockPath, 'c');
        if ($lockHandle !== false) {
            if (flock($lockHandle, LOCK_EX | LOCK_NB)) {
                if (ar_rank_needs_refresh($rankPath)) {
                    ar_generate_rank_html($pdo, $rankPath);
                }
                flock($lockHandle, LOCK_UN);
            }
            fclose($lockHandle);
        }
    }

    if (is_file($rankPath)) {
        echo file_get_contents($rankPath);
        return;
    }
} catch (Throwable $e) {
    ar_log_exception($e);
}

?>
<!doctype html>
<html lang="ja">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>AccessRank INランキング</title>
<style>
body{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;background:#f7f7f9;color:#222;margin:0;padding:16px;}
.notice{background:#fff;border:1px solid #e6e6e6;border-radius:8px;padding:16px;font-size:14px;}
</style>
</head>
<body>
<div class="notice">AccessRank 逆アクセスランキングは準備中です。</div>
</body>
</html>
