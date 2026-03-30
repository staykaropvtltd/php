<?php

declare(strict_types=1);

require_once __DIR__ . '/../../lib/db.php';
require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../lib/validators.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_response(false, null, 'Method not allowed', 405);
    exit;
}

try {
    $pdo = get_pdo();
    $user = require_client($pdo);

    $statusParam = isset($_GET['status']) && is_string($_GET['status']) ? $_GET['status'] : null;
    $status = sanitize_service_request_status($statusParam);

    $limitParam = isset($_GET['limit']) && is_string($_GET['limit']) ? (int) $_GET['limit'] : 50;
    if ($limitParam <= 0) {
        $limitParam = 50;
    }
    if ($limitParam > 200) {
        $limitParam = 200;
    }

    if ($statusParam !== null && $status === null) {
        json_response(false, null, 'Invalid status filter', 400);
        exit;
    }

    if ($status !== null) {
        $stmt = $pdo->prepare(
            'select id,
                    request_title,
                    category,
                    description,
                    budget_range,
                    expected_start,
                    status,
                    created_at,
                    updated_at
             from service_requests
             where client_id = :client_id
               and status = :status
             order by created_at desc
             limit :limit'
        );
        $stmt->bindValue(':client_id', $user['id']);
        $stmt->bindValue(':status', $status);
        $stmt->bindValue(':limit', $limitParam, PDO::PARAM_INT);
        $stmt->execute();
    } else {
        $stmt = $pdo->prepare(
            'select id,
                    request_title,
                    category,
                    description,
                    budget_range,
                    expected_start,
                    status,
                    created_at,
                    updated_at
             from service_requests
             where client_id = :client_id
             order by created_at desc
             limit :limit'
        );
        $stmt->bindValue(':client_id', $user['id']);
        $stmt->bindValue(':limit', $limitParam, PDO::PARAM_INT);
        $stmt->execute();
    }

    $requests = $stmt->fetchAll();

    json_response(true, ['requests' => $requests], null, 200);
} catch (Throwable $e) {
    json_response(false, null, 'Failed to load requests', 500);
}

