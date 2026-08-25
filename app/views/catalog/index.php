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
    <a class="brand" href="/">
        <span class="brand-mark">DL</span>
        <span>Digital Library</span>
    </a>
    <nav aria-label="Main navigation">
        <a href="/">Browse</a>
        <a href="/login">Log in</a>
        <a class="nav-button" href="/login">Join now</a>
    </nav>
</header>
<main class="container page-shell">
    <section class="hero">
        <div class="intro">
            <p class="eyebrow">Community collection</p>
            <h1>Find your next read.</h1>
            <p>Search the collection by title, author, or ISBN and discover something worth keeping.</p>
        </div>
        <aside class="hero-panel" aria-label="Catalog highlights">
            <p class="panel-label">Library highlights</p>
            <h2>Borrow with confidence.</h2>
            <ul class="feature-list">
                <li>Streamlined catalog search</li>
                <li>Live copy availability</li>
                <li>Member-friendly borrowing</li>
            </ul>
            <div class="mini-stats">
                <div>
                    <strong><?= e((string) $catalog['total']) ?></strong>
                    <span>titles</span>
                </div>
                <div>
                    <strong>24/7</strong>
                    <span>access</span>
                </div>
            </div>
        </aside>
    </section>

    <form class="catalog-filters" method="get" action="/" aria-label="Catalog filters">
        <div class="field search-field">
            <label for="q">Search</label>
            <input id="q" name="q" value="<?= e($catalog['filters']['query']) ?>" placeholder="Title, author, or ISBN">
        </div>
        <div class="field">
            <label for="availability">Availability</label>
            <select id="availability" name="availability">
                <option value="">All copies</option>
                <option value="available" <?= $catalog['filters']['availability'] === 'available' ? 'selected' : '' ?>>Available now</option>
                <option value="unavailable" <?= $catalog['filters']['availability'] === 'unavailable' ? 'selected' : '' ?>>Currently unavailable</option>
            </select>
        </div>
        <button type="submit">Search catalog</button>
    </form>

    <section class="catalog" aria-labelledby="catalog-title">
        <div class="section-heading">
            <h2 id="catalog-title">Catalog</h2>
            <span class="muted"><?= e((string) $catalog['total']) ?> results</span>
        </div>
        <?php if ($catalog['books'] === []): ?>
            <div class="empty-state">
                <h3>No books found</h3>
                <p>Try a different search or refine the availability filter.</p>
            </div>
        <?php else: ?>
            <div class="book-grid">
                <?php foreach ($catalog['books'] as $book): ?>
                    <article class="book-card">
                        <div class="book-cover" aria-hidden="true">
                            <span><?= e(mb_substr((string) $book['title'], 0, 1, 'UTF-8') ?: 'B') ?></span>
                        </div>
                        <p class="eyebrow book-category"><?= e($book['category'] ?: 'Uncategorized') ?></p>
                        <h3><?= e($book['title']) ?></h3>
                        <p class="book-author"><?= e($book['authors'] ?: 'Author unavailable') ?></p>
                        <div class="book-meta">
                            <span class="availability-pill"><?= e((string) ($book['available_count'] ?? 0)) ?> available</span>
                            <span class="copy-count"><?= e((string) ($book['copy_count'] ?? 0)) ?> copies</span>
                        </div>
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