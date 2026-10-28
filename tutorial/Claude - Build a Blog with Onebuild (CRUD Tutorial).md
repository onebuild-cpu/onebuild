# Build a Blog with Onebuild

## A step-by-step CRUD tutorial for non-programmers

*Rewritten for your real Onebuild project. The code in this tutorial was tested end to end (login, create, edit, delete, image upload) before printing.*

## Read this first

**What you will build.** A small blog inside your Onebuild site:

- a **Categories** manager (add, list, edit, delete)
- an **Articles** manager (the same four actions, plus a picture upload)
- two **public pages** where visitors read the articles

Programmers call the four actions **CRUD**: **C**reate, **R**ead, **U**pdate, **D**elete. Almost every website is CRUD on different things (products, customers, invoices). Learn it once and you can reuse it forever.

**How long it takes.** About 3 to 4 hours, in several sessions. The site works at every CHECKPOINT, so you can stop after any Part and continue another day.

**How to follow this paper**

1. Every file starts with a label such as `FILE: app/Models/CategoryModel.php` and says **NEW FILE** or **EDIT FILE**.
2. Type the code exactly as printed. PHP is strict about these characters: `;` `{ }` `( )` `[ ]` `' '` `$` `->` `=>`. One missing character gives an error page.
3. Indentation (the spaces at the start of a line) is only for reading. Keep it anyway, it helps you spot mistakes.
4. Lines that start with `//` or `*`, or that sit between `/**` and `*/`, are comments written for humans. You may skip typing them.
5. Names are case-sensitive. The file `AdminCategoryController.php` must contain `class AdminCategoryController`. Same letters, same capitals.
6. Use a code editor such as VS Code (free). Do not use Word.
7. **Typo detector.** After you save a PHP file, open a terminal in your project folder (in Laragon: Menu, then Terminal) and run `php -l app/Helpers/BlogHelper.php`, using the path of the file you just made. It answers `No syntax errors detected`, or tells you which line has the problem. It works for view files too.

## Before you start (checklist)

Tick each box.

- [ ] Your Onebuild home page opens in the browser.
- [ ] You can sign in at `/login` and reach `/dashboard`.
- [ ] Your account has the role **root** or **admin**. The admin pages you will build refuse everybody else. (A root account is created by running `php bin/add_root.php "+628123456789" "YourPassword"` once in the project folder.)
- [ ] While learning on your own computer, open the file `.env` and set `APP_ENV=development`. Error messages then show on screen instead of a blank page. Never leave this on a real website: set it back to `production` when you publish.
- [ ] You have a database tool (phpMyAdmin or HeidiSQL) and you know which database Onebuild uses. It is the `DB_NAME` line in `.env`.

## The big picture (read once, 5 minutes)

Think of a restaurant.

- **Route** = the menu board at the door. It says "this address is handled by that waiter". Onebuild keeps its routes in `public/index.php`.
- **Controller** = the waiter. It receives the request, checks you are allowed, fetches what is needed and brings it to the table. Controllers live in `app/Controllers/`.
- **Model** = the pantry keeper. The only one who touches the database. Models live in `app/Models/`.
- **View** = the plate. The HTML page the visitor sees. Views live in `views/`.

What happens when you open the page `/dashboard/categories`:

```text
Browser asks for  /dashboard/categories
        |
        v
public/index.php    route list says: AdminCategoryController -> index()
        |
        v
AdminCategoryController::index()
        1. Is this person an admin?             (requireAdmin)
        2. Ask the model for all categories     (CategoryModel -> database)
        3. Hand the list to the view
        |
        v
views/admin/categories/index.php   builds the HTML page and sends it back
```

**One rule you will see everywhere.** Anything with a form needs **two** controller methods. One **shows** the form (the browser *asks* for a page: this is called GET). The other **receives** the filled form (the browser *sends* data: this is called POST).

- Create = `create()` shows the empty form, `store()` saves it
- Read = `index()` shows the list
- Update = `edit()` shows the filled form, `update()` saves it
- Delete = `delete()` removes the item (a small form with a Delete button triggers it)

**Words you will meet**

- **Slug**: the web-friendly version of a name. "My First Post!" becomes `my-first-post` and is used in the address `/blog/my-first-post`.
- **Session**: the server's memory of who is logged in.
- **Flash message**: a green or red message shown once (for example "Category created.") and then forgotten. Onebuild's top menu already displays them. You only write `$_SESSION['success'] = '...'` or `$_SESSION['error'] = '...'`.
- **CSRF token**: a secret code hidden inside every form. It proves the form came from your own site and not from a trick page. Every form you build contains one, and every action that receives a form checks it.
- **Layout**: a file holding the parts that every page shares (top menu, sidebar, footer).

**What every Onebuild model can already do** (free, you inherit it):

- `findAll('name ASC')`: all rows, sorted
- `findById(5)`: one row, or `null` when it does not exist
- `insert([...])`: add a row (the dates are filled in for you)
- `update(5, [...])`: change a row
- `delete(5)`: remove a row
- `query('SELECT ...', [values])`: your own SQL, returns many rows
- `queryOne('SELECT ...', [values])`: your own SQL, returns one row or `null`

**Files you will make (NEW) or change (EDIT)**

```text
database (phpMyAdmin)                           2 new tables
app/Helpers/BlogHelper.php                      NEW
app/Models/CategoryModel.php                    NEW
app/Models/ArticleModel.php                     NEW
app/Controllers/AdminCategoryController.php     NEW
app/Controllers/AdminArticleController.php      NEW
app/Controllers/BlogController.php              NEW
views/layouts/admin_top.php                     NEW
views/layouts/admin_bottom.php                  NEW
views/admin/categories/  _form, create, edit, index    NEW (4 files)
views/admin/articles/    _form, create, edit, index    NEW (4 files)
views/blog/              index, show                   NEW (2 files)
views/dashboard/_sidebar.php                    EDIT
views/layouts/navbar.php                        EDIT
public/index.php                                EDIT
```

## Part 1: The database tables

A table is like an Excel sheet: columns across the top, one row for each item.

**Step 1.1** Open phpMyAdmin (or HeidiSQL) and click your Onebuild database.

**Step 1.2** Click the **SQL** tab (HeidiSQL: the Query tab). Type this, then press **Go** (HeidiSQL: Execute).

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
    FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

The lines that matter:

- `id ... AUTO_INCREMENT`: the database numbers every row 1, 2, 3... for you.
- `slug ... UNIQUE`: two rows can never have the same slug.
- `category_id`: the link that says which category an article belongs to.
- `ON DELETE RESTRICT`: the database refuses to delete a category that still has articles. This protects you from wiping out articles by accident.
- `created_at`, `updated_at`: dates that fill themselves in.

**CHECKPOINT 1.** In the list of tables you now see `categories` and `articles`, both empty.

## Part 2: The helper file

A **helper** is a toolbox of small functions that belong to no single page. Ours has five tools:

- `makeSlug()` turns "My First Post!" into `my-first-post`.
- `imageFolder()` says where pictures are stored.
- `saveImageAsWebp()` checks an uploaded picture, converts it to WebP (a small, fast format) and saves it under a random name.
- `deleteImage()` removes a picture file.
- `excerpt()` cuts a long text short for the list page.

