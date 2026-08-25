<?php
declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if (in_array($path, ['/privacy-policy', '/terms', '/contact', '/accessibility'], true)) {
    $page = match ($path) {
        '/privacy-policy' => 'privacy',
        '/terms' => 'terms',
        '/contact' => 'contact',
        default => 'accessibility',
    };
    require dirname(__DIR__) . '/app/views/legal/' . $page . '.php';
    exit;
}

if (in_array($path, ['/login', '/register'], true)) {
    $mode = ltrim($path, '/');
    $error = null;
    $notice = ($mode === 'login' && isset($_GET['registered'])) ? 'Registration complete. You can now log in.' : null;

    if ($method === 'POST') {
        verify_csrf($_POST['csrf_token'] ?? null);

        // Session-based throttle against brute-force and abuse.
        $now = time();
        $attempts = $_SESSION['login_attempts'] ?? [];
        $attempts = array_filter($attempts, static fn (int $timestamp): bool => $timestamp > $now - 900);
        $registrations = $_SESSION['register_attempts'] ?? [];
        $registrations = array_filter($registrations, static fn (int $timestamp): bool => $timestamp > $now - 3600);
        if (count($attempts) >= 10 || ($mode === 'register' && count($registrations) >= 5)) {
            $error = 'Too many attempts. Please try again later.';
        } else {
            $name = trim((string) ($_POST['name'] ?? ''));
            $email = trim((string) ($_POST['email'] ?? ''));
            $password = (string) ($_POST['password'] ?? '');

            if (!filter_var($email, FILTER_VALIDATE_EMAIL) || ($mode === 'register' && ($name === '' || strlen($name) > 120)) || strlen($password) < 8 || strlen($password) > 4096) {
                $error = 'Please provide valid details. Passwords must contain at least 8 characters.';
            } else {
                try {
                    $db = database($config);
                    $auth = new AuthService($db, new AuditService(new AuditRepository($db)));
                    if ($mode === 'register') {
                        $registrations[] = $now;
                        $_SESSION['register_attempts'] = $registrations;
                        $auth->register($name, $email, $password);
                        header('Location: /login?registered=1', true, 303);
                        exit;
                    }
                    $attempts[] = $now;
                    $_SESSION['login_attempts'] = $attempts;
                    $user = $auth->attempt($email, $password);
                    if ($user === null) {
                        $error = 'The email or password is incorrect.';
                    } else {
                        unset($_SESSION['login_attempts'], $_SESSION['register_attempts']);
                        session_regenerate_id(true);
                        csrf_regenerate();
                        $_SESSION['user'] = $user;
                        header('Location: /dashboard', true, 303);
                        exit;
                    }
                } catch (InvalidArgumentException $exception) {
                    $error = $exception->getMessage();
                } catch (Throwable $exception) {
                    $error = 'Unable to complete this request.';
                    error_log($exception->__toString());
                }
            }
        }
    }

    require dirname(__DIR__) . '/app/views/auth/form.php';
    exit;
}

if ($path === '/logout') {
    if ($method === 'POST') {
        verify_csrf($_POST['csrf_token'] ?? null);
        $user = AuthorizationMiddleware::currentUser();
        if ($user !== null) {
            $db = database($config);
            (new AuditService(new AuditRepository($db)))->record((int) $user['id'], 'logout', 'user', (int) $user['id']);
        }
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
    (new DashboardController(new DashboardService(new DashboardRepository(database($config)))))->index();
    exit;
}

if ($path === '/profile') {
    (new ProfileController(new ProfileService(new UserRepository(database($config)), new AuditService(new AuditRepository(database($config))))))->edit();
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

if ($path === '/manage/members') {
    (new MemberController(new MemberService(new UserRepository(database($config)), new AuthService(database($config), new AuditService(new AuditRepository(database($config)))))))->index();
    exit;
}

if ($path === '/manage/books') {
    (new BookManagementController(new BookManagementService(new BookManagementRepository(database($config)), new UploadService(dirname(__DIR__) . '/storage/uploads', $config['upload_max_bytes'])), new CategoryRepository(database($config)), new AuthorRepository(database($config))))->index();
    exit;
}

if ($path === '/manage/copies') {
    (new CopyController(new CopyService(new CopyRepository(database($config))), new BookManagementRepository(database($config))))->index();
    exit;
}

if ($path === '/loans') {
    (new LoanController(new LoanService(new LoanRepository(database($config)), $config, new ReservationRepository(database($config)), new CopyRepository(database($config)))))->member();
    exit;
}

if ($path === '/manage/returns') {
    (new LoanController(new LoanService(new LoanRepository(database($config)), $config, new ReservationRepository(database($config)), new CopyRepository(database($config)))))->librarian();
    exit;
}

if ($path === '/reservations') {
    (new ReservationController(new ReservationService(new ReservationRepository(database($config)))))->member();
    exit;
}

if ($path === '/manage/reservations') {
    (new ReservationController(new ReservationService(new ReservationRepository(database($config)))))->librarian();
    exit;
}

if ($path === '/admin/audit') {
    (new AuditLogController(new AuditLogService(new AuditLogRepository(database($config)))))->index();
    exit;
}

http_response_code(404);
header('Content-Type: text/html; charset=utf-8');
echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Not found | Digital Library</title><link rel="stylesheet" href="/assets/app.css"></head><body><main class="container page-shell"><p class="eyebrow">404</p><h1>Page not found.</h1><p>The page you are looking for does not exist.</p><p><a href="/">Return to catalog</a></p></main></body></html>';
