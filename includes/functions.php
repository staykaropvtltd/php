<?php

function get_config(): array
{
    return $GLOBALS['app_config'] ?? [];
}

function config_value(string $key, $default = null)
{
    $config = get_config();
    return $config[$key] ?? $default;
}

function start_app_session(): void
{
    if (session_status() !== PHP_SESSION_NONE) {
        return;
    }

    session_name((string) config_value('session_name', 'company_site_session'));
    session_start();
}

function ensure_directory(string $path): void
{
    if (!is_dir($path)) {
        mkdir($path, 0755, true);
    }
}

function ensure_json_file(string $filePath, $defaultData): void
{
    if (is_file($filePath)) {
        return;
    }

    ensure_directory(dirname($filePath));
    file_put_contents(
        $filePath,
        json_encode($defaultData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
        LOCK_EX
    );
}

function read_json_file(string $filePath, $fallback)
{
    if (!is_file($filePath)) {
        return $fallback;
    }

    $contents = file_get_contents($filePath);
    if ($contents === false || trim($contents) === '') {
        return $fallback;
    }

    $decoded = json_decode($contents, true);
    return is_array($decoded) ? $decoded : $fallback;
}

function write_json_file(string $filePath, $data): bool
{
    $encoded = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    if ($encoded === false) {
        return false;
    }

    return file_put_contents($filePath, $encoded, LOCK_EX) !== false;
}

function bootstrap_storage(): void
{
    ensure_json_file((string) config_value('content_file'), default_site_content());
    ensure_json_file((string) config_value('users_file'), default_users());
    ensure_json_file((string) config_value('token_file'), []);
    ensure_directory((string) config_value('gallery_dir'));
}

function default_users(): array
{
    return [
        [
            'username' => 'admin',
            'password' => '$2y$12$AQUJGrPLukGOLS1MA.qJrOTD1J4kYmFB7thfR/wLt/Kb9OnkmNN7O',
            'role' => 'admin',
            'name' => 'Admin User',
        ],
        [
            'username' => 'client',
            'password' => '$2y$12$Sfu7mF3J8v9ggiL9rgm1L.rjGtQEdl3T.WFyLByu6eYvRSEYwd3YO',
            'role' => 'client',
            'name' => 'Client User',
        ],
    ];
}

function default_site_content(): array
{
    return [
        'settings' => [
            'site_title' => 'AIT NEXT GEN PRO & TALENT SOLUTIONS PRIVATE LIMITED',
            'primary_color' => '#1D2D8C',
            'secondary_color' => '#5A39C7',
            'accent_color' => '#2A63D1',
        ],
        'pages' => [
            'home' => [
                'heading' => 'Software, Hardware and Talent Solutions',
                'subheading' => 'End-to-end delivery for business technology and staffing needs.',
                'content' => "We provide practical software engineering, dependable hardware supply, and deployment support tailored to your timelines and budgets.",
                'highlight' => 'One team for planning, implementation, and post-deployment support.',
            ],
            'about' => [
                'heading' => 'About AIT Next Gen Pro',
                'subheading' => 'Focused on quality delivery with transparent execution.',
                'content' => "AIT Next Gen Pro & Talent Solutions Private Limited supports clients with software development, IT infrastructure, and professional talent requirements.",
                'highlight' => 'Business-first solutions with measurable results and long-term support.',
            ],
            'service' => [
                'heading' => 'Our Services',
                'subheading' => 'Software, hardware, manpower, and operational support.',
                'content' => "Custom Software Development\nHardware Supply and Installation\nIT AMC and Support Services\nCloud and Infrastructure Consulting\nTalent Staffing and Deployment",
                'highlight' => 'Single-point ownership from requirement to delivery.',
            ],
        ],
    ];
}

function load_site_content(): array
{
    $default = default_site_content();
    $content = read_json_file((string) config_value('content_file'), $default);

    if (!isset($content['settings']) || !is_array($content['settings'])) {
        $content['settings'] = $default['settings'];
    }
    if (!isset($content['pages']) || !is_array($content['pages'])) {
        $content['pages'] = $default['pages'];
    }

    $content['settings'] = array_merge($default['settings'], $content['settings']);

    foreach ($default['pages'] as $pageKey => $fields) {
        $current = $content['pages'][$pageKey] ?? [];
        if (!is_array($current)) {
            $current = [];
        }
        $content['pages'][$pageKey] = array_merge($fields, $current);
    }

    return $content;
}

function save_site_content(array $content): bool
{
    return write_json_file((string) config_value('content_file'), $content);
}

function get_site_settings(array $content): array
{
    return $content['settings'] ?? default_site_content()['settings'];
}

function editable_pages(): array
{
    return [
        'home' => 'Home',
        'about' => 'About',
        'service' => 'Service',
    ];
}

function get_page_content(array $content, string $pageKey): array
{
    $defaults = default_site_content()['pages'][$pageKey] ?? [
        'heading' => '',
        'subheading' => '',
        'content' => '',
        'highlight' => '',
    ];

    $page = $content['pages'][$pageKey] ?? [];
    if (!is_array($page)) {
        $page = [];
    }

    return array_merge($defaults, $page);
}

function update_page_content(string $pageKey, array $updatedFields): bool
{
    $allowed = editable_pages();
    if (!isset($allowed[$pageKey])) {
        return false;
    }

    $content = load_site_content();
    $existing = get_page_content($content, $pageKey);
    $content['pages'][$pageKey] = array_merge($existing, $updatedFields);

    return save_site_content($content);
}

function normalize_hex_color(string $color, string $fallback): string
{
    return preg_match('/^#[0-9a-fA-F]{6}$/', $color) ? strtoupper($color) : $fallback;
}

function update_site_settings(array $settings): bool
{
    $content = load_site_content();
    $current = get_site_settings($content);

    $updated = [
        'site_title' => trim((string) ($settings['site_title'] ?? $current['site_title'])),
        'primary_color' => normalize_hex_color((string) ($settings['primary_color'] ?? ''), $current['primary_color']),
        'secondary_color' => normalize_hex_color((string) ($settings['secondary_color'] ?? ''), $current['secondary_color']),
        'accent_color' => normalize_hex_color((string) ($settings['accent_color'] ?? ''), $current['accent_color']),
    ];

    if ($updated['site_title'] === '') {
        $updated['site_title'] = $current['site_title'];
    }

    $content['settings'] = $updated;
    return save_site_content($content);
}

function load_users(): array
{
    $users = read_json_file((string) config_value('users_file'), []);
    return is_array($users) ? $users : [];
}

function find_user(string $username): ?array
{
    $needle = strtolower(trim($username));
    if ($needle === '') {
        return null;
    }

    foreach (load_users() as $user) {
        if (!is_array($user)) {
            continue;
        }

        $candidate = strtolower((string) ($user['username'] ?? ''));
        if ($candidate === $needle) {
            return $user;
        }
    }

    return null;
}

function authenticate_user(string $username, string $password): ?array
{
    $user = find_user($username);
    if ($user === null) {
        return null;
    }

    $hash = (string) ($user['password'] ?? '');
    if ($hash === '' || !password_verify($password, $hash)) {
        return null;
    }

    return [
        'username' => (string) ($user['username'] ?? ''),
        'role' => (string) ($user['role'] ?? 'client'),
        'name' => (string) ($user['name'] ?? $user['username'] ?? 'User'),
    ];
}

function cookie_path(): string
{
    $basePath = trim((string) config_value('base_path', ''));
    if ($basePath === '') {
        return '/';
    }

    return '/' . trim($basePath, '/') . '/';
}

function persist_remember_token(string $username): void
{
    $cookieName = (string) config_value('remember_cookie', 'remember_token');
    $days = max(1, (int) config_value('remember_days', 14));

    $expiresAt = time() + ($days * 86400);
    $rawToken = bin2hex(random_bytes(32));
    $tokenHash = hash('sha256', $rawToken);

    $tokens = read_json_file((string) config_value('token_file'), []);
    $filtered = [];
    $now = time();

    foreach ($tokens as $token) {
        if (!is_array($token)) {
            continue;
        }

        if ((int) ($token['expires_at'] ?? 0) <= $now) {
            continue;
        }

        $filtered[] = $token;
    }

    $filtered[] = [
        'token_hash' => $tokenHash,
        'username' => $username,
        'expires_at' => $expiresAt,
    ];

    write_json_file((string) config_value('token_file'), $filtered);

    setcookie($cookieName, $rawToken, [
        'expires' => $expiresAt,
        'path' => cookie_path(),
        'httponly' => true,
        'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'samesite' => 'Lax',
    ]);
}

function remove_token_from_store(?string $rawToken): void
{
    if ($rawToken === null || $rawToken === '') {
        return;
    }

    $tokenHash = hash('sha256', $rawToken);
    $tokens = read_json_file((string) config_value('token_file'), []);
    $now = time();
    $filtered = [];

    foreach ($tokens as $token) {
        if (!is_array($token)) {
            continue;
        }

        if ((int) ($token['expires_at'] ?? 0) <= $now) {
            continue;
        }

        $storedHash = (string) ($token['token_hash'] ?? '');
        if ($storedHash !== '' && hash_equals($storedHash, $tokenHash)) {
            continue;
        }

        $filtered[] = $token;
    }

    write_json_file((string) config_value('token_file'), $filtered);
}

function clear_remember_cookie(): void
{
    $cookieName = (string) config_value('remember_cookie', 'remember_token');
    setcookie($cookieName, '', [
        'expires' => time() - 3600,
        'path' => cookie_path(),
        'httponly' => true,
        'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'samesite' => 'Lax',
    ]);

    unset($_COOKIE[$cookieName]);
}

function login_user(array $user, bool $remember = false): void
{
    $_SESSION['user'] = [
        'username' => (string) ($user['username'] ?? ''),
        'role' => (string) ($user['role'] ?? 'client'),
        'name' => (string) ($user['name'] ?? 'User'),
    ];

    if ($remember) {
        persist_remember_token((string) ($user['username'] ?? ''));
    }
}

function auto_login_from_cookie(): void
{
    if (is_logged_in()) {
        return;
    }

    $cookieName = (string) config_value('remember_cookie', 'remember_token');
    $rawToken = (string) ($_COOKIE[$cookieName] ?? '');
    if ($rawToken === '') {
        return;
    }

    $tokens = read_json_file((string) config_value('token_file'), []);
    $tokenHash = hash('sha256', $rawToken);
    $now = time();

    $filtered = [];
    $matchedUser = null;

    foreach ($tokens as $token) {
        if (!is_array($token)) {
            continue;
        }

        $expiresAt = (int) ($token['expires_at'] ?? 0);
        if ($expiresAt <= $now) {
            continue;
        }

        $filtered[] = $token;

        $storedHash = (string) ($token['token_hash'] ?? '');
        if ($storedHash !== '' && hash_equals($storedHash, $tokenHash)) {
            $matchedUser = (string) ($token['username'] ?? '');
        }
    }

    if ($filtered !== $tokens) {
        write_json_file((string) config_value('token_file'), $filtered);
    }

    if ($matchedUser === null || $matchedUser === '') {
        clear_remember_cookie();
        return;
    }

    $user = find_user($matchedUser);
    if ($user === null) {
        clear_remember_cookie();
        return;
    }

    $_SESSION['user'] = [
        'username' => (string) ($user['username'] ?? ''),
        'role' => (string) ($user['role'] ?? 'client'),
        'name' => (string) ($user['name'] ?? $user['username'] ?? 'User'),
    ];
}

function logout_user(): void
{
    $cookieName = (string) config_value('remember_cookie', 'remember_token');
    $rawToken = (string) ($_COOKIE[$cookieName] ?? '');

    if ($rawToken !== '') {
        remove_token_from_store($rawToken);
    }

    clear_remember_cookie();

    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'] ?? '', (bool) $params['secure'], (bool) $params['httponly']);
    }

    session_destroy();
}

