<?php
namespace App\Controllers;

use PDO;
use App\Core\Controller;
use App\Core\CsrfProtection;
use App\Core\ImageUploader;
use App\Models\ProductModel;

class ProductController extends Controller
{
    private ProductModel $model;

    public function __construct(PDO $pdo)
    {
        parent::__construct($pdo);
        $this->model = new ProductModel($pdo);
    }

    /** GET /products — list all products */
    public function index(): void
    {
        $this->requireLogin();

        $products = $this->model->findAll('id DESC');

        $this->view('products/index', [
            'pageTitle'       => 'Products',
            'metaDescription' => 'Product list',
            'products'        => $products,
        ]);
    }

    /** GET /products/create — show empty form */
    public function create(): void
    {
        $this->requireLogin();

        $this->view('products/create', [
            'pageTitle'       => 'New Product',
            'metaDescription' => 'Add product',
            'error'           => $_SESSION['error'] ?? '',
            'old'             => $_SESSION['old'] ?? [],
        ]);

        unset($_SESSION['error'], $_SESSION['old']);
    }

    /** POST /products/store — save new product */
    public function store(): void
    {
        $this->requireLogin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/products');
            return;
        }

        CsrfProtection::requireValidToken();

        // 1. Validate text fields
        $errors = [];
        $data   = [];

        $data['sku']         = trim($_POST['sku'] ?? '');
        $data['barcode']     = trim($_POST['barcode'] ?? '');
        $data['name']        = trim($_POST['name'] ?? '');
        $data['description'] = trim($_POST['description'] ?? '');
        $data['variant']     = trim($_POST['variant'] ?? '');
        $data['price']       = $_POST['price'] ?? '0';

        if ($data['sku'] === '')         $errors[] = 'SKU is required.';
        if ($data['name'] === '')        $errors[] = 'Product name is required.';
        if ($data['description'] === '') $errors[] = 'Description is required.';
        if ($data['variant'] === '')     $errors[] = 'Variant is required.';
        if (!is_numeric($data['price'])) $errors[] = 'Price must be a number.';

        if ($data['sku'] !== '' && $this->model->skuExists($data['sku'])) {
            $errors[] = 'That SKU is already used.';
        }

        if ($errors) {
            $_SESSION['error'] = implode(' ', $errors);
            $_SESSION['old']   = $_POST;
            $this->redirect('/products/create');
            return;
        }

        // 2. Handle image uploads (only after text is valid)
        $savedFiles = [];

        try {
            if (!empty($_FILES['product_photo']['name'])) {
                $savedFiles['product_photo'] = ImageUploader::toWebp(
                    $_FILES['product_photo'], 'products', 1600, 82
                );
            }
            if (!empty($_FILES['packaging_photo']['name'])) {
                $savedFiles['packaging_photo'] = ImageUploader::toWebp(
                    $_FILES['packaging_photo'], 'products', 1600, 82
                );
            }
        } catch (\Exception $e) {
            // Cleanup any file we already saved
            foreach ($savedFiles as $path) {
                ImageUploader::delete($path);
            }
            $_SESSION['error'] = 'Image upload failed: ' . $e->getMessage();
            $_SESSION['old']   = $_POST;
            $this->redirect('/products/create');
            return;
        }

