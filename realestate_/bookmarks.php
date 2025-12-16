<?php
session_start();
include('./Database/connection.php');
include('./includes/route.php');
include('./includes/toast.php');
include('./includes/security.php');

$page_title = 'My Bookmarks - Real Estate';
$page_description = 'View and manage your saved property bookmarks. Keep track of properties you are interested in.';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$message = '';
$success = false;

// Handle bookmark removal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_bookmark'])) {
    $csrf = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!$csrf || !SecurityValidator::getInstance()->validateCSRFToken($csrf)) {
        $success = false;
        $message = 'Invalid request. Please refresh and try again.';
    } else {
    $property_id = (int)$_POST['property_id'];
    
    $remove_stmt = $conn->prepare("DELETE FROM bookmarks WHERE user_id = ? AND property_id = ?");
    $remove_stmt->bind_param("ii", $user_id, $property_id);
    
    if ($remove_stmt->execute()) {
        $success = true;
        $message = 'Property removed from bookmarks successfully!';
    } else {
        $message = 'Failed to remove bookmark. Please try again.';
    }
    $remove_stmt->close();
    }
}

// Get user's bookmarks
$bookmarks = [];
$bookmark_query = "SELECT p.id, p.propertiesname, p.description, pr.amount, pr.currency, pr.price_type, 
                          pt.type_name, l.city, l.country, l.region, p.bedrooms, p.bathrooms, p.area_sqft, 
                          p.images, b.created_at as bookmarked_at
FROM bookmarks b
JOIN properties p ON b.property_id = p.id
JOIN prices pr ON p.price_id = pr.id
JOIN property_types pt ON p.property_type_id = pt.id
JOIN locations l ON p.location_id = l.id
WHERE b.user_id = ? AND p.status = 'available'
ORDER BY b.created_at DESC";

