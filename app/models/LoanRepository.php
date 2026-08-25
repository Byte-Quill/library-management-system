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
        $this->db->beginTransaction();
        try {
            $copy = $this->db->prepare("SELECT c.id, c.book_id, c.status FROM book_copies c INNER JOIN books b ON b.id = c.book_id WHERE c.id = :copy_id AND b.status = 'active' FOR UPDATE");
            $copy->execute(['copy_id' => $copyId]);
            $copyData = $copy->fetch();
            if (!$copyData || $copyData['status'] !== 'available') {
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
            // A ready reservation for this title is satisfied by this loan.
            $this->db->prepare("UPDATE reservations SET status = 'fulfilled', fulfilled_at = NOW() WHERE book_id = :book_id AND member_id = :member_id AND status = 'ready'")->execute(['book_id' => $copyData['book_id'], 'member_id' => $memberId]);
            $this->db->commit();
        } catch (Throwable $exception) { $this->db->rollBack(); throw $exception; }
    }

    public function activeForReturn(): array
    {
        return $this->db->query("SELECT l.id, l.due_at, l.member_id, b.title, u.name AS member_name, c.accession_number, (l.due_at < NOW()) AS is_overdue FROM loans l INNER JOIN users u ON u.id = l.member_id INNER JOIN book_copies c ON c.id = l.copy_id INNER JOIN books b ON b.id = c.book_id WHERE l.returned_at IS NULL ORDER BY l.due_at")->fetchAll();
    }

    public function returnLoan(int $loanId, array $fine): void
    {
        $this->db->beginTransaction();
        try {
            $statement = $this->db->prepare('SELECT l.copy_id, l.due_at, l.returned_at FROM loans l WHERE l.id = :id FOR UPDATE');
            $statement->execute(['id' => $loanId]); $loan = $statement->fetch();
            if (!$loan || $loan['returned_at'] !== null) throw new InvalidArgumentException('This loan has already been returned.');
            $lateDays = max(0, (int) ceil((time() - strtotime($loan['due_at'])) / 86400) - $fine['grace_days']);
            $amount = min($fine['max_amount'], $lateDays * $fine['daily_rate']);
            $update = $this->db->prepare('UPDATE loans SET returned_at = NOW(), fine_amount = :fine WHERE id = :id AND returned_at IS NULL');
            $update->execute(['fine' => number_format($amount, 2, '.', ''), 'id' => $loanId]);
            $this->db->prepare("UPDATE book_copies SET status = 'available' WHERE id = :copy_id AND status = 'borrowed'")->execute(['copy_id' => $loan['copy_id']]);
            $this->db->commit();
        } catch (Throwable $exception) { $this->db->rollBack(); throw $exception; }
    }
}