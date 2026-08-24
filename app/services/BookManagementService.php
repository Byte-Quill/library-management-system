<?php
declare(strict_types=1);

final class BookManagementService
{
    public function __construct(private BookManagementRepository $books, private UploadService $uploads)
    {
    }

    public function all(): array { return $this->books->all(); }

    public function create(array $input, array $files = []): void
    {
        $title = trim((string) ($input['title'] ?? ''));
        $language = trim((string) ($input['language'] ?? 'English'));
        $authorIds = array_values(array_unique(array_filter(array_map('intval', (array) ($input['author_ids'] ?? [])), static fn (int $id): bool => $id > 0)));
        if ($title === '' || strlen($title) > 255 || $language === '' || strlen($language) > 80 || $authorIds === []) {
            throw new InvalidArgumentException('Title, language, and at least one author are required.');
        }
        $cover = $this->uploads->store($files['cover'] ?? []);
        try {
            $this->books->create([
                'category_id' => filter_var($input['category_id'] ?? null, FILTER_VALIDATE_INT) ?: null,
                'title' => $title, 'isbn' => trim((string) ($input['isbn'] ?? '')) ?: null,
                'publisher' => trim((string) ($input['publisher'] ?? '')) ?: null,
                'publication_year' => filter_var($input['publication_year'] ?? null, FILTER_VALIDATE_INT) ?: null,
                'language' => $language, 'description' => trim((string) ($input['description'] ?? '')) ?: null,
                'page_count' => filter_var($input['page_count'] ?? null, FILTER_VALIDATE_INT) ?: null,
                'cover_path' => $cover,
            ], $authorIds);
        } catch (Throwable $exception) {
            if ($cover !== null) @unlink(dirname(__DIR__, 2) . '/storage/uploads/' . $cover);
            throw $exception;
        }
    }

    public function archive(string $id): void
    {
        $bookId = filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($bookId === false || !$this->books->archive($bookId)) throw new InvalidArgumentException('The selected book could not be archived.');
    }
}