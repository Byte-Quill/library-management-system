<?php
declare(strict_types=1);

final class LoanRepository
{
    public function __construct(private PDO $db)
    {
    }

    public function activeForMember(int $memberId): array
    {
        $statement = $this->db->prepare("SELECT l.id, l.due_at, b.title, c.accession_number, c.id AS copy_id FROM loans l INNER JOIN book_copies c ON c.id = l.copy_id INNER JOIN books b ON b.id = c.book_id WHERE l.member_id = :member_id AND l.returned_at IS NULL ORDER BY l.due_at");
        $statement->execute(['member_id' => $memberId]);
        return $statement->fetchAll();
    }

    public function issue(int $memberId, int $copyId, int $maxActiveLoans, int $loanDays): void
    {
        // Retry once on InnoDB deadlock: this transaction locks copies then
        // reservations, while expireAndPromote() can lock them in the
        // opposite order. A deadlock is a clean rollback, so replaying the
        // whole check-then-insert is safe.
        for ($attempt = 0; $attempt < 2; $attempt++) {
            $this->db->beginTransaction();
            try {
                $this->issueWithinTransaction($memberId, $copyId, $maxActiveLoans, $loanDays);
                $this->db->commit();
                return;
            } catch (Throwable $exception) {
                if ($this->db->inTransaction()) {
                    $this->db->rollBack();
                }
                $deadlock = $exception instanceof PDOException
                    && ($exception->getCode() === '40001' || str_contains($exception->getMessage(), 'Deadlock found'));
                if ($deadlock && $attempt === 0) {
                    continue;
                }
                throw $exception;
            }
        }
    }

    private function issueWithinTransaction(int $memberId, int $copyId, int $maxActiveLoans, int $loanDays): void
    {
        $copy = $this->db->prepare("SELECT c.id, c.book_id, c.status FROM book_copies c INNER JOIN books b ON b.id = c.book_id WHERE c.id = :copy_id AND b.status = 'active' FOR UPDATE");
            $copy->execute(['copy_id' => $copyId]);
            $copyData = $copy->fetch();
            if (!$copyData) {
                throw new InvalidArgumentException('This copy is not available.');
            }
            if ($copyData['status'] === 'reserved') {
                // A copy held for a ready reservation may only be borrowed by
                // the member it is held for. Locks the reservation row so a
                // concurrent expiry cannot release it mid-transaction.
                $held = $this->db->prepare("SELECT COUNT(*) FROM reservations WHERE book_id = :book_id AND member_id = :member_id AND status = 'ready' FOR UPDATE");
                $held->execute(['book_id' => $copyData['book_id'], 'member_id' => $memberId]);
                if ((int) $held->fetchColumn() < 1) {
                    throw new InvalidArgumentException('This copy is not available.');
                }
            } elseif ($copyData['status'] !== 'available') {
                throw new InvalidArgumentException('This copy is not available.');
            }
            $active = $this->db->prepare('SELECT COUNT(*) FROM loans WHERE member_id = :member_id AND returned_at IS NULL');
            $active->execute(['member_id' => $memberId]);
            if ((int) $active->fetchColumn() >= $maxActiveLoans) {
                throw new InvalidArgumentException('The active loan limit has been reached.');
            }
            $duplicate = $this->db->prepare('SELECT COUNT(*) FROM loans l INNER JOIN book_copies c ON c.id = l.copy_id WHERE l.member_id = :member_id AND c.book_id = :book_id AND l.returned_at IS NULL');
            $duplicate->execute(['member_id' => $memberId, 'book_id' => $copyData['book_id']]);
            if ((int) $duplicate->fetchColumn() > 0) {
                throw new InvalidArgumentException('You already have an active loan for this title.');
            }
            // Compute issued_at and due_at on the database server so both
            // timestamps share one clock and timezone.
            $loan = $this->db->prepare('INSERT INTO loans (copy_id, member_id, issued_at, due_at) VALUES (:copy_id, :member_id, NOW(), DATE_ADD(NOW(), INTERVAL :loan_days DAY))');
            $loan->bindValue(':copy_id', $copyId, PDO::PARAM_INT);
            $loan->bindValue(':member_id', $memberId, PDO::PARAM_INT);
            $loan->bindValue(':loan_days', $loanDays, PDO::PARAM_INT);
            $loan->execute();
            $this->db->prepare("UPDATE book_copies SET status = 'borrowed' WHERE id = :copy_id")->execute(['copy_id' => $copyId]);
            // Any active reservation for this title is satisfied by this loan.
            // Drop an older fulfilled row first so the unique key on
            // (book_id, member_id, status) cannot collide.
            $this->db->prepare("DELETE FROM reservations WHERE book_id = :book_id AND member_id = :member_id AND status = 'fulfilled'")->execute(['book_id' => $copyData['book_id'], 'member_id' => $memberId]);
            $this->db->prepare("UPDATE reservations SET status = 'fulfilled', fulfilled_at = NOW() WHERE book_id = :book_id AND member_id = :member_id AND status IN ('pending', 'ready')")->execute(['book_id' => $copyData['book_id'], 'member_id' => $memberId]);
            // Release held copies that no longer match a ready reservation
            // (the borrower may have taken a different copy than the hold).
            $ready = $this->db->prepare("SELECT COUNT(*) FROM reservations WHERE book_id = :book_id AND status = 'ready' FOR UPDATE");
            $ready->execute(['book_id' => $copyData['book_id']]);
            $reserved = $this->db->prepare("SELECT COUNT(*) FROM book_copies WHERE book_id = :book_id AND status = 'reserved' FOR UPDATE");
            $reserved->execute(['book_id' => $copyData['book_id']]);
            $excess = (int) $reserved->fetchColumn() - (int) $ready->fetchColumn();
            if ($excess > 0) {
                $releaseHeld = $this->db->prepare("UPDATE book_copies SET status = 'available' WHERE book_id = :book_id AND status = 'reserved' ORDER BY id LIMIT 1");
                for ($i = 0; $i < $excess; $i++) {
                    $releaseHeld->execute(['book_id' => $copyData['book_id']]);
                }
            }
    }

