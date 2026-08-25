<?php
declare(strict_types=1);

/**
 * Application route table.
 *
 * Maps a request path to a handler that receives the application
 * configuration and dispatches to the matching controller or view.
 * Keep this file declarative: wiring only, no business logic.
 *
 * @return array<string, callable(array): void>
 */

return [
    // Legal pages (static views).
    '/privacy-policy' => static function (): void { require __DIR__ . '/views/legal/privacy.php'; },
    '/terms' => static function (): void { require __DIR__ . '/views/legal/terms.php'; },
    '/contact' => static function (): void { require __DIR__ . '/views/legal/contact.php'; },
    '/accessibility' => static function (): void { require __DIR__ . '/views/legal/accessibility.php'; },

    // Authentication.
    '/login' => static function (array $config): void {
        $db = database($config);
        $audits = new AuditService(new AuditRepository($db));
        (new AuthController(new AuthService($db, $audits), $audits))->login();
    },
    '/register' => static function (array $config): void {
        $db = database($config);
        $audits = new AuditService(new AuditRepository($db));
        (new AuthController(new AuthService($db, $audits), $audits))->register();
    },
    '/logout' => static function (array $config): void {
        $db = database($config);
        $audits = new AuditService(new AuditRepository($db));
        (new AuthController(new AuthService($db, $audits), $audits))->logout();
    },

    // Public catalog.
    '/' => static function (array $config): void {
        (new CatalogController(new CatalogService(new BookRepository(database($config)))))->index();
    },

    // Account.
    '/dashboard' => static function (array $config): void {
        (new DashboardController(new DashboardService(new DashboardRepository(database($config)))))->index();
    },
    '/profile' => static function (array $config): void {
        $db = database($config);
        (new ProfileController(new ProfileService(new UserRepository($db), new AuditService(new AuditRepository($db)))))->edit();
    },

    // Member circulation.
    '/loans' => static function (array $config): void {
        $db = database($config);
        (new LoanController(new LoanService(new LoanRepository($db), $config, new ReservationRepository($db), new CopyRepository($db))))->member();
    },
    '/reservations' => static function (array $config): void {
        (new ReservationController(new ReservationService(new ReservationRepository(database($config)))))->member();
    },

    // Staff management.
    '/manage/categories' => static function (array $config): void {
        (new CategoryController(new CategoryService(new CategoryRepository(database($config)))))->index();
    },
    '/manage/authors' => static function (array $config): void {
        (new AuthorController(new AuthorService(new AuthorRepository(database($config)))))->index();
    },
    '/manage/members' => static function (array $config): void {
        $db = database($config);
        (new MemberController(new MemberService(new UserRepository($db), new AuthService($db, new AuditService(new AuditRepository($db))))))->index();
    },
    '/manage/books' => static function (array $config): void {
        $db = database($config);
        (new BookManagementController(
            new BookManagementService(new BookManagementRepository($db), new UploadService(dirname(__DIR__) . '/storage/uploads', $config['upload_max_bytes'])),
            new CategoryRepository($db),
            new AuthorRepository($db)
        ))->index();
    },
    '/manage/copies' => static function (array $config): void {
        $db = database($config);
        (new CopyController(new CopyService(new CopyRepository($db)), new BookManagementRepository($db)))->index();
    },
    '/manage/returns' => static function (array $config): void {
        $db = database($config);
        (new LoanController(new LoanService(new LoanRepository($db), $config, new ReservationRepository($db), new CopyRepository($db))))->librarian();
    },
    '/manage/reservations' => static function (array $config): void {
        (new ReservationController(new ReservationService(new ReservationRepository(database($config)))))->librarian();
    },

    // Administration.
    '/admin/audit' => static function (array $config): void {
        (new AuditController(new AuditService(new AuditRepository(database($config)))))->index();
    },
];
