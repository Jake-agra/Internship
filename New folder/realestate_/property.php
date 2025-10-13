<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
include('./Database/connection.php');

// ----------------- Get filter values (from GET) -----------------
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$property_type = isset($_GET['property_type']) ? trim($_GET['property_type']) : '';
$location = isset($_GET['location']) ? trim($_GET['location']) : '';
$show_all = isset($_GET['show_all']) && $_GET['show_all'] === '1';

// ----------------- Build dynamic query with prepared statement -----------------
$sql = "SELECT p.id, p.propertiesname, p.description, p.price_id, p.user_id, p.property_type_id, p.location_id, p.status, p.bedrooms, p.bathrooms, p.area_sqft, p.year_built, p.images, pr.currency, pr.price_type, pr.amount AS price, pt.type_name AS property_type, l.city, l.country, l.region
FROM properties p
JOIN prices pr ON p.price_id = pr.id
JOIN locations l ON p.location_id = l.id
JOIN property_types pt ON p.property_type_id = pt.id
WHERE p.status = 'available'";

$types = '';
$values = [];

// search by name/description/location
if ($search !== '') {
    $sql .= " AND (p.propertiesname LIKE ? OR p.description LIKE ? OR l.city LIKE ? OR l.country LIKE ? OR l.region LIKE ?)";
    $like = "%{$search}%";
    for ($i = 0; $i < 5; $i++) $values[] = $like;
    $types .= str_repeat('s', 5);
}

// filter by property type id
if ($property_type !== '') {
    $sql .= " AND p.property_type_id = ?";
    $values[] = (int) $property_type;
    $types .= 'i';
}

// filter by location (city/country/region)
if ($location !== '') {
    $sql .= " AND (l.city LIKE ? OR l.country LIKE ? OR l.region LIKE ?)";
    $loclike = "%{$location}%";
    for ($i = 0; $i < 3; $i++) $values[] = $loclike;
    $types .= str_repeat('s', 3);
}

$sql .= " ORDER BY p.created_at DESC";
if (!$show_all) {
    $sql .= " LIMIT 6";
}

// prepare & execute
$featured_properties = [];
if ($stmt = $conn->prepare($sql)) {
    if (!empty($values)) {
        // bind params dynamically (mysqli requires references)
        array_unshift($values, $types); // first param is types string
        $refs = [];
        foreach ($values as $key => $val) $refs[$key] = &$values[$key];
        call_user_func_array([$stmt, 'bind_param'], $refs);
    }
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res) {
        $featured_properties = $res->fetch_all(MYSQLI_ASSOC);
        $res->free();
    }
    $stmt->close();
}

// ----------------- Fetch property types for the dropdown -----------------
$property_types = [];
if ($type_result = $conn->query("SELECT id, type_name FROM property_types ORDER BY type_name")) {
    while ($r = $type_result->fetch_assoc()) {
        $property_types[] = $r;
    }
    $type_result->free();
}

// ----------------- Stats -----------------
$stats = [
    'total_properties' => 0,
    'available_properties' => 0,
    'sold_properties' => 0,
    'avg_price' => 0
];

