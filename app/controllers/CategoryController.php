<?php
declare(strict_types=1);

final class CategoryController
{
    public function __construct(private CategoryService $categories)
    {
    }

    public function index(): void
    {
        $user = AuthorizationMiddleware::requireRole(['librarian', 'administrator']);
        $error = null;
        $success = null;
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            try {
                verify_csrf($_POST['csrf_token'] ?? null);
                if (($_POST['action'] ?? '') === 'archive') {
                    $this->categories->archive((string) ($_POST['id'] ?? ''));
                    $success = 'Category archived.';
                } else {
                    $this->categories->create((string) ($_POST['name'] ?? ''));
                    $success = 'Category created.';
                }
            } catch (Throwable $exception) {
                $error = $exception instanceof InvalidArgumentException ? $exception->getMessage() : 'Unable to update categories.';
                error_log($exception->__toString());
            }
        }
        $categories = $this->categories->list();
        require dirname(__DIR__) . '/views/categories/index.php';
    }
}