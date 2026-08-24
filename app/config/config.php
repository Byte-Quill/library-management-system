<?php
declare(strict_types=1);

function env(string $key, ?string $default = null): string
{
    static $loaded = false;
    static $values = [];

    if (!$loaded) {
        $path = dirname(__DIR__, 2) . '/.env';
        if (is_readable($path)) {
            foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
                $line = trim($line);
                if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                    continue;
                }
                [$name, $value] = explode('=', $line, 2);
                $values[trim($name)] = trim($value, " \t\n\r\0\x0B\"");
            }
        }
        $loaded = true;
    }

    return $values[$key] ?? $_ENV[$key] ?? $default ?? '';
}

return [
    'env' => env('APP_ENV', 'production'),
    'url' => rtrim(env('APP_URL', ''), '/'),
    'session_timeout' => max(300, (int) env('SESSION_TIMEOUT', '1800')),
    'max_active_loans' => max(1, (int) env('MAX_ACTIVE_LOANS', '5')),
    'loan_days' => max(1, (int) env('LOAN_DAYS', '14')),
    'fine' => [
        'daily_rate' => max(0, (float) env('FINE_DAILY_RATE', '1.00')),
        'grace_days' => max(0, (int) env('FINE_GRACE_DAYS', '0')),
        'max_amount' => max(0, (float) env('FINE_MAX_AMOUNT', '100.00')),
    ],
    'upload_max_bytes' => max(1, (int) env('UPLOAD_MAX_BYTES', '2097152')),
    'database' => [
        'dsn' => sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', env('DB_HOST', '127.0.0.1'), env('DB_PORT', '3306'), env('DB_NAME', 'digital_library')),
        'username' => env('DB_USER'),
        'password' => env('DB_PASS'),
    ],
];
