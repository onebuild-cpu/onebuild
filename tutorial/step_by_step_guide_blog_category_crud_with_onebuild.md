# Building Your First Blog with the onebuild PHP Framework

Welcome! This step-by-step guide is designed for non-programmers who want to build a complete Blog using the **onebuild** PHP MVC framework.

In this tutorial, you will create:

1. **Database Tables** for Categories and Articles.

2. **Helper File** for image handling and generating clean URLs (slugs).

3. **Navigation Bar (Menu)** for header navigation across the website and admin backend.

4. **Category & Article CRUD** (Backend) to create, edit, list, and delete items.

5. **Admin Dashboard Landing Page** to manage everything from one simple location.

6. **Public Frontend Pages** to display a list of articles and detailed single-article views.

## Code Rules Used in This Guide

* **No Ternary Operators**: We use explicit `if / else` statements so the logic is easy to read.

* **Technologies Used**: PHP, Bootstrap 5, jQuery 3, WebP image conversion.

---

## Step 1: Create the Database Tables

Open **phpMyAdmin** or your database client, select your database, and run this SQL query:

```sql
CREATE TABLE IF NOT EXISTS `categories` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `slug` VARCHAR(100) NOT NULL UNIQUE,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `articles` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `category_id` INT NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `slug` VARCHAR(255) NOT NULL UNIQUE,
    `content` TEXT NOT NULL,
    `image` VARCHAR(255) NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

## Step 2: Create the Helper File (Image Upload & Slugs)

Create a helper file to handle **slug creation** and **WebP image conversion**.

📁 **File Location:** `app/Helpers/BlogHelper.php`

```php
<?php

namespace App\Helpers;

class BlogHelper
{
    /**
     * Converts any string into an SEO-friendly URL slug.
     */
    public static function makeSlug(string $text): string
    {
        $text = strtolower($text);
        $text = preg_replace('/[^a-z0-9 -]/', '', $text);
        $text = preg_replace('/[\s-]+/', '-', $text);
        $text = trim($text, '-');
        
        if (empty($text)) {
            return 'n-a';
        }
        
        return $text;
    }

    /**
     * Uploads an image file and converts it to .webp format.
     */
    public static function uploadAndConvertToWebp(array $file, string $targetDirectory): string
    {
        if (empty($file['tmp_name'])) {
            return '';
        }

        if (!is_dir($targetDirectory)) {
            mkdir($targetDirectory, 0755, true);
        }

        $sourcePath = $file['tmp_name'];
        $fileInfo = getimagesize($sourcePath);

        if ($fileInfo === false) {
            return '';
        }

        $mimeType = $fileInfo['mime'];
        $imageResource = null;

        if ($mimeType === 'image/jpeg') {
            $imageResource = imagecreatefromjpeg($sourcePath);
        } else if ($mimeType === 'image/png') {
            $imageResource = imagecreatefrompng($sourcePath);
        } else if ($mimeType === 'image/webp') {
            $imageResource = imagecreatefromwebp($sourcePath);
        }

        if ($imageResource === null) {
            return '';
        }

        $filename = uniqid('img_', true) . '.webp';
        $destinationPath = rtrim($targetDirectory, '/') . '/' . $filename;

        imagewebp($imageResource, $destinationPath, 80);
        imagedestroy($imageResource);

        return $filename;
    }
}
```

---

## Step 3: Create the Navigation Bar (Menu Layout)

Create a reusable Bootstrap menu bar layout that sits on top of your site so you can easily navigate between public pages and the admin backend.

📁 **File Location:** `app/Views/layouts/navbar.php`

```php
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container">
    <a class="navbar-brand fw-bold" href="/">My Fast Blog</a>
    
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar">
      <span class="navbar-toggler-icon"></span>
    </button>
    
    <div class="collapse navbar-collapse" id="mainNavbar">
      <!-- Left Menu Items -->
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item">
          <a class="nav-link" href="/">Home</a>
        </li>
      </ul>

      <!-- Right Menu Items (Admin Panel) -->
      <ul class="navbar-nav ms-auto">
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle btn btn-outline-light text-white px-3 border-0" href="#" id="adminDropdown" role="button" data-bs-toggle="dropdown">
            ⚙️ Admin Panel
          </a>
          <ul class="dropdown-menu dropdown-menu-end">
            <li><a class="dropdown-item" href="/admin">Dashboard Overview</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="/admin/articles">Manage Articles</a></li>
            <li><a class="dropdown-item" href="/admin/categories">Manage Categories</a></li>
          </ul>
        </li>
      </ul>
    </div>
  </div>
