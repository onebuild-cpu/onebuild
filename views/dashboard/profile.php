<?php
$base = '';
if (isset($GLOBALS['base'])) {
    $base = $GLOBALS['base'];
}
$nonce = '';
if (isset($GLOBALS['csp_nonce'])) {
    $nonce = $GLOBALS['csp_nonce'];
}
// Set a default page title only if the controller did not provide one
if (!isset($pageTitle)) {
    $pageTitle = 'OneBuild PHP MVC Framework';
}

// Set a default meta description only if the controller did not provide one
if (!isset($metaDescription)) {
     $metaDescription = 'OneBuild Description';
}
?>
<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<?php require_once __DIR__ . '/../layouts/navbar.php'; ?>
<link rel="stylesheet" href="<?= $base ?>/assets/css/dashboard.css" nonce="<?= $nonce ?>">

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <?php require_once __DIR__ . '/_sidebar.php'; ?>

        <!-- Main content -->
        <section class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Profile</h1>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($success) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="row">
                <div class="col-md-4">
                    <!-- Avatar Card -->
                    <div class="card mb-4">
                        <div class="card-body text-center">
                            <?php if (!empty($user['avatar'])): ?>
                                <img src="<?= $base . htmlspecialchars($user['avatar']) ?>" alt="Avatar" class="rounded-circle mb-3 avatar-lg">
                            <?php else: ?>
                                <div class="rounded-circle bg-secondary d-inline-flex align-items-center justify-content-center mb-3 avatar-lg">
                                    <i class="bi bi-person-fill text-white fs-1"></i>
                                </div>
                            <?php endif; ?>
                            <h5><?= htmlspecialchars($user['username'] ?? $user['phone']) ?></h5>
                            <p class="text-muted small"><?= htmlspecialchars($user['phone']) ?></p>
                            <span class="badge bg-<?= $user['role'] === 'admin' ? 'danger' : ($user['role'] === 'editor' ? 'warning' : 'primary') ?>">
                                <?= ucfirst($user['role']) ?>
                            </span>
                        </div>
                    </div>

                    <!-- Change Avatar Form -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Change Avatar</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="<?= $base ?>/dashboard/profile" enctype="multipart/form-data" class="needs-validation" novalidate>
                                <input type="hidden" name="action" value="update_avatar">
                                <?= \App\Core\CsrfProtection::getHiddenInput() ?>
                                <div class="mb-3">
                                    <input type="file" class="form-control" name="avatar" id="avatar" accept="image/jpeg,image/png,image/gif,image/webp" required>
                                    <div class="form-text">Max 2MB. Allowed: JPG, PNG, GIF, WebP</div>
                                </div>
                                <button type="submit" class="btn btn-primary w-100">Upload Avatar</button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-md-8">
                    <!-- Username Card -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Username</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="<?= $base ?>/dashboard/profile" class="needs-validation" novalidate>
                                <input type="hidden" name="action" value="update_username">
                                <?= \App\Core\CsrfProtection::getHiddenInput() ?>
                                <div class="mb-3">
                                    <label for="username" class="form-label">Username</label>
                                    <div class="input-group">
                                        <span class="input-group-text">@</span>
                                        <input type="text" class="form-control" name="username" id="username" 
                                               value="<?= htmlspecialchars($user['username'] ?? '') ?>" 
                                               placeholder="Enter username" 
                                               pattern="[a-zA-Z0-9_]{3,50}"
                                               maxlength="50">
                                    </div>
                                    <div class="form-text">3-50 characters. Letters, numbers, and underscores only.</div>
                                    <div class="invalid-feedback">Please enter a valid username (3-50 characters, letters/numbers/underscores only).</div>
                                </div>
                                <button type="submit" class="btn btn-primary">Save Username</button>
                            </form>
                        </div>
                    </div>

                    <!-- Change Password Card -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Change Password</h5>
                        </div>
                        <div class="card-body">
                            <form id="changePasswordForm" method="POST" action="<?= $base ?>/dashboard/profile" class="needs-validation" novalidate>
                                <input type="hidden" name="action" value="change_password">
                                <?= \App\Core\CsrfProtection::getHiddenInput() ?>
                                <div class="mb-3">
                                    <label for="current_password" class="form-label">Current Password</label>
                                    <input type="password" class="form-control" name="current_password" id="current_password" required>
                                    <div class="invalid-feedback">Please enter your current password.</div>
                                </div>
                                <div class="mb-3">
                                    <label for="new_password" class="form-label">New Password</label>
                                    <input type="password" class="form-control" name="new_password" id="new_password" required minlength="8">
                                    <div class="form-text">Must be at least 8 characters.</div>
                                    <div class="invalid-feedback">Password must be at least 8 characters.</div>
                                </div>
                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">Confirm New Password</label>
                                    <input type="password" class="form-control" name="confirm_password" id="confirm_password" required>
                                    <div class="invalid-feedback">Please confirm your new password.</div>
                                </div>
                                <button type="submit" class="btn btn-primary">Change Password</button>
                            </form>
                        </div>
                    </div>

                    <!-- Account Info Card -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Account Information</h5>
                        </div>
                        <div class="card-body">
                            <table class="table table-borderless mb-0">
                                <tbody>
                                    <tr>
                                        <th scope="row" class="col-label">User ID</th>
                                        <td><?= $user['id'] ?></td>
                                    </tr>
                                    <tr>
                                        <th scope="row" class="col-label">Phone</th>
                                        <td><?= htmlspecialchars($user['phone']) ?></td>
                                    </tr>
                                    <tr>
                                        <th scope="row" class="col-label">Role</th>
                                        <td>
                                            <span class="badge bg-<?= $user['role'] === 'admin' ? 'danger' : ($user['role'] === 'editor' ? 'warning' : 'primary') ?>">
                                                <?= ucfirst($user['role']) ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row" class="col-label">Member Since</th>
                                        <td><?= date('F j, Y', strtotime($user['created_at'])) ?></td>
                                    </tr>
                                    <tr>
                                        <th scope="row" class="col-label">Last Updated</th>
                                        <td><?= date('F j, Y H:i', strtotime($user['updated_at'])) ?></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>

