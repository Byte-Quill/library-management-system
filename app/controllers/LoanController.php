<?php
declare(strict_types=1);

final class LoanController
{
    public function __construct(private LoanService $loans)
    {
    }

    public function member(): void
    {
        $user = AuthorizationMiddleware::requireRole(['member']); $error = null; $success = null;
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') { try { verify_csrf($_POST['csrf_token'] ?? null); $this->loans->issue((int) $user['id'], (string) ($_POST['copy_id'] ?? '')); $success = 'Book borrowed.'; } catch (Throwable $exception) { $error = $exception instanceof InvalidArgumentException ? $exception->getMessage() : 'Unable to borrow this book.'; } }
        $loans = $this->loans->activeForMember((int) $user['id']); $availableCopies = $this->loans->availableCopies(); require dirname(__DIR__) . '/views/loans/member.php';
    }

    public function librarian(): void
    {
        AuthorizationMiddleware::requireRole(['librarian', 'administrator']); $error = null; $success = null;
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') { try { verify_csrf($_POST['csrf_token'] ?? null); $this->loans->returnLoan((string) ($_POST['loan_id'] ?? '')); $success = 'Book returned.'; } catch (Throwable $exception) { $error = $exception instanceof InvalidArgumentException ? $exception->getMessage() : 'Unable to return this book.'; } }
        $loans = $this->loans->activeForReturn(); require dirname(__DIR__) . '/views/loans/librarian.php';
    }
}