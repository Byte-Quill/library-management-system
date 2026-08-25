<?php
$pageTitle = 'My loans';
$headerNav = 'member';
require dirname(__DIR__) . '/partials/layout-top.php';
?>
<p class="eyebrow">Member account</p><h1>My loans</h1><?php if ($error): ?><p class="form-error" role="alert"><?= e($error) ?></p><?php endif; ?><?php if ($success): ?><p class="form-success" role="status"><?= e($success) ?></p><?php endif; ?><?php if ($availableCopies !== []): ?><form method="post" class="book-form"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><label for="copy_id">Borrow an available copy</label><select id="copy_id" name="copy_id" required><option value="">Select a copy</option><?php foreach ($availableCopies as $copy): ?><option value="<?= e((string) $copy['id']) ?>"><?= e($copy['title']) ?> (<?= e($copy['accession_number']) ?>)</option><?php endforeach; ?></select><button type="submit">Borrow</button></form><?php endif; ?><?php if ($loans === []): ?><div class="empty-state"><h2>No active loans</h2></div><?php else: ?><div class="book-grid"><?php foreach ($loans as $loan): ?><article class="book-card"><h2><?= e($loan['title']) ?></h2><p>Copy <?= e($loan['accession_number']) ?></p><p class="muted">Due <?= e($loan['due_at']) ?></p></article><?php endforeach; ?></div><?php endif; ?>
<?php require dirname(__DIR__) . '/partials/layout-bottom.php'; ?>
