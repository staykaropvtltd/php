<?php

declare(strict_types=1);

require_once __DIR__ . '/../../lib/db.php';
require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../lib/validators.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(false, null, 'Method not allowed', 405);
    exit;
}

$input = read_request_input();

$name = sanitize_string(isset($input['name']) ? (string) $input['name'] : '', 255);
$email = sanitize_string(isset($input['email']) ? (string) $input['email'] : '', 255);
$phone = sanitize_string(isset($input['phone']) ? (string) $input['phone'] : '', 50);
$subject = sanitize_string(isset($input['subject']) ? (string) $input['subject'] : '', 255);
$message = sanitize_string(isset($input['message']) ? (string) $input['message'] : '', 4000);

if ($name === '' || $subject === '' || $message === '') {
    json_response(false, null, 'Name, subject, and message are required', 400);
    exit;
}

if ($email === '' || !is_valid_email($email)) {
    json_response(false, null, 'A valid email is required', 400);
    exit;
}

try {
    $pdo = get_pdo();

    $stmt = $pdo->prepare(
        'insert into contact_messages (name, email, phone, subject, message, status)
         values (:name, :email, :phone, :subject, :message, :status)
         returning id, created_at'
    );

    $stmt->execute([
        ':name'    => $name,
        ':email'   => $email,
        ':phone'   => $phone !== '' ? $phone : null,
        ':subject' => $subject,
        ':message' => $message,
        ':status'  => 'new',
    ]);

    $row = $stmt->fetch();

    json_response(true, ['id' => $row['id'] ?? null, 'created_at' => $row['created_at'] ?? null], null, 201);
} catch (Throwable $e) {
    json_response(false, null, 'Failed to submit contact message', 500);
}

