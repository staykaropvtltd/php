<?php

declare(strict_types=1);

require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/auth.php';

header('Content-Type: application/json');

try {
    $pdo = get_pdo();
    $stmt = $pdo->query('select 1 as db_ok');
    $row = $stmt->fetch();

    json_response(true, [
        'db' => ($row && (int) $row['db_ok'] === 1) ? 'ok' : 'error',
    ], null, 200);
} catch (Throwable $e) {
    json_response(false, null, 'Health check failed', 500);
}