    public function activeForReturn(): array
    {
        return $this->db->query("SELECT l.id, l.due_at, l.member_id, b.title, u.name AS member_name, c.accession_number, (l.due_at < NOW()) AS is_overdue FROM loans l INNER JOIN users u ON u.id = l.member_id INNER JOIN book_copies c ON c.id = l.copy_id INNER JOIN books b ON b.id = c.book_id WHERE l.returned_at IS NULL ORDER BY l.due_at")->fetchAll();
    }

    public function returnLoan(int $loanId, array $fine): void
    {
        $this->db->beginTransaction();
        try {
            // Compute lateness on the database server: due_at was written
            // with NOW(), so the app server clock must not decide the fine.
            $statement = $this->db->prepare('SELECT l.copy_id, l.due_at, l.returned_at, GREATEST(0, CEIL(TIMESTAMPDIFF(SECOND, l.due_at, NOW()) / 86400)) AS late_days FROM loans l WHERE l.id = :id FOR UPDATE');
            $statement->execute(['id' => $loanId]); $loan = $statement->fetch();
            if (!$loan || $loan['returned_at'] !== null) throw new InvalidArgumentException('This loan has already been returned.');
            $lateDays = max(0, (int) $loan['late_days'] - $fine['grace_days']);
            $amount = min($fine['max_amount'], $lateDays * $fine['daily_rate']);
            $update = $this->db->prepare('UPDATE loans SET returned_at = NOW(), fine_amount = :fine WHERE id = :id AND returned_at IS NULL');
            $update->execute(['fine' => number_format($amount, 2, '.', ''), 'id' => $loanId]);
            $this->db->prepare("UPDATE book_copies SET status = 'available' WHERE id = :copy_id AND status = 'borrowed'")->execute(['copy_id' => $loan['copy_id']]);
            $this->db->commit();
        } catch (Throwable $exception) { $this->db->rollBack(); throw $exception; }
    }
}