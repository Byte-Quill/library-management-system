<?php
declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

if (!is_array($config) || $config['max_active_loans'] < 1) {
    throw new RuntimeException('Configuration smoke check failed.');
}

if (e('<script>alert(1)</script>') !== '&lt;script&gt;alert(1)&lt;/script&gt;') {
    throw new RuntimeException('Escaping smoke check failed.');
}

// Every class referenced by the front controller must be loadable.
$classes = [
    'AuthorizationMiddleware',
    'AuthService',
    'AuditController',
    'AuditService',
    'AuditRepository',
    'AuthorController',
    'AuthorRepository',
    'AuthorService',
    'BookManagementController',
    'BookManagementRepository',
    'BookManagementService',
    'BookRepository',
    'CatalogController',
    'CatalogService',
    'CategoryController',
    'CategoryRepository',
    'CategoryService',
    'CopyController',
    'CopyRepository',
    'CopyService',
    'DashboardController',
    'DashboardRepository',
    'DashboardService',
    'LoanController',
    'LoanRepository',
    'LoanService',
    'MemberController',
    'MemberService',
    'ProfileController',
    'ProfileService',
    'ReservationController',
    'ReservationRepository',
    'ReservationService',
    'UploadService',
    'UserRepository',
];
foreach ($classes as $class) {
    if (!class_exists($class)) {
        throw new RuntimeException("Missing class: {$class}");
    }
}

// Every view required by controllers and routes must exist.
$views = [
    'audit/index.php',
    'auth/form.php',
    'authors/index.php',
    'books/index.php',
    'catalog/index.php',
    'categories/index.php',
    'copies/index.php',
    'dashboard/index.php',
    'errors/403.php',
    'legal/privacy.php',
    'legal/terms.php',
    'legal/contact.php',
    'legal/accessibility.php',
    'loans/librarian.php',
    'loans/member.php',
    'members/index.php',
    'profile/edit.php',
    'reservations/librarian.php',
    'reservations/member.php',
];
foreach ($views as $view) {
    if (!is_file(dirname(__DIR__) . '/app/views/' . $view)) {
        throw new RuntimeException("Missing view: {$view}");
    }
}

echo "Foundation smoke checks passed.\n";
