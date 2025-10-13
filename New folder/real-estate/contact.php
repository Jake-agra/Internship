<?php
session_start();
require_once __DIR__ . '/composer/vendor/autoload.php';
include('./includes/header.php');
include('./includes/toast.php');
include('./Database/connection.php');

// Handle form submission
$message_sent = false;
$error_message = '';

if ($_POST && isset($_POST['submit_contact'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $subject = trim($_POST['subject']);
    $message = trim($_POST['message']);
    
    // Basic validation
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $error_message = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Please enter a valid email address.';
    } else {
        try {
            // Initialize email service
            require_once __DIR__ . '/includes/EmailService.php';
            $emailService = new EmailService();
            
            // Try to send email
            if ($emailService->sendContactForm($name, $email, $phone, $subject, $message)) {
                // Only save to database if email was sent successfully
                try {
                    $stmt = $conn->prepare("INSERT INTO contact_messages (name, email, phone, subject, message, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
                    $stmt->bind_param("sssss", $name, $email, $phone, $subject, $message);
                    
                    if ($stmt->execute()) {
                        $message_sent = true;
                    } else {
                        $error_message = 'Email sent but failed to save record. Your message was still delivered.';
                        error_log('Database error: ' . $stmt->error);
                    }
                    $stmt->close();
                } catch (Exception $e) {
                    $error_message = 'Email sent but failed to save record. Your message was still delivered.';
                    error_log('Database exception: ' . $e->getMessage());
                }
            } else {
                $error_message = 'Failed to send email. ' . $emailService->getError();
            }
        } catch (Exception $e) {
            $error_message = 'An error occurred while processing your request. Please try again later.';
            error_log('Email service error: ' . $e->getMessage());
        }
    }
}
?>

    <!-- Contact Header Section -->
    <section class="contact-header">
        <div class="container">
            <h1>Contact Us</h1>
            <p>Have questions? We're here to help.</p>
        </div>
    </section>

    <!-- Contact Form Section -->
    <div class="contact-form-container">
        <?php 
        // Use toast notifications instead of Bootstrap alerts plsease but you can chose to use any if you want okay
        if ($message_sent): 
            showToastMessage('Thank you! Your message has been sent successfully. We\'ll get back to you soon.', 'success');
        endif; 
        
        if ($error_message): 
            showToastMessage($error_message, 'error');
        endif; 
        ?>

        <div class="contact-form">
            <form method="POST" action="">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="name" class="form-label">Full Name *</label>
                        <input type="text" class="form-control" id="name" name="name" required 
                               value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>"
                               placeholder="Your full name">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="email" class="form-label">Email Address *</label>
                        <input type="email" class="form-control" id="email" name="email" required
                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                               placeholder="your@email.com">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="phone" class="form-label">Phone Number</label>
                        <input type="tel" class="form-control" id="phone" name="phone"
                               value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>"
                               placeholder="Your phone number">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="subject" class="form-label">Subject *</label>
                        <select class="form-control" id="subject" name="subject" required>
                            <option value="">Select a subject</option>
                            <option value="General Inquiry" <?php echo (isset($_POST['subject']) && $_POST['subject'] == 'General Inquiry') ? 'selected' : ''; ?>>General Inquiry</option>
                            <option value="Property Information" <?php echo (isset($_POST['subject']) && $_POST['subject'] == 'Property Information') ? 'selected' : ''; ?>>Property Information</option>
                            <option value="Schedule Viewing" <?php echo (isset($_POST['subject']) && $_POST['subject'] == 'Schedule Viewing') ? 'selected' : ''; ?>>Schedule Viewing</option>
                            <option value="Buying Process" <?php echo (isset($_POST['subject']) && $_POST['subject'] == 'Buying Process') ? 'selected' : ''; ?>>Buying Process</option>
                            <option value="Selling Property" <?php echo (isset($_POST['subject']) && $_POST['subject'] == 'Selling Property') ? 'selected' : ''; ?>>Selling Property</option>
                        </select>
                    </div>
                </div>
                <div class="mb-3">
                    <label for="message" class="form-label">Message *</label>
                    <textarea class="form-control" id="message" name="message" rows="5" required 
                              placeholder="Your message..."><?php echo isset($_POST['message']) ? htmlspecialchars($_POST['message']) : ''; ?></textarea>
                </div>
                <div class="text-center">
                    <button type="submit" name="submit_contact" class="btn btn-submit">
                        Send Message
                    </button>
                </div>
            </form>
        </div>
    </div>



    <?php include('./includes/footer.php'); ?>

    <!-- Toast Container -->
    <?php renderToastContainer(); ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Toast Notification Scripts -->
    <?php renderToastScript(); ?>
</body>
</html>
