<?php
// Database configuration
$dbhost = "localhost";
$dbname = "realestate_";
$dbpassword = "";
$dbuser = "root";
$dbport = 3306; // Default MySQL port

// Try different connection methods for XAMPP compatibility
try {
    // Method 1: Standard connection
    $conn = new mysqli($dbhost, $dbuser, $dbpassword, $dbname, $dbport);
    
    // Check connection
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    // Set charset to utf8
    $conn->set_charset("utf8");
    
} catch (Exception $e) {
    // Method 2: Try with socket (for XAMPP)
    try {
        $socket_path = "/opt/lampp/var/mysql/mysql.sock";
        $conn = new mysqli($dbhost, $dbuser, $dbpassword, $dbname, null, $socket_path);
        
        if ($conn->connect_error) {
            throw new Exception("Socket connection failed: " . $conn->connect_error);
        }
        
        $conn->set_charset("utf8");
        
    } catch (Exception $e2) {
        // Method 3: Try localhost with different port
        try {
            $conn = new mysqli("127.0.0.1", $dbuser, $dbpassword, $dbname, 3306);
            
            if ($conn->connect_error) {
                throw new Exception("127.0.0.1 connection failed: " . $conn->connect_error);
            }
            
            $conn->set_charset("utf8");
            
        } catch (Exception $e3) {
            // All methods failed
            die("<div style='background: #f8d7da; color: #721c24; padding: 20px; border: 1px solid #f5c6cb; border-radius: 5px; margin: 20px;'>
                <h3>Database Connection Error</h3>
                <p><strong>Could not connect to MySQL database.</strong></p>
                <p>Please ensure XAMPP is running and MySQL service is started.</p>
                <p>Error details:</p>
                <ul>
                    <li>Standard connection: " . $e->getMessage() . "</li>
                    <li>Socket connection: " . $e2->getMessage() . "</li>
                    <li>127.0.0.1 connection: " . $e3->getMessage() . "</li>
                </ul>
                <p><strong>To fix this:</strong></p>
                <ol>
                    <li>Start XAMPP Control Panel</li>
                    <li>Start Apache and MySQL services</li>
                    <li>Refresh this page</li>
                </ol>
            </div>");
        }
    }
}

// Enable error reporting for mysqli
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

?>