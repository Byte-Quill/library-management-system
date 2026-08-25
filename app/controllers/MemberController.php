<?php
declare(strict_types=1);

final class MemberController
{
    public function __construct(private MemberService $members)
    {
    }

    public function index(): void
    {
        AuthorizationMiddleware::requireRole(['librarian', 'administrator']);
        $error = null;
        $success = null;
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            try {
                verify_csrf($_POST['csrf_token'] ?? null);
                $this->members->create(
                    (string) ($_POST['name'] ?? ''),
                    (string) ($_POST['email'] ?? ''),
                    (string) ($_POST['password'] ?? '')
                );
                $success = 'Member created.';
            } catch (Throwable $exception) {
                $error = $exception instanceof InvalidArgumentException ? $exception->getMessage() : 'Unable to create member.';
                error_log($exception->__toString());
            }
        }
        $members = $this->members->list();
        require dirname(__DIR__) . '/views/members/index.php';
    }
}
