<?php
declare(strict_types=1);

final class AuthorizationMiddleware
{
    public static function currentUser(): ?array
    {
        return isset($_SESSION['user']) && is_array($_SESSION['user']) ? $_SESSION['user'] : null;
    }

    public static function requireAuthentication(): array
    {
        $user = self::currentUser();
        if ($user === null) {
            header('Location: /login', true, 303);
            exit;
        }
        return $user;
    }

    public static function requireRole(array $roles): array
    {
        $user = self::requireAuthentication();
        if (!in_array($user['role'] ?? '', $roles, true)) {
            http_response_code(403);
            require dirname(__DIR__) . '/views/errors/403.php';
            exit;
        }
        return $user;
    }
}