</nav>
```

---

## Step 4: Create the Models

Models communicate directly with database tables.

### 4.1 Category Model

📁 **File Location:** `app/Models/CategoryModel.php`

```php
<?php

namespace App\Models;

use App\Core\Model;

class CategoryModel extends Model
{
    protected string $table = 'categories';
    protected array $fillable = ['name', 'slug'];

    /**
     * Get total count for dashboard statistics.
     */
    public function countAll(): int
    {
        $sql = "SELECT COUNT(*) as total FROM categories";
        $result = $this->db->fetch($sql);
        return (int) ($result['total'] ?? 0);
    }
}
```

### 4.2 Article Model

📁 **File Location:** `app/Models/ArticleModel.php`

```php
<?php

namespace App\Models;

use App\Core\Model;

class ArticleModel extends Model
{
    protected string $table = 'articles';
    protected array $fillable = ['category_id', 'title', 'slug', 'content', 'image'];

    /**
     * Get total count for dashboard statistics.
     */
    public function countAll(): int
    {
        $sql = "SELECT COUNT(*) as total FROM articles";
        $result = $this->db->fetch($sql);
        return (int) ($result['total'] ?? 0);
    }

    /**
     * Custom query to get all articles along with their category name.
     */
    public function getAllWithCategory(): array
    {
        $sql = "SELECT articles.*, categories.name AS category_name 
                FROM articles 
                LEFT JOIN categories ON articles.category_id = categories.id 
                ORDER BY articles.id DESC";
        return $this->db->fetchAll($sql);
    }

    /**
     * Get recent articles for dashboard overview.
     */
    public function getRecent(int $limit = 5): array
    {
        $sql = "SELECT articles.*, categories.name AS category_name 
                FROM articles 
                LEFT JOIN categories ON articles.category_id = categories.id 
                ORDER BY articles.id DESC LIMIT " . (int)$limit;
        return $this->db->fetchAll($sql);
    }

    /**
     * Find a single article by its slug along with its category name.
     */
    public function findBySlug(string $slug): ?array
    {
        $sql = "SELECT articles.*, categories.name AS category_name 
                FROM articles 
                LEFT JOIN categories ON articles.category_id = categories.id 
                WHERE articles.slug = :slug LIMIT 1";
        $result = $this->db->fetch($sql, ['slug' => $slug]);
        
        if ($result === false) {
            return null;
        }

        return $result;
    }
}
```

---

## Step 5: Create the Controllers

### 5.1 Admin Dashboard Controller

📁 **File Location:** `app/Controllers/AdminDashboardController.php`

```php
<?php

namespace App\Controllers;

use App\Core\Controller;

class AdminDashboardController extends Controller
{
    private $articleModel;
    private $categoryModel;

    public function __construct()
    {
        $this->articleModel = $this->model('ArticleModel');
        $this->categoryModel = $this->model('CategoryModel');
    }

    public function index(): void
    {
        $totalArticles = $this->articleModel->countAll();
        $totalCategories = $this->categoryModel->countAll();
        $recentArticles = $this->articleModel->getRecent(5);

        $this->view('admin/dashboard', [
            'totalArticles'   => $totalArticles,
            'totalCategories' => $totalCategories,
            'recentArticles'  => $recentArticles
        ]);
    }
}
```

### 5.2 Admin Category Controller

📁 **File Location:** `app/Controllers/AdminCategoryController.php`

```php
<?php

namespace App\Controllers;

use App\Core\Controller;

class AdminCategoryController extends Controller
{
    private $categoryModel;

    public function __construct()
    {
        $this->categoryModel = $this->model('CategoryModel');
    }

    public function index(): void
    {
        $categories = $this->categoryModel->all();
        $this->view('admin/categories/index', ['categories' => $categories]);
    }

    public function create(): void
    {
        $this->view('admin/categories/create');
    }

    public function store(): void
    {
        $name = $_POST['name'] ?? '';
        $slug = $_POST['slug'] ?? '';

        if (empty($slug)) {
            $slug = \App\Helpers\BlogHelper::makeSlug($name);
        } else {
            $slug = \App\Helpers\BlogHelper::makeSlug($slug);
        }

        $this->categoryModel->create([
            'name' => $name,
            'slug' => $slug
        ]);

        header('Location: /admin/categories');
        exit;
    }