        // 3. Insert into DB
        try {
            $this->model->insert([
                'sku'             => $data['sku'],
                'barcode'         => $data['barcode'] !== '' ? $data['barcode'] : null,
                'name'            => $data['name'],
                'description'     => $data['description'],
                'variant'         => $data['variant'],
                'price'           => round((float) $data['price'], 2),
                'product_photo'   => $savedFiles['product_photo']   ?? null,
                'packaging_photo' => $savedFiles['packaging_photo'] ?? null,
            ]);

            $_SESSION['success'] = 'Product created.';
            $this->redirect('/products');
        } catch (\Exception $e) {
            // Roll back uploaded files
            foreach ($savedFiles as $path) {
                ImageUploader::delete($path);
            }
            $_SESSION['error'] = 'Database error: ' . $e->getMessage();
            $_SESSION['old']   = $_POST;
            $this->redirect('/products/create');
        }
    }

    /** GET /products/edit/{id} — show form filled with existing data */
    public function edit(string $id): void
    {
        $this->requireLogin();

        $product = $this->model->findById((int) $id);
        if (!$product) {
            $_SESSION['error'] = 'Product not found.';
            $this->redirect('/products');
            return;
        }

        $this->view('products/edit', [
            'pageTitle'       => 'Edit Product',
            'metaDescription' => 'Edit product',
            'product'         => $product,
            'error'           => $_SESSION['error'] ?? '',
        ]);

        unset($_SESSION['error']);
    }

    /** POST /products/update/{id} — save edits */
    public function update(string $id): void
    {
        $this->requireLogin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/products');
            return;
        }

        CsrfProtection::requireValidToken();

        $product = $this->model->findById((int) $id);
        if (!$product) {
            $_SESSION['error'] = 'Product not found.';
            $this->redirect('/products');
            return;
        }

        // 1. Validate text
        $errors = [];
        $data   = [];

        $data['sku']         = trim($_POST['sku'] ?? '');
        $data['barcode']     = trim($_POST['barcode'] ?? '');
        $data['name']        = trim($_POST['name'] ?? '');
        $data['description'] = trim($_POST['description'] ?? '');
        $data['variant']     = trim($_POST['variant'] ?? '');
        $data['price']       = $_POST['price'] ?? '0';

        if ($data['sku'] === '')         $errors[] = 'SKU is required.';
        if ($data['name'] === '')        $errors[] = 'Name is required.';
        if ($data['description'] === '') $errors[] = 'Description is required.';
        if ($data['variant'] === '')     $errors[] = 'Variant is required.';
        if (!is_numeric($data['price'])) $errors[] = 'Price must be a number.';

        if ($data['sku'] !== '' && $this->model->skuExists($data['sku'], (int) $id)) {
            $errors[] = 'That SKU is already used by another product.';
        }

        if ($errors) {
            $_SESSION['error'] = implode(' ', $errors);
            $this->redirect('/products/edit/' . $id);
            return;
        }

        // 2. Handle new images if provided
        $newFiles = [];
        $oldFiles = [];

        try {
            if (!empty($_FILES['product_photo']['name'])) {
                $newFiles['product_photo'] = ImageUploader::toWebp(
                    $_FILES['product_photo'], 'products', 1600, 82
                );
                if (!empty($product['product_photo'])) {
                    $oldFiles[] = $product['product_photo'];
                }
            }
            if (!empty($_FILES['packaging_photo']['name'])) {
                $newFiles['packaging_photo'] = ImageUploader::toWebp(
                    $_FILES['packaging_photo'], 'products', 1600, 82
                );
                if (!empty($product['packaging_photo'])) {
                    $oldFiles[] = $product['packaging_photo'];
                }
            }
        } catch (\Exception $e) {
            foreach ($newFiles as $path) {
                ImageUploader::delete($path);
            }
            $_SESSION['error'] = 'Image upload failed: ' . $e->getMessage();
            $this->redirect('/products/edit/' . $id);
            return;
        }

        // 3. Update DB
        try {
            $this->model->update((int) $id, [
                'sku'         => $data['sku'],
                'barcode'     => $data['barcode'] !== '' ? $data['barcode'] : null,
                'name'        => $data['name'],
                'description' => $data['description'],
                'variant'     => $data['variant'],
                'price'       => round((float) $data['price'], 2),
                'product_photo'   => $newFiles['product_photo']   ?? $product['product_photo'],
                'packaging_photo' => $newFiles['packaging_photo'] ?? $product['packaging_photo'],
            ]);

            // Delete old images only after DB success
            foreach ($oldFiles as $old) {
                ImageUploader::delete($old);
            }

            $_SESSION['success'] = 'Product updated.';
            $this->redirect('/products');
        } catch (\Exception $e) {
            foreach ($newFiles as $path) {
                ImageUploader::delete($path);
            }
            $_SESSION['error'] = 'Database error: ' . $e->getMessage();
            $this->redirect('/products/edit/' . $id);
        }
    }

    /** POST /products/delete/{id} */
    public function delete(string $id): void
    {
        $this->requireLogin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/products');
            return;
        }

        CsrfProtection::requireValidToken();

        $product = $this->model->findById((int) $id);
        if (!$product) {
            $_SESSION['error'] = 'Product not found.';
            $this->redirect('/products');
            return;
        }

        try {
            $this->model->delete((int) $id);

            // Clean up images after DB success
            if (!empty($product['product_photo'])) {
                ImageUploader::delete($product['product_photo']);
            }
            if (!empty($product['packaging_photo'])) {
                ImageUploader::delete($product['packaging_photo']);
            }

            $_SESSION['success'] = 'Product deleted.';
        } catch (\Exception $e) {
            $_SESSION['error'] = 'Delete failed: ' . $e->getMessage();
        }

        $this->redirect('/products');
    }
}