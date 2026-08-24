<?php
declare(strict_types=1);

final class ReservationController
{
    public function __construct(private ReservationService $reservations)
    {
    }

    public function member(): void
    {
        $user = AuthorizationMiddleware::requireRole(['member']); $error = null; $success = null;
        try { $this->reservations->refresh(); } catch (Throwable $exception) { error_log($exception->__toString()); }
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') { try { verify_csrf($_POST['csrf_token'] ?? null); if (($_POST['action'] ?? '') === 'cancel') $this->reservations->cancel((int) $user['id'], (string) ($_POST['id'] ?? '')); else $this->reservations->create((int) $user['id'], (string) ($_POST['book_id'] ?? '')); $success = 'Reservation updated.'; } catch (Throwable $exception) { $error = $exception instanceof InvalidArgumentException ? $exception->getMessage() : 'Unable to update reservation.'; } }
        $reservations = $this->reservations->forMember((int) $user['id']); require dirname(__DIR__) . '/views/reservations/member.php';
    }

    public function librarian(): void
    {
        AuthorizationMiddleware::requireRole(['librarian', 'administrator']); $this->reservations->refresh(); $reservations = $this->reservations->allActive(); require dirname(__DIR__) . '/views/reservations/librarian.php';
    }
}