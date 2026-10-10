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
}