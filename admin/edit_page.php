<?php
require_once __DIR__ . '/../includes/init.php';
require_login('admin');

$pageMap = editable_pages();
$pageKey = (string) ($_GET['page'] ?? 'home');
if (!isset($pageMap[$pageKey])) {
    $pageKey = 'home';
}

$errorMessage = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!check_csrf_token($_POST['csrf_token'] ?? null)) {
        $errorMessage = 'Invalid request token. Please try again.';
    } else {
        $updated = [
            'heading' => trim((string) ($_POST['heading'] ?? '')),
            'subheading' => trim((string) ($_POST['subheading'] ?? '')),
            'content' => trim((string) ($_POST['content'] ?? '')),
            'highlight' => trim((string) ($_POST['highlight'] ?? '')),
        ];

        if (update_page_content($pageKey, $updated)) {
            set_flash('success', $pageMap[$pageKey] . ' page updated successfully.');
            redirect('admin/edit_page.php?page=' . rawurlencode($pageKey));
        }

        $errorMessage = 'Unable to update this page right now.';
    }
}

$siteContent = load_site_content();
$pageData = get_page_content($siteContent, $pageKey);

$pageTitle = 'Edit ' . $pageMap[$pageKey];
$activeNav = '';

require_once __DIR__ . '/../includes/header.php';
?>

<section class="container">
    <section class="card">
        <h2>Edit <?php echo h($pageMap[$pageKey]); ?> Page</h2>
        <p class="notice">Use the selector to switch between Home, About, and Service pages.</p>

        <div class="actions mt-1">
            <?php foreach ($pageMap as $key => $label): ?>
                <a class="btn <?php echo $key === $pageKey ? '' : 'btn-outline'; ?>" href="<?php echo h(url('admin/edit_page.php?page=' . rawurlencode($key))); ?>">
                    <?php echo h($label); ?>
                </a>
            <?php endforeach; ?>
            <a class="btn btn-outline" href="<?php echo h(url('admin/dashboard.php')); ?>">Back to Dashboard</a>
        </div>

        <?php if ($errorMessage !== null): ?>
            <div class="flash flash-error mt-1"><?php echo h($errorMessage); ?></div>
        <?php endif; ?>

        <form method="post" action="<?php echo h(url('admin/edit_page.php?page=' . rawurlencode($pageKey))); ?>" class="mt-1">
            <input type="hidden" name="csrf_token" value="<?php echo h(csrf_token()); ?>">

            <div class="form-grid">
                <div class="form-group form-group-full">
                    <label for="heading">Heading</label>
                    <input id="heading" name="heading" type="text" value="<?php echo h((string) $pageData['heading']); ?>" required>
                </div>

                <div class="form-group form-group-full">
                    <label for="subheading">Subheading</label>
                    <input id="subheading" name="subheading" type="text" value="<?php echo h((string) $pageData['subheading']); ?>" required>
                </div>

                <div class="form-group form-group-full">
                    <label for="content">Main Content</label>
                    <textarea id="content" name="content" required><?php echo h((string) $pageData['content']); ?></textarea>
                </div>

                <div class="form-group form-group-full">
                    <label for="highlight">Highlight Box Content</label>
                    <textarea id="highlight" name="highlight" required><?php echo h((string) $pageData['highlight']); ?></textarea>
                </div>
            </div>

            <div class="actions mt-1">
                <button class="btn" type="submit">Save Changes</button>
                <a class="btn btn-outline" href="<?php echo h(url($pageKey . '.php')); ?>" target="_blank" rel="noreferrer">Preview Page</a>
            </div>
        </form>
    </section>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
