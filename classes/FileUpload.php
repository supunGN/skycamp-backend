<?php
/**
 * File Upload Handler Class
 * Handles profile picture and NIC document uploads
 */
class FileUpload {
    private $uploadPath;
    private $maxFileSize;
    private $allowedTypes;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->uploadPath = UPLOAD_PATH;
        $this->maxFileSize = MAX_FILE_SIZE;
        $this->allowedTypes = ALLOWED_IMAGE_TYPES;
        
        // Create upload directories if they don't exist
        $this->createDirectories();
    }
    
    /**
     * Create upload directories
     */
    private function createDirectories() {
        $directories = [
            $this->uploadPath . 'profile_pictures/',
            $this->uploadPath . 'nic_documents/'
        ];
        
        foreach ($directories as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
        }
    }
    
    /**
     * Upload profile picture
     */
    public function uploadProfilePicture($file) {
        return $this->uploadFile($file, 'profile_pictures/');
    }
    
    /**
     * Upload NIC document
     */
    public function uploadNicDocument($file, $type = 'front') {
        return $this->uploadFile($file, 'nic_documents/', $type . '_');
    }
    
    /**
     * Generic file upload method
     */
    private function uploadFile($file, $subfolder, $prefix = '') {
        // Validate file
        $validation = $this->validateFile($file);
        if (!$validation['valid']) {
            throw new Exception($validation['message']);
        }
        
        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = $prefix . uniqid() . '_' . time() . '.' . $extension;
        $targetPath = $this->uploadPath . $subfolder . $filename;
        
        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            return $subfolder . $filename; // Return relative path
        } else {
            throw new Exception("Failed to upload file");
        }
    }
    
    /**
     * Validate uploaded file
     */
    private function validateFile($file) {
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['valid' => false, 'message' => 'File upload error'];
        }
        
        // Check file size
        if ($file['size'] > $this->maxFileSize) {
            return ['valid' => false, 'message' => 'File size exceeds 5MB limit'];
        }
        
        // Check file type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mimeType, $this->allowedTypes)) {
            return ['valid' => false, 'message' => 'Invalid file type. Only JPG, JPEG, PNG allowed'];
        }
        
        return ['valid' => true];
    }
    
    /**
     * Delete uploaded file
     */
    public function deleteFile($filePath) {
        $fullPath = $this->uploadPath . $filePath;
        if (file_exists($fullPath)) {
            return unlink($fullPath);
        }
        return false;
    }
}
?>
