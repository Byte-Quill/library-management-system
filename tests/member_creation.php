<?php
declare(strict_types=1);

require dirname(__DIR__) . '/app/autoload.php';
require dirname(__DIR__) . '/app/bootstrap.php';

if (!method_exists(AuthService::class, 'createMember')) {
    throw new RuntimeException('AuthService::createMember is missing.');
}

echo "member creation API is available.\n";
