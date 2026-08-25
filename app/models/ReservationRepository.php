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

    public function reservableBooks(): array
    {
        return $this->db->query("SELECT id, title FROM books WHERE status = 'active' ORDER BY title ASC")->fetchAll();
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
        $this->db->beginTransaction();
        try {
            $lookup = $this->db->prepare("SELECT book_id, status FROM reservations WHERE id = :id AND member_id = :member_id AND status IN ('pending', 'ready')");
            $lookup->execute(['id' => $reservationId, 'member_id' => $memberId]);
            $reservation = $lookup->fetch();
            if (!$reservation) {
                $this->db->rollBack();
                return false;
            }
            $statement = $this->db->prepare("UPDATE reservations SET status = 'cancelled' WHERE id = :id AND member_id = :member_id AND status IN ('pending', 'ready')");
            $statement->execute(['id' => $reservationId, 'member_id' => $memberId]);
            if ($reservation['status'] === 'ready') {
                $this->db->prepare("UPDATE book_copies SET status = 'available' WHERE book_id = :book_id AND status = 'reserved' ORDER BY id LIMIT 1")->execute(['book_id' => $reservation['book_id']]);
            }
            $this->db->commit();
            return $statement->rowCount() === 1;
        } catch (Throwable $exception) { $this->db->rollBack(); throw $exception; }
    }

    public function expireAndPromote(): void
    {
        $this->db->beginTransaction();
        try {
            // Expire ready reservations whose pickup window has passed and release the copies they hold.
            $expired = $this->db->query("SELECT book_id, COUNT(*) AS total FROM reservations WHERE status = 'ready' AND expires_at IS NOT NULL AND expires_at < NOW() GROUP BY book_id")->fetchAll();
            if ($expired !== []) {
                $this->db->exec("UPDATE reservations SET status = 'expired' WHERE status = 'ready' AND expires_at IS NOT NULL AND expires_at < NOW()");
                $release = $this->db->prepare("UPDATE book_copies SET status = 'available' WHERE book_id = :book_id AND status = 'reserved' ORDER BY id LIMIT 1");
                foreach ($expired as $row) {
                    for ($i = 0; $i < (int) $row['total']; $i++) {
                        $release->execute(['book_id' => $row['book_id']]);
                    }
                }
            }

            // Promote the oldest pending reservations for each book that has available copies,
            // holding one copy per promoted reservation. Done row-by-row to avoid updating
            // reservations from a subquery on reservations itself (MySQL error 1093).
            $candidates = $this->db->query("SELECT DISTINCT book_id FROM reservations WHERE status = 'pending'")->fetchAll(PDO::FETCH_COLUMN);
            $promote = $this->db->prepare("UPDATE reservations SET status = 'ready', expires_at = DATE_ADD(NOW(), INTERVAL 3 DAY) WHERE id = :id AND status = 'pending'");
            $hold = $this->db->prepare("UPDATE book_copies SET status = 'reserved' WHERE book_id = :book_id AND status = 'available' ORDER BY id LIMIT 1");
            $next = $this->db->prepare("SELECT id FROM reservations WHERE book_id = :book_id AND status = 'pending' ORDER BY created_at ASC, id ASC LIMIT 1");
            $available = $this->db->prepare("SELECT COUNT(*) FROM book_copies WHERE book_id = :book_id AND status = 'available'");
            foreach ($candidates as $bookId) {
                while (true) {
                    $available->execute(['book_id' => $bookId]);
                    if ((int) $available->fetchColumn() < 1) {
                        break;
                    }
                    $next->execute(['book_id' => $bookId]);
                    $reservationId = $next->fetchColumn();
                    if ($reservationId === false) {
                        break;
                    }
                    $promote->execute(['id' => $reservationId]);
                    $hold->execute(['book_id' => $bookId]);
                }
            }

            $this->db->commit();
        } catch (Throwable $exception) { $this->db->rollBack(); throw $exception; }
    }
}