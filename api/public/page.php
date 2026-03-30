<?php

declare(strict_types=1);

require_once __DIR__ . '/../../lib/db.php';
require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../lib/validators.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_response(false, null, 'Method not allowed', 405);
    exit;
}

$slugParam = $_GET['slug'] ?? '';
$slugParam = is_string($slugParam) ? $slugParam : '';

$slug = sanitize_page_slug($slugParam);
if ($slug === null) {
    json_response(false, null, 'Invalid page slug', 400);
    exit;
}

try {
    $pdo = get_pdo();

    $stmt = $pdo->prepare(
        'select p.slug,
                p.title,
                p.is_published,
                c.heading,
                c.subheading,
                c.content,
                c.highlight
         from pages p
         left join page_content c on c.page_id = p.id
         where p.slug = :slug
           and p.is_published = true
         limit 1'
    );
    $stmt->execute([':slug' => $slug]);
    $page = $stmt->fetch();

    if (!$page) {
        json_response(false, null, 'Page not found', 404);
        exit;
    }

    $response = [
        'slug'       => $page['slug'],
        'title'      => $page['title'],
        'heading'    => $page['heading'],
        'subheading' => $page['subheading'],
        'content'    => $page['content'],
        'highlight'  => $page['highlight'],
    ];

    json_response(true, ['page' => $response], null, 200);
} catch (Throwable $e) {
    json_response(false, null, 'Failed to load page', 500);
}

