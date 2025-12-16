<?php
session_start();
include('./Database/connection.php');
include('./includes/route.php');
include('./includes/security.php');

$page_title = 'Property Listings - Real Estate';
$page_description = 'Browse our comprehensive collection of premium properties. Find your perfect home from thousands of listings.';

// Initialize variables to prevent errors
$properties = [];
$property_types = [];
$locations = [];
$total_properties = 0;

// Get filter values with defaults
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$property_type = isset($_GET['property_type']) ? trim($_GET['property_type']) : '';
$location = isset($_GET['location']) ? trim($_GET['location']) : '';
$price_range = isset($_GET['price_range']) ? trim($_GET['price_range']) : '';
$min_price = isset($_GET['min_price']) ? (int)$_GET['min_price'] : 0;
$max_price = isset($_GET['max_price']) ? (int)$_GET['max_price'] : 0;
$bedrooms = isset($_GET['bedrooms']) ? (int)$_GET['bedrooms'] : 0;
$bathrooms = isset($_GET['bathrooms']) ? (int)$_GET['bathrooms'] : 0;
$min_area = isset($_GET['min_area']) ? (int)$_GET['min_area'] : 0;
$max_area = isset($_GET['max_area']) ? (int)$_GET['max_area'] : 0;
$year_built = isset($_GET['year_built']) ? (int)$_GET['year_built'] : 0;
$sort_by = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'newest';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 12;
$show_featured = isset($_GET['featured']) && $_GET['featured'] == '1';
$show_all = isset($_GET['show_all']) && $_GET['show_all'] == '1';

// Handle price range selection
if ($price_range && strpos($price_range, '-') !== false) {
    list($min_price, $max_price) = explode('-', $price_range);
    $min_price = (int)$min_price;
    $max_price = (int)$max_price;
}

// Only proceed if database connection is successful
if ($conn) {
    // Build simple query
    $where_conditions = [];
    $params = [];
    $types = '';
    
    // Base query
    $sql = "SELECT p.id, p.propertiesname, p.description, p.bedrooms, p.bathrooms, p.area_sqft, p.year_built, p.images, p.is_featured, p.status,
           pr.amount AS price, pr.currency, pr.price_type,
           pt.type_name AS property_type,
           l.city, l.country, l.region
           FROM properties p
           LEFT JOIN prices pr ON p.price_id = pr.id
           LEFT JOIN property_types pt ON p.property_type_id = pt.id
           LEFT JOIN locations l ON p.location_id = l.id
           WHERE p.status = 'available'";
    
    // Add search conditions
    if (!empty($search)) {
        $sql .= " AND (p.propertiesname LIKE ? OR p.description LIKE ? OR l.city LIKE ?)";
        $search_term = "%{$search}%";
        $params[] = $search_term;
        $params[] = $search_term;
        $params[] = $search_term;
        $types .= 'sss';
    }
    
    if (!empty($property_type)) {
        $sql .= " AND p.property_type_id = ?";
        $params[] = (int)$property_type;
        $types .= 'i';
    }
    
    if (!empty($location)) {
        $sql .= " AND (l.city LIKE ? OR l.region LIKE ?)";
        $location_term = "%{$location}%";
        $params[] = $location_term;
        $params[] = $location_term;
        $types .= 'ss';
    }
    
    if ($min_price > 0) {
        $sql .= " AND pr.amount >= ?";
        $params[] = $min_price;
        $types .= 'd';
    }
    
    if ($max_price > 0) {
        $sql .= " AND pr.amount <= ?";
        $params[] = $max_price;
        $types .= 'd';
    }
    
    if ($bedrooms > 0) {
        $sql .= " AND p.bedrooms >= ?";
        $params[] = $bedrooms;
        $types .= 'i';
    }
    
    if ($bathrooms > 0) {
        $sql .= " AND p.bathrooms >= ?";
        $params[] = $bathrooms;
        $types .= 'i';
    }
    
    if ($min_area > 0) {
        $sql .= " AND p.area_sqft >= ?";
        $params[] = $min_area;
        $types .= 'i';
    }
    
    if ($max_area > 0) {
        $sql .= " AND p.area_sqft <= ?";
        $params[] = $max_area;
        $types .= 'i';
    }
    
    if ($year_built > 0) {
        $sql .= " AND p.year_built >= ?";
        $params[] = $year_built;
        $types .= 'i';
    }
    
    if ($show_featured) {
        $sql .= " AND p.is_featured = 1";
    }
    
    // Add sorting
    switch ($sort_by) {
        case 'price_low':
            $sql .= " ORDER BY pr.amount ASC";
            break;
        case 'price_high':
            $sql .= " ORDER BY pr.amount DESC";
            break;
        case 'newest':
            $sql .= " ORDER BY p.created_at DESC";
            break;
        case 'featured':
            $sql .= " ORDER BY p.is_featured DESC, p.created_at DESC";
            break;
        default:
            $sql .= " ORDER BY p.created_at DESC";
    }
    
    // Get total count first
    $count_sql = str_replace('SELECT p.id, p.propertiesname, p.description, p.bedrooms, p.bathrooms, p.area_sqft, p.year_built, p.images, p.is_featured, p.status,
           pr.amount AS price, pr.currency, pr.price_type,
           pt.type_name AS property_type,
           l.city, l.country, l.region', 'SELECT COUNT(*) as total', $sql);
    $count_sql = preg_replace('/ORDER BY.*$/', '', $count_sql);
    
    try {
        if (!empty($params)) {
            $count_stmt = $conn->prepare($count_sql);
            $count_stmt->bind_param($types, ...$params);
            $count_stmt->execute();
            $count_result = $count_stmt->get_result();
            $total_properties = $count_result->fetch_assoc()['total'];
            $count_stmt->close();
        } else {
            $count_result = $conn->query($count_sql);
            $total_properties = $count_result->fetch_assoc()['total'];
        }
    } catch (Exception $e) {
        // Fallback for count
        $total_properties = 0;
    }
    
    // Add pagination to main query
    $offset = ($page - 1) * $per_page;
    $sql .= " LIMIT {$per_page} OFFSET {$offset}";
    
    // Execute main query
    try {
        if (!empty($params)) {
            $stmt = $conn->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
            $properties = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
        } else {
            $result = $conn->query($sql);
            $properties = $result->fetch_all(MYSQLI_ASSOC);
        }
    } catch (Exception $e) {
        // Fallback for properties
        $properties = [];
    }
    
    // Get property types
    $type_result = $conn->query("SELECT id, type_name FROM property_types ORDER BY type_name");
    if ($type_result) {
        while ($r = $type_result->fetch_assoc()) {
            $property_types[] = $r;
        }
    }
    
    // Get locations
    $location_result = $conn->query("SELECT DISTINCT city, region, country FROM locations ORDER BY city");
    if ($location_result) {
        while ($r = $location_result->fetch_assoc()) {
            $locations[] = $r;
        }
    }
}



// Remove duplicated dropdown fetches (handled above)

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
        $stats['avg_price'] = ($p && $p['avg_price'] !== null) ? round($p['avg_price'], 2) : 0;
        $price_query->free();
    }

