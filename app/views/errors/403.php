<?php
$pageTitle = 'Access denied';
$headerNav = 'none';
require dirname(__DIR__) . '/partials/layout-top.php';
?>
    <p class="eyebrow">403</p>
    <h1>Access denied.</h1>
    <p>You do not have permission to view this page.</p>
    <p><a href="/">Return to catalog</a></p>
<?php require dirname(__DIR__) . '/partials/layout-bottom.php'; ?>
