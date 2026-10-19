<?php
$requestUri = $GLOBALS['requestUri'] ?? '';
// $requestUri is trimmed (no leading/trailing slashes): 'login', 'register', 'dashboard', 'dashboard/profile', '' (home)
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top">
    <div class="container">
        <a class="navbar-brand" href="<?= $base ?>">OneBuild MVC PHP</a>

        <div class="d-flex align-items-center">
            <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] !== 0): ?>
            <button class="navbar-toggler me-2" type="button" id="sidebarToggle" aria-label="Toggle sidebar">
                <span>&#x25E7;</span>
            </button>
            <?php endif; ?>

            <button class="navbar-toggler" type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#navbarNav"
                    aria-controls="navbarNav"
                    aria-expanded="false"
                    aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
        </div>

        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link <?= ($requestUri === '' || $requestUri === '/') ? 'active' : '' ?>" href="<?= $base ?>">Home</a>
                </li>
                <?php /*
                <li class="nav-item">
                    <a class="nav-link" href="#">Products</a>
                </li>
                */ ?>
                <?php /* Commented out because registration can only be done by root/admin
                <?php if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] == 0): ?>
                    <li class="nav-item">
                        <a class="nav-link <?= $requestUri === 'login' ? 'active' : '' ?>" href="<?= $base ?>/login">Sign In</a>
                    </li>
                    
                    <li class="nav-item">
                        <a class="nav-link <?= $requestUri === 'register' ? 'active' : '' ?>" href="<?= $base ?>/register">Register</a>
                    </li>
                
                <?php else: ?>
                */ ?>
                <?php if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] == 0): ?>
                    <li class="nav-item">
                        <a class="nav-link <?= $requestUri === 'login' ? 'active' : '' ?>" href="<?= $base ?>/login">Sign In</a>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link <?= stripos($requestUri, 'dashboard') === 0 ? 'active' : '' ?>" href="<?= $base ?>/dashboard">Dashboard</a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <?php if (!empty($_SESSION['avatar'])): ?>
                                <img src="<?= $base . htmlspecialchars($_SESSION['avatar']) ?>" alt="Avatar" class="rounded-circle me-2 avatar-sm">
                            <?php else: ?>
                                <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center me-2 avatar-sm">
                                    <i class="bi bi-person-fill text-white fs-6"></i>
                                </div>
                            <?php endif; ?>
                            <span><?= htmlspecialchars($_SESSION['username'] ?? 'User') ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><h6 class="dropdown-header"><?= htmlspecialchars($_SESSION['username'] ?? 'User') ?></h6></li>
                            <li><small class="dropdown-item-text text-muted"><?= htmlspecialchars($_SESSION['phone'] ?? '') ?></small></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?= $base ?>/dashboard/profile"><i class="bi bi-person me-2"></i>Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form method="POST" action="<?= $base ?>/logout" style="margin:0;">
                                    <?= \App\Core\CsrfProtection::getHiddenInput() ?>
                                    <button type="submit" class="dropdown-item" style="background:none; border:none; width:100%; text-align:left;">
                                        <i class="bi bi-box-arrow-right me-2"></i>Sign Out
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<div class="mt-4"></div>

<style nonce="<?= $nonce ?>">
.avatar-sm {
    width: 32px;
    height: 32px;
}
.avatar-sm img {
    object-fit: cover;
}
.fs-6 {
    font-size: 0.875rem !important;
}
</style>

<script nonce="<?= $nonce ?>">
document.addEventListener('DOMContentLoaded', function() {
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebarMenu');
    const overlay = document.getElementById('sidebarOverlay');
    const base = <?= json_encode($base) ?>;
    
    function closeSidebar() {
        if (sidebar) sidebar.classList.remove('show');
        if (overlay) overlay.classList.remove('show');
    }
    
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
            if (sidebar) {
                sidebar.classList.toggle('show');
                if (overlay) overlay.classList.toggle('show');
            } else {
                // Redirect to dashboard if sidebar doesn't exist on this page
                window.location.href = base + '/dashboard';
            }
        });
    }
    
    if (overlay) {
        overlay.addEventListener('click', closeSidebar);
    }
    
    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', function(e) {
        if (window.innerWidth < 768) {
            if (sidebar && !sidebar.contains(e.target) && sidebarToggle && !sidebarToggle.contains(e.target)) {
                closeSidebar();
            }
        }
    });
    
    // Close sidebar on window resize to desktop
    window.addEventListener('resize', function() {
        if (window.innerWidth >= 768) {
            closeSidebar();
        }
    });
});
</script>

<?php // Open the main content area now that the navbar is done ?>
<main class="main-content">

    <?php // Render flash messages from session, then clear them ?>
    <?php if (!empty($_SESSION['success'])): ?>
        <div class="container mt-3">
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($_SESSION['success']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?php if (!empty($_SESSION['error'])): ?>
        <div class="container mt-3">
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($_SESSION['error']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>