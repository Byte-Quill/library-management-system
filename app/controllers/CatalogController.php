<?php
declare(strict_types=1);

final class CatalogController
{
    public function __construct(private CatalogService $catalog)
    {
    }

    public function index(): void
    {
        $catalog = $this->catalog->browse($_GET);
        require dirname(__DIR__) . '/views/catalog/index.php';
    }
}