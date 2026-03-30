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

    $requestId = isset($input['request_id']) ? (string) $input['request_id'] : '';
    $statusRaw = isset($input['status']) ? (string) $input['status'] : '';
    $note = sanitize_string(isset($input['note']) ? (string) $input['note'] : '', 4000);

    if ($requestId === '') {
        json_response(false, null, 'request_id is required', 400);
        exit;
    }

    // Basic UUID format guard; database will enforce actual validity.
    if (!preg_match('/^[0-9a-fA-F-]{36}$/', $requestId)) {
        json_response(false, null, 'Invalid request_id format', 400);
        exit;
    }

    $status = sanitize_service_request_status($statusRaw);
    if ($status === null) {
        json_response(false, null, 'Invalid status', 400);
        exit;
    }

    $pdo->beginTransaction();

    $update = $pdo->prepare(
        'update service_requests
         set status = :status,
             updated_at = now()
         where id = :id
         returning id, status, updated_at'
    );
    $update->execute([
        ':status' => $status,
        ':id'     => $requestId,
    ]);

    $updated = $update->fetch();
    if (!$updated) {
        $pdo->rollBack();
        json_response(false, null, 'Service request not found', 404);
        exit;
    }

    if ($note !== '') {
        $insertNote = $pdo->prepare(
            'insert into service_request_updates (request_id, author_id, note)
             values (:request_id, :author_id, :note)'
        );
        $insertNote->execute([
            ':request_id' => $requestId,
            ':author_id'  => $admin['id'],
            ':note'       => $note,
        ]);
    }

    $pdo->commit();

    json_response(true, ['request' => $updated], null, 200);
} catch (Throwable $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    json_response(false, null, 'Failed to update request status', 500);
}

