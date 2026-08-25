<?php
declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

$routes = require dirname(__DIR__) . '/app/routes.php';

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

if (isset($routes[$path])) {
    $routes[$path]($config);
    exit;
}

http_response_code(404);
header('Content-Type: text/html; charset=utf-8');
require dirname(__DIR__) . '/app/views/errors/404.php';
