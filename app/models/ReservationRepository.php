<?php
declare(strict_types=1);

final class ReservationRepository
{
    public function __construct(private PDO $db)
    {
    }

    public function forMember(int $memberId): array
    {
        $statement = $this->db->prepare('SELECT r.id, r.status, r.created_at, r.expires_at, b.title FROM reservations r INNER JOIN books b ON b.id = r.book_id WHERE r.member_id = :member_id ORDER BY r.created_at DESC');
        $statement->execute(['member_id' => $memberId]);
        return $statement->fetchAll();
    }

    public function allActive(): array
    {
        return $this->db->query("SELECT r.id, r.status, r.created_at, r.expires_at, b.title, u.name AS member_name FROM reservations r INNER JOIN books b ON b.id = r.book_id INNER JOIN users u ON u.id = r.member_id WHERE r.status IN ('pending', 'ready') ORDER BY r.book_id, r.created_at")->fetchAll();
    }

    public function create(int $memberId, int $bookId): void
    {
        $this->db->beginTransaction();
        try {
            $available = $this->db->prepare("SELECT COUNT(*) FROM book_copies WHERE book_id = :book_id AND status = 'available'");
            $available->execute(['book_id' => $bookId]);
            if ((int) $available->fetchColumn() > 0) throw new InvalidArgumentException('This title is currently available to borrow.');
            $duplicate = $this->db->prepare("SELECT COUNT(*) FROM reservations WHERE book_id = :book_id AND member_id = :member_id AND status IN ('pending', 'ready')");
            $duplicate->execute(['book_id' => $bookId, 'member_id' => $memberId]);
            if ((int) $duplicate->fetchColumn() > 0) throw new InvalidArgumentException('You already have an active reservation for this title.');
            $statement = $this->db->prepare("INSERT INTO reservations (book_id, member_id, status) SELECT id, :member_id, 'pending' FROM books WHERE id = :book_id AND status = 'active'");
            $statement->execute(['book_id' => $bookId, 'member_id' => $memberId]);
            if ($statement->rowCount() !== 1) throw new InvalidArgumentException('The selected title is unavailable for reservation.');
            $this->db->commit();
        } catch (Throwable $exception) { $this->db->rollBack(); throw $exception; }
    }

    public function cancel(int $reservationId, int $memberId): bool
    {
        $statement = $this->db->prepare("UPDATE reservations SET status = 'cancelled' WHERE id = :id AND member_id = :member_id AND status IN ('pending', 'ready')");
        $statement->execute(['id' => $reservationId, 'member_id' => $memberId]);
        return $statement->rowCount() === 1;
    }

    public function expireAndPromote(): void
    {
        $this->db->beginTransaction();
        try {
            $this->db->exec("UPDATE reservations SET status = 'expired' WHERE status = 'ready' AND expires_at IS NOT NULL AND expires_at < NOW()");
            $this->db->exec("UPDATE reservations r SET status = 'ready', expires_at = DATE_ADD(NOW(), INTERVAL 3 DAY) WHERE r.status = 'pending' AND EXISTS (SELECT 1 FROM book_copies c WHERE c.book_id = r.book_id AND c.status = 'available') AND r.created_at = (SELECT MIN(queue.created_at) FROM reservations queue WHERE queue.book_id = r.book_id AND queue.status = 'pending')");
            $this->db->commit();
        } catch (Throwable $exception) { $this->db->rollBack(); throw $exception; }
    }
}