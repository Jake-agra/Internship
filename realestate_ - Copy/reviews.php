<?php
session_start();
include('./Database/connection.php');
include('./includes/route.php');

$page_title = 'Property Reviews - Real Estate';
$page_description = 'Read property reviews and ratings from real customers. Share your experience and help others make informed decisions.';

$property_id = isset($_GET['property_id']) ? (int)$_GET['property_id'] : 0;
$message = '';
$success = false;

// Handle review submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
    
    $user_id = $_SESSION['user_id'];
    $rating = (int)$_POST['rating'];
    $review_text = trim($_POST['review_text']);
    
    if ($rating < 1 || $rating > 5) {
        $message = 'Please select a valid rating between 1 and 5 stars.';
    } elseif (empty($review_text)) {
        $message = 'Please write a review.';
    } else {
        // Check if property_reviews table exists, if not create it
        $table_check = $conn->query("SHOW TABLES LIKE 'property_reviews'");
        if ($table_check->num_rows == 0) {
            $create_reviews = "CREATE TABLE IF NOT EXISTS property_reviews (
                id INT AUTO_INCREMENT PRIMARY KEY,
                property_id INT NOT NULL,
                user_id INT NOT NULL,
                rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
                review_text TEXT,
                is_approved BOOLEAN DEFAULT FALSE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY unique_property_user_review (property_id, user_id),
                FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )";
            $conn->query($create_reviews);
        }
        
        // Check if user already reviewed this property
        $check_stmt = $conn->prepare("SELECT id FROM property_reviews WHERE property_id = ? AND user_id = ?");
        $check_stmt->bind_param("ii", $property_id, $user_id);
        $check_stmt->execute();
        $existing = $check_stmt->get_result();
        
        if ($existing->num_rows > 0) {
            // Update existing review
            $update_stmt = $conn->prepare("UPDATE property_reviews SET rating = ?, review_text = ?, is_approved = FALSE, updated_at = CURRENT_TIMESTAMP WHERE property_id = ? AND user_id = ?");
            $update_stmt->bind_param("isii", $rating, $review_text, $property_id, $user_id);
            
            if ($update_stmt->execute()) {
                $success = true;
                $message = 'Your review has been updated and is pending approval.';
            } else {
                $message = 'Failed to update review. Please try again.';
            }
            $update_stmt->close();
        } else {
            // Insert new review
            $insert_stmt = $conn->prepare("INSERT INTO property_reviews (property_id, user_id, rating, review_text, is_approved) VALUES (?, ?, ?, ?, FALSE)");
            $insert_stmt->bind_param("iiis", $property_id, $user_id, $rating, $review_text);
            
            if ($insert_stmt->execute()) {
                $success = true;
                $message = 'Thank you for your review! It will be published after approval.';
            } else {
                $message = 'Failed to submit review. Please try again.';
            }
            $insert_stmt->close();
        }
        $check_stmt->close();
    }
}

// Get property information if property_id is provided
$property = null;
if ($property_id > 0) {
    $property_stmt = $conn->prepare("SELECT p.propertiesname, p.description, pr.amount, pr.currency, pt.type_name, l.city, l.country FROM properties p JOIN prices pr ON p.price_id = pr.id JOIN property_types pt ON p.property_type_id = pt.id JOIN locations l ON p.location_id = l.id WHERE p.id = ?");
    $property_stmt->bind_param("i", $property_id);
    $property_stmt->execute();
    $property_result = $property_stmt->get_result();
    $property = $property_result->fetch_assoc();
    $property_stmt->close();
}

// Get approved reviews for this property
$reviews = [];
if ($property_id > 0) {
    $reviews_query = "SELECT pr.rating, pr.review_text, pr.created_at, u.first_name, u.last_name 
                     FROM property_reviews pr 
                     JOIN users u ON pr.user_id = u.id 
                     WHERE pr.property_id = ? AND pr.is_approved = TRUE 
                     ORDER BY pr.created_at DESC";
    
    $reviews_stmt = $conn->prepare($reviews_query);
    $reviews_stmt->bind_param("i", $property_id);
    $reviews_stmt->execute();
    $reviews_result = $reviews_stmt->get_result();
    $reviews = $reviews_result->fetch_all(MYSQLI_ASSOC);
    $reviews_stmt->close();
}

// Calculate average rating
$avg_rating = 0;
$total_reviews = count($reviews);
if ($total_reviews > 0) {
    $sum_ratings = array_sum(array_column($reviews, 'rating'));
    $avg_rating = round($sum_ratings / $total_reviews, 1);
}

include('./includes/header.php');
?>

<style>
.page-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 3rem 0;
    margin-top: 76px;
}

.review-card {
    background: white;
    border-radius: 15px;
    padding: 1.5rem;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    margin-bottom: 1.5rem;
}

.rating-stars {
    color: #ffc107;
    margin-bottom: 0.5rem;
}

.star-rating {
    font-size: 1.5rem;
    color: #ddd;
    cursor: pointer;
    transition: color 0.3s;
}

.star-rating:hover,
.star-rating.active {
    color: #ffc107;
}

.property-summary {
    background: white;
    border-radius: 15px;
    padding: 2rem;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    margin-bottom: 2rem;
}
</style>

<?php include('./includes/nav.php'); ?>

<!-- Page Header -->
<section class="page-header text-center">
    <div class="container">
        <h1 class="page-title">Property Reviews</h1>
        <p class="page-subtitle">Share your experience and read reviews from other customers</p>
    </div>