$bookmark_stmt = $conn->prepare($bookmark_query);
$bookmark_stmt->bind_param("i", $user_id);
$bookmark_stmt->execute();
$bookmark_result = $bookmark_stmt->get_result();
$bookmarks = $bookmark_result->fetch_all(MYSQLI_ASSOC);
$bookmark_stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bookmarks - Real Estate</title>
    <link rel="stylesheet" href="bootstrap-5.3.7-dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2563eb;
            --secondary-color: #1e40af;
            --accent-color: #3b82f6;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --dark-color: #1f2937;
            --light-color: #f8fafc;
            --text-muted: #6b7280;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            line-height: 1.6;
            color: var(--dark-color);
            background-color: var(--light-color);
        }

        /* Navigation */
        .navbar {
            background: rgba(255, 255, 255, 0.95) !important;
            backdrop-filter: blur(10px);
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
            color: var(--primary-color) !important;
        }

        .nav-link {
            font-weight: 500;
            color: var(--dark-color) !important;
            transition: color 0.3s ease;
        }

        .nav-link:hover {
            color: var(--primary-color) !important;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
            border-radius: 8px;
            padding: 10px 20px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(37, 99, 235, 0.3);
        }

        /* Page Header */
        .page-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 3rem 0;
            margin-top: 76px;
        }

        .page-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .page-subtitle {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        /* Property Cards */
        .property-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            height: 100%;
        }

        .property-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }

        .property-image {
            height: 250px;
            background-size: cover;
            background-position: center;
            position: relative;
        }

        .property-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            background: var(--primary-color);
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .bookmark-actions {
            position: absolute;
            top: 15px;
            left: 15px;
        }

        .property-content {
            padding: 1.5rem;
        }

        .property-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--dark-color);
        }

        .property-location {
            color: var(--text-muted);
            margin-bottom: 1rem;
            font-size: 0.9rem;
        }

        .property-price {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }

        .property-features {
            display: flex;
            gap: 1rem;
            margin-bottom: 1rem;
            font-size: 0.9rem;
            color: var(--text-muted);
        }

        .bookmarked-date {
            font-size: 0.8rem;
            color: var(--text-muted);
            margin-bottom: 1rem;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .empty-state i {
            font-size: 4rem;
            color: var(--text-muted);
            margin-bottom: 1.5rem;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .page-title {
                font-size: 2rem;
            }
            
            .property-features {
                flex-direction: column;
                gap: 0.5rem;
}
        }
    </style>
</head>
<body>

<!-- Navigation -->
<nav class="navbar navbar-expand-lg navbar-light fixed-top">
    <div class="container">
        <a href="index.php" class="navbar-brand">
            <i class="fas fa-home me-2"></i>Real Estate
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="index.php">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="property.php">Properties</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="index.php#about">About</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="contact.php">Contact</a>
                </li>
            </ul>

            <!-- User authentication section -->
            <ul class="navbar-nav align-items-center ms-auto">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-user-circle me-2"></i>
                        <?= htmlspecialchars($_SESSION['user_email']); ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                        <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i> My Profile</a></li>
                        <li><a class="dropdown-item" href="property.php"><i class="fas fa-home me-2"></i> Properties</a></li>
                        <li><a class="dropdown-item active" href="bookmarks.php"><i class="fas fa-bookmark me-2"></i> Bookmarks</a></li>
                        <?php if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin'): ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="admin/dashboard.php"><i class="fas fa-cog me-2"></i> Admin Panel</a></li>
                        <?php endif; ?>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- Page Header -->
<section class="page-header">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <h1 class="page-title">My Bookmarks</h1>
                <p class="page-subtitle">Your saved properties for easy access</p>
            </div>
            <div class="col-lg-4 text-lg-end">
                <div class="text-white">
                    <h4 class="mb-0"><?= count($bookmarks); ?></h4>
                    <small>Saved Properties</small>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Main Content -->
<section class="py-5">
    <div class="container">
        <?php if ($message): ?>
            <div class="alert <?= $success ? 'alert-success' : 'alert-danger'; ?> alert-dismissible fade show">
                <?= htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (empty($bookmarks)): ?>
            <div class="empty-state">
                <i class="fas fa-bookmark"></i>
                <h3>No Saved Properties</h3>
                <p class="text-muted mb-4">You haven't saved any properties yet. Start browsing and save your favorites!</p>
                <a href="property.php" class="btn btn-primary btn-lg">
                    <i class="fas fa-search me-2"></i>Browse Properties
                </a>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($bookmarks as $property): ?>
                    <?php
                    // Use professional real estate images if no custom images
                    $default_images = [
                        'https://images.unsplash.com/photo-1564013799919-ab600027ffc6?w=600&h=400&fit=crop',
                        'https://images.unsplash.com/photo-1570129477492-45c003edd2be?w=600&h=400&fit=crop',
                        'https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?w=600&h=400&fit=crop',
                        'https://images.unsplash.com/photo-1560448075-bb485b067938?w=600&h=400&fit=crop',
                        'https://images.unsplash.com/photo-1560448204-603b3fc33ddc?w=600&h=400&fit=crop',
                        'https://images.unsplash.com/photo-1512917774080-9991f1c4c750?w=600&h=400&fit=crop'
                    ];
                    
                    if (!empty($property['images'])) {
                        $images = json_decode($property['images'], true);
                        $img = is_array($images) && !empty($images) ? $images[0] : $default_images[$property['id'] % count($default_images)];
                    } else {
                        $img = $default_images[$property['id'] % count($default_images)];
                    }
                    ?>
                    <div class="col-lg-4 col-md-6">
                        <div class="property-card">
                            <div class="property-image" style="background-image: url('<?= htmlspecialchars($img); ?>')">
                                <span class="property-badge"><?= htmlspecialchars($property['type_name']); ?></span>
                                <div class="bookmark-actions">
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="csrf_token" value="<?= SecurityValidator::getInstance()->generateCSRFToken(); ?>">
                                        <input type="hidden" name="property_id" value="<?= $property['id']; ?>">
                                        <button type="submit" name="remove_bookmark" class="btn btn-sm btn-danger" 
                                                onclick="return confirm('Are you sure you want to remove this property from your bookmarks?')">
                                            <i class="fas fa-bookmark"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                            <div class="property-content">
                                <h5 class="property-title"><?= htmlspecialchars($property['propertiesname']); ?></h5>
                                <p class="property-location">
                                    <i class="fas fa-map-marker-alt me-1"></i>
                                    <?= htmlspecialchars($property['city'] . ', ' . $property['country']); ?>
                                </p>
                                <div class="property-price">
                                    <?= htmlspecialchars($property['currency']); ?> <?= number_format($property['amount']); ?>
                                    <small class="text-muted d-block"><?= htmlspecialchars($property['price_type']); ?></small>
                                </div>
                                <div class="property-features">
                                    <span><i class="fas fa-bed me-1"></i><?= $property['bedrooms']; ?> beds</span>
                                    <span><i class="fas fa-bath me-1"></i><?= $property['bathrooms']; ?> baths</span>
                                    <span><i class="fas fa-ruler-combined me-1"></i><?= number_format($property['area_sqft']); ?> sqft</span>
                                </div>
                                <div class="bookmarked-date">
                                    <i class="fas fa-calendar me-1"></i>
                                    Bookmarked on <?= date('M j, Y', strtotime($property['bookmarked_at'])); ?>
                                </div>
                                <div class="d-flex gap-2">
                                    <a href="property_details.php?id=<?= $property['id']; ?>" class="btn btn-primary flex-fill">
                                        <i class="fas fa-eye me-2"></i>View Details
                                    </a>
                                    <button class="btn btn-outline-primary" onclick="shareProperty(<?= $property['id']; ?>)">
                                        <i class="fas fa-share"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- Footer -->
<footer class="bg-dark text-white py-5 mt-5">
    <div class="container">
        <div class="row">
            <div class="col-lg-4 mb-4">
                <h5 class="mb-3">
                    <i class="fas fa-home me-2"></i>Real Estate
                </h5>
                <p class="text-muted">Your trusted partner in finding the perfect property.</p>
            </div>
            <div class="col-lg-8 text-lg-end">
                <p class="text-muted mb-0">© <?= date('Y'); ?> Real Estate. All rights reserved.</p>
            </div>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function shareProperty(propertyId) {
        const url = window.location.origin + '/property_details.php?id=' + propertyId;
        
        if (navigator.share) {
            navigator.share({
                title: 'Check out this property!',
                url: url
            });
        } else {
            navigator.clipboard.writeText(url).then(function() {
                alert('Property link copied to clipboard!');
            });
        }
    }
</script>
</body>
</html>
