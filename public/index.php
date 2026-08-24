<?php
declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

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

        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || ($mode === 'register' && ($name === '' || strlen($name) > 120)) || strlen($password) < 8 || strlen($password) > 4096) {
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
                    header('Location: /dashboard', true, 303);
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

if ($path === '/') {
    $controller = new CatalogController(new CatalogService(new BookRepository(database($config))));
    $controller->index();
    exit;
}

if ($path === '/dashboard') {
    (new DashboardController())->index();
    exit;
}

if ($path === '/manage/categories') {
    (new CategoryController(new CategoryService(new CategoryRepository(database($config)))))->index();
    exit;
}

if ($path === '/manage/authors') {
    (new AuthorController(new AuthorService(new AuthorRepository(database($config)))))->index();
    exit;
}

if ($path === '/manage/books') {
    (new BookManagementController(new BookManagementService(new BookManagementRepository(database($config))), new CategoryRepository(database($config)), new AuthorRepository(database($config))))->index();
    exit;
}

if ($path === '/manage/copies') {
    (new CopyController(new CopyService(new CopyRepository(database($config))), new BookManagementRepository(database($config))))->index();
    exit;
}

http_response_code(404);
echo 'Page not found.';
