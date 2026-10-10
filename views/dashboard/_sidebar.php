<?php
$base = $base ?? ($GLOBALS['base'] ?? '');
$currentPage = $currentPage ?? 'index';
?>
<!-- Sidebar Overlay (mobile) -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- Sidebar -->
<nav class="col-md-3 col-lg-2 d-md-block bg-light sidebar" id="sidebarMenu">
    <div class="position-sticky pt-3">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?= $currentPage === 'index' ? 'active' : '' ?>" href="<?= $base ?>/dashboard">
                    <i class="bi bi-speedometer2"></i> Overview
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?= $currentPage === 'profile' ? 'active' : '' ?>" href="<?= $base ?>/dashboard/profile">
                    <i class="bi bi-person"></i> Profile
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="<?= $base ?>/logout">
                    <i class="bi bi-box-arrow-right"></i> Sign Out
                </a>
            </li>
        </ul>
    </div>
</nav>