**Step 2.1** Create a new folder named `Helpers` inside the `app` folder.

**Step 2.2** Create the file below.

**FILE: `app/Helpers/BlogHelper.php`  (NEW FILE)**

```php
<?php

namespace App\Helpers;

use Exception;

class BlogHelper
{
    /**
     * Turns any text into a clean web-address word.
     * Example: "My First Post!" becomes "my-first-post"
     */
    public static function makeSlug(string $text): string
    {
        $text = strtolower($text);
        $text = preg_replace('/[^a-z0-9 -]/', '', $text);
        $text = preg_replace('/[\s-]+/', '-', $text);
        $text = trim($text, '-');

        if ($text === '') {
            return 'n-a';
        }

        return $text;
    }

    /**
     * The folder on the server where article images are stored.
     */
    public static function imageFolder(): string
    {
        return dirname(__DIR__, 2) . '/public/uploads/images';
    }

    /**
     * Takes an uploaded image (JPG, PNG or WebP), converts it to WebP,
     * saves it in the folder, and returns the new file name.
     * If something is wrong it throws an Exception with a friendly message.
     */
    public static function saveImageAsWebp(array $file, string $folder): string
    {
        $maxSize = 2 * 1024 * 1024; // 2 MB

        if ($file['size'] > $maxSize) {
            throw new Exception('The image is too big. Maximum size is 2 MB.');
        }

        $info = getimagesize($file['tmp_name']);
        if ($info === false) {
            throw new Exception('The uploaded file is not a valid image.');
        }

        $mime = $info['mime'];
        $image = false;

        if ($mime === 'image/jpeg') {
            $image = imagecreatefromjpeg($file['tmp_name']);
        } elseif ($mime === 'image/png') {
            $image = imagecreatefrompng($file['tmp_name']);
            if ($image !== false) {
                imagepalettetotruecolor($image);
                imagealphablending($image, true);
                imagesavealpha($image, true);
            }
        } elseif ($mime === 'image/webp') {
            $image = imagecreatefromwebp($file['tmp_name']);
        } else {
            throw new Exception('Only JPG, PNG and WebP images are allowed.');
        }

        if ($image === false) {
            throw new Exception('The image could not be read. Please try another file.');
        }

        if (!is_dir($folder)) {
            mkdir($folder, 0755, true);
        }

        $filename = 'img_' . bin2hex(random_bytes(8)) . '.webp';
        $saved = imagewebp($image, $folder . '/' . $filename, 80);

        if ($saved === false) {
            throw new Exception('The image could not be saved.');
        }

        return $filename;
    }

    /**
     * Deletes an image file from the folder (does nothing if it is not there).
     */
    public static function deleteImage(string $folder, string $filename): void
    {
        if ($filename === '') {
            return;
        }

        $path = $folder . '/' . basename($filename);
        if (is_file($path)) {
            unlink($path);
        }
    }

    /**
     * Returns the first part of a long text, for the blog list page.
     */
    public static function excerpt(string $text, int $length = 120): string
    {
        if (mb_strlen($text) <= $length) {
            return $text;
        }

        return mb_substr($text, 0, $length) . '...';
    }
}
```

What to notice:

- `namespace App\Helpers;` tells Onebuild where the file lives. The namespace `App\Helpers` means the folder `app/Helpers`. Because of this, you never have to write `require` for your own classes: Onebuild finds them by name.
- `static` means you can use the tool directly, without making an object first: `BlogHelper::makeSlug('Hello')`.
- `throw new Exception('...')` rings an alarm that carries a message. The controller will catch it and show the message to the user.
- Images are saved in `public/uploads/images`. That folder already exists in Onebuild and already blocks the running of any program placed inside it, which keeps uploads safe.

Run the typo detector: `php -l app/Helpers/BlogHelper.php`

## Part 3: The models

A model is the only place that talks to the database. Both of ours inherit all the free methods (`findAll`, `findById`, `insert`, `update`, `delete`) and add a few of their own.

**FILE: `app/Models/CategoryModel.php`  (NEW FILE)**

```php
<?php

namespace App\Models;

use App\Core\Model;

class CategoryModel extends Model
{
    protected string $table = 'categories';

    /**
     * Is this slug already used by another category?
     */
    public function slugExists(string $slug, int $ignoreId = 0): bool
    {
        $row = $this->queryOne(
            "SELECT id FROM categories WHERE slug = ? AND id != ? LIMIT 1",
            [$slug, $ignoreId]
        );

        return $row !== null;
    }

    /**
     * How many articles belong to this category?
     */
    public function countArticles(int $categoryId): int
    {
        $row = $this->queryOne(
            "SELECT COUNT(*) AS total FROM articles WHERE category_id = ?",
            [$categoryId]
        );

        return (int) $row['total'];
    }
}
```

**FILE: `app/Models/ArticleModel.php`  (NEW FILE)**

```php
<?php

namespace App\Models;

use App\Core\Model;

class ArticleModel extends Model
{
    protected string $table = 'articles';

    /**
     * Is this slug already used by another article?
     */
    public function slugExists(string $slug, int $ignoreId = 0): bool
    {
        $row = $this->queryOne(
            "SELECT id FROM articles WHERE slug = ? AND id != ? LIMIT 1",
            [$slug, $ignoreId]
        );

        return $row !== null;
    }

    /**
     * All articles, newest first, together with their category name.
     */
    public function getAllWithCategory(): array
    {
        $sql = "SELECT articles.*, categories.name AS category_name
                FROM articles
                LEFT JOIN categories ON articles.category_id = categories.id
                ORDER BY articles.id DESC";

        return $this->query($sql);
    }

    /**
     * One article (found by its slug) together with its category name.
     */
    public function findWithCategoryBySlug(string $slug): ?array
    {
        $sql = "SELECT articles.*, categories.name AS category_name
                FROM articles
                LEFT JOIN categories ON articles.category_id = categories.id
                WHERE articles.slug = ?
                LIMIT 1";

        return $this->queryOne($sql, [$slug]);
    }
}
```

What to notice:

- `extends Model` means "start with everything a normal Onebuild model can do".
- `protected string $table = 'articles';` tells the model which table it manages.
- Each `?` in the SQL is a blank that is filled safely with the matching value from the list `[...]`. **Never paste user text directly into SQL.** The `?` blanks are what protect your site from SQL injection (a classic hacking trick).
- `LEFT JOIN` borrows the category name from the other table, so the list page can show a name instead of a number.
- `?array` means "returns an array, or `null` when nothing was found".

Run the typo detector on both files.

## Part 4: Shared layout pieces

Every admin page needs the same frame: header, top menu, sidebar, and a footer. Instead of repeating 15 lines in eight files, we make two small files: one **opens** the frame, one **closes** it. Each page puts its own content in between, like a picture in a picture frame.

**FILE: `views/layouts/admin_top.php`  (NEW FILE)**

```php
<?php
// Opens every admin page: header + top menu + sidebar + start of the main area.
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/navbar.php';
?>
<link rel="stylesheet" href="<?= $base ?>/assets/css/dashboard.css" nonce="<?= $nonce ?>">

<div class="container-fluid">
    <div class="row">
        <?php require_once __DIR__ . '/../dashboard/_sidebar.php'; ?>

        <section class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><?= htmlspecialchars($pageTitle) ?></h1>
            </div>
```

