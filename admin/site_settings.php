<?php
require_once __DIR__ . '/../includes/init.php';
require_login('admin');

$errorMessage = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!check_csrf_token($_POST['csrf_token'] ?? null)) {
        $errorMessage = 'Invalid request token. Please refresh and try again.';
    } else {
        $payload = [
            'site_title' => trim((string) ($_POST['site_title'] ?? '')),
            'primary_color' => trim((string) ($_POST['primary_color'] ?? '')),
            'secondary_color' => trim((string) ($_POST['secondary_color'] ?? '')),
            'accent_color' => trim((string) ($_POST['accent_color'] ?? '')),
        ];

        if (update_site_settings($payload)) {
            set_flash('success', 'Website title and colors updated successfully.');
            redirect('admin/site_settings.php');
        }

        $errorMessage = 'Unable to update settings right now.';
    }
}

$siteContent = load_site_content();
$settings = get_site_settings($siteContent);

$pageTitle = 'Site Settings';
$activeNav = '';

require_once __DIR__ . '/../includes/header.php';
?>

<section class="container">
    <section class="card">
        <h2>Site Title & Color Theme</h2>
        <p class="notice">Set values based on your reference image so the whole website updates instantly.</p>

        <?php if ($errorMessage !== null): ?>
            <div class="flash flash-error mt-1"><?php echo h($errorMessage); ?></div>
        <?php endif; ?>

        <form method="post" action="<?php echo h(url('admin/site_settings.php')); ?>" class="mt-1">
            <input type="hidden" name="csrf_token" value="<?php echo h(csrf_token()); ?>">
            <div class="form-grid">
                <div class="form-group form-group-full">
                    <label for="site_title">Website Title</label>
                    <input id="site_title" name="site_title" type="text" value="<?php echo h((string) $settings['site_title']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="primary_color">Primary Color</label>
                    <input id="primary_color" name="primary_color" type="text" value="<?php echo h((string) $settings['primary_color']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="secondary_color">Secondary Color</label>
                    <input id="secondary_color" name="secondary_color" type="text" value="<?php echo h((string) $settings['secondary_color']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="accent_color">Accent Color</label>
                    <input id="accent_color" name="accent_color" type="text" value="<?php echo h((string) $settings['accent_color']); ?>" required>
                </div>
            </div>

            <div class="actions mt-1">
                <button class="btn" type="submit">Save Theme</button>
                <a class="btn btn-outline" href="<?php echo h(url('admin/dashboard.php')); ?>">Back to Dashboard</a>
            </div>
        </form>
    </section>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
