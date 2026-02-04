<?php
declare(strict_types=1);

require_once __DIR__ . '/admin_lib.php';

$username = ar_admin_require_login();

$error = '';
$notice = '';

try {
    $pdo = ar_get_pdo();
    $user = ar_fetch_admin_user($pdo, $username);
    if (!$user) {
        session_destroy();
        header('Location: login.php');
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $csrf = $_POST['csrf_token'] ?? '';
        $password = (string) ($_POST['password'] ?? '');
        $confirm = (string) ($_POST['password_confirm'] ?? '');

        if (!ar_admin_verify_csrf($csrf)) {
            $error = 'セッションが期限切れです。もう一度お試しください。';
        } elseif ($password === '' || $confirm === '') {
            $error = 'パスワードを入力してください。';
        } elseif ($password !== $confirm) {
            $error = 'パスワードが一致しません。';
        } elseif (strlen($password) < 8) {
            $error = 'パスワードは8文字以上にしてください。';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            ar_update_admin_password($pdo, $username, $hash);
            $notice = 'パスワードを更新しました。';
        }
    }

    ar_admin_require_password_change($pdo, $username);
} catch (Throwable $e) {
    ar_log_exception($e);
    $error = '処理中にエラーが発生しました。';
}

ar_admin_render_header('AccessRank パスワード変更');
?>
<div class="card">
    <h1 class="title">パスワード変更</h1>
    <?php if ($error !== ''): ?>
        <div class="error"><?php echo ar_escape($error); ?></div>
    <?php elseif ($notice !== ''): ?>
        <div class="notice"><?php echo ar_escape($notice); ?></div>
    <?php endif; ?>
    <form method="post" action="change_password.php">
        <input type="hidden" name="csrf_token" value="<?php echo ar_escape(ar_admin_get_csrf_token()); ?>">
        <div class="field">
            <label class="label" for="password">新しいパスワード</label>
            <input class="input" type="password" name="password" id="password" required autocomplete="new-password">
        </div>
        <div class="field">
            <label class="label" for="password_confirm">確認</label>
            <input class="input" type="password" name="password_confirm" id="password_confirm" required autocomplete="new-password">
        </div>
        <button class="button" type="submit">更新する</button>
    </form>
    <p style="margin-top:12px;font-size:12px;color:#666;">初回ログイン時は必ず変更してください。</p>
</div>
<?php
ar_admin_render_footer();
