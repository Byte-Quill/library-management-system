<?php
declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Content-Security-Policy: default-src \'self\'; style-src \'self\';');

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if (in_array($path, ['/login', '/register'], true)) {
    $mode = ltrim($path, '/');
    $error = null;

    if ($method === 'POST') {
        verify_csrf($_POST['csrf_token'] ?? null);
        $name = trim((string) ($_POST['name'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || ($mode === 'register' && ($name === '' || strlen($name) > 120)) || strlen($password) < 8) {
            $error = 'Please provide valid details. Passwords must contain at least 8 characters.';
        } else {
            try {
                $auth = new AuthService(database($config));
                if ($mode === 'register') {
                    $auth->register($name, $email, $password);
                    header('Location: /login?registered=1', true, 303);
                    exit;
                }
                $user = $auth->attempt($email, $password);
                if ($user === null) {
                    $error = 'The email or password is incorrect.';
                } else {
                    session_regenerate_id(true);
                    $_SESSION['user'] = $user;
                    header('Location: /', true, 303);
                    exit;
                }
            } catch (Throwable $exception) {
                $error = 'Unable to complete this request.';
                error_log($exception->__toString());
            }
        }
    }

    require dirname(__DIR__) . '/app/views/auth/form.php';
    exit;
}

if ($path === '/logout') {
    if ($method === 'POST') {
        verify_csrf($_POST['csrf_token'] ?? null);
        $_SESSION = [];
        session_destroy();
    }
    header('Location: /', true, 303);
    exit;
}

if ($path !== '/') {
    http_response_code(404);
    echo 'Page not found.';
    exit;
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Digital Library</title>
    <link rel="stylesheet" href="/assets/app.css">
</head>
<body>
<header class="site-header">
    <a class="brand" href="/">Digital Library</a>
    <nav aria-label="Main navigation"><a href="#catalog">Catalog</a><a href="/login">Log in</a></nav>
</header>
<main class="container">
    <section class="intro">
        <p class="eyebrow">Community collection</p>
        <h1>Find your next read.</h1>
        <p>Browse the library catalog, check availability, and keep every loan in one place.</p>
    </section>
    <section id="catalog" class="catalog" aria-labelledby="catalog-title">
        <div class="section-heading"><h2 id="catalog-title">Catalog</h2><span class="muted">Coming next</span></div>
        <div class="empty-state"><h3>The catalog is being prepared</h3><p>Books, search, and filters will appear here after the database is configured.</p></div>
    </section>
</main>
</body>
</html>
