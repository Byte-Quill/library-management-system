<?php
declare(strict_types=1);

function start_secure_session(array $config): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    ini_set('session.use_strict_mode', '1');
    // Maximise session ID entropy.
    ini_set('session.sid_length', '48');
    ini_set('session.sid_bits_per_character', '6');

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => is_https($config),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();

    if (isset($_SESSION['last_activity']) && time() - (int) $_SESSION['last_activity'] > $config['session_timeout']) {
        $_SESSION = [];
        session_regenerate_id(true);
    }

    // Absolute session lifetime: force re-authentication even if the user
    // stays continuously active.
    $absolute = (int) ($config['session_absolute'] ?? 0);
    if ($absolute > 0 && isset($_SESSION['created_at']) && time() - (int) $_SESSION['created_at'] > $absolute) {
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

/**
 * Read a request parameter as a string. PHP lets clients send any field as an
 * array (e.g. ?q[]=x); casting an array to string raises a warning that the
 * application error handler turns into a fatal error, so coerce safely.
 */
function str_param(mixed $value, string $default = ''): string
{
    return is_scalar($value) ? (string) $value : $default;
}

function csrf_regenerate(): string
{
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf_token'];
}

function is_https(array $config = []): bool
{
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        return true;
    }
    // Only trust X-Forwarded-Proto when the direct peer is a configured
    // TLS-terminating proxy; otherwise the header is attacker-controlled.
    $proxies = $config['trusted_proxies'] ?? [];
    $remote = $_SERVER['REMOTE_ADDR'] ?? '';
    if ($proxies !== [] && in_array($remote, $proxies, true)) {
        return ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
    }
    return false;
}

function verify_csrf(mixed $token): void
{
    if (!is_string($token) || $token === '' || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        error_log('CSRF validation failed for ' . ($_SERVER['REQUEST_METHOD'] ?? '?') . ' ' . ($_SERVER['REQUEST_URI'] ?? '?'));
        http_response_code(419);
        header('Content-Type: text/html; charset=utf-8');
        exit('<!doctype html><html lang="en"><head><meta charset="utf-8"><title>Invalid request</title></head><body><h1>Invalid request token.</h1><p>Your session may have expired. Please go back and try again.</p></body></html>');
    }
}
