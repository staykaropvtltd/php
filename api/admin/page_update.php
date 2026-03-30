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
    $admin = require_admin($pdo);

    $input = read_request_input();

    $slugRaw = isset($input['slug']) ? (string) $input['slug'] : '';
    $slug = sanitize_page_slug($slugRaw);
    if ($slug === null) {
        json_response(false, null, 'Invalid page slug', 400);
        exit;
    }

    $title = sanitize_string(isset($input['title']) ? (string) $input['title'] : '', 255);
    $heading = sanitize_string(isset($input['heading']) ? (string) $input['heading'] : '', 255);
    $subheading = sanitize_string(isset($input['subheading']) ? (string) $input['subheading'] : '', 255);
    $content = sanitize_string(isset($input['content']) ? (string) $input['content'] : '', 8000);
    $highlight = sanitize_string(isset($input['highlight']) ? (string) $input['highlight'] : '', 1000);

    $isPublished = $input['is_published'] ?? true;
    $isPublished = filter_var($isPublished, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    if ($isPublished === null) {
        $isPublished = true;
    }

    if ($title === '') {
        // Fallback to a simple derived title if not provided.
        $title = ucfirst($slug);
    }

    $pdo->beginTransaction();

    // Upsert page row
    $selectPage = $pdo->prepare('select id from pages where slug = :slug limit 1');
    $selectPage->execute([':slug' => $slug]);
    $page = $selectPage->fetch();

    if ($page) {
        $pageId = $page['id'];

        $updatePage = $pdo->prepare(
            'update pages
             set title = :title,
                 is_published = :is_published,
                 updated_by = :updated_by,
                 updated_at = now()
             where id = :id'
        );
        $updatePage->execute([
            ':title'        => $title,
            ':is_published' => $isPublished,
            ':updated_by'   => $admin['id'],
            ':id'           => $pageId,
        ]);
    } else {
        $insertPage = $pdo->prepare(
            'insert into pages (slug, title, is_published, updated_by)
             values (:slug, :title, :is_published, :updated_by)
             returning id'
        );
        $insertPage->execute([
            ':slug'        => $slug,
            ':title'       => $title,
            ':is_published'=> $isPublished,
            ':updated_by'  => $admin['id'],
        ]);
        $row = $insertPage->fetch();
        $pageId = $row['id'];
    }

    // Upsert page_content
    $upsertContent = $pdo->prepare(
        'insert into page_content (page_id, heading, subheading, content, highlight, updated_by, updated_at)
         values (:page_id, :heading, :subheading, :content, :highlight, :updated_by, now())
         on conflict (page_id) do update
         set heading = excluded.heading,
             subheading = excluded.subheading,
             content = excluded.content,
             highlight = excluded.highlight,
             updated_by = excluded.updated_by,
             updated_at = excluded.updated_at'
    );
    $upsertContent->execute([
        ':page_id'    => $pageId,
        ':heading'    => $heading !== '' ? $heading : null,
        ':subheading' => $subheading !== '' ? $subheading : null,
        ':content'    => $content !== '' ? $content : null,
        ':highlight'  => $highlight !== '' ? $highlight : null,
        ':updated_by' => $admin['id'],
    ]);

    $pdo->commit();

    json_response(true, ['slug' => $slug], null, 200);
} catch (Throwable $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    json_response(false, null, 'Failed to update page', 500);
}