// Calculate pagination
$total_pages = ceil($total_properties / $per_page);
$start_page = max(1, $page - 2);
$end_page = min($total_pages, $page + 2);
?>
<?php include('./includes/header.php'); ?>

<?php include('./includes/nav.php'); ?>

<!-- Page Header -->
<section class="page-header">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <h1 class="page-title">Property Listings</h1>
                <p class="page-subtitle">Discover your perfect home from our comprehensive collection of properties</p>
            </div>
            <div class="col-lg-4 text-lg-end">
                <div class="text-white">
                    <h4 class="mb-0"><?= $total_properties; ?></h4>
                    <small>Properties Found</small>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Advanced Search & Filter -->
<section class="py-5">
    <div class="container">
        <div class="filter-section">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4><i class="fas fa-filter me-2"></i>Advanced Search & Filters</h4>
                <button class="btn btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#advancedFilters">
                    <i class="fas fa-sliders-h me-2"></i>More Filters
                </button>
            </div>
            
            <form method="get" id="propertySearchForm">
                <input type="hidden" name="csrf_token" value="<?= SecurityValidator::getInstance()->generateCSRFToken(); ?>">
                <!-- Basic Search Row -->
                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label for="search" class="form-label">Search Property</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                            <input type="text" name="search" value="<?= htmlspecialchars($search); ?>" 
                                   class="form-control" id="search" 
                                   placeholder="Name, description, city or region">
                        </div>
                    </div>

                    <div class="col-md-3">
                        <label for="property_type" class="form-label">Property Type</label>
                        <select name="property_type" id="property_type" class="form-select">
                            <option value="">All Types</option>
                            <?php foreach ($property_types as $type): ?>
                                <option value="<?= $type['id']; ?>" <?= $property_type == $type['id'] ? 'selected' : ''; ?>>
                                    <?= htmlspecialchars($type['type_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label for="location" class="form-label">Location</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-map-marker-alt"></i></span>
                            <input type="text" name="location" value="<?= htmlspecialchars($location); ?>" 
                                   class="form-control" id="location" 
                                   placeholder="City, Country, Region">
                        </div>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search me-2"></i>Search
                        </button>
                    </div>
                </div>

                <!-- Advanced Filters Collapse -->
                <div class="collapse" id="advancedFilters">
                    <div class="row g-3 mb-3">
                        <div class="col-md-3">
                            <label for="min_price" class="form-label">Min Price</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" name="min_price" value="<?= $min_price; ?>" 
                                       class="form-control" id="min_price" placeholder="0">
                            </div>
                        </div>

                        <div class="col-md-3">
                            <label for="max_price" class="form-label">Max Price</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" name="max_price" value="<?= $max_price; ?>" 
                                       class="form-control" id="max_price" placeholder="Any">
                            </div>
                        </div>

                        <div class="col-md-2">
                            <label for="bedrooms" class="form-label">Min Bedrooms</label>
                            <select name="bedrooms" id="bedrooms" class="form-select">
                                <option value="">Any</option>
                                <?php for($i = 1; $i <= 5; $i++): ?>
                                    <option value="<?= $i; ?>" <?= $bedrooms == $i ? 'selected' : ''; ?>>
                                        <?= $i; ?>+
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>

                        <div class="col-md-2">
                            <label for="bathrooms" class="form-label">Min Bathrooms</label>
                            <select name="bathrooms" id="bathrooms" class="form-select">
                                <option value="">Any</option>
                                <?php for($i = 1; $i <= 4; $i++): ?>
                                    <option value="<?= $i; ?>" <?= $bathrooms == $i ? 'selected' : ''; ?>>
                                        <?= $i; ?>+
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>

                        <div class="col-md-2">
                            <label for="sort_by" class="form-label">Sort By</label>
                            <select name="sort_by" id="sort_by" class="form-select">
                                <option value="newest" <?= $sort_by == 'newest' ? 'selected' : ''; ?>>Newest First</option>
                                <option value="price_low" <?= $sort_by == 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                                <option value="price_high" <?= $sort_by == 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
                                <option value="featured" <?= $sort_by == 'featured' ? 'selected' : ''; ?>>Featured First</option>
                                <option value="popular" <?= $sort_by == 'popular' ? 'selected' : ''; ?>>Most Popular</option>
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-3">
                            <label for="min_area" class="form-label">Min Area (sqft)</label>
                            <input type="number" name="min_area" value="<?= isset($_GET['min_area']) ? (int)$_GET['min_area'] : ''; ?>" 
                                   class="form-control" id="min_area" placeholder="0">
                        </div>

                        <div class="col-md-3">
                            <label for="max_area" class="form-label">Max Area (sqft)</label>
                            <input type="number" name="max_area" value="<?= isset($_GET['max_area']) ? (int)$_GET['max_area'] : ''; ?>" 
                                   class="form-control" id="max_area" placeholder="Any">
                        </div>

                        <div class="col-md-3">
                            <label for="year_built" class="form-label">Min Year Built</label>
                            <select name="year_built" id="year_built" class="form-select">
                                <option value="">Any Year</option>
                                <?php 
                                $current_year = date('Y');
                                $selected_year = isset($_GET['year_built']) ? (int)$_GET['year_built'] : '';
                                for($year = $current_year; $year >= 1950; $year -= 10): 
                                ?>
                                    <option value="<?= $year; ?>" <?= $selected_year == $year ? 'selected' : ''; ?>>
                                        <?= $year; ?>+
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <div class="form-check form-switch mt-4">
                                <input class="form-check-input" type="checkbox" id="featured" name="featured" value="1" 
                                       <?= $show_featured ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="featured">
                                    <i class="fas fa-star text-warning me-1"></i>Featured Properties Only
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="row g-3">
                    <div class="col-md-6">
                        <a href="property.php" class="btn btn-outline-secondary">
                            <i class="fas fa-times me-2"></i>Clear All Filters
                        </a>
                        <button type="button" class="btn btn-outline-info ms-2" onclick="saveSearch()">
                            <i class="fas fa-bookmark me-2"></i>Save Search
                        </button>
                    </div>
                    <div class="col-md-6 text-end">
                        <small class="text-muted">
                            Showing <?= count($properties); ?> of <?= $total_properties; ?> properties
                            <?php if($search || $property_type || $location || $min_price || $max_price): ?>
                                <span class="badge bg-primary ms-1">Filtered</span>
                            <?php endif; ?>
                        </small>
                    </div>
                </div>
            </form>
        </div>
    </div>
</section>

<!-- Property Listings -->
<section class="container mt-5">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h2 class="mb-1">Available Properties</h2>
            <p class="text-muted mb-0">Discover your perfect home from our curated collection</p>
        </div>
        <div class="d-flex align-items-center gap-3">
            <div class="btn-group" role="group" aria-label="View toggle">
                <input type="radio" class="btn-check" name="view-mode" id="grid-view" autocomplete="off" checked>
                <label class="btn btn-outline-primary" for="grid-view"><i class="fas fa-th-large"></i> Grid</label>
                
                <input type="radio" class="btn-check" name="view-mode" id="list-view" autocomplete="off">
                <label class="btn btn-outline-primary" for="list-view"><i class="fas fa-list"></i> List</label>
                
                <input type="radio" class="btn-check" name="view-mode" id="map-view" autocomplete="off">
                <label class="btn btn-outline-primary" for="map-view"><i class="fas fa-map"></i> Map</label>
            </div>
            <div class="text-end">
                <div class="text-muted small">Showing <?= count($properties); ?> <?= (count($properties) === 1) ? 'property' : 'properties'; ?></div>
                <div class="small text-primary"><?= (int)$stats['available_properties']; ?> total available</div>
            </div>
        </div>

        <!-- Enhanced Result Summary -->
        <div class="alert alert-info mt-3 mb-4" role="alert">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <strong><i class="fas fa-search me-2"></i>Search Results:</strong>
                    <span class="ms-2">Found <strong><?= $total_properties; ?></strong> <?= ($total_properties === 1) ? 'property' : 'properties'; ?></span>
                    <?php if (!empty($search)): ?>
                        <span class="ms-2">matching "<strong><?= htmlspecialchars($search); ?></strong>"</span>
                    <?php endif; ?>
                    <?php if (!empty($property_type)): ?>
                        <span class="ms-2">- Type: <strong><?= htmlspecialchars(implode(', ', array_filter(array_map(function($t) use ($property_type) { return $t['id'] == $property_type ? $t['type_name'] : ''; }, $property_types)))); ?></strong></span>
                    <?php endif; ?>
                    <?php if (!empty($location)): ?>
                        <span class="ms-2">- Location: <strong><?= htmlspecialchars($location); ?></strong></span>
                    <?php endif; ?>
                    <?php if ($min_price > 0 || $max_price > 0): ?>
                        <span class="ms-2">- Price range: <strong>
                            <?php if ($min_price > 0): ?><?= number_format($min_price); ?><?php endif; ?>
                            <?php if ($min_price > 0 && $max_price > 0): ?> - <?php endif; ?>
                            <?php if ($max_price > 0): ?><?= number_format($max_price); ?><?php endif; ?>
                        </strong></span>
                    <?php endif; ?>
                    <?php if ($page > 1): ?>
                        <div class="mt-2">
                            <small class="text-muted">
                                <i class="fas fa-info-circle me-1"></i>
                                Displaying <?= max(1, ($page - 1) * $per_page + 1); ?> to <?= min($page * $per_page, $total_properties); ?> of <?= $total_properties; ?> properties
                            </small>
                        </div>
                    <?php endif; ?>
                </div>
                <?php if (isLoggedIn() && getUserRole() === 'client'): ?>
                    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#saveSearchModal">
                        <i class="fas fa-bookmark me-1"></i> Save Search
                    </button>
                <?php endif; ?>
                <button type="button" class="btn btn-info btn-sm" onclick="viewComparison()" id="viewComparisonBtn" style="display: none;">
                    <i class="fas fa-columns me-1"></i> Compare
                    <span id="comparisonCount" class="badge bg-light text-dark ms-1" style="display: none;">0</span>
                </button>
            </div>
        </div>

        <!-- Save Search Modal -->
        <?php if (isLoggedIn() && getUserRole() === 'client'): ?>
        <div class="modal fade" id="saveSearchModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title"><i class="fas fa-bookmark me-2"></i>Save This Search</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <form method="POST" action="saved_searches_handler.php">
                        <div class="modal-body">
                            <input type="hidden" name="csrf_token" value="<?= SecurityValidator::getInstance()->generateCSRFToken(); ?>">
                            <input type="hidden" name="save_search" value="1">
                            
                            <div class="mb-3">
                                <label for="searchName" class="form-label">Search Name</label>
                                <input type="text" class="form-control" id="searchName" name="search_name" placeholder="e.g., Waterfront Properties Under $500K" required>
                                <small class="text-muted">Give your search a memorable name</small>
                            </div>

                            <div class="form-check mb-3">
                                <input type="checkbox" class="form-check-input" id="emailAlerts" name="email_alerts">
                                <label class="form-check-label" for="emailAlerts">
                                    Receive Email Alerts
                                </label>
                                <small class="text-muted d-block mt-1">Get notified when new properties match your search</small>
                            </div>

                            <div class="mb-3">
                                <label for="alertFrequency" class="form-label">Alert Frequency</label>
                                <select class="form-select" id="alertFrequency" name="alert_frequency">
                                    <option value="daily">Daily</option>
                                    <option value="weekly" selected>Weekly</option>
                                    <option value="monthly">Monthly</option>
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i> Save Search
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Map View -->
    <div id="map-container" class="mb-4" style="display: none;">
        <div id="property-map" style="height: 500px; border-radius: 10px;"></div>
        <div class="mt-3">
            <small class="text-muted">
                <i class="fas fa-info-circle me-1"></i>
                Click on map markers to view property details. Zoom and pan to explore different areas.
            </small>
        </div>
    </div>

    <?php if (!empty($properties)): ?>
        <div class="row g-4">
            <?php foreach ($properties as $property): ?>
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
                                    <div class="d-flex gap-2">
                                        <?php if (isset($_SESSION['user_id'])): ?>
                                            <button class="btn btn-outline-danger btn-sm bookmark-btn" 
                                                    data-property-id="<?= $property['id']; ?>"
                                                    title="Bookmark this property">
                                                <i class="fas fa-heart"></i>
                                            </button>
                                        <?php endif; ?>
                                        <button type="button" class="btn btn-outline-secondary btn-sm comparison-btn"
                                                data-property-id="<?= $property['id']; ?>"
                                                title="Add to comparison">
                                            <i class="fas fa-columns me-1"></i>Compare
                                        </button>
                                        <a href="property_details.php?id=<?= $property['id']; ?>" class="btn btn-primary btn-sm">
                                            <i class="fas fa-eye me-1"></i>View Details
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <?php if (!$show_all && count($properties) >= 6): ?>
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

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
        <nav aria-label="Property pagination" class="mt-5">
            <ul class="pagination">
                <!-- Previous Page -->
                <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">
                            <i class="fas fa-chevron-left"></i> Previous
                        </a>
                    </li>
                <?php endif; ?>

                <!-- Page Numbers -->
                <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                    <li class="page-item <?= $i == $page ? 'active' : ''; ?>">
                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])); ?>">
                            <?= $i; ?>
                        </a>
                    </li>
                <?php endfor; ?>

                <!-- Next Page -->
                <?php if ($page < $total_pages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">
                            Next <i class="fas fa-chevron-right"></i>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>

        <!-- Pagination Info -->
        <div class="text-center mt-3">
            <p class="text-muted">
                Showing <?= (($page - 1) * $per_page) + 1; ?> to <?= min($page * $per_page, $total_properties); ?> 
                of <?= $total_properties; ?> properties
            </p>
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

<?php /* Footer will be included at the end after scripts */ ?>

<!-- Leaflet Map CSS and JS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
// Property data for map
const propertyData = <?= json_encode($properties); ?>;

// Initialize map
let map = null;
let markers = [];

// View mode switching
document.addEventListener('DOMContentLoaded', function() {
    const viewModes = document.querySelectorAll('input[name="view-mode"]');
    const mapContainer = document.getElementById('map-container');
    const propertiesGrid = document.querySelector('.row.g-4');
    
    viewModes.forEach(mode => {
        mode.addEventListener('change', function() {
            switch(this.id) {
                case 'grid-view':
                    mapContainer.style.display = 'none';
                    propertiesGrid.style.display = 'flex';
                    propertiesGrid.className = 'row g-4';
                    break;
                case 'list-view':
                    mapContainer.style.display = 'none';
                    propertiesGrid.style.display = 'block';
                    propertiesGrid.className = 'row g-2 list-view';
                    break;
                case 'map-view':
                    mapContainer.style.display = 'block';
                    propertiesGrid.style.display = 'none';
                    setTimeout(initializeMap, 100); // Small delay for container to be visible
                    break;
            }
        });
    });
});

function initializeMap() {
    if (map) {
        map.remove();
    }
    
    // Initialize map centered on a default location
    map = L.map('property-map').setView([40.7128, -74.0060], 10); // New York as default
    
    // Add tile layer
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
    }).addTo(map);
    
    // Clear existing markers
    markers.forEach(marker => marker.remove());
    markers = [];
    
    // Add property markers
    if (propertyData && propertyData.length > 0) {
        let bounds = [];
        
        propertyData.forEach(property => {
            // Generate coordinates based on location (in real app, you'd have actual coordinates)
            const lat = 40.7128 + (Math.random() - 0.5) * 0.5; // Random coords around NYC
            const lng = -74.0060 + (Math.random() - 0.5) * 0.5;
            
            const marker = L.marker([lat, lng]).addTo(map);
            
            // Create popup content
            const popupContent = `
                <div class="map-popup">
                    <img src="${property.images || 'https://images.unsplash.com/photo-1564013799919-ab600027ffc6?w=200&h=150&fit=crop'}" 
                         style="width: 200px; height: 150px; object-fit: cover; border-radius: 5px;">
                    <h6 class="mt-2 mb-1">${property.propertiesname}</h6>
                    <p class="small text-muted mb-1">
                        <i class="fas fa-map-marker-alt me-1"></i>${property.city}, ${property.country}
                    </p>
                    <p class="fw-bold text-primary mb-2">${property.currency} ${Number(property.price || 0).toLocaleString()}</p>
                    <div class="small text-muted mb-2">
                        <i class="fas fa-bed me-1"></i>${property.bedrooms} beds
                        <i class="fas fa-bath ms-2 me-1"></i>${property.bathrooms} baths
                        <i class="fas fa-ruler-combined ms-2 me-1"></i>${Number(property.area_sqft || 0).toLocaleString()} sqft
                    </div>
                    <a href="property_details.php?id=${property.id}" class="btn btn-primary btn-sm">
                        <i class="fas fa-eye me-1"></i>View Details
                    </a>
                </div>
            `;
            
            marker.bindPopup(popupContent);
            markers.push(marker);
            bounds.push([lat, lng]);
        });
        
        // Fit map to show all markers
        if (bounds.length > 0) {
            map.fitBounds(bounds, { padding: [20, 20] });
        }
    }
}

