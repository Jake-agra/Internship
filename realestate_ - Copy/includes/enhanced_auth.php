<?php
/**
 * Enhanced Authentication System
 * Provides secure user authentication with advanced security features
 */

require_once 'security.php';

class EnhancedAuth {
    private $conn;
    private $max_login_attempts = 5;
    private $lockout_duration = 900; // 15 minutes
    
    public function __construct($database_connection) {
        $this->conn = $database_connection;
    }
    
    /**
     * Register a new user with enhanced security
     */
    public function register($data) {
        $errors = [];
        
        // Validate input data
        $first_name = InputValidator::sanitizeString($data['first_name'], 50);
        $last_name = InputValidator::sanitizeString($data['last_name'], 50);
        $email = InputValidator::sanitizeString($data['email'], 100);
        $phone = InputValidator::sanitizeString($data['phone'], 20);
        $password = $data['password'];
        $confirm_password = $data['confirm_password'];
        
        // Validation
        if (empty($first_name) || empty($last_name) || empty($email) || empty($password)) {
            $errors[] = 'All fields are required';
        }
        
        if (!InputValidator::validateEmail($email)) {
            $errors[] = 'Invalid email format';
        }
        
        if (!InputValidator::validatePhone($phone)) {
            $errors[] = 'Invalid phone number format';
        }
        
        if ($password !== $confirm_password) {
            $errors[] = 'Passwords do not match';
        }
        
        $password_errors = InputValidator::validatePassword($password);
        if (!empty($password_errors)) {
            $errors = array_merge($errors, $password_errors);
        }
        
        // Check if email already exists
        if (empty($errors)) {
            $check_stmt = $this->conn->prepare("SELECT id FROM users WHERE email = ?");
            $check_stmt->bind_param("s", $email);
            $check_stmt->execute();
            $result = $check_stmt->get_result();
            
            if ($result->num_rows > 0) {
                $errors[] = 'Email address is already registered';
            }
            $check_stmt->close();
        }
        
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        // Rate limiting check
        $identifier = $_SERVER['REMOTE_ADDR'] . '_register';
        if (!RateLimiter::checkRateLimit($identifier, 3, 3600)) { // 3 attempts per hour
            $errors[] = 'Too many registration attempts. Please try again later.';
            SecurityLogger::logSuspiciousActivity('Rate limit exceeded for registration', null);
            return ['success' => false, 'errors' => $errors];
        }
        
        try {
            // Hash password securely
            $hashed_password = AuthSecurity::hashPassword($password);
            
            // Generate email verification token
            $verification_token = AuthSecurity::generateSecureToken(32);
            
            // Insert user into database
            $stmt = $this->conn->prepare("INSERT INTO users (first_name, last_name, email, phone, password, verification_token, role_id, created_at) VALUES (?, ?, ?, ?, ?, ?, 3, NOW())");
            $stmt->bind_param("ssssss", $first_name, $last_name, $email, $phone, $hashed_password, $verification_token);
            
            if ($stmt->execute()) {
                $user_id = $this->conn->insert_id;
                
                // Record successful registration
                RateLimiter::recordAttempt($identifier);
                SecurityLogger::logEvent('user_registration', "New user registered: {$email}", $user_id);
                
                // TODO: Send verification email
                // $this->sendVerificationEmail($email, $verification_token);
                
                $stmt->close();
                return ['success' => true, 'user_id' => $user_id, 'message' => 'Registration successful! Please check your email for verification.'];
            } else {
                $stmt->close();
                return ['success' => false, 'errors' => ['Registration failed. Please try again.']];
            }
        } catch (Exception $e) {
            SecurityLogger::logEvent('registration_error', "Registration error: " . $e->getMessage());
            return ['success' => false, 'errors' => ['An error occurred during registration.']];
        }
    }
    
