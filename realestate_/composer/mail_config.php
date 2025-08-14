<?php
// SMTP Configuration for PHPMailer



//Gmail SMTP Configuration
return [  
    'host' => 'smtp.gmail.com',
    'username' => 'agrajeff15@gmail.com',
    'password' => 'your_email_password',
    'port' => 587,
    'encryption' => 'tls',
    'from_email' => 'agrajeff15@gmail.com',
    'from_name' => 'RealEstate',
    'admin_email' => 'agrajeff15@example.com'
];

// IMPORTANT: For Gmail to work, you MUST:
// 1. Enable 2-Step Verification for your Google account.
// 2. Generate an App Password and use it here instead of your regular password.
// 3. Use the 16-digit App Password in the 'password' field above.


// Steps to get Gmail App Password:
// 1. Go to your Google Account.
// 2. Click on "Security" in the left sidebar.
// 3. Under "Signing in to Google," click 2-Step Verification.
// 4. Follow the prompts to set up 2-Step Verification.
// 5. Once 2-Step Verification is enabled, go back to the "Security" page.
// 6. Under "Signing in to Google," find "App passwords" and click on it.
// 7. You may need to sign in again.
// 8. Select the app and device you want to generate the app password for.
// 9. Click "Generate."
// 10. Copy the 16-digit App Password and use it in the 'password' field above.


?>