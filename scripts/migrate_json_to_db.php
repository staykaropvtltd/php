<?php

declare(strict_types=1);

require_once __DIR__ . '/../lib/db.php';

/**
 * One-time helper script to migrate existing JSON data
 * (data/users.json and data/site_content.json) into Postgres.
 *
 * Usage:
 *   php scripts/migrate_json_to_db.php
 *
 * Requires DATABASE_URL (or DB_* env vars) to be set.
 */

function load_json_file(string $path): array
{
    if (!is_file($path)) {
        echo "Skipping missing file: {$path}\n";
        return [];
    }

    $raw = file_get_contents($path);
    if ($raw === false) {
        echo "Failed to read file: {$path}\n";
        return [];
    }

    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        echo "Invalid JSON in file: {$path}\n";
        return [];
    }

    return $decoded;
}

try {
    $pdo = get_pdo();
    echo "Connected to database.\n";

    // 1) Migrate users.json
    $usersPath = __DIR__ . '/../data/users.json';
    $users = load_json_file($usersPath);

    if ($users !== []) {
        echo "Migrating users from {$usersPath}...\n";

        $select = $pdo->prepare(
            'select id from users where username = :username limit 1'
        );
        $insert = $pdo->prepare(
            'insert into users (username, full_name, password_hash, role, is_active)
             values (:username, :full_name, :password_hash, :role, true)'
        );
        $update = $pdo->prepare(
            'update users
             set full_name = :full_name,
                 password_hash = :password_hash,
                 role = :role,
                 is_active = true,
                 updated_at = now()
             where id = :id'
        );

        foreach ($users as $user) {
            if (!is_array($user)) {
                continue;
            }

            $username = isset($user['username']) ? trim((string) $user['username']) : '';
            $name = isset($user['name']) ? trim((string) $user['name']) : $username;
            $passwordHash = isset($user['password']) ? (string) $user['password'] : '';
            $role = isset($user['role']) ? trim((string) $user['role']) : 'client';

            if ($username === '' || $passwordHash === '') {
                continue;
            }

            if (!in_array($role, ['admin', 'client'], true)) {
                $role = 'client';
            }

            $select->execute([':username' => $username]);
            $existing = $select->fetch();

            if ($existing) {
                $update->execute([
                    ':id'           => $existing['id'],
                    ':full_name'    => $name,
                    ':password_hash'=> $passwordHash,
                    ':role'         => $role,
                ]);
                echo "Updated user: {$username}\n";
            } else {
                $insert->execute([
                    ':username'      => $username,
                    ':full_name'     => $name,
                    ':password_hash' => $passwordHash,
                    ':role'          => $role,
                ]);
                echo "Inserted user: {$username}\n";
            }
        }
    } else {
        echo "No users to migrate.\n";
    }

    // 2) Migrate site_content.json
    $contentPath = __DIR__ . '/../data/site_content.json';
    $content = load_json_file($contentPath);

    if ($content !== []) {
        echo "Migrating site content from {$contentPath}...\n";

        $settings = isset($content['settings']) && is_array($content['settings'])
            ? $content['settings']
            : [];

        $pages = isset($content['pages']) && is_array($content['pages'])
            ? $content['pages']
            : [];

        // Site settings
        if ($settings !== []) {
            $upsertSetting = $pdo->prepare(
                'insert into site_settings (key, value, updated_at)
                 values (:key, :value, now())
                 on conflict (key) do update
                 set value = excluded.value,
                     updated_at = excluded.updated_at'
            );

            foreach ($settings as $key => $value) {
                if (!is_string($key) || $key === '') {
                    continue;
                }

                $valueStr = is_string($value) ? $value : json_encode($value);
                if ($valueStr === false) {
                    continue;
                }

                $upsertSetting->execute([
                    ':key'   => $key,
                    ':value' => $valueStr,
                ]);
                echo "Upserted setting: {$key}\n";
            }
        }

        // Pages (home/about/service)
        if ($pages !== []) {
            $selectPage = $pdo->prepare('select id from pages where slug = :slug limit 1');
            $insertPage = $pdo->prepare(
                'insert into pages (slug, title, is_published)
                 values (:slug, :title, true)
                 returning id'
            );
            $updatePage = $pdo->prepare(
                'update pages
                 set title = :title,
                     is_published = true,
                     updated_at = now()
                 where id = :id'
            );

            $upsertContent = $pdo->prepare(
                'insert into page_content (page_id, heading, subheading, content, highlight, updated_at)
                 values (:page_id, :heading, :subheading, :content, :highlight, now())
                 on conflict (page_id) do update
                 set heading = excluded.heading,
                     subheading = excluded.subheading,
                     content = excluded.content,
                     highlight = excluded.highlight,
                     updated_at = excluded.updated_at'
            );

            foreach ($pages as $slug => $pageData) {
                if (!is_string($slug) || !is_array($pageData)) {
                    continue;
                }

                $slug = strtolower(trim($slug));
                if (!in_array($slug, ['home', 'about', 'service'], true)) {
                    continue;
                }

                $heading = isset($pageData['heading']) ? trim((string) $pageData['heading']) : '';
                $subheading = isset($pageData['subheading']) ? trim((string) $pageData['subheading']) : '';
                $body = isset($pageData['content']) ? (string) $pageData['content'] : '';
                $highlight = isset($pageData['highlight']) ? trim((string) $pageData['highlight']) : '';

                $title = ucfirst($slug);

                $selectPage->execute([':slug' => $slug]);
                $existingPage = $selectPage->fetch();

                if ($existingPage) {
                    $pageId = $existingPage['id'];
                    $updatePage->execute([
                        ':id'    => $pageId,
                        ':title' => $title,
                    ]);
                    echo "Updated page: {$slug}\n";
                } else {
                    $insertPage->execute([
                        ':slug'  => $slug,
                        ':title' => $title,
                    ]);
                    $row = $insertPage->fetch();
                    $pageId = $row['id'];
                    echo "Inserted page: {$slug}\n";
                }

                $upsertContent->execute([
                    ':page_id'    => $pageId,
                    ':heading'    => $heading !== '' ? $heading : null,
                    ':subheading' => $subheading !== '' ? $subheading : null,
                    ':content'    => $body !== '' ? $body : null,
                    ':highlight'  => $highlight !== '' ? $highlight : null,
                ]);
                echo "Upserted content for page: {$slug}\n";
            }
        }
    } else {
        echo "No site content to migrate.\n";
    }

    echo "Migration completed.\n";
} catch (Throwable $e) {
    fwrite(STDERR, "Migration failed.\n");
    exit(1);
}