// Location-based search
function searchByLocation() {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function(position) {
            const lat = position.coords.latitude;
            const lng = position.coords.longitude;
            
            // You would implement reverse geocoding here to get the address
            // and then filter properties by location
            console.log('User location:', lat, lng);
            
            // For demo, just show a message
            showToast('Location detected! In a real implementation, this would filter properties near you.', 'info');
        }, function(error) {
            showToast('Unable to get your location. Please enter a location manually.', 'warning');
        });
    } else {
        showToast('Geolocation is not supported by this browser.', 'warning');
    }
}

// Add location search button to the filter section
document.addEventListener('DOMContentLoaded', function() {
    const locationInput = document.getElementById('location');
    if (locationInput) {
        const locationButton = document.createElement('button');
        locationButton.type = 'button';
        locationButton.className = 'btn btn-outline-secondary';
        locationButton.innerHTML = '<i class="fas fa-location-arrow"></i>';
        locationButton.title = 'Use my location';
        locationButton.onclick = searchByLocation;
        
        const inputGroup = locationInput.parentElement;
        if (inputGroup.classList.contains('input-group')) {
            inputGroup.appendChild(locationButton);
        }
    }
});
</script>

<style>
/* Map and list view styles */
.map-popup {
    min-width: 200px;
}

.list-view .col-lg-4 {
    flex: 0 0 100%;
    max-width: 100%;
}