**FILE: `views/layouts/admin_bottom.php`  (NEW FILE)**

```php
        </section>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
```

What to notice:

- `require_once` means "paste that file here".
- `<?= $base ?>` prints your website's base address. Put it in front of every link and every file path, so the site works no matter which folder or domain it runs on.
- `<?= htmlspecialchars(...) ?>` prints text safely. **Always** wrap text that comes from the database or from a visitor in `htmlspecialchars()`. Without it a visitor could inject harmful code into your page.
- `$nonce` is a one-time pass that Onebuild's security rules require on every inline `<script>` and `<link>`. Remember this: **an inline `<script>` without `nonce="<?= $nonce ?>"` is blocked by the browser.**

**Step 4.3** Add two links to the sidebar.

**FILE: `views/dashboard/_sidebar.php`  (EDIT FILE)**

Open the file and find this existing block (the "Overview" menu item):

```php
            <li class="nav-item">
                <a class="nav-link <?= $currentPage === 'index' ? 'active' : '' ?>" href="<?= $base ?>/dashboard">
                    <i class="bi bi-speedometer2"></i> Overview
                </a>
            </li>
```

Directly **under** that block (after its closing `</li>`) add:

```php
            <li class="nav-item">
                <a class="nav-link <?= $currentPage === 'categories' ? 'active' : '' ?>" href="<?= $base ?>/dashboard/categories">
                    <i class="bi bi-tags"></i> Categories
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link <?= $currentPage === 'articles' ? 'active' : '' ?>" href="<?= $base ?>/dashboard/articles">
                    <i class="bi bi-journal-text"></i> Articles
                </a>
            </li>
```

(The `? 'active' : ''` part copies the style already used in that file: it adds the word `active` to highlight the menu item of the page you are on.)

**CHECKPOINT 4.** Sign in and open `/dashboard`. The sidebar now shows **Categories** and **Articles**. Clicking them gives a "404" page for now. That is correct: you have not made those pages yet.

## Part 5: Category CRUD

We build the complete cycle for categories first because it is the smallest one. Articles (Part 6) use exactly the same pattern, with a few extras.

**Step 5.1** The controller. One class with six actions you can see in the browser (`index`, `create`, `store`, `edit`, `update`, `delete`) plus two helper methods that tidy up and check the form data.

**FILE: `app/Controllers/AdminCategoryController.php`  (NEW FILE)**

```php
<?php

namespace App\Controllers;

use PDO;
use App\Core\Controller;
use App\Core\CsrfProtection;
use App\Core\Validator;
use App\Helpers\BlogHelper;
use App\Models\CategoryModel;

class AdminCategoryController extends Controller
{
    private CategoryModel $categoryModel;

    public function __construct(PDO $pdo)
    {
        parent::__construct($pdo);
        $this->categoryModel = new CategoryModel($pdo);
    }

    /**
     * Page: list of all categories
     */
    public function index(): void
    {
        $this->requireAdmin();

        $categories = $this->categoryModel->findAll('name ASC');

        $this->view('admin/categories/index', [
            'pageTitle'   => 'Categories',
            'currentPage' => 'categories',
            'categories'  => $categories,
        ]);
    }

    /**
     * Page: empty form to add a category
     */
    public function create(): void
    {
        $this->requireAdmin();

        $this->view('admin/categories/create', [
            'pageTitle'   => 'New Category',
            'currentPage' => 'categories',
            'category'    => ['name' => '', 'slug' => ''],
        ]);
    }

    /**
     * Action: save the new category (the form sends data here)
     */
    public function store(): void
    {
        $this->requireAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/dashboard/categories');
            return;
        }
        CsrfProtection::requireValidToken();

        $category = $this->readForm();
        $error = $this->validateCategory($category, 0);

        if ($error !== '') {
            $_SESSION['error'] = $error;
            $this->view('admin/categories/create', [
                'pageTitle'   => 'New Category',
                'currentPage' => 'categories',
                'category'    => $category,
            ]);
            return;
        }

        $this->categoryModel->insert([
            'name' => $category['name'],
            'slug' => $category['slug'],
        ]);

        $_SESSION['success'] = 'Category created.';
        $this->redirect('/dashboard/categories');
    }

    /**
     * Page: form filled with the current values of one category
     */
    public function edit(int $id): void
    {
        $this->requireAdmin();

        $category = $this->categoryModel->findById($id);
        if ($category === null) {
            $_SESSION['error'] = 'Category not found.';
            $this->redirect('/dashboard/categories');
            return;
        }

        $this->view('admin/categories/edit', [
            'pageTitle'   => 'Edit Category',
            'currentPage' => 'categories',
            'category'    => $category,
        ]);
    }

    /**
     * Action: save the changes (the edit form sends data here)
     */
    public function update(int $id): void
    {
        $this->requireAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/dashboard/categories');
            return;
        }
        CsrfProtection::requireValidToken();

        $existing = $this->categoryModel->findById($id);
        if ($existing === null) {
            $_SESSION['error'] = 'Category not found.';
            $this->redirect('/dashboard/categories');
            return;
        }

        $category = $this->readForm();
        $category['id'] = $id;
        $error = $this->validateCategory($category, $id);

        if ($error !== '') {
            $_SESSION['error'] = $error;
            $this->view('admin/categories/edit', [
                'pageTitle'   => 'Edit Category',
                'currentPage' => 'categories',
                'category'    => $category,
            ]);
            return;
        }

        $this->categoryModel->update($id, [
            'name' => $category['name'],
            'slug' => $category['slug'],
        ]);

        $_SESSION['success'] = 'Category updated.';
        $this->redirect('/dashboard/categories');
    }

    /**
     * Action: delete one category
     */
    public function delete(int $id): void
    {
        $this->requireAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/dashboard/categories');
            return;
        }
        CsrfProtection::requireValidToken();

        $category = $this->categoryModel->findById($id);
        if ($category === null) {
            $_SESSION['error'] = 'Category not found.';
            $this->redirect('/dashboard/categories');
            return;
        }

        $total = $this->categoryModel->countArticles($id);
        if ($total > 0) {
            $_SESSION['error'] = 'This category still has ' . $total . ' article(s). Move or delete them first.';
            $this->redirect('/dashboard/categories');
            return;
        }

        $this->categoryModel->delete($id);

        $_SESSION['success'] = 'Category deleted.';
        $this->redirect('/dashboard/categories');
    }

    /**
     * Reads the form fields and cleans them up.
     */
    private function readForm(): array
    {
        $name = Validator::sanitize($_POST['name'] ?? '', 100);
        $slug = Validator::sanitize($_POST['slug'] ?? '', 100);

        if ($slug === '') {
            $slug = $name;
        }
        $slug = BlogHelper::makeSlug($slug);

        return ['name' => $name, 'slug' => $slug];
    }

    /**
     * Checks the data. Returns an error message, or '' when everything is fine.
     */
    private function validateCategory(array $category, int $ignoreId): string
    {
        if ($category['name'] === '') {
            return 'Category name is required.';
        }

        if ($this->categoryModel->slugExists($category['slug'], $ignoreId)) {
            return 'This slug is already used by another category. Please type a different slug.';
        }

        return '';
    }
}
```

