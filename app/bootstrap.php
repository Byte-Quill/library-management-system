<?php
declare(strict_types=1);

$config = require __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/helpers/session.php';
require_once __DIR__ . '/helpers/icons.php';
require_once __DIR__ . '/autoload.php';

start_secure_session($config);

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: strict-origin-when-cross-origin');
header("Content-Security-Policy: default-src 'self'; style-src 'self'; form-action 'self'; frame-ancestors 'none'; base-uri 'self'");
if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}

if ($config['env'] === 'production') {
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
}

set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
    if (!(error_reporting() & $severity)) {
        return false;
    }
    throw new ErrorException($message, 0, $severity, $file, $line);
});

set_exception_handler(static function (Throwable $exception) use ($config): void {
    error_log($exception->__toString());
    http_response_code(500);
    if (PHP_SAPI === 'cli' || $config['env'] === 'development') {
        echo 'Application error: ' . $exception->getMessage();
        return;
    }
    echo 'Something went wrong. Please try again later.';
});

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
