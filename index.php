<?php
require_once __DIR__ . '/includes/init.php';

$pageTitle = 'Home';
$activeNav = 'home';
$home = get_page_content($siteContent, 'home');

require_once __DIR__ . '/includes/header.php';
?>

<section class="container">
    <div class="hero">
        <span class="eyebrow">Technology Delivery Partner</span>
        <h1><?php echo h((string) $home['heading']); ?></h1>
        <p><?php echo h((string) $home['subheading']); ?></p>
        <div class="actions mt-1">
            <a class="btn" href="<?php echo h(url('service.php')); ?>">Explore Services</a>
            <a class="btn btn-outline" href="<?php echo h(url('contact.php')); ?>">Talk to Us</a>
        </div>
    </div>

    <div class="section-grid">
        <article class="card">
            <h2>What We Deliver</h2>
            <p><?php echo nl2br_safe((string) $home['content']); ?></p>
        </article>
        <aside class="card card-muted">
            <h3>Why Clients Choose Us</h3>
            <p class="mb-0"><?php echo h((string) $home['highlight']); ?></p>
        </aside>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