if ($count_query = $conn->query("SELECT  
    COUNT(*) as total_properties,
    SUM(CASE WHEN status='available' THEN 1 ELSE 0 END) as available_properties,
    SUM(CASE WHEN status='sold' THEN 1 ELSE 0 END) as sold_properties
    FROM properties")) {
    $stats = array_merge($stats, $count_query->fetch_assoc());
    $count_query->free();
}

if ($price_query = $conn->query("SELECT AVG(pr.amount) as avg_price FROM properties p JOIN prices pr ON p.price_id = pr.id")) {
    $p = $price_query->fetch_assoc();
    $stats['avg_price'] = $p && $p['avg_price'] !== null ? round($p['avg_price'], 2) : 0;
    $price_query->free();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Real Estate Listings</title>

    <!-- Bootstrap & FontAwesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

    <style>
        :root{
            --accent1: #6dd5ed;
            --accent2: #2193b0;
            --card-gradient: linear-gradient(135deg, #ffffff 0%, #f3f9ff 100%);
            --muted: #6c757d;
        }
        body{
            background: linear-gradient(180deg, #f7fbff 0%, #e9f7ff 100%);
            font-family: "Inter", system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial;
            padding-top: 80px; /* room for navbar */
        }
        .hero-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin-top: -80px;
            padding-top: 120px;
            position: relative;
            overflow: hidden;
        }
        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 100" fill="rgba(255,255,255,0.1)"><polygon points="0,100 1000,0 1000,100"/></svg>');
            background-size: cover;
        }
        .hero-section .container {
            position: relative;
            z-index: 2;
        }
        /* Search area */
        .search-filter-section{
            background: linear-gradient(90deg, rgba(109,213,237,0.15), rgba(33,147,176,0.08));
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 6px 18px rgba(33,147,176,0.08);
        }
        .search-filter-section .form-label { font-weight: 600; color: #0b4a59; }
        .btn-primary {
            background: linear-gradient(90deg, var(--accent1), var(--accent2));
            border: none;
            box-shadow: 0 6px 12px rgba(33,147,176,0.15);
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(33,147,176,0.25);
        }
        .btn-outline-secondary {
            border-radius: 8px;
        }
        /* Cards */
        .card {
            border: none;
            border-radius: 15px;
            background: var(--card-gradient);
            overflow: hidden;
            box-shadow: 0 10px 24px rgba(15, 40, 70, 0.06);
            transition: all 0.3s ease;
            position: relative;
        }
        .card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 50px rgba(15, 40, 70, 0.15);
        }
        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--accent1), var(--accent2));
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }
        .card:hover::before {
            transform: scaleX(1);
        }
        .card-img-top{
            height: 250px;
            object-fit: cover;
            width: 100%;
            transition: transform 0.3s ease;
        }
        .card:hover .card-img-top {
            transform: scale(1.05);
        }
        .property-badge{
            position: absolute;
            right: 12px;
            top: 12px;
            z-index: 3;
            background: rgba(255, 255, 255, 0.95) !important;
            color: var(--accent2) !important;
            font-weight: 600;
            padding: 8px 12px;
            border-radius: 20px;
            backdrop-filter: blur(10px);
        }
        .stats-card {
            border-radius: 12px;
            padding: 25px;
            color: #053642;
            background: linear-gradient(135deg, rgba(109,213,237,0.12), rgba(33,147,176,0.06));
            box-shadow: 0 8px 20px rgba(33,147,176,0.06);
        }
        .bg-gradient-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .rounded-4 {
            border-radius: 1rem !important;
        }
        footer{
            padding: 40px 0;
            color: var(--muted);
            text-align: center;
            background: #f8f9fa;
        }
    </style>
</head>
<body>

<?php include('./includes/nav.php'); ?>

