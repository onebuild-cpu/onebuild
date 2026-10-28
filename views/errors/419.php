<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<?php require_once __DIR__ . '/../layouts/navbar.php'; ?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6 text-center">
            <h1 class="display-1 text-warning fw-bold">419</h1>
            <h2 class="mb-4">Page Expired</h2>
            <p class="text-muted mb-4">
                Your session has expired or the security token is invalid. 
                This is a security measure to protect your data.
            </p>
            <a href="<?= $base ?>" class="btn btn-primary">
                <i class="bi bi-house-door me-2"></i>Go to Homepage
            </a>
            <button onclick="window.history.back()" class="btn btn-outline-secondary ms-2">
                <i class="bi bi-arrow-left me-2"></i>Go Back
            </button>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>