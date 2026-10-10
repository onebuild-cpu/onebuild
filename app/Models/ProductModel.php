<?php
namespace App\Models;

use App\Core\Model;
use PDO;

class ProductModel extends Model
{
    protected string $table = 'products';

    /** Check if a SKU already exists (for validation) */
    public function skuExists(string $sku, ?int $exceptId = null): bool
    {
        $sql = "SELECT COUNT(*) AS c FROM {$this->table} WHERE sku = :sku";
        $params = ['sku' => $sku];

        if ($exceptId !== null) {
            $sql .= " AND id != :id";
            $params['id'] = $exceptId;
        }

        $row = $this->queryOne($sql, $params);
        return ((int) $row['c']) > 0;
    }

        /**
     * Generate a unique slug from a name.
     * If the base slug is taken, appends -2, -3, etc.
     */
    public function generateSlug(string $name, ?int $exceptId = null): string
    {
        $base = $this->slugify($name);
        if ($base === '') {
            $base = 'product';
        }

        $slug = $base;
        $i = 2;

        while ($this->slugExists($slug, $exceptId)) {
            $slug = $base . '-' . $i;
            $i++;
            if ($i > 500) {
                // safety valve
                $slug = $base . '-' . bin2hex(random_bytes(4));
                break;
            }
        }

        return $slug;
    }

    /** Convert any string into a URL-safe slug. */
    public function slugify(string $text): string
    {
        $text = trim($text);
        $text = mb_strtolower($text, 'UTF-8');
        // Replace non-alphanumeric with dashes
        $text = preg_replace('/[^a-z0-9]+/', '-', $text);
        // Trim dashes from ends
        $text = trim($text, '-');
        // Collapse multiple dashes
        $text = preg_replace('/-+/', '-', $text);
        // Hard cap length
        return substr($text, 0, 280);
    }

    /** Check if a slug is already in use. */
    public function slugExists(string $slug, ?int $exceptId = null): bool
    {
        $sql = "SELECT COUNT(*) AS c FROM {$this->table} WHERE slug = :slug";
        $params = ['slug' => $slug];

        if ($exceptId !== null) {
            $sql .= " AND id != :id";
            $params['id'] = $exceptId;
        }

        $row = $this->queryOne($sql, $params);
        return ((int) $row['c']) > 0;
    }

    /** Fetch a product by its slug (used on the public page). */
    public function findBySlug(string $slug): ?array
    {
        // The base Model already has findBySlug, but it looks for a `slug` column.
        // Since we added it, we can just call the parent method:
        return parent::findBySlug($slug);
    }
}