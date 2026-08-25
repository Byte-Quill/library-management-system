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
                $value = trim($value);
                // Quoted values keep their contents (and any inline comment text
                // inside the quotes); unquoted values stop at an inline comment.
                if (strlen($value) >= 2 && ($value[0] === '"' || $value[0] === "'") && str_ends_with($value, $value[0])) {
                    $value = substr($value, 1, -1);
                } else {
                    $hash = strpos($value, ' #');
                    if ($hash !== false) {
                        $value = rtrim(substr($value, 0, $hash));
                    }
                }
                $values[trim($name)] = $value;
            }
        }
        $loaded = true;
    }

    if (array_key_exists($key, $values)) {
        return $values[$key];
    }
    $envValue = $_ENV[$key] ?? getenv($key);
    if ($envValue !== false && $envValue !== null && $envValue !== '') {
        return (string) $envValue;
    }
    return $default ?? '';
}

function databaseUrlConfig(?string $url): ?array
{
    if ($url === null || trim($url) === '') {
        return null;
    }

    $parsed = parse_url($url);
    if ($parsed === false || empty($parsed['host']) || empty($parsed['scheme'])) {
        return null;
    }

    $database = ltrim($parsed['path'] ?? '/', '/');
    return [
        'dsn' => sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $parsed['host'], (string) ($parsed['port'] ?? 3306), $database),
        'username' => rawurldecode($parsed['user'] ?? ''),
        'password' => rawurldecode($parsed['pass'] ?? ''),
    ];
}

$dbConfig = databaseUrlConfig(env('DB_URL')) ?? [
    'dsn' => sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', env('DB_HOST', '127.0.0.1'), env('DB_PORT', '3306'), env('DB_NAME', 'digital_library')),
    'username' => env('DB_USER', ''),
    'password' => env('DB_PASS', ''),
];

return [
    'env' => env('APP_ENV', 'production'),
    'url' => rtrim(env('APP_URL', ''), '/'),
    'session_timeout' => max(300, (int) env('SESSION_TIMEOUT', '1800')),
    'session_absolute' => max(0, (int) env('SESSION_ABSOLUTE', '28800')),
    'trusted_proxies' => array_values(array_filter(array_map('trim', explode(',', env('TRUSTED_PROXIES', ''))))),
    'max_active_loans' => max(1, (int) env('MAX_ACTIVE_LOANS', '5')),
    'loan_days' => max(1, (int) env('LOAN_DAYS', '14')),
    'fine' => [
        'daily_rate' => max(0, (float) env('FINE_DAILY_RATE', '1.00')),
        'grace_days' => max(0, (int) env('FINE_GRACE_DAYS', '0')),
        'max_amount' => max(0, (float) env('FINE_MAX_AMOUNT', '100.00')),
    ],
    'upload_max_bytes' => max(1, (int) env('UPLOAD_MAX_BYTES', '2097152')),
    'database' => $dbConfig,
];
