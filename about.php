<?php
require_once __DIR__ . '/includes/init.php';

$pageTitle = 'About';
$activeNav = 'about';
$about = get_page_content($siteContent, 'about');

require_once __DIR__ . '/includes/header.php';
?>

<section class="container">
    <div class="hero">
        <span class="eyebrow">About Our Company</span>
        <h1><?php echo h((string) $about['heading']); ?></h1>
        <p><?php echo h((string) $about['subheading']); ?></p>
    </div>

    <div class="section-grid">
        <article class="card">
            <h2>Who We Are</h2>
            <p><?php echo nl2br_safe((string) $about['content']); ?></p>
        </article>
        <aside class="card card-muted">
            <h3>Working Style</h3>
            <p><?php echo h((string) $about['highlight']); ?></p>
        </aside>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
