<?php
declare(strict_types=1);

final class AuthController
{
    private const LOGIN_MAX_ATTEMPTS = 10;
    private const LOGIN_WINDOW = 900;
    private const REGISTER_MAX_ATTEMPTS = 5;
    private const REGISTER_WINDOW = 3600;

    public function __construct(private AuthService $auth, private AuditService $audits)
    {
    }

    public function login(): void
    {
        $this->handle('login');
    }

    public function register(): void
    {
        $this->handle('register');
    }

    public function logout(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            verify_csrf($_POST['csrf_token'] ?? null);
            $user = AuthorizationMiddleware::currentUser();
            if ($user !== null) {
                $this->audits->record((int) $user['id'], 'logout', 'user', (int) $user['id']);
            }
            $_SESSION = [];
            session_destroy();
        }
        header('Location: /', true, 303);
        exit;
    }

    private function handle(string $mode): void
    {
        $error = null;
        $notice = ($mode === 'login' && isset($_GET['registered'])) ? 'Registration complete. You can now log in.' : null;

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $error = $this->processSubmission($mode);
            if ($error === null) {
                return; // A successful submission already redirected.
            }
        }

        require dirname(__DIR__) . '/views/auth/form.php';
    }

    private function processSubmission(string $mode): ?string
    {
        verify_csrf($_POST['csrf_token'] ?? null);

        if ($this->isThrottled($mode)) {
            return 'Too many attempts. Please try again later.';
        }

        $name = trim(str_param($_POST['name'] ?? ''));
        $email = trim(str_param($_POST['email'] ?? ''));
        $password = str_param($_POST['password'] ?? '');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)
            || ($mode === 'register' && ($name === '' || strlen($name) > 120))
            || strlen($password) < 8 || strlen($password) > 4096) {
            return 'Please provide valid details. Passwords must contain at least 8 characters.';
        }

        try {
            if ($mode === 'register') {
                $this->trackAttempt('register_attempts', self::REGISTER_WINDOW);
                $this->auth->register($name, $email, $password);
                header('Location: /login?registered=1', true, 303);
                exit;
            }

            $this->trackAttempt('login_attempts', self::LOGIN_WINDOW);
            $user = $this->auth->attempt($email, $password);
            if ($user === null) {
                return 'The email or password is incorrect.';
            }

            unset($_SESSION['login_attempts'], $_SESSION['register_attempts']);
            session_regenerate_id(true);
            csrf_regenerate();
            // Restart the absolute lifetime: a fresh login is a fresh
            // authentication, even if the anonymous session is older.
            $_SESSION['created_at'] = time();
            $_SESSION['user'] = $user;
            header('Location: /dashboard', true, 303);
            exit;
        } catch (InvalidArgumentException $exception) {
            return $exception->getMessage();
        } catch (Throwable $exception) {
            error_log($exception->__toString());
            return 'Unable to complete this request.';
        }
    }

    private function isThrottled(string $mode): bool
    {
        if (count($this->recentAttempts('login_attempts', self::LOGIN_WINDOW)) >= self::LOGIN_MAX_ATTEMPTS) {
            return true;
        }
        return $mode === 'register'
            && count($this->recentAttempts('register_attempts', self::REGISTER_WINDOW)) >= self::REGISTER_MAX_ATTEMPTS;
    }

    /**
     * @return list<int> Timestamps of recent attempts, pruned to the window.
     */
    private function recentAttempts(string $key, int $window): array
    {
        $now = time();
        $attempts = array_values(array_filter(
            $_SESSION[$key] ?? [],
            static fn (int $timestamp): bool => $timestamp > $now - $window
        ));
        $_SESSION[$key] = $attempts;
        return $attempts;
    }

    private function trackAttempt(string $key, int $window): void
    {
        $attempts = $this->recentAttempts($key, $window);
        $attempts[] = time();
        $_SESSION[$key] = $attempts;
    }
}
