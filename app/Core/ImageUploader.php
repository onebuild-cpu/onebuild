<?php
/**
 * ImageUploader
 * Handles secure image upload + automatic WebP conversion + compression.
 * Requires GD (available on DomaiNesia shared hosting).
 */
namespace App\Core;

use Exception;

class ImageUploader
{
    /** @var array Allowed source MIME types */
    private static array $allowedMimes = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/gif'  => 'gif',
        'image/webp' => 'webp',
    ];

    /**
     * Process an uploaded file: validate, convert to WebP, save, return relative path.
     *
     * @param array  $file          $_FILES['...'] entry
     * @param string $subdir        e.g. 'products', 'packaging'
     * @param int    $maxDim        max width/height in pixels (image is resized proportionally)
     * @param int    $quality       WebP quality 0-100
     * @param int    $maxBytes      max upload size in bytes
     * @return string               relative path like '/uploads/products/abc123.webp'
     * @throws Exception
     */
    public static function toWebp(
        array $file,
        string $subdir,
        int $maxDim = 1600,
        int $quality = 82,
        int $maxBytes = 5242880
    ): string {
        if (!isset($file['error']) || is_array($file['error'])) {
            throw new Exception('Invalid upload payload.');
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Upload failed (code ' . $file['error'] . ').');
        }

        if ($file['size'] > $maxBytes) {
            throw new Exception('File too large. Max ' . round($maxBytes / 1048576, 1) . ' MB.');
        }

        if (!is_uploaded_file($file['tmp_name'])) {
            throw new Exception('Invalid upload source.');
        }

        // Detect real MIME from content
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!isset(self::$allowedMimes[$mime])) {
            throw new Exception('Unsupported image type: ' . $mime);
        }

        // Load source image
        $src = self::loadImage($file['tmp_name'], $mime);
        if (!$src) {
            throw new Exception('Could not read image.');
        }

        // Auto-rotate JPEGs based on EXIF
        if ($mime === 'image/jpeg' && function_exists('exif_read_data')) {
            $exif = @exif_read_data($file['tmp_name']);
            if (!empty($exif['Orientation'])) {
                $src = self::applyExifRotation($src, (int) $exif['Orientation']);
            }
        }

        // Resize proportionally if larger than maxDim
        $w = imagesx($src);
        $h = imagesy($src);
        if ($w > $maxDim || $h > $maxDim) {
            $ratio = min($maxDim / $w, $maxDim / $h);
            $newW  = max(1, (int) round($w * $ratio));
            $newH  = max(1, (int) round($h * $ratio));
            $dst   = imagecreatetruecolor($newW, $newH);

            // Preserve transparency for PNG/GIF/WebP sources
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
            $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
            imagefilledrectangle($dst, 0, 0, $newW, $newH, $transparent);

            imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $w, $h);
            imagedestroy($src);
            $src = $dst;
        }

        // Ensure output directory exists
        $uploadRoot = __DIR__ . '/../../public/uploads/images/' . trim($subdir, '/');
        if (!is_dir($uploadRoot)) {
            if (!mkdir($uploadRoot, 0755, true) && !is_dir($uploadRoot)) {
                throw new Exception('Could not create upload directory.');
            }
        }

        // Generate safe filename
        $filename = bin2hex(random_bytes(16)) . '.webp';
        $fullPath = $uploadRoot . '/' . $filename;

        // Encode as WebP
        if (!function_exists('imagewebp')) {
            imagedestroy($src);
            throw new Exception('WebP support not enabled on this server.');
        }

        if (!imagewebp($src, $fullPath, $quality)) {
            imagedestroy($src);
            throw new Exception('Failed to write WebP file.');
        }

        imagedestroy($src);

        return '/uploads/images/' . trim($subdir, '/') . '/' . $filename;
    }

    /**
     * Delete a previously uploaded file (path relative to /public).
     */
    public static function delete(?string $relativePath): void
    {
        if (!$relativePath) {
            return;
        }
        $full = __DIR__ . '/../../public/' . ltrim($relativePath, '/');
        if (is_file($full)) {
            @unlink($full);
        }
    }

    private static function loadImage(string $path, string $mime)
    {
        switch ($mime) {
            case 'image/jpeg': return imagecreatefromjpeg($path);
            case 'image/png':  return imagecreatefrompng($path);
            case 'image/gif':  return imagecreatefromgif($path);
            case 'image/webp': return imagecreatefromwebp($path);
        }
        return false;
    }

    private static function applyExifRotation($img, int $orientation)
    {
        switch ($orientation) {
            case 3: return imagerotate($img, 180, 0);
            case 6: return imagerotate($img, -90, 0);
            case 8: return imagerotate($img, 90, 0);
        }
        return $img;
    }
}