    public function edit(int $id): void
    {
        $category = $this->categoryModel->find($id);
        $this->view('admin/categories/edit', ['category' => $category]);
    }

    public function update(int $id): void
    {
        $name = $_POST['name'] ?? '';
        $slug = $_POST['slug'] ?? '';

        if (empty($slug)) {
            $slug = \App\Helpers\BlogHelper::makeSlug($name);
        } else {
            $slug = \App\Helpers\BlogHelper::makeSlug($slug);
        }

        $this->categoryModel->update($id, [
            'name' => $name,
            'slug' => $slug
        ]);

        header('Location: /admin/categories');
        exit;
    }

    public function delete(int $id): void
    {
        $this->categoryModel->delete($id);
        header('Location: /admin/categories');
        exit;
    }
}
```

### 5.3 Admin Article Controller

📁 **File Location:** `app/Controllers/AdminArticleController.php`

```php
<?php

namespace App\Controllers;

use App\Core\Controller;

class AdminArticleController extends Controller
{
    private $articleModel;
    private $categoryModel;

    public function __construct()
    {
        $this->articleModel = $this->model('ArticleModel');
        $this->categoryModel = $this->model('CategoryModel');
    }

    public function index(): void
    {
        $articles = $this->articleModel->getAllWithCategory();
        $this->view('admin/articles/index', ['articles' => $articles]);
    }

    public function create(): void
    {
        $categories = $this->categoryModel->all();
        $this->view('admin/articles/create', ['categories' => $categories]);
    }

    public function store(): void
    {
        $title = $_POST['title'] ?? '';
        $slug = $_POST['slug'] ?? '';
        $categoryId = $_POST['category_id'] ?? 0;
        $content = $_POST['content'] ?? '';

        if (empty($slug)) {
            $slug = \App\Helpers\BlogHelper::makeSlug($title);
        } else {
            $slug = \App\Helpers\BlogHelper::makeSlug($slug);
        }

        $imageName = '';
        if (isset($_FILES['image'])) {
            if ($_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/../../public/uploads/';
                $imageName = \App\Helpers\BlogHelper::uploadAndConvertToWebp($_FILES['image'], $uploadDir);
            }
        }

        $this->articleModel->create([
            'category_id' => $categoryId,
            'title'       => $title,
            'slug'        => $slug,
            'content'     => $content,
            'image'       => $imageName
        ]);

        header('Location: /admin/articles');
        exit;
    }

    public function edit(int $id): void
    {
        $article = $this->articleModel->find($id);
        $categories = $this->categoryModel->all();

        $this->view('admin/articles/edit', [
            'article'    => $article,
            'categories' => $categories
        ]);
    }

    public function update(int $id): void
    {
        $article = $this->articleModel->find($id);
        $title = $_POST['title'] ?? '';
        $slug = $_POST['slug'] ?? '';
        $categoryId = $_POST['category_id'] ?? 0;
        $content = $_POST['content'] ?? '';

        if (empty($slug)) {
            $slug = \App\Helpers\BlogHelper::makeSlug($title);
        } else {
            $slug = \App\Helpers\BlogHelper::makeSlug($slug);
        }

        $imageName = $article['image'];
        if (isset($_FILES['image'])) {
            if ($_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/../../public/uploads/';
                $newImage = \App\Helpers\BlogHelper::uploadAndConvertToWebp($_FILES['image'], $uploadDir);
                if (!empty($newImage)) {
                    $imageName = $newImage;
                }
            }
        }

        $this->articleModel->update($id, [
            'category_id' => $categoryId,
            'title'       => $title,
            'slug'        => $slug,
            'content'     => $content,
            'image'       => $imageName
        ]);

        header('Location: /admin/articles');
        exit;
    }

    public function delete(int $id): void
    {
        $this->articleModel->delete($id);
        header('Location: /admin/articles');
        exit;
    }
}
```

### 5.4 Public Blog Controller

📁 **File Location:** `app/Controllers/BlogController.php`

```php
<?php

namespace App\Controllers;

use App\Core\Controller;

class BlogController extends Controller
{
    private $articleModel;

    public function __construct()
    {
        $this->articleModel = $this->model('ArticleModel');
    }

    public function index(): void
    {
        $articles = $this->articleModel->getAllWithCategory();
        $this->view('blog/index', ['articles' => $articles]);
    }