function current_user(): ?array
{
    if (!isset($_SESSION['user']) || !is_array($_SESSION['user'])) {
        return null;
    }

    return $_SESSION['user'];
}

function is_logged_in(): bool
{
    return current_user() !== null;
}

function is_admin(): bool
{
    $user = current_user();
    return ($user['role'] ?? '') === 'admin';
}

function is_client(): bool
{
    $user = current_user();
    return ($user['role'] ?? '') === 'client';
}

function redirect(string $path): void
{
    header('Location: ' . url($path));
    exit;
}

function require_login(?string $role = null): void
{
    $user = current_user();
    if ($user === null) {
        set_flash('error', 'Please log in to continue.');
        redirect('login.php');
    }

    if ($role !== null && ($user['role'] ?? '') !== $role) {
        set_flash('error', 'You do not have permission to access that page.');
        if (($user['role'] ?? '') === 'admin') {
            redirect('admin/dashboard.php');
        }
        redirect('client/dashboard.php');
    }
}

function set_flash(string $type, string $message): void
{
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message,
    ];
}

function get_flash(): ?array
{
    if (!isset($_SESSION['flash']) || !is_array($_SESSION['flash'])) {
        return null;
    }

    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);

    return $flash;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return (string) $_SESSION['csrf_token'];
}