<style nonce="<?= $nonce ?>">
.avatar-lg {
    width: 120px;
    height: 120px;
}
.avatar-lg img {
    object-fit: cover;
}
.fs-1 {
    font-size: 3rem !important;
}
.col-label {
    width: 150px;
}
</style>

<script nonce="<?= $nonce ?>">
document.addEventListener('DOMContentLoaded', function() {
    // Form validation
    const forms = document.querySelectorAll('.needs-validation');
    forms.forEach(form => {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    });

    // Password match validation
    const changePasswordForm = document.getElementById('changePasswordForm');
    if (changePasswordForm) {
        const newPassword = changePasswordForm.querySelector('#new_password');
        const confirmPassword = changePasswordForm.querySelector('#confirm_password');
        
        function checkPasswordMatch() {
            if (newPassword.value && confirmPassword.value && newPassword.value !== confirmPassword.value) {
                confirmPassword.setCustomValidity('Passwords do not match');
            } else {
                confirmPassword.setCustomValidity('');
            }
        }
        
        newPassword.addEventListener('input', checkPasswordMatch);
        confirmPassword.addEventListener('input', checkPasswordMatch);
    }

    // Avatar preview
    const avatarInput = document.getElementById('avatar');
    if (avatarInput) {
        avatarInput.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const img = document.querySelector('.card-body.text-center img');
                    const placeholder = document.querySelector('.card-body.text-center .rounded-circle.bg-secondary');
                    if (img) {
                        img.src = e.target.result;
                    } else if (placeholder) {
                        placeholder.innerHTML = `<img src="${e.target.result}" alt="Avatar Preview" class="rounded-circle avatar-lg">`;
                    }
                };
                reader.readAsDataURL(file);
            }
        });
    }
});
</script>