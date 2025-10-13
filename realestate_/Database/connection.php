<?php
/**
 * Simple and Reliable Database Connection
 * Compatible with existing code structure
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database configuration - Simple and straightforward
$db_config = [
    'host' => 'localhost',
    'database' => 'realestate',
    'username' => 'root',
    'password' => '',
    'port' => 3306
];

// Create simple connection
$conn = null;
try {
    $conn = new mysqli($db_config['host'], $db_config['username'], $db_config['password'], $db_config['database'], $db_config['port']);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    $conn->set_charset("utf8mb4");
} catch (Exception $e) {
    die("<div style='padding: 20px; background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; margin: 20px; border-radius: 5px;'>
        <h3>Database Connection Error</h3>
        <p>" . $e->getMessage() . "</p>
        <p><strong>Please check:</strong></p>
        <ul>
            <li>XAMPP/WAMP is running</li>
            <li>MySQL service is started</li>
            <li>Database 'realestate' exists</li>
        </ul>
    </div>");
}

// For backward compatibility
class DatabaseConfig {
    public static function get($key) {
        global $db_config;
        return isset($db_config[$key]) ? $db_config[$key] : null;
    }
}

// Verify connection is working
if ($conn) {
    // Test query to ensure database is accessible
    $test_query = "SELECT 1";
    if (!$conn->query($test_query)) {
        die("<div style='padding: 20px; background: #f8d7da; color: #721c24;'>
            <h3>Database Test Failed</h3>
            <p>Connection established but cannot execute queries.</p>
        </div>");
    }
}

// Simple helper functions
function executeQuery($sql, $params = []) {
    global $conn;
    if (empty($params)) {
        return $conn->query($sql);
    }
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->execute($params);
        return $stmt;
    }
    return false;
}

function getLastInsertId() {
    global $conn;
    return $conn->insert_id;
}

?>