How to read it:

1. `__construct` runs when Onebuild creates the controller. `parent::__construct($pdo)` hands the database connection to the Onebuild base controller. Then we create our model.
2. The first line of every action is `$this->requireAdmin();`. Anybody who is not logged in as admin or root is sent to the login page. **Forget this line and that page is open to the whole world.**
3. `store`, `update` and `delete` receive a form, so they check two things first: the request really is a POST, and the secret CSRF code is correct (`CsrfProtection::requireValidToken()`). A wrong or missing code stops everything with error 419.
4. `$_SESSION['success'] = '...'` stores a flash message. `$this->redirect(...)` then sends the browser back to the list, where the message appears once. Redirecting after saving also means that pressing the Refresh key will not save twice.
5. When the data is wrong we do **not** redirect. We show the form again with what the user already typed (`'category' => $category`), so nothing has to be retyped.
6. `readForm()` collects and cleans the typed values. `Validator::sanitize()` is a Onebuild tool that trims spaces, removes HTML tags and cuts the text to a maximum length. `$_POST['name'] ?? ''` means "the field `name`, or an empty text if it is missing". If the slug field is empty, the name is used to make one.
7. `delete()` refuses to remove a category that still contains articles.
8. The number in `edit(int $id)` comes from the address (`/dashboard/categories/edit/3`). Step 5.3 shows how the route passes it in.

Run the typo detector.

**Step 5.2** The four views. Create the folders `views/admin` and `views/admin/categories` first.

The form is needed twice (create and edit), so we write it once in `_form.php`. A file name that starts with an underscore is a *piece* that other pages paste in. It is not a page by itself.

**FILE: `views/admin/categories/_form.php`  (NEW FILE)**

```php
<form method="POST" action="<?= $formAction ?>">
    <?= \App\Core\CsrfProtection::getHiddenInput() ?>

    <div class="mb-3">
        <label for="name" class="form-label">Category Name</label>
        <input type="text" class="form-control" id="name" name="name"
               value="<?= htmlspecialchars($category['name']) ?>" maxlength="100" required>
    </div>

    <div class="mb-3">
        <label for="slug" class="form-label">Slug (optional)</label>
        <input type="text" class="form-control" id="slug" name="slug"
               value="<?= htmlspecialchars($category['slug']) ?>" maxlength="100">
        <div class="form-text">The word used in the web address. Leave it empty and it is created from the name.</div>
    </div>

    <button type="submit" class="btn btn-primary"><?= $submitLabel ?></button>
    <a href="<?= $base ?>/dashboard/categories" class="btn btn-secondary">Cancel</a>
</form>
```

**FILE: `views/admin/categories/create.php`  (NEW FILE)**

```php
<?php require_once __DIR__ . '/../../layouts/admin_top.php'; ?>

<?php
$formAction  = $base . '/dashboard/categories/store';
$submitLabel = 'Save Category';
?>

<div class="row">
    <div class="col-lg-6">
        <?php require __DIR__ . '/_form.php'; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../layouts/admin_bottom.php'; ?>
```

**FILE: `views/admin/categories/edit.php`  (NEW FILE)**

```php
<?php require_once __DIR__ . '/../../layouts/admin_top.php'; ?>

<?php
$formAction  = $base . '/dashboard/categories/update/' . (int) $category['id'];
$submitLabel = 'Update Category';
?>

<div class="row">
    <div class="col-lg-6">
        <?php require __DIR__ . '/_form.php'; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../layouts/admin_bottom.php'; ?>
```

**FILE: `views/admin/categories/index.php`  (NEW FILE)**

```php
<?php require_once __DIR__ . '/../../layouts/admin_top.php'; ?>

<div class="mb-3">
    <a href="<?= $base ?>/dashboard/categories/create" class="btn btn-primary">Add New Category</a>
</div>

<div class="table-responsive">
    <table class="table table-bordered table-striped align-middle">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Slug</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($categories)): ?>
                <tr>
                    <td colspan="4" class="text-center text-muted">No categories yet.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($categories as $cat): ?>
                    <tr>
                        <td><?= (int) $cat['id'] ?></td>
                        <td><?= htmlspecialchars($cat['name']) ?></td>
                        <td><?= htmlspecialchars($cat['slug']) ?></td>
                        <td>
                            <a href="<?= $base ?>/dashboard/categories/edit/<?= (int) $cat['id'] ?>" class="btn btn-sm btn-warning">Edit</a>

                            <form method="POST" action="<?= $base ?>/dashboard/categories/delete/<?= (int) $cat['id'] ?>"
                                  class="d-inline js-confirm" data-message="Delete this category?">
                                <?= \App\Core\CsrfProtection::getHiddenInput() ?>
                                <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script nonce="<?= $nonce ?>">
// Ask "Are you sure?" before any Delete button sends its form.
document.querySelectorAll('.js-confirm').forEach(function (form) {
    form.addEventListener('submit', function (event) {
        if (!confirm(form.dataset.message)) {
            event.preventDefault();
        }
    });
});
</script>

<?php require_once __DIR__ . '/../../layouts/admin_bottom.php'; ?>
```

What to notice in the views:

- `<?php if (...): ?> ... <?php else: ?> ... <?php endif; ?>` and `<?php foreach (...): ?> ... <?php endforeach; ?>` are PHP's way of writing "if" and "repeat for each row" in the middle of HTML. Every `if` and `foreach` must be closed by its `endif` and `endforeach`.
- `<?= \App\Core\CsrfProtection::getHiddenInput() ?>` prints the hidden secret code. **Every form needs it**, or the controller answers with error 419.
- **Delete is a tiny form with a button, not a plain link.** A plain link can be triggered by a trick page on another website. A form with the secret code cannot.
- The little `<script>` at the bottom shows the "Are you sure?" popup. It is plain JavaScript (no jQuery needed) and it carries `nonce="<?= $nonce ?>"`, which Onebuild's security rules demand.
- There is no `style="..."` anywhere. Onebuild's security rules block inline styles, so we use Bootstrap's ready-made classes (`mb-3`, `col-lg-6`, `d-inline` and so on) instead.

**Step 5.3** Tell Onebuild which address belongs to which method. This is the route list.

**FILE: `public/index.php`  (EDIT FILE)**

Scroll down to the end of the file and find these lines:

```php
// Protected route example
$router->addRoute('dashboard', 'DashboardController', 'index');
$router->addRoute('dashboard/profile', 'DashboardController', 'profile');

// Dispatch request - FIXED FOR SUBDIRECTORY
```

Add the following block **between** the `dashboard/profile` line and the `// Dispatch request` comment:

```php
// Admin: categories
$router->addRoute('dashboard/categories', 'AdminCategoryController', 'index');
$router->addRoute('dashboard/categories/create', 'AdminCategoryController', 'create');
$router->addRoute('dashboard/categories/store', 'AdminCategoryController', 'store');
$router->addRoute('dashboard/categories/edit/([0-9]+)', 'AdminCategoryController', 'edit');
$router->addRoute('dashboard/categories/update/([0-9]+)', 'AdminCategoryController', 'update');
$router->addRoute('dashboard/categories/delete/([0-9]+)', 'AdminCategoryController', 'delete');

```

