<?php
$pageTitle = 'Members';
$headerNav = 'staff';
require dirname(__DIR__) . '/partials/layout-top.php';
?>
    <p class="eyebrow">Membership</p>
    <h1>Members</h1>
    <?php if ($error): ?><p class="form-error" role="alert"><?= e($error) ?></p><?php endif; ?>
    <?php if ($success): ?><p class="form-success" role="status"><?= e($success) ?></p><?php endif; ?>
    <form method="post" class="book-form">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <label for="name">Name</label>
        <input id="name" name="name" required maxlength="120" autocomplete="name">
        <label for="email">Email</label>
        <input id="email" name="email" type="email" required maxlength="190" autocomplete="email">
        <label for="password">Temporary password</label>
        <input id="password" name="password" type="password" required minlength="8" maxlength="4096" autocomplete="new-password">
        <button type="submit">Add member</button>
    </form>
    <section class="catalog" aria-labelledby="member-list">
        <div class="section-heading"><h2 id="member-list">Active members</h2><span class="muted"><?= e((string) count($members)) ?></span></div>
        <?php if ($members === []): ?><div class="empty-state"><h3>No members yet</h3></div><?php else: ?>
            <div class="book-grid">
                <?php foreach ($members as $member): ?>
                    <article class="book-card">
                        <h3><?= e($member['name']) ?></h3>
                        <p><?= e($member['email']) ?></p>
                        <p class="muted">Joined <?= e($member['created_at']) ?></p>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
<?php require dirname(__DIR__) . '/partials/layout-bottom.php'; ?>