    public function show(string $slug): void
    {
        $article = $this->articleModel->findBySlug($slug);

        if ($article === null) {
            http_response_code(404);
            echo "Article not found.";
            return;
        }

        $this->view('blog/show', ['article' => $article]);
    }
}
```

---

## Step 6: Create the View Files

### 6.1 Admin Dashboard Central Hub View

📁 **File Location:** `app/Views/admin/dashboard.php`

```php
<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Admin Dashboard</h2>
        <a href="/" target="_blank" class="btn btn-outline-secondary">View Website &rarr;</a>
    </div>

    <!-- Quick Navigation Bar -->
    <div class="card mb-4 bg-light">
        <div class="card-body d-flex justify-content-start gap-2">
            <a href="/admin" class="btn btn-dark active">Dashboard Overview</a>
            <a href="/admin/articles" class="btn btn-outline-primary">Manage Articles</a>
            <a href="/admin/categories" class="btn btn-outline-primary">Manage Categories</a>
        </div>
    </div>

    <!-- Summary Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-6 mb-3">
            <div class="card text-white bg-primary h-100">
                <div class="card-body">
                    <h5 class="card-title">Total Articles</h5>
                    <p class="card-text display-4 fw-bold"><?php echo $totalArticles; ?></p>
                    <a href="/admin/articles/create" class="btn btn-light btn-sm">+ Add Article</a>
                </div>
            </div>
        </div>

        <div class="col-md-6 mb-3">
            <div class="card text-white bg-success h-100">
                <div class="card-body">
                    <h5 class="card-title">Total Categories</h5>
                    <p class="card-text display-4 fw-bold"><?php echo $totalCategories; ?></p>
                    <a href="/admin/categories/create" class="btn btn-light btn-sm">+ Add Category</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Articles Table -->
    <div class="card">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Recently Added Articles</h5>
            <a href="/admin/articles" class="btn btn-sm btn-link">View All</a>
        </div>
        <div class="card-body p-0">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Category</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recentArticles)): ?>
                        <tr>
                            <td colspan="4" class="text-center py-3 text-muted">No articles found yet.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recentArticles as $art): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($art['title']); ?></td>
                            <td><span class="badge bg-info"><?php echo htmlspecialchars($art['category_name'] ?? 'Uncategorized'); ?></span></td>
                            <td><?php echo $art['created_at']; ?></td>
                            <td>
                                <a href="/admin/articles/edit/<?php echo $art['id']; ?>" class="btn btn-sm btn-warning">Edit</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
```

### 6.2 Admin Views (Categories)

📁 **Category List View:** `app/Views/admin/categories/index.php`

```php
<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Category Management</h2>
        <div>
            <a href="/admin" class="btn btn-outline-secondary me-2">&larr; Dashboard</a>
            <a href="/admin/categories/create" class="btn btn-primary">Add New Category</a>
        </div>
    </div>

    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Category Name</th>
                <th>Slug</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($categories as $cat): ?>
            <tr>
                <td><?php echo $cat['id']; ?></td>
                <td><?php echo htmlspecialchars($cat['name']); ?></td>
                <td><?php echo htmlspecialchars($cat['slug']); ?></td>
                <td>
                    <a href="/admin/categories/edit/<?php echo $cat['id']; ?>" class="btn btn-sm btn-warning">Edit</a>
                    <a href="/admin/categories/delete/<?php echo $cat['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this category?');">Delete</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
```

📁 **Category Create View:** `app/Views/admin/categories/create.php`

```php
<div class="container my-5" style="max-width: 600px;">
    <h2>Create New Category</h2>
    <form action="/admin/categories/store" method="POST">
        <div class="mb-3">
            <label class="form-label">Category Name</label>
            <input type="text" id="cat-name" name="name" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Slug (Optional)</label>
            <input type="text" id="cat-slug" name="slug" class="form-control">
        </div>
        <button type="submit" class="btn btn-success">Save Category</button>
        <a href="/admin/categories" class="btn btn-secondary">Cancel</a>
    </form>
</div>

<script>
$('#cat-name').on('keyup', function() {
    let val = $(this).val().toLowerCase().replace(/[^a-z0-9 -]/g, '').replace(/\s+/g, '-');
    $('#cat-slug').val(val);
});
</script>
```

📁 **Category Edit View:** `app/Views/admin/categories/edit.php`

```php
<div class="container my-5" style="max-width: 600px;">
    <h2>Edit Category</h2>
    <form action="/admin/categories/update/<?php echo $category['id']; ?>" method="POST">
        <div class="mb-3">
            <label class="form-label">Category Name</label>
            <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($category['name']); ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Slug</label>
            <input type="text" name="slug" class="form-control" value="<?php echo htmlspecialchars($category['slug']); ?>">
        </div>
        <button type="submit" class="btn btn-primary">Update Category</button>
        <a href="/admin/categories" class="btn btn-secondary">Cancel</a>
    </form>
