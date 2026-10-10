<?php
$base  = $GLOBALS['base'] ?? '';
$nonce = $GLOBALS['csp_nonce'] ?? '';
$pageTitle = $pageTitle ?? 'Products';
$metaDescription = $metaDescription ?? 'Products';
$currentPage = 'products';
$products = $products ?? [];
?>
<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<?php require_once __DIR__ . '/../layouts/navbar.php'; ?>
<link rel="stylesheet" href="<?= $base ?>/assets/css/dashboard.css" nonce="<?= $nonce ?>">

<div class="container-fluid">
    <div class="row">
        <?php require_once __DIR__ . '/../dashboard/_sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Products</h1>
                <a href="<?= $base ?>/products/create" class="btn btn-primary">
                    <i class="bi bi-plus-lg"></i> New Product
                </a>
            </div>

            <?php if (!empty($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <?= htmlspecialchars($_SESSION['error']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>
            <?php if (!empty($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <?= htmlspecialchars($_SESSION['success']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            <div class="card">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Photo</th>
                                <th>SKU</th>
                                <th>Name</th>
                                <th>Variant</th>
                                <th>Price</th>
                                <th class="col-width-120">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($products)): ?>
                            <tr><td colspan="7" class="text-center text-muted py-4">No products yet.</td></tr>
                        <?php else: ?>
                            <?php foreach ($products as $p): ?>
                                <tr>
                                    <td><?= (int) $p['id'] ?></td>
                                    <td>
                                        <?php if (!empty($p['product_photo'])): ?>
                                            <img src="<?= $base . htmlspecialchars($p['product_photo']) ?>"
                                                 style="width:48px;height:48px;object-fit:cover;border-radius:4px;"
                                                 alt="">
                                        <?php else: ?>
                                            <span class="text-muted small">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($p['sku']) ?></td>
                                    <td>
                                        <a href="<?= $base ?>/product/<?= htmlspecialchars($p['slug']) ?>" target="_blank">
                                            <?= htmlspecialchars($p['name']) ?>
                                        </a>
                                    </td>
                                    <td><?= htmlspecialchars($p['variant']) ?></td>
                                    <td>$<?= number_format((float) $p['price'], 2) ?></td>
                                    <td>
                                        <a href="<?= $base ?>/products/edit/<?= (int) $p['id'] ?>"
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form method="POST"
                                              action="<?= $base ?>/products/delete/<?= (int) $p['id'] ?>"
                                              class="d-inline"
                                              onsubmit="return confirm('Delete this product?');">
                                            <?= \App\Core\CsrfProtection::getHiddenInput() ?>
                                            <button class="btn btn-sm btn-outline-danger">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>