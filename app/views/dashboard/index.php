<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard | Digital Library</title>
    <link rel="stylesheet" href="/assets/app.css">
</head>
<body>
<header class="site-header">
    <a class="brand" href="/">Digital Library</a>
    <nav aria-label="Account navigation">
        <span><?= e($user['name']) ?></span>
        <form method="post" action="/logout">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <button type="submit"><?= icon('logout') ?><span>Log out</span></button>
        </form>
    </nav>
</header>
<main class="container">
    <p class="eyebrow"><?= e($user['role']) ?> account</p>
    <h1>Welcome back, <?= e($user['name']) ?>.</h1>
    <div class="book-grid">
        <?php foreach ($stats as $label => $value): ?><article class="book-card"><p class="eyebrow"><?= e(str_replace('_', ' ', $label)) ?></p><h2><?= e((string) $value) ?></h2></article><?php endforeach; ?>
    </div>
</main>
</body>
</html>