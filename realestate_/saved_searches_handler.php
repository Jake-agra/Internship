<?php
session_start();
include('./Database/connection.php');
include('./includes/route.php');
include('./includes/security.php');

// Require login
if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$message = '';
$success = false;

// Handle save search request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_search'])) {
    $csrf = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!$csrf || !SecurityValidator::getInstance()->validateCSRFToken($csrf)) {
        $_SESSION['error_message'] = 'Invalid security token';
    } else {
        $search_name = trim($_POST['search_name']);
        $email_alerts = isset($_POST['email_alerts']) ? 1 : 0;
        $alert_frequency = $_POST['alert_frequency'] ?? 'weekly';
        
        // Build search criteria from GET parameters
        $search_criteria = [
            'search' => $_GET['search'] ?? '',
            'property_type' => $_GET['property_type'] ?? '',
            'location' => $_GET['location'] ?? '',
            'min_price' => $_GET['min_price'] ?? 0,
            'max_price' => $_GET['max_price'] ?? 0,
            'bedrooms' => $_GET['bedrooms'] ?? 0,
            'bathrooms' => $_GET['bathrooms'] ?? 0,
            'min_area' => $_GET['min_area'] ?? 0,
            'max_area' => $_GET['max_area'] ?? 0,
            'year_built' => $_GET['year_built'] ?? 0
        ];
        
        // Validate inputs
        if (empty($search_name)) {
            $_SESSION['error_message'] = 'Please provide a name for this search.';
        } elseif (strlen($search_name) < 3 || strlen($search_name) > 255) {
            $_SESSION['error_message'] = 'Search name must be between 3 and 255 characters.';
        } elseif (!in_array($alert_frequency, ['daily', 'weekly', 'monthly'])) {
            $_SESSION['error_message'] = 'Invalid alert frequency.';
        } else {
            $criteria_json = json_encode($search_criteria);
            
            // Insert saved search
            $insert_stmt = $conn->prepare("INSERT INTO saved_searches (user_id, search_name, search_criteria, email_alerts, alert_frequency) VALUES (?, ?, ?, ?, ?)");
            $insert_stmt->bind_param("issii", $user_id, $search_name, $criteria_json, $email_alerts, $alert_frequency);
            
            if ($insert_stmt->execute()) {
                $_SESSION['success_message'] = 'Search saved successfully!';
            } else {
                $_SESSION['error_message'] = 'Failed to save search. Please try again.';
            }
            $insert_stmt->close();
        }
    }
    
    header('Location: client/saved_searches.php');
    exit();
}

// Handle delete saved search
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_search'])) {
    $csrf = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!$csrf || !SecurityValidator::getInstance()->validateCSRFToken($csrf)) {
        $_SESSION['error_message'] = 'Invalid security token';
    } else {
        $search_id = (int)$_POST['search_id'];
        
        // Verify ownership
        $check_stmt = $conn->prepare("SELECT user_id FROM saved_searches WHERE id = ?");
        $check_stmt->bind_param("i", $search_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $row = $check_result->fetch_assoc();
            if ($row['user_id'] === $user_id) {
                // Delete search
                $delete_stmt = $conn->prepare("DELETE FROM saved_searches WHERE id = ? AND user_id = ?");
                $delete_stmt->bind_param("ii", $search_id, $user_id);
                
                if ($delete_stmt->execute()) {
                    $_SESSION['success_message'] = 'Search deleted successfully!';
                } else {
                    $_SESSION['error_message'] = 'Failed to delete search.';
                }
                $delete_stmt->close();
            }
        }
        $check_stmt->close();
    }
    
    header('Location: client/saved_searches.php');
    exit();
}

// Handle toggle alerts
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_alerts'])) {
    $csrf = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!$csrf || !SecurityValidator::getInstance()->validateCSRFToken($csrf)) {
        $_SESSION['error_message'] = 'Invalid security token';
    } else {
        $search_id = (int)$_POST['search_id'];
        $email_alerts = isset($_POST['email_alerts']) ? 1 : 0;
        $alert_frequency = $_POST['alert_frequency'] ?? 'weekly';
        
        // Update alerts
        $update_stmt = $conn->prepare("UPDATE saved_searches SET email_alerts = ?, alert_frequency = ? WHERE id = ? AND user_id = ?");
        $update_stmt->bind_param("isii", $email_alerts, $alert_frequency, $search_id, $user_id);
        
        if ($update_stmt->execute()) {
            $_SESSION['success_message'] = 'Alert settings updated!';
        } else {
            $_SESSION['error_message'] = 'Failed to update settings.';
        }
        $update_stmt->close();
    }
    
    header('Location: client/saved_searches.php');
    exit();
}

?>
