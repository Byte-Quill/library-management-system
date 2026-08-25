<?php
$pageTitle = 'Reservation queue';
$headerNav = 'staff';
require dirname(__DIR__) . '/partials/layout-top.php';
?>
<p class="eyebrow">Circulation desk</p><h1>Reservation queue</h1><div class="book-grid"><?php foreach ($reservations as $reservation): ?><article class="book-card"><p class="eyebrow"><?= e($reservation['status']) ?></p><h2><?= e($reservation['title']) ?></h2><p><?= e($reservation['member_name']) ?></p><p class="muted"><?= e($reservation['created_at']) ?></p></article><?php endforeach; ?></div>
<?php require dirname(__DIR__) . '/partials/layout-bottom.php'; ?>
