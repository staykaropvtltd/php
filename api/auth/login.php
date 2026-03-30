<?php

declare(strict_types=1);

require_once __DIR__ . '/../../lib/db.php';
require_once __DIR__ . '/../../lib/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(false, null, 'Method not allowed', 405);
    exit;
}

// Support both form-encoded and JSON payloads
$input = $_POST;
if (empty($input)) {
    $raw = file_get_contents('php://input');
    if (is_string($raw) && $raw !== '') {
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            $input = $decoded;
        }
    }
}

$username = trim((string) ($input['username'] ?? ''));
$password = (string) ($input['password'] ?? '');
$remember = (bool) ($input['remember_me'] ?? false);

if ($username === '' || $password === '') {
    json_response(false, null, 'Username and password are required', 400);
    exit;
}

try {
    $pdo = get_pdo();
    $user = find_user_by_username($pdo, $username);

    if (!$user || empty($user['is_active']) || !password_verify($password, $user['password_hash'])) {
        json_response(false, null, 'Invalid credentials', 401);
        exit;
    }

    set_auth_cookie($user);

    if ($remember) {
        create_remember_token($pdo, $user);
    }

    $safeUser = [
        'id'        => $user['id'],
        'username'  => $user['username'],
        'full_name' => $user['full_name'],
        'email'     => $user['email'],
        'role'      => $user['role'],
    ];

    json_response(true, ['user' => $safeUser], null, 200);
} catch (Throwable $e) {
    json_response(false, null, 'Login failed', 500);
}