.list-view .card {
    flex-direction: row;
    height: auto;
}

.list-view .card-img-top {
    width: 200px;
    height: 150px;
    object-fit: cover;
    border-radius: 0;
}

.list-view .card-body {
    flex: 1;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}

.btn-check:checked + .btn-outline-primary {
    background-color: var(--primary-color);
    border-color: var(--primary-color);
    color: white;
}

@media (max-width: 768px) {
    .list-view .card {
        flex-direction: column;
    }
    
    .list-view .card-img-top {
        width: 100%;
        height: 200px;
    }
}
</style>

<script>
// Enhanced property search functionality
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Auto-submit form on filter change
    const form = document.getElementById('propertySearchForm');
    const autoSubmitElements = ['property_type', 'sort_by', 'bedrooms', 'bathrooms'];
    
    autoSubmitElements.forEach(id => {
        const element = document.getElementById(id);
        if (element) {
            element.addEventListener('change', function() {
                // Add loading indicator
                const submitBtn = form.querySelector('button[type="submit"]');
                const originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Searching...';
                submitBtn.disabled = true;
                
                // Submit form after short delay
                setTimeout(() => form.submit(), 300);
            });
        }
    });

    // Initialize bookmark functionality
    initializeBookmarks();
    
    // Property card hover effects
    const propertyCards = document.querySelectorAll('.card');
    propertyCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-10px)';
            this.style.transition = 'all 0.3s ease';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });
    
    // Search suggestions (simple implementation)
    const searchInput = document.getElementById('search');
    if (searchInput) {
        let searchTimeout;
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                // Could implement autocomplete here
                console.log('Searching for:', this.value);
            }, 300);
        });
    }
    
    // Price range formatting
    const priceInputs = document.querySelectorAll('input[name="min_price"], input[name="max_price"]');
    priceInputs.forEach(input => {
        input.addEventListener('input', function() {
            // Format price with commas
            let value = this.value.replace(/,/g, '');
            if (value && !isNaN(value)) {
                this.value = parseInt(value).toLocaleString();
            }
        });
        
        input.addEventListener('blur', function() {
            // Remove commas for form submission
            this.value = this.value.replace(/,/g, '');
        });
    });
    
    // Advanced filters state
    const advancedFilters = document.getElementById('advancedFilters');
    const hasAdvancedFilters = <?= json_encode(
        !empty($_GET['min_price']) || !empty($_GET['max_price']) || 
        !empty($_GET['min_area']) || !empty($_GET['max_area']) || 
        !empty($_GET['year_built'])
    ); ?>;
    
    if (hasAdvancedFilters && advancedFilters) {
        advancedFilters.classList.add('show');
    }
    
    // Initialize comparison button handlers
    const comparisonBtns = document.querySelectorAll('.comparison-btn');
    comparisonBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            addToComparison(this.dataset.propertyId);
        });
    });
    
    // Load comparison cart from localStorage
    loadComparisonCart();
    
    // Initialize view mode from localStorage
    const savedViewMode = localStorage.getItem('propertyViewMode');
    if (savedViewMode === 'list') {
        toggleViewMode('list');
    }
});

