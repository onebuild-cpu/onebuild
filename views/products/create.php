<?php
$base  = $GLOBALS['base'] ?? '';
$nonce = $GLOBALS['csp_nonce'] ?? '';
$pageTitle = $pageTitle ?? 'New Product';
$metaDescription = $metaDescription ?? 'Add product';
$currentPage = 'products';
$old = $old ?? [];
$error = $error ?? '';
?>
<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<?php require_once __DIR__ . '/../layouts/navbar.php'; ?>
<link rel="stylesheet" href="<?= $base ?>/assets/css/dashboard.css" nonce="<?= $nonce ?>">

<div class="container-fluid">
    <div class="row">
        <?php require_once __DIR__ . '/../dashboard/_sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">New Product</h1>
                <a href="<?= $base ?>/products" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Back
                </a>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <div class="card">
                <div class="card-body">
                    <form method="POST" action="<?= $base ?>/products/store" enctype="multipart/form-data" novalidate>
                        <?= \App\Core\CsrfProtection::getHiddenInput() ?>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="sku" class="form-label">SKU <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="sku" name="sku"
                                       value="<?= htmlspecialchars($old['sku'] ?? '') ?>" maxlength="100" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="barcode" class="form-label">Barcode / UPC</label>
                                <input type="text" class="form-control" id="barcode" name="barcode"
                                       value="<?= htmlspecialchars($old['barcode'] ?? '') ?>" maxlength="100">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="name" class="form-label">Product Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name"
                                   value="<?= htmlspecialchars($old['name'] ?? '') ?>" maxlength="255" required>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="description" name="description" rows="4" required><?= htmlspecialchars($old['description'] ?? '') ?></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="variant" class="form-label">Variant <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="variant" name="variant"
                                       value="<?= htmlspecialchars($old['variant'] ?? '') ?>"
                                       placeholder="e.g. 250ml / Red / XL" maxlength="255" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="price" class="form-label">Price <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" min="0" class="form-control" id="price" name="price"
                                       value="<?= htmlspecialchars($old['price'] ?? '0.00') ?>" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="product_photo" class="form-label">Product Photo</label>
                                <input type="file" class="form-control" id="product_photo" name="product_photo"
                                       accept="image/jpeg,image/png,image/gif,image/webp">
                                <div class="form-text">JPG, PNG, GIF, WebP. Auto-converted to WebP. Max 5MB.</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="packaging_photo" class="form-label">Packaging Photo</label>
                                <input type="file" class="form-control" id="packaging_photo" name="packaging_photo"
                                       accept="image/jpeg,image/png,image/gif,image/webp">
                                <div class="form-text">JPG, PNG, GIF, WebP. Auto-converted to WebP. Max 5MB.</div>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Save Product
                        </button>
                    </form>
                </div>
            </div>
        </main>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>