<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
include('./Database/connection.php');

// Get property ID from URL
$property_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$property_id) {
    header('Location: property.php');
    exit;
}

// Fetch property details with all related information
$sql = "SELECT p.*, pr.currency, pr.price_type, pr.amount AS price, 
        pt.type_name AS property_type, l.city, l.country, l.region, l.street_address,
        u.first_name, u.last_name, u.email, u.phone
        FROM properties p
        JOIN prices pr ON p.price_id = pr.id
        JOIN locations l ON p.location_id = l.id
        JOIN property_types pt ON p.property_type_id = pt.id
        JOIN users u ON p.user_id = u.id
        WHERE p.id = ? AND p.status = 'available'";

$property = null;
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param('i', $property_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows > 0) {
        $property = $result->fetch_assoc();
    }
    $stmt->close();
}

if (!$property) {
    header('Location: property.php');
    exit;
}

// Parse images (assuming they're stored as comma-separated URLs)
$images = [];
if (!empty($property['images'])) {
    $images = array_filter(array_map('trim', explode(',', $property['images'])));
}
if (empty($images)) {
    // Default placeholder images for demonstration
    $images = [
        'https://images.unsplash.com/photo-1564013799919-ab600027ffc6?w=800&h=600&fit=crop',
        'https://images.unsplash.com/photo-1570129477492-45c003edd2be?w=800&h=600&fit=crop',
        'https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?w=800&h=600&fit=crop',
        'https://images.unsplash.com/photo-1560448075-bb485b067938?w=800&h=600&fit=crop',
        'https://images.unsplash.com/photo-1560448204-603b3fc33ddc?w=800&h=600&fit=crop'
    ];
}

// Fetch similar properties
$similar_sql = "SELECT p.id, p.propertiesname, p.bedrooms, p.bathrooms, p.area_sqft, p.images,
                pr.amount AS price, pr.currency, l.city, l.country
                FROM properties p
                JOIN prices pr ON p.price_id = pr.id
                JOIN locations l ON p.location_id = l.id
                WHERE p.status = 'available' AND p.id != ? AND p.property_type_id = ?
                ORDER BY RAND() LIMIT 3";