    /**
     * Login user with enhanced security
     */
    public function login($email, $password, $remember_me = false) {
        $errors = [];
        
        // Rate limiting check
        $identifier = $_SERVER['REMOTE_ADDR'] . '_login';
        if (!RateLimiter::checkRateLimit($identifier, $this->max_login_attempts, $this->lockout_duration)) {
            $errors[] = 'Too many login attempts. Please try again later.';
            SecurityLogger::logFailedLogin($email);
            return ['success' => false, 'errors' => $errors];
        }
        
        // Validate input
        if (empty($email) || empty($password)) {
            $errors[] = 'Email and password are required';
            RateLimiter::recordAttempt($identifier);
            return ['success' => false, 'errors' => $errors];
        }
        
        if (!InputValidator::validateEmail($email)) {
            $errors[] = 'Invalid email format';
            RateLimiter::recordAttempt($identifier);
            return ['success' => false, 'errors' => $errors];
        }
        
        try {
            // Get user from database
            $stmt = $this->conn->prepare("SELECT u.id, u.first_name, u.last_name, u.email, u.password, u.role_id, u.is_active, u.last_login, u.failed_login_attempts, u.locked_until, r.role_name FROM users u JOIN roles r ON u.role_id = r.id WHERE u.email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                $errors[] = 'Invalid email or password';
                RateLimiter::recordAttempt($identifier);
                SecurityLogger::logFailedLogin($email);
                $stmt->close();
                return ['success' => false, 'errors' => $errors];
            }
            
            $user = $result->fetch_assoc();
            $stmt->close();
            
            // Check if account is locked
            if ($user['locked_until'] && strtotime($user['locked_until']) > time()) {
                $errors[] = 'Account is temporarily locked due to too many failed attempts.';
                SecurityLogger::logSuspiciousActivity("Login attempt on locked account: {$email}");
                return ['success' => false, 'errors' => $errors];
            }
            
            // Check if account is active
            if (!$user['is_active']) {
                $errors[] = 'Account is inactive. Please contact administrator.';
                SecurityLogger::logSuspiciousActivity("Login attempt on inactive account: {$email}");
                return ['success' => false, 'errors' => $errors];
            }
            
            // Verify password
            if (!AuthSecurity::verifyPassword($password, $user['password'])) {
                // Update failed login attempts
                $this->updateFailedLoginAttempts($user['id']);
                $errors[] = 'Invalid email or password';
                RateLimiter::recordAttempt($identifier);
                SecurityLogger::logFailedLogin($email);
                return ['success' => false, 'errors' => $errors];
            }
            
            // Successful login
            $this->handleSuccessfulLogin($user, $remember_me);
            SecurityLogger::logSuccessfulLogin($user['id']);
            
            return ['success' => true, 'user' => $user, 'message' => 'Login successful!'];
            
        } catch (Exception $e) {
            SecurityLogger::logEvent('login_error', "Login error: " . $e->getMessage());
            return ['success' => false, 'errors' => ['An error occurred during login.']];
        }
    }
    
    /**
     * Handle successful login
     */
    private function handleSuccessfulLogin($user, $remember_me) {
        // Clear failed login attempts
        $stmt = $this->conn->prepare("UPDATE users SET failed_login_attempts = 0, locked_until = NULL, last_login = NOW() WHERE id = ?");
        $stmt->bind_param("i", $user['id']);
        $stmt->execute();
        $stmt->close();
        
        // Regenerate session ID for security
        session_regenerate_id(true);
        
        // Set session variables
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
        $_SESSION['user_role'] = $user['role_name'];
        $_SESSION['user_role_id'] = $user['role_id'];
        $_SESSION['logged_in'] = true;
        $_SESSION['last_activity'] = time();
        $_SESSION['login_time'] = time();
        
        // Set remember me cookie if requested
        if ($remember_me) {
            $token = AuthSecurity::generateSecureToken(32);
            $expires = time() + (30 * 24 * 60 * 60); // 30 days
            
            // Store remember token in database
            $stmt = $this->conn->prepare("INSERT INTO user_sessions (user_id, token, expires_at, created_at) VALUES (?, ?, FROM_UNIXTIME(?), NOW())");
            $stmt->bind_param("isi", $user['id'], $token, $expires);
            $stmt->execute();
            $stmt->close();
            
            // Set secure cookie
            setcookie('remember_token', $token, $expires, '/', '', isset($_SERVER['HTTPS']), true);
        }
    }
    
