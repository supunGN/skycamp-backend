<?php

/**
 * File Upload Utility Class
 * Handles secure file uploads for images
 * Following security best practices
 */

class FileUpload
{

    // Allowed file types and sizes
    private $allowed_types = ['image/jpeg', 'image/png', 'image/webp'];
    private $max_file_size = 5242880; // 5MB in bytes
    private $upload_directory;

    /**
     * Constructor
     */
    public function __construct($upload_directory = 'uploads/')
    {
        $this->upload_directory = rtrim($upload_directory, '/') . '/';
        $this->createUploadDirectory();
    }

    /**
     * Upload profile picture
     * 
     * @param array $file $_FILES array element
     * @param string $user_id User ID for filename
     * @return string|false File path on success, false on failure
     */
    public function uploadProfilePicture($file, $user_id)
    {
        if (!$this->validateFile($file)) {
            return false;
        }

        $directory = $this->upload_directory . 'profiles/';
        $this->createDirectory($directory);

        $file_extension = $this->getFileExtension($file['type']);
        $filename = 'profile_' . $user_id . '_' . time() . '.' . $file_extension;
        $filepath = $directory . $filename;

        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            return $filepath;
        }

        return false;
    }

    /**
     * Upload NIC image
     * 
     * @param array $file $_FILES array element
     * @param string $user_id User ID for filename
     * @param string $side 'front' or 'back'
     * @return string|false File path on success, false on failure
     */
    public function uploadNicImage($file, $user_id, $side = 'front')
    {
        if (!$this->validateFile($file)) {
            return false;
        }

        $directory = $this->upload_directory . 'nic/';
        $this->createDirectory($directory);

        $file_extension = $this->getFileExtension($file['type']);
        $filename = 'nic_' . $side . '_' . $user_id . '_' . time() . '.' . $file_extension;
        $filepath = $directory . $filename;

        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            return $filepath;
        }

        return false;
    }

    /**
     * Validate uploaded file
     * 
     * @param array $file $_FILES array element
     * @return bool True if valid, false otherwise
     */
    private function validateFile($file)
    {
        // Check if file was uploaded
        if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
            return false;
        }

        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            error_log("Upload error: " . $file['error']);
            return false;
        }

        // Check file size
        if ($file['size'] > $this->max_file_size) {
            error_log("File too large: " . $file['size'] . " bytes");
            return false;
        }

        // Check file type
        $file_type = $this->getFileType($file['tmp_name']);
        if (!in_array($file_type, $this->allowed_types)) {
            error_log("Invalid file type: " . $file_type);
            return false;
        }

        // Additional security check - verify it's actually an image
        if (!$this->isValidImage($file['tmp_name'])) {
            error_log("Invalid image file");
            return false;
        }

        return true;
    }

    /**
     * Get actual file MIME type
     * 
     * @param string $filepath File path
     * @return string MIME type
     */
    private function getFileType($filepath)
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $filepath);
        finfo_close($finfo);
        return $mime_type;
    }

    /**
     * Get file extension from MIME type
     * 
     * @param string $mime_type MIME type
     * @return string File extension
     */
    private function getFileExtension($mime_type)
    {
        $extensions = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp'
        ];

        return isset($extensions[$mime_type]) ? $extensions[$mime_type] : 'jpg';
    }

    /**
     * Verify if file is a valid image
     * 
     * @param string $filepath File path
     * @return bool True if valid image, false otherwise
     */
    private function isValidImage($filepath)
    {
        $image_info = @getimagesize($filepath);
        return $image_info !== false;
    }

    /**
     * Create upload directory if it doesn't exist
     */
    private function createUploadDirectory()
    {
        $this->createDirectory($this->upload_directory);
    }

    /**
     * Create directory with proper permissions
     * 
     * @param string $directory Directory path
     */
    private function createDirectory($directory)
    {
        if (!file_exists($directory)) {
            mkdir($directory, 0755, true);

            // Create .htaccess file for security
            $htaccess_content = "Options -Indexes\n";
            $htaccess_content .= "Options -ExecCGI\n";
            $htaccess_content .= "<FilesMatch \"\\.(php|phtml|php3|php4|php5|pl|py|jsp|asp|sh|cgi)$\">\n";
            $htaccess_content .= "    Require all denied\n";
            $htaccess_content .= "</FilesMatch>\n";

            file_put_contents($directory . '.htaccess', $htaccess_content);
        }
    }

    /**
     * Delete uploaded file
     * 
     * @param string $filepath File path to delete
     * @return bool Success status
     */
    public function deleteFile($filepath)
    {
        if (file_exists($filepath) && is_file($filepath)) {
            return unlink($filepath);
        }
        return false;
    }

    /**
     * Get file size in human readable format
     * 
     * @param int $bytes File size in bytes
     * @return string Human readable size
     */
    public function formatFileSize($bytes)
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= pow(1024, $pow);

        return round($bytes, 2) . ' ' . $units[$pow];
    }

    /**
     * Validate file upload limits
     * 
     * @return array Upload limits information
     */
    public function getUploadLimits()
    {
        return [
            'max_file_size' => $this->max_file_size,
            'max_file_size_formatted' => $this->formatFileSize($this->max_file_size),
            'allowed_types' => $this->allowed_types,
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'max_file_uploads' => ini_get('max_file_uploads')
        ];
    }
}
