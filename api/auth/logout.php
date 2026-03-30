<?php

declare(strict_types=1);

require_once __DIR__ . '/../../lib/db.php';
require_once __DIR__ . '/../../lib/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(false, null, 'Method not allowed', 405);
    exit;
}

try {
    $pdo = get_pdo();
    clear_auth_cookie();
    clear_remember_token($pdo);

    json_response(true, ['message' => 'Logged out'], null, 200);
} catch (Throwable $e) {
    json_response(false, null, 'Logout failed', 500);
}

