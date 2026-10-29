<?php
namespace App\Models;

use App\Core\Model;
use PDO;
use Exception;

class UserModel extends Model {
    protected string $table = 'users';

    private function passwordAlgorithm(): string {
        if (defined('PASSWORD_ARGON2ID')) {
            return PASSWORD_ARGON2ID;
        }
        return PASSWORD_DEFAULT;
    }
    
    /**
     * Normalize phone number to E.164 format (+628123456789)
     */
    private function normalizePhone(string $phone): string {
        $phone = trim($phone);
        
        // Remove all non-digit characters except +
        $phone = preg_replace('/[^\d+]/', '', $phone);
        
        // If starts with 0, replace with country code (assuming Indonesia +62)
        if (str_starts_with($phone, '0')) {
            $phone = '+62' . substr($phone, 1);
        }
        
        // If doesn't start with +, assume it's missing country code
        if (!str_starts_with($phone, '+')) {
            throw new Exception('Phone number must include country code (e.g., +628123456789)');
        }
        
        // Validate length (E.164 max 15 digits)
        $digitsOnly = substr($phone, 1);
        if (strlen($digitsOnly) < 8 || strlen($digitsOnly) > 15) {
            throw new Exception('Invalid phone number length');
        }
        
        return $phone;
    }

        public function register(string $phone, string $plainPassword, string $role = 'user'): int {
        // Validate password
        if (strlen($plainPassword) < 8) {
            throw new Exception('Password must be at least 8 characters');
        }

        // Validate and normalize phone
        try {
            $phone = $this->normalizePhone($phone);
        } catch (Exception $e) {
            throw new Exception('Invalid phone number: ' . $e->getMessage());
        }

        // Check duplicate
        if ($this->findByPhone($phone)) {
            throw new Exception('Phone number already registered');
        }

        // Whitelist role
        $allowedRoles = ['root', 'admin', 'user'];
        if (!in_array($role, $allowedRoles, true)) {
            $role = 'user';
        }

        // Hash & store
        $hash = password_hash($plainPassword, $this->passwordAlgorithm());
        return $this->insert([
            'phone' => $phone,
            'password' => $hash,
            'role' => $role,
        ]);
    }

    /**
     * Secure login with Timing Attack Prevention
     */
    public function login(string $phone, string $plainPassword): ?array 
    {
        // Normalize phone for lookup
        try {
            $phone = $this->normalizePhone($phone);
        } catch (Exception $e) {
            // Fail securely: use a dummy hash to prevent timing attacks
            password_verify($plainPassword, '$argon2id$v=19$m=65536,t=4,p=1$ZmFrZQ$ZmFrZQ');
            return null;
        }

        $user = $this->queryOne("SELECT * FROM {$this->table} WHERE phone = ?", [$phone]);
        
        // SECURITY: Prevent Timing Attacks (User Enumeration)
        // If the user doesn't exist, we still run password_verify against a dummy hash.
        $hashToVerify = $user ? $user['password'] : '$argon2id$v=19$m=65536,t=4,p=1$ZmFrZQ$ZmFrZQ';
        
        // CRITICAL FIX: Execute password_verify FIRST. 
        // If we put it inside `if ($user && ...)`, PHP's short-circuit evaluation 
        // will SKIP it when $user is null, defeating the timing attack prevention!
        $isPasswordCorrect = password_verify($plainPassword, $hashToVerify);
        
        if ($user && $isPasswordCorrect) {
            $passwordHash = $user['password'];
            unset($user['password']);
            
            // Rehash if the algorithm or cost has changed
            if (password_needs_rehash($passwordHash, $this->passwordAlgorithm())) {
                $newHash = password_hash($plainPassword, $this->passwordAlgorithm());
                $this->update($user['id'], ['password' => $newHash]);
            }
            
            // Update the last login timestamp
            $this->update($user['id'], ['last_login_at' => date('Y-m-d H:i:s')]);
            
            return $user;
        }
        
        return null;
    }

    public function findByPhone(string $phone): ?array {
        return $this->queryOne("SELECT * FROM {$this->table} WHERE phone = ?", [$phone]);
    }

    public function findById(int $id): ?array {
        return $this->queryOne("SELECT * FROM {$this->table} WHERE id = ?", [$id]);
    }

    public function updateUsername(int $id, string $username): bool {
        $username = trim($username);

        if ($username === '') {
            return $this->update($id, ['username' => null]);
        }

        $valid = preg_match('/^[a-zA-Z0-9_]{3,50}$/', $username);
        if (!$valid) {
            throw new Exception('Username must be 3-50 characters, using only letters, numbers, and underscores');
        }

        $existing = $this->queryOne("SELECT id FROM {$this->table} WHERE username = ? AND id != ?", [$username, $id]);
        if ($existing) {
            throw new Exception('Username already taken');
        }

        return $this->update($id, ['username' => $username]);
    }

    /**
     * Change a user's password after verifying the current one.
     * Enforces the same strength policy as registration so the two paths
     * cannot diverge.
     */
    public function changePassword(int $id, string $currentPassword, string $newPassword): bool {
        // Load the current row and verify the old password.
        $user = $this->findById($id);
        if (!$user || !password_verify($currentPassword, $user['password'])) {
            throw new Exception('Current password is incorrect');
        }

        // Enforce the shared password strength policy.
        $pwCheck = \App\Core\Validator::passwordStrength($newPassword);
        if (!$pwCheck['valid']) {
            throw new Exception(implode('; ', $pwCheck['errors']));
        }

        // Hash with Argon2id and persist the new hash.
        $hash = password_hash($newPassword, $this->passwordAlgorithm());
        return $this->update($id, ['password' => $hash]);
    }

    public function updateAvatar(int $id, string $avatarPath): bool {
        return $this->update($id, ['avatar' => $avatarPath]);
    }

    // =====================================================================
    // New methods for Phase 3 (Multi-Auth)
    // =====================================================================

    /**
     * Update a user's role.
     * Only intended to be called by Root admins.
     */
    public function updateRole(int $id, string $role): bool {
        $allowedRoles = ['root', 'admin', 'user'];

        // Reject any role not in the whitelist.
        if (!in_array($role, $allowedRoles, true)) {
            throw new Exception('Invalid role');
        }

        return $this->update($id, ['role' => $role]);
    }

    /**
     * Mark a user as verified (useful for Phase 5 WhatsApp verification).
     */
    public function markVerified(int $id): bool {
        return $this->update($id, ['is_verified' => 1]);
    }

    /**
     * Count how many users have a specific role.
     * Useful for dashboard statistics.
     */
    public function countByRole(string $role): int {
        $sql = "SELECT COUNT(*) AS c FROM {$this->table} WHERE role = ?";
        $row = $this->queryOne($sql, [$role]);
        return (int) $row['c'];
    }

    /**
     * Return all users for the management list.
     */
    public function findAllUsers(string $orderBy = 'id ASC'): array {
        return $this->findAll($orderBy);
    }
}