How a route reads: `addRoute('address', 'ControllerName', 'methodName')`. The address has no slash at the start. `([0-9]+)` means "one or more digits", and whatever digits are found are handed to the method: the address `dashboard/categories/edit/3` calls `edit(3)`.

**CHECKPOINT 5. Try everything.**

1. Open a private/incognito browser window and visit `/dashboard/categories`. You must be sent to the login page. (This proves the admin lock works.)
2. In your normal window, click **Categories** in the sidebar. You see the table with "No categories yet."
3. Click **Add New Category**. Type `Technology News`, leave Slug empty, click **Save Category**. You return to the list with a green message, and the slug shows `technology-news`.
4. Add `Technology News` again. This time you stay on the form, see a red message that the slug is already used, and your typed text is still there.
5. Click **Edit** on the row. Change the name to `Tech` and the slug to `tech`. Click **Update Category**.
6. Add a category named `Temporary`. Click its **Delete** button. A popup asks "Delete this category?". Click Cancel: nothing happens. Click Delete again and then OK: it disappears.

Keep the `Tech` category for the next Part. If the popup does not appear when you click Delete, the `nonce="<?= $nonce ?>"` part of the script line is probably mistyped.

## Part 6: Article CRUD

Articles follow the very same pattern as categories. Three things are new:

- a **drop-down list** to pick the category,
- a **picture upload** (converted to WebP and saved in `public/uploads/images`),
- on edit, the old picture is **kept** unless you choose a new one (and the old file is removed when replaced or when the article is deleted).

**Step 6.1** The controller.

**FILE: `app/Controllers/AdminArticleController.php`  (NEW FILE)**

```php
<?php

namespace App\Controllers;

use PDO;
use Exception;
use App\Core\Controller;
use App\Core\CsrfProtection;
use App\Core\Validator;
use App\Helpers\BlogHelper;
use App\Models\ArticleModel;
use App\Models\CategoryModel;

class AdminArticleController extends Controller
{
    private ArticleModel $articleModel;
    private CategoryModel $categoryModel;

    public function __construct(PDO $pdo)
    {
        parent::__construct($pdo);
        $this->articleModel  = new ArticleModel($pdo);
        $this->categoryModel = new CategoryModel($pdo);
    }

    /**
     * Page: list of all articles
     */
    public function index(): void
    {
        $this->requireAdmin();

        $articles = $this->articleModel->getAllWithCategory();

        $this->view('admin/articles/index', [
            'pageTitle'   => 'Articles',
            'currentPage' => 'articles',
            'articles'    => $articles,
        ]);
    }

    /**
     * Page: empty form to write a new article
     */
    public function create(): void
    {
        $this->requireAdmin();

        $categories = $this->categoryModel->findAll('name ASC');
        if (empty($categories)) {
            $_SESSION['error'] = 'Please create a category first.';
            $this->redirect('/dashboard/categories');
            return;
        }

        $this->view('admin/articles/create', [
            'pageTitle'   => 'New Article',
            'currentPage' => 'articles',
            'categories'  => $categories,
            'article'     => [
                'title'       => '',
                'slug'        => '',
                'category_id' => 0,
                'content'     => '',
                'image'       => '',
            ],
        ]);
    }

    /**
     * Action: save the new article
     */
    public function store(): void
    {
        $this->requireAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/dashboard/articles');
            return;
        }
        CsrfProtection::requireValidToken();

        $article = $this->readForm();
        $article['image'] = '';
        $error = $this->validateArticle($article, 0);

        $newImage = '';
        if ($error === '') {
            try {
                $newImage = $this->handleUpload();
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        }

        if ($error !== '') {
            $_SESSION['error'] = $error;
            $this->view('admin/articles/create', [
                'pageTitle'   => 'New Article',
                'currentPage' => 'articles',
                'categories'  => $this->categoryModel->findAll('name ASC'),
                'article'     => $article,
            ]);
            return;
        }

        $this->articleModel->insert([
            'category_id' => $article['category_id'],
            'title'       => $article['title'],
            'slug'        => $article['slug'],
            'content'     => $article['content'],
            'image'       => $newImage,
        ]);

        $_SESSION['success'] = 'Article published.';
        $this->redirect('/dashboard/articles');
    }

    /**
     * Page: form filled with the current values of one article
     */
    public function edit(int $id): void
    {
        $this->requireAdmin();

        $article = $this->articleModel->findById($id);
        if ($article === null) {
            $_SESSION['error'] = 'Article not found.';
            $this->redirect('/dashboard/articles');
            return;
        }

        $this->view('admin/articles/edit', [
            'pageTitle'   => 'Edit Article',
            'currentPage' => 'articles',
            'categories'  => $this->categoryModel->findAll('name ASC'),
            'article'     => $article,
        ]);
    }

    /**
     * Action: save the changes
     */
    public function update(int $id): void
    {
        $this->requireAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/dashboard/articles');
            return;
        }
        CsrfProtection::requireValidToken();

        $existing = $this->articleModel->findById($id);
        if ($existing === null) {
            $_SESSION['error'] = 'Article not found.';
            $this->redirect('/dashboard/articles');
            return;
        }

        $article = $this->readForm();
        $article['id'] = $id;
        $article['image'] = $existing['image'];
        $error = $this->validateArticle($article, $id);

        $newImage = '';
        if ($error === '') {
            try {
                $newImage = $this->handleUpload();
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        }

        if ($error !== '') {
            $_SESSION['error'] = $error;
            $this->view('admin/articles/edit', [
                'pageTitle'   => 'Edit Article',
                'currentPage' => 'articles',
                'categories'  => $this->categoryModel->findAll('name ASC'),
                'article'     => $article,
            ]);
            return;
        }

        $imageName = $existing['image'];
        if ($newImage !== '') {
            BlogHelper::deleteImage(BlogHelper::imageFolder(), (string) $existing['image']);
            $imageName = $newImage;
        }

        $this->articleModel->update($id, [
            'category_id' => $article['category_id'],
            'title'       => $article['title'],
            'slug'        => $article['slug'],
            'content'     => $article['content'],
            'image'       => $imageName,
        ]);

        $_SESSION['success'] = 'Article updated.';
        $this->redirect('/dashboard/articles');
    }

    /**
     * Action: delete one article (and its image file)
     */
    public function delete(int $id): void
    {
        $this->requireAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/dashboard/articles');
            return;
        }
        CsrfProtection::requireValidToken();

        $article = $this->articleModel->findById($id);
        if ($article === null) {
            $_SESSION['error'] = 'Article not found.';
            $this->redirect('/dashboard/articles');
            return;
        }

        BlogHelper::deleteImage(BlogHelper::imageFolder(), (string) $article['image']);
        $this->articleModel->delete($id);

        $_SESSION['success'] = 'Article deleted.';
        $this->redirect('/dashboard/articles');
    }

    /**
     * Reads the form fields and cleans them up.
     */
    private function readForm(): array
    {
        $title      = Validator::sanitize($_POST['title'] ?? '', 255);
        $slug       = Validator::sanitize($_POST['slug'] ?? '', 255);
        $categoryId = (int) ($_POST['category_id'] ?? 0);
        $content    = trim($_POST['content'] ?? '');

        if ($slug === '') {
            $slug = $title;
        }
        $slug = BlogHelper::makeSlug($slug);

        return [
            'title'       => $title,
            'slug'        => $slug,
            'category_id' => $categoryId,
            'content'     => $content,
        ];
    }

    /**
     * Checks the data. Returns an error message, or '' when everything is fine.
     */
    private function validateArticle(array $article, int $ignoreId): string
    {
        if ($article['title'] === '') {
            return 'Title is required.';
        }

        if ($this->categoryModel->findById($article['category_id']) === null) {
            return 'Please choose a category.';
        }

        if ($article['content'] === '') {
            return 'Content is required.';
        }

        if (strlen($article['content']) > 60000) {
            return 'Content is too long.';
        }

        if ($this->articleModel->slugExists($article['slug'], $ignoreId)) {
            return 'This slug is already used by another article. Please type a different slug.';
        }

        return '';
    }

    /**
     * Saves the uploaded image (if any) and returns its file name.
     * Returns '' when no file was chosen. Throws an Exception on problems.
     */
    private function handleUpload(): string
    {
        if (!isset($_FILES['image'])) {
            return '';
        }

        $code = $_FILES['image']['error'];

        if ($code === UPLOAD_ERR_NO_FILE) {
            return '';
        }

        if ($code !== UPLOAD_ERR_OK) {
            throw new Exception('The image could not be uploaded (error code ' . $code . ').');
        }

        return BlogHelper::saveImageAsWebp($_FILES['image'], BlogHelper::imageFolder());
    }
}
```

