<?php
require_once __DIR__ . '/../includes/init.php';
require_login('client');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!check_csrf_token($_POST['csrf_token'] ?? null)) {
        set_flash('error', 'Invalid request token. Please submit again.');
    } else {
        set_flash('success', 'Service request placeholder submitted. Final fields can be updated later.');
    }

    redirect('client/request_service.php');
}

$pageTitle = 'Request Service';
$activeNav = '';

require_once __DIR__ . '/../includes/header.php';
?>

<section class="container">
    <section class="card">
        <h2>Request Services</h2>
        <p class="notice">Temporary placeholder form for software/hardware requests. Final fields can be replaced later.</p>

        <form method="post" action="<?php echo h(url('client/request_service.php')); ?>" class="mt-1">
            <input type="hidden" name="csrf_token" value="<?php echo h(csrf_token()); ?>">

            <div class="form-grid">
                <div class="form-group">
                    <label for="service_type">Service Type</label>
                    <select id="service_type" name="service_type" required>
                        <option value="">Select</option>
                        <option value="software">Software</option>
                        <option value="hardware">Hardware</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="priority">Priority</label>
                    <select id="priority" name="priority" required>
                        <option value="normal">Normal</option>
                        <option value="urgent">Urgent</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="project_name">Project / Requirement Name</label>
                    <input id="project_name" name="project_name" type="text" required>
                </div>

                <div class="form-group">
                    <label for="expected_date">Expected Delivery Date</label>
                    <input id="expected_date" name="expected_date" type="date">
                </div>

                <div class="form-group form-group-full">
                    <label for="details">Requirement Details</label>
                    <textarea id="details" name="details" placeholder="Placeholder text. Replace with final requirement fields later."></textarea>
                </div>
            </div>

            <div class="actions mt-1">
                <button class="btn" type="submit">Submit Request</button>
                <a class="btn btn-outline" href="<?php echo h(url('client/dashboard.php')); ?>">Back to Dashboard</a>
            </div>
        </form>
    </section>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
