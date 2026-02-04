<?php
declare(strict_types=1);

require_once __DIR__ . '/admin_lib.php';

$username = ar_admin_require_login();

try {
    $pdo = ar_get_pdo();
    ar_admin_require_password_change($pdo, $username);
} catch (Throwable $e) {
    ar_log_exception($e);
    session_destroy();
    header('Location: login.php');
    exit;
}

ar_admin_render_header('AccessRank 管理ダッシュボード');
?>
<div class="card">
    <h1 class="title">AccessRank 管理ダッシュボード</h1>
    <p style="font-size:14px;margin-top:0;">ログイン中: <?php echo ar_escape($username); ?></p>
    <ul style="padding-left:18px;font-size:14px;">
        <li><a class="link" href="change_password.php">パスワード変更</a></li>
        <li><a class="link" href="logout.php">ログアウト</a></li>
    </ul>
    <p style="font-size:12px;color:#666;">必要に応じて <code>config.php</code> の内部ホスト設定を確認してください。</p>
</div>
<?php
ar_admin_render_footer();
