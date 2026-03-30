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

    $stmt = $pdo->query('select key, value from site_settings');
    $rows = $stmt->fetchAll();

    $settings = [];
    foreach ($rows as $row) {
        $settings[$row['key']] = $row['value'];
    }

    json_response(true, ['settings' => $settings], null, 200);
} catch (Throwable $e) {
    json_response(false, null, 'Failed to load site settings', 500);
}

