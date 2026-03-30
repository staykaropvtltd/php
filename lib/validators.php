<?php

declare(strict_types=1);

/**
 * Read request input supporting:
 * - GET query params
 * - POST form-encoded
 * - JSON body
 */
function read_request_input(): array
{
    $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

    if ($method === 'GET') {
        return $_GET;
    }

    if (!empty($_POST)) {
        return $_POST;
    }

    $raw = file_get_contents('php://input');
    if (!is_string($raw) || $raw === '') {
        return [];
    }

    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        return [];
    }

    return $decoded;
}

/**
 * Trim and limit a string from input.
 */
function sanitize_string(?string $value, int $maxLength = 255): string
{
    $value = $value ?? '';
    $value = trim($value);

    if ($maxLength > 0 && strlen($value) > $maxLength) {
        $value = substr($value, 0, $maxLength);
    }

    return $value;
}

/**
 * Basic email validation.
 */
function is_valid_email(?string $email): bool
{
    if ($email === null || $email === '') {
        return false;
    }

    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Whitelist page slugs to avoid arbitrary input.
 */
function sanitize_page_slug(string $slug): ?string
{
    $slug = strtolower(trim($slug));

    $allowed = ['home', 'about', 'service'];

    return in_array($slug, $allowed, true) ? $slug : null;
}

/**
 * Sanitize service request status enum.
 */
function sanitize_service_request_status(?string $status): ?string
{
    if ($status === null) {
        return null;
    }

    $status = strtolower(trim($status));

    $allowed = [
        'submitted',
        'in_review',
        'quoted',
        'approved',
        'in_progress',
        'completed',
        'rejected',
    ];

    return in_array($status, $allowed, true) ? $status : null;
}


