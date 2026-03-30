<?php
require_once __DIR__ . '/includes/init.php';

$pageTitle = 'Service';
$activeNav = 'service';
$service = get_page_content($siteContent, 'service');

$serviceLines = preg_split('/\R/', (string) $service['content']) ?: [];
$serviceLines = array_values(array_filter(array_map('trim', $serviceLines)));

require_once __DIR__ . '/includes/header.php';
?>

<section class="container">
    <div class="hero">
        <span class="eyebrow">Our Service Lines</span>
        <h1><?php echo h((string) $service['heading']); ?></h1>
        <p><?php echo h((string) $service['subheading']); ?></p>
    </div>

    <div class="section-grid">
        <article class="card">
            <h2>Service Catalog</h2>
            <?php if ($serviceLines === []): ?>
                <p>Service details will be updated shortly.</p>
            <?php else: ?>
                <ul>
                    <?php foreach ($serviceLines as $line): ?>
                        <li><?php echo h($line); ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </article>
        <aside class="card card-muted">
            <h3>Delivery Note</h3>
            <p><?php echo h((string) $service['highlight']); ?></p>
        </aside>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
