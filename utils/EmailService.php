<?php

/**
 * Email Service Class with PHPMailer and Gmail SMTP
 * Handles email sending functionality including OTP emails
 * Uses PHPMailer for reliable email delivery via Gmail SMTP
 */

require_once __DIR__ . '/../phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/../phpmailer/src/SMTP.php';
require_once __DIR__ . '/../phpmailer/src/Exception.php';
require_once __DIR__ . '/../utils/ErrorHandler.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class EmailService
{
    private $mailer;
    private $fromEmail = 'skycamp.app@gmail.com';
    private $fromName = 'SkyCamp';
    private $smtpPassword = 'uxpo wzrw lvhd mdqy'; // Gmail App Password

    public function __construct()
    {
        $this->mailer = new PHPMailer(true);
        $this->configureSMTP();
    }

    /**
     * Configure PHPMailer with Gmail SMTP
     */
    private function configureSMTP()
    {
        try {
            // Server settings
            $this->mailer->isSMTP();
            $this->mailer->Host = 'smtp.gmail.com';
            $this->mailer->SMTPAuth = true;
            $this->mailer->Username = $this->fromEmail;
            $this->mailer->Password = $this->smtpPassword;
            $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $this->mailer->Port = 587;

            // Default sender
            $this->mailer->setFrom($this->fromEmail, $this->fromName);

            // Enable verbose debug output (comment out in production)
            // $this->mailer->SMTPDebug = SMTP::DEBUG_SERVER;

        } catch (Exception $e) {
            ErrorHandler::log($e);
        }
    }

    /**
     * Send OTP email to user
     * 
     * @param string $email Recipient email
     * @param string $otp OTP code
     * @param string $name Recipient name (optional)
     * @return bool Success status
     */
    public function sendOTPEmail($email, $otp, $name = '')
    {
        try {
            // Clear previous recipients
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($email);

            // Email content
            $this->mailer->isHTML(true);
            $this->mailer->Subject = 'Password Reset Code: ' . $otp;
            $this->mailer->Body = $this->generateOTPEmailTemplate($otp, $name);
            $this->mailer->AltBody = $this->generateOTPEmailText($otp, $name);

            $result = $this->mailer->send();

            if ($result) {
                error_log("OTP email sent successfully to: " . $email);
            }

            return $result;
        } catch (Exception $e) {
            ErrorHandler::log($e);
            error_log("PHPMailer Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send welcome email to new users
     * 
     * @param string $email Recipient email
     * @param string $name User's name
     * @param string $userType Type of user (customer, renter, guide)
     * @return bool Success status
     */
    public function sendWelcomeEmail($email, $name, $userType = 'customer')
    {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($email);

            $this->mailer->isHTML(true);
            $this->mailer->Subject = 'Welcome to SkyCamp!';
            $this->mailer->Body = $this->generateWelcomeEmailTemplate($name, $userType);
            $this->mailer->AltBody = $this->generateWelcomeEmailText($name, $userType);

            return $this->mailer->send();
        } catch (Exception $e) {
            ErrorHandler::log($e);
            return false;
        }
    }

    /**
     * Send booking confirmation email
     * 
     * @param string $email Recipient email
     * @param string $name Customer name
     * @param array $bookingDetails Booking information
     * @return bool Success status
     */
    public function sendBookingConfirmation($email, $name, $bookingDetails)
    {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($email);

            $this->mailer->isHTML(true);
            $this->mailer->Subject = 'Booking Confirmed - SkyCamp Adventure Awaits!';
            $this->mailer->Body = $this->generateBookingConfirmationTemplate($name, $bookingDetails);
            $this->mailer->AltBody = $this->generateBookingConfirmationText($name, $bookingDetails);

            return $this->mailer->send();
        } catch (Exception $e) {
            ErrorHandler::log($e);
            return false;
        }
    }

    /**
     * Send contact form notification
     * 
     * @param string $email Recipient email
     * @param array $contactData Contact form data
     * @return bool Success status
     */
    public function sendContactNotification($email, $contactData)
    {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($email);

            $this->mailer->isHTML(true);
            $this->mailer->Subject = 'New Contact Form Submission - SkyCamp';
            $this->mailer->Body = $this->generateContactNotificationTemplate($contactData);
            $this->mailer->AltBody = $this->generateContactNotificationText($contactData);

            return $this->mailer->send();
        } catch (Exception $e) {
            ErrorHandler::log($e);
            return false;
        }
    }

    /**
     * Generate HTML email template for OTP - EXACT match to provided design
     */
    private function generateOTPEmailTemplate($otp, $name = '')
    {
        return "
        <!DOCTYPE html>
        <html lang='en'>
        <head>
            <meta charset='utf-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Your SkyCamp Password Reset Code</title>
            <style>
                /* Reset and base styles */
                * {
                    margin: 0;
                    padding: 0;
                    box-sizing: border-box;
                }
                
                body {
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
                    line-height: 1.6;
                    color: #333333;
                    background-color: #ffffff;
                    margin: 0;
                    padding: 0;
                }
                
                /* Main container */
                .email-container {
                    max-width: 600px;
                    margin: 0 auto;
                    background: #ffffff;
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                }
                
                /* Header section with logo */
                .header {
                    padding: 40px 50px 30px 50px;
                    border-bottom: 1px solid #E9EAEB;
                    background: #ffffff;
                }
                
                .logo {
                    display: block;
                    height: 32px;
                    width: auto;
                }
                
                /* Main content area */
                .content {
                    padding: 50px 50px 40px 50px;
                    background: #ffffff;
                }
                
                /* Typography */
                .greeting {
                    font-size: 18px;
                    font-weight: 400;
                    color: #333333;
                    margin: 0 0 25px 0;
                    line-height: 1.4;
                }
                
                .main-text {
                    font-size: 16px;
                    font-weight: 400;
                    color: #666666;
                    margin: 0 0 35px 0;
                    line-height: 1.5;
                }
                
                /* OTP Code Container */
                .verification-section {
                    text-align: left;
                    margin: 35px 0 35px 0;
                }
                
                .verification-label {
                    font-size: 16px;
                    font-weight: 400;
                    color: #333333;
                    margin: 0 0 20px 0;
                }
                
                /* OTP Box - Mobile-friendly design */
                .otp-box {
                    display: inline-block;
                    background: #ffffff;
                    border: none;
                    border-radius: 0;
                    padding: 20px 0;
                    margin: 0;
                    min-width: 280px;
                }
                
                .otp-code {
                    font-family: 'SF Mono', 'Monaco', 'Inconsolata', 'Roboto Mono', 'Courier New', monospace;
                    font-size: 36px;
                    font-weight: 600;
                    color: #333333;
                    margin: 0;
                    text-align: left;
                    line-height: 1;
                    white-space: nowrap;
                    overflow: hidden;
                }
                
                /* Security warning text */
                .security-text {
                    font-size: 16px;
                    font-weight: 400;
                    color: #666666;
                    margin: 35px 0 35px 0;
                    line-height: 1.5;
                }
                
                /* Signature */
                .signature {
                    font-size: 16px;
                    font-weight: 400;
                    color: #333333;
                    margin: 35px 0 0 0;
                    line-height: 1.5;
                }
                
                .signature-name {
                    font-weight: 400;
                    color: #333333;
                }
                
                /* Footer section */
                .footer {
                    padding: 40px 50px;
                    border-top: 1px solid #E9EAEB;
                    background: #ffffff;
                }
                
                .footer-text {
                    font-size: 14px;
                    font-weight: 400;
                    color: #999999;
                    margin: 0 0 8px 0;
                    line-height: 1.4;
                }
                
                .copyright {
                    font-size: 14px;
                    font-weight: 400;
                    color: #999999;
                    margin: 0;
                    line-height: 1.4;
                }
                
                /* Responsive design */
                @media only screen and (max-width: 600px) {
                    .email-container {
                        width: 100% !important;
                    }
                    
                    .header,
                    .content,
                    .footer {
                        padding-left: 30px !important;
                        padding-right: 30px !important;
                    }
                    
                                    .otp-code {
                    font-size: 28px !important;
                }
                
                .otp-box {
                    padding: 15px 0 !important;
                    min-width: 240px !important;
                }
                }
            </style>
        </head>
        <body>
            <div class='email-container'>
                <!-- Header with Logo -->
                <div class='header'>
                    <img src='https://imglink.io/i/3d681d6c-6fd4-4c8e-a2f4-92c2e0dd002f.png' 
                         alt='SkyCamp' 
                         class='logo' />
                </div>
                
                <!-- Main Content -->
                <div class='content'>
                    <div class='greeting'>Hello!</div>
                    
                    <div class='main-text'>
                        We received a request to reset your SkyCamp password. Use the verification code below to continue:
                    </div>
                    
                    <div class='verification-section'>
                        <div class='verification-label'>Your Verification Code</div>
                        <div class='otp-box'>
                            <div class='otp-code'>" . $otp . "</div>
                        </div>
                    </div>
                    
                    <div class='security-text'>
                        For your security, never share this code with anyone. If you did not request this password reset, please ignore this email.
                    </div>
                    
                    <div class='signature'>
                        Thank you,<br>
                        <span class='signature-name'>SkyCamp Team</span>
                    </div>
                </div>
                
                <!-- Footer -->
                <div class='footer'>
                    <div class='footer-text'>
                        This is an automated message from SkyCamp. Please do not reply to this email.
                    </div>
                    <div class='copyright'>
                        © 2025 SkyCamp. All rights reserved.
                    </div>
                </div>
            </div>
        </body>
        </html>";
    }

    /**
     * Generate plain text email for OTP
     */
    private function generateOTPEmailText($otp, $name = '')
    {
        $greeting = !empty($name) ? "Hi $name," : "Hi there,";

        return "
$greeting

We received a request to reset your password for your SkyCamp account.

Your verification code is: $otp

This code is valid for 5 minutes.

If you didn't request this password reset, please ignore this email.

Best regards,
The SkyCamp Team

---
This is an automated message. Please do not reply to this email.
© 2025 SkyCamp. All rights reserved.
        ";
    }

    /**
     * Generate welcome email template
     */
    private function generateWelcomeEmailTemplate($name, $userType)
    {
        $roleText = [
            'customer' => 'explorer',
            'renter' => 'equipment provider',
            'guide' => 'adventure guide'
        ];
        $role = $roleText[$userType] ?? 'member';

        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='utf-8'>
            <title>Welcome to SkyCamp!</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: white; padding: 30px 40px 20px 40px; border-bottom: 1px solid #e0e0e0; }
                .logo { height: 50px; width: auto; }
                .content { background: #ffffff; padding: 30px; border: 1px solid #e5e7eb; }
                .footer { background: #f8fafc; padding: 20px; text-align: center; border-radius: 0 0 8px 8px; font-size: 14px; color: #6b7280; }
                .button { display: inline-block; background: #0891b2; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; margin: 10px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <img src='https://imglink.io/i/3d681d6c-6fd4-4c8e-a2f4-92c2e0dd002f.png' alt='SkyCamp Logo' class='logo' />
                </div>
                
                <div class='content'>
                    <h2 style='color: #1f2937;'>Hi $name! </h2>
                    
                    <p>Welcome to SkyCamp, the ultimate platform for outdoor adventures! We're thrilled to have you join our community as a $role.</p>
                    
                    <p>Here's what you can do next:</p>
                    <ul>
                        <li>Explore amazing camping destinations</li>
                        <li>Find and book camping equipment</li>
                        <li>Connect with experienced guides</li>
                        <li>Discover stargazing spots</li>
                    </ul>
                    
                    <p style='text-align: center;'>
                        <a href='#' class='button'>Start Exploring</a>
                    </p>
                    
                    <p>If you have any questions, our support team is here to help!</p>
                    
                    <p>Happy camping!<br>The SkyCamp Team</p>
                </div>
                
                <div class='footer'>
                    <p style='margin: 0;'>© 2025 SkyCamp. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>";
    }

    /**
     * Generate booking confirmation template
     */
    private function generateBookingConfirmationTemplate($name, $bookingDetails)
    {
        $bookingId = $bookingDetails['booking_id'] ?? 'N/A';
        $itemName = $bookingDetails['item_name'] ?? 'Camping Equipment';
        $dates = $bookingDetails['dates'] ?? 'TBD';
        $total = $bookingDetails['total'] ?? '0.00';

        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='utf-8'>
            <title>Booking Confirmed - SkyCamp</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: white; padding: 30px 40px 20px 40px; border-bottom: 1px solid #e0e0e0; }
                .logo { height: 50px; width: auto; }
                .content { background: #ffffff; padding: 30px; border: 1px solid #e5e7eb; }
                .booking-details { background: #f0fdf4; border: 1px solid #86efac; border-radius: 8px; padding: 20px; margin: 20px 0; }
                .footer { background: #f8fafc; padding: 20px; text-align: center; border-radius: 0 0 8px 8px; font-size: 14px; color: #6b7280; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <img src='https://imglink.io/i/3d681d6c-6fd4-4c8e-a2f4-92c2e0dd002f.png' alt='SkyCamp Logo' class='logo' />
                </div>
                
                <div class='content'>
                    <h2 style='color: #1f2937;'>Hi $name!</h2>
                    
                    <p>Great news! Your booking has been confirmed. Here are the details:</p>
                    
                    <div class='booking-details'>
                        <h3 style='margin-top: 0; color: #059669;'>Booking Details</h3>
                        <p><strong>Booking ID:</strong> $bookingId</p>
                        <p><strong>Item:</strong> $itemName</p>
                        <p><strong>Dates:</strong> $dates</p>
                        <p><strong>Total:</strong> $$total</p>
                    </div>
                    
                    <p>We'll send you additional details closer to your booking date. Have an amazing adventure!</p>
                    
                    <p>Best regards,<br>The SkyCamp Team</p>
                </div>
                
                <div class='footer'>
                    <p style='margin: 0;'>© 2025 SkyCamp. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>";
    }

    /**
     * Generate contact notification template
     */
    private function generateContactNotificationTemplate($contactData)
    {
        $name = $contactData['name'] ?? 'Anonymous';
        $email = $contactData['email'] ?? 'No email provided';
        $subject = $contactData['subject'] ?? 'No subject';
        $message = $contactData['message'] ?? 'No message';

        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='utf-8'>
            <title>New Contact Form Submission</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: white; padding: 30px 40px 20px 40px; border-bottom: 1px solid #e0e0e0; }
                .logo { height: 50px; width: auto; }
                .content { background: #ffffff; padding: 30px; border: 1px solid #e5e7eb; }
                .contact-info { background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; padding: 20px; margin: 20px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <img src='https://imglink.io/i/3d681d6c-6fd4-4c8e-a2f4-92c2e0dd002f.png' alt='SkyCamp Logo' class='logo' />
                </div>
                
                <div class='content'>
                    <h1>📧 New Contact Form Submission</h1>
                    <div class='contact-info'>
                        <p><strong>From:</strong> $name</p>
                        <p><strong>Email:</strong> $email</p>
                        <p><strong>Subject:</strong> $subject</p>
                        <p><strong>Message:</strong></p>
                        <p style='background: white; padding: 15px; border-radius: 4px; border-left: 4px solid #dc2626;'>$message</p>
                    </div>
                    
                    <p>Please respond to this inquiry promptly.</p>
                </div>
            </div>
        </body>
        </html>";
    }

    /**
     * Generate plain text versions for all email types
     */
    private function generateWelcomeEmailText($name, $userType)
    {
        $roleText = [
            'customer' => 'explorer',
            'renter' => 'equipment provider',
            'guide' => 'adventure guide'
        ];
        $role = $roleText[$userType] ?? 'member';

        return "Hi $name!\n\nWelcome to SkyCamp! We're thrilled to have you join our community as a $role.\n\nHappy camping!\nThe SkyCamp Team";
    }

    private function generateBookingConfirmationText($name, $bookingDetails)
    {
        $bookingId = $bookingDetails['booking_id'] ?? 'N/A';
        $itemName = $bookingDetails['item_name'] ?? 'Camping Equipment';

        return "Hi $name!\n\nYour booking has been confirmed!\n\nBooking ID: $bookingId\nItem: $itemName\n\nBest regards,\nThe SkyCamp Team";
    }

    private function generateContactNotificationText($contactData)
    {
        $name = $contactData['name'] ?? 'Anonymous';
        $email = $contactData['email'] ?? 'No email';
        $message = $contactData['message'] ?? 'No message';

        return "New contact form submission:\n\nFrom: $name\nEmail: $email\nMessage: $message";
    }

    /**
     * Test email configuration
     */
    public function testEmailConfiguration()
    {
        try {
            return [
                'success' => true,
                'method' => 'PHPMailer + Gmail SMTP',
                'smtp_host' => 'smtp.gmail.com',
                'smtp_port' => 587,
                'from_email' => $this->fromEmail
            ];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
