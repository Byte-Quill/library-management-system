<?php
declare(strict_types=1);

/**
 * Shared page head, site header, and opening <main> tag.
 *
 * Expected view variables:
 *   $pageTitle string Page title (the site suffix is appended).
 *   $headerNav string One of: staff, member, catalog, legal, dashboard, none.
 *   $mainClass string Optional extra class for <main> (e.g. 'page-shell').
 *   $staffLink array  Optional ['href' => ..., 'label' => ...] override for the staff nav link.
 */
$headerNav ??= 'none';
$mainClass ??= '';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?> | Digital Library</title>
    <link rel="stylesheet" href="/assets/app.css">
</head>
<body>
<?php if ($headerNav === 'staff'): ?>
<?php $staffLink ??= ['href' => '/', 'label' => 'Catalog']; ?>
<header class="site-header"><a class="brand" href="/dashboard">Digital Library</a><nav><a href="<?= e($staffLink['href']) ?>"><?= e($staffLink['label']) ?></a></nav></header>
<?php elseif ($headerNav === 'member'): ?>
<header class="site-header"><a class="brand" href="/dashboard">Digital Library</a><nav><a href="/">Catalog</a><form method="post" action="/logout"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><button type="submit">Log out</button></form></nav></header>
<?php elseif ($headerNav === 'legal'): ?>
<header class="site-header"><a class="brand" href="/">Digital Library</a><nav><a href="/">Catalog</a></nav></header>
<?php elseif ($headerNav === 'dashboard'): ?>
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
<?php elseif ($headerNav === 'catalog'): ?>
<header class="site-header">
    <a class="brand" href="/">
        <span class="brand-mark">DL</span>
        <span>Digital Library</span>
    </a>
    <nav aria-label="Main navigation">
        <a href="/">Browse</a>
        <?php if (AuthorizationMiddleware::currentUser() !== null): ?>
            <a class="nav-button" href="/dashboard">My dashboard</a>
        <?php else: ?>
            <a href="/login">Log in</a>
            <a class="nav-button" href="/register">Join now</a>
        <?php endif; ?>
    </nav>
</header>
<?php endif; ?>
<main class="container<?= $mainClass !== '' ? ' ' . e($mainClass) : '' ?>">
