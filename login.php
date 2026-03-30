<?php
require_once __DIR__ . '/includes/init.php';

if (is_logged_in()) {
    $user = current_user();
    if (($user['role'] ?? '') === 'admin') {
        redirect('admin/dashboard.php');
    }
    redirect('client/dashboard.php');
}

$pageTitle = 'Login';
$activeNav = '';
$errorMessage = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!check_csrf_token($_POST['csrf_token'] ?? null)) {
        $errorMessage = 'Invalid request token. Please refresh and try again.';
    } else {
        $username = trim((string) ($_POST['username'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $remember = isset($_POST['remember_me']);

        $user = authenticate_user($username, $password);

        if ($user === null) {
            $errorMessage = 'Invalid username or password.';
        } else {
            login_user($user, $remember);
            set_flash('success', 'Welcome back, ' . ($user['name'] ?? $user['username']) . '.');

            if (($user['role'] ?? '') === 'admin') {
                redirect('admin/dashboard.php');
            }
            redirect('client/dashboard.php');
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<section class="container">
    <div class="section-grid">
        <section class="card">
            <h2>Role Based Login</h2>
            <p class="notice">Admin and Client users can log in from this page.</p>

            <?php if ($errorMessage !== null): ?>
                <div class="flash flash-error"><?php echo h($errorMessage); ?></div>
            <?php endif; ?>

            <form method="post" action="<?php echo h(url('login.php')); ?>">
                <input type="hidden" name="csrf_token" value="<?php echo h(csrf_token()); ?>">
                <div class="form-grid">
                    <div class="form-group form-group-full">
                        <label for="username">Username</label>
                        <input id="username" name="username" type="text" required>
                    </div>
                    <div class="form-group form-group-full">
                        <label for="password">Password</label>
                        <input id="password" name="password" type="password" required>
                    </div>
                </div>

                <label class="mt-1" style="display:inline-flex; align-items:center; gap:0.45rem;">
                    <input type="checkbox" name="remember_me" value="1" style="width:auto;">
                    Keep me logged in (cookie)
                </label>

                <div class="actions mt-1">
                    <button class="btn" type="submit">Login</button>
                </div>
            </form>
        </section>

        <aside class="card card-muted">
            <h3>Demo Credentials</h3>
            <p><strong>Admin:</strong> admin / admin123</p>
            <p><strong>Client:</strong> client / client123</p>
            <p class="mb-0">You can change credentials in <code>data/users.json</code>.</p>
        </aside>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
