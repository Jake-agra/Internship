<?php
session_start();
require_once __DIR__ . '/composer/vendor/autoload.php';
// include("./includes/header.php");
include("./Database/connection.php");
include("./includes/toast.php");


//Handle form submission
$message_sent = false;
$error_message='';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_contact'])){
    $namee = trim($_POST['name']);
    $email = trim($_POST['email']);
    $message = trim($_POST['message']);

    // Basic Validation
    if(empty($namee) || empty($email) || empty($message)) {
        $error_message = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Please enter a valid email address.';
    } else {
        try {
            // Initialize email service
            require_once __DIR__ . '/includes/EmailService.php';
            $emailService = new EmailService();

            // Try sending a  mail
        if ($message_sent = $emailService->sendContactForm($namee, $email, null, $message)) {

            // Save to database if mail sent successfully
           try{
                $stmt = $conn->prepare("INSERT INTO contact(name, email, message, created_at) VALUES (?, ?, ?, NOW())");
                $stmt->bind_param("sss", $namee, $email, $message);

               if ($stmt->execute()) {
                   $message_sent = true;
               }
               else {
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
        error_log('Contact form error: ' . $e->getMessage());
    }
}
}

?>
<body>
    <style>
      /* General Page Styling */
body {
    font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
    background: #379ceaff;
    margin: 0;
    padding: 0;
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
}

/* Contact Form Container */
.login-box {
    background: #fff;
    padding: 30px 40px;
    border-radius: 12px;
    box-shadow: 0px 4px 15px rgba(0, 0, 0, 0.1);
    width: 400px;
    max-width: 90%;
    text-align: center;
    animation: fadeIn 0.5s ease-in-out;
}

/* Heading */
.login-box h1 {
    margin-bottom: 20px;
    font-size: 26px;
    color: #333;
    font-weight: 600;
}

/* Form Groups */
.form-group {
    margin-bottom: 18px;
    text-align: left;
}

.form-group label {
    display: block;
    margin-bottom: 6px;
    font-size: 14px;
    font-weight: 500;
    color: #444;
}

/* Input & Textarea Styling */
.form-group input,
.form-group textarea {
    width: 100%;
    padding: 12px 14px;
    font-size: 15px;
    border: 1px solid #ccc;
    border-radius: 6px;
    outline: none;
    transition: all 0.3s ease;
    background: #fafafa;
    resize: none;
}

.form-group input:focus,
.form-group textarea:focus {
    border-color: #007bff;
    background: #fff;
    box-shadow: 0px 0px 6px rgba(0, 123, 255, 0.25);
}

/* Button */
.login-btn {
    width: 100%;
    padding: 12px;
    background: #007bff;
    border: none;
    border-radius: 6px;
    color: white;
    font-size: 16px;
    font-weight: 500;
    cursor: pointer;
    transition: background 0.3s ease;
}

.login-btn:hover {
    background: #0056b3;
}

/* Smooth Animation */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Responsive */
@media (max-width: 500px) {
    .login-box {
        width: 95%;
        padding: 25px 20px;
    }
    .login-box h1 {
        font-size: 22px;
    }
}

    </style>
    

<div class="login-box">
    <h1> Contact Us</h1>
    <form action="contactUS.php" method="POST">
        <div class="form-group">
            <label for="name">Full Name</label> 
       <input type="text" name="name" placeholder=" Enter Your Name" required>
        </div>

        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" name="email" placeholder="Enter Your Email" required>
        </div>

        <div class="form-group">
            <label class="form-label" for="message">Message</label>
            <textarea class="form-group" name="message" placeholder="Enter Your Message" required></textarea>
        </div>

        <button type="submit" name="submit_contact" class="login-btn">Send message</button>
    </form>
</div>

</body>
</html>
    