// Bookmark functionality
function initializeBookmarks() {
    const bookmarkBtns = document.querySelectorAll('.bookmark-btn');
    
    // Initialize bookmark states
    bookmarkBtns.forEach(btn => {
        const propertyId = btn.dataset.propertyId;
        checkBookmarkStatus(propertyId, btn);
    });
    
    // Add click event listeners
    bookmarkBtns.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const propertyId = this.dataset.propertyId;
            const isBookmarked = this.classList.contains('bookmarked');
            const action = isBookmarked ? 'remove' : 'add';
            
            // Visual feedback
            this.disabled = true;
            const icon = this.querySelector('i');
            icon.className = 'fas fa-spinner fa-spin';
            
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
                        icon.className = 'fas fa-heart';
                        this.title = 'Remove from bookmarks';
                        showToast('Property bookmarked!', 'success');
                    } else if (data.action === 'removed') {
                        this.classList.remove('bookmarked');
                        this.classList.remove('btn-danger');
                        this.classList.add('btn-outline-danger');
                        icon.className = 'fas fa-heart';
                        this.title = 'Bookmark this property';
                        showToast('Bookmark removed!', 'info');
                    }
                } else {
                    showToast(data.message || 'An error occurred', 'error');
                    icon.className = 'fas fa-heart';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('An error occurred. Please try again.', 'error');
                icon.className = 'fas fa-heart';
            })
            .finally(() => {
                this.disabled = false;
            });
        });
    });
}

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
            btn.title = 'Remove from bookmarks';
        }
    })
    .catch(error => {
        console.error('Bookmark check error:', error);
    });
}

