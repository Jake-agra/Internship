<?php
session_start();
include('./Database/connection.php');
include('./includes/route.php');
include('./includes/security.php');

$message = '';
$success = false;
$token = isset($_GET['token']) ? trim($_GET['token']) : '';

if (empty($token)) {
    $message = 'No verification token provided.';
} else {
    // Find user with this token
    $stmt = $conn->prepare("SELECT id, email, email_verified FROM users WHERE email_verification_token = ? AND email_verified = 0");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        // Update user to mark email as verified and clear token
        $update_stmt = $conn->prepare("UPDATE users SET email_verified = 1, email_verification_token = NULL WHERE id = ?");
        $update_stmt->bind_param("i", $user['id']);
        
        if ($update_stmt->execute()) {
            $success = true;
            $message = 'Email verified successfully! You can now login.';
            $_SESSION['success_message'] = $message;
            
            // Redirect to login after 3 seconds
            header('Refresh: 3; url=login.php');
        } else {
            $message = 'Failed to verify email. Please try again.';
        }
        $update_stmt->close();
    } else {
        $message = 'Invalid or expired verification token.';
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Email - Real Estate</title>
    <link rel="stylesheet" href="bootstrap-5.3.7-dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Inter', sans-serif;
        }
        
        .verification-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            padding: 3rem;
            max-width: 500px;
            width: 100%;
            text-align: center;
        }
        
        .verification-icon {
            font-size: 4rem;
            margin-bottom: 1.5rem;
        }
        
        .icon-success {
            color: #10b981;
        }
        
        .icon-error {
            color: #ef4444;
        }
        
        .verification-title {
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: #1f2937;
        }
        
        .verification-message {
            font-size: 1rem;
            color: #6b7280;
            margin-bottom: 2rem;
            line-height: 1.6;
        }
        
        .btn-login {
            background: linear-gradient(135deg, #2563eb, #1e40af);
            border: none;
            color: white;
            padding: 0.75rem 2rem;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-login:hover {
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(37, 99, 235, 0.2);
        }
        
        .spinner {
            display: inline-block;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="verification-container">
        <?php if ($success): ?>
            <div class="verification-icon icon-success">
                <i class="fas fa-check-circle"></i>
            </div>
            <h1 class="verification-title">Email Verified!</h1>
            <p class="verification-message">
                Your email address has been successfully verified. 
                You will be redirected to the login page in a moment.
            </p>
            <a href="login.php" class="btn btn-login">
                <i class="fas fa-sign-in-alt me-2"></i>Go to Login
            </a>
        <?php else: ?>
            <div class="verification-icon icon-error">
                <i class="fas fa-times-circle"></i>
            </div>
            <h1 class="verification-title">Verification Failed</h1>
            <p class="verification-message">
                <?= htmlspecialchars($message); ?>
            </p>
            <div>
                <p class="text-muted mb-3">
                    <small>
                        If you didn't receive the verification email, please check your spam folder 
                        or <a href="contact.php">contact support</a>.
                    </small>
                </p>
                <a href="login.php" class="btn btn-login">
                    <i class="fas fa-arrow-left me-2"></i>Back to Login
                </a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