What is new compared to the category controller:

- `try { ... } catch (Exception $e) { ... }` means "try this; if the helper rings its alarm (`throw`), do not crash, run the catch part instead". Here the catch part stores the alarm's message (`$e->getMessage()`) so it can be shown in red.
- `handleUpload()` looks at `$_FILES['image']`, PHP's box holding the uploaded picture. It returns the new file name, or an empty text when no picture was chosen.
- In `update()`: no new picture means the old one stays. A new picture replaces the old one, and the old file is deleted so your folder does not fill up with unused pictures.
- `create()` sends you to the Categories page when there is no category yet, because an article needs one.
- `(int) $_POST['category_id']` forces the value to be a whole number. Never trust what arrives from a browser.

Run the typo detector.

**Step 6.2** The four views. Create the folder `views/admin/articles` first.

**FILE: `views/admin/articles/_form.php`  (NEW FILE)**

```php
<form method="POST" action="<?= $formAction ?>" enctype="multipart/form-data">
    <?= \App\Core\CsrfProtection::getHiddenInput() ?>

    <div class="mb-3">
        <label for="title" class="form-label">Title</label>
        <input type="text" class="form-control" id="title" name="title"
               value="<?= htmlspecialchars($article['title']) ?>" maxlength="255" required>
    </div>

    <div class="mb-3">
        <label for="slug" class="form-label">Slug (optional)</label>
        <input type="text" class="form-control" id="slug" name="slug"
               value="<?= htmlspecialchars($article['slug']) ?>" maxlength="255">
        <div class="form-text">The word used in the web address. Leave it empty and it is created from the title.</div>
    </div>

    <div class="mb-3">
        <label for="category_id" class="form-label">Category</label>
        <select class="form-select" id="category_id" name="category_id" required>
            <option value="">-- Choose a category --</option>
            <?php foreach ($categories as $cat): ?>
                <?php
                $selected = '';
                if ((int) $cat['id'] === (int) $article['category_id']) {
                    $selected = 'selected';
                }
                ?>
                <option value="<?= (int) $cat['id'] ?>" <?= $selected ?>><?= htmlspecialchars($cat['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="mb-3">
        <label for="image" class="form-label">Featured Image (JPG, PNG or WebP, max 2 MB)</label>
        <?php if (!empty($article['image'])): ?>
            <div class="mb-2">
                <img src="<?= $base ?>/uploads/images/<?= htmlspecialchars($article['image']) ?>"
                     class="img-thumbnail" width="150" alt="Current image">
                <div class="form-text">Current image. Choose a new file only if you want to replace it.</div>
            </div>
        <?php endif; ?>
        <input type="file" class="form-control" id="image" name="image" accept="image/jpeg,image/png,image/webp">
    </div>

    <div class="mb-3">
        <label for="content" class="form-label">Content</label>
        <textarea class="form-control" id="content" name="content" rows="10" required><?= htmlspecialchars($article['content']) ?></textarea>
    </div>

    <button type="submit" class="btn btn-primary"><?= $submitLabel ?></button>
    <a href="<?= $base ?>/dashboard/articles" class="btn btn-secondary">Cancel</a>
</form>
```

**FILE: `views/admin/articles/create.php`  (NEW FILE)**

```php
<?php require_once __DIR__ . '/../../layouts/admin_top.php'; ?>

<?php
$formAction  = $base . '/dashboard/articles/store';
$submitLabel = 'Publish Article';
?>

<div class="row">
    <div class="col-lg-8">
        <?php require __DIR__ . '/_form.php'; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../layouts/admin_bottom.php'; ?>
```

**FILE: `views/admin/articles/edit.php`  (NEW FILE)**

```php
<?php require_once __DIR__ . '/../../layouts/admin_top.php'; ?>

<?php
$formAction  = $base . '/dashboard/articles/update/' . (int) $article['id'];
$submitLabel = 'Update Article';
?>

<div class="row">
    <div class="col-lg-8">
        <?php require __DIR__ . '/_form.php'; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../layouts/admin_bottom.php'; ?>
```

**FILE: `views/admin/articles/index.php`  (NEW FILE)**

```php
<?php require_once __DIR__ . '/../../layouts/admin_top.php'; ?>

<div class="mb-3">
    <a href="<?= $base ?>/dashboard/articles/create" class="btn btn-primary">Write New Article</a>
</div>

<div class="table-responsive">
    <table class="table table-bordered table-striped align-middle">
        <thead>
            <tr>
                <th>Image</th>
                <th>Title</th>
                <th>Category</th>
                <th>Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($articles)): ?>
                <tr>
                    <td colspan="5" class="text-center text-muted">No articles yet.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($articles as $art): ?>
                    <tr>
                        <td>
                            <?php if (!empty($art['image'])): ?>
                                <img src="<?= $base ?>/uploads/images/<?= htmlspecialchars($art['image']) ?>"
                                     width="60" class="img-thumbnail" alt="Thumbnail">
                            <?php else: ?>
                                <span class="badge bg-secondary">No image</span>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($art['title']) ?></td>
                        <td><?= htmlspecialchars($art['category_name'] ?? 'Uncategorized') ?></td>
                        <td><?= htmlspecialchars(date('d M Y', strtotime($art['created_at']))) ?></td>
                        <td>
                            <a href="<?= $base ?>/blog/<?= htmlspecialchars($art['slug']) ?>" class="btn btn-sm btn-info" target="_blank">View</a>
                            <a href="<?= $base ?>/dashboard/articles/edit/<?= (int) $art['id'] ?>" class="btn btn-sm btn-warning">Edit</a>

                            <form method="POST" action="<?= $base ?>/dashboard/articles/delete/<?= (int) $art['id'] ?>"
                                  class="d-inline js-confirm" data-message="Delete this article?">
                                <?= \App\Core\CsrfProtection::getHiddenInput() ?>
                                <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script nonce="<?= $nonce ?>">
// Ask "Are you sure?" before any Delete button sends its form.
document.querySelectorAll('.js-confirm').forEach(function (form) {
    form.addEventListener('submit', function (event) {
        if (!confirm(form.dataset.message)) {
            event.preventDefault();
        }
    });
});
</script>

<?php require_once __DIR__ . '/../../layouts/admin_bottom.php'; ?>
```

