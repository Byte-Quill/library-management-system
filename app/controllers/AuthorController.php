<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/middleware/AuthorizationMiddleware.php';

final class AuthorController
{
    public function __construct(private AuthorService $authors)
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
                if (($_POST['action'] ?? '') === 'archive') {
                    $this->authors->archive((string) ($_POST['id'] ?? ''));
                    $success = 'Author archived.';
                } else {
                    $this->authors->create((string) ($_POST['name'] ?? ''));
                    $success = 'Author created.';
                }
            } catch (Throwable $exception) {
                $error = $exception instanceof InvalidArgumentException ? $exception->getMessage() : 'Unable to update authors.';
                error_log($exception->__toString());
            }
        }
        $authors = $this->authors->list();
        require dirname(__DIR__) . '/views/authors/index.php';
    }
}