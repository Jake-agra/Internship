<?php
session_start();
include('./Database/connection.php');

// Initialize variables to prevent undefined errors
$featured_properties = [];
$stats = [
    'total_properties' => 0,
    'available_properties' => 0,
    'sold_properties' => 0,
    'avg_price' => 0
];
$property_types = [];

// Only proceed if database connection is successful
if ($conn) {
    // Get featured properties for homepage
    $featured_query = "SELECT p.id, p.propertiesname, p.description, p.price_id, p.user_id, p.property_type_id, p.location_id, p.status, p.bedrooms, p.bathrooms, p.area_sqft, p.year_built, p.images, pr.currency, pr.price_type, pr.amount AS price, pt.type_name AS property_type, l.city, l.country, l.region
    FROM properties p
    JOIN prices pr ON p.price_id = pr.id
    JOIN locations l ON p.location_id = l.id
    JOIN property_types pt ON p.property_type_id = pt.id
    WHERE p.status = 'available' AND p.is_featured = 1
    ORDER BY p.created_at DESC
    LIMIT 6";

    $result = $conn->query($featured_query);
    if ($result) {
        $featured_properties = $result->fetch_all(MYSQLI_ASSOC);
        $result->free();
    }

    // Get statistics
    $count_query = $conn->query("SELECT  
        COUNT(*) as total_properties,
        SUM(CASE WHEN status='available' THEN 1 ELSE 0 END) as available_properties,
        SUM(CASE WHEN status='sold' THEN 1 ELSE 0 END) as sold_properties
        FROM properties");
    
    if ($count_query) {
        $count_result = $count_query->fetch_assoc();
        if ($count_result) {
            $stats = array_merge($stats, $count_result);
        }
        $count_query->free();
    }

    $price_query = $conn->query("SELECT AVG(pr.amount) as avg_price FROM properties p JOIN prices pr ON p.price_id = pr.id");
    if ($price_query) {
        $p = $price_query->fetch_assoc();
        $stats['avg_price'] = $p && $p['avg_price'] !== null ? round($p['avg_price'], 2) : 0;
        $price_query->free();
    }

    // Get property types for search dropdown
    $type_result = $conn->query("SELECT id, type_name FROM property_types ORDER BY type_name");
    if ($type_result) {
        while ($r = $type_result->fetch_assoc()) {
            $property_types[] = $r;
        }
        $type_result->free();
    }
}
?>

<?php
$page_title = 'Real Estate - Find Your Dream Home';
$page_description = 'Professional Real Estate Platform - Find your perfect home from our curated collection of premium properties.';
include('./includes/header.php');
?>

<?php include('./includes/nav.php'); ?>

<!-- Hero Section -->
<section class="hero-section">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <div class="hero-content">
                    <h1 class="hero-title">Find Your Dream Home</h1>
                    <p class="hero-subtitle">Discover amazing properties in prime locations with our comprehensive real estate platform. From luxury homes to investment opportunities.</p>
                    <div class="d-flex flex-wrap gap-3 mb-4">
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
                    <div class="d-flex gap-3">
                        <a href="property.php" class="btn btn-primary btn-lg">
                            <i class="fas fa-search me-2"></i>Browse Properties
                        </a>
                        <a href="#about" class="btn btn-outline-light btn-lg">
                            <i class="fas fa-play me-2"></i>Learn More
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="search-form">
                    <h4 class="text-center mb-4">Advanced Property Search</h4>
                    <form method="get" action="property.php" class="row g-3" id="hero-search-form">
                        <div class="col-md-6">
                            <label for="search" class="form-label">Search Property</label>
                            <input type="text" name="search" class="form-control" id="search" placeholder="Name, description, city or region">
                        </div>
                        <div class="col-md-6">
                            <label for="location" class="form-label">Location</label>
                            <input type="text" name="location" class="form-control" id="location" placeholder="City, Country, Region">
                        </div>
                        <div class="col-md-6">
                            <label for="property_type" class="form-label">Property Type</label>
                            <select name="property_type" id="property_type" class="form-select">
                                <option value="">All Types</option>
                                <?php foreach ($property_types as $type): ?>
                                    <option value="<?= $type['id']; ?>"><?= htmlspecialchars($type['type_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="price_range" class="form-label">Price Range</label>
                            <select name="price_range" id="price_range" class="form-select">
                                <option value="">Any Price</option>
                                <option value="0-100000">Under $100K</option>
                                <option value="100000-300000">$100K - $300K</option>
                                <option value="300000-500000">$300K - $500K</option>
                                <option value="500000-1000000">$500K - $1M</option>
                                <option value="1000000-999999999">$1M+</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="bedrooms" class="form-label">Bedrooms</label>
                            <select name="bedrooms" id="bedrooms" class="form-select">
                                <option value="">Any</option>
                                <option value="1">1+ beds</option>
                                <option value="2">2+ beds</option>
                                <option value="3">3+ beds</option>
                                <option value="4">4+ beds</option>
                                <option value="5">5+ beds</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="bathrooms" class="form-label">Bathrooms</label>
                            <select name="bathrooms" id="bathrooms" class="form-select">
                                <option value="">Any</option>
                                <option value="1">1+ baths</option>
                                <option value="2">2+ baths</option>
                                <option value="3">3+ baths</option>
                                <option value="4">4+ baths</option>
                            </select>
                        </div>
                        <div class="col-12 d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-search me-2"></i>Search Properties
                            </button>
                        </div>
                        <div class="col-12 text-center">
                            <button type="button" class="btn btn-link text-primary" data-bs-toggle="collapse" data-bs-target="#advanced-filters">
                                <i class="fas fa-sliders-h me-1"></i>More Filters
                            </button>
                            <button type="button" class="btn btn-link text-success ms-2" onclick="searchByLocation()">
                                <i class="fas fa-location-arrow me-1"></i>Use My Location
                            </button>
                        </div>
                        <div class="collapse" id="advanced-filters">
                            <div class="row g-3 mt-1">
                                <div class="col-md-6">
                                    <label for="min_area" class="form-label">Min Area (sqft)</label>
                                    <input type="number" name="min_area" class="form-control" id="min_area" placeholder="e.g. 1000">
                                </div>
                                <div class="col-md-6">
                                    <label for="max_area" class="form-label">Max Area (sqft)</label>
                                    <input type="number" name="max_area" class="form-control" id="max_area" placeholder="e.g. 5000">
                                </div>
                                <div class="col-md-6">
                                    <label for="year_built" class="form-label">Year Built (after)</label>
                                    <input type="number" name="year_built" class="form-control" id="year_built" placeholder="e.g. 2000" min="1900" max="<?= date('Y'); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label for="parking" class="form-label">Parking Spaces</label>
                                    <select name="parking" id="parking" class="form-select">
                                        <option value="">Any</option>
                                        <option value="1">1+ spaces</option>
                                        <option value="2">2+ spaces</option>
                                        <option value="3">3+ spaces</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Statistics Section -->
<section class="stats-section">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-3 col-md-6">
                <div class="stat-card">
                    <div class="stat-number"><?= (int)$stats['total_properties']; ?></div>
                    <div class="stat-label">Total Properties</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stat-card">
                    <div class="stat-number"><?= (int)$stats['available_properties']; ?></div>
                    <div class="stat-label">Available Now</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stat-card">
                    <div class="stat-number"><?= (int)$stats['sold_properties']; ?></div>
                    <div class="stat-label">Recently Sold</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stat-card">
                    <div class="stat-number">$<?= is_numeric($stats['avg_price']) ? number_format($stats['avg_price'], 0) : '0'; ?></div>
                    <div class="stat-label">Average Price</div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Featured Properties Section -->
<section class="featured-section">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="section-title mb-2">Featured Properties</h2>
                <p class="section-subtitle mb-0">Discover our handpicked selection of premium properties</p>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-primary" data-filter="all">All</button>
                <button class="btn btn-outline-primary" data-filter="house">Houses</button>
                <button class="btn btn-outline-primary" data-filter="apartment">Apartments</button>
                <button class="btn btn-outline-primary" data-filter="condo">Condos</button>
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
                        <div class="property-card">
                            <div class="property-image" style="background-image: url('<?= htmlspecialchars($img); ?>')">
                                <span class="property-badge"><?= htmlspecialchars($property['property_type']); ?></span>
                            </div>
                            <div class="property-content">
                                <h5 class="property-title"><?= htmlspecialchars($property['propertiesname']); ?></h5>
                                <p class="property-location">
                                    <i class="fas fa-map-marker-alt me-1"></i>
                                    <?= htmlspecialchars($property['city'] . ', ' . $property['country']); ?>
                                </p>
                                <div class="property-price">
                                    <?= htmlspecialchars($property['currency']); ?> <?= $price; ?>
                                    <small class="text-muted d-block"><?= htmlspecialchars($property['price_type']); ?></small>
                                </div>
                                <div class="property-features">
                                    <span><i class="fas fa-bed me-1"></i><?= $property['bedrooms']; ?> beds</span>
                                    <span><i class="fas fa-bath me-1"></i><?= $property['bathrooms']; ?> baths</span>
                                    <span><i class="fas fa-ruler-combined me-1"></i><?= number_format($property['area_sqft']); ?> sqft</span>
                                </div>
                                <a href="property_details.php?id=<?= $property['id']; ?>" class="btn btn-primary w-100">
                                    <i class="fas fa-eye me-2"></i>View Details
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="text-center mt-5">
                <a href="property.php" class="btn btn-primary btn-lg">
                    <i class="fas fa-list me-2"></i>View All Properties
                </a>
            </div>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="fas fa-home fa-3x text-muted mb-3"></i>
                <h4>No Featured Properties Available</h4>
                <p class="text-muted">Check back soon for our latest featured listings.</p>
                <a href="property.php" class="btn btn-primary">Browse All Properties</a>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- About Section -->
<section id="about" class="py-5" style="background: var(--light-color);">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <h2 class="section-title text-start">Why Choose Us?</h2>
                <p class="section-subtitle text-start mb-4">We're committed to helping you find the perfect property</p>
                
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="d-flex align-items-start">
                            <div class="flex-shrink-0">
                                <div class="bg-primary text-white rounded-circle p-3">
                                    <i class="fas fa-search fa-lg"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h5>Expert Search</h5>
                                <p class="text-muted">Advanced filtering and search tools to find exactly what you're looking for.</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex align-items-start">
                            <div class="flex-shrink-0">
                                <div class="bg-success text-white rounded-circle p-3">
                                    <i class="fas fa-shield-alt fa-lg"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h5>Verified Listings</h5>
                                <p class="text-muted">All our properties are verified and regularly updated for accuracy.</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex align-items-start">
                            <div class="flex-shrink-0">
                                <div class="bg-warning text-white rounded-circle p-3">
                                    <i class="fas fa-headset fa-lg"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h5>24/7 Support</h5>
                                <p class="text-muted">Our dedicated team is here to help you every step of the way.</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex align-items-start">
                            <div class="flex-shrink-0">
                                <div class="bg-info text-white rounded-circle p-3">
                                    <i class="fas fa-chart-line fa-lg"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h5>Market Insights</h5>
                                <p class="text-muted">Get the latest market trends and property value insights.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="text-center">
                    <img src="https://images.unsplash.com/photo-1560518883-ce09059eeffa?w=600&h=400&fit=crop" 
                         alt="Real Estate Team" class="img-fluid rounded-3 shadow">
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Call to Action -->
<section class="py-5" style="background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <h2 class="text-white mb-3">Ready to Find Your Dream Home?</h2>
                <p class="text-white-50 lead mb-0">Join thousands of satisfied customers who found their perfect property with us.</p>
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

<?php include('./includes/footer.php'); ?>

<script>
// Location-based search for homepage
function searchByLocation() {
    if (navigator.geolocation) {
        // Show loading state
        const button = event.target.closest('button');
        const originalText = button.innerHTML;
        button.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Getting Location...';
        button.disabled = true;
        
        navigator.geolocation.getCurrentPosition(function(position) {
            const lat = position.coords.latitude;
            const lng = position.coords.longitude;
            
            // Use reverse geocoding to get address (using a free service)
            fetch(`https://api.bigdatacloud.net/data/reverse-geocode-client?latitude=${lat}&longitude=${lng}&localityLanguage=en`)
                .then(response => response.json())
                .then(data => {
                    // Fill in the location field
                    const locationInput = document.getElementById('location');
                    if (locationInput && data.city) {
                        locationInput.value = `${data.city}, ${data.countryName}`;
                        
                        // Show success message
                        showToast(`Location detected: ${data.city}, ${data.countryName}`, 'success');
                    }
                })
                .catch(error => {
                    console.error('Geocoding error:', error);
                    showToast('Location detected but unable to get address details.', 'warning');
                })
                .finally(() => {
                    // Restore button
                    button.innerHTML = originalText;
                    button.disabled = false;
                });
        }, function(error) {
            // Restore button
            button.innerHTML = originalText;
            button.disabled = false;
            
            let errorMessage = 'Unable to get your location. ';
            switch(error.code) {
                case error.PERMISSION_DENIED:
                    errorMessage += 'Location access denied by user.';
                    break;
                case error.POSITION_UNAVAILABLE:
                    errorMessage += 'Location information unavailable.';
                    break;
                case error.TIMEOUT:
                    errorMessage += 'Location request timed out.';
                    break;
                default:
                    errorMessage += 'Unknown error occurred.';
                    break;
            }
            showToast(errorMessage, 'warning');
        });
    } else {
        showToast('Geolocation is not supported by this browser.', 'warning');
    }
}

// Toast notification function
function showToast(message, type = 'info') {
    // Remove existing toasts
    const existingToasts = document.querySelectorAll('.toast-notification');
    existingToasts.forEach(toast => toast.remove());
    
    const toastTypes = {
        'success': 'alert-success',
        'error': 'alert-danger', 
        'warning': 'alert-warning',
        'info': 'alert-info'
    };
    
    const icons = {
        'success': 'fa-check-circle',
        'error': 'fa-exclamation-triangle',
        'warning': 'fa-exclamation-triangle', 
        'info': 'fa-info-circle'
    };
    
    const toastHtml = `
        <div class="toast-notification position-fixed" style="top: 20px; right: 20px; z-index: 9999; max-width: 350px;">
            <div class="alert ${toastTypes[type]} alert-dismissible fade show" role="alert">
                <i class="fas ${icons[type]} me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', toastHtml);
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        const toast = document.querySelector('.toast-notification');
        if (toast) {
            toast.style.transition = 'opacity 0.5s';
            toast.style.opacity = '0';
            setTimeout(() => toast.remove(), 500);
        }
    }, 5000);
}
</script>
