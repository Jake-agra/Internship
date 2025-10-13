<?php
// Load environment variables from .env file if it exists
if (file_exists(__DIR__ . '/../.env')) {
    $lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        if (!array_key_exists($name, $_ENV)) {
            $_ENV[$name] = $value;
            putenv("$name=$value");
        }
    }
}

return [
    'smtp_host' => getenv('SMTP_HOST') ?: 'smtp.example.com',
    'smtp_port' => (int)(getenv('SMTP_PORT') ?: 587),
    'smtp_username' => getenv('SMTP_USERNAME') ?: 'your_smtp_username',
    'smtp_password' => getenv('SMTP_PASSWORD') ?: 'your_smtp_password',
    'from_email' => getenv('FROM_EMAIL') ?: 'noreply@realestate.com',
    'from_name' => getenv('FROM_NAME') ?: 'RealEstate',
    'admin_email' => getenv('ADMIN_EMAIL') ?: 'admin@realestate.com',
    'smtp_secure' => getenv('SMTP_SECURE') ?: 'tls',
    'smtp_auth' => filter_var(getenv('SMTP_AUTH') !== false ? getenv('SMTP_AUTH') : 'true', FILTER_VALIDATE_BOOLEAN),
    'debug' => (int)(getenv('SMTP_DEBUG') !== false ? getenv('SMTP_DEBUG') : 0),
    
    // Email templates
    'contact_subject_prefix' => 'New Contact Form Submission: ',
    'auto_reply_subject' => 'Thank you for contacting Real Estate ',
    
    // Company information
    'company_name' => getenv('COMPANY_NAME') ?: 'Real Estate',
    'company_email' => getenv('COMPANY_EMAIL') ?: 'agrajeff15@gmail.com',
    'company_address' => getenv('COMPANY_ADDRESS') ?: '123 Real Estate Street, Business District, City, State 12345',
    'company_phone' => getenv('COMPANY_PHONE') ?: '+1 (555) 123-4567',
];
?>
