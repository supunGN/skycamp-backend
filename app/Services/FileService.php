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
     * Normalizes all images to .jpg format
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

        // Ensure directory exists
        $storagePath = $this->getStoragePath();
        $userDir = $storagePath . '/users/' . $userId;

        if (!is_dir($userDir)) {
            mkdir($userDir, 0755, true);
        }

        // Clean up existing files with same logical name but different extensions
        $this->cleanupUserImageFiles($userDir, $logicalName);

        // Generate normalized filename (always .jpg)
        $filename = $logicalName . '.jpg';
        $fullPath = $userDir . '/' . $filename;

        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $fullPath)) {
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
     * Upload NIC front image with strict validation
     */
    public function uploadNicFront(array $file, string $userId): array
    {
        try {
            // Strict validation for NIC documents
            $validationResult = $this->validateNicDocument($file, 'NIC Front');
            if (!$validationResult['valid']) {
                return [
                    'success' => false,
                    'message' => $validationResult['message'],
                    'errors' => [$validationResult['message']]
                ];
            }

            $directory = "users/{$userId}";
            $filePath = $this->uploader->upload($file, $directory, 'nic_front');

            if (!$filePath) {
                return [
                    'success' => false,
                    'message' => 'Failed to upload NIC front image',
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
     * Upload NIC back image with strict validation
     */
    public function uploadNicBack(array $file, string $userId): array
    {
        try {
            // Strict validation for NIC documents
            $validationResult = $this->validateNicDocument($file, 'NIC Back');
            if (!$validationResult['valid']) {
                return [
                    'success' => false,
                    'message' => $validationResult['message'],
                    'errors' => [$validationResult['message']]
                ];
            }

            $directory = "users/{$userId}";
            $filePath = $this->uploader->upload($file, $directory, 'nic_back');

            if (!$filePath) {
                return [
                    'success' => false,
                    'message' => 'Failed to upload NIC back image',
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
        // Return direct storage path URL
        return 'http://localhost/skycamp/skycamp-backend/storage/uploads/' . ltrim($filePath, '/\\');
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
     * Validate NIC document with strict checks
     */
    private function validateNicDocument(array $file, string $documentType): array
    {
        // Check if file was uploaded
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return [
                'valid' => false,
                'message' => "$documentType: No file uploaded or invalid upload."
            ];
        }

        // Check file size (5MB max)
        if ($file['size'] > (5 * 1024 * 1024)) {
            return [
                'valid' => false,
                'message' => "$documentType: File size exceeds 5MB limit."
            ];
        }

        // Check file size (minimum 1KB to avoid empty files)
        if ($file['size'] < 1024) {
            return [
                'valid' => false,
                'message' => "$documentType: File too small. Please upload a valid image."
            ];
        }

        // Check file extension
        $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png'];

        if (!in_array($fileExtension, $allowedExtensions)) {
            return [
                'valid' => false,
                'message' => "$documentType: Only JPG and PNG images are allowed for verification documents."
            ];
        }

        // Strict MIME type validation
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        $allowedMimeTypes = ['image/jpeg', 'image/png'];
        if (!in_array($mimeType, $allowedMimeTypes)) {
            return [
                'valid' => false,
                'message' => "$documentType: Invalid file type. Only JPG and PNG images are accepted."
            ];
        }

        // Verify MIME type matches extension
        $expectedMime = match ($fileExtension) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            default => null
        };

        if ($expectedMime && $mimeType !== $expectedMime) {
            return [
                'valid' => false,
                'message' => "$documentType: File content does not match the file extension."
            ];
        }

        // Use getimagesize() to verify it's a real image
        $imageInfo = @getimagesize($file['tmp_name']);
        if ($imageInfo === false) {
            return [
                'valid' => false,
                'message' => "$documentType: Invalid image file. The uploaded file is not a valid image."
            ];
        }

        // Verify image dimensions are reasonable for a document
        $width = $imageInfo[0];
        $height = $imageInfo[1];

        if ($width < 200 || $height < 100) {
            return [
                'valid' => false,
                'message' => "$documentType: Image too small. Minimum dimensions are 200x100 pixels for clear document visibility."
            ];
        }

        if ($width > 4000 || $height > 4000) {
            return [
                'valid' => false,
                'message' => "$documentType: Image too large. Maximum dimensions are 4000x4000 pixels."
            ];
        }

        // Verify the image type matches what we expect
        $expectedImageType = match ($fileExtension) {
            'jpg', 'jpeg' => IMAGETYPE_JPEG,
            'png' => IMAGETYPE_PNG,
            default => null
        };

        if ($expectedImageType && $imageInfo[2] !== $expectedImageType) {
            return [
                'valid' => false,
                'message' => "$documentType: Image type mismatch. File content does not match the expected image format."
            ];
        }

        // Additional check: ensure the image has reasonable aspect ratio for documents
        $aspectRatio = $width / $height;
        if ($aspectRatio < 0.5 || $aspectRatio > 3.0) {
            return [
                'valid' => false,
                'message' => "$documentType: Unusual image dimensions. Please ensure you're uploading a document image."
            ];
        }

        return [
            'valid' => true,
            'message' => 'Valid NIC document'
        ];
    }

    /**
     * Clean up existing user image files with same logical name but different extensions
     */
    private function cleanupUserImageFiles(string $userDir, string $logicalName): void
    {
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];

        foreach ($allowedExtensions as $ext) {
            $filePath = $userDir . '/' . $logicalName . '.' . $ext;
            if (file_exists($filePath)) {
                @unlink($filePath);
            }
        }
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
