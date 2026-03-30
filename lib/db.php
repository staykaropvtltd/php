<?php

declare(strict_types=1);

/**
 * Small helper to read environment variables with an optional default.
 */
function env(string $key, ?string $default = null): ?string
{
    $value = getenv($key);
    if ($value === false) {
        return $default;
    }

    $trimmed = trim($value);
    return $trimmed === '' ? $default : $trimmed;
}

/**
 * Create (and reuse) a PDO connection to Supabase Postgres.
 *
 * Configuration:
 * - Prefer DATABASE_URL (e.g. postgres://user:pass@host:5432/dbname?sslmode=require)
 * - Fallback to DB_HOST, DB_NAME, DB_USER, DB_PASS
 *
 * Guarantees:
 * - sslmode=require
 * - no persistent connections
 * - associative fetch mode by default
 * - emulated prepares enabled (avoids server-side prepared statement dependency)
 *
 * @throws RuntimeException when configuration is invalid or connection fails.
 */
function get_pdo(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $databaseUrl = env('DATABASE_URL');
    $dsn = null;
    $user = null;
    $pass = null;

    if ($databaseUrl) {
        $parts = parse_url($databaseUrl);
        if ($parts === false || !isset($parts['host'], $parts['path'])) {
            throw new RuntimeException('Invalid DATABASE_URL.');
        }

        $host = $parts['host'];
        $port = $parts['port'] ?? 5432;
        $dbname = ltrim($parts['path'], '/');
        $user = $parts['user'] ?? null;
        $pass = $parts['pass'] ?? null;

        if ($user === null || $pass === null || $dbname === '') {
            throw new RuntimeException('DATABASE_URL must include username, password, and database name.');
        }

        // Always enforce sslmode=require for Supabase, regardless of URL query params.
        $dsn = sprintf(
            'pgsql:host=%s;port=%d;dbname=%s;sslmode=require',
            $host,
            $port,
            $dbname
        );
    } else {
        $host = env('DB_HOST');
        $dbname = env('DB_NAME');
        $user = env('DB_USER');
        $pass = env('DB_PASS');

        if ($host === null || $dbname === null || $user === null || $pass === null) {
            throw new RuntimeException('Database configuration is missing. Set DATABASE_URL or DB_HOST/DB_NAME/DB_USER/DB_PASS.');
        }

        $port = (int) (env('DB_PORT', '5432') ?? 5432);

        $dsn = sprintf(
            'pgsql:host=%s;port=%d;dbname=%s;sslmode=require',
            $host,
            $port,
            $dbname
        );
    }

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_PERSISTENT         => false,
        // Use client-side emulated prepares to stay compatible with Supabase transaction pooler.
        PDO::ATTR_EMULATE_PREPARES   => true,
    ];

    try {
        $pdo = new PDO($dsn, (string) $user, (string) $pass, $options);
    } catch (\Throwable $e) {
        // Do not leak sensitive connection details.
        throw new RuntimeException('Failed to connect to the database.');
    }

    return $pdo;
}