What is new in these views:

- A form that uploads a file **must** have `enctype="multipart/form-data"` in its `<form>` tag. Without it the picture never arrives.
- The drop-down: for every category we print an `<option>`. The one whose number matches the article's category gets the word `selected`, so the right choice is pre-picked when you edit.
- The "View" button on the list opens the public page of the article (you build it in Part 7).

**Step 6.3** Add the article routes.

**FILE: `public/index.php`  (EDIT FILE)**

Directly under the categories block you added in Step 5.3 (and still above the `// Dispatch request` comment), add:

```php
// Admin: articles
$router->addRoute('dashboard/articles', 'AdminArticleController', 'index');
$router->addRoute('dashboard/articles/create', 'AdminArticleController', 'create');
$router->addRoute('dashboard/articles/store', 'AdminArticleController', 'store');
$router->addRoute('dashboard/articles/edit/([0-9]+)', 'AdminArticleController', 'edit');
$router->addRoute('dashboard/articles/update/([0-9]+)', 'AdminArticleController', 'update');
$router->addRoute('dashboard/articles/delete/([0-9]+)', 'AdminArticleController', 'delete');

```

**CHECKPOINT 6. Try everything.**

1. Click **Articles** in the sidebar. You see "No articles yet."
2. Click **Write New Article**. Title: `My First Post!`. Category: `Tech`. Choose a small JPG or PNG picture. Content: type two short lines. Click **Publish Article**. You return to the list with a thumbnail and a green message.
3. Look inside the folder `public/uploads/images`. You find one new file whose name ends with `.webp`. That is your converted picture.
4. Write another article with the same title. You get the red "slug already used" message and your text is kept.
5. Try to upload a text file that you renamed to `picture.jpg`. You get "not a valid image".
6. Click **Edit** on the first article. Change the title, choose **no** new picture, click **Update Article**. The thumbnail is still there.
7. Edit it again, this time choose a different picture. Check the folder: there is still exactly one `.webp` file (the old one was removed).
8. Go to **Categories** and try to delete `Tech`. You get a red message: the category still has an article.
9. Back in **Articles**, make sure you end with **two articles** in the `Tech` category (one with a picture, one without). You need them for Part 7. You can test **Delete** on a third, temporary article.

## Part 7: The public blog pages

These are the pages ordinary visitors see. There is **no** `requireAdmin()` here on purpose: everybody may read. Only two actions are needed: a list (`index`) and one full article (`show`).

**Step 7.1** The controller.

**FILE: `app/Controllers/BlogController.php`  (NEW FILE)**

```php
<?php

namespace App\Controllers;

use PDO;
use App\Core\Controller;
use App\Models\ArticleModel;

class BlogController extends Controller
{
    private ArticleModel $articleModel;

    public function __construct(PDO $pdo)
    {
        parent::__construct($pdo);
        $this->articleModel = new ArticleModel($pdo);
    }

    /**
     * Public page: list of all articles
     */
    public function index(): void
    {
        $articles = $this->articleModel->getAllWithCategory();

        $this->view('blog/index', [
            'pageTitle'       => 'Blog',
            'metaDescription' => 'Latest articles',
            'articles'        => $articles,
        ]);
    }

    /**
     * Public page: one full article
     */
    public function show(string $slug): void
    {
        $article = $this->articleModel->findWithCategoryBySlug($slug);

        if ($article === null) {
            http_response_code(404);
            $this->view('errors/404', ['pageTitle' => 'Article not found']);
            return;
        }

        $this->view('blog/show', [
            'pageTitle'       => $article['title'],
            'metaDescription' => 'Read: ' . $article['title'],
            'article'         => $article,
        ]);
    }
}
```

If no article matches the address, we answer with Onebuild's existing 404 page and the proper "404" code. The `pageTitle` text becomes the title in the browser tab.

**Step 7.2** The two views. Create the folder `views/blog` first. These pages use Onebuild's normal public frame (header, navbar, footer), exactly like `views/home/index.php` does.

**FILE: `views/blog/index.php`  (NEW FILE)**

```php
<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<?php require_once __DIR__ . '/../layouts/navbar.php'; ?>

<div class="container">
    <h1 class="mb-4">Latest Articles</h1>

    <?php if (empty($articles)): ?>
        <p class="text-muted">No articles have been published yet.</p>
    <?php else: ?>
        <div class="row">
            <?php foreach ($articles as $art): ?>
                <div class="col-md-4 mb-4">
                    <div class="card h-100 shadow-sm">
                        <?php if (!empty($art['image'])): ?>
                            <img src="<?= $base ?>/uploads/images/<?= htmlspecialchars($art['image']) ?>"
                                 class="card-img-top" alt="<?= htmlspecialchars($art['title']) ?>">
                        <?php endif; ?>
                        <div class="card-body">
                            <span class="badge bg-primary mb-2"><?= htmlspecialchars($art['category_name'] ?? 'General') ?></span>
                            <h5 class="card-title"><?= htmlspecialchars($art['title']) ?></h5>
                            <p class="card-text text-muted"><?= htmlspecialchars(\App\Helpers\BlogHelper::excerpt($art['content'], 120)) ?></p>
                        </div>
                        <div class="card-footer bg-white border-0 pb-3">
                            <a href="<?= $base ?>/blog/<?= htmlspecialchars($art['slug']) ?>" class="btn btn-outline-primary btn-sm">Read more</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
```

**FILE: `views/blog/show.php`  (NEW FILE)**

```php
<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<?php require_once __DIR__ . '/../layouts/navbar.php'; ?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <a href="<?= $base ?>/blog" class="btn btn-outline-secondary btn-sm mb-3">&larr; Back to articles</a>

            <span class="badge bg-info text-dark mb-2"><?= htmlspecialchars($article['category_name'] ?? 'General') ?></span>
            <h1 class="fw-bold"><?= htmlspecialchars($article['title']) ?></h1>
            <p class="text-muted small">Published on <?= htmlspecialchars(date('d M Y', strtotime($article['created_at']))) ?></p>

            <?php if (!empty($article['image'])): ?>
                <img src="<?= $base ?>/uploads/images/<?= htmlspecialchars($article['image']) ?>"
                     class="img-fluid rounded my-3 w-100" alt="<?= htmlspecialchars($article['title']) ?>">
            <?php endif; ?>

            <div class="mt-4 fs-5">
                <?= nl2br(htmlspecialchars($article['content'])) ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
```

What to notice: `nl2br(htmlspecialchars(...))` first makes the text safe, then turns your line breaks into `<br>` so paragraphs look right. The `??` in `$art['category_name'] ?? 'General'` means "use this value, or `General` if there is none".

**Step 7.3** Add a **Blog** link to the top menu.

**FILE: `views/layouts/navbar.php`  (EDIT FILE)**

Find the Home menu item:

```php
                <li class="nav-item">
                    <a class="nav-link <?= ($requestUri === '' || $requestUri === '/') ? 'active' : '' ?>" href="<?= $base ?>">Home</a>
                </li>
```

Directly **under** it add:

```php
                <li class="nav-item">
                    <a class="nav-link <?= stripos($requestUri, 'blog') === 0 ? 'active' : '' ?>" href="<?= $base ?>/blog">Blog</a>
                </li>
```

**Step 7.4** Add the two public routes.

**FILE: `public/index.php`  (EDIT FILE)**

Under the articles block, still above `// Dispatch request`, add:

```php
// Blog (public pages)
$router->addRoute('blog', 'BlogController', 'index');
$router->addRoute('blog/([a-z0-9-]+)', 'BlogController', 'show');

```

The pattern `([a-z0-9-]+)` means "letters, digits and dashes", which is exactly what a slug is made of. The address `blog/my-first-post` calls `show('my-first-post')`.

**CHECKPOINT 7. The final test.**

1. Open a private/incognito window (you are not logged in) and go to `/blog`. You see a card for each article, with picture, category badge, title and the start of the text.
2. Click **Read more**. You see the full article. The address in the browser ends with the slug, for example `/blog/my-first-post`.
3. Type `<b>hello</b>` inside an article's content and save. On the public page the visible text is literally `<b>hello</b>` and nothing turns bold. That is the `htmlspecialchars` protection working.
4. Visit `/blog/does-not-exist`. You get the 404 page.
5. In the private window visit `/dashboard/articles`. You are sent to the login page.
6. The top menu now has a **Blog** link. Your original home page, login and profile page still work.

**Congratulations. You have built a complete CRUD application.**

## Part 8: What happens when you click "Publish Article"

Read this slowly once. It ties everything together.

1. The browser sends the form (POST) to the address `/dashboard/articles/store`, with your text, the picture and the secret CSRF code.
2. `public/index.php` looks through the route list and finds `dashboard/articles/store`. It creates `AdminArticleController` and calls `store()`.
3. `store()` first runs `requireAdmin()` (are you an admin?) and `requireValidToken()` (is the secret code correct?).
4. `readForm()` collects and cleans the text. `validateArticle()` checks it (title present? real category? slug free?).
5. `handleUpload()` passes the picture to `BlogHelper::saveImageAsWebp()`, which checks that it really is an image, converts it and saves it.
6. `ArticleModel::insert()` (inherited from Onebuild's Model) writes the new row into the `articles` table.
7. The controller stores "Article published." in the session and redirects to `/dashboard/articles`.
8. The list page runs `index()`, asks `ArticleModel` for the rows, hands them to `views/admin/articles/index.php`, and the top menu shows the green message once.

Route, controller, model, view. Every page you will ever build in Onebuild is a variation of this chain.

## When something goes wrong

First: make sure `APP_ENV=development` is set in `.env` (local computer only). Then find your symptom.

**A white page, or "Something went wrong" / Error 500**

PHP found a problem it could not recover from. Run the typo detector (`php -l path/to/file.php`) on the file you edited last. If it says "No syntax errors", the message is hidden in your server's PHP error log (in Laragon: Menu, Apache or Nginx, then the error log). Read the last line.

**"Parse error: syntax error, unexpected ..." with a line number**

A typo. Look at that line **and the line above it**. Usual suspects: a missing `;` at the end of a line, a missing `)` or `}` or `]`, a missing closing quote, `->` typed as `-` or `=>` typed as `=`.

**A page you made shows the 404 page**

Onebuild shows 404 whenever it cannot match a route **or** cannot find the controller class or method. Check, in this order: (1) the route line in `public/index.php` is spelled exactly like the address and has **no** slash at the start; (2) the controller file name equals the class name, capitals included; (3) the method name in the route equals the real method name.

**"Class ... not found" or "View not found"**

The file is in the wrong folder or has the wrong name. `AdminCategoryController` must be in `app/Controllers/`, models in `app/Models/`, the helper in `app/Helpers/`, views under `views/`. Also check the `namespace` line at the top of the file.

**"Table ... doesn't exist" (an SQLSTATE message)**

You ran the SQL of Part 1 in the wrong database. The right one is named in the `DB_NAME` line of `.env`.

**"CSRF token validation failed" (error 419)**

The form is missing the line `<?= \App\Core\CsrfProtection::getHiddenInput() ?>`, or you left the form open for more than an hour (reload the page and try again), or the picture was bigger than your server allows and the browser's whole form was dropped. Try a smaller picture.

**"The image could not be uploaded (error code 1)" or "(error code 2)"**

The picture is larger than the server's limit. Choose a smaller picture, or raise `upload_max_filesize` and `post_max_size` in `php.ini` and restart the server.

**"Call to undefined function imagecreatefromwebp()" or "imagewebp()"**

PHP's picture extension is switched off. In `php.ini` enable `extension=gd` and restart the server (in Laragon: Menu, PHP, Extensions, tick `gd`).

**"Access denied. Admin privileges required."**

You are logged in, but your role is not `root` or `admin`. Open the `users` table in your database tool and look at the `role` column of your user.

**The "Are you sure?" popup does not appear when I click Delete**

The `<script>` line at the bottom of the list view is missing `nonce="<?= $nonce ?>"` or has a typo in it. The browser silently refuses scripts without the nonce.

**Pictures do not show up**

Open the folder `public/uploads/images`: is the `.webp` file there? If the folder is empty on a live server, the folder is probably not writable (give it permission 755).

**I fixed the file but nothing changed**

Save the file (Ctrl+S), then reload the browser page with Ctrl+F5.

## Make your own CRUD: the 10-step recipe

To manage anything else (products, clients, events...), copy the pattern. Example: **Products**.

1. Create the table in the database (`products` with the columns you need).
2. Copy `CategoryModel.php` to `ProductModel.php`. Change the class name, `$table`, and the SQL inside.
3. Copy `AdminCategoryController.php` to `AdminProductController.php`. Change the class name, the model, the view folder names, the address text (`/dashboard/products`) and the messages.
4. Adjust `readForm()` and `validateProduct()` to your fields.
5. Copy the four views of `views/admin/categories/` into `views/admin/products/` and change the fields and the table columns.
6. Add six routes to `public/index.php`.
7. Add a link in `views/dashboard/_sidebar.php` with its own `$currentPage` word.
8. Test: create, edit, delete, empty form, duplicate value, no login.
9. Read your controller once more and ask: is `requireAdmin()` the first line of every action? Does every form carry the CSRF code? Is every printed value wrapped in `htmlspecialchars()`?
10. Before going live, set `APP_ENV=production` in the live `.env`.

## Ideas for practice

- **Draft or published.** Add a column `status` to `articles` (`'draft'` or `'published'`), add a drop-down in the form, and show only published articles on `/blog`. Hint: the base Model already has `findAllPublished()`, which looks for `status = 'published'`.
- **Dashboard numbers.** Show "Total articles" and "Total categories" on the dashboard overview page. Hint: add a `countAll()` method to each model using `queryOne('SELECT COUNT(*) AS total FROM ...')`.
- **Articles per category.** Add the route `blog/category/([a-z0-9-]+)` that lists only the articles of one category.

If you get stuck on any step, tell me the file name and copy the exact error message. That is all I need to help you.