    /**
     * Update failed login attempts
     */
    private function updateFailedLoginAttempts($user_id) {
        $stmt = $this->conn->prepare("UPDATE users SET failed_login_attempts = failed_login_attempts + 1 WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        
        // Get current failed attempts
        $stmt = $this->conn->prepare("SELECT failed_login_attempts FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        
        // Lock account if too many failed attempts
        if ($user['failed_login_attempts'] >= $this->max_login_attempts) {
            $lock_until = date('Y-m-d H:i:s', time() + $this->lockout_duration);
            $stmt = $this->conn->prepare("UPDATE users SET locked_until = ? WHERE id = ?");
            $stmt->bind_param("si", $lock_until, $user_id);
            $stmt->execute();
            SecurityLogger::logSuspiciousActivity("Account locked due to failed login attempts", $user_id);
        }
        
        $stmt->close();
    }
    
    /**
     * Logout user securely
     */
    public function logout() {
        $user_id = $_SESSION['user_id'] ?? null;
        
        // Remove remember me token
        if (isset($_COOKIE['remember_token'])) {
            $stmt = $this->conn->prepare("DELETE FROM user_sessions WHERE token = ?");
            $stmt->bind_param("s", $_COOKIE['remember_token']);
            $stmt->execute();
            $stmt->close();
            
            setcookie('remember_token', '', time() - 3600, '/', '', isset($_SERVER['HTTPS']), true);
        }
        
        SecurityLogger::logEvent('user_logout', "User logged out", $user_id);
        AuthSecurity::secureLogout();
    }
    
    /**
     * Check remember me token
     */
    public function checkRememberToken() {
        if (isset($_COOKIE['remember_token']) && !isset($_SESSION['user_id'])) {
            $token = $_COOKIE['remember_token'];
            
            $stmt = $this->conn->prepare("SELECT u.id, u.first_name, u.last_name, u.email, u.role_id, r.role_name FROM users u JOIN roles r ON u.role_id = r.id JOIN user_sessions s ON u.id = s.user_id WHERE s.token = ? AND s.expires_at > NOW() AND u.is_active = 1");
            $stmt->bind_param("s", $token);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $user = $result->fetch_assoc();
                
                // Regenerate session ID
                session_regenerate_id(true);
                
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
                $_SESSION['user_role'] = $user['role_name'];
                $_SESSION['user_role_id'] = $user['role_id'];
                $_SESSION['logged_in'] = true;
                $_SESSION['last_activity'] = time();
                
                SecurityLogger::logEvent('auto_login', "User auto-logged in via remember token", $user['id']);
            }
            $stmt->close();
        }
    }
    
    /**
     * Change password
     */
    public function changePassword($user_id, $current_password, $new_password) {
        $errors = [];
        
        // Validate new password
        $password_errors = InputValidator::validatePassword($new_password);
        if (!empty($password_errors)) {
            $errors = array_merge($errors, $password_errors);
        }
        
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        try {
            // Get current password hash
            $stmt = $this->conn->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                return ['success' => false, 'errors' => ['User not found']];
            }
            
            $user = $result->fetch_assoc();
            
            // Verify current password
            if (!AuthSecurity::verifyPassword($current_password, $user['password'])) {
                return ['success' => false, 'errors' => ['Current password is incorrect']];
            }
            
            // Hash new password
            $new_password_hash = AuthSecurity::hashPassword($new_password);
            
            // Update password
            $stmt = $this->conn->prepare("UPDATE users SET password = ?, password_changed_at = NOW() WHERE id = ?");
            $stmt->bind_param("si", $new_password_hash, $user_id);
            
            if ($stmt->execute()) {
                SecurityLogger::logEvent('password_change', "Password changed successfully", $user_id);
                return ['success' => true, 'message' => 'Password changed successfully'];
            } else {
                return ['success' => false, 'errors' => ['Failed to update password']];
            }
            
            $stmt->close();
        } catch (Exception $e) {
            SecurityLogger::logEvent('password_change_error', "Password change error: " . $e->getMessage(), $user_id);
            return ['success' => false, 'errors' => ['An error occurred while changing password']];
        }
    }
    
