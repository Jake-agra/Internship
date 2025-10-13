<?php
/**
 * Security and Validation Helper Class
 * Provides comprehensive security functions for the Real Estate application
 */

class SecurityValidator {
    
    private static $instance = null;
    private $settings;
    
    public function __construct() {
        $this->settings = [
            'max_login_attempts' => 5,
            'lockout_duration' => 1800, // 30 minutes
            'password_min_length' => 8,
            'csrf_token_lifetime' => 3600, // 1 hour
            'file_upload_max_size' => 10 * 1024 * 1024, // 10MB
            'allowed_file_types' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
            'max_file_uploads' => 20
        ];
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Generate CSRF Token
     */
    public function generateCSRFToken() {
        if (!isset($_SESSION['csrf_token']) || !isset($_SESSION['csrf_token_time']) 
            || (time() - $_SESSION['csrf_token_time']) > $this->settings['csrf_token_lifetime']) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            $_SESSION['csrf_token_time'] = time();
        }
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Validate CSRF Token
     */
    public function validateCSRFToken($token) {
        if (!isset($_SESSION['csrf_token']) || !isset($_SESSION['csrf_token_time'])) {
            return false;
        }
        
        if ((time() - $_SESSION['csrf_token_time']) > $this->settings['csrf_token_lifetime']) {
            unset($_SESSION['csrf_token'], $_SESSION['csrf_token_time']);
            return false;
        }
        
        return hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Sanitize input data
     */
    public function sanitizeInput($input, $type = 'string') {
        if (is_array($input)) {
            return array_map(function($item) use ($type) {
                return $this->sanitizeInput($item, $type);
            }, $input);
        }
        
        switch ($type) {
            case 'email':
                return filter_var(trim($input), FILTER_SANITIZE_EMAIL);
            case 'url':
                return filter_var(trim($input), FILTER_SANITIZE_URL);
            case 'int':
                return filter_var($input, FILTER_SANITIZE_NUMBER_INT);
            case 'float':
                return filter_var($input, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
            case 'string':
            default:
                return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
        }
    }
    
    /**
     * Validate input data
     */
    public function validateInput($input, $rules) {
        $errors = [];
        
        foreach ($rules as $field => $fieldRules) {
            $value = $input[$field] ?? '';
            
            foreach ($fieldRules as $rule => $param) {
                switch ($rule) {
                    case 'required':
                        if ($param && empty($value)) {
                            $errors[$field][] = ucfirst($field) . ' is required';
                        }
                        break;
                        
                    case 'min_length':
                        if (strlen($value) < $param) {
                            $errors[$field][] = ucfirst($field) . " must be at least {$param} characters";
                        }
                        break;
                        
                    case 'max_length':
                        if (strlen($value) > $param) {
                            $errors[$field][] = ucfirst($field) . " must not exceed {$param} characters";
                        }
                        break;
                        
                    case 'email':
                        if ($param && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                            $errors[$field][] = ucfirst($field) . ' must be a valid email address';
                        }
                        break;
                        
                    case 'numeric':
                        if ($param && !is_numeric($value)) {
                            $errors[$field][] = ucfirst($field) . ' must be a number';
                        }
                        break;
                        
                    case 'positive':
                        if ($param && $value <= 0) {
                            $errors[$field][] = ucfirst($field) . ' must be a positive number';
                        }
                        break;
                        
                    case 'pattern':
                        if (!preg_match($param, $value)) {
                            $errors[$field][] = ucfirst($field) . ' format is invalid';
                        }
                        break;
                        
                    case 'in':
                        if (!in_array($value, $param)) {
                            $errors[$field][] = ucfirst($field) . ' contains an invalid value';
                        }
                        break;
                }
            }
        }
        
        return $errors;
    }
    
    /**
     * Hash password securely
     */
    public function hashPassword($password) {
        return password_hash($password, PASSWORD_DEFAULT);
    }
    
    /**
     * Verify password
     */
    public function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
    
    /**
     * Validate password strength
     */
    public function validatePassword($password) {
        $errors = [];
        
        if (strlen($password) < $this->settings['password_min_length']) {
            $errors[] = "Password must be at least {$this->settings['password_min_length']} characters long";
        }
        
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = "Password must contain at least one uppercase letter";
        }
        
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = "Password must contain at least one lowercase letter";
        }
        
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = "Password must contain at least one number";
        }
        
        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors[] = "Password must contain at least one special character";
        }
        
