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

        // Revalidate against the database so suspended or removed accounts
        // lose access immediately instead of when the session expires.
        try {
            $fresh = (new UserRepository(database($GLOBALS['config'])))->find((int) ($user['id'] ?? 0));
        } catch (Throwable $exception) {
            error_log($exception->__toString());
            return $user; // Database unavailable; keep the cached session user.
        }
        if ($fresh === null || ($fresh['status'] ?? '') !== 'active') {
            $_SESSION = [];
            session_regenerate_id(true);
            header('Location: /login', true, 303);
            exit;
        }
        unset($fresh['password_hash']);
        $_SESSION['user'] = $fresh;
        return $fresh;
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