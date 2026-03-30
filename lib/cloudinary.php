<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';

/**
 * Upload an image file to Cloudinary using the REST API.
 *
 * @param array $file One element from $_FILES (e.g. $_FILES['image'])
 * @param string|null $folder Cloudinary folder, defaults to "ait/gallery"
 *
 * @return array Decoded Cloudinary response (subset of fields).
 *
 * @throws RuntimeException on validation or upload error.
 */
function cloudinary_upload_image(array $file, ?string $folder = null): array
{
    if (!isset($file['error'], $file['tmp_name'], $file['name'], $file['type'], $file['size'])) {
        throw new RuntimeException('Invalid upload payload.');
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('File upload error.');
    }

    if (!is_string($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        throw new RuntimeException('Invalid uploaded file.');
    }

    $maxBytes = (int) (env('GALLERY_MAX_BYTES', (string) (5 * 1024 * 1024)) ?? (5 * 1024 * 1024));
    if ($file['size'] <= 0 || $file['size'] > $maxBytes) {
        throw new RuntimeException('Image size is out of allowed range.');
    }

    $mime = (string) $file['type'];
    if (strpos($mime, 'image/') !== 0) {
        throw new RuntimeException('Only image uploads are allowed.');
    }

    $cloudName = env('CLOUDINARY_CLOUD_NAME');
    $apiKey = env('CLOUDINARY_API_KEY');
    $apiSecret = env('CLOUDINARY_API_SECRET');

    if (!$cloudName || !$apiKey || !$apiSecret) {
        throw new RuntimeException('Cloudinary is not configured.');
    }

    $folder = $folder ?? 'ait/gallery';

    $timestamp = time();
    $paramsToSign = [
        'folder'    => $folder,
        'timestamp' => $timestamp,
    ];

    ksort($paramsToSign);
    $toSignParts = [];
    foreach ($paramsToSign as $key => $value) {
        $toSignParts[] = $key . '=' . $value;
    }
    $toSign = implode('&', $toSignParts);
    $signature = sha1($toSign . $apiSecret);

    $url = sprintf('https://api.cloudinary.com/v1_1/%s/image/upload', $cloudName);

    $postFields = [
        'file'      => curl_file_create($file['tmp_name'], $mime, $file['name']),
        'api_key'   => $apiKey,
        'timestamp' => $timestamp,
        'folder'    => $folder,
        'signature' => $signature,
    ];

    $ch = curl_init($url);
    if ($ch === false) {
        throw new RuntimeException('Failed to initialize upload.');
    }

    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_POSTFIELDS     => $postFields,
    ]);

    $responseBody = curl_exec($ch);
    if ($responseBody === false) {
        $error = curl_error($ch);
        curl_close($ch);
        throw new RuntimeException('Upload request failed.');
    }

    $statusCode = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);

    $decoded = json_decode($responseBody, true);
    if (!is_array($decoded)) {
        throw new RuntimeException('Invalid response from Cloudinary.');
    }

    if ($statusCode < 200 || $statusCode >= 300) {
        throw new RuntimeException('Cloudinary upload error.');
    }

    if (empty($decoded['secure_url']) || empty($decoded['public_id'])) {
        throw new RuntimeException('Cloudinary response missing required fields.');
    }

    return [
        'public_id'  => $decoded['public_id'],
        'secure_url' => $decoded['secure_url'],
        'format'     => $decoded['format'] ?? null,
        'bytes'      => $decoded['bytes'] ?? null,
        'width'      => $decoded['width'] ?? null,
        'height'     => $decoded['height'] ?? null,
    ];
}

