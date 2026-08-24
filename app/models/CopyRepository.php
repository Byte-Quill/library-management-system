<?php
declare(strict_types=1);

final class CopyRepository
{
    public function __construct(private PDO $db)
    {
    }

    public function all(): array
    {
        return $this->db->query('SELECT c.id, c.book_id, c.accession_number, c.condition_label, c.location, c.status, b.title FROM book_copies c INNER JOIN books b ON b.id = c.book_id ORDER BY b.title, c.accession_number')->fetchAll();
    }

    public function create(array $copy): bool
    {
        return $this->db->prepare('INSERT INTO book_copies (book_id, accession_number, condition_label, location, status) VALUES (:book_id, :accession_number, :condition_label, :location, :status)')->execute($copy);
    }

    public function updateStatus(int $id, string $status): bool
    {
        $statement = $this->db->prepare('UPDATE book_copies SET status = :status WHERE id = :id AND status NOT IN (\'borrowed\', \'reserved\')');
        $statement->execute(['id' => $id, 'status' => $status]);
        return $statement->rowCount() === 1;
    }
}