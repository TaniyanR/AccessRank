<?php
declare(strict_types=1);

require_once __DIR__ . '/admin_lib.php';

ar_admin_start_session();

$username = ar_admin_get_logged_in_user();
if ($username !== null) {
    try {
        $pdo = ar_get_pdo();
        ar_admin_require_password_change($pdo, $username);
    } catch (Throwable $e) {
        ar_log_exception($e);
    }
    header('Location: dashboard.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    $inputUser = (string) ($_POST['username'] ?? '');
    $inputPass = (string) ($_POST['password'] ?? '');

    if (!ar_admin_verify_csrf($csrf)) {
        $error = 'セッションが期限切れです。もう一度お試しください。';
    } else {
        try {
            $pdo = ar_get_pdo();
            $user = ar_fetch_admin_user($pdo, $inputUser);
            if ($user && password_verify($inputPass, (string) $user['password_hash'])) {
                session_regenerate_id(true);
                $_SESSION['admin_user'] = $inputUser;
                if ((int) $user['must_change'] === 1) {
                    header('Location: change_password.php');
                    exit;
                }
                header('Location: dashboard.php');
                exit;
            }
        } catch (Throwable $e) {
            ar_log_exception($e);
        }
        $error = 'ユーザー名またはパスワードが違います。';
    }
}

ar_admin_render_header('AccessRank 管理ログイン');
?>
<div class="card">
    <h1 class="title">AccessRank 管理ログイン</h1>
    <?php if ($error !== ''): ?>
        <div class="error"><?php echo ar_escape($error); ?></div>
    <?php endif; ?>
    <form method="post" action="login.php">
        <input type="hidden" name="csrf_token" value="<?php echo ar_escape(ar_admin_get_csrf_token()); ?>">
        <div class="field">
            <label class="label" for="username">ユーザー名</label>
            <input class="input" type="text" name="username" id="username" required autocomplete="username">
        </div>
        <div class="field">
            <label class="label" for="password">パスワード</label>
            <input class="input" type="password" name="password" id="password" required autocomplete="current-password">
        </div>
        <button class="button" type="submit">ログイン</button>
    </form>
    <p style="margin-top:12px;font-size:12px;color:#666;">初期ログインは admin / pass です。初回ログイン後に変更が必要です。</p>
</div>
<?php
ar_admin_render_footer();
