<?php
session_start();

//add your vendor folder here to trigger the smtp to send well


include("./includes/header.php");
include("./Database/connection.php");
include("./includes/nav.php");



// handle form submission
$message_sent = false;
$error_message = '';

if($_POST && isset($_POST['submit_contact'])){

    // define all the fields for submitting  a message

    $name=trim($_POST['name']);
    $email=trim($_POST['email']);
    $phone=trim($_POST['phone']);
    $message=trim($_POST['message']);

    //basic validation
    if(empty($name) || empty($email) || empty($phone) || empty($message)){
        $error_message = 'All fields are required.';
    } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)){
        $error_message = 'Invalid email format.';
    } else {
        //send email
        $to = 'info@realestate.com';
        $subject = 'New Contact Form Submission';
        $body = "Name: $name\nEmail: $email\nPhone: $phone\nMessage: $message";
        $headers = "From: $email";

        if(mail($to, $subject, $body, $headers)){
            $message_sent = true;
        } else {
            $error_message = 'Failed to send message.';
        }
    }

}
?>





<?php renderToastContainer(); ?>

<div class="login-box">
  <h2>Contact Us</h2>
  
  <form action="contact.php" method="POST" id="registerForm">
   
  <!-- First Name -->
    <div class="form-group">
      <label for="name">First Name</label>
      <input type="text" id="name" name="name" required />
    </div>
    
   
    <!-- Email -->
    <div class="form-group">
      <label for="email">Email</label>
      <input type="email" id="email" name="email"  required />
    </div>

    <!-- Phone -->
    <div class="form-group">
      <label for="phone">Phone Number</label>
      <input type="tel" id="phone" name="phone"  required />
    </div>

    <!-- Message -->
    <div class="form-control">
      <label for="message">Enter your message</label>
      <!-- <input type="tel" id="phone" name="phonenum"  required /> -->
      <textarea name="message" id="message">Send message</textarea>
    </div>

    
    <button type="submit" name="submit_contact" class="login-btn">Send message</button>
    
  </form>
</div>