</div>
```

### 6.3 Admin Views (Articles)

📁 **Article List View:** `app/Views/admin/articles/index.php`

```php
<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Article Management</h2>
        <div>
            <a href="/admin" class="btn btn-outline-secondary me-2">&larr; Dashboard</a>
            <a href="/admin/articles/create" class="btn btn-primary">Create Article</a>
        </div>
    </div>

    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>Image</th>
                <th>Title</th>
                <th>Category</th>
                <th>Slug</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($articles as $art): ?>
            <tr>
                <td>
                    <?php if (!empty($art['image'])): ?>
                        <img src="/uploads/<?php echo $art['image']; ?>" width="60" class="img-thumbnail" alt="thumbnail">
                    <?php else: ?>
                        <span class="badge bg-secondary">No Image</span>
                    <?php endif; ?>
                </td>
                <td><?php echo htmlspecialchars($art['title']); ?></td>
                <td><span class="badge bg-info"><?php echo htmlspecialchars($art['category_name'] ?? 'Uncategorized'); ?></span></td>
                <td><?php echo htmlspecialchars($art['slug']); ?></td>
                <td>
                    <a href="/admin/articles/edit/<?php echo $art['id']; ?>" class="btn btn-sm btn-warning">Edit</a>
                    <a href="/admin/articles/delete/<?php echo $art['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete article?');">Delete</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
```

📁 **Article Create View:** `app/Views/admin/articles/create.php`

```php
<div class="container my-5" style="max-width: 800px;">
    <h2>Create New Article</h2>
    <form action="/admin/articles/store" method="POST" enctype="multipart/form-data">
        <div class="mb-3">
            <label class="form-label">Article Title</label>
            <input type="text" id="article-title" name="title" class="form-control" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Slug</label>
            <input type="text" id="article-slug" name="slug" class="form-control">
        </div>

        <div class="mb-3">
            <label class="form-label">Category</label>
            <select name="category_id" class="form-select" required>
                <option value="">Select Category</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">Featured Image (Will autoconvert to WebP)</label>
            <input type="file" name="image" class="form-control" accept="image/*">
        </div>

        <div class="mb-3">
            <label class="form-label">Article Content</label>
            <textarea name="content" class="form-control" rows="8" required></textarea>
        </div>

        <button type="submit" class="btn btn-success">Publish Article</button>
        <a href="/admin/articles" class="btn btn-secondary">Cancel</a>
    </form>
</div>

