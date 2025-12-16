<?php
// Include Composer autoloader
require_once __DIR__ . '/../composer/vendor/autoload.php';
require_once __DIR__ . '/smtp_config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class EmailService {
    private $config;
    private $mailer;
    private $error;

    public function __construct() {
        $this->config = include(__DIR__ . '/smtp_config.php');
        $this->initializeMailer();
    }

    private function initializeMailer() {
        $this->mailer = new PHPMailer(true);
        
        try {
            // Server settings
            $this->mailer->SMTPDebug = SMTP::DEBUG_OFF; // Always disable debug output for production
            $this->mailer->isSMTP();
            $this->mailer->Host = $this->config['smtp_host'];
            $this->mailer->SMTPAuth = $this->config['smtp_auth'];
            $this->mailer->Username = $this->config['smtp_username'];
            $this->mailer->Password = $this->config['smtp_password'];
            $this->mailer->SMTPSecure = $this->config['smtp_secure'];
            $this->mailer->Port = $this->config['smtp_port'];
            
            // Set email format to HTML
            $this->mailer->isHTML(true);
            $this->mailer->CharSet = 'UTF-8';
            
            // Default from address
            $this->mailer->setFrom(
                $this->config['from_email'],
                $this->config['from_name']
            );
            
            // Enable SMTP keep alive
            $this->mailer->SMTPKeepAlive = true;
            
        } catch (Exception $e) {
            $this->error = "Mailer Error: " . $e->getMessage();
            error_log($this->error);
        }
    }

    public function sendContactForm($namee, $email, $phone, $message, $subject = 'General Inquiry') {
        try {
            // Reset all addresses, attachments, and headers before sending
            $this->mailer->clearAddresses();
            $this->mailer->clearAttachments();
            $this->mailer->clearReplyTos();
            $this->mailer->clearCustomHeaders();

            // Recipients
            $this->mailer->addAddress($this->config['admin_email']);
            $this->mailer->addReplyTo($email, $namee);

            // Content
            $this->mailer->Subject = $this->config['contact_subject_prefix'] . $subject;
            $this->mailer->Body = $this->getContactEmailTemplate($namee, $email, $phone, $message, $subject);
            $this->mailer->AltBody = strip_tags($this->mailer->Body);

            // Send the email
            $result = $this->mailer->send();
            
            return $result;
            
        } catch (Exception $e) {
            $this->error = "Message could not be sent. Mailer Error: {$this->mailer->ErrorInfo}";
            error_log($this->error);
            return false;
        }
    }

    public function sendContactConfirmation($name, $email, $subject = 'General Inquiry') {
        try {
            // Reset all addresses, attachments, and headers before sending
            $this->mailer->clearAddresses();
            $this->mailer->clearAttachments();
            $this->mailer->clearReplyTos();
            $this->mailer->clearCustomHeaders();

            // Send to the person who submitted the form
            $this->mailer->addAddress($email, $name);
            
            $this->mailer->Subject = 'Thank you for contacting us - ' . $subject;
            $this->mailer->Body = $this->getContactConfirmationTemplate($name, $subject);
            $this->mailer->AltBody = strip_tags($this->mailer->Body);
            
            return $this->mailer->send();
            
        } catch (Exception $e) {
            error_log("Contact confirmation email failed: " . $e->getMessage());
            return false;
        }
    }

    private function sendAutoReply($email, $namee, $message = '') {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($email, $namee);
            
            $this->mailer->Subject = $this->config['auto_reply_subject'];
            $this->mailer->Body = $this->getAutoReplyTemplate($namee, $message);
            $this->mailer->AltBody = strip_tags($this->mailer->Body);
            
            return $this->mailer->send();
            
        } catch (Exception $e) {
            error_log("Auto-reply failed: " . $e->getMessage());
            return false;
        }
    }

    private function getContactEmailTemplate($namee, $email, $phone, $message, $subject = 'General Inquiry') {
        $companyName = htmlspecialchars($this->config['company_name']);
        $companyEmail = htmlspecialchars($this->config['company_email']);
        $companyPhone = htmlspecialchars($this->config['company_phone']);
        $companyAddress = htmlspecialchars($this->config['company_address']);
    return "
    <!DOCTYPE html>
    <html lang='en'>
    <head>
        <meta charset='UTF-8'>
        <style>
            body {
                font-family: Arial, sans-serif;
                background-color: #f4f4f7;
                margin: 0;
                padding: 20px;
            }
            .container {
                background: #ffffff;
                max-width: 600px;
                margin: auto;
                padding: 20px;
                border-radius: 10px;
                box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            }
            h2 {
                color: #333;
                margin-bottom: 15px;
            }
            .content p {
                font-size: 14px;
                margin: 6px 0;
            }
            .footer {
                margin-top: 20px;
                font-size: 12px;
                color: #777;
                text-align: center;
            }
        </style>
    </head>
    <body>
        <div class='container'>
            <h2>📩 New Website Inquiry</h2>
            <div class='content'>
                <p><strong>Name:</strong> " . htmlspecialchars($namee) . "</p>
                <p><strong>Email:</strong> " . htmlspecialchars($email) . "</p>
                <p><strong>Phone:</strong> " . htmlspecialchars($phone) . "</p>
                <p><strong>Subject:</strong> " . htmlspecialchars($subject) . "</p>
                <p><strong>Message:</strong></p>
                <p>" . nl2br(htmlspecialchars($message)) . "</p>
            </div>
            <div class='footer'>
                <p>This message was sent via the <strong>$companyName</strong> website contact form.</p>
                <p>Company Email: $companyEmail | Phone: $companyPhone</p>
            </div>
        </div>
    </body>
    </html>";
}


    private function getContactConfirmationTemplate($name, $subject) {
        $companyName = htmlspecialchars($this->config['company_name']);
        $companyEmail = htmlspecialchars($this->config['company_email']);
        $companyPhone = htmlspecialchars($this->config['company_phone']);
        $companyAddress = htmlspecialchars($this->config['company_address']);
        $websiteUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . "://" . $_SERVER['HTTP_HOST'];

        return "
        <!DOCTYPE html>
        <html>
        <head>
            <title>Thank you for contacting us</title>
            <meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\">
            <style>
                body { 
                    font-family: Arial, sans-serif; 
                    line-height: 1.6; 
                    color: #333; 
                    margin: 0;
                    padding: 0;
                    background-color: #f5f5f5;
                }
                .container { 
                    max-width: 600px; 
                    margin: 20px auto; 
                    background: #ffffff;
                    border-radius: 8px;
                    overflow: hidden;
                    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
                }
                .header { 
                    background-color: #1c28ccff; 
                    color: white; 
                    padding: 25px 20px; 
                    text-align: center; 
                }
                .content { 
                    padding: 30px; 
                    color: #444;
                    font-size: 15px;
                }
                .footer { 
                    background-color: #6c757d; 
                    color: white; 
                    padding: 15px; 
                    text-align: center; 
                    font-size: 12px; 
                    line-height: 1.5;
                }
                .button { 
                    display: inline-block; 
                    padding: 12px 25px; 
                    background-color: #007bff; 
                    color: white !important; 
                    text-decoration: none; 
                    border-radius: 4px; 
                    margin: 25px 0;
                    font-weight: bold;
                    text-align: center;
                }
                .contact-info {
                    margin: 25px 0;
                    padding: 15px;
                    background-color: #f8f9fa;
                    border-left: 4px solid #2738efff;
                }
                .contact-info p {
                    margin: 5px 0;
                    font-size: 14px;
                }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>Thank You for Contacting Us!</h2>
                </div>
                <div class='content'>
                    <p>Dear " . htmlspecialchars($name) . ",</p>
                    
                    <p>Thank you for reaching out to <strong>" . $companyName . "</strong>. 
                    We have received your inquiry regarding <strong>" . htmlspecialchars($subject) . "</strong> 
                    and our team will review it shortly. 
                    We typically respond within <strong>24–48 hours</strong> during our business hours.</p>
                    
                    <div class='contact-info'>
                        <p><strong>Your inquiry details:</strong></p>
                        <p>• <strong>Subject:</strong> " . htmlspecialchars($subject) . "</p>
                        <p>• <strong>Date:</strong> " . date('F j, Y') . "</p>
                        <p>• <strong>Reference #:</strong> #" . strtoupper(uniqid()) . "</p>
                    </div>
                    
                    <p>For your convenience, here are our contact details:</p>
                    <p>
                        <strong>" . $companyName . "</strong><br>
                        " . $companyAddress . "<br>
                        <strong>Phone:</strong> " . $companyPhone . "<br>
                        <strong>Email:</strong> 
                        <a href='mailto:" . $companyEmail . "' style='color: #007bff;'>" . $companyEmail . "</a>
                    </p>
                    
                    <p>If you need immediate assistance, please call us during business hours.</p>
                    
                    <p>Best regards,<br>
                    <strong>The " . $companyName . " Team</strong></p>
                    
                    <center>
                        <a href='" . $websiteUrl . "' class='button'>Visit Our Website</a>
                    </center>
                </div>
                <div class='footer'>
                    &copy; " . date('Y') . " " . $companyName . ". All rights reserved.<br>
                    <small>This is an automated message, please do not reply directly.</small>
                </div>
            </div>
        </body>
        </html>";
    }

    private function getAutoReplyTemplate($name, $message = '') { 
    $companyName    = htmlspecialchars($this->config['company_name']);
    $companyEmail   = htmlspecialchars($this->config['company_email']);
    $companyPhone   = htmlspecialchars($this->config['company_phone']);
    $companyAddress = htmlspecialchars($this->config['company_address']);
    $websiteUrl     = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . "://" . $_SERVER['HTTP_HOST'];

    return "
    <!DOCTYPE html>
    <html>
    <head>
        <title>Thank you for contacting us</title>
        <meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\">
        <style>
            body { 
                font-family: Arial, sans-serif; 
                line-height: 1.6; 
                color: #333; 
                margin: 0;
                padding: 0;
                background-color: #f5f5f5;
            }
            .container { 
                max-width: 600px; 
                margin: 20px auto; 
                background: #ffffff;
                border-radius: 8px;
                overflow: hidden;
                box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            }
            .header { 
                background-color: #1c28ccff; 
                color: white; 
                padding: 25px 20px; 
                text-align: center; 
            }
            .content { 
                padding: 30px; 
                color: #444;
                font-size: 15px;
            }
            .footer { 
                background-color: #6c757d; 
                color: white; 
                padding: 15px; 
                text-align: center; 
                font-size: 12px; 
                line-height: 1.5;
            }
            .button { 
                display: inline-block; 
                padding: 12px 25px; 
                background-color: #007bff; 
                color: white !important; 
                text-decoration: none; 
                border-radius: 4px; 
                margin: 25px 0;
                font-weight: bold;
                text-align: center;
            }
            .contact-info {
                margin: 25px 0;
                padding: 15px;
                background-color: #f8f9fa;
                border-left: 4px solid #2738efff;
            }
            .contact-info p {
                margin: 5px 0;
                font-size: 14px;
            }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>Thank You for Contacting Us!</h2>
            </div>
            <div class='content'>
                <p>Dear " . htmlspecialchars($name) . ",</p>
                
                <p>Thank you for reaching out to <strong>" . $companyName . "</strong>. 
                We have received your message and our team will review it shortly. 
                We typically respond within <strong>24–48 hours</strong> of our Working Hours.</p>
                
                <div class='contact-info'>
                    <p><strong>Your inquiry summary:</strong></p>
                    <p>" . nl2br(htmlspecialchars($message)) . "</p>
                    <p>• <strong>Date:</strong> " . date('F j, Y') . "</p>
                    <p>• <strong>Reference #:</strong> #" . strtoupper(uniqid()) . "</p>
                </div>
                
                <p>For your convenience, here are our contact details:</p>
                <p>
                    <strong>" . $companyName . "</strong><br>
                    " . $companyAddress . "<br>
                    <strong>Phone:</strong> " . $companyPhone . "<br>
                    <strong>Email:</strong> 
                    <a href='mailto:" . $companyEmail . "' style='color: #007bff;'>" . $companyEmail . "</a>
                </p>
                
                <p>If you need immediate assistance, please call us during business hours.</p>
                
                <p>Best regards,<br>
                <strong>The " . $companyName . " Team</strong></p>
                
                <center>
                    <a href='" . $websiteUrl . "' class='button'>Visit Our Page</a>
                </center>
            </div>
            <div class='footer'>
                &copy; " . date('Y') . " " . $companyName . ". All rights reserved.<br>
                <small>This is an automated message, please do not reply directly.</small>
            </div>
        </div>
    </body>
    </html>";
}


    public function sendWelcomeEmail($name, $email) {
        try {
            // Reset all addresses, attachments, and headers before sending
            $this->mailer->clearAddresses();
            $this->mailer->clearAttachments();
            $this->mailer->clearReplyTos();
            $this->mailer->clearCustomHeaders();

            // Send to the new user
            $this->mailer->addAddress($email, $name);
            
            $this->mailer->Subject = 'Welcome to ' . $this->config['company_name'] . '!';
            $this->mailer->Body = $this->getWelcomeEmailTemplate($name);
            $this->mailer->AltBody = strip_tags($this->mailer->Body);
            
            return $this->mailer->send();
            
        } catch (Exception $e) {
            error_log("Welcome email failed: " . $e->getMessage());
            return false;
        }
    }

    private function getWelcomeEmailTemplate($name) {
        $companyName = htmlspecialchars($this->config['company_name']);
        $companyEmail = htmlspecialchars($this->config['company_email']);
        $companyPhone = htmlspecialchars($this->config['company_phone']);
        $companyAddress = htmlspecialchars($this->config['company_address']);
        $websiteUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . "://" . $_SERVER['HTTP_HOST'];

        return "
        <!DOCTYPE html>
        <html>
        <head>
            <title>Welcome to " . $companyName . "!</title>
            <meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\">
            <style>
                body { 
                    font-family: Arial, sans-serif; 
                    line-height: 1.6; 
                    color: #333; 
                    margin: 0;
                    padding: 0;
                    background-color: #f5f5f5;
                }
                .container { 
                    max-width: 600px; 
                    margin: 20px auto; 
                    background: #ffffff;
                    border-radius: 8px;
                    overflow: hidden;
                    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
                }
                .header { 
                    background: linear-gradient(135deg, #2563eb, #1e40af); 
                    color: white; 
                    padding: 40px 20px; 
                    text-align: center; 
                }
                .header h1 {
                    margin: 0;
                    font-size: 28px;
                    font-weight: bold;
                }
                .content { 
                    padding: 40px 30px; 
                    color: #444;
                    font-size: 16px;
                }
                .features {
                    background-color: #f8f9fa;
                    padding: 20px;
                    border-radius: 8px;
                    margin: 25px 0;
                }
                .features ul {
                    margin: 0;
                    padding-left: 20px;
                }
                .features li {
                    margin: 8px 0;
                    color: #555;
                }
                .footer { 
                    background-color: #6c757d; 
                    color: white; 
                    padding: 20px; 
                    text-align: center; 
                    font-size: 12px; 
                    line-height: 1.5;
                }
                .button { 
                    display: inline-block; 
                    padding: 15px 30px; 
                    background: linear-gradient(135deg, #2563eb, #1e40af); 
                    color: white !important; 
                    text-decoration: none; 
                    border-radius: 6px; 
                    margin: 25px 0;
                    font-weight: bold;
                    text-align: center;
                }
                .contact-info {
                    margin: 30px 0;
                    padding: 20px;
                    background-color: #e3f2fd;
                    border-left: 4px solid #2563eb;
                    border-radius: 4px;
                }
                .contact-info p {
                    margin: 5px 0;
                    font-size: 14px;
                }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🏠 Welcome to " . $companyName . "!</h1>
                    <p style='margin: 10px 0 0 0; font-size: 18px; opacity: 0.9;'>Your journey to finding the perfect property starts here</p>
                </div>
                <div class='content'>
                    <p>Dear " . htmlspecialchars($name) . ",</p>
                    
                    <p>Welcome to <strong>" . $companyName . "</strong>! We're thrilled to have you join our community of property seekers and real estate enthusiasts.</p>
                    
                    <p>Your account has been successfully created, and you now have access to all our premium features:</p>
                    
                    <div class='features'>
                        <h3 style='margin-top: 0; color: #2563eb;'>What you can do with your account:</h3>
                        <ul>
                            <li><strong>Browse Properties:</strong> Access our extensive database of properties for sale and rent</li>
                            <li><strong>Save Favorites:</strong> Bookmark properties you're interested in for easy access</li>
                            <li><strong>Advanced Search:</strong> Use powerful filters to find your perfect property</li>
                            <li><strong>Property Inquiries:</strong> Contact agents directly about properties you love</li>
                            <li><strong>Saved Searches:</strong> Save your search criteria and get notified of new matches</li>
                            <li><strong>Property Alerts:</strong> Receive email updates when new properties match your criteria</li>
                        </ul>
                    </div>
                    
                    <p>Ready to start exploring? Click the button below to browse our latest properties:</p>
                    
                    <center>
                        <a href='" . $websiteUrl . "/property.php' class='button'>Browse Properties</a>
                    </center>
                    
                    <div class='contact-info'>
                        <h4 style='margin-top: 0; color: #2563eb;'>Need Help Getting Started?</h4>
                        <p>Our friendly team is here to help you every step of the way:</p>
                        <p><strong>📞 Phone:</strong> " . $companyPhone . "</p>
                        <p><strong>📧 Email:</strong> <a href='mailto:" . $companyEmail . "' style='color: #2563eb;'>" . $companyEmail . "</a></p>
                        <p><strong>🏢 Address:</strong> " . $companyAddress . "</p>
                        <p><strong>🕒 Business Hours:</strong> Monday - Friday: 9:00 AM - 6:00 PM</p>
                    </div>
                    
                    <p>Thank you for choosing " . $companyName . ". We look forward to helping you find your dream property!</p>
                    
                    <p>Best regards,<br>
                    <strong>The " . $companyName . " Team</strong></p>
                </div>
                <div class='footer'>
                    &copy; " . date('Y') . " " . $companyName . ". All rights reserved.<br>
                    <a href='" . $websiteUrl . "' style='color: #ccc;'>Visit our website</a> | 
                    <a href='" . $websiteUrl . "/contact.php' style='color: #ccc;'>Contact us</a>
                </div>
            </div>
        </body>
        </html>";
    }

    public function sendVerificationEmail($name, $email, $verification_token) {
        try {
            // Reset all addresses, attachments, and headers before sending
            $this->mailer->clearAddresses();
            $this->mailer->clearAttachments();
            $this->mailer->clearReplyTos();
            $this->mailer->clearCustomHeaders();

            // Send to the new user
            $this->mailer->addAddress($email, $name);
            
            $this->mailer->Subject = 'Verify Your Email Address - ' . $this->config['company_name'];
            $this->mailer->Body = $this->getVerificationEmailTemplate($name, $email, $verification_token);
            $this->mailer->AltBody = strip_tags($this->mailer->Body);
            
            return $this->mailer->send();
            
        } catch (Exception $e) {
            error_log("Verification email failed: " . $e->getMessage());
            return false;
        }
    }

    private function getVerificationEmailTemplate($name, $email, $verification_token) {
        $companyName = htmlspecialchars($this->config['company_name']);
        $companyEmail = htmlspecialchars($this->config['company_email']);
        $websiteUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . "://" . $_SERVER['HTTP_HOST'];
        $verificationUrl = $websiteUrl . '/verify_email.php?token=' . urlencode($verification_token);
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <title>Verify Your Email Address</title>
            <meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\">
            <style>
                body { 
                    font-family: Arial, sans-serif; 
                    line-height: 1.6;
                    color: #333;
                    margin: 0;
                    padding: 0;
                }
                .container {
                    max-width: 600px;
                    margin: 20px auto;
                    background: #ffffff;
                    border-radius: 8px;
                    overflow: hidden;
                    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
                }
                .header { 
                    background: linear-gradient(135deg, #10b981, #059669); 
                    color: white; 
                    padding: 40px 20px; 
                    text-align: center; 
                }
                .header h1 {
                    margin: 0;
                    font-size: 28px;
                    font-weight: bold;
                }
                .content { 
                    padding: 40px 30px; 
                    color: #444;
                    font-size: 16px;
                }
                .verification-box {
                    background-color: #f0fdf4;
                    border-left: 4px solid #10b981;
                    padding: 20px;
                    margin: 25px 0;
                    border-radius: 4px;
                }
                .verification-link {
                    display: inline-block;
                    background: #10b981;
                    color: white;
                    text-decoration: none;
                    padding: 12px 30px;
                    border-radius: 6px;
                    font-weight: bold;
                    margin: 20px 0;
                }
                .verification-link:hover {
                    background: #059669;
                    color: white;
                }
                .footer { 
                    background: #f8f9fa; 
                    color: #666; 
                    text-align: center; 
                    padding: 20px; 
                    border-top: 1px solid #ddd;
                    font-size: 12px;
                }
                .footer a {
                    color: #999;
                    text-decoration: none;
                }
                .note {
                    color: #999;
                    font-size: 14px;
                    margin-top: 20px;
                    padding: 15px;
                    background-color: #f8f9fa;
                    border-radius: 4px;
                }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1><i class='✓'></i> Verify Your Email</h1>
                </div>
                <div class='content'>
                    <p>Hello <strong>" . htmlspecialchars($name) . "</strong>,</p>
                    
                    <p>Thank you for registering with " . $companyName . "! To complete your registration and access all features, please verify your email address.</p>
                    
                    <div class='verification-box'>
                        <strong>Click the button below to verify your email:</strong><br><br>
                        <a href='" . htmlspecialchars($verificationUrl) . "' class='verification-link'>
                            Verify Email Address
                        </a>
                    </div>
                    
                    <p><strong>Or copy and paste this link in your browser:</strong></p>
                    <p style='word-break: break-all; background-color: #f8f9fa; padding: 10px; border-radius: 4px;'>
                        <a href='" . htmlspecialchars($verificationUrl) . "' style='color: #2563eb;'>" . htmlspecialchars($verificationUrl) . "</a>
                    </p>
                    
                    <div class='note'>
                        <strong>Note:</strong> This verification link will expire in 24 hours. If you didn't create this account, please ignore this email.
                    </div>
                    
                    <p>Best regards,<br><strong>The " . $companyName . " Team</strong></p>
                </div>
                <div class='footer'>
                    &copy; " . date('Y') . " " . $companyName . ". All rights reserved.<br>
                    <a href='" . $websiteUrl . "' style='color: #666;'>Visit our website</a> | 
                    <a href='" . $websiteUrl . "/contact.php' style='color: #666;'>Contact us</a>
                </div>
            </div>
        </body>
        </html>";
    }

    public function sendReviewNotificationEmail($ownerName, $ownerEmail, $propertyName, $reviewerName, $rating, $reviewText) {
        try {
            // Reset all addresses, attachments, and headers before sending
            $this->mailer->clearAddresses();
            $this->mailer->clearAttachments();
            $this->mailer->clearReplyTos();
            $this->mailer->clearCustomHeaders();

            // Send to property owner
            $this->mailer->addAddress($ownerEmail, $ownerName);
            
            $this->mailer->Subject = 'New Review: ' . htmlspecialchars($propertyName);
            $this->mailer->Body = $this->getReviewNotificationTemplate($ownerName, $propertyName, $reviewerName, $rating, $reviewText);
            $this->mailer->AltBody = strip_tags($this->mailer->Body);
            
            return $this->mailer->send();
            
        } catch (Exception $e) {
            error_log("Review notification email failed: " . $e->getMessage());
            return false;
        }
    }

    private function getReviewNotificationTemplate($ownerName, $propertyName, $reviewerName, $rating, $reviewText) {
        $companyName = htmlspecialchars($this->config['company_name']);
        $stars = str_repeat('★', $rating) . str_repeat('☆', 5 - $rating);
        $websiteUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . "://" . $_SERVER['HTTP_HOST'];
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <title>New Property Review</title>
            <meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\">
            <style>
                body { 
                    font-family: Arial, sans-serif; 
                    line-height: 1.6;
                    color: #333;
                    margin: 0;
                    padding: 0;
                }
                .container {
                    max-width: 600px;
                    margin: 20px auto;
                    background: #ffffff;
                    border-radius: 8px;
                    overflow: hidden;
                    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
                }
                .header { 
                    background: linear-gradient(135deg, #f59e0b, #d97706); 
                    color: white; 
                    padding: 40px 20px; 
                    text-align: center; 
                }
                .header h1 {
                    margin: 0;
                    font-size: 24px;
                    font-weight: bold;
                }
                .content { 
                    padding: 40px 30px; 
                    color: #444;
                    font-size: 16px;
                }
                .review-box {
                    background-color: #fff7ed;
                    border-left: 4px solid #f59e0b;
                    padding: 20px;
                    margin: 20px 0;
                    border-radius: 4px;
                }
                .stars {
                    font-size: 24px;
                    color: #f59e0b;
                    margin: 10px 0;
                    letter-spacing: 5px;
                }
                .reviewer-name {
                    font-weight: bold;
                    color: #2563eb;
                    margin-bottom: 10px;
                }
                .review-text {
                    line-height: 1.8;
                    color: #555;
                    padding: 15px;
                    background-color: white;
                    border-radius: 4px;
                    margin: 15px 0;
                }
                .button {
                    display: inline-block;
                    background: #2563eb;
                    color: white;
                    text-decoration: none;
                    padding: 12px 30px;
                    border-radius: 6px;
                    font-weight: bold;
                    margin: 20px 0;
                }
                .button:hover {
                    background: #1e40af;
                    color: white;
                }
                .footer { 
                    background: #f8f9fa; 
                    color: #666; 
                    text-align: center; 
                    padding: 20px; 
                    border-top: 1px solid #ddd;
                    font-size: 12px;
                }
                .footer a {
                    color: #999;
                    text-decoration: none;
                }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>⭐ New Review on Your Property</h1>
                </div>
                <div class='content'>
                    <p>Hello <strong>" . htmlspecialchars($ownerName) . "</strong>,</p>
                    
                    <p>Great news! Someone has left a review on your property <strong>" . htmlspecialchars($propertyName) . "</strong>.</p>
                    
                    <div class='review-box'>
                        <div class='reviewer-name'>Review by " . htmlspecialchars($reviewerName) . "</div>
                        <div class='stars'>" . htmlspecialchars($stars) . " (" . $rating . "/5 stars)</div>
                        
                        <div class='review-text'>
                            \"" . nl2br(htmlspecialchars(substr($reviewText, 0, 300))) . (strlen($reviewText) > 300 ? '...' : '') . "\"
                        </div>
                    </div>
                    
                    <p><a href='" . $websiteUrl . "/admin/dashboard.php' class='button'>View All Reviews</a></p>
                    
                    <p>Reviews help potential buyers and renters learn about your property. Thank you for maintaining an active listing!</p>
                    
                    <p>Best regards,<br><strong>The " . $companyName . " Team</strong></p>
                </div>
                <div class='footer'>
                    &copy; " . date('Y') . " " . $companyName . ". All rights reserved.<br>
                    <a href='" . $websiteUrl . "' style='color: #666;'>Visit our website</a>
                </div>
            </div>
        </body>
        </html>";
    }

    public function getError() {
        return $this->error;
    }

    public function __destruct() {
        if ($this->mailer) {
            $this->mailer->smtpClose();
        }
    }
}
