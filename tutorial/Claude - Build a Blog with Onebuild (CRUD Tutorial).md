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
