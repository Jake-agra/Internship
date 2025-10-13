<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
include('./includes/security.php');
include('./includes/header.php');
include('./Database/connection.php');
include('./includes/toast.php');

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verify_csrf($_POST['csrf_token'])) {
        $error_message = 'Invalid request. Please refresh and try again.';
    } else {
        $fname = trim($_POST['fnam']);
        $lname = trim($_POST['lname']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phonenum']);
        $password = $_POST['pass'];

        // Validation
        if (empty($fname) || empty($lname) || empty($email) || empty($phone) || empty($password)) {
            $error_message = 'Please fill in all fields';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error_message = 'Please enter a valid email address';
        } elseif (strlen($password) < 6) {
            $error_message = 'Password must be at least 6 characters long';
        } else {
            // Check for existing user in the database
            $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $check->bind_param("s", $email);
            $check->execute();
            $check->store_result();

            if ($check->num_rows > 0) {
                $error_message = 'Email is already registered. Please use a different email.';
            } else {
                // Get client role ID
                $role_query = $conn->prepare("SELECT id FROM roles WHERE role_name = 'client'");
                $role_query->execute();
                $role_result = $role_query->get_result();
                $role_row = $role_result->fetch_assoc();
                $client_role_id = $role_row['id'];
                $role_query->close();

                // Hash password and insert user
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO users (first_name, last_name, phone, email, password, role_id) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sssssi", $fname, $lname, $phone, $email, $hashed_password, $client_role_id);

                if ($stmt->execute()) {
                    $success_message = 'Registration successful! You can now login.';

                    // Send welcome email using Composer-based EmailService
                    try {
                        require_once 'includes/EmailService.php';
                        $emailService = new EmailService();
                        $emailService->sendWelcomeEmail($fname . ' ' . $lname, $email);
                    } catch (Exception $e) {
                        error_log('Welcome email failed for registration: ' . $e->getMessage());
                    }

                    // Clear form data on success
                    $_POST = array();
                } else {
                    $error_message = 'Registration failed. Please try again. Error: ' . $stmt->error;
                }
                $stmt->close();
            }
            $check->close();
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Real Estate</title>
    <link rel="stylesheet" href="bootstrap-5.3.7-dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2563eb;
            --secondary-color: #1e40af;
            --success-color: #10b981;
            --danger-color: #ef4444;
            --dark-color: #1f2937;
            --light-color: #f8fafc;
            --text-muted: #6b7280;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
        }

        .register-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            max-width: 1000px;
            width: 100%;
            min-height: 700px;
            display: flex;
        }

        .register-image {
            flex: 1;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            color: white;
            padding: 2rem;
            text-align: center;
        }

        .register-form {
            flex: 1;
            padding: 3rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
            max-height: 700px;
            overflow-y: auto;
        }

        .register-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .register-header h1 {
            font-size: 2rem;
            font-weight: 700;
            color: var(--dark-color);
            margin-bottom: 0.5rem;
        }

        .register-header p {
            color: var(--text-muted);
            font-size: 1rem;
        }

        .form-floating {
            margin-bottom: 1.25rem;
        }

        .form-control {
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            padding: 1rem;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.8);
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
            background: white;
        }

        .form-floating label {
            color: var(--text-muted);
            font-weight: 500;
        }

        .btn-register {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
            border-radius: 12px;
            padding: 1rem 2rem;
            font-weight: 600;
            font-size: 1rem;
            color: white;
            transition: all 0.3s ease;
            margin-bottom: 1.5rem;
        }

        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(37, 99, 235, 0.3);
            color: white;
        }

        .alert {
            border-radius: 10px;
            border: none;
            padding: 1rem 1.25rem;
            margin-bottom: 1.5rem;
        }

        .alert-danger {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger-color);
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success-color);
        }

        .auth-links {
            text-align: center;
            margin-top: 1rem;
        }

        .auth-links a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .auth-links a:hover {
            color: var(--secondary-color);
        }

        .back-to-home {
            position: absolute;
            top: 2rem;
            left: 2rem;
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .back-to-home:hover {
            color: rgba(255, 255, 255, 0.8);
            transform: translateX(-5px);
        }

        .password-strength {
            font-size: 0.8rem;
            margin-top: 0.5rem;
        }

        .strength-weak { color: var(--danger-color); }
        .strength-medium { color: #f59e0b; }
        .strength-strong { color: var(--success-color); }

        @media (max-width: 768px) {
            .register-container {
                flex-direction: column;
                max-width: 400px;
                min-height: auto;
            }

            .register-image {
                padding: 1.5rem;
            }

            .register-form {
                padding: 2rem;
                max-height: none;
            }

            .back-to-home {
                position: static;
                display: block;
                text-align: center;
                margin-bottom: 1rem;
                color: white;
            }
        }
    </style>
</head>
<body>
    <a href="index.php" class="back-to-home">
        <i class="fas fa-arrow-left me-2"></i>Back to Home
    </a>

    <div class="register-container">
        <!-- Left Side - Image/Branding -->
        <div class="register-image d-none d-md-flex">
            <div>
                <h2><i class="fas fa-home me-3"></i>Join Real Estate</h2>
                <p>Create your account and start your journey to finding the perfect property. Get access to exclusive listings and personalized recommendations.</p>
                <div class="mt-4">
                    <div class="row text-center">
                        <div class="col-4">
                            <i class="fas fa-search fa-2x mb-2"></i>
                            <p class="small">Search Properties</p>
                        </div>
                        <div class="col-4">
                            <i class="fas fa-heart fa-2x mb-2"></i>
                            <p class="small">Save Favorites</p>
                        </div>
                        <div class="col-4">
                            <i class="fas fa-handshake fa-2x mb-2"></i>
                            <p class="small">Connect Agents</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Side - Register Form -->
        <div class="register-form">
            <div class="register-header">
                <h1>Create Account</h1>
                <p>Join our real estate community today</p>
            </div>
            
            <?php if(!empty($error_message)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>
            
            <?php if(!empty($success_message)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>
            
            <form action="register.php" method="POST" id="registerForm">
                <input type="hidden" name="csrf_token" value="<?= csrf_token(); ?>">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-floating">
                            <input type="text" class="form-control" id="fname" name="fnam" 
                                   placeholder="First Name" 
                                   value="<?php echo isset($_POST['fnam']) ? htmlspecialchars($_POST['fnam']) : ''; ?>" required>
                            <label for="fname"><i class="fas fa-user me-2"></i>First Name</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating">
                            <input type="text" class="form-control" id="lname" name="lname" 
                                   placeholder="Last Name" 
                                   value="<?php echo isset($_POST['lname']) ? htmlspecialchars($_POST['lname']) : ''; ?>" required>
                            <label for="lname"><i class="fas fa-user me-2"></i>Last Name</label>
                        </div>
                    </div>
                </div>

                <div class="form-floating">
                    <input type="email" class="form-control" id="email" name="email" 
                           placeholder="name@example.com" 
                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                    <label for="email"><i class="fas fa-envelope me-2"></i>Email Address</label>
                </div>

                <div class="form-floating">
                    <input type="tel" class="form-control" id="phone" name="phonenum" 
                           placeholder="Phone Number" 
                           value="<?php echo isset($_POST['phonenum']) ? htmlspecialchars($_POST['phonenum']) : ''; ?>" required>
                    <label for="phone"><i class="fas fa-phone me-2"></i>Phone Number</label>
                </div>

                <div class="form-floating">
                    <input type="password" class="form-control" id="pass" name="pass" 
                           placeholder="Password" minlength="6" required>
                    <label for="pass"><i class="fas fa-lock me-2"></i>Password</label>
                    <div class="password-strength" id="passwordStrength"></div>
                </div>
                
                <button type="submit" class="btn btn-register w-100">
                    <i class="fas fa-user-plus me-2"></i>Create Account
                </button>
            </form>

            <div class="auth-links">
                Already have an account? <a href="login.php">Sign In</a>
            </div>
        </div>
    </div>

    <?php 
    // Render toast script and show messages
    if (function_exists('renderToastScript')) {
        renderToastScript();
        
        if(!empty($error_message)) {
            showToastMessage($error_message, 'error');
        }
        
        if(!empty($success_message)) {
            showToastMessage($success_message, 'success');
        }
    }
    ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Password strength indicator
        document.getElementById('pass').addEventListener('input', function() {
            const password = this.value;
            const strengthDiv = document.getElementById('passwordStrength');
            
            if (password.length === 0) {
                strengthDiv.innerHTML = '';
                return;
            }
            
            let strength = 0;
            let feedback = [];
            
            if (password.length >= 6) strength++;
            else feedback.push('At least 6 characters');
            
            if (/[A-Z]/.test(password)) strength++;
            else feedback.push('One uppercase letter');
            
            if (/[a-z]/.test(password)) strength++;
            else feedback.push('One lowercase letter');
            
            if (/[0-9]/.test(password)) strength++;
            else feedback.push('One number');
            
            if (/[^A-Za-z0-9]/.test(password)) strength++;
            else feedback.push('One special character');
            
            if (strength < 2) {
                strengthDiv.innerHTML = '<span class="strength-weak">Weak password</span>';
            } else if (strength < 4) {
                strengthDiv.innerHTML = '<span class="strength-medium">Medium strength</span>';
            } else {
                strengthDiv.innerHTML = '<span class="strength-strong">Strong password</span>';
            }
        });
    </script>
</body>
</html>