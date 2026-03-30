<?php
require_once __DIR__ . '/../includes/init.php';
require_login('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!check_csrf_token($_POST['csrf_token'] ?? null)) {
        set_flash('error', 'Invalid request token. Please try again.');
        redirect('admin/edit_gallery.php');
    }

    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'upload') {
        [$ok, $message] = upload_gallery_image($_FILES['gallery_image'] ?? []);
        set_flash($ok ? 'success' : 'error', $message);
        redirect('admin/edit_gallery.php');
    }

    if ($action === 'remove') {
        $filename = (string) ($_POST['filename'] ?? '');
        [$ok, $message] = remove_gallery_image($filename);
        set_flash($ok ? 'success' : 'error', $message);
        redirect('admin/edit_gallery.php');
    }

    set_flash('error', 'Unknown action requested.');
    redirect('admin/edit_gallery.php');
}

$pageTitle = 'Edit Gallery';
$activeNav = '';
$images = get_gallery_images();

require_once __DIR__ . '/../includes/header.php';
?>

<section class="container">
    <section class="card">
        <h2>Gallery Manager</h2>
        <p class="notice">Upload new photos or remove existing ones from the live gallery page.</p>

        <form method="post" action="<?php echo h(url('admin/edit_gallery.php')); ?>" enctype="multipart/form-data" class="mt-1">
            <input type="hidden" name="csrf_token" value="<?php echo h(csrf_token()); ?>">
            <input type="hidden" name="action" value="upload">

            <div class="form-grid">
                <div class="form-group form-group-full">
                    <label for="gallery_image">Select Image (JPG, PNG, WEBP, GIF | max 4MB)</label>
                    <input id="gallery_image" name="gallery_image" type="file" accept="image/*" required>
                </div>
            </div>

            <div class="actions">
                <button class="btn" type="submit">Upload Image</button>
                <a class="btn btn-outline" href="<?php echo h(url('admin/dashboard.php')); ?>">Back to Dashboard</a>
            </div>
        </form>
    </section>

    <section class="card mt-1">
        <h3>Current Gallery Images</h3>

        <?php if ($images === []): ?>
            <p class="mb-0">No images available yet.</p>
        <?php else: ?>
            <div class="gallery-grid">
                <?php foreach ($images as $image): ?>
                    <article class="gallery-item">
                        <img src="<?php echo h(gallery_image_url($image)); ?>" alt="Gallery image">
                        <div class="meta">
                            <p style="margin-top:0; margin-bottom:0.55rem;"><?php echo h($image); ?></p>
                            <form method="post" action="<?php echo h(url('admin/edit_gallery.php')); ?>">
                                <input type="hidden" name="csrf_token" value="<?php echo h(csrf_token()); ?>">
                                <input type="hidden" name="action" value="remove">
                                <input type="hidden" name="filename" value="<?php echo h($image); ?>">
                                <button class="btn btn-outline btn-small" type="submit">Remove</button>
                            </form>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
