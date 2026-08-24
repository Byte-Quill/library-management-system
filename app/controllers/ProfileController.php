<?php
declare(strict_types=1);

final class ProfileController
{
    public function __construct(private ProfileService $profiles) {}

    public function edit(): void
    {
        $user = AuthorizationMiddleware::requireAuthentication(); $error = null; $success = null;
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            try { verify_csrf($_POST['csrf_token'] ?? null); $user = $this->profiles->update((int) $user['id'], $_POST); $_SESSION['user'] = $user; $success = 'Profile updated.'; }
            catch (Throwable $exception) { $error = $exception instanceof InvalidArgumentException ? $exception->getMessage() : 'Unable to update profile.'; }
        }
        require dirname(__DIR__) . '/views/profile/edit.php';
    }
}