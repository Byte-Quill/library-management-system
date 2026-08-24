<?php
declare(strict_types=1);

final class BookManagementController
{
    public function __construct(private BookManagementService $books, private CategoryRepository $categories, private AuthorRepository $authors) {}

    public function index(): void
    {
        AuthorizationMiddleware::requireRole(['librarian', 'administrator']);
        $error = null; $success = null;
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            try {
                verify_csrf($_POST['csrf_token'] ?? null);
                if (($_POST['action'] ?? '') === 'archive') { $this->books->archive((string) ($_POST['id'] ?? '')); $success = 'Book archived.'; }
                else { $this->books->create($_POST, $_FILES); $success = 'Book created.'; }
            } catch (Throwable $exception) { $error = $exception instanceof InvalidArgumentException ? $exception->getMessage() : 'Unable to update books.'; error_log($exception->__toString()); }
        }
        $books = $this->books->all(); $categories = $this->categories->all(); $authors = $this->authors->all();
        require dirname(__DIR__) . '/views/books/index.php';
    }
}