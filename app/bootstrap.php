<?php
declare(strict_types=1);

$config = require __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/helpers/session.php';
require_once __DIR__ . '/middleware/AuthorizationMiddleware.php';
require_once __DIR__ . '/models/CategoryRepository.php';
require_once __DIR__ . '/models/AuthorRepository.php';
require_once __DIR__ . '/models/BookRepository.php';
require_once __DIR__ . '/services/CategoryService.php';
require_once __DIR__ . '/services/AuthorService.php';
require_once __DIR__ . '/services/CatalogService.php';
require_once __DIR__ . '/controllers/CatalogController.php';
require_once __DIR__ . '/controllers/DashboardController.php';
require_once __DIR__ . '/controllers/CategoryController.php';
require_once __DIR__ . '/controllers/AuthorController.php';
require_once __DIR__ . '/services/AuthService.php';

start_secure_session($config);

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
