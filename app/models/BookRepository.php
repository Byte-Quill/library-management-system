<?php
declare(strict_types=1);

final class BookRepository
{
    public function __construct(private PDO $db)
    {
    }

    public function search(array $filters, int $limit, int $offset): array
    {
        $conditions = ["b.status = 'active'"];
        $parameters = [];

        if ($filters['query'] !== '') {
            $conditions[] = '(b.title LIKE :query OR b.isbn LIKE :query OR EXISTS (
                SELECT 1 FROM book_authors search_ba
                INNER JOIN authors search_a ON search_a.id = search_ba.author_id
                WHERE search_ba.book_id = b.id AND search_a.name LIKE :author_query
            ))';
            $parameters['query'] = '%' . $filters['query'] . '%';
            $parameters['author_query'] = '%' . $filters['query'] . '%';
        }
        if ($filters['category_id'] !== null) {
            $conditions[] = 'b.category_id = :category_id';
            $parameters['category_id'] = $filters['category_id'];
        }
        if ($filters['language'] !== '') {
            $conditions[] = 'b.language = :language';
            $parameters['language'] = $filters['language'];
        }
        if ($filters['publication_year'] !== null) {
            $conditions[] = 'b.publication_year = :publication_year';
            $parameters['publication_year'] = $filters['publication_year'];
        }
        if ($filters['availability'] === 'available') {
            $conditions[] = "EXISTS (SELECT 1 FROM book_copies available_copy WHERE available_copy.book_id = b.id AND available_copy.status = 'available')";
        } elseif ($filters['availability'] === 'unavailable') {
            $conditions[] = "NOT EXISTS (SELECT 1 FROM book_copies unavailable_copy WHERE unavailable_copy.book_id = b.id AND unavailable_copy.status = 'available')";
        }

        $where = implode(' AND ', $conditions);
        $statement = $this->db->prepare("SELECT b.id, b.title, b.isbn, b.publisher, b.publication_year, b.language,
                b.description, b.page_count, b.cover_path, c.name AS category,
                COUNT(DISTINCT copies.id) AS copy_count,
                SUM(CASE WHEN copies.status = 'available' THEN 1 ELSE 0 END) AS available_count,
                GROUP_CONCAT(DISTINCT authors.name ORDER BY authors.name SEPARATOR ', ') AS authors
            FROM books b
            LEFT JOIN categories c ON c.id = b.category_id AND c.status = 'active'
            LEFT JOIN book_copies copies ON copies.book_id = b.id
            LEFT JOIN book_authors ba ON ba.book_id = b.id
            LEFT JOIN authors ON authors.id = ba.author_id AND authors.status = 'active'
            WHERE {$where}
            GROUP BY b.id
            ORDER BY b.title ASC
            LIMIT :limit OFFSET :offset");
        foreach ($parameters as $name => $value) {
            $statement->bindValue(':' . $name, $value);
        }
        $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
        $statement->bindValue(':offset', $offset, PDO::PARAM_INT);
        $statement->execute();
        return $statement->fetchAll();
    }

    public function count(array $filters): int
    {
        $conditions = ["b.status = 'active'"];
        $parameters = [];
        if ($filters['query'] !== '') {
            $conditions[] = '(b.title LIKE :query OR b.isbn LIKE :query OR EXISTS (
                SELECT 1 FROM book_authors search_ba
                INNER JOIN authors search_a ON search_a.id = search_ba.author_id
                WHERE search_ba.book_id = b.id AND search_a.name LIKE :author_query
            ))';
            $parameters['query'] = '%' . $filters['query'] . '%';
            $parameters['author_query'] = '%' . $filters['query'] . '%';
        }
        if ($filters['category_id'] !== null) {
            $conditions[] = 'b.category_id = :category_id';
            $parameters['category_id'] = $filters['category_id'];
        }
        if ($filters['language'] !== '') {
            $conditions[] = 'b.language = :language';
            $parameters['language'] = $filters['language'];
        }
        if ($filters['publication_year'] !== null) {
            $conditions[] = 'b.publication_year = :publication_year';
            $parameters['publication_year'] = $filters['publication_year'];
        }
        if ($filters['availability'] === 'available') {
            $conditions[] = "EXISTS (SELECT 1 FROM book_copies available_copy WHERE available_copy.book_id = b.id AND available_copy.status = 'available')";
        } elseif ($filters['availability'] === 'unavailable') {
            $conditions[] = "NOT EXISTS (SELECT 1 FROM book_copies unavailable_copy WHERE unavailable_copy.book_id = b.id AND unavailable_copy.status = 'available')";
        }
        $statement = $this->db->prepare('SELECT COUNT(*) FROM books b WHERE ' . implode(' AND ', $conditions));
        $statement->execute($parameters);
        return (int) $statement->fetchColumn();
    }
}