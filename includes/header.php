<?php
$siteSettings = $siteSettings ?? get_site_settings(load_site_content());
$siteTitle = (string) ($siteSettings['site_title'] ?? 'Company Website');
$documentTitle = isset($pageTitle) && trim((string) $pageTitle) !== ''
    ? trim((string) $pageTitle) . ' | ' . $siteTitle
    : $siteTitle;

$activeNav = $activeNav ?? '';
$flash = get_flash();
$user = current_user();
$brandLabel = str_ireplace('PRIVATE LIMITED', 'PVT.LTD', $siteTitle);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo h($documentTitle); ?></title>
    <link rel="stylesheet" href="<?php echo h(url('assets/css/style.css')); ?>">
    <style>
        :root {
            --primary-color: <?php echo h((string) $siteSettings['primary_color']); ?>;
            --secondary-color: <?php echo h((string) $siteSettings['secondary_color']); ?>;
            --accent-color: <?php echo h((string) $siteSettings['accent_color']); ?>;
        }
    </style>
</head>
<body>
<header class="site-header">
    <div class="header-nav-wrap">
        <div class="nav-shell">
            <a class="brand" href="<?php echo h(url('index.php')); ?>" title="<?php echo h($brandLabel); ?>"><?php echo h($brandLabel); ?></a>

            <div class="header-right">
                <nav class="site-nav" aria-label="Primary Navigation">
                    <a class="<?php echo $activeNav === 'home' ? 'active' : ''; ?>" href="<?php echo h(url('index.php')); ?>">Home</a>
                    <a class="<?php echo $activeNav === 'about' ? 'active' : ''; ?>" href="<?php echo h(url('about.php')); ?>">About</a>
                    <a class="<?php echo $activeNav === 'service' ? 'active' : ''; ?>" href="<?php echo h(url('service.php')); ?>">Service</a>
                    <a class="<?php echo $activeNav === 'gallery' ? 'active' : ''; ?>" href="<?php echo h(url('gallery.php')); ?>">Gallery</a>
                    <a class="<?php echo $activeNav === 'contact' ? 'active' : ''; ?>" href="<?php echo h(url('contact.php')); ?>">Contact Us</a>
                </nav>

                <div class="nav-actions">
                    <?php if ($user === null): ?>
                        <a class="btn btn-small" href="<?php echo h(url('login.php')); ?>">Login</a>
                    <?php else: ?>
                        <a class="btn btn-small" href="<?php echo h(url(($user['role'] ?? '') === 'admin' ? 'admin/dashboard.php' : 'client/dashboard.php')); ?>">Dashboard</a>
                        <a class="btn btn-outline btn-small" href="<?php echo h(url('logout.php')); ?>">Logout</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</header>

<main class="page-main">
    <?php if ($flash !== null): ?>
        <div class="container">
            <div class="flash flash-<?php echo h((string) ($flash['type'] ?? 'info')); ?>">
                <?php echo h((string) ($flash['message'] ?? '')); ?>
            </div>
        </div>
    <?php endif; ?>