function check_csrf_token(?string $token): bool
{
    $sessionToken = (string) ($_SESSION['csrf_token'] ?? '');
    if ($sessionToken === '' || $token === null || $token === '') {
        return false;
    }

    return hash_equals($sessionToken, $token);
}

function url(string $path = ''): string
{
    $basePath = trim((string) config_value('base_path', ''));
    $basePath = $basePath === '' ? '' : '/' . trim($basePath, '/');

    if ($path === '') {
        return $basePath === '' ? '/' : $basePath . '/';
    }

    return ($basePath === '' ? '/' : $basePath . '/') . ltrim($path, '/');
}

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function nl2br_safe(string $value): string
{
    return nl2br(h($value));
}

function get_gallery_images(): array
{
    $galleryDir = (string) config_value('gallery_dir');
    $patterns = ['*.jpg', '*.jpeg', '*.png', '*.gif', '*.webp'];

    $files = [];
    foreach ($patterns as $pattern) {
        $matches = glob($galleryDir . DIRECTORY_SEPARATOR . $pattern);
        if ($matches !== false) {
            $files = array_merge($files, $matches);
        }
    }

    usort($files, static function (string $a, string $b): int {
        return filemtime($b) <=> filemtime($a);
    });

    return array_map('basename', $files);
}

function gallery_image_url(string $filename): string
{
    $webPath = trim((string) config_value('gallery_web_path', 'uploads/gallery'), '/');
    return url($webPath . '/' . rawurlencode($filename));
}