    /**
     * Reset password request
     */
    public function requestPasswordReset($email) {
        if (!InputValidator::validateEmail($email)) {
            return ['success' => false, 'errors' => ['Invalid email format']];
        }
        
        // Rate limiting
        $identifier = $_SERVER['REMOTE_ADDR'] . '_password_reset';
        if (!RateLimiter::checkRateLimit($identifier, 3, 3600)) {
            return ['success' => false, 'errors' => ['Too many password reset requests. Please try again later.']];
        }
        
        try {
            $stmt = $this->conn->prepare("SELECT id, first_name FROM users WHERE email = ? AND is_active = 1");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $user = $result->fetch_assoc();
                $reset_token = AuthSecurity::generateSecureToken(32);
                $expires_at = date('Y-m-d H:i:s', time() + 3600); // 1 hour
                
                // Store reset token
                $stmt = $this->conn->prepare("UPDATE users SET reset_token = ?, reset_token_expires = ? WHERE id = ?");
                $stmt->bind_param("ssi", $reset_token, $expires_at, $user['id']);
                $stmt->execute();
                $stmt->close();
                
                // TODO: Send reset email
                // $this->sendPasswordResetEmail($email, $reset_token, $user['first_name']);
                
                SecurityLogger::logEvent('password_reset_request', "Password reset requested", $user['id']);
            }
            
            // Always return success for security (don't reveal if email exists)
            return ['success' => true, 'message' => 'If the email exists, you will receive password reset instructions.'];
            
        } catch (Exception $e) {
            SecurityLogger::logEvent('password_reset_error', "Password reset error: " . $e->getMessage());
            return ['success' => false, 'errors' => ['An error occurred while processing your request']];
        }
    }
    
    /**
     * Reset password with token
     */
    public function resetPassword($token, $new_password) {
        $errors = [];
        
        // Validate new password
        $password_errors = InputValidator::validatePassword($new_password);
        if (!empty($password_errors)) {
            $errors = array_merge($errors, $password_errors);
        }
        
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        try {
            $stmt = $this->conn->prepare("SELECT id FROM users WHERE reset_token = ? AND reset_token_expires > NOW() AND is_active = 1");
            $stmt->bind_param("s", $token);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                return ['success' => false, 'errors' => ['Invalid or expired reset token']];
            }
            
            $user = $result->fetch_assoc();
            $new_password_hash = AuthSecurity::hashPassword($new_password);
            
            // Update password and clear reset token
            $stmt = $this->conn->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_token_expires = NULL, password_changed_at = NOW() WHERE id = ?");
            $stmt->bind_param("si", $new_password_hash, $user['id']);
            
            if ($stmt->execute()) {
                SecurityLogger::logEvent('password_reset_success', "Password reset successfully", $user['id']);
                return ['success' => true, 'message' => 'Password reset successfully'];
            } else {
                return ['success' => false, 'errors' => ['Failed to reset password']];
            }
            
            $stmt->close();
        } catch (Exception $e) {
            SecurityLogger::logEvent('password_reset_error', "Password reset error: " . $e->getMessage());
            return ['success' => false, 'errors' => ['An error occurred while resetting password']];
        }
    }
}

// Initialize enhanced auth if database connection exists
if (isset($conn)) {
    $enhanced_auth = new EnhancedAuth($conn);
    
    // Check remember me token on every request
    $enhanced_auth->checkRememberToken();
}
?>
