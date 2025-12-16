<?php
session_start();
include('./Database/connection.php');
include('./includes/route.php');
include('./includes/security.php');

// Get property ID from URL
$property_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($property_id <= 0) {
    header('Location: property.php');
    exit();
}

// Track property view
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    // Insert view record
    $view_stmt = $conn->prepare("INSERT INTO property_views (property_id, user_id, ip_address, user_agent) VALUES (?, ?, ?, ?)");
    $view_stmt->bind_param("iiss", $property_id, $user_id, $ip_address, $user_agent);
    $view_stmt->execute();
    $view_stmt->close();
}

$page_title = 'Property Details - Real Estate';
$page_description = 'View detailed information about this property including images, features, and contact details.';

// Fetch property details with all related information
$property_query = "SELECT 
    p.id, p.propertiesname, p.description, p.price_id, p.user_id, p.property_type_id, p.location_id, 
    p.status, p.bedrooms, p.bathrooms, p.area_sqft, p.year_built, p.parking_spaces, p.images, 
    p.features, p.address_details, p.is_featured, p.views_count, p.created_at, p.updated_at,
    p.video_url, p.virtual_tour_url,
    pr.amount as price, pr.currency, pr.price_type, pr.is_negotiable,
    pt.type_name as property_type,
    l.city, l.region, l.country, l.postal_code,
    u.first_name as agent_first_name, u.last_name as agent_last_name, u.email as agent_email, u.phone as agent_phone
FROM properties p
JOIN prices pr ON p.price_id = pr.id
JOIN property_types pt ON p.property_type_id = pt.id
JOIN locations l ON p.location_id = l.id
JOIN users u ON p.user_id = u.id
WHERE p.id = ? AND p.status = 'available'";

$stmt = $conn->prepare($property_query);
$stmt->bind_param("i", $property_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: property.php');
    exit();
}

$property = $result->fetch_assoc();
$stmt->close();

// Increment view count
$update_views = $conn->prepare("UPDATE properties SET views_count = views_count + 1 WHERE id = ?");
$update_views->bind_param("i", $property_id);
$update_views->execute();
$update_views->close();

// Handle inquiry form submission
$inquiry_message = '';
$inquiry_success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_inquiry'])) {
    $csrf = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!$csrf || !SecurityValidator::getInstance()->validateCSRFToken($csrf)) {
        $inquiry_message = 'Invalid request. Please refresh and try again.';
    } else {
    $client_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    $contact_name = trim($_POST['contact_name']);
    $contact_email = trim($_POST['contact_email']);
    $contact_phone = trim($_POST['contact_phone']);
    $message = trim($_POST['message']);
    
    if (empty($contact_name) || empty($contact_email) || empty($message)) {
        $inquiry_message = 'Please fill in all required fields.';
    } elseif (!filter_var($contact_email, FILTER_VALIDATE_EMAIL)) {
        $inquiry_message = 'Please enter a valid email address.';
    } else {
        // Insert inquiry (using correct column names: name and email, not contact_name and contact_email)
        $inquiry_stmt = $conn->prepare("INSERT INTO inquiries (property_id, client_id, name, email, phone, message, status) VALUES (?, ?, ?, ?, ?, ?, 'pending')");
        $inquiry_stmt->bind_param("iissss", $property_id, $client_id, $contact_name, $contact_email, $contact_phone, $message);
        
        if ($inquiry_stmt->execute()) {
            $inquiry_success = true;
            $inquiry_message = 'Your inquiry has been sent successfully! We will contact you soon.';
        } else {
            $inquiry_message = 'Failed to send inquiry. Please try again.';
        }
        $inquiry_stmt->close();
    }
}
}

// Parse images and features
$property_images = [];
if (!empty($property['images'])) {
    $property_images = json_decode($property['images'], true) ?: [];
}

$property_features = [];
if (!empty($property['features'])) {
    $property_features = json_decode($property['features'], true) ?: [];
}

// Default images if none provided
if (empty($property_images)) {
    $default_images = [
        'https://images.unsplash.com/photo-1564013799919-ab600027ffc6?w=800&h=600&fit=crop',
        'https://images.unsplash.com/photo-1570129477492-45c003edd2be?w=800&h=600&fit=crop',
        'https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?w=800&h=600&fit=crop',
        'https://images.unsplash.com/photo-1560448075-bb485b067938?w=800&h=600&fit=crop'
    ];
    $property_images = $default_images;
}

