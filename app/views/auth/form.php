<?php
$isLogin = $mode === 'login';
$pageTitle = $isLogin ? 'Welcome back' : 'Join the library';
$action = $isLogin ? '/login' : '/register';
$headerNav = 'none';
$mainClass = 'auth-page';
require dirname(__DIR__) . '/partials/layout-top.php';
?>
    <p class="eyebrow">Digital Library</p>
    <h1><?= e($pageTitle) ?></h1>
    <?php if (!empty($notice)): ?><p class="form-success" role="status"><?= e($notice) ?></p><?php endif; ?>
    <?php if ($error): ?><p class="form-error" role="alert"><?= e($error) ?></p><?php endif; ?>
    <form method="post" action="<?= e($action) ?>" class="auth-form">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <?php if (!$isLogin): ?>
            <label for="name">Name</label>
            <input id="name" name="name" required maxlength="120" autocomplete="name" value="<?= e(str_param($_POST['name'] ?? '')) ?>">
        <?php endif; ?>
        <label for="email">Email</label>
        <input id="email" name="email" type="email" required maxlength="190" autocomplete="email" value="<?= e(str_param($_POST['email'] ?? '')) ?>">
        <label for="password">Password</label>
        <input id="password" name="password" type="password" required minlength="8" maxlength="4096" autocomplete="<?= $isLogin ? 'current-password' : 'new-password' ?>">
        <button type="submit"><?= e($isLogin ? 'Log in' : 'Register') ?></button>
    </form>
    <p><?= $isLogin ? 'New here? <a href="/register">Create an account</a>.' : 'Already a member? <a href="/login">Log in</a>.' ?></p>
    <p><a href="/">Back to catalog</a></p>
<?php require dirname(__DIR__) . '/partials/layout-bottom.php'; ?>