// Enhanced toast notifications
function showToast(message, type = 'info') {
    // Map 'error' to 'danger' for Bootstrap
    const bootstrapType = type === 'error' ? 'danger' : type;
    
    // Remove existing toasts
    const existingToasts = document.querySelectorAll('.toast-custom');
    existingToasts.forEach(toast => toast.remove());

    const toastHtml = `
        <div class="toast-custom alert alert-${bootstrapType} position-fixed"
            style="top: 20px; right: 20px; z-index: 9999; min-width: 250px;">
            <strong>${message}</strong>
        </div>
    `;

    document.body.insertAdjacentHTML('beforeend', toastHtml);

    // Auto-remove after 5 seconds
    setTimeout(() => {
        const toast = document.querySelector('.toast-custom');
        if (toast) {
            toast.style.transition = 'opacity 0.5s';
            toast.style.opacity = '0';
            setTimeout(() => toast.remove(), 500);
        }
    }, 5000);
}

// Save search functionality
function saveSearch() {
    const form = document.getElementById('propertySearchForm');
    const formData = new FormData(form);
    const searchParams = new URLSearchParams(formData);
    
    // Save to localStorage
    const searchData = {
        url: window.location.href,
        params: Object.fromEntries(searchParams),
        timestamp: new Date().toISOString(),
        name: `Search ${new Date().toLocaleDateString()}`
    };
    
    let savedSearches = JSON.parse(localStorage.getItem('savedSearches') || '[]');
    savedSearches.unshift(searchData);
    
    // Keep only last 10 searches
    savedSearches = savedSearches.slice(0, 10);
    
    localStorage.setItem('savedSearches', JSON.stringify(savedSearches));
    showToast('Search saved successfully!', 'success');
}

