<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

function ar_log_exception(Throwable $e): void
{
    error_log('[AccessRank] ' . $e->getMessage());
}

function ar_escape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function ar_get_pdo(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $pdo = new PDO('sqlite:' . AR_DB_PATH, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    $pdo->exec('PRAGMA journal_mode=WAL');
    $pdo->exec('PRAGMA synchronous=NORMAL');
    $pdo->exec('PRAGMA busy_timeout=3000');
    $pdo->exec('PRAGMA foreign_keys=ON');

    ar_init_schema($pdo);

    return $pdo;
}

function ar_init_schema(PDO $pdo): void
{
    $pdo->exec('CREATE TABLE IF NOT EXISTS pv_daily (date TEXT PRIMARY KEY, count INTEGER NOT NULL)');
    $pdo->exec('CREATE TABLE IF NOT EXISTS pv_total (id INTEGER PRIMARY KEY CHECK(id=1), count INTEGER NOT NULL)');
    $pdo->exec('CREATE TABLE IF NOT EXISTS pv_rate_limit (key TEXT PRIMARY KEY, last_ts INTEGER NOT NULL)');
    $pdo->exec('CREATE TABLE IF NOT EXISTS in_daily (date TEXT NOT NULL, host TEXT NOT NULL, count INTEGER NOT NULL, PRIMARY KEY(date, host))');
    $pdo->exec('CREATE TABLE IF NOT EXISTS in_total (host TEXT PRIMARY KEY, count INTEGER NOT NULL)');
    $pdo->exec('CREATE TABLE IF NOT EXISTS admin_users (username TEXT PRIMARY KEY, password_hash TEXT NOT NULL, must_change INTEGER NOT NULL)');

    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_in_total_count ON in_total (count DESC)');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_in_daily_date ON in_daily (date)');

    $stmt = $pdo->prepare('INSERT INTO admin_users(username, password_hash, must_change) VALUES(:username, :hash, 1)
        ON CONFLICT(username) DO NOTHING');
    $stmt->execute([
        ':username' => 'admin',
        ':hash' => password_hash('pass', PASSWORD_DEFAULT),
    ]);
}

function ar_get_remote_ip(): string
{
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

function ar_is_bot_user_agent(string $userAgent): bool
{
    return (bool) preg_match('/bot|spider|crawl/i', $userAgent);
}

function ar_rate_limit_hit(PDO $pdo, string $key, int $windowSeconds): bool
{
    $now = time();
    $stmt = $pdo->prepare(
        'INSERT INTO pv_rate_limit(key, last_ts) VALUES(:key, :now)
         ON CONFLICT(key) DO UPDATE SET last_ts = :now WHERE :now - pv_rate_limit.last_ts >= :window'
    );
    $stmt->execute([
        ':key' => $key,
        ':now' => $now,
        ':window' => $windowSeconds,
    ]);

    return $stmt->rowCount() === 0;
}

function ar_increment_pv(PDO $pdo, string $date): void
{
    $stmt = $pdo->prepare('INSERT INTO pv_daily(date, count) VALUES(:date, 1)
        ON CONFLICT(date) DO UPDATE SET count = count + 1');
    $stmt->execute([':date' => $date]);

    $stmt = $pdo->prepare('INSERT INTO pv_total(id, count) VALUES(1, 1)
        ON CONFLICT(id) DO UPDATE SET count = count + 1');
    $stmt->execute();
}

function ar_increment_inbound(PDO $pdo, string $date, string $host): void
{
    $stmt = $pdo->prepare('INSERT INTO in_daily(date, host, count) VALUES(:date, :host, 1)
        ON CONFLICT(date, host) DO UPDATE SET count = count + 1');
    $stmt->execute([':date' => $date, ':host' => $host]);

    $stmt = $pdo->prepare('INSERT INTO in_total(host, count) VALUES(:host, 1)
        ON CONFLICT(host) DO UPDATE SET count = count + 1');
    $stmt->execute([':host' => $host]);
}

function ar_get_widget_cache_path(): string
{
    return __DIR__ . '/data/widget_cache.json';
}

function ar_get_rank_cache_path(): string
{
    return __DIR__ . '/rank.html';
}

function ar_get_widget_stats(PDO $pdo): array
{
    $cachePath = ar_get_widget_cache_path();
    if (is_file($cachePath) && (time() - filemtime($cachePath) < AR_WIDGET_CACHE_TTL)) {
        $cached = json_decode((string) file_get_contents($cachePath), true);
        if (is_array($cached)) {
            return $cached;
        }
    }

    $today = new DateTimeImmutable('today');
    $start = $today->modify('-13 days');
    $startDate = $start->format('Y-m-d');
    $todayDate = $today->format('Y-m-d');
    $yesterdayDate = $today->modify('-1 day')->format('Y-m-d');

    $stmt = $pdo->prepare('SELECT date, count FROM pv_daily WHERE date >= :start ORDER BY date ASC');
    $stmt->execute([':start' => $startDate]);
    $rows = $stmt->fetchAll();

    $counts = [];
    foreach ($rows as $row) {
        $counts[$row['date']] = (int) $row['count'];
    }

    $series = [];
    for ($i = 0; $i < 14; $i++) {
        $date = $start->modify("+{$i} days")->format('Y-m-d');
        $series[] = [
            'date' => $date,
            'count' => $counts[$date] ?? 0,
        ];
    }

    $total = 0;
    $stmt = $pdo->query('SELECT count FROM pv_total WHERE id = 1');
    if ($stmt !== false) {
        $row = $stmt->fetch();
        if ($row) {
            $total = (int) $row['count'];
        }
    }

    $stats = [
        'today' => $counts[$todayDate] ?? 0,
        'yesterday' => $counts[$yesterdayDate] ?? 0,
        'total' => $total,
        'series' => $series,
    ];

    file_put_contents($cachePath, json_encode($stats, JSON_UNESCAPED_UNICODE), LOCK_EX);

    return $stats;
}

function ar_rank_needs_refresh(string $rankPath): bool
{
    return !is_file($rankPath) || (time() - filemtime($rankPath) >= AR_RANK_CACHE_TTL);
}

function ar_generate_rank_html(PDO $pdo, string $rankPath): void
{
    $today = (new DateTimeImmutable('today'))->format('Y-m-d');
    $stmt = $pdo->prepare(
        'SELECT t.host, t.count AS total, COALESCE(d.count, 0) AS today
         FROM in_total t
         LEFT JOIN in_daily d ON t.host = d.host AND d.date = :date
         ORDER BY t.count DESC
         LIMIT :limit'
    );
    $stmt->bindValue(':date', $today, PDO::PARAM_STR);
    $stmt->bindValue(':limit', AR_RANK_LIMIT, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll();

    $items = '';
    $rank = 1;
    foreach ($rows as $row) {
        $host = ar_escape((string) $row['host']);
        $total = number_format((int) $row['total']);
        $todayCount = number_format((int) $row['today']);
        $items .= "<tr><td class=\"rank\">{$rank}</td><td class=\"host\">{$host}</td><td class=\"today\">{$todayCount}</td><td class=\"total\">{$total}</td></tr>";
        $rank++;
    }

    if ($items === '') {
        $items = '<tr><td colspan="4">まだデータがありません。</td></tr>';
    }

    $generatedAt = ar_escape((new DateTimeImmutable())->format('Y-m-d H:i:s'));

    $html = "<!doctype html>
<html lang=\"ja\">
<head>
<meta charset=\"utf-8\">
<meta name=\"viewport\" content=\"width=device-width,initial-scale=1\">
<title>AccessRank INランキング</title>
<style>
body{font-family:-apple-system,BlinkMacSystemFont,\"Segoe UI\",sans-serif;background:#f7f7f9;color:#222;margin:0;padding:16px;}
.table{width:100%;border-collapse:collapse;font-size:14px;background:#fff;}
.table th,.table td{border-bottom:1px solid #e6e6e6;padding:8px;text-align:left;}
.table th{background:#fafafa;font-weight:600;}
.rank{width:48px;text-align:right;color:#666;}
.today,.total{text-align:right;white-space:nowrap;}
.host{word-break:break-all;}
.footer{margin-top:12px;font-size:12px;color:#666;}
</style>
</head>
<body>
<h1 style=\"font-size:18px;margin:0 0 12px;\">AccessRank 逆アクセスランキング</h1>
<table class=\"table\">
<thead>
<tr><th>順位</th><th>参照元</th><th>今日</th><th>累計</th></tr>
</thead>
<tbody>
{$items}
</tbody>
</table>
<div class=\"footer\">更新: {$generatedAt}</div>
</body>
</html>";

    $tmpPath = $rankPath . '.tmp';
    file_put_contents($tmpPath, $html, LOCK_EX);
    rename($tmpPath, $rankPath);
}

function ar_fetch_admin_user(PDO $pdo, string $username): ?array
{
    $stmt = $pdo->prepare('SELECT username, password_hash, must_change FROM admin_users WHERE username = :username');
    $stmt->execute([':username' => $username]);
    $row = $stmt->fetch();
    if (!is_array($row)) {
        return null;
    }

    return $row;
}

function ar_update_admin_password(PDO $pdo, string $username, string $passwordHash): void
{
    $stmt = $pdo->prepare('UPDATE admin_users SET password_hash = :hash, must_change = 0 WHERE username = :username');
    $stmt->execute([
        ':hash' => $passwordHash,
        ':username' => $username,
    ]);
}
