<?php

/**
 * File Service
 * Handles file operations, storage, and serving
 */

class FileService
{
    private array $config;
    private Uploader $uploader;

    public function __construct()
    {
        $this->config = Database::getConfig('upload') ?? [];
        $this->uploader = new Uploader();
    }

    /**
     * Save user image (profile, nic_front, nic_back)
     * Returns relative path or null if not provided/invalid
     */
    public function saveUserImage(string $userId, array $file, string $logicalName): ?string
    {
        // Validate logical name
        if (!in_array($logicalName, ['profile', 'nic_front', 'nic_back'])) {
            return null;
        }

        // Check if file was uploaded
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return null;
        }

        // Validate file size (5MB max)
        if ($file['size'] > (5 * 1024 * 1024)) {
            return null;
        }

        // Validate MIME type using finfo_file
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
        if (!in_array($mimeType, $allowedMimes)) {
            return null;
        }

        // Determine extension from MIME type
        $extension = match ($mimeType) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => null
        };

        if (!$extension) {
            return null;
        }

        // Ensure directory exists in both locations
        $storagePath = $this->getStoragePath();
        $publicPath = __DIR__ . '/../../public/storage/uploads';

        $userDir = $storagePath . '/users/' . $userId;
        $publicUserDir = $publicPath . '/users/' . $userId;

        if (!is_dir($userDir)) {
            mkdir($userDir, 0755, true);
        }
        if (!is_dir($publicUserDir)) {
            mkdir($publicUserDir, 0755, true);
        }

        // Generate safe filename
        $filename = $logicalName . '.' . $extension;
        $fullPath = $userDir . '/' . $filename;
        $publicFullPath = $publicUserDir . '/' . $filename;

        // Move uploaded file to both locations
        if (move_uploaded_file($file['tmp_name'], $fullPath)) {
            // Copy to public directory as well
            copy($fullPath, $publicFullPath);
            // Return relative path for database storage
            return "users/{$userId}/{$filename}";
        }

        return null;
    }

    /**
     * Upload user profile image
     */
    public function uploadProfileImage(array $file, string $userId): array
    {
        try {
            $directory = "users/{$userId}";
            $filePath = $this->uploader->upload($file, $directory, 'profile');

            if (!$filePath) {
                return [
                    'success' => false,
                    'message' => 'Upload failed',
                    'errors' => $this->uploader->getErrors()
                ];
            }

            return [
                'success' => true,
                'message' => 'Profile image uploaded successfully',
                'file_path' => $filePath,
                'url' => $this->getFileUrl($filePath)
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Upload failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Upload NIC front image
     */
    public function uploadNicFront(array $file, string $userId): array
    {
        try {
            $directory = "users/{$userId}";
            $filePath = $this->uploader->upload($file, $directory, 'nic_front');

            if (!$filePath) {
                return [
                    'success' => false,
                    'message' => 'Upload failed',
                    'errors' => $this->uploader->getErrors()
                ];
            }

            return [
                'success' => true,
                'message' => 'NIC front image uploaded successfully',
                'file_path' => $filePath,
                'url' => $this->getFileUrl($filePath)
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Upload failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Upload NIC back image
     */
    public function uploadNicBack(array $file, string $userId): array
    {
        try {
            $directory = "users/{$userId}";
            $filePath = $this->uploader->upload($file, $directory, 'nic_back');

            if (!$filePath) {
                return [
                    'success' => false,
                    'message' => 'Upload failed',
                    'errors' => $this->uploader->getErrors()
                ];
            }

            return [
                'success' => true,
                'message' => 'NIC back image uploaded successfully',
                'file_path' => $filePath,
                'url' => $this->getFileUrl($filePath)
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Upload failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Upload general file
     */
    public function uploadFile(array $file, string $directory = '', string $filename = null): array
    {
        try {
            $filePath = $this->uploader->upload($file, $directory, $filename);

            if (!$filePath) {
                return [
                    'success' => false,
                    'message' => 'Upload failed',
                    'errors' => $this->uploader->getErrors()
                ];
            }

            return [
                'success' => true,
                'message' => 'File uploaded successfully',
                'file_path' => $filePath,
                'url' => $this->getFileUrl($filePath)
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Upload failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Delete file
     */
    public function deleteFile(string $filePath): array
    {
        try {
            if ($this->uploader->delete($filePath)) {
                return [
                    'success' => true,
                    'message' => 'File deleted successfully'
                ];
            }

            return [
                'success' => false,
                'message' => 'Failed to delete file'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Delete failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get file URL for serving
     */
    public function getFileUrl(string $filePath): string
    {
        $relativePath = str_replace($this->config['storage_path'], '', $filePath);
        return '/api/files' . $relativePath;
    }

    /**
     * Serve file (for file serving endpoint)
     */
    public function serveFile(string $filePath): void
    {
        if (!file_exists($filePath)) {
            http_response_code(404);
            echo 'File not found';
            return;
        }

        // Get file info
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $filePath);
        finfo_close($finfo);

        // Set headers
        header('Content-Type: ' . $mimeType);
        header('Content-Length: ' . filesize($filePath));
        header('Cache-Control: public, max-age=3600'); // Cache for 1 hour

        // Output file
        readfile($filePath);
    }

    /**
     * Validate file type
     */
    public function validateFileType(array $file): bool
    {
        $allowedTypes = $this->config['allowed_types'] ?? ['jpg', 'jpeg', 'png', 'webp'];
        $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        return in_array($fileExtension, $allowedTypes);
    }

    /**
     * Validate file size
     */
    public function validateFileSize(array $file): bool
    {
        $maxSize = $this->config['max_size'] ?? (5 * 1024 * 1024); // 5MB default
        return $file['size'] <= $maxSize;
    }

    /**
     * Get file info
     */
    public function getFileInfo(string $filePath): array
    {
        if (!file_exists($filePath)) {
            return [];
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $filePath);
        finfo_close($finfo);

        return [
            'path' => $filePath,
            'size' => filesize($filePath),
            'mime_type' => $mimeType,
            'extension' => pathinfo($filePath, PATHINFO_EXTENSION),
            'filename' => basename($filePath),
            'url' => $this->getFileUrl($filePath),
            'created' => date('Y-m-d H:i:s', filectime($filePath)),
            'modified' => date('Y-m-d H:i:s', filemtime($filePath))
        ];
    }

    /**
     * Create directory if it doesn't exist
     */
    public function ensureDirectory(string $directory): bool
    {
        $fullPath = $this->config['storage_path'] . '/' . $directory;

        if (!is_dir($fullPath)) {
            return mkdir($fullPath, 0755, true);
        }

        return true;
    }

    /**
     * Get storage path
     */
    public function getStoragePath(): string
    {
        return $this->config['storage_path'] ?? __DIR__ . '/../../storage/uploads';
    }

    /**
     * Clean up old files
     */
    public function cleanupOldFiles(int $daysOld = 30): int
    {
        $storagePath = $this->getStoragePath();
        $cutoffTime = time() - ($daysOld * 24 * 60 * 60);
        $deletedCount = 0;

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($storagePath, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getMTime() < $cutoffTime) {
                if (unlink($file->getPathname())) {
                    $deletedCount++;
                }
            }
        }

        return $deletedCount;
    }
}
