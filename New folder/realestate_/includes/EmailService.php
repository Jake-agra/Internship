<?php
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

    public function sendContactForm($namee, $email, $phone, $message) {
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
            // $this->mailer->Subject = $this->config['contact_subject_prefix'] . $subject;
            $this->mailer->Body = $this->getContactEmailTemplate($namee, $email, $phone,  $message);
            $this->mailer->AltBody = strip_tags($this->mailer->Body);

            // Send the email
            $result = $this->mailer->send();
            
            // If successful, send auto-reply
            if ($result) {
                $this->sendAutoReply($email, $namee, $message);
            }
            
            return $result;
            
        } catch (Exception $e) {
            $this->error = "Message could not be sent. Mailer Error: {$this->mailer->ErrorInfo}";
            error_log($this->error);
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

    private function getContactEmailTemplate($namee, $email, $phone, $message) {
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


   private function getAutoReplyTemplate($namee, $message = '') { 
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
                <p>Dear " . htmlspecialchars($namee) . ",</p>
                
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


    public function getError() {
        return $this->error;
    }

    public function __destruct() {
        if ($this->mailer) {
            $this->mailer->smtpClose();
        }
    }
}
