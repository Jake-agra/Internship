<?php
session_start();
include('./Database/connection.php');
include('./includes/route.php');
include('./includes/security.php');

header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Please login to bookmark properties']);
    exit();
}

$user_id = $_SESSION['user_id'];
$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token from header or body
    $csrf = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!$csrf || !verify_csrf($csrf)) {
        echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
        exit();
    }
    $property_id = isset($_POST['property_id']) ? (int)$_POST['property_id'] : 0;
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
    if ($property_id <= 0) {
        $response['message'] = 'Invalid property ID';
        echo json_encode($response);
        exit();
    }
    
    // Check if bookmarks table exists, if not create it
    $table_check = $conn->query("SHOW TABLES LIKE 'bookmarks'");
    if ($table_check->num_rows == 0) {
        $create_bookmarks = "CREATE TABLE bookmarks (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            property_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE,
            UNIQUE KEY unique_bookmark (user_id, property_id)
        )";
        if (!$conn->query($create_bookmarks)) {
            $response['message'] = 'Database error: Could not create bookmarks table';
            echo json_encode($response);
            exit();
        }
    }
    
    // Check if property exists
    $property_check = $conn->prepare("SELECT id FROM properties WHERE id = ? AND status = 'available'");
    $property_check->bind_param("i", $property_id);
    $property_check->execute();
    $property_result = $property_check->get_result();
    
    if ($property_result->num_rows === 0) {
        $response['message'] = 'Property not found or not available';
        echo json_encode($response);
        exit();
    }
    $property_check->close();
    
    if ($action === 'add') {
        // Add bookmark
        $insert_stmt = $conn->prepare("INSERT IGNORE INTO bookmarks (user_id, property_id) VALUES (?, ?)");
        $insert_stmt->bind_param("ii", $user_id, $property_id);
        
        if ($insert_stmt->execute()) {
            if ($insert_stmt->affected_rows > 0) {
                $response['success'] = true;
                $response['message'] = 'Property bookmarked successfully!';
                $response['action'] = 'bookmarked';
            } else {
                $response['message'] = 'Property is already bookmarked';
                $response['action'] = 'already_bookmarked';
            }
        } else {
            $response['message'] = 'Failed to bookmark property';
        }
        $insert_stmt->close();
        
    } elseif ($action === 'remove') {
        // Remove bookmark
        $remove_stmt = $conn->prepare("DELETE FROM bookmarks WHERE user_id = ? AND property_id = ?");
        $remove_stmt->bind_param("ii", $user_id, $property_id);
        
        if ($remove_stmt->execute()) {
            if ($remove_stmt->affected_rows > 0) {
                $response['success'] = true;
                $response['message'] = 'Bookmark removed successfully!';
                $response['action'] = 'removed';
            } else {
                $response['message'] = 'Bookmark not found';
            }
        } else {
            $response['message'] = 'Failed to remove bookmark';
        }
        $remove_stmt->close();
        
    } elseif ($action === 'check') {
        // Check if property is bookmarked
        $check_stmt = $conn->prepare("SELECT id FROM bookmarks WHERE user_id = ? AND property_id = ?");
        $check_stmt->bind_param("ii", $user_id, $property_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        $response['success'] = true;
        $response['bookmarked'] = $check_result->num_rows > 0;
        $response['message'] = 'Bookmark status checked';
        $check_stmt->close();
        
    } else {
        $response['message'] = 'Invalid action';
    }
} else {
    $response['message'] = 'Invalid request method';
}

echo json_encode($response);
?>