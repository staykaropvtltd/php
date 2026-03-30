<?php

declare(strict_types=1);

require_once __DIR__ . '/../../lib/db.php';
require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../lib/validators.php';
require_once __DIR__ . '/../../lib/cloudinary.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(false, null, 'Method not allowed', 405);
    exit;
}

try {
    $pdo = get_pdo();
    $admin = require_admin($pdo);

    if (!isset($_FILES['image'])) {
        json_response(false, null, 'Image file is required', 400);
        exit;
    }

    $input = read_request_input();
    $title = sanitize_string(isset($input['title']) ? (string) $input['title'] : '', 255);
    $caption = sanitize_string(isset($input['caption']) ? (string) $input['caption'] : '', 1000);

    $sortOrder = null;
    if (isset($input['sort_order'])) {
        $sortOrderRaw = is_numeric($input['sort_order']) ? (int) $input['sort_order'] : null;
        if ($sortOrderRaw !== null && $sortOrderRaw >= 0) {
            $sortOrder = $sortOrderRaw;
        }
    }

    $uploadInfo = cloudinary_upload_image($_FILES['image'], 'ait/gallery');

    $stmt = $pdo->prepare(
        'insert into gallery_images (
             public_id,
             secure_url,
             format,
             bytes,
             width,
             height,
             title,
             caption,
             sort_order,
             is_visible,
             uploaded_by,
             uploaded_at
         ) values (
             :public_id,
             :secure_url,
             :format,
             :bytes,
             :width,
             :height,
             :title,
             :caption,
             :sort_order,
             :is_visible,
             :uploaded_by,
             now()
         )
         returning id, title, caption, secure_url'
    );

    $stmt->bindValue(':public_id', $uploadInfo['public_id']);
    $stmt->bindValue(':secure_url', $uploadInfo['secure_url']);
    $stmt->bindValue(':format', $uploadInfo['format']);
    $stmt->bindValue(':bytes', $uploadInfo['bytes']);
    $stmt->bindValue(':width', $uploadInfo['width']);
    $stmt->bindValue(':height', $uploadInfo['height']);
    $stmt->bindValue(':title', $title !== '' ? $title : null);
    $stmt->bindValue(':caption', $caption !== '' ? $caption : null);
    if ($sortOrder !== null) {
        $stmt->bindValue(':sort_order', $sortOrder, PDO::PARAM_INT);
    } else {
        $stmt->bindValue(':sort_order', null, PDO::PARAM_NULL);
    }
    $stmt->bindValue(':is_visible', true, PDO::PARAM_BOOL);
    $stmt->bindValue(':uploaded_by', $admin['id']);

    $stmt->execute();
    $row = $stmt->fetch();

    json_response(true, [
        'id'         => $row['id'] ?? null,
        'secure_url' => $row['secure_url'] ?? $uploadInfo['secure_url'],
        'title'      => $row['title'] ?? $title,
        'caption'    => $row['caption'] ?? $caption,
    ], null, 201);
} catch (Throwable $e) {
    json_response(false, null, 'Failed to upload image', 500);
}

