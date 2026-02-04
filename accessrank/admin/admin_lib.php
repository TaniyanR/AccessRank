<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib.php';

function ar_admin_start_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function ar_admin_get_csrf_token(): string
{
    ar_admin_start_session();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return (string) $_SESSION['csrf_token'];
}

function ar_admin_verify_csrf(?string $token): bool
{
    ar_admin_start_session();
    if ($token === null || $token === '') {
        return false;
    }

    return hash_equals((string) ($_SESSION['csrf_token'] ?? ''), $token);
}

function ar_admin_get_logged_in_user(): ?string
{
    ar_admin_start_session();
    $username = $_SESSION['admin_user'] ?? '';
    if (!is_string($username) || $username === '') {
        return null;
    }

    return $username;
}

function ar_admin_require_login(): string
{
    $username = ar_admin_get_logged_in_user();
    if ($username === null) {
        header('Location: login.php');
        exit;
    }

    return $username;
}

function ar_admin_require_password_change(PDO $pdo, string $username): void
{
    $user = ar_fetch_admin_user($pdo, $username);
    if (!$user) {
        session_destroy();
        header('Location: login.php');
        exit;
    }

    if ((int) $user['must_change'] === 1) {
        $current = basename((string) ($_SERVER['SCRIPT_NAME'] ?? ''));
        if ($current !== 'change_password.php') {
            header('Location: change_password.php');
            exit;
        }
    }
}

function ar_admin_render_header(string $title): void
{
    $safeTitle = ar_escape($title);
    echo "<!doctype html>\n";
    echo "<html lang=\"ja\">\n";
    echo "<head>\n";
    echo "<meta charset=\"utf-8\">\n";
    echo "<meta name=\"viewport\" content=\"width=device-width,initial-scale=1\">\n";
    echo "<title>{$safeTitle}</title>\n";
    echo "<style>";
    echo "body{font-family:-apple-system,BlinkMacSystemFont,\"Segoe UI\",sans-serif;background:#f7f7f9;color:#222;margin:0;padding:24px;}";
    echo ".card{max-width:520px;margin:0 auto;background:#fff;border:1px solid #e6e6e6;border-radius:10px;padding:20px;box-shadow:0 2px 8px rgba(0,0,0,0.04);}";
    echo ".title{font-size:18px;font-weight:700;margin:0 0 16px;}";
    echo ".field{margin-bottom:12px;}";
    echo ".label{display:block;font-size:13px;color:#555;margin-bottom:6px;}";
    echo ".input{width:100%;padding:10px 12px;border:1px solid #ccc;border-radius:6px;font-size:14px;box-sizing:border-box;}";
    echo ".button{display:inline-block;background:#4c7cf0;color:#fff;border:0;border-radius:6px;padding:10px 16px;font-size:14px;cursor:pointer;}";
    echo ".link{color:#4c7cf0;text-decoration:none;font-size:14px;}";
    echo ".error{background:#fff2f2;border:1px solid #f2b8b8;color:#b00020;padding:10px;border-radius:6px;margin-bottom:12px;font-size:13px;}";
    echo ".notice{background:#f4f7ff;border:1px solid #d4e0ff;color:#274690;padding:10px;border-radius:6px;margin-bottom:12px;font-size:13px;}";
    echo "</style>\n";
    echo "</head>\n";
    echo "<body>\n";
}

function ar_admin_render_footer(): void
{
    echo "</body>\n</html>";
}
