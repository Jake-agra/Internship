<?php
session_start();
include('./Database/connection.php');
include('./includes/route.php');
include('./includes/security.php');

// Handle review submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    if (!isLoggedIn()) {
        $_SESSION['error_message'] = 'Please login to submit a review.';
    } else {
        $csrf = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
        if (!$csrf || !SecurityValidator::getInstance()->validateCSRFToken($csrf)) {
            $_SESSION['error_message'] = 'Invalid security token';
        } else {
            $property_id = (int)$_POST['property_id'];
            $rating = (int)$_POST['rating'];
            $review_text = trim($_POST['review_text'] ?? '');
            $user_id = $_SESSION['user_id'];
            
            // Validate inputs
            if ($rating < 1 || $rating > 5) {
                $_SESSION['error_message'] = 'Rating must be between 1 and 5 stars.';
            } elseif (empty($review_text)) {
                $_SESSION['error_message'] = 'Please provide a review.';
            } elseif (strlen($review_text) < 10) {
                $_SESSION['error_message'] = 'Review must be at least 10 characters long.';
            } elseif (strlen($review_text) > 5000) {
                $_SESSION['error_message'] = 'Review cannot exceed 5000 characters.';
            } else {
                // Check if user already reviewed this property
                $check_stmt = $conn->prepare("SELECT id FROM property_reviews WHERE property_id = ? AND user_id = ?");
                $check_stmt->bind_param("ii", $property_id, $user_id);
                $check_stmt->execute();
                $check_stmt->store_result();
                
                if ($check_stmt->num_rows > 0) {
                    $_SESSION['error_message'] = 'You have already reviewed this property. Each user can submit only one review per property.';
                } else {
                    // Insert review
                    $insert_stmt = $conn->prepare("INSERT INTO property_reviews (property_id, user_id, rating, review_text, is_approved) VALUES (?, ?, ?, ?, 1)");
                    $insert_stmt->bind_param("iiis", $property_id, $user_id, $rating, $review_text);
                    
                    if ($insert_stmt->execute()) {
                        $_SESSION['success_message'] = 'Thank you! Your review has been submitted successfully.';
                        
                        // Send notification email to property owner
                        try {
                            // Get property owner
                            $owner_stmt = $conn->prepare("SELECT u.first_name, u.email FROM users u JOIN properties p ON u.id = p.user_id WHERE p.id = ?");
                            $owner_stmt->bind_param("i", $property_id);
                            $owner_stmt->execute();
                            $owner_result = $owner_stmt->get_result();
                            
                            if ($owner_result->num_rows > 0) {
                                $owner = $owner_result->fetch_assoc();
                                
                                // Get reviewer name
                                $reviewer_stmt = $conn->prepare("SELECT first_name, last_name FROM users WHERE id = ?");
                                $reviewer_stmt->bind_param("i", $user_id);
                                $reviewer_stmt->execute();
                                $reviewer_result = $reviewer_stmt->get_result();
                                $reviewer = $reviewer_result->fetch_assoc();
                                
                                // Get property name
                                $prop_stmt = $conn->prepare("SELECT propertiesname FROM properties WHERE id = ?");
                                $prop_stmt->bind_param("i", $property_id);
                                $prop_stmt->execute();
                                $prop_result = $prop_stmt->get_result();
                                $property = $prop_result->fetch_assoc();
                                
                                // Send email using EmailService
                                require_once 'includes/EmailService.php';
                                $emailService = new EmailService();
                                $emailService->sendReviewNotificationEmail(
                                    $owner['first_name'],
                                    $owner['email'],
                                    $property['propertiesname'],
                                    $reviewer['first_name'] . ' ' . $reviewer['last_name'],
                                    $rating,
                                    $review_text
                                );
                                
                                $reviewer_stmt->close();
                                $prop_stmt->close();
                            }
                            $owner_stmt->close();
                        } catch (Exception $e) {
                            error_log('Review notification email failed: ' . $e->getMessage());
                        }
                    } else {
                        $_SESSION['error_message'] = 'Failed to submit review. Please try again.';
                    }
                    $insert_stmt->close();
                }
                $check_stmt->close();
            }
        }
    }
    
    // Redirect back to property page
    $property_id = (int)$_POST['property_id'];
    header("Location: property_details.php?id=$property_id");
    exit();
}

// Get reviews for property
if (isset($_GET['property_id'])) {
    $property_id = (int)$_GET['property_id'];
    
    $reviews_stmt = $conn->prepare("
        SELECT 
            pr.id, 
            pr.rating, 
            pr.review_text, 
            pr.created_at,
            u.first_name, 
            u.last_name
        FROM property_reviews pr
        JOIN users u ON pr.user_id = u.id
        WHERE pr.property_id = ? AND pr.is_approved = 1
        ORDER BY pr.created_at DESC
    ");
    
    $reviews_stmt->bind_param("i", $property_id);
    $reviews_stmt->execute();
    $reviews_result = $reviews_stmt->get_result();
    $reviews = $reviews_result->fetch_all(MYSQLI_ASSOC);
    $reviews_stmt->close();
    
    // Calculate average rating
    $avg_stmt = $conn->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as total_reviews FROM property_reviews WHERE property_id = ? AND is_approved = 1");
    $avg_stmt->bind_param("i", $property_id);
    $avg_stmt->execute();
    $avg_result = $avg_stmt->get_result();
    $rating_stats = $avg_result->fetch_assoc();
    $avg_stmt->close();
    
    echo json_encode([
        'success' => true,
        'reviews' => $reviews,
        'average_rating' => round($rating_stats['avg_rating'], 1),
        'total_reviews' => $rating_stats['total_reviews']
    ]);
    exit();
}

?>
