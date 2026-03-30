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
    require_admin($pdo);

    $statusParam = isset($_GET['status']) && is_string($_GET['status']) ? $_GET['status'] : null;
    $status = sanitize_service_request_status($statusParam);

    $limitParam = isset($_GET['limit']) && is_string($_GET['limit']) ? (int) $_GET['limit'] : 100;
    if ($limitParam <= 0) {
        $limitParam = 100;
    }
    if ($limitParam > 500) {
        $limitParam = 500;
    }

    if ($statusParam !== null && $status === null) {
        json_response(false, null, 'Invalid status filter', 400);
        exit;
    }

    if ($status !== null) {
        $stmt = $pdo->prepare(
            'select sr.id,
                    sr.request_title,
                    sr.category,
                    sr.description,
                    sr.budget_range,
                    sr.expected_start,
                    sr.status,
                    sr.created_at,
                    sr.updated_at,
                    u.id as client_id,
                    u.username as client_username,
                    u.full_name as client_name
             from service_requests sr
             join users u on u.id = sr.client_id
             where sr.status = :status
             order by sr.created_at desc
             limit :limit'
        );
        $stmt->bindValue(':status', $status);
        $stmt->bindValue(':limit', $limitParam, PDO::PARAM_INT);
        $stmt->execute();
    } else {
        $stmt = $pdo->prepare(
            'select sr.id,
                    sr.request_title,
                    sr.category,
                    sr.description,
                    sr.budget_range,
                    sr.expected_start,
                    sr.status,
                    sr.created_at,
                    sr.updated_at,
                    u.id as client_id,
                    u.username as client_username,
                    u.full_name as client_name
             from service_requests sr
             join users u on u.id = sr.client_id
             order by sr.created_at desc
             limit :limit'
        );
        $stmt->bindValue(':limit', $limitParam, PDO::PARAM_INT);
        $stmt->execute();
    }

    $requests = $stmt->fetchAll();

    json_response(true, ['requests' => $requests], null, 200);
} catch (Throwable $e) {
    json_response(false, null, 'Failed to load service requests', 500);
}

