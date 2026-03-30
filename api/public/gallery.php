<?php

declare(strict_types=1);

require_once __DIR__ . '/../../lib/db.php';
require_once __DIR__ . '/../../lib/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_response(false, null, 'Method not allowed', 405);
    exit;
}

try {
    $pdo = get_pdo();

    $stmt = $pdo->query(
        'select id,
                public_id,
                secure_url,
                format,
                bytes,
                width,
                height,
                title,
                caption,
                sort_order
         from gallery_images
         where is_visible = true
           and deleted_at is null
         order by sort_order nulls last, uploaded_at desc'
    );

    $images = $stmt->fetchAll();

    json_response(true, ['images' => $images], null, 200);
} catch (Throwable $e) {
    json_response(false, null, 'Failed to load gallery', 500);
}

