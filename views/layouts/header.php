<?php
$base = '';
if (isset($GLOBALS['base'])) {
    $base = $GLOBALS['base'];
}
$nonce = '';
if (isset($GLOBALS['csp_nonce'])) {
    $nonce = $GLOBALS['csp_nonce'];
}
$pageTitle = 'OneBuild PHP MVC Framework';
if (isset($pageTitle)) {
    $pageTitle = $pageTitle;
}
$metaDescription = 'OneBuild Description';
if (isset($metaDescription)) {
    $metaDescription = $metaDescription;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <meta name="description" content="<?= htmlspecialchars($metaDescription) ?>">
    <link rel="icon" type="image/x-icon" href="<?= $base ?>/favicon.ico"> 

    <link rel="stylesheet" href="<?= $base ?>/assets/css/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= $base ?>/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= $base ?>/assets/css/summernote-bs5.min.css" nonce="<?= $nonce ?>">
    
    <!-- CSP Nonce for inline scripts/styles -->
    <meta name="csp-nonce" content="<?= $nonce ?>">
    <meta name="csrf-token" content="<?= \App\Core\CsrfProtection::getToken() ?>">

</head>
<body>
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <?= htmlspecialchars($_SESSION['success']) ?>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-error">
            <?= htmlspecialchars($_SESSION['error']) ?>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>
    
    <main class="main-content">
