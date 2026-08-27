<?php
declare(strict_types=1);

final class AuthorService
{
    public function __construct(private AuthorRepository $authors)
    {
    }

    public function list(): array
    {
        return $this->authors->all();
    }

    public function create(string $name): void
    {
        $name = trim($name);
        if ($name === '' || strlen($name) > 180) {
            throw new InvalidArgumentException('Author names must contain between 1 and 180 characters.');
        }
        try {
            if (!$this->authors->create($name)) {
                throw new RuntimeException('Unable to create author.');
            }
        } catch (PDOException $exception) {
            if ($exception->getCode() === '23000') {
                throw new InvalidArgumentException('That author already exists.');
            }
            throw $exception;
        }
    }

    public function archive(string $id): void
    {
        $authorId = filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($authorId === false || !$this->authors->archive($authorId)) {
            throw new InvalidArgumentException('The selected author could not be archived.');
        }
    }
}