<script>
$('#article-title').on('keyup', function() {
    let val = $(this).val().toLowerCase().replace(/[^a-z0-9 -]/g, '').replace(/\s+/g, '-');
    $('#article-slug').val(val);
});
</script>
```

📁 **Article Edit View:** `app/Views/admin/articles/edit.php`

```php
<div class="container my-5" style="max-width: 800px;">
    <h2>Edit Article</h2>
    <form action="/admin/articles/update/<?php echo $article['id']; ?>" method="POST" enctype="multipart/form-data">
        <div class="mb-3">
            <label class="form-label">Article Title</label>
            <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($article['title']); ?>" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Slug</label>
            <input type="text" name="slug" class="form-control" value="<?php echo htmlspecialchars($article['slug']); ?>">
        </div>

        <div class="mb-3">
            <label class="form-label">Category</label>
            <select name="category_id" class="form-select" required>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo $cat['id']; ?>" <?php if ($cat['id'] == $article['category_id']) { echo 'selected'; } ?>>
                        <?php echo htmlspecialchars($cat['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">Current Image</label><br>
            <?php if (!empty($article['image'])): ?>
                <img src="/uploads/<?php echo $article['image']; ?>" class="img-thumbnail mb-2" width="150" alt="Current Image">
            <?php else: ?>
                <p class="text-muted">No image uploaded.</p>
            <?php endif; ?>
            <input type="file" name="image" class="form-control" accept="image/*">
        </div>

        <div class="mb-3">
            <label class="form-label">Content</label>
            <textarea name="content" class="form-control" rows="8" required><?php echo htmlspecialchars($article['content']); ?></textarea>
        </div>

        <button type="submit" class="btn btn-primary">Update Article</button>
        <a href="/admin/articles" class="btn btn-secondary">Cancel</a>
    </form>
</div>
```

### 6.4 Public Frontend Views

📁 **Public Article List Page:** `app/Views/blog/index.php`

```php
<div class="container my-5">
    <h1 class="mb-4">Latest Blog Posts</h1>
    <div class="row">
        <?php foreach ($articles as $art): ?>
            <div class="col-md-4 mb-4">
                <div class="card h-100 shadow-sm">
                    <?php if (!empty($art['image'])): ?>
                        <img src="/uploads/<?php echo $art['image']; ?>" class="card-img-top" alt="<?php echo htmlspecialchars($art['title']); ?>" style="height: 200px; object-fit: cover;">
                    <?php endif; ?>
                    <div class="card-body">
                        <span class="badge bg-primary mb-2"><?php echo htmlspecialchars($art['category_name'] ?? 'General'); ?></span>
                        <h5 class="card-title"><?php echo htmlspecialchars($art['title']); ?></h5>
                        <p class="card-text text-muted">
                            <?php echo htmlspecialchars(substr($art['content'], 0, 100)); ?>...
                        </p>
                    </div>
                    <div class="card-footer bg-white border-0 pb-3">
                        <a href="/article/<?php echo $art['slug']; ?>" class="btn btn-outline-primary btn-sm">Read Full Article</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
```

📁 **Single Article Detail View:** `app/Views/blog/show.php`

```php
<div class="container my-5" style="max-width: 800px;">
    <a href="/" class="btn btn-outline-secondary mb-3">&larr; Back to Articles</a>
    
    <span class="badge bg-info text-dark d-inline-block mb-2"><?php echo htmlspecialchars($article['category_name']); ?></span>
    <h1 class="fw-bold"><?php echo htmlspecialchars($article['title']); ?></h1>
    <p class="text-muted small">Published on: <?php echo $article['created_at']; ?></p>

    <?php if (!empty($article['image'])): ?>
        <img src="/uploads/<?php echo $article['image']; ?>" class="img-fluid rounded my-3 w-100" alt="<?php echo htmlspecialchars($article['title']); ?>">
    <?php endif; ?>

    <div class="mt-4 fs-5 leading-relaxed">
        <?php echo nl2br(htmlspecialchars($article['content'])); ?>
    </div>
</div>
```

---

## Step 7: Register Routes

Open your framework routing file (`routes/web.php`) and add the main `/admin` route along with all action endpoints:

📁 **File Location:** `routes/web.php`

```php
use App\Core\Router;

// Public Routes
Router::get('/', 'BlogController@index');
Router::get('/article/{slug}', 'BlogController@show');

// Admin Dashboard Overview Route
Router::get('/admin', 'AdminDashboardController@index');

// Admin Category Routes
Router::get('/admin/categories', 'AdminCategoryController@index');
Router::get('/admin/categories/create', 'AdminCategoryController@create');
Router::post('/admin/categories/store', 'AdminCategoryController@store');
Router::get('/admin/categories/edit/{id}', 'AdminCategoryController@edit');
Router::post('/admin/categories/update/{id}', 'AdminCategoryController@update');
Router::get('/admin/categories/delete/{id}', 'AdminCategoryController@delete');

// Admin Article Routes
Router::get('/admin/articles', 'AdminArticleController@index');
Router::get('/admin/articles/create', 'AdminArticleController@create');
Router::post('/admin/articles/store', 'AdminArticleController@store');
Router::get('/admin/articles/edit/{id}', 'AdminArticleController@edit');
Router::post('/admin/articles/update/{id}', 'AdminArticleController@update');
Router::get('/admin/articles/delete/{id}', 'AdminArticleController@delete');
```

---

## Testing Your Complete Dashboard Flow

1. **Include Navbar**: Simply `require` or include `app/Views/layouts/navbar.php` inside your primary layout header or at the top of your page views.
2. **Dashboard Home**: Go to **`http://localhost/admin`** to see your main dashboard summary cards and total counts.
3. **Category Management**: Click **Manage Categories** or go to `http://localhost/admin/categories` to add, edit, or remove categories.
4. **Article Management**: Click **Manage Articles** or go to `http://localhost/admin/articles` to write posts, auto-generate WebP images, edit dynamic slugs, or delete old articles.
5. **View Website**: Click **View Website** at the top right of your dashboard to jump to the public homepage and check how your articles look to real visitors.