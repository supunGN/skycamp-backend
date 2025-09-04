<?php

/**
 * OTP Password Reset Controller
 * Handles email-based OTP password reset functionality
 * Following secure password reset practices with time-limited OTPs
 */

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../utils/EmailService.php';
require_once __DIR__ . '/../utils/ErrorHandler.php';

class OTPPasswordResetController
{
    private $db;
    private $conn;
    private $emailService;

    public function __construct()
    {
        $this->db = new Database();
        $this->conn = $this->db->getConnection();
        $this->emailService = new EmailService();
    }

    /**
     * Request password reset - Generate and send OTP
     * 
     * @param string $email User's email address
     * @return array Response with success/error status
     */
    public function requestReset($email)
    {
        try {
            // Validate email format
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return ['success' => false, 'message' => 'Invalid email format'];
            }

            // Check if user exists
            $user = $this->findUserByEmail($email);
            if (!$user) {
                // Don't reveal if email doesn't exist for security
                return ['success' => true, 'message' => 'If the email exists, an OTP has been sent'];
            }

            // Check for recent reset requests (rate limiting)
            if ($this->hasRecentResetRequest($email)) {
                return ['success' => false, 'message' => 'Please wait before requesting another reset'];
            }

            // Generate OTP
            $otp = $this->generateOTP();
            $token = $this->generateSecureToken();
            $expiresAt = date('Y-m-d H:i:s', time() + 300); // 5 minutes expiry

            // Save reset token to database
            $tokenId = $this->saveResetToken($user['user_id'], $email, $token, $otp, $expiresAt);

            if (!$tokenId) {
                return ['success' => false, 'message' => 'Failed to generate reset token'];
            }

            // Send OTP email
            $emailSent = $this->emailService->sendOTPEmail($email, $otp, $user['first_name'] ?? '');

            if (!$emailSent) {
                // Clean up the token if email failed
                $this->deleteResetToken($tokenId);
                return ['success' => false, 'message' => 'Failed to send email. Please try again later'];
            }

            return [
                'success' => true,
                'message' => 'OTP sent successfully',
                'token' => $token, // Send token to frontend for verification
                'expires_in' => 300 // 5 minutes
            ];
        } catch (Exception $e) {
            ErrorHandler::log($e);
            return ['success' => false, 'message' => 'An error occurred. Please try again later'];
        }
    }

    /**
     * Verify OTP code
     * 
     * @param string $token Reset token
     * @param string $otp OTP code
     * @return array Response with success/error status
     */
    public function verifyOTP($token, $otp)
    {
        try {
            // Validate inputs
            if (empty($token) || empty($otp)) {
                return ['success' => false, 'message' => 'Token and OTP are required'];
            }

            // Find and validate reset token
            $resetData = $this->findResetToken($token, $otp);

            if (!$resetData) {
                return ['success' => false, 'message' => 'Invalid or expired OTP'];
            }

            // Check if token is expired
            if (strtotime($resetData['expires_at']) < time()) {
                $this->deleteResetToken($resetData['token_id']);
                return ['success' => false, 'message' => 'OTP has expired. Please request a new one'];
            }

            // Check if token has been used
            if ($resetData['used']) {
                return ['success' => false, 'message' => 'OTP has already been used'];
            }

            // Mark token as verified (but not used yet)
            $this->markTokenAsVerified($resetData['token_id']);

            return [
                'success' => true,
                'message' => 'OTP verified successfully',
                'token' => $token // Keep token for password reset step
            ];
        } catch (Exception $e) {
            ErrorHandler::log($e);
            return ['success' => false, 'message' => 'An error occurred during verification'];
        }
    }

    /**
     * Reset password after OTP verification
     * 
     * @param string $token Reset token
     * @param string $newPassword New password
     * @return array Response with success/error status
     */
    public function resetPassword($token, $newPassword)
    {
        try {
            // Validate inputs
            if (empty($token) || empty($newPassword)) {
                return ['success' => false, 'message' => 'Token and password are required'];
            }

            // Validate password strength
            if (strlen($newPassword) < 8) {
                return ['success' => false, 'message' => 'Password must be at least 8 characters long'];
            }

            // Find reset token
            $resetData = $this->findResetTokenByToken($token);

            if (!$resetData) {
                return ['success' => false, 'message' => 'Invalid reset token'];
            }

            // Check if token is expired
            if (strtotime($resetData['expires_at']) < time()) {
                $this->deleteResetToken($resetData['token_id']);
                return ['success' => false, 'message' => 'Reset token has expired'];
            }

            // Check if token has been used
            if ($resetData['used']) {
                return ['success' => false, 'message' => 'Reset token has already been used'];
            }

            // Update user password
            $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
            $passwordUpdated = $this->updateUserPassword($resetData['user_id'], $passwordHash);

            if (!$passwordUpdated) {
                return ['success' => false, 'message' => 'Failed to update password'];
            }

            // Mark token as used
            $this->markTokenAsUsed($resetData['token_id']);

            // Clean up old reset tokens for this user
            $this->cleanupOldResetTokens($resetData['user_id']);

            return [
                'success' => true,
                'message' => 'Password reset successfully'
            ];
        } catch (Exception $e) {
            ErrorHandler::log($e);
            return ['success' => false, 'message' => 'An error occurred during password reset'];
        }
    }

    /**
     * Find user by email
     */
    private function findUserByEmail($email)
    {
        $query = "SELECT u.user_id, u.email, u.role,
                         COALESCE(c.first_name, r.first_name, g.first_name) as first_name,
                         COALESCE(c.last_name, r.last_name, g.last_name) as last_name
                  FROM users u
                  LEFT JOIN customers c ON u.user_id = c.user_id
                  LEFT JOIN renters r ON u.user_id = r.user_id  
                  LEFT JOIN guides g ON u.user_id = g.user_id
                  WHERE u.email = ? AND u.is_active = 1";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([$email]);
        return $stmt->fetch();
    }

    /**
     * Check for recent reset requests
     */
    private function hasRecentResetRequest($email)
    {
        $query = "SELECT COUNT(*) as count FROM password_reset_tokens 
                  WHERE email = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 MINUTE)";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([$email]);
        $result = $stmt->fetch();

        return $result['count'] > 0;
    }

    /**
     * Generate 6-digit OTP
     */
    private function generateOTP()
    {
        return sprintf('%06d', mt_rand(100000, 999999));
    }

    /**
     * Generate secure token
     */
    private function generateSecureToken()
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * Save reset token to database
     */
    private function saveResetToken($userId, $email, $token, $otp, $expiresAt)
    {
        $tokenId = $this->generateUUID();

        $query = "INSERT INTO password_reset_tokens 
                  (token_id, user_id, email, token, otp_code, expires_at, used, created_at)
                  VALUES (?, ?, ?, ?, ?, ?, 0, NOW())";

        $stmt = $this->conn->prepare($query);
        $result = $stmt->execute([$tokenId, $userId, $email, $token, $otp, $expiresAt]);

        return $result ? $tokenId : false;
    }

    /**
     * Find reset token by token and OTP
     */
    private function findResetToken($token, $otp)
    {
        $query = "SELECT * FROM password_reset_tokens 
                  WHERE token = ? AND otp_code = ?";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([$token, $otp]);
        return $stmt->fetch();
    }

    /**
     * Find reset token by token only
     */
    private function findResetTokenByToken($token)
    {
        $query = "SELECT * FROM password_reset_tokens WHERE token = ?";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([$token]);
        return $stmt->fetch();
    }

    /**
     * Mark token as verified
     */
    private function markTokenAsVerified($tokenId)
    {
        $query = "UPDATE password_reset_tokens SET expires_at = DATE_ADD(NOW(), INTERVAL 10 MINUTE) WHERE token_id = ?";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$tokenId]);
    }

    /**
     * Mark token as used
     */
    private function markTokenAsUsed($tokenId)
    {
        $query = "UPDATE password_reset_tokens SET used = 1 WHERE token_id = ?";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$tokenId]);
    }

    /**
     * Update user password
     */
    private function updateUserPassword($userId, $passwordHash)
    {
        $query = "UPDATE users SET password_hash = ? WHERE user_id = ?";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$passwordHash, $userId]);
    }

    /**
     * Delete reset token
     */
    private function deleteResetToken($tokenId)
    {
        $query = "DELETE FROM password_reset_tokens WHERE token_id = ?";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$tokenId]);
    }

    /**
     * Clean up old reset tokens for user
     */
    private function cleanupOldResetTokens($userId)
    {
        $query = "DELETE FROM password_reset_tokens 
                  WHERE user_id = ? AND (used = 1 OR expires_at < NOW())";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$userId]);
    }

    /**
     * Generate UUID
     */
    private function generateUUID()
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );
    }
}
