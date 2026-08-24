<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/services/CopyService.php';
require_once dirname(__DIR__) . '/models/BookManagementRepository.php';

final class CopyController
{
    public function __construct(private CopyService $copies, private BookManagementRepository $books)
    {
    }

    public function index(): void
    {
        AuthorizationMiddleware::requireRole(['librarian', 'administrator']);
        $error = null; $success = null;
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            try {
                verify_csrf($_POST['csrf_token'] ?? null);
                if (($_POST['action'] ?? '') === 'status') { $this->copies->updateStatus((string) ($_POST['id'] ?? ''), (string) ($_POST['status'] ?? '')); $success = 'Copy status updated.'; }
                else { $this->copies->create($_POST); $success = 'Physical copy created.'; }
            } catch (Throwable $exception) { $error = $exception instanceof InvalidArgumentException ? $exception->getMessage() : 'Unable to update physical copies.'; error_log($exception->__toString()); }
        }
        $copies = $this->copies->all(); $books = array_values(array_filter($this->books->all(), static fn (array $book): bool => $book['status'] === 'active'));
        require dirname(__DIR__) . '/views/copies/index.php';
    }
}