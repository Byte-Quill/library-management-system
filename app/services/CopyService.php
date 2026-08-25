<?php
declare(strict_types=1);

final class CopyService
{
    // Statuses a librarian may set directly. 'borrowed' and 'reserved' are
    // managed exclusively by the loan and reservation flows.
    private const STATUSES = ['available', 'maintenance', 'lost', 'damaged'];

    public function __construct(private CopyRepository $copies)
    {
    }

    public function all(): array { return $this->copies->all(); }

    public function create(array $input): void
    {
        $bookId = filter_var($input['book_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $accession = trim((string) ($input['accession_number'] ?? ''));
        $condition = trim((string) ($input['condition_label'] ?? 'good'));
        $location = trim((string) ($input['location'] ?? ''));
        if ($bookId === false || $accession === '' || strlen($accession) > 64 || $condition === '' || strlen($condition) > 80 || $location === '' || strlen($location) > 120) {
            throw new InvalidArgumentException('Book, accession number, condition, and location are required.');
        }
        if (!$this->copies->create(['book_id' => $bookId, 'accession_number' => $accession, 'condition_label' => $condition, 'location' => $location, 'status' => 'available'])) {
            throw new RuntimeException('Unable to create physical copy.');
        }
    }

    public function updateStatus(string $id, string $status): void
    {
        $copyId = filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($copyId === false || !in_array($status, self::STATUSES, true) || !$this->copies->updateStatus($copyId, $status)) {
            throw new InvalidArgumentException('The copy status could not be updated. Borrowed or reserved copies require circulation actions.');
        }
    }
}