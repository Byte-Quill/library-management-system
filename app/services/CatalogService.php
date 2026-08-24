<?php
declare(strict_types=1);

final class CatalogService
{
    public function __construct(private BookRepository $books)
    {
    }

    public function browse(array $input): array
    {
        $page = max(1, min(10000, (int) ($input['page'] ?? 1)));
        $perPage = 12;
        $filters = [
            'query' => trim((string) ($input['q'] ?? '')),
            'category_id' => filter_var($input['category_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: null,
            'language' => trim((string) ($input['language'] ?? '')),
            'publication_year' => filter_var($input['publication_year'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 9999]]) ?: null,
            'availability' => in_array($input['availability'] ?? '', ['available', 'unavailable'], true) ? $input['availability'] : '',
        ];
        $total = $this->books->count($filters);
        $totalPages = max(1, (int) ceil($total / $perPage));
        $page = min($page, $totalPages);
        return [
            'books' => $this->books->search($filters, $perPage, ($page - 1) * $perPage),
            'filters' => $filters,
            'page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'total_pages' => $totalPages,
        ];
    }
}