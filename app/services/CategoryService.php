<?php
declare(strict_types=1);

final class CategoryService
{
    public function __construct(private CategoryRepository $categories)
    {
    }

    public function list(): array
    {
        return $this->categories->all();
    }

    public function create(string $name): void
    {
        $name = trim($name);
        if ($name === '' || strlen($name) > 120) {
            throw new InvalidArgumentException('Category names must contain between 1 and 120 characters.');
        }
        if (!$this->categories->create($name)) {
            throw new RuntimeException('Unable to create category.');
        }
    }

    public function archive(string $id): void
    {
        $categoryId = filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($categoryId === false || !$this->categories->archive($categoryId)) {
            throw new InvalidArgumentException('The selected category could not be archived.');
        }
    }
}