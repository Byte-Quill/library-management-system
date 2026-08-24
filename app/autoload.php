<?php
declare(strict_types=1);

spl_autoload_register(static function (string $class): void {
    $directories = ['controllers', 'middleware', 'models', 'services'];
    foreach ($directories as $directory) {
        $file = __DIR__ . '/' . $directory . '/' . $class . '.php';
        if (is_file($file)) {
            require_once $file;
            return;
        }
    }
});