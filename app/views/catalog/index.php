<?php
$query = http_build_query(array_filter([
    'q' => $catalog['filters']['query'],
    'category_id' => $catalog['filters']['category_id'],
    'language' => $catalog['filters']['language'],
    'publication_year' => $catalog['filters']['publication_year'],
    'availability' => $catalog['filters']['availability'],
]));
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Catalog | Digital Library</title>
    <link rel="stylesheet" href="/assets/app.css">
</head>
<body>
<header class="site-header">
    <a class="brand" href="/">Digital Library</a>
    <nav aria-label="Main navigation"><a href="/login">Log in</a></nav>
</header>
<main class="container">
    <section class="intro">
        <p class="eyebrow">Community collection</p>
        <h1>Find your next read.</h1>
        <p>Search the collection by title, author, or ISBN.</p>
    </section>
    <form class="catalog-filters" method="get" action="/" aria-label="Catalog filters">
        <label for="q">Search</label>
        <input id="q" name="q" value="<?= e($catalog['filters']['query']) ?>" placeholder="Title, author, or ISBN">
        <label for="availability">Availability</label>
        <select id="availability" name="availability">
            <option value="">All copies</option>
            <option value="available" <?= $catalog['filters']['availability'] === 'available' ? 'selected' : '' ?>>Available now</option>
            <option value="unavailable" <?= $catalog['filters']['availability'] === 'unavailable' ? 'selected' : '' ?>>Currently unavailable</option>
        </select>
        <button type="submit">Search catalog</button>
    </form>
    <section class="catalog" aria-labelledby="catalog-title">
        <div class="section-heading"><h2 id="catalog-title">Catalog</h2><span class="muted"><?= e((string) $catalog['total']) ?> results</span></div>
        <?php if ($catalog['books'] === []): ?>
            <div class="empty-state"><h3>No books found</h3><p>Try a different search or filter.</p></div>
        <?php else: ?>
            <div class="book-grid">
                <?php foreach ($catalog['books'] as $book): ?>
                    <article class="book-card">
                        <p class="eyebrow"><?= e($book['category'] ?: 'Uncategorized') ?></p>
                        <h3><?= e($book['title']) ?></h3>
                        <p><?= e($book['authors'] ?: 'Author unavailable') ?></p>
                        <p class="muted"><?= e((string) ($book['available_count'] ?? 0)) ?> of <?= e((string) ($book['copy_count'] ?? 0)) ?> copies available</p>
                    </article>
                <?php endforeach; ?>
            </div>
            <?php if ($catalog['total_pages'] > 1): ?>
                <nav class="pagination" aria-label="Catalog pages">
                    <?php for ($page = 1; $page <= $catalog['total_pages']; $page++): ?>
                        <a <?= $page === $catalog['page'] ? 'aria-current="page"' : '' ?> href="/?<?= e($query . ($query === '' ? '' : '&') . 'page=' . $page) ?>"><?= e((string) $page) ?></a>
                    <?php endfor; ?>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </section>
</main>
</body>
</html>