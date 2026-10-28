<?php
/**
 * Base Model Class
 * Provides common database operations for all models
 */
namespace App\Core;

use PDO;
use Exception;

class Model
{
    protected PDO $pdo;
    protected string $table = '';
    protected bool $timestamps = true;

// Whitelist of allowed table names for security
    // Empty array means allow any valid table name (alphanumeric + underscore)
    protected static array $allowedTables = [];

    public function __construct(PDO $pdo)
    {
        if (!$pdo) {
            throw new Exception('PDO connection is required.');
        }
        $this->pdo = $pdo;
        
        // Validate table name on construction
        if ($this->table && !self::isValidTable($this->table)) {
            throw new Exception("Invalid table name: {$this->table}");
        }
    }

    /**
     * Validate table name against whitelist (if configured)
     */
    protected static function isValidTable(string $table): bool
    {
        // If whitelist is empty, allow any valid table name format
        if (empty(self::$allowedTables)) {
            return true;
        }
        return in_array($table, self::$allowedTables, true);
    }

    /**
     * Validate and sanitize table name for use in queries
     * Only allows alphanumeric and underscore
     */
    protected function validateTable(string $table): string
    {
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
            throw new Exception("Invalid table name format: $table");
        }
        if (!self::isValidTable($table)) {
            throw new Exception("Table not in allowed list: $table");
        }
        return $table;
    }

    /**
     * Find all records
     */
    public function findAll(string $orderBy = 'id ASC'): array
    {
        $table = $this->validateTable($this->table);
        
        if (!preg_match('/^[a-zA-Z0-9_]+ (ASC|DESC)$/i', $orderBy)) {
            $orderBy = 'id ASC';
        }

        $sql = "SELECT * FROM {$table} ORDER BY {$orderBy}";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Find all published records
     */
    public function findAllPublished(string $orderBy = 'id ASC'): array
    {
        $table = $this->validateTable($this->table);
        
        if (!preg_match('/^[a-zA-Z0-9_]+ (ASC|DESC)$/i', $orderBy)) {
            $orderBy = 'id ASC';
        }

        $sql = "SELECT * FROM {$table} WHERE status = 'published' ORDER BY {$orderBy}";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Find record by ID
     */
    public function findById(int $id): ?array
    {
        $table = $this->validateTable($this->table);
        $sql = "SELECT * FROM {$table} WHERE id = :id LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Find record by slug
     */
    public function findBySlug(string $slug): ?array
    {
        $table = $this->validateTable($this->table);
        $sql = "SELECT * FROM {$table} WHERE slug = :slug LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['slug' => $slug]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Insert new record
     * Column names are validated before being interpolated into SQL,
     * so callers can safely build the data array dynamically.
     */
    public function insert(array $data): int
    {
        // GUARD CLAUSE: Prevent "INSERT INTO table () VALUES ()" syntax errors
        if (empty($data)) {
            throw new Exception("Cannot insert empty data array into {$this->table}.");
        }

        $table = $this->validateTable($this->table);

        // Auto-fill created_at / updated_at when timestamps are enabled.
        if ($this->timestamps) {
            $createdAt = 'created_at';
            $updatedAt = 'updated_at';
            if (!isset($data[$createdAt])) {
                $data[$createdAt] = date('Y-m-d H:i:s');
            }
            if (!isset($data[$updatedAt])) {
                $data[$updatedAt] = date('Y-m-d H:i:s');
            }
        }

        // Reject any column name that is not a plain identifier.
        // This prevents SQL injection through attacker-controlled keys.
        foreach ($data as $key => $value) {
            if (!preg_match('/^[a-zA-Z0-9_]+$/', $key)) {
                throw new Exception("Invalid column name: $key");
            }
        }

        // Build the INSERT statement from the validated column names.
        $columns      = array_keys($data);
        $placeholders = array_map(fn($c) => ':' . $c, $columns);

        $sql = "INSERT INTO {$table} (" . implode(',', $columns) . ")
                VALUES (" . implode(',', $placeholders) . ")";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($data);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Update record by ID
     */
    public function update(int $id, array $data): bool
    {
        $table = $this->validateTable($this->table);
        
        // Remove 'id' from data to prevent it from being updated or causing issues
        unset($data['id']);

        // GUARD CLAUSE: Prevent "UPDATE table SET WHERE id = :id" syntax errors
        if (empty($data)) {
            throw new Exception("Cannot update record. No valid columns provided.");
        }

        if ($this->timestamps) {
            $data['updated_at'] = date('Y-m-d H:i:s');
        }

        $set = [];
        foreach ($data as $key => $value) {
            // Validate column names
            if (!preg_match('/^[a-zA-Z0-9_]+$/', $key)) {
                throw new Exception("Invalid column name: $key");
            }
            $set[] = "{$key} = :{$key}";
        }

        $sql = "UPDATE {$table} SET " . implode(', ', $set) . " WHERE id = :id";
        $data['id'] = $id;

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($data);
    }

    /**
     * Delete record by ID
     */
    public function delete(int $id): bool
    {
        $table = $this->validateTable($this->table);
        $sql = "DELETE FROM {$table} WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }

    /**
     * Execute custom query (returns multiple rows)
     * Table names in custom queries must be validated by caller
     */
    public function query(string $sql, array $params = []): array
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Execute custom query (returns single row)
     * Table names in custom queries must be validated by caller
     */
    public function queryOne(string $sql, array $params = []): ?array
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }
}