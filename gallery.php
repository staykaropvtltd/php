<?php
require_once __DIR__ . '/includes/init.php';

$pageTitle = 'Gallery';
$activeNav = 'gallery';
$images = get_gallery_images();

require_once __DIR__ . '/includes/header.php';
?>

<section class="container">
    <div class="hero">
        <span class="eyebrow">Project Visuals</span>
        <h1>Gallery</h1>
        <p>Recent snapshots from our software and hardware delivery work.</p>
    </div>

    <?php if ($images === []): ?>
        <div class="card mt-1">
            <p class="mb-0">Gallery is currently empty. Admin can upload images from the dashboard.</p>
        </div>
    <?php else: ?>
        <div class="gallery-grid">
            <?php foreach ($images as $image): ?>
                <figure class="gallery-item">
                    <img src="<?php echo h(gallery_image_url($image)); ?>" alt="Gallery Image">
                    <figcaption class="meta"><?php echo h($image); ?></figcaption>
                </figure>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
