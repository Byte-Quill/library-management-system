<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Authors | Digital Library</title>
    <link rel="stylesheet" href="/assets/app.css">
</head>
<body>
<header class="site-header"><a class="brand" href="/dashboard">Digital Library</a><nav><a href="/">Catalog</a></nav></header>
<main class="container">
    <p class="eyebrow">Collection management</p><h1>Authors</h1>
    <?php if ($error): ?><p class="form-error" role="alert"><?= e($error) ?></p><?php endif; ?>
    <?php if ($success): ?><p class="form-success" role="status"><?= e($success) ?></p><?php endif; ?>
    <form method="post" class="catalog-filters"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><label for="name">Author name</label><input id="name" name="name" required maxlength="180"><button type="submit">Add author</button></form>
    <section class="catalog" aria-labelledby="author-list"><div class="section-heading"><h2 id="author-list">Active authors</h2><span class="muted"><?= e((string) count($authors)) ?></span></div>
        <?php if ($authors === []): ?><div class="empty-state"><h3>No authors yet</h3></div><?php else: ?><div class="book-grid"><?php foreach ($authors as $author): ?><article class="book-card"><h3><?= e($author['name']) ?></h3><form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="archive"><input type="hidden" name="id" value="<?= e((string) $author['id']) ?>"><button type="submit">Archive</button></form></article><?php endforeach; ?></div><?php endif; ?>
    </section>
</main>
</body>
</html>