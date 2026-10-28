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
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <meta name="description" content="<?= htmlspecialchars($metaDescription) ?>">
    <link rel="icon" type="image/x-icon" href="<?= $base ?>/favicon.ico"> 

    <link rel="stylesheet" href="<?= $base ?>/assets/css/bootstrap-icons.min.css?v=1.0.1">
    <link rel="stylesheet" href="<?= $base ?>/assets/css/summernote-bs5.min.css?v=1.0.1" nonce="<?= $nonce ?>">
    <link rel="stylesheet" href="<?= $base ?>/assets/css/bootstrap.min.css?v=1.0.1">
    
    <!-- CSP Nonce for inline scripts/styles -->
    <meta name="csp-nonce" content="<?= $nonce ?>">
    <meta name="csrf-token" content="<?= \App\Core\CsrfProtection::getToken() ?>">

</head>
<?php
if (!isset($bodyClass)) {
    $bodyClass = '';
}
?>
<body class="<?= htmlspecialchars($bodyClass) ?>">