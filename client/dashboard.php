<?php
require_once __DIR__ . '/../includes/init.php';
require_login('client');

$pageTitle = 'Client Dashboard';
$activeNav = '';

require_once __DIR__ . '/../includes/header.php';
?>

<section class="container">
    <div class="hero">
        <span class="eyebrow">Client Portal</span>
        <h1>Client Dashboard</h1>
        <p>Request software/hardware services and track your coordination process with our team.</p>
    </div>

    <div class="grid-tiles">
        <article class="tile">
            <h3>Request New Service</h3>
            <p>Create a new service request for software or hardware requirements.</p>
            <a class="btn" href="<?php echo h(url('client/request_service.php')); ?>">Open Request Form</a>
        </article>
        <article class="tile">
            <h3>Support Note</h3>
            <p>This is a starter client dashboard. Workflow details can be expanded later.</p>
            <a class="btn btn-outline" href="<?php echo h(url('contact.php')); ?>">Contact Support</a>
        </article>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
