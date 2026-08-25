<?php
$pageTitle = 'Audit log';
$headerNav = 'staff';
require dirname(__DIR__) . '/partials/layout-top.php';
?>
<p class="eyebrow">Administration</p><h1>Audit log</h1><p class="muted"><?= e((string) $audit['total']) ?> recorded events</p><?php if ($audit['logs'] === []): ?><div class="empty-state"><h2>No audit events</h2></div><?php else: ?><div class="audit-list"><?php foreach ($audit['logs'] as $log): ?><article class="book-card"><p class="eyebrow"><?= e($log['action']) ?></p><h2><?= e($log['user_name'] ?: 'System') ?></h2><p><?= e($log['entity_type'] ?: 'general') ?> <?= e((string) ($log['entity_id'] ?: '')) ?></p><p class="muted"><?= e($log['created_at']) ?> · <?= e($log['ip_address'] ?: 'unknown IP') ?></p></article><?php endforeach; ?></div><?php if ($audit['pages'] > 1): ?><nav class="pagination" aria-label="Audit pages"><?php for ($page = 1; $page <= $audit['pages']; $page++): ?><a <?= $page === $audit['page'] ? 'aria-current="page"' : '' ?> href="/admin/audit?page=<?= e((string) $page) ?>"><?= e((string) $page) ?></a><?php endfor; ?></nav><?php endif; ?><?php endif; ?>
<?php require dirname(__DIR__) . '/partials/layout-bottom.php'; ?>
