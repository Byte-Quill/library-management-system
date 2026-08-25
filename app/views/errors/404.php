<?php
$pageTitle = 'Not found';
$headerNav = 'none';
$mainClass = 'page-shell';
require dirname(__DIR__) . '/partials/layout-top.php';
?>
    <p class="eyebrow">404</p>
    <h1>Page not found.</h1>
    <p>The page you are looking for does not exist.</p>
    <p><a href="/">Return to catalog</a></p>
<?php require dirname(__DIR__) . '/partials/layout-bottom.php'; ?>