</section>

<div class="container my-5">
    <?php if ($property): ?>
        <!-- Property Summary -->
        <div class="property-summary">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h3><?= htmlspecialchars($property['propertiesname']); ?></h3>
                    <p class="text-muted mb-2">
                        <i class="fas fa-map-marker-alt me-2"></i>
                        <?= htmlspecialchars($property['city'] . ', ' . $property['country']); ?>
                    </p>
                    <p class="text-primary h5 mb-0">
                        <?= htmlspecialchars($property['currency']); ?> <?= number_format($property['amount']); ?>
                    </p>
                </div>
                <div class="col-md-4 text-md-end">
                    <div class="rating-summary">
                        <div class="rating-stars">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="fas fa-star<?= $i <= $avg_rating ? '' : '-o'; ?>"></i>
                            <?php endfor; ?>
                        </div>
                        <p class="mb-0"><?= $avg_rating; ?>/5 (<?= $total_reviews; ?> reviews)</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Alert Messages -->
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?= $success ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert">
                <i class="fas fa-<?= $success ? 'check-circle' : 'exclamation-triangle'; ?> me-2"></i>
                <?= htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Review Form -->
        <?php if (isLoggedIn()): ?>
            <div class="review-card">
                <h4>Write a Review</h4>
                <form method="POST" id="reviewForm">
                    <input type="hidden" name="property_id" value="<?= $property_id; ?>">
                    
                    <div class="mb-3">
                        <label class="form-label">Rating</label>
                        <div class="star-rating-input">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="fas fa-star star-rating" data-rating="<?= $i; ?>"></i>
                            <?php endfor; ?>
                            <input type="hidden" name="rating" id="ratingInput" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="review_text" class="form-label">Your Review</label>
                        <textarea class="form-control" id="review_text" name="review_text" rows="4" 
                                placeholder="Share your experience with this property..." required></textarea>
                    </div>

                    <button type="submit" name="submit_review" class="btn btn-primary">
                        <i class="fas fa-paper-plane me-2"></i>Submit Review
                    </button>
                </form>
            </div>
        <?php else: ?>
            <div class="review-card text-center">
                <h4>Want to write a review?</h4>
                <p class="text-muted">Please log in to share your experience with this property.</p>
                <a href="login.php" class="btn btn-primary">
                    <i class="fas fa-sign-in-alt me-2"></i>Login to Review
                </a>
            </div>
        <?php endif; ?>

        <!-- Reviews List -->
        <h3 class="mt-5 mb-4">Customer Reviews (<?= $total_reviews; ?>)</h3>
        
        <?php if (empty($reviews)): ?>
            <div class="review-card text-center">
                <i class="fas fa-comments fa-3x text-muted mb-3"></i>
                <h4>No Reviews Yet</h4>
                <p class="text-muted">Be the first to review this property!</p>
            </div>
        <?php else: ?>
            <?php foreach ($reviews as $review): ?>
                <div class="review-card">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <h5 class="mb-1"><?= htmlspecialchars($review['first_name'] . ' ' . $review['last_name']); ?></h5>
                            <div class="rating-stars">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="fas fa-star<?= $i <= $review['rating'] ? '' : '-o'; ?>"></i>
                                <?php endfor; ?>
                            </div>
                        </div>
                        <small class="text-muted"><?= date('M j, Y', strtotime($review['created_at'])); ?></small>
                    </div>
                    <p class="mb-0"><?= htmlspecialchars($review['review_text']); ?></p>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <div class="text-center mt-4">
            <a href="property_details.php?id=<?= $property_id; ?>" class="btn btn-outline-primary">
                <i class="fas fa-arrow-left me-2"></i>Back to Property
            </a>
        </div>

    <?php else: ?>
        <div class="text-center py-5">
            <i class="fas fa-home fa-3x text-muted mb-3"></i>
            <h3>Property Not Found</h3>
            <p class="text-muted">The property you are looking for does not exist or has been removed.</p>
            <a href="property.php" class="btn btn-primary">
                <i class="fas fa-search me-2"></i>Browse Properties
            </a>
        </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Star rating functionality
    const stars = document.querySelectorAll('.star-rating');
    const ratingInput = document.getElementById('ratingInput');
    
    stars.forEach((star, index) => {
        star.addEventListener('click', function() {
            const rating = this.getAttribute('data-rating');
            ratingInput.value = rating;
            
            // Update visual state
            stars.forEach((s, i) => {
                if (i < rating) {
                    s.classList.add('active');
                } else {
                    s.classList.remove('active');
                }
            });
        });
        
        star.addEventListener('mouseover', function() {
            const rating = this.getAttribute('data-rating');
            stars.forEach((s, i) => {
                if (i < rating) {
                    s.style.color = '#ffc107';
                } else {
                    s.style.color = '#ddd';
                }
            });
        });
    });
    
    // Reset on mouse leave
    document.querySelector('.star-rating-input').addEventListener('mouseleave', function() {
        const currentRating = ratingInput.value;
        stars.forEach((s, i) => {
            if (i < currentRating) {
                s.style.color = '#ffc107';
            } else {
                s.style.color = '#ddd';
            }
        });
    });
    
    // Form validation
    document.getElementById('reviewForm').addEventListener('submit', function(e) {
        if (!ratingInput.value) {
            e.preventDefault();
            alert('Please select a rating before submitting your review.');
        }
    });
});
</script>

<?php include('./includes/footer.php'); ?>