function upload_gallery_image(array $file): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return [false, 'Please choose an image to upload.'];
    }

    $maxSize = (int) config_value('max_upload_size', 4 * 1024 * 1024);
    if ((int) ($file['size'] ?? 0) > $maxSize) {
        return [false, 'Image is too large. Maximum allowed size is 4MB.'];
    }

    $tmpName = (string) ($file['tmp_name'] ?? '');
    if ($tmpName === '' || !is_uploaded_file($tmpName)) {
        return [false, 'Invalid upload source.'];
    }

    $mimeType = (string) mime_content_type($tmpName);
    $allowed = (array) config_value('allowed_image_types', []);

    if (!isset($allowed[$mimeType])) {
        return [false, 'Unsupported image type. Use JPG, PNG, WEBP, or GIF.'];
    }

    $extension = $allowed[$mimeType];
    $filename = 'gallery_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
    $targetPath = rtrim((string) config_value('gallery_dir'), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;

    if (!move_uploaded_file($tmpName, $targetPath)) {
        return [false, 'Upload failed. Please try again.'];
    }

    return [true, 'Image uploaded successfully.'];
}

function remove_gallery_image(string $filename): array
{
    $safeName = basename($filename);
    if ($safeName === '') {
        return [false, 'Invalid image selection.'];
    }

    $galleryDir = realpath((string) config_value('gallery_dir'));
    if ($galleryDir === false) {
        return [false, 'Gallery directory not found.'];
    }

    $target = realpath($galleryDir . DIRECTORY_SEPARATOR . $safeName);
    if ($target === false || strpos($target, $galleryDir) !== 0 || !is_file($target)) {
        return [false, 'Image file not found.'];
    }

    if (!unlink($target)) {
        return [false, 'Unable to remove image.'];
    }

    return [true, 'Image removed successfully.'];
}
