<?php

declare(strict_types=1);

require_once __DIR__ . '/../../lib/db.php';
require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../lib/validators.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(false, null, 'Method not allowed', 405);
    exit;
}

try {
    $pdo = get_pdo();
    $admin = require_admin($pdo);

    $input = read_request_input();

    // Expect settings as an associative array: { "settings": { "key": "value", ... } }
    $settings = $input['settings'] ?? null;
    if (!is_array($settings) || $settings === []) {
        json_response(false, null, 'settings payload is required', 400);
        exit;
    }

    $cleanSettings = [];
    foreach ($settings as $key => $value) {
        if (!is_string($key) || $key === '') {
            continue;
        }

        // Limit key and value lengths to keep data reasonable.
        $cleanKey = sanitize_string($key, 100);
        $cleanValue = sanitize_string(is_string($value) ? $value : json_encode($value), 2000);

        if ($cleanKey === '') {
            continue;
        }

        $cleanSettings[$cleanKey] = $cleanValue;
    }

    if ($cleanSettings === []) {
        json_response(false, null, 'No valid settings provided', 400);
        exit;
    }

    $pdo->beginTransaction();

    $stmt = $pdo->prepare(
        'insert into site_settings (key, value, updated_by, updated_at)
         values (:key, :value, :updated_by, now())
         on conflict (key) do update
         set value = excluded.value,
             updated_by = excluded.updated_by,
             updated_at = excluded.updated_at'
    );

    foreach ($cleanSettings as $key => $value) {
        $stmt->execute([
            ':key'        => $key,
            ':value'      => $value,
            ':updated_by' => $admin['id'],
        ]);
    }

    $pdo->commit();

    json_response(true, ['settings' => $cleanSettings], null, 200);
} catch (Throwable $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    json_response(false, null, 'Failed to update site settings', 500);
}