        return $errors;
    }
    
    /**
     * Rate limiting for login attempts
     */
    public function checkLoginAttempts($identifier) {
        $key = "login_attempts_{$identifier}";
        $attempts = $_SESSION[$key] ?? 0;
        $lockout_time = $_SESSION["{$key}_lockout"] ?? 0;
        
        // Check if currently locked out
        if ($lockout_time > time()) {
            return [
                'allowed' => false,
                'message' => 'Account temporarily locked. Please try again later.',
                'lockout_remaining' => $lockout_time - time()
            ];
        }
        
        // Reset if lockout period has passed
        if ($lockout_time <= time() && $lockout_time > 0) {
            unset($_SESSION[$key], $_SESSION["{$key}_lockout"]);
            $attempts = 0;
        }
        
        return [
            'allowed' => true,
            'attempts' => $attempts,
            'remaining' => $this->settings['max_login_attempts'] - $attempts
        ];
    }
    
    /**
     * Record failed login attempt
     */
    public function recordFailedLogin($identifier) {
        $key = "login_attempts_{$identifier}";
        $attempts = ($_SESSION[$key] ?? 0) + 1;
        $_SESSION[$key] = $attempts;
        
        if ($attempts >= $this->settings['max_login_attempts']) {
            $_SESSION["{$key}_lockout"] = time() + $this->settings['lockout_duration'];
        }
        
        return $attempts;
    }
    
    /**
     * Clear login attempts on successful login
     */
    public function clearLoginAttempts($identifier) {
        $key = "login_attempts_{$identifier}";
        unset($_SESSION[$key], $_SESSION["{$key}_lockout"]);
    }
    
    /**
     * Validate file upload
     */
    public function validateFileUpload($file) {
        $errors = [];
        
        // Check if file was uploaded
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            $errors[] = 'Invalid file upload';
            return $errors;
        }
        
        // Check file size
        if ($file['size'] > $this->settings['file_upload_max_size']) {
            $errors[] = 'File size exceeds maximum allowed size';
        }
        
        // Check file extension
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $this->settings['allowed_file_types'])) {
            $errors[] = 'File type not allowed';
        }
        
        // Check MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        $allowed_mimes = [
            'image/jpeg',
            'image/jpg', 
            'image/png',
            'image/gif',
            'image/webp'
        ];
        
        if (!in_array($mime_type, $allowed_mimes)) {
            $errors[] = 'Invalid file type detected';
        }
        
        // Additional security: Check if it's actually an image
        if (!getimagesize($file['tmp_name'])) {
            $errors[] = 'File is not a valid image';
        }
        
        return $errors;
    }
    
    /**
     * Validate multiple file uploads
     */
    public function validateMultipleFileUploads($files) {
        $errors = [];
        
        if (!is_array($files['name'])) {
            return $this->validateFileUpload($files);
        }
        
        $file_count = count($files['name']);
        
        if ($file_count > $this->settings['max_file_uploads']) {
            $errors[] = "Maximum {$this->settings['max_file_uploads']} files allowed";
            return $errors;
        }
        
        for ($i = 0; $i < $file_count; $i++) {
            if ($files['error'][$i] === UPLOAD_ERR_OK) {
                $file = [
                    'name' => $files['name'][$i],
                    'type' => $files['type'][$i],
                    'tmp_name' => $files['tmp_name'][$i],
                    'error' => $files['error'][$i],
                    'size' => $files['size'][$i]
                ];
                
                $file_errors = $this->validateFileUpload($file);
                if (!empty($file_errors)) {
                    $errors["{$files['name'][$i]}"] = $file_errors;
                }
            }
        }
        
        return $errors;
    }
    
    /**
     * Log security events
     */
    public function logSecurityEvent($event, $details = [], $level = 'INFO') {
        $log_entry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'level' => $level,
            'event' => $event,
            'user_id' => $_SESSION['user_id'] ?? 'anonymous',
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'details' => $details
        ];
        
        // Write to security log file
        $log_file = '../logs/security.log';
        if (!file_exists(dirname($log_file))) {
            mkdir(dirname($log_file), 0755, true);
        }
        
        file_put_contents($log_file, json_encode($log_entry) . "\n", FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Check if current request is from admin panel
     */
    public function isAdminRequest() {
        $script_name = $_SERVER['SCRIPT_NAME'] ?? '';
        return strpos($script_name, '/admin/') !== false;
    }
    
    /**
     * Validate admin access
     */
    public function validateAdminAccess() {
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
            $this->logSecurityEvent('UNAUTHORIZED_ADMIN_ACCESS', [
                'reason' => 'No valid session'
            ], 'WARNING');
            return false;
        }
        
        if (!in_array($_SESSION['user_role'], ['admin', 'agent'])) {
            $this->logSecurityEvent('UNAUTHORIZED_ADMIN_ACCESS', [
                'user_id' => $_SESSION['user_id'],
                'user_role' => $_SESSION['user_role'],
                'reason' => 'Insufficient privileges'
            ], 'WARNING');
            return false;
        }
        
        return true;
    }
    
    /**
     * Generate secure filename
     */
    public function generateSecureFilename($original_name, $prefix = '') {
        $extension = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
        $base_name = $prefix . bin2hex(random_bytes(16)) . '_' . time();
        return $base_name . '.' . $extension;
    }
    
    /**
     * Clean and validate directory path
     */
    public function validateDirectoryPath($path) {
        // Remove any directory traversal attempts
        $path = str_replace(['../', '.\\', '..\\'], '', $path);
        $path = ltrim($path, '/\\');
        
        // Only allow alphanumeric characters, hyphens, underscores, and forward slashes
        if (!preg_match('/^[a-zA-Z0-9\-_\/]+$/', $path)) {
            return false;
        }
        
        return $path;
    }
    
    /**
     * Validate URL for redirects
     */
    public function validateRedirectURL($url) {
        // Only allow relative URLs or URLs from the same domain
        if (filter_var($url, FILTER_VALIDATE_URL)) {
            $parsed_url = parse_url($url);
            $current_host = $_SERVER['HTTP_HOST'] ?? '';
            
            if (isset($parsed_url['host']) && $parsed_url['host'] !== $current_host) {
                return false;
            }
        }
        
        // Check for common XSS patterns
        if (preg_match('/[<>"\'\(\)&]/', $url)) {
            return false;
        }
        
        return true;
    }
}

// Helper functions for easy access
function security() {
    return SecurityValidator::getInstance();
}

function sanitize($input, $type = 'string') {
    return security()->sanitizeInput($input, $type);
}

function validate($input, $rules) {
    return security()->validateInput($input, $rules);
}

function csrf_token() {
    return security()->generateCSRFToken();
}

function verify_csrf($token) {
    return security()->validateCSRFToken($token);
}

function secure_filename($original_name, $prefix = '') {
    return security()->generateSecureFilename($original_name, $prefix);
}
?>