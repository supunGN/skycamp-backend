<?php

/**
 * File Uploader Class
 * Handles safe file uploads with type and size validation
 */

class Uploader
{
    private array $config;
    private array $errors = [];

    public function __construct()
    {
        $this->config = Database::getConfig('upload') ?? [];
    }

    /**
     * Upload file with validation
     */
    public function upload(array $file, string $directory = '', ?string $filename = null): ?string
    {
        $this->errors = [];

        // Validate file
        if (!$this->validateFile($file)) {
            return null;
        }

        // Determine extension from uploaded file
        $originalExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if ($filename === null) {
            // Generate filename with extension
            $filename = $this->generateFilename($file);
        } else {
            // For user uploads, normalize to .jpg extension
            if (in_array($originalExtension, ['jpg', 'jpeg', 'png', 'webp'])) {
                $filename = $filename . '.jpg';
            } else {
                // Ensure provided filename has an extension; if not, append detected extension
                if (pathinfo($filename, PATHINFO_EXTENSION) === '') {
                    $filename = $filename . '.' . $originalExtension;
                }
            }
        }

        // Create directory path (storage)
        $uploadPath = $this->getUploadPath($directory);
        $filePath = $uploadPath . '/' . $filename;

        // Ensure directory exists
        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0755, true);
        }

        // For user uploads, delete existing files with same base name but different extensions
        if (strpos($directory, 'users/') === 0) {
            $this->cleanupDuplicateFiles($uploadPath, pathinfo($filename, PATHINFO_FILENAME));
        }

        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $filePath)) {
            // Return relative path for database storage
            $relative = ltrim(trim($directory, '/') . '/' . $filename, '/');
            return $relative;
        }

        $this->errors[] = 'Failed to upload file';
        return null;
    }

    /**
     * Validate uploaded file with strict image validation
     */
    private function validateFile(array $file): bool
    {
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $this->errors[] = $this->getUploadErrorMessage($file['error']);
            return false;
        }

        // Check file size
        $maxSize = $this->config['max_size'] ?? (5 * 1024 * 1024); // 5MB default
        if ($file['size'] > $maxSize) {
            $this->errors[] = 'File size exceeds maximum allowed size (5MB)';
            return false;
        }

        // Check file type by extension
        $allowedTypes = $this->config['allowed_types'] ?? ['jpg', 'jpeg', 'png', 'webp'];
        $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($fileExtension, $allowedTypes)) {
            $this->errors[] = 'File type not allowed. Only JPG, PNG, and WebP images are accepted.';
            return false;
        }

        // Strict MIME type validation
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        $allowedMimeTypes = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp'
        ];

        if (!isset($allowedMimeTypes[$fileExtension]) || $mimeType !== $allowedMimeTypes[$fileExtension]) {
            $this->errors[] = 'Invalid file type. File content does not match the extension.';
            return false;
        }

        // Additional validation using getimagesize() to ensure it's a real image
        $imageInfo = @getimagesize($file['tmp_name']);
        if ($imageInfo === false) {
            $this->errors[] = 'Invalid image file. The uploaded file is not a valid image.';
            return false;
        }

        // Verify image dimensions are reasonable (not too small or too large)
        $width = $imageInfo[0];
        $height = $imageInfo[1];

        if ($width < 50 || $height < 50) {
            $this->errors[] = 'Image too small. Minimum dimensions are 50x50 pixels.';
            return false;
        }

        if ($width > 8000 || $height > 8000) {
            $this->errors[] = 'Image too large. Maximum dimensions are 8000x8000 pixels.';
            return false;
        }

        // Verify the image type matches what we expect
        $expectedImageType = match ($fileExtension) {
            'jpg', 'jpeg' => IMAGETYPE_JPEG,
            'png' => IMAGETYPE_PNG,
            'webp' => IMAGETYPE_WEBP,
            default => null
        };

        if ($expectedImageType && $imageInfo[2] !== $expectedImageType) {
            $this->errors[] = 'Image type mismatch. File content does not match the expected image format.';
            return false;
        }

        return true;
    }

    /**
     * Generate unique filename
     */
    private function generateFilename(array $file): string
    {
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $uuid = Uuid::generate();
        return $uuid . '.' . $extension;
    }

    /**
     * Get upload directory path
     */
    private function getUploadPath(string $directory = ''): string
    {
        $basePath = $this->config['storage_path'] ?? __DIR__ . '/../../storage/uploads';

        if ($directory) {
            return $basePath . '/' . trim($directory, '/');
        }

        return $basePath;
    }

    /**
     * Get upload error message
     */
    private function getUploadErrorMessage(int $errorCode): string
    {
        switch ($errorCode) {
            case UPLOAD_ERR_INI_SIZE:
                return 'File exceeds upload_max_filesize directive';
            case UPLOAD_ERR_FORM_SIZE:
                return 'File exceeds MAX_FILE_SIZE directive';
            case UPLOAD_ERR_PARTIAL:
                return 'File was only partially uploaded';
            case UPLOAD_ERR_NO_FILE:
                return 'No file was uploaded';
            case UPLOAD_ERR_NO_TMP_DIR:
                return 'Missing temporary folder';
            case UPLOAD_ERR_CANT_WRITE:
                return 'Failed to write file to disk';
            case UPLOAD_ERR_EXTENSION:
                return 'File upload stopped by extension';
            default:
                return 'Unknown upload error';
        }
    }

    /**
     * Get upload errors
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Check if upload has errors
     */
    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    /**
     * Delete uploaded file
     */
    public function delete(string $filePath): bool
    {
        if (file_exists($filePath)) {
            return unlink($filePath);
        }
        return false;
    }

    /**
     * Clean up duplicate files with same base name but different extensions
     */
    private function cleanupDuplicateFiles(string $directory, string $baseName): void
    {
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];

        foreach ($allowedExtensions as $ext) {
            $filePath = $directory . '/' . $baseName . '.' . $ext;
            if (file_exists($filePath)) {
                @unlink($filePath);
            }
        }
    }

    /**
     * Get file URL for serving
     */
    public function getFileUrl(string $filePath): string
    {
        // Accepts either absolute storage path or already-relative path
        $storageBase = rtrim($this->config['storage_path'] ?? __DIR__ . '/../../storage/uploads', '/');
        if (str_starts_with($filePath, $storageBase)) {
            $relativePath = ltrim(str_replace($storageBase, '', $filePath), '/');
        } else {
            $relativePath = ltrim($filePath, '/');
        }
        return '/storage/uploads/' . $relativePath;
    }
}
