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
    public function upload(array $file, string $directory = '', string $filename = null): ?string
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
            // Ensure provided filename has an extension; if not, append detected extension
            if (pathinfo($filename, PATHINFO_EXTENSION) === '') {
                $filename = $filename . '.' . $originalExtension;
            }
        }

        // Create directory path (storage)
        $uploadPath = $this->getUploadPath($directory);
        $filePath = $uploadPath . '/' . $filename;

        // Also compute public path for direct serving via /storage/uploads
        $publicBase = __DIR__ . '/../../public/storage/uploads';
        $publicDir = rtrim($publicBase . '/' . trim($directory, '/'), '/');
        $publicPath = $publicDir . '/' . $filename;

        // Ensure directory exists
        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0755, true);
        }
        if (!is_dir($publicDir)) {
            mkdir($publicDir, 0755, true);
        }

        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $filePath)) {
            // Mirror to public directory
            @copy($filePath, $publicPath);
            // Return web-accessible URL instead of full file path
            return $this->getFileUrl($filePath);
        }

        $this->errors[] = 'Failed to upload file';
        return null;
    }

    /**
     * Validate uploaded file
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
            $this->errors[] = 'File size exceeds maximum allowed size';
            return false;
        }

        // Check file type
        $allowedTypes = $this->config['allowed_types'] ?? ['jpg', 'jpeg', 'png', 'webp'];
        $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($fileExtension, $allowedTypes)) {
            $this->errors[] = 'File type not allowed';
            return false;
        }

        // Additional MIME type check
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
            $this->errors[] = 'Invalid file type';
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