// Load saved searches (could be expanded)
function loadSavedSearches() {
    return JSON.parse(localStorage.getItem('savedSearches') || '[]');
}

// Quick filters
function applyQuickFilter(type, value) {
    const form = document.getElementById('propertySearchForm');
    const input = form.querySelector(`[name="${type}"]`);
    if (input) {
        if (input.type === 'checkbox') {
            input.checked = value;
        } else {
            input.value = value;
        }
        form.submit();
    }
}

// Property comparison (placeholder for future enhancement)
const selectedProperties = new Set();

function togglePropertyComparison(propertyId) {
    if (selectedProperties.has(propertyId)) {
        selectedProperties.delete(propertyId);
    } else {
        selectedProperties.add(propertyId);
    }
    
    updateComparisonUI();
}

function updateComparisonUI() {
    // Could implement comparison feature here
    console.log('Selected properties for comparison:', selectedProperties);
}

// View mode toggle (grid/list)
function toggleViewMode(mode) {
    const container = document.querySelector('.row.g-4');
    if (mode === 'list') {
        container.classList.add('list-view');
    } else {
        container.classList.remove('list-view');
    }
    
    // Save preference
    localStorage.setItem('propertyViewMode', mode);
}


// Comparison cart management
function addToComparison(propertyId) {
    let cart = JSON.parse(localStorage.getItem('comparisonCart') || '[]');
    
    if (cart.includes(propertyId)) {
        cart = cart.filter(id => id !== propertyId);
        showToast('Property removed from comparison', 'info');
    } else {
        if (cart.length >= 4) {
            showToast('Maximum 4 properties can be compared', 'warning');
            return;
        }
        cart.push(propertyId);
        showToast('Property added to comparison', 'success');
    }
    
    localStorage.setItem('comparisonCart', JSON.stringify(cart));
    updateComparisonButton(propertyId);
    updateComparisonCart();
}

