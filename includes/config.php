<?php

return [
    // Set this to '/folder-name' if the app is hosted in a subdirectory.
    'base_path' => '',
    'session_name' => 'company_site_session',
    'remember_cookie' => 'company_site_remember',
    'remember_days' => 14,

    'users_file' => __DIR__ . '/../data/users.json',
    'content_file' => __DIR__ . '/../data/site_content.json',
    'token_file' => __DIR__ . '/../data/remember_tokens.json',

    'gallery_dir' => __DIR__ . '/../uploads/gallery',
    'gallery_web_path' => 'uploads/gallery',

    'max_upload_size' => 4 * 1024 * 1024,
    'allowed_image_types' => [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
    ],
];