$similar_properties = [];
if ($stmt = $conn->prepare($similar_sql)) {
    $stmt->bind_param('ii', $property_id, $property['property_type_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $similar_properties[] = $row;
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($property['propertiesname']); ?> - Real Estate</title>
    
    <!-- Bootstrap & FontAwesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    
    <!-- Lightbox for image gallery -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.4/css/lightbox.min.css" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #2c3e50;
            --accent-color: #3498db;
            --success-color: #27ae60;
            --warning-color: #f39c12;
            --danger-color: #e74c3c;
            --light-gray: #ecf0f1;
            --dark-gray: #7f8c8d;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
            color: var(--primary-color);
            background-color: #f8f9fa;
        }
        
        .hero-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 60px 0;
            margin-bottom: 40px;
        }
        
        .property-header {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .price-tag {
            background: linear-gradient(135deg, var(--success-color), #2ecc71);
            color: white;
            padding: 15px 25px;
            border-radius: 25px;
            font-size: 1.5rem;
            font-weight: bold;
            display: inline-block;
            margin-bottom: 20px;
        }
        
        .image-gallery {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .main-image {
            border-radius: 15px;
            overflow: hidden;
            margin-bottom: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        }
        
        .main-image img {
            width: 100%;
            height: 400px;
            object-fit: cover;
        }
        
        .thumbnail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
        }
        
        .thumbnail {
            border-radius: 10px;
            overflow: hidden;
            cursor: pointer;
            transition: transform 0.3s ease;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .thumbnail:hover {
            transform: scale(1.05);
        }
        
        .thumbnail img {
            width: 100%;
            height: 100px;
            object-fit: cover;
        }
        
        .property-details {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .detail-item {
            display: flex;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid var(--light-gray);
        }
        
        .detail-item:last-child {
            border-bottom: none;
        }
        
        .detail-icon {
            width: 40px;
            height: 40px;
            background: var(--accent-color);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            flex-shrink: 0;
        }
        
        .contact-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            position: sticky;
            top: 20px;
        }
        
        .agent-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--accent-color), #2980b9);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2rem;
            font-weight: bold;
            margin: 0 auto 20px;
        }
        
        .btn-contact {
            background: linear-gradient(135deg, var(--accent-color), #2980b9);
            border: none;
            color: white;
            padding: 12px 30px;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s ease;
            width: 100%;
            margin-bottom: 10px;
        }
        
        .btn-contact:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(52, 152, 219, 0.3);
            color: white;
        }
        
        .similar-properties {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .property-card {
            border: none;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        
        .property-card:hover {
            transform: translateY(-5px);
        }
        
        .property-card img {
            height: 200px;
            object-fit: cover;
        }
        
        .amenities-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .amenity-item {
            display: flex;
            align-items: center;
            padding: 15px;
            background: var(--light-gray);
            border-radius: 10px;
        }
        
        .amenity-icon {
            width: 30px;
            height: 30px;
            background: var(--accent-color);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            flex-shrink: 0;
        }
        
        .breadcrumb {
            background: transparent;
            padding: 0;
            margin-bottom: 20px;
        }
        
        .breadcrumb-item a {
            color: var(--accent-color);
            text-decoration: none;
        }
        
        .breadcrumb-item.active {
            color: var(--dark-gray);
        }
        
        @media (max-width: 768px) {
            .hero-section {
                padding: 40px 0;
            }
            
            .property-header,
            .image-gallery,
            .property-details,
            .contact-card {
                padding: 20px;
            }
            
            .main-image img {
                height: 250px;
            }
            
            .thumbnail-grid {
                grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            }
        }
    </style>
</head>
<body>

<?php include('./includes/nav.php'); ?>

<!-- Hero Section -->
<section class="hero-section">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="property.php">Properties</a></li>
                <li class="breadcrumb-item active"><?= htmlspecialchars($property['propertiesname']); ?></li>
            </ol>
        </nav>
        <h1 class="display-4 fw-bold"><?= htmlspecialchars($property['propertiesname']); ?></h1>
        <p class="lead mb-0">
            <i class="fas fa-map-marker-alt me-2"></i>
            <?= htmlspecialchars($property['city'] . ', ' . $property['country']); ?>
        </p>
    </div>
</section>

<div class="container">
    <!-- Property Header -->
    <div class="property-header">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <h2 class="mb-3"><?= htmlspecialchars($property['propertiesname']); ?></h2>
                <p class="text-muted mb-3">
                    <i class="fas fa-map-marker-alt me-2"></i>
                    <?= htmlspecialchars($property['street_address'] . ', ' . $property['city'] . ', ' . $property['country']); ?>
                </p>
                <div class="d-flex flex-wrap gap-3">
                    <span class="badge bg-primary fs-6 px-3 py-2">
                        <i class="fas fa-bed me-1"></i><?= $property['bedrooms']; ?> Bedrooms
                    </span>
                    <span class="badge bg-info fs-6 px-3 py-2">
                        <i class="fas fa-bath me-1"></i><?= $property['bathrooms']; ?> Bathrooms
                    </span>
                    <span class="badge bg-success fs-6 px-3 py-2">
                        <i class="fas fa-ruler-combined me-1"></i><?= number_format($property['area_sqft']); ?> sq ft
                    </span>
                    <span class="badge bg-warning fs-6 px-3 py-2">
                        <i class="fas fa-calendar me-1"></i>Built <?= $property['year_built']; ?>
                    </span>
                </div>
            </div>
            <div class="col-lg-4 text-lg-end">
                <div class="price-tag">
                    <?= htmlspecialchars($property['currency']); ?> <?= number_format($property['price'], 2); ?>
                </div>
                <p class="text-muted mb-0"><?= htmlspecialchars($property['price_type']); ?></p>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <!-- Image Gallery -->
            <div class="image-gallery">
                <h3 class="mb-4">Property Gallery</h3>
                <div class="main-image">
                    <a href="<?= htmlspecialchars($images[0]); ?>" data-lightbox="property-gallery">
                        <img src="<?= htmlspecialchars($images[0]); ?>" alt="Main property image" id="main-image">
                    </a>
                </div>
                <div class="thumbnail-grid">
                    <?php foreach ($images as $index => $image): ?>
                        <div class="thumbnail" onclick="changeMainImage('<?= htmlspecialchars($image); ?>')">
                            <a href="<?= htmlspecialchars($image); ?>" data-lightbox="property-gallery">
                                <img src="<?= htmlspecialchars($image); ?>" alt="Property image <?= $index + 1; ?>">
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Property Details -->
            <div class="property-details">
                <h3 class="mb-4">Property Details</h3>
                <p class="lead mb-4"><?= htmlspecialchars($property['description']); ?></p>
                
                <div class="detail-item">
                    <div class="detail-icon">
                        <i class="fas fa-home"></i>
                    </div>
                    <div>
                        <strong>Property Type:</strong> <?= htmlspecialchars($property['property_type']); ?>
                    </div>
                </div>
                
                <div class="detail-item">
                    <div class="detail-icon">
                        <i class="fas fa-bed"></i>
                    </div>
                    <div>
                        <strong>Bedrooms:</strong> <?= $property['bedrooms']; ?>
                    </div>
                </div>
                
                <div class="detail-item">
                    <div class="detail-icon">
                        <i class="fas fa-bath"></i>
                    </div>
                    <div>
                        <strong>Bathrooms:</strong> <?= $property['bathrooms']; ?>
                    </div>
                </div>
                
                <div class="detail-item">
                    <div class="detail-icon">
                        <i class="fas fa-ruler-combined"></i>
                    </div>
                    <div>
                        <strong>Area:</strong> <?= number_format($property['area_sqft']); ?> square feet
                    </div>
                </div>
                
                <div class="detail-item">
                    <div class="detail-icon">
                        <i class="fas fa-calendar"></i>
                    </div>
                    <div>
                        <strong>Year Built:</strong> <?= $property['year_built']; ?>
                    </div>
                </div>
                
                <div class="detail-item">
                    <div class="detail-icon">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <div>
                        <strong>Location:</strong> <?= htmlspecialchars($property['street_address'] . ', ' . $property['city'] . ', ' . $property['region'] . ', ' . $property['country']); ?>
                    </div>
                </div>
            </div>

            <!-- Amenities -->
            <div class="property-details">
                <h3 class="mb-4">Amenities & Features</h3>
                <div class="amenities-grid">
                    <div class="amenity-item">
                        <div class="amenity-icon">
                            <i class="fas fa-wifi"></i>
                        </div>
                        <span>High-Speed Internet</span>
                    </div>
                    <div class="amenity-item">
                        <div class="amenity-icon">
                            <i class="fas fa-car"></i>
                        </div>
                        <span>Parking Space</span>
                    </div>
                    <div class="amenity-item">
                        <div class="amenity-icon">
                            <i class="fas fa-snowflake"></i>
                        </div>
                        <span>Air Conditioning</span>
                    </div>
                    <div class="amenity-item">
                        <div class="amenity-icon">
                            <i class="fas fa-fire"></i>
                        </div>
                        <span>Heating System</span>
                    </div>
                    <div class="amenity-item">
                        <div class="amenity-icon">
                            <i class="fas fa-utensils"></i>
                        </div>
                        <span>Kitchen Appliances</span>
                    </div>
                    <div class="amenity-item">
                        <div class="amenity-icon">
                            <i class="fas fa-tshirt"></i>
                        </div>
                        <span>Laundry Facilities</span>
                    </div>
                </div>
            </div>

            <!-- Similar Properties -->
            <?php if (!empty($similar_properties)): ?>
            <div class="similar-properties">
                <h3 class="mb-4">Similar Properties</h3>
                <div class="row">
                    <?php foreach ($similar_properties as $similar): ?>
                        <div class="col-md-4 mb-3">
                            <div class="card property-card">
                                <img src="<?= !empty($similar['images']) ? explode(',', $similar['images'])[0] : 'https://images.unsplash.com/photo-1564013799919-ab600027ffc6?w=400&h=300&fit=crop'; ?>" class="card-img-top" alt="Property">
                                <div class="card-body">
                                    <h6 class="card-title"><?= htmlspecialchars($similar['propertiesname']); ?></h6>
                                    <p class="text-muted small">
                                        <i class="fas fa-map-marker-alt me-1"></i>
                                        <?= htmlspecialchars($similar['city'] . ', ' . $similar['country']); ?>
                                    </p>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="fw-bold text-primary">
                                            <?= htmlspecialchars($similar['currency']); ?> <?= number_format($similar['price'], 2); ?>
                                        </span>
                                        <a href="property_details.php?id=<?= $similar['id']; ?>" class="btn btn-sm btn-outline-primary">View</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Contact Sidebar -->
        <div class="col-lg-4">
            <div class="contact-card">
                <h4 class="mb-4">Contact Agent</h4>
                <div class="agent-avatar">
                    <?= strtoupper(substr($property['first_name'], 0, 1) . substr($property['last_name'], 0, 1)); ?>
                </div>
                <h5 class="text-center mb-2"><?= htmlspecialchars($property['first_name'] . ' ' . $property['last_name']); ?></h5>
                <p class="text-muted text-center mb-4">Real Estate Agent</p>
                
                <div class="mb-3">
                    <a href="mailto:<?= htmlspecialchars($property['email']); ?>" class="btn btn-contact">
                        <i class="fas fa-envelope me-2"></i>Send Email
                    </a>
                </div>
                
                <div class="mb-3">
                    <a href="tel:<?= htmlspecialchars($property['phone']); ?>" class="btn btn-contact">
                        <i class="fas fa-phone me-2"></i>Call Now
                    </a>
                </div>
                
                <div class="mb-3">
                    <button class="btn btn-contact" onclick="scheduleViewing()">
                        <i class="fas fa-calendar-alt me-2"></i>Schedule Viewing
                    </button>
                </div>
                
                <hr>
                
                <div class="text-center">
                    <h6>Quick Info</h6>
                    <p class="text-muted small mb-2">
                        <i class="fas fa-clock me-1"></i>Available for viewing
                    </p>
                    <p class="text-muted small mb-0">
                        <i class="fas fa-calendar me-1"></i>Listed recently
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Footer -->
<footer class="bg-dark text-white text-center py-4 mt-5">
    <div class="container">
        <p class="mb-0">&copy; <?= date('Y'); ?> Real Estate. All rights reserved.</p>
    </div>
</footer>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.4/js/lightbox.min.js"></script>

<script>
function changeMainImage(imageSrc) {
    document.getElementById('main-image').src = imageSrc;
}

function scheduleViewing() {
    alert('Thank you for your interest! Our agent will contact you soon to schedule a viewing.');
}

// Initialize lightbox
lightbox.option({
    'resizeDuration': 200,
    'wrapAround': true,
    'albumLabel': 'Image %1 of %2'
});
</script>

</body>
</html> 