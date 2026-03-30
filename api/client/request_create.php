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
    $user = require_client($pdo);

    $input = read_request_input();

    $title = sanitize_string(isset($input['request_title']) ? (string) $input['request_title'] : '', 255);
    $category = sanitize_string(isset($input['category']) ? (string) $input['category'] : '', 255);
    $description = sanitize_string(isset($input['description']) ? (string) $input['description'] : '', 4000);
    $budgetRange = sanitize_string(isset($input['budget_range']) ? (string) $input['budget_range'] : '', 255);
    $expectedStart = sanitize_string(isset($input['expected_start']) ? (string) $input['expected_start'] : '', 32);

    if ($title === '') {
        json_response(false, null, 'Request title is required', 400);
        exit;
    }

    $expectedStartDate = null;
    if ($expectedStart !== '') {
        $dt = DateTimeImmutable::createFromFormat('Y-m-d', $expectedStart);
        if (!$dt) {
            json_response(false, null, 'expected_start must be in YYYY-MM-DD format', 400);
            exit;
        }
        $expectedStartDate = $dt->format('Y-m-d');
    }

    $stmt = $pdo->prepare(
        'insert into service_requests (
             client_id,
             request_title,
             category,
             description,
             budget_range,
             expected_start,
             status
         )
         values (
             :client_id,
             :request_title,
             :category,
             :description,
             :budget_range,
             :expected_start,
             :status
         )
         returning id, created_at, status'
    );

    $stmt->execute([
        ':client_id'     => $user['id'],
        ':request_title' => $title,
        ':category'      => $category !== '' ? $category : null,
        ':description'   => $description !== '' ? $description : null,
        ':budget_range'  => $budgetRange !== '' ? $budgetRange : null,
        ':expected_start'=> $expectedStartDate,
        ':status'        => 'submitted',
    ]);

    $row = $stmt->fetch();

    json_response(true, ['request' => $row], null, 201);
} catch (Throwable $e) {
    json_response(false, null, 'Failed to create request', 500);
}