<!-- Hero Section -->
<section class="hero-section py-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <h1 class="display-4 fw-bold text-white mb-3">Find Your Dream Home</h1>
                <p class="lead text-white-50 mb-4">Discover amazing properties in prime locations with our comprehensive real estate listings.</p>
                <div class="d-flex flex-wrap gap-3">
                    <div class="text-white">
                        <h4 class="mb-0"><?= (int)$stats['total_properties']; ?>+</h4>
                        <small>Properties Listed</small>
                    </div>
                    <div class="text-white">
                        <h4 class="mb-0"><?= (int)$stats['available_properties']; ?>+</h4>
                        <small>Available Now</small>
                    </div>
                    <div class="text-white">
                        <h4 class="mb-0">100%</h4>
                        <small>Verified Listings</small>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="search-filter-section">
                    <h4 class="text-center mb-4">Search Properties</h4>
                    <form method="get" class="row g-3 align-items-end">
                        <div class="col-md-6">
                            <label for="search" class="form-label">Search Property</label>
                            <input type="text" name="search" value="<?= htmlspecialchars($search); ?>" class="form-control" id="search" placeholder="Name, description, city or region">
                        </div>

                        <div class="col-md-6">
                            <label for="location" class="form-label">Location</label>
                            <input type="text" name="location" value="<?= htmlspecialchars($location); ?>" class="form-control" id="location" placeholder="City, Country, Region">
                        </div>

                        <div class="col-md-6">
                            <label for="property_type" class="form-label">Property Type</label>
                            <select name="property_type" id="property_type" class="form-select">
                                <option value="">All Types</option>
                                <?php foreach ($property_types as $type): ?>
                                    <option value="<?= $type['id']; ?>" <?= $property_type == $type['id'] ? 'selected' : ''; ?>><?= htmlspecialchars($type['type_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6 d-grid">
                            <button type="submit" class="btn btn-primary btn-lg"><i class="fa fa-search me-2"></i>Search</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Advanced Search & Filter -->
<section class="container mt-4">
    <div class="search-filter-section">
        <form method="get" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label for="search" class="form-label">Search Property</label>
                <input type="text" name="search" value="<?= htmlspecialchars($search); ?>" class="form-control" id="search" placeholder="Name, description, city or region">
            </div>

            <div class="col-md-3">
                <label for="property_type" class="form-label">Property Type</label>
                <select name="property_type" id="property_type" class="form-select">
                    <option value="">All Types</option>
                    <?php foreach ($property_types as $type): ?>
                        <option value="<?= $type['id']; ?>" <?= $property_type == $type['id'] ? 'selected' : ''; ?>><?= htmlspecialchars($type['type_name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-3">
                <label for="location" class="form-label">Location</label>
                <input type="text" name="location" value="<?= htmlspecialchars($location); ?>" class="form-control" id="location" placeholder="City, Country, Region">
            </div>

            <div class="col-md-2 d-grid">
                <button type="submit" class="btn btn-primary btn-lg"><i class="fa fa-search me-2"></i>Search</button>
            </div>

            <div class="col-12 d-flex align-items-center mt-2">
                <a href="<?= htmlspecialchars(basename($_SERVER['PHP_SELF'])); ?>" class="btn btn-outline-secondary me-3"><i class="fa fa-rotate-left me-2"></i>Reset</a>
                <div class="form-check form-switch ms-auto">
                    <input class="form-check-input" type="checkbox" id="show_all" name="show_all" value="1" <?= $show_all ? 'checked' : ''; ?> onchange="this.form.submit();">
                    <label class="form-check-label" for="show_all">Show All Results</label>
                </div>
            </div>
        </form>
    </div>
</section>

<!-- Property Listings -->
<section class="container mt-5">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h2 class="mb-1">Available Properties</h2>
            <p class="text-muted mb-0">Discover your perfect home from our curated collection</p>
        </div>
        <div class="text-end">
            <div class="text-muted small">Showing <?= count($featured_properties); ?> <?= (count($featured_properties) === 1) ? 'property' : 'properties'; ?></div>
            <div class="small text-primary"><?= (int)$stats['available_properties']; ?> total available</div>
        </div>
    </div>

    <?php if (!empty($featured_properties)): ?>
        <div class="row g-4">
            <?php foreach ($featured_properties as $property): ?>
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
                        $img = $property['images'];
                    } else {
                        // Use a different image for each property based on ID for variety
                        $img = $default_images[$property['id'] % count($default_images)];
                    }
                    $price = isset($property['price']) ? number_format($property['price'], 2) : 'N/A';
                ?>
                <div class="col-lg-4 col-md-6">
                    <div class="card h-100 position-relative">
                        <span class="badge bg-info text-dark property-badge"><?= htmlspecialchars($property['property_type']); ?></span>
                        <img src="<?= htmlspecialchars($img); ?>" class="card-img-top" alt="Property image">
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title mb-1"><?= htmlspecialchars($property['propertiesname']); ?></h5>
                            <p class="text-muted small mb-2"><i class="fa fa-map-marker-alt me-2"></i><?= htmlspecialchars($property['city'] . ', ' . $property['country']); ?></p>
                            <p class="card-text text-truncate mb-2"><?= htmlspecialchars($property['description']); ?></p>

                            <div class="mt-auto">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div>
                                        <div class="h5 mb-0 text-primary fw-bold"><?= htmlspecialchars($property['currency']); ?> <?= $price; ?></div>
                                        <small class="text-muted"><?= htmlspecialchars($property['price_type']); ?></small>
                                    </div>
                                    <div class="text-end">
                                        <span class="badge bg-success">Available</span>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="small text-muted">
                                        <i class="fas fa-bed me-1"></i><?= $property['bedrooms']; ?> beds
                                        <i class="fas fa-bath ms-2 me-1"></i><?= $property['bathrooms']; ?> baths
                                        <i class="fas fa-ruler-combined ms-2 me-1"></i><?= number_format($property['area_sqft']); ?> sqft
                                    </div>
                                    <a href="property_details.php?id=<?= $property['id']; ?>" class="btn btn-primary btn-sm">
                                        <i class="fas fa-eye me-1"></i>View Details
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <?php if (!$show_all && count($featured_properties) >= 6): ?>
        <div class="text-center mt-5">
            <a href="?show_all=1" class="btn btn-outline-primary btn-lg">
                <i class="fas fa-list me-2"></i>View All Properties
            </a>
        </div>
        <?php endif; ?>
    <?php else: ?>
        <div class="col-12">
            <div class="p-5 rounded bg-white text-center shadow-sm">
                <i class="fas fa-search fa-3x text-muted mb-3"></i>
                <h4 class="mb-2">No properties found</h4>
                <p class="text-muted mb-3">Try adjusting your search criteria or browse all available properties.</p>
                <a href="property.php" class="btn btn-primary">Browse All Properties</a>
            </div>
        </div>
    <?php endif; ?>
</section>

<!-- Statistics -->
<section class="container mt-5">
    <div class="text-center mb-5">
        <h2 class="mb-2">Market Overview</h2>
        <p class="text-muted">Get insights into our current property market</p>
    </div>
    <div class="row g-4">
        <div class="col-lg-3 col-md-6">
            <div class="stats-card text-center h-100">
                <div class="mb-3">
                    <i class="fas fa-home fa-2x text-primary"></i>
                </div>
                <h3 class="mb-1 text-primary"><?= (int)$stats['total_properties']; ?></h3>
                <p class="text-muted mb-0">Total Properties</p>
                <small class="text-success">+5% from last month</small>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="stats-card text-center h-100">
                <div class="mb-3">
                    <i class="fas fa-key fa-2x text-success"></i>
                </div>
                <h3 class="mb-1 text-success"><?= (int)$stats['available_properties']; ?></h3>
                <p class="text-muted mb-0">Available Now</p>
                <small class="text-success">Ready for viewing</small>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="stats-card text-center h-100">
                <div class="mb-3">
                    <i class="fas fa-check-circle fa-2x text-info"></i>
                </div>
                <h3 class="mb-1 text-info"><?= (int)$stats['sold_properties']; ?></h3>
                <p class="text-muted mb-0">Recently Sold</p>
                <small class="text-info">This quarter</small>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="stats-card text-center h-100">
                <div class="mb-3">
                    <i class="fas fa-dollar-sign fa-2x text-warning"></i>
                </div>
                <h3 class="mb-1 text-warning"><?= is_numeric($stats['avg_price']) ? number_format($stats['avg_price'], 0) : '0'; ?></h3>
                <p class="text-muted mb-0">Average Price</p>
                <small class="text-warning">Market value</small>
            </div>
        </div>
    </div>
</section>

<!-- Call to Action -->
<section class="container mt-5">
    <div class="bg-gradient-primary text-white rounded-4 p-5 text-center">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <h2 class="mb-3">Ready to Find Your Dream Home?</h2>
                <p class="lead mb-0">Join thousands of satisfied customers who found their perfect property with us.</p>
            </div>
            <div class="col-lg-4 text-lg-end">
                <a href="register.php" class="btn btn-light btn-lg me-2">
                    <i class="fas fa-user-plus me-2"></i>Sign Up
                </a>
                <a href="contact.php" class="btn btn-outline-light btn-lg">
                    <i class="fas fa-phone me-2"></i>Contact Us
                </a>
            </div>
        </div>
    </div>
</section>

<footer class="mt-5">
    <div class="container">
        <div class="row py-5">
            <div class="col-lg-4 mb-4">
                <h5 class="mb-3">Real Estate</h5>
                <p class="text-muted">Your trusted partner in finding the perfect property. We connect buyers with their dream homes and sellers with qualified buyers.</p>
                <div class="d-flex gap-3">
                    <a href="#" class="text-muted"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="text-muted"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="text-muted"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="text-muted"><i class="fab fa-linkedin-in"></i></a>
                </div>
            </div>
            <div class="col-lg-2 col-md-6 mb-4">
                <h6 class="mb-3">Properties</h6>
                <ul class="list-unstyled">
                    <li><a href="#" class="text-muted text-decoration-none">Houses</a></li>
                    <li><a href="#" class="text-muted text-decoration-none">Apartments</a></li>
                    <li><a href="#" class="text-muted text-decoration-none">Commercial</a></li>
                    <li><a href="#" class="text-muted text-decoration-none">Land</a></li>
                </ul>
            </div>
            <div class="col-lg-2 col-md-6 mb-4">
                <h6 class="mb-3">Services</h6>
                <ul class="list-unstyled">
                    <li><a href="#" class="text-muted text-decoration-none">Buy</a></li>
                    <li><a href="#" class="text-muted text-decoration-none">Sell</a></li>
                    <li><a href="#" class="text-muted text-decoration-none">Rent</a></li>
                    <li><a href="#" class="text-muted text-decoration-none">Invest</a></li>
                </ul>
            </div>
            <div class="col-lg-2 col-md-6 mb-4">
                <h6 class="mb-3">Company</h6>
                <ul class="list-unstyled">
                    <li><a href="#" class="text-muted text-decoration-none">About</a></li>
                    <li><a href="#" class="text-muted text-decoration-none">Careers</a></li>
                    <li><a href="#" class="text-muted text-decoration-none">Contact</a></li>
                    <li><a href="#" class="text-muted text-decoration-none">Blog</a></li>
                </ul>
            </div>
            <div class="col-lg-2 col-md-6 mb-4">
                <h6 class="mb-3">Support</h6>
                <ul class="list-unstyled">
                    <li><a href="#" class="text-muted text-decoration-none">Help Center</a></li>
                    <li><a href="#" class="text-muted text-decoration-none">Privacy Policy</a></li>
                    <li><a href="#" class="text-muted text-decoration-none">Terms of Service</a></li>
                    <li><a href="#" class="text-muted text-decoration-none">FAQ</a></li>
                </ul>
            </div>
        </div>
        <hr>
        <div class="text-center py-3">
            <small class="text-muted">© <?= date('Y'); ?> Real Estate. All rights reserved. Built with ❤️ for property seekers.</small>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
