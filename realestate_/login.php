<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
include('./Database/connection.php');
include('./includes/security.php');
include('./includes/toast.php');

$page_title = 'Login - Real Estate';
$page_description = 'Login to your Real Estate account to access property listings, save favorites, and manage your profile.';

$error_message = '';
$success_message = '';

if($_SERVER['REQUEST_METHOD'] == 'POST'){
    $csrf = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!$csrf || !SecurityValidator::getInstance()->validateCSRFToken($csrf)) {
        $error_message = 'Invalid request. Please refresh and try again.';
    } else {
    $email = trim($_POST['email']);
    $password = $_POST['pass'];
    $selected_role = strtolower(trim($_POST['role'] ?? ''));

    if(empty($email) || empty($password)){
        $error_message = "Please fill in all fields";
    } else {
        // Prepare and execute the query with role information
        $stmt = $conn->prepare("SELECT u.id, u.email, u.password, u.role_id, u.email_verified, r.role_name FROM users u JOIN roles r ON u.role_id = r.id WHERE u.email = ? AND u.is_active = TRUE");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if($stmt->num_rows === 1){
            $stmt->bind_result($id, $db_email, $db_password, $role_id, $email_verified, $role_name);
            $stmt->fetch();
            
            // If user specified a role on login, ensure the account matches that role
            if (!empty($selected_role) && strtolower($role_name) !== $selected_role) {
                $error_message = "No account with that role was found for this email.";
            } elseif(!$email_verified) {
                $error_message = "Please verify your email address first. Check your inbox for the verification link.";
            } elseif(password_verify($password, $db_password)){
                // Set session variables
                $_SESSION['user_id'] = $id;
                $_SESSION['user_email'] = $db_email;
                $_SESSION['role_id'] = $role_id;
                $_SESSION['user_role'] = $role_name; // Updated for consistency
                $_SESSION['user_type'] = $role_name; // Keep for backward compatibility
                $_SESSION['logged_in'] = true;
                
                // Update last login
                $update_login = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                $update_login->bind_param("i", $id);
                $update_login->execute();
                $update_login->close();
            
                    // Role-based redirect
                    if($role_name === 'admin') {
                        header("Location: admin/dashboard.php");
                    } elseif($role_name === 'agent') {
                        header("Location: agent/dashboard.php");
                    } else {
                        header("Location: client/dashboard.php");
                    }
                exit();
            } else {
                $error_message = "Invalid email or password";
            }
        } else {
            $error_message = "Invalid email or password";
        }
        $stmt->close();
    }
    }
}

include('./includes/header.php');
?>

<style>
body {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 2rem 1rem;
}

.login-container {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    border-radius: 20px;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
    overflow: hidden;
    max-width: 900px;
    width: 100%;
    min-height: 600px;
    display: flex;
}

.login-image {
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

.login-image h2 {
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 1rem;
}

.login-image p {
    font-size: 1.1rem;
    opacity: 0.9;
    line-height: 1.6;
}

.login-form {
    flex: 1;
    padding: 3rem;
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.btn-login {
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    border: none;
    border-radius: 12px;
    padding: 1rem 2rem;
    font-weight: 600;
    color: white;
    transition: all 0.3s ease;
    margin-bottom: 1.5rem;
}

.btn-login:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(37, 99, 235, 0.3);
    color: white;
}

@media (max-width: 768px) {
    .login-container {
        flex-direction: column;
        max-width: 500px;
    }
    
    .login-image {
        padding: 2rem 1rem;
    }
    
    .login-form {
        padding: 2rem;
    }
}
</style>

<div class="login-container">
    <!-- Left Side - Image/Info -->
    <div class="login-image">
        <i class="fas fa-home fa-4x mb-4"></i>
        <h2>Welcome Back!</h2>
        <p>Sign in to access your real estate dashboard, manage your properties, and discover new opportunities in the market.</p>
    </div>

    <!-- Right Side - Login Form -->
    <div class="login-form">
        <div class="login-header text-center mb-4">
            <h1>Sign In</h1>
            <p class="text-muted">Access your account</p>
        </div>

        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <?= htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <?= htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <form method="POST" id="loginForm">
            <input type="hidden" name="csrf_token" value="<?= SecurityValidator::getInstance()->generateCSRFToken(); ?>">
            <div class="form-floating mb-3">
                <input type="email" class="form-control" id="email" name="email" placeholder="Email" required>
                <label for="email"><i class="fas fa-envelope me-2"></i>Email Address</label>
            </div>

            <div class="form-floating mb-3">
                <input type="password" class="form-control" id="pass" name="pass" placeholder="Password" required>
                <label for="pass"><i class="fas fa-lock me-2"></i>Password</label>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="me-3" style="min-width:180px;">
                    <label for="role" class="form-label mb-1">Account Type</label>
                    <select class="form-select form-select-sm" id="role" name="role">
                        <option value="admin">Admin</option>
                        <option value="agent">Agent</option>
                        <option value="client">Client</option>
                    </select>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="remember">
                    <label class="form-check-label" for="remember">
                        Remember me
                    </label>
                </div>
                <a href="#" class="text-decoration-none">Forgot password?</a>
            </div>

            <button type="submit" class="btn btn-login w-100">
                <i class="fas fa-sign-in-alt me-2"></i>Sign In
            </button>
        </form>

        <div class="text-center mt-4">
            <p class="mb-0">Don't have an account? 
                <a href="register.php" class="text-decoration-none fw-bold">Sign up here</a>
            </p>
        </div>

        <div class="text-center mt-4">
            <a href="index.php" class="btn btn-outline-secondary">
                <i class="fas fa-home me-2"></i>Back to Home
            </a>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Form validation
    const form = document.getElementById('loginForm');
    const email = document.getElementById('email');
    const password = document.getElementById('pass');

    form.addEventListener('submit', function(e) {
        let isValid = true;

        // Reset previous error states
        email.classList.remove('is-invalid');
        password.classList.remove('is-invalid');

        // Email validation
        if (!email.value.trim()) {
            email.classList.add('is-invalid');
            isValid = false;
        }

        // Password validation
        if (!password.value.trim()) {
            password.classList.add('is-invalid');
            isValid = false;
        }

        if (!isValid) {
            e.preventDefault();
            showToast('Please fill in all required fields', 'error');
        }
    });

    // Real-time validation
    email.addEventListener('input', function() {
        if (this.value.trim()) {
            this.classList.remove('is-invalid');
            this.classList.add('is-valid');
        }
    });

    password.addEventListener('input', function() {
        if (this.value.trim()) {
            this.classList.remove('is-invalid');
            this.classList.add('is-valid');
        }
    });
});
</script>

<?php include('./includes/footer.php'); ?>