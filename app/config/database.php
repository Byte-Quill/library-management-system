<?php
declare(strict_types=1);

function database(array $config): PDO
{
    static $connection;
    if ($connection instanceof PDO) {
        return $connection;
    }

    $connection = new PDO(
        $config['database']['dsn'],
        $config['database']['username'],
        $config['database']['password'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );

    // Avoid silent truncation of aggregated author lists in catalog queries.
    $connection->exec('SET SESSION group_concat_max_len = 10000');

    return $connection;
}
