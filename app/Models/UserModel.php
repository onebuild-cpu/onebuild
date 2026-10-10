<?php
namespace App\Models;

use App\Core\Model;
use PDO;

class UserModel extends Model {
    protected string $table = 'users';

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
        $allowedRoles = ['user', 'admin', 'editor'];
        if (!in_array($role, $allowedRoles, true)) {
            $role = 'user';
        }

        // Hash & store
        $hash = password_hash($plainPassword, PASSWORD_ARGON2ID);
        return $this->insert([
            'phone' => $phone,
            'password' => $hash,
            'role' => $role,
        ]);
    }

    public function login(string $phone, string $plainPassword): ?array {
        // Normalize phone for lookup
        try {
            $phone = $this->normalizePhone($phone);
        } catch (Exception $e) {
            return null;
        }
        
        $user = $this->queryOne("SELECT * FROM {$this->table} WHERE phone = ?", [$phone]);
        
        if ($user && password_verify($plainPassword, $user['password'])) {
            $passwordHash = $user['password'];
            unset($user['password']);
            
            if (password_needs_rehash($passwordHash, PASSWORD_ARGON2ID)) {
                $newHash = password_hash($plainPassword, PASSWORD_ARGON2ID);
                $this->update($user['id'], ['password' => $newHash]);
            }
            
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
        if ($username === '') {
            return $this->update($id, ['username' => null]);
        }
        
        $existing = $this->queryOne("SELECT id FROM {$this->table} WHERE username = ? AND id != ?", [$username, $id]);
        if ($existing) {
            throw new Exception('Username already taken');
        }
        
        return $this->update($id, ['username' => $username]);
    }

    public function changePassword(int $id, string $currentPassword, string $newPassword): bool {
        $user = $this->findById($id);
        if (!$user || !password_verify($currentPassword, $user['password'])) {
            throw new Exception('Current password is incorrect');
        }
        
        if (strlen($newPassword) < 8) {
            throw new Exception('New password must be at least 8 characters');
        }
        
        $hash = password_hash($newPassword, PASSWORD_ARGON2ID);
        return $this->update($id, ['password' => $hash]);
    }

    public function updateAvatar(int $id, string $avatarPath): bool {
        return $this->update($id, ['avatar' => $avatarPath]);
    }
}