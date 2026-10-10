<?php
namespace App\Controllers;

use PDO;
use App\Core\Controller;
use App\Models\UserModel;

class DashboardController extends Controller
{
    private UserModel $userModel;

    public function __construct(PDO $pdo)
    {
        parent::__construct($pdo);
        $this->userModel = new UserModel($pdo);
    }

    public function index(): void
    {
        $this->requireLogin();

        $this->view('dashboard/index', [
            'pageTitle' => 'Dashboard',
            'metaDescription' => 'Admin Dashboard - Overview of your application',
        ]);
    }

    public function profile(): void
    {
        $this->requireLogin();

        $userId = (int)$_SESSION['user_id'];
        $user = $this->userModel->findById($userId);

        $error = '';
        $success = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';

            try {
                if ($action === 'update_username') {
                    $username = trim($_POST['username'] ?? '');
                    $this->userModel->updateUsername($userId, $username);
                    $_SESSION['username'] = $username ?: null;
                    $success = 'Username updated successfully';
                    $user = $this->userModel->findById($userId);
                } elseif ($action === 'change_password') {
                    $currentPassword = $_POST['current_password'] ?? '';
                    $newPassword = $_POST['new_password'] ?? '';
                    $confirmPassword = $_POST['confirm_password'] ?? '';

                    if ($newPassword !== $confirmPassword) {
                        throw new Exception('New passwords do not match');
                    }

                    $this->userModel->changePassword($userId, $currentPassword, $newPassword);
                    $success = 'Password changed successfully';
                } elseif ($action === 'update_avatar') {
                    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
                        $avatarPath = $this->handleAvatarUpload($_FILES['avatar'], $userId);
                        $this->userModel->updateAvatar($userId, $avatarPath);
                        $_SESSION['avatar'] = $avatarPath;
                        $success = 'Avatar updated successfully';
                        $user = $this->userModel->findById($userId);
                    } else {
                        throw new Exception('Please select a valid image file');
                    }
                }
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        }

        $this->view('dashboard/profile', [
            'pageTitle' => 'Profile',
            'metaDescription' => 'User Profile',
            'user' => $user,
            'error' => $error,
            'success' => $success,
        ]);
    }

    private function handleAvatarUpload(array $file, int $userId): string
    {
        $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $maxSize = 2 * 1024 * 1024; // 2MB

        if ($file['size'] > $maxSize) {
            throw new Exception('File size must be less than 2MB');
        }

        // Detect actual MIME type using file content (not user-supplied)
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mime, $allowedMimes, true)) {
            throw new Exception('Invalid file type. Allowed: JPG, PNG, GIF, WebP');
        }

        // Map MIME to safe extension
        $extMap = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
        ];
        $ext = $extMap[$mime] ?? 'jpg';

        $filename = 'avatar_' . $userId . '_' . time() . '.' . $ext;
        
        $uploadDir = __DIR__ . '/../../public/uploads/avatars/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $destination = $uploadDir . $filename;
        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            throw new Exception('Failed to upload avatar');
        }

        return '/uploads/avatars/' . $filename;
    }
}