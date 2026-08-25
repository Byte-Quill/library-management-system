<?php
declare(strict_types=1);

final class DashboardRepository
{
    public function __construct(private PDO $db) {}

    public function memberStats(int $memberId): array
    {
        $stats = ['active_loans' => 0, 'overdue_loans' => 0, 'reservations' => 0, 'fines' => 0.0];
        $statement = $this->db->prepare("SELECT COUNT(*) AS active_loans, COALESCE(SUM(CASE WHEN due_at < NOW() THEN 1 ELSE 0 END), 0) AS overdue_loans FROM loans WHERE member_id = :member_id AND returned_at IS NULL");
        $statement->execute(['member_id' => $memberId]); $stats = array_merge($stats, $statement->fetch() ?: []);
        $statement = $this->db->prepare("SELECT COUNT(*) FROM reservations WHERE member_id = :member_id AND status IN ('pending', 'ready')");
        $statement->execute(['member_id' => $memberId]); $stats['reservations'] = (int) $statement->fetchColumn();
        $statement = $this->db->prepare('SELECT COALESCE(SUM(fine_amount), 0) FROM loans WHERE member_id = :member_id AND fine_amount > 0');
        $statement->execute(['member_id' => $memberId]); $stats['fines'] = (float) $statement->fetchColumn();
        return $stats;
    }

    public function staffStats(): array
    {
        $queries = [
            'books' => "SELECT COUNT(*) FROM books WHERE status = 'active'",
            'copies' => 'SELECT COUNT(*) FROM book_copies',
            'available_copies' => "SELECT COUNT(*) FROM book_copies WHERE status = 'available'",
            'active_loans' => 'SELECT COUNT(*) FROM loans WHERE returned_at IS NULL',
            'overdue_loans' => 'SELECT COUNT(*) FROM loans WHERE returned_at IS NULL AND due_at < NOW()',
            'pending_reservations' => "SELECT COUNT(*) FROM reservations WHERE status = 'pending'",
            'members' => "SELECT COUNT(*) FROM users u INNER JOIN roles r ON r.id = u.role_id WHERE r.name = 'member' AND u.status = 'active'",
        ];
        $stats = []; foreach ($queries as $key => $query) $stats[$key] = (int) $this->db->query($query)->fetchColumn();
        return $stats;
    }
}