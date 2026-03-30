<?php
require_once __DIR__ . '/includes/init.php';

$pageTitle = 'Contact Us';
$activeNav = 'contact';
$message = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!check_csrf_token($_POST['csrf_token'] ?? null)) {
        $message = ['type' => 'error', 'text' => 'Invalid request token. Please submit the form again.'];
    } else {
        $message = ['type' => 'success', 'text' => 'Thanks for contacting us. We will reach out shortly.'];
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<section class="container">
    <div class="hero">
        <span class="eyebrow">Contact Us</span>
        <h1>Let us discuss your requirement</h1>
        <p>Share your needs and our team will respond with the next steps.</p>
    </div>

    <section class="card mt-1">
        <h2>Contact Form</h2>

        <?php if ($message !== null): ?>
            <div class="flash flash-<?php echo h($message['type']); ?>">
                <?php echo h($message['text']); ?>
            </div>
        <?php endif; ?>

        <form method="post" action="<?php echo h(url('contact.php')); ?>">
            <input type="hidden" name="csrf_token" value="<?php echo h(csrf_token()); ?>">
            <div class="form-grid">
                <div class="form-group">
                    <label for="name">Name</label>
                    <input id="name" name="name" type="text" required>
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input id="email" name="email" type="email" required>
                </div>
                <div class="form-group form-group-full">
                    <label for="subject">Subject</label>
                    <input id="subject" name="subject" type="text" required>
                </div>
                <div class="form-group form-group-full">
                    <label for="message">Message</label>
                    <textarea id="message" name="message" required></textarea>
                </div>
            </div>
            <div class="actions">
                <button class="btn" type="submit">Send Message</button>
            </div>
        </form>
    </section>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
