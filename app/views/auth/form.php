<?php
$isLogin = $mode === 'login';
$title = $isLogin ? 'Welcome back' : 'Join the library';
$action = $isLogin ? '/login' : '/register';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?> | Digital Library</title>
    <link rel="stylesheet" href="/assets/app.css">
</head>
<body>
<main class="container auth-page">
    <p class="eyebrow">Digital Library</p>
    <h1><?= e($title) ?></h1>
    <?php if ($error): ?><p class="form-error" role="alert"><?= e($error) ?></p><?php endif; ?>
    <form method="post" action="<?= e($action) ?>" class="auth-form">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <?php if (!$isLogin): ?>
            <label for="name">Name</label>
            <input id="name" name="name" required maxlength="120" autocomplete="name" value="<?= e($_POST['name'] ?? '') ?>">
        <?php endif; ?>
        <label for="email">Email</label>
        <input id="email" name="email" type="email" required maxlength="190" autocomplete="email" value="<?= e($_POST['email'] ?? '') ?>">
        <label for="password">Password</label>
        <input id="password" name="password" type="password" required minlength="8" maxlength="4096" autocomplete="<?= $isLogin ? 'current-password' : 'new-password' ?>">
        <button type="submit"><?= e($isLogin ? 'Log in' : 'Register') ?></button>
    </form>
    <p><a href="/">Back to catalog</a></p>
</main>
</body>
</html>
