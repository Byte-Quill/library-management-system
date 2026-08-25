<?php
$pageTitle = 'Dashboard';
$headerNav = 'dashboard';
require dirname(__DIR__) . '/partials/layout-top.php';
?>
    <p class="eyebrow"><?= e($user['role']) ?> account</p>
    <h1>Welcome back, <?= e($user['name']) ?>.</h1>
    <div class="book-grid">
        <?php foreach ($stats as $label => $value): ?><article class="book-card"><p class="eyebrow"><?= e(str_replace('_', ' ', $label)) ?></p><h2><?= e((string) $value) ?></h2></article><?php endforeach; ?>
    </div>
<?php require dirname(__DIR__) . '/partials/layout-bottom.php'; ?>