// Default features if none provided
if (empty($property_features)) {
    $property_features = [
        'Air Conditioning',
        'Heating',
        'Parking',
        'Garden',
        'Security System',
        'Modern Kitchen'
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($property['propertiesname']); ?> - Real Estate</title>
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
            background-color: #ffffff;
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

        /* Property Header */
        .property-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem 0;
            margin-top: 76px;
        }

        .property-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .property-location {
            font-size: 1.1rem;
            opacity: 0.9;
            margin-bottom: 1rem;
        }

        .property-price {
            font-size: 2rem;
            font-weight: 700;
            color: #fbbf24;
        }

        /* Image Gallery */
        .image-gallery {
            position: relative;
        }

        .main-image {
            height: 500px;
            background-size: cover;
            background-position: center;
            border-radius: 15px;
            position: relative;
            cursor: pointer;
            transition: transform 0.3s ease;
        }

        .main-image:hover {
            transform: scale(1.02);
        }

        .thumbnail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
            gap: 10px;
            margin-top: 15px;
        }

        .thumbnail {
            height: 80px;
            background-size: cover;
            background-position: center;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 3px solid transparent;
        }

        .thumbnail:hover,
        .thumbnail.active {
            border-color: var(--primary-color);
            transform: scale(1.05);
        }

        /* Property Details */
        .property-details {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }

        .detail-item {
            display: flex;
            align-items: center;
            padding: 1rem 0;
            border-bottom: 1px solid #e5e7eb;
        }

        .detail-item:last-child {
            border-bottom: none;
        }

        .detail-icon {
            width: 50px;
            height: 50px;
            background: var(--light-color);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            color: var(--primary-color);
        }

        .detail-content h6 {
            margin-bottom: 0.25rem;
            font-weight: 600;
        }

        .detail-content p {
            margin: 0;
            color: var(--text-muted);
        }

        /* Features */
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }

        .feature-item {
            display: flex;
            align-items: center;
            padding: 0.75rem;
            background: var(--light-color);
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .feature-item:hover {
            background: var(--primary-color);
            color: white;
        }

        .feature-item i {
            margin-right: 0.5rem;
            color: var(--primary-color);
        }

        .feature-item:hover i {
            color: white;
        }

        /* Contact Form */
        .contact-form {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 100px;
        }

        .form-control, .form-select {
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            padding: 12px 16px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        /* Agent Info */
        .agent-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .agent-avatar {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            color: white;
            font-size: 2rem;
        }

        /* Map */
        .map-container {
            height: 400px;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .property-title {
                font-size: 2rem;
            }
            
            .main-image {
                height: 300px;
            }
            
            .contact-form {
                position: static;
                margin-top: 2rem;
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
                <?php if (isset($_SESSION['user_email']) && !empty($_SESSION['user_email'])): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-user-circle me-2"></i>
                            <?= htmlspecialchars($_SESSION['user_email']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i> My Profile</a></li>
                            <li><a class="dropdown-item" href="property.php"><i class="fas fa-home me-2"></i> Properties</a></li>
                            <li><a class="dropdown-item" href="bookmarks.php"><i class="fas fa-bookmark me-2"></i> Bookmarks</a></li>
                            <?php if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin'): ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="admin/dashboard.php"><i class="fas fa-cog me-2"></i> Admin Panel</a></li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="login.php">Login</a>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-primary ms-2" href="register.php">Register</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<!-- Property Header -->
<section class="property-header">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <h1 class="property-title"><?= htmlspecialchars($property['propertiesname']); ?></h1>
                <p class="property-location">
                    <i class="fas fa-map-marker-alt me-2"></i>
                    <?= htmlspecialchars($property['city'] . ', ' . $property['region'] . ', ' . $property['country']); ?>
                </p>
                <div class="d-flex flex-wrap gap-4">
                    <div>
                        <i class="fas fa-bed me-2"></i>
                        <span><?= $property['bedrooms']; ?> Bedrooms</span>
                    </div>
                    <div>
                        <i class="fas fa-bath me-2"></i>
                        <span><?= $property['bathrooms']; ?> Bathrooms</span>
                    </div>
                    <div>
                        <i class="fas fa-ruler-combined me-2"></i>
                        <span><?= number_format($property['area_sqft']); ?> sqft</span>
                    </div>
                    <div>
                        <i class="fas fa-eye me-2"></i>
                        <span><?= $property['views_count']; ?> Views</span>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 text-lg-end">
                <div class="property-price">
                    <?= htmlspecialchars($property['currency']); ?> <?= number_format($property['price'], 2); ?>
                </div>
                <p class="mb-0"><?= htmlspecialchars($property['price_type']); ?></p>
                <?php if ($property['is_negotiable']): ?>
                    <small class="text-warning">Price is negotiable</small>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<!-- Main Content -->
<div class="container my-5">
    <div class="row">
        <!-- Left Column -->
        <div class="col-lg-8">
            <!-- Image Gallery -->
            <div class="image-gallery mb-4">
                <div class="main-image" id="mainImage" style="background-image: url('<?= htmlspecialchars($property_images[0]); ?>')"></div>
                <div class="thumbnail-grid">
                    <?php foreach ($property_images as $index => $image): ?>
                        <div class="thumbnail <?= $index === 0 ? 'active' : ''; ?>" 
                             style="background-image: url('<?= htmlspecialchars($image); ?>')"
                             onclick="changeMainImage('<?= htmlspecialchars($image); ?>', this)"></div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Video & Virtual Tour Section -->
            <?php if (!empty($property['video_url']) || !empty($property['virtual_tour_url'])): ?>
            <div class="mb-4">
                <ul class="nav nav-tabs mb-3" id="mediaTab">
                    <?php if (!empty($property['video_url'])): ?>
                    <li class="nav-item">
                        <button class="nav-link active" id="video-tab" data-bs-toggle="tab" data-bs-target="#videoContent" type="button">
                            <i class="fas fa-video me-2"></i>Video Tour
                        </button>
                    </li>
                    <?php endif; ?>
                    <?php if (!empty($property['virtual_tour_url'])): ?>
                    <li class="nav-item">
                        <button class="nav-link <?= empty($property['video_url']) ? 'active' : ''; ?>" id="tour-tab" data-bs-toggle="tab" data-bs-target="#tourContent" type="button">
                            <i class="fas fa-vr-cardboard me-2"></i>Virtual Tour
                        </button>
                    </li>
                    <?php endif; ?>
                </ul>

                <div class="tab-content" id="mediaTabContent">
                    <?php if (!empty($property['video_url'])): ?>
                    <div class="tab-pane fade show active" id="videoContent">
                        <div class="ratio ratio-16x9 mb-3">
                            <?php 
                            $video_url = $property['video_url'];
                            // Check if it's a YouTube URL
                            if (preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\s]{11})/i', $video_url, $matches)) {
                                $youtube_id = $matches[1];
                            ?>
                            <iframe src="https://www.youtube.com/embed/<?= htmlspecialchars($youtube_id); ?>" allowfullscreen="" loading="lazy"></iframe>
                            <?php } else { ?>
                            <video controls>
                                <source src="<?= htmlspecialchars($video_url); ?>" type="video/mp4">
                                Your browser does not support the video tag.
                            </video>
                            <?php } ?>
                        </div>
                        <p class="text-muted small">Professional property video tour</p>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($property['virtual_tour_url'])): ?>
                    <div class="tab-pane fade <?= empty($property['video_url']) ? 'show active' : ''; ?>" id="tourContent">
                        <div class="ratio ratio-16x9 mb-3">
                            <iframe src="<?= htmlspecialchars($property['virtual_tour_url']); ?>" allowfullscreen="" loading="lazy"></iframe>
                        </div>
                        <p class="text-muted small">Interactive 360° virtual tour</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Property Description -->
            <div class="property-details">
                <h3 class="mb-4">Property Description</h3>
                <p class="lead"><?= htmlspecialchars($property['description']); ?></p>
                
                <?php if (!empty($property['address_details'])): ?>
                    <h5 class="mt-4 mb-3">Address Details</h5>
                    <p><?= htmlspecialchars($property['address_details']); ?></p>
                <?php endif; ?>
            </div>

            <!-- Property Details -->
            <div class="property-details">
                <h3 class="mb-4">Property Details</h3>
                <div class="row">
                    <div class="col-md-6">
                        <div class="detail-item">
                            <div class="detail-icon">
                                <i class="fas fa-home"></i>
                            </div>
                            <div class="detail-content">
                                <h6>Property Type</h6>
                                <p><?= htmlspecialchars($property['property_type']); ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="detail-item">
                            <div class="detail-icon">
                                <i class="fas fa-bed"></i>
                            </div>
                            <div class="detail-content">
                                <h6>Bedrooms</h6>
                                <p><?= $property['bedrooms']; ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="detail-item">
                            <div class="detail-icon">
                                <i class="fas fa-bath"></i>
                            </div>
                            <div class="detail-content">
                                <h6>Bathrooms</h6>
                                <p><?= $property['bathrooms']; ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="detail-item">
                            <div class="detail-icon">
                                <i class="fas fa-ruler-combined"></i>
                            </div>
                            <div class="detail-content">
                                <h6>Area</h6>
                                <p><?= number_format($property['area_sqft']); ?> sqft</p>
                            </div>
                        </div>
                    </div>
                    <?php if ($property['year_built']): ?>
                    <div class="col-md-6">
                        <div class="detail-item">
                            <div class="detail-icon">
                                <i class="fas fa-calendar"></i>
                            </div>
                            <div class="detail-content">
                                <h6>Year Built</h6>
                                <p><?= $property['year_built']; ?></p>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    <div class="col-md-6">
                        <div class="detail-item">
                            <div class="detail-icon">
                                <i class="fas fa-car"></i>
                            </div>
                            <div class="detail-content">
                                <h6>Parking Spaces</h6>
                                <p><?= $property['parking_spaces']; ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Features -->
            <div class="property-details">
                <h3 class="mb-4">Features & Amenities</h3>
                <div class="features-grid">
                    <?php foreach ($property_features as $feature): ?>
                        <div class="feature-item">
                            <i class="fas fa-check"></i>
                            <span><?= htmlspecialchars($feature); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Location Map -->
            <div class="property-details">
                <h3 class="mb-4">Location</h3>
                <div class="map-container">
                    <iframe 
                        src="https://www.google.com/maps/embed/v1/place?key=YOUR_API_KEY&q=<?= urlencode($property['city'] . ', ' . $property['region'] . ', ' . $property['country']); ?>"
                        width="100%" 
                        height="100%" 
                        style="border:0;" 
                        allowfullscreen="" 
                        loading="lazy">
                    </iframe>
                </div>
            </div>
        </div>

        <!-- Right Column -->
        <div class="col-lg-4">
            <!-- Agent Information -->
            <div class="agent-card mt-4">
                <h5 class="mb-3">Listed By</h5>
                <div class="agent-avatar">
                    <i class="fas fa-user"></i>
                </div>
                <h6><?= htmlspecialchars($property['agent_first_name'] . ' ' . $property['agent_last_name']); ?></h6>
                <p class="text-muted mb-3">Real Estate Agent</p>
                <div class="d-grid gap-2">
                    <a href="tel:<?= htmlspecialchars($property['agent_phone']); ?>" class="btn btn-outline-primary">
                        <i class="fas fa-phone me-2"></i>Call Agent
                    </a>
                    <a href="mailto:<?= htmlspecialchars($property['agent_email']); ?>" class="btn btn-outline-primary">
                        <i class="fas fa-envelope me-2"></i>Email Agent
                    </a>
                </div>
            </div>
         

            <!-- Share Property -->
            <div class="property-details mt-4">
                <h5 class="mb-3">Share This Property</h5>
                <div class="d-flex gap-2 mb-3">
                    <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>" 
                       target="_blank" class="btn btn-outline-primary btn-sm">
                        <i class="fab fa-facebook-f"></i>
                    </a>
                    <a href="https://twitter.com/intent/tweet?url=<?= urlencode('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>" 
                       target="_blank" class="btn btn-outline-primary btn-sm">
                        <i class="fab fa-twitter"></i>
                    </a>
                    <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?= urlencode('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>" 
                       target="_blank" class="btn btn-outline-primary btn-sm">
                        <i class="fab fa-linkedin-in"></i>
                    </a>
                    <button onclick="copyToClipboard()" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-link"></i>
                    </button>
                </div>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <div class="d-grid">
                        <button class="btn btn-outline-danger bookmark-btn" 
                                data-property-id="<?= $property['id']; ?>"
                                title="Bookmark this property">
                            <i class="fas fa-heart me-2"></i>Bookmark Property
                        </button>
                    </div>
                <?php endif; ?>
            </div>
           
            <!-- Contact Form -->
            <div class="contact-form">
                <h4 class="mb-4">Contact Agent</h4>
                
                <?php if ($inquiry_message): ?>
                    <div class="alert <?= $inquiry_success ? 'alert-success' : 'alert-danger'; ?>">
                        <?= htmlspecialchars($inquiry_message); ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= SecurityValidator::getInstance()->generateCSRFToken(); ?>">
                    <div class="mb-3">
                        <label for="contact_name" class="form-label">Full Name *</label>
                        <input type="text" class="form-control" id="contact_name" name="contact_name" 
                               value="<?= isset($_SESSION['user_email']) ? htmlspecialchars($_SESSION['user_email']) : ''; ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="contact_email" class="form-label">Email *</label>
                        <input type="email" class="form-control" id="contact_email" name="contact_email" 
                               value="<?= isset($_SESSION['user_email']) ? htmlspecialchars($_SESSION['user_email']) : ''; ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="contact_phone" class="form-label">Phone Number</label>
                        <input type="tel" class="form-control" id="contact_phone" name="contact_phone">
                    </div>
                    
                    <div class="mb-3">
                        <label for="message" class="form-label">Message *</label>
                        <textarea class="form-control" id="message" name="message" rows="4" 
                                  placeholder="I'm interested in this property..." required></textarea>
                    </div>
                    
                    <button type="submit" name="submit_inquiry" class="btn btn-primary w-100">
                        <i class="fas fa-paper-plane me-2"></i>Send Inquiry
                    </button>
                </form>
            </div>

        </div>
    </div>
</div>

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
    function changeMainImage(imageUrl, thumbnail) {
        document.getElementById('mainImage').style.backgroundImage = `url('${imageUrl}')`;
        
        // Remove active class from all thumbnails
        document.querySelectorAll('.thumbnail').forEach(t => t.classList.remove('active'));
        
        // Add active class to clicked thumbnail
        thumbnail.classList.add('active');
    }

    function copyToClipboard() {
        navigator.clipboard.writeText(window.location.href).then(function() {
            showToast('Property link copied to clipboard!', 'success');
        });
    }

    // Bookmark functionality
    document.addEventListener('DOMContentLoaded', function() {
        const bookmarkBtn = document.querySelector('.bookmark-btn');
        
        if (bookmarkBtn) {
            const propertyId = bookmarkBtn.dataset.propertyId;
            
            // Check initial bookmark status
            checkBookmarkStatus(propertyId, bookmarkBtn);
            
            // Add click event listener
            bookmarkBtn.addEventListener('click', function() {
                const isBookmarked = this.classList.contains('bookmarked');
                const action = isBookmarked ? 'remove' : 'add';
                
                // Disable button during request
                this.disabled = true;
                
            fetch('bookmark_handler.php', {
                    method: 'POST',
                    headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-CSRF-Token': document.querySelector('input[name="csrf_token"]')?.value || ''
                    },
                body: `property_id=${propertyId}&action=${action}&csrf_token=${encodeURIComponent(document.querySelector('input[name="csrf_token"]')?.value || '')}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        if (data.action === 'bookmarked') {
                            this.classList.add('bookmarked');
                            this.classList.remove('btn-outline-danger');
                            this.classList.add('btn-danger');
                            this.innerHTML = '<i class="fas fa-heart me-2"></i>Remove Bookmark';
                            this.title = 'Remove from bookmarks';
                        } else if (data.action === 'removed') {
                            this.classList.remove('bookmarked');
                            this.classList.remove('btn-danger');
                            this.classList.add('btn-outline-danger');
                            this.innerHTML = '<i class="fas fa-heart me-2"></i>Bookmark Property';
                            this.title = 'Bookmark this property';
                        }
                        
                        // Show success message
                        showToast(data.message, 'success');
                    } else {
                        showToast(data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('An error occurred. Please try again.', 'error');
                })
                .finally(() => {
                    this.disabled = false;
                });
            });
        }
    });

    function checkBookmarkStatus(propertyId, btn) {
        fetch('bookmark_handler.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-CSRF-Token': document.querySelector('input[name="csrf_token"]')?.value || ''
            },
            body: `property_id=${propertyId}&action=check&csrf_token=${encodeURIComponent(document.querySelector('input[name="csrf_token"]')?.value || '')}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.bookmarked) {
                btn.classList.add('bookmarked');
                btn.classList.remove('btn-outline-danger');
                btn.classList.add('btn-danger');
                btn.innerHTML = '<i class="fas fa-heart me-2"></i>Remove Bookmark';
                btn.title = 'Remove from bookmarks';
            }
        })
        .catch(error => {
            console.error('Bookmark check error:', error);
        });
    }

    function showToast(message, type) {
        // Create toast element
        const toastHtml = `
            <div class="toast align-items-center text-white bg-${type === 'success' ? 'success' : 'danger'} border-0" role="alert">
                <div class="d-flex">
                    <div class="toast-body">${message}</div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            </div>
        `;
        
        // Create toast container if it doesn't exist
        let toastContainer = document.querySelector('.toast-container');
        if (!toastContainer) {
            toastContainer = document.createElement('div');
            toastContainer.className = 'toast-container position-fixed top-0 end-0 p-3';
            document.body.appendChild(toastContainer);
        }
        
        // Add toast to container
        toastContainer.insertAdjacentHTML('beforeend', toastHtml);
        
        // Initialize and show toast
        const toastElement = toastContainer.lastElementChild;
        const toast = new bootstrap.Toast(toastElement);
        toast.show();
        
        // Remove toast element after it's hidden
        toastElement.addEventListener('hidden.bs.toast', () => {
            toastElement.remove();
        });
    }
</script>
</body>
</html>