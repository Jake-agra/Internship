route
<?php
// Simple routing system for the real estate application

class Router {
    private $routes = [];
    
    public function addRoute($path, $callback) {
        $this->routes[$path] = $callback;
    }
    
    public function route($requestUri) {
        // Remove query string
        $path = parse_url($requestUri, PHP_URL_PATH);
        
        // Remove leading slash if present
        $path = ltrim($path, '/');
        
        // If empty, set to home
        if (empty($path)) {
            $path = 'home';
        }
        
        if (array_key_exists($path, $this->routes)) {
            return $this->routes[$path]();
        } else {
            // 404 - Page not found
            http_response_code(404);
            return "Page not found";
        }
    }
}

// Authentication helper functions
function isLoggedIn() {
    return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

function getUserType() {
    return isset($_SESSION['user_type']) ? $_SESSION['user_type'] : null;
}

function isAdmin() {
    return getUserType() === 'admin';
}

function isAgent() {
    return getUserType() === 'agent';
}

function redirectTo($path) {
    header("Location: $path");
    exit();
}

// CSRF Protection
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
?>
