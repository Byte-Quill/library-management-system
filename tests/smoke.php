<?php
declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

if (!is_array($config) || $config['max_active_loans'] < 1) {
    throw new RuntimeException('Configuration smoke check failed.');
}

if (e('<script>alert(1)</script>') !== '&lt;script&gt;alert(1)&lt;/script&gt;') {
    throw new RuntimeException('Escaping smoke check failed.');
}

echo "Foundation smoke checks passed.\n";
