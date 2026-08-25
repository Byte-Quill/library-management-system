<?php
declare(strict_types=1);

function start_secure_session(array $config): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    ini_set('session.use_strict_mode', '1');

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => is_https(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();

    if (isset($_SESSION['last_activity']) && time() - (int) $_SESSION['last_activity'] > $config['session_timeout']) {
        $_SESSION = [];
        session_regenerate_id(true);
    }
    $_SESSION['last_activity'] = time();

    if (!isset($_SESSION['created_at'])) {
        $_SESSION['created_at'] = time();
    }
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function is_https(): bool
{
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        return true;
    }
    // Typical behind TLS-terminating proxies on shared hosting.
    return ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
}

function verify_csrf(?string $token): void
{
    if (!$token || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(419);
        exit('Invalid request token.');
    }
}
