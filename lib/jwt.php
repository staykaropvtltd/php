<?php

declare(strict_types=1);

/**
 * Minimal HS256 JWT implementation for stateless auth cookies.
 *
 * Uses a shared secret from the JWT_SECRET environment variable.
 */

require_once __DIR__ . '/db.php';

/**
 * Base64 URL-safe encoding (no padding).
 */
function base64url_encode(string $data): string
{
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

/**
 * Base64 URL-safe decoding.
 */
function base64url_decode(string $data): string
{
    $remainder = strlen($data) % 4;
    if ($remainder) {
        $data .= str_repeat('=', 4 - $remainder);
    }

    $decoded = base64_decode(strtr($data, '-_', '+/'), true);
    if ($decoded === false) {
        throw new RuntimeException('Invalid base64url input.');
    }

    return $decoded;
}

/**
 * Retrieve the JWT secret key from environment.
 *
 * @throws RuntimeException if not configured.
 */
function get_jwt_secret(): string
{
    $secret = env('JWT_SECRET');
    if ($secret === null || $secret === '') {
        throw new RuntimeException('JWT_SECRET is not configured.');
    }

    return $secret;
}

/**
 * Encode a JWT with HS256.
 *
 * @param array $claims Custom claims to include in payload.
 * @param int|null $ttlSeconds Time-to-live in seconds (default 3600).
 */
function jwt_encode(array $claims, ?int $ttlSeconds = null): string
{
    $ttl = $ttlSeconds ?? (int) (env('JWT_TTL_SECONDS', '3600') ?? '3600');
    $issuedAt = time();
    $expiresAt = $issuedAt + $ttl;

    $header = [
        'typ' => 'JWT',
        'alg' => 'HS256',
    ];

    $payload = array_merge($claims, [
        'iat' => $issuedAt,
        'exp' => $expiresAt,
    ]);

    $headerJson = json_encode($header, JSON_UNESCAPED_SLASHES);
    $payloadJson = json_encode($payload, JSON_UNESCAPED_SLASHES);

    if ($headerJson === false || $payloadJson === false) {
        throw new RuntimeException('Failed to encode JWT.');
    }

    $segments = [
        base64url_encode($headerJson),
        base64url_encode($payloadJson),
    ];

    $signingInput = implode('.', $segments);
    $signature = hash_hmac('sha256', $signingInput, get_jwt_secret(), true);
    $segments[] = base64url_encode($signature);

    return implode('.', $segments);
}

/**
 * Decode and verify a JWT.
 *
 * Returns payload array on success, or null on failure.
 */
function jwt_decode(string $token): ?array
{
    $parts = explode('.', $token);
    if (count($parts) !== 3) {
        return null;
    }

    [$encodedHeader, $encodedPayload, $encodedSignature] = $parts;

    try {
        $headerJson = base64url_decode($encodedHeader);
        $payloadJson = base64url_decode($encodedPayload);
        $signature = base64url_decode($encodedSignature);
    } catch (RuntimeException $e) {
        return null;
    }

    $header = json_decode($headerJson, true);
    $payload = json_decode($payloadJson, true);

    if (!is_array($header) || !is_array($payload)) {
        return null;
    }

    if (($header['alg'] ?? null) !== 'HS256') {
        return null;
    }

    $signingInput = $encodedHeader . '.' . $encodedPayload;
    $expectedSignature = hash_hmac('sha256', $signingInput, get_jwt_secret(), true);

    if (!hash_equals($expectedSignature, $signature)) {
        return null;
    }

    $now = time();
    if (isset($payload['nbf']) && is_int($payload['nbf']) && $now < $payload['nbf']) {
        return null;
    }

    if (isset($payload['exp']) && is_int($payload['exp']) && $now >= $payload['exp']) {
        return null;
    }

    return $payload;
}

