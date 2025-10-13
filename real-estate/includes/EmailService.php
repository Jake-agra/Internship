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

    public function sendContactForm($name, $email, $phone, $subject, $message) {
        try {
            // Reset all addresses, attachments, and headers before sending
            $this->mailer->clearAddresses();
            $this->mailer->clearAttachments();
            $this->mailer->clearReplyTos();
            $this->mailer->clearCustomHeaders();

            // Recipients
            $this->mailer->addAddress($this->config['admin_email']);
            $this->mailer->addReplyTo($email, $name);

            // Content
            $this->mailer->Subject = $this->config['contact_subject_prefix'] . $subject;
            $this->mailer->Body = $this->getContactEmailTemplate($name, $email, $phone, $subject, $message);
            $this->mailer->AltBody = strip_tags($this->mailer->Body);

            // Send the email
            $result = $this->mailer->send();
            
            // If successful, send auto-reply
            if ($result) {
                $this->sendAutoReply($email, $name);
            }
            
            return $result;
            
        } catch (Exception $e) {
            $this->error = "Message could not be sent. Mailer Error: {$this->mailer->ErrorInfo}";
            error_log($this->error);
            return false;
        }
    }

    private function sendAutoReply($email, $name) {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($email, $name);
            
            $this->mailer->Subject = $this->config['auto_reply_subject'];
            $this->mailer->Body = $this->getAutoReplyTemplate($name);
            $this->mailer->AltBody = strip_tags($this->mailer->Body);
            
            return $this->mailer->send();
            
        } catch (Exception $e) {
            error_log("Auto-reply failed: " . $e->getMessage());
            return false;
        }
    }

    private function getContactEmailTemplate($name, $email, $phone, $subject, $message) {
        $companyName = htmlspecialchars($this->config['company_name']);
        $companyEmail = htmlspecialchars($this->config['company_email']);
        $companyPhone = htmlspecialchars($this->config['company_phone']);
        $companyAddress = htmlspecialchars($this->config['company_address']);
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <title>New Contact Form Submission</title>
            <meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\">
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; }
                .container { max-width: 600px; margin: 0 auto; padding: 0; }
                .header { background-color: #007bff; color: white; padding: 20px; text-align: center; }
                .content { background-color: #f8f9fa; padding: 20px; }
                .field { margin-bottom: 15px; }
                .field strong { color: #007bff; }
                .footer { 
                    background-color: #6c757d; 
                    color: white; 
                    padding: 15px; 
                    text-align: center; 
                    font-size: 12px; 
                    line-height: 1.5;
                }
                .company-info {
                    margin-top: 20px;
                    padding-top: 15px;
                    border-top: 1px solid #dee2e6;
                }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>New Contact Form Submission</h2>
                </div>
                <div class='content'>
                    <div class='field'><strong>Name:</strong> " . htmlspecialchars($name) . "</div>
                    <div class='field'><strong>Email:</strong> <a href='mailto:" . htmlspecialchars($email) . "'>" . htmlspecialchars($email) . "</a></div>
                    <div class='field'><strong>Phone:</strong> " . ($phone ? htmlspecialchars($phone) : 'Not provided') . "</div>
                    <div class='field'><strong>Subject:</strong> " . htmlspecialchars($subject) . "</div>
                    <div class='field'><strong>Message:</strong><br>" . nl2br(htmlspecialchars($message)) . "</div>
                    <div class='field'><strong>Submitted:</strong> " . date('Y-m-d H:i:s') . "</div>
                    
                    <div class='company-info'>
                        <strong>" . $companyName . "</strong><br>
                        " . $companyAddress . "<br>
                        Phone: " . $companyPhone . "<br>
                        Email: <a href='mailto:" . $companyEmail . "'>" . $companyEmail . "</a>
                    </div>
                </div>
                <div class='footer'>
                    This email was sent from the " . $companyName . " contact form. Please do not reply to this automated message.
                </div>
            </div>
        </body>
        </html>";
    }

    private function getAutoReplyTemplate($name) {
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
                    margin: 0 auto; 
                    padding: 0;
                    background: #ffffff;
                    border-radius: 5px;
                    overflow: hidden;
                    box-shadow: 0 0 10px rgba(0,0,0,0.1);
                }
                .header { 
                    background-color: #28a745; 
                    color: white; 
                    padding: 25px 20px; 
                    text-align: center; 
                }
                .content { 
                    padding: 30px; 
                    line-height: 1.7;
                    color: #444;
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
                    margin: 20px 0;
                    font-weight: bold;
                    text-align: center;
                }
                .contact-info {
                    margin: 25px 0;
                    padding: 15px;
                    background-color: #f8f9fa;
                    border-left: 4px solid #28a745;
                }
                .contact-info p {
                    margin: 5px 0;
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
                    
                    <p>Thank you for reaching out to <strong>" . $companyName . "</strong>. We have received your message and our team will review it shortly. We typically respond within 24-48 hours.</p>
                    
                    <div class='contact-info'>
                        <p><strong>Your inquiry is important to us.</strong> Here's a summary of the information we received:</p>
                        <p>• <strong>Inquiry Date:</strong> " . date('F j, Y') . "</p>
                        <p>• <strong>Reference Number:</strong> #" . strtoupper(uniqid()) . "</p>
                    </div>
                    
                    <p>For your convenience, here are our contact details:</p>
                    
                    <p><strong>" . $companyName . "</strong><br>
                    " . $companyAddress . "<br>
                    <strong>Phone:</strong> " . $companyPhone . "<br>
                    <strong>Email:</strong> <a href='mailto:" . $companyEmail . "' style='color: #007bff;'>" . $companyEmail . "</a></p>
                    
                    <p>If you need immediate assistance, please don't hesitate to call us during our business hours.</p>
                    
                    <p>We appreciate your interest in our services and look forward to assisting you!</p>
                    
                    <p>Best regards,<br>
                    <strong>The " . $companyName . " Team</strong></p>
                    
                    <center>
                        <a href='" . $websiteUrl . "' class='button'>Visit Our Website</a>
                    </center>
                </div>
                <div class='footer'>
                    &copy; " . date('Y') . " " . $companyName . ". All rights reserved.<br>
                    <small>This is an automated message, please do not reply directly to this email.</small>
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
