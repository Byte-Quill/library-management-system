<?php
declare(strict_types=1);

final class BookManagementRepository
{
    public function __construct(private PDO $db)
    {
    }

    public function all(): array
    {
        return $this->db->query("SELECT b.id, b.title, b.isbn, b.status, c.name AS category, COUNT(copies.id) AS copy_count FROM books b LEFT JOIN categories c ON c.id = b.category_id LEFT JOIN book_copies copies ON copies.book_id = b.id GROUP BY b.id ORDER BY b.title")->fetchAll();
    }

    public function create(array $book, array $authorIds): void
    {
        $this->db->beginTransaction();
        try {
            $statement = $this->db->prepare('INSERT INTO books (category_id, title, isbn, publisher, publication_year, language, description, page_count) VALUES (:category_id, :title, :isbn, :publisher, :publication_year, :language, :description, :page_count)');
            $statement->execute($book);
            $bookId = (int) $this->db->lastInsertId();
            $authors = $this->db->prepare('INSERT INTO book_authors (book_id, author_id) VALUES (:book_id, :author_id)');
            foreach ($authorIds as $authorId) {
                $authors->execute(['book_id' => $bookId, 'author_id' => $authorId]);
            }
            $this->db->commit();
        } catch (Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }
    }

    public function archive(int $id): bool
    {
        $statement = $this->db->prepare("UPDATE books SET status = 'archived' WHERE id = :id AND status = 'active'");
        $statement->execute(['id' => $id]);
        return $statement->rowCount() === 1;
    }
}