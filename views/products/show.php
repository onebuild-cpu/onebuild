<?php
$base  = $GLOBALS['base'] ?? '';
$nonce = $GLOBALS['csp_nonce'] ?? '';
$pageTitle = $pageTitle ?? 'Product';
$metaDescription = $metaDescription ?? '';
$product = $product ?? [];
?>
<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<?php require_once __DIR__ . '/../layouts/navbar.php'; ?>

<div class="container py-5">
    <div class="row">
        <!-- Left: images -->
        <div class="col-md-6">
            <?php if (!empty($product['product_photo'])): ?>
                <img src="<?= $base . htmlspecialchars($product['product_photo']) ?>"
                     class="img-fluid rounded mb-3"
                     alt="<?= htmlspecialchars($product['name']) ?>">
            <?php else: ?>
                <div class="bg-light rounded d-flex align-items-center justify-content-center mb-3"
                     style="height:400px;">
                    <span class="text-muted">No product photo</span>
                </div>
            <?php endif; ?>

            <?php if (!empty($product['packaging_photo'])): ?>
                <div class="mb-3">
                    <small class="text-muted d-block mb-2">Packaging</small>
                    <img src="<?= $base . htmlspecialchars($product['packaging_photo']) ?>"
                         class="img-fluid rounded"
                         style="max-height:200px;"
                         alt="Packaging">
                </div>
            <?php endif; ?>
        </div>

        <!-- Right: details -->
        <div class="col-md-6">
            <h1 class="mb-3"><?= htmlspecialchars($product['name']) ?></h1>

            <p class="text-muted mb-2">
                SKU: <code><?= htmlspecialchars($product['sku']) ?></code>
                <?php if (!empty($product['barcode'])): ?>
                    &nbsp;|&nbsp; Barcode: <code><?= htmlspecialchars($product['barcode']) ?></code>
                <?php endif; ?>
            </p>

            <p class="mb-3">
                <span class="badge bg-secondary"><?= htmlspecialchars($product['variant']) ?></span>
            </p>

            <h3 class="text-primary mb-4">
                $<?= number_format((float) $product['price'], 2) ?>
            </h3>

            <div class="mb-4">
                <h5>Description</h5>
                <p><?= nl2br(htmlspecialchars($product['description'])) ?></p>
            </div>

            <p class="text-muted small">
                Added on <?= date('F j, Y', strtotime($product['created_at'])) ?>
            </p>

            <a href="<?= $base ?>/products" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back to products
            </a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>