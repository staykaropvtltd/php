<?php
require_once __DIR__ . '/../includes/init.php';
require_login('admin');

$pageTitle = 'Admin Dashboard';
$activeNav = '';

require_once __DIR__ . '/../includes/header.php';
?>

<section class="container">
    <div class="hero">
        <span class="eyebrow">Admin Panel</span>
        <h1>Website Management Dashboard</h1>
        <p>Edit site sections, update gallery, and control design settings from one place.</p>
    </div>

    <div class="grid-tiles">
        <article class="tile">
            <h3>Edit Home</h3>
            <p>Update heading, intro text, and highlights on the Home page.</p>
            <a class="btn" href="<?php echo h(url('admin/edit_page.php?page=home')); ?>">Edit Home</a>
        </article>
        <article class="tile">
            <h3>Edit About</h3>
            <p>Manage company description and About page messaging.</p>
            <a class="btn" href="<?php echo h(url('admin/edit_page.php?page=about')); ?>">Edit About</a>
        </article>
        <article class="tile">
            <h3>Edit Service</h3>
            <p>Update service list and related content shown to visitors.</p>
            <a class="btn" href="<?php echo h(url('admin/edit_page.php?page=service')); ?>">Edit Service</a>
        </article>
        <article class="tile">
            <h3>Edit Gallery</h3>
            <p>Add or remove photos displayed in the public gallery.</p>
            <a class="btn" href="<?php echo h(url('admin/edit_gallery.php')); ?>">Edit Gallery</a>
        </article>
        <article class="tile">
            <h3>Site Title & Colors</h3>
            <p>Set brand title and theme colors used across the site.</p>
            <a class="btn" href="<?php echo h(url('admin/site_settings.php')); ?>">Edit Theme</a>
        </article>
        <article class="tile">
            <h3>View Live Site</h3>
            <p>Open the public website to review your latest changes.</p>
            <a class="btn btn-outline" href="<?php echo h(url('index.php')); ?>">Open Website</a>
        </article>
    </div>

    <p class="notice mt-1">Contact Us page is intentionally excluded from admin content editing, as requested.</p>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
