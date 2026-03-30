<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/jwt.php';

const AUTH_COOKIE_NAME = 'auth_token';
const REMEMBER_COOKIE_NAME = 'remember_token';

/**
 * Send a JSON response in the standard format and exit.
 *
 * @param mixed $data
 */
function json_response(bool $success, $data = null, ?string $error = null, int $statusCode = 200): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json');

    echo json_encode([
        'success' => $success,
        'data'    => $data,
        'error'   => $error,
    ]);
}

/**
 * Determine whether cookies should be marked secure.
 * For production deployments this should be true (HTTPS).
 */
function auth_cookie_secure(): bool
{
    $env = env('APP_ENV', 'production') ?? 'production';
    return $env !== 'local';
}

/**
 * Set the auth JWT cookie for a user.
 *
 * @param array{id:string,username:string,role:string,full_name?:string} $user
 */
function set_auth_cookie(array $user): void
{
    $claims = [
        'sub'       => $user['id'],
        'username'  => $user['username'],
        'role'      => $user['role'],
        'full_name' => $user['full_name'] ?? null,
    ];

    $token = jwt_encode($claims, null);

    setcookie(
        AUTH_COOKIE_NAME,
        $token,
        [
            'expires'  => 0,
            'path'     => '/',
            'secure'   => auth_cookie_secure(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]
    );
}

/**
 * Clear the auth JWT cookie.
 */
function clear_auth_cookie(): void
{
    setcookie(
        AUTH_COOKIE_NAME,
        '',
        [
            'expires'  => time() - 3600,
            'path'     => '/',
            'secure'   => auth_cookie_secure(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]
    );
}

/**
 * Create and persist a remember-me token for the user.
 *
 * @param array{id:string} $user
 */
function create_remember_token(PDO $pdo, array $user): void
{
    $selector = base64url_encode(random_bytes(9));
    $validator = base64url_encode(random_bytes(32));

    $tokenHash = hash('sha256', $validator);
    $expiresAt = (new DateTimeImmutable('+30 days'))->format(DateTimeInterface::ATOM);

    $stmt = $pdo->prepare(
        'insert into remember_tokens (user_id, selector, token_hash, expires_at)
         values (:user_id, :selector, :token_hash, :expires_at)'
    );
    $stmt->execute([
        ':user_id'    => $user['id'],
        ':selector'   => $selector,
        ':token_hash' => $tokenHash,
        ':expires_at' => $expiresAt,
    ]);

    $cookieValue = $selector . ':' . $validator;

    setcookie(
        REMEMBER_COOKIE_NAME,
        $cookieValue,
        [
            'expires'  => time() + 60 * 60 * 24 * 30,
            'path'     => '/',
            'secure'   => auth_cookie_secure(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]
    );
}

/**
 * Clear remember-me cookie and revoke the token if present.
 */
function clear_remember_token(PDO $pdo): void
{
    $cookie = $_COOKIE[REMEMBER_COOKIE_NAME] ?? '';
    if (is_string($cookie) && $cookie !== '') {
        $parts = explode(':', $cookie, 2);
        if (count($parts) === 2) {
            $selector = $parts[0];

            $stmt = $pdo->prepare(
                'update remember_tokens
                 set revoked_at = now()
                 where selector = :selector and revoked_at is null'
            );
            $stmt->execute([':selector' => $selector]);
        }
    }

    setcookie(
        REMEMBER_COOKIE_NAME,
        '',
        [
            'expires'  => time() - 3600,
            'path'     => '/',
            'secure'   => auth_cookie_secure(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]
    );
}

/**
 * Attempt to hydrate user from remember-me cookie when JWT is absent/invalid.
 */
function try_login_with_remember_me(PDO $pdo): ?array
{
    $cookie = $_COOKIE[REMEMBER_COOKIE_NAME] ?? '';
    if (!is_string($cookie) || $cookie === '') {
        return null;
    }

    $parts = explode(':', $cookie, 2);
    if (count($parts) !== 2) {
        return null;
    }

    [$selector, $validator] = $parts;
    if ($selector === '' || $validator === '') {
        return null;
    }

    $stmt = $pdo->prepare(
        'select rt.id,
                rt.user_id,
                rt.token_hash,
                rt.expires_at,
                u.username,
                u.full_name,
                u.role,
                u.is_active
         from remember_tokens rt
         join users u on u.id = rt.user_id
         where rt.selector = :selector
           and rt.revoked_at is null
           and rt.expires_at > now()'
    );
    $stmt->execute([':selector' => $selector]);
    $row = $stmt->fetch();

    if (!$row) {
        return null;
    }

    if (empty($row['is_active'])) {
        return null;
    }

    $expectedHash = $row['token_hash'] ?? '';
    $actualHash = hash('sha256', $validator);

    if (!hash_equals((string) $expectedHash, $actualHash)) {
        return null;
    }

    $user = [
        'id'        => $row['user_id'],
        'username'  => $row['username'],
        'full_name' => $row['full_name'],
        'role'      => $row['role'],
        'is_active' => (bool) $row['is_active'],
    ];

    // Rotate validator on each successful use
    $newValidator = base64url_encode(random_bytes(32));
    $newHash = hash('sha256', $newValidator);

    $update = $pdo->prepare(
        'update remember_tokens
         set token_hash = :token_hash
         where id = :id'
    );
    $update->execute([
        ':token_hash' => $newHash,
        ':id'         => $row['id'],
    ]);

    $newCookieValue = $selector . ':' . $newValidator;

    setcookie(
        REMEMBER_COOKIE_NAME,
        $newCookieValue,
        [
            'expires'  => time() + 60 * 60 * 24 * 30,
            'path'     => '/',
            'secure'   => auth_cookie_secure(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]
    );

    // Issue fresh JWT
    set_auth_cookie($user);

    return $user;
}

/**
 * Fetch a user row by username.
 */
function find_user_by_username(PDO $pdo, string $username): ?array
{
    $stmt = $pdo->prepare(
        'select id, username, full_name, email, password_hash, role, is_active
         from users
         where username = :username
         limit 1'
    );
    $stmt->execute([':username' => $username]);
    $user = $stmt->fetch();

    return $user ?: null;
}

/**
 * Fetch a user row by id.
 */
function find_user_by_id(PDO $pdo, string $id): ?array
{
    $stmt = $pdo->prepare(
        'select id, username, full_name, email, password_hash, role, is_active
         from users
         where id = :id
         limit 1'
    );
    $stmt->execute([':id' => $id]);
    $user = $stmt->fetch();

    return $user ?: null;
}

/**
 * Get the currently authenticated user from JWT or remember-me.
 */
function get_current_user(?PDO $pdo = null): ?array
{
    $pdo = $pdo ?? get_pdo();

    $token = $_COOKIE[AUTH_COOKIE_NAME] ?? null;
    if (is_string($token) && $token !== '') {
        $payload = jwt_decode($token);
        if (is_array($payload) && isset($payload['sub'])) {
            $user = find_user_by_id($pdo, (string) $payload['sub']);
            if ($user && !empty($user['is_active'])) {
                return $user;
            }
        }
    }

    // Fallback to remember-me cookie
    return try_login_with_remember_me($pdo);
}

/**
 * Ensure there is a logged-in user, otherwise respond 401 and exit.
 *
 * @return array Authenticated user row.
 */
function require_login(?PDO $pdo = null): array
{
    $user = get_current_user($pdo);
    if (!$user) {
        json_response(false, null, 'Authentication required', 401);
        exit;
    }

    return $user;
}

/**
 * Ensure the current user has admin role.
 */
function require_admin(?PDO $pdo = null): array
{
    $user = require_login($pdo);
    if (($user['role'] ?? null) !== 'admin') {
        json_response(false, null, 'Admin access required', 403);
        exit;
    }

    return $user;
}

/**
 * Ensure the current user has client role.
 */
function require_client(?PDO $pdo = null): array
{
    $user = require_login($pdo);
    if (($user['role'] ?? null) !== 'client') {
        json_response(false, null, 'Client access required', 403);
        exit;
    }

    return $user;
}