function updateComparisonButton(propertyId) {
    const btn = document.querySelector(`[data-property-id="${propertyId}"].comparison-btn`);
    if (!btn) return;
    
    let cart = JSON.parse(localStorage.getItem('comparisonCart') || '[]');
    if (cart.includes(propertyId)) {
        btn.classList.add('active');
        btn.classList.remove('btn-outline-secondary');
        btn.classList.add('btn-secondary');
        btn.innerHTML = '<i class="fas fa-check me-1"></i>Added';
    } else {
        btn.classList.remove('active');
        btn.classList.add('btn-outline-secondary');
        btn.classList.remove('btn-secondary');
        btn.innerHTML = '<i class="fas fa-columns me-1"></i>Compare';
    }
}

function loadComparisonCart() {
    let cart = JSON.parse(localStorage.getItem('comparisonCart') || '[]');
    cart.forEach(propertyId => {
        updateComparisonButton(propertyId);
    });
    updateComparisonCart();
}

function updateComparisonCart() {
    let cart = JSON.parse(localStorage.getItem('comparisonCart') || '[]');
    const countBadge = document.getElementById('comparisonCount');
    const viewBtn = document.getElementById('viewComparisonBtn');
    if (countBadge && viewBtn) {
        countBadge.textContent = cart.length;
        countBadge.style.display = cart.length > 0 ? 'inline-block' : 'none';
        viewBtn.style.display = cart.length > 0 ? 'inline-block' : 'none';
    }
}

// View comparison
function viewComparison() {
    let cart = JSON.parse(localStorage.getItem('comparisonCart') || '[]');
    if (cart.length > 0) {
        window.location.href = `property_comparison.php?ids=${cart.join(',')}`;
    } else {
        showToast('No properties to compare', 'warning');
    }
}
</script>
<?php include('./includes/footer.php'); ?>
