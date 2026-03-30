<?php

declare(strict_types=1);

require_once __DIR__ . '/../../lib/db.php';
require_once __DIR__ . '/../../lib/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_response(false, null, 'Method not allowed', 405);
    exit;
}

try {
    $pdo = get_pdo();
    $user = require_login($pdo);

    $safeUser = [
        'id'        => $user['id'],
        'username'  => $user['username'],
        'full_name' => $user['full_name'],
        'email'     => $user['email'],
        'role'      => $user['role'],
    ];

    json_response(true, ['user' => $safeUser], null, 200);
} catch (Throwable $e) {
    json_response(false, null, 'Failed to fetch current user', 500);
}

