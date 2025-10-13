<?php
session_start();
include('../Database/connection.php');
include('../includes/route.php');

// Check if user is logged in and is a client
if (!isLoggedIn()) {
    header('Location: ../login.php');
    exit();
}

// Redirect non-clients to appropriate dashboard
$user_role = getUserRole();
if ($user_role === 'admin') {
    header('Location: ../admin/dashboard.php');
    exit();
} elseif ($user_role === 'agent') {
    header('Location: ../agent/dashboard.php');
    exit();
}

$user_id = getUserId();

// Search parameters
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$property_type = isset($_GET['type']) ? $_GET['type'] : '';
$min_price = isset($_GET['min_price']) ? (int)$_GET['min_price'] : 0;
$max_price = isset($_GET['max_price']) ? (int)$_GET['max_price'] : 0;
$location = isset($_GET['location']) ? trim($_GET['location']) : '';
$bedrooms = isset($_GET['bedrooms']) ? (int)$_GET['bedrooms'] : 0;
$bathrooms = isset($_GET['bathrooms']) ? (int)$_GET['bathrooms'] : 0;
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 12;
$offset = ($page - 1) * $per_page;

// Build search query
$where_conditions = ["p.status = 'available'"];
$params = [];
$types = "";

if (!empty($search_query)) {
    $where_conditions[] = "(p.propertiesname LIKE ? OR p.description LIKE ?)";
    $search_term = "%$search_query%";
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= "ss";
}

if (!empty($property_type)) {
    $where_conditions[] = "pt.type_name = ?";
    $params[] = $property_type;
    $types .= "s";
}

if ($min_price > 0) {
    $where_conditions[] = "pr.amount >= ?";
    $params[] = $min_price;
    $types .= "i";
}

if ($max_price > 0) {
    $where_conditions[] = "pr.amount <= ?";
    $params[] = $max_price;
    $types .= "i";
}

if (!empty($location)) {
    $where_conditions[] = "(l.city LIKE ? OR l.state LIKE ? OR l.address LIKE ?)";
    $location_term = "%$location%";
    $params[] = $location_term;
    $params[] = $location_term;
    $params[] = $location_term;
    $types .= "sss";
}

if ($bedrooms > 0) {
    $where_conditions[] = "p.bedrooms >= ?";
    $params[] = $bedrooms;
    $types .= "i";
}

if ($bathrooms > 0) {
    $where_conditions[] = "p.bathrooms >= ?";
    $params[] = $bathrooms;
    $types .= "i";
}

$where_clause = implode(' AND ', $where_conditions);

// Determine sort order
$order_by = "p.created_at DESC";
switch ($sort) {
    case 'price_low':
        $order_by = "pr.amount ASC";
        break;
    case 'price_high':
        $order_by = "pr.amount DESC";
        break;
    case 'newest':
        $order_by = "p.created_at DESC";
        break;
    case 'oldest':
        $order_by = "p.created_at ASC";
        break;
    case 'bedrooms':
        $order_by = "p.bedrooms DESC";
        break;
}

// Count total results
$count_query = "SELECT COUNT(*) as total
FROM properties p
JOIN prices pr ON p.price_id = pr.id
JOIN property_types pt ON p.property_type_id = pt.id
JOIN locations l ON p.location_id = l.id
WHERE $where_clause";

$count_stmt = $conn->prepare($count_query);
if (!empty($params)) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$total_results = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_results / $per_page);
$count_stmt->close();

// Get properties
$query = "SELECT p.id, p.propertiesname, p.description, p.status, p.bedrooms, p.bathrooms, p.area_sqft, p.images, p.is_featured,
pr.amount, pr.currency, pt.type_name, l.city, l.state, l.address, l.latitude, l.longitude,
(SELECT COUNT(*) FROM favorites WHERE property_id = p.id AND user_id = ?) as is_favorited
FROM properties p
JOIN prices pr ON p.price_id = pr.id
JOIN property_types pt ON p.property_type_id = pt.id
JOIN locations l ON p.location_id = l.id
WHERE $where_clause
ORDER BY p.is_featured DESC, $order_by
LIMIT ? OFFSET ?";

$final_params = [$user_id, ...$params, $per_page, $offset];
$final_types = "i" . $types . "ii";

$stmt = $conn->prepare($query);
$stmt->bind_param($final_types, ...$final_params);
$stmt->execute();
$properties = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get property types for filter
$types_result = $conn->query("SELECT DISTINCT type_name FROM property_types ORDER BY type_name");
$property_types = $types_result->fetch_all(MYSQLI_ASSOC);

// Get locations for filter
$locations_result = $conn->query("SELECT DISTINCT city, state FROM locations ORDER BY city");
$locations = $locations_result->fetch_all(MYSQLI_ASSOC);

// Save search if user performed a search
if (!empty($search_query) || !empty($property_type) || $min_price > 0 || $max_price > 0 || !empty($location)) {
    $search_data = json_encode([
        'search_query' => $search_query,
        'property_type' => $property_type,
        'min_price' => $min_price,
        'max_price' => $max_price,
        'location' => $location,
        'bedrooms' => $bedrooms,
        'bathrooms' => $bathrooms
    ]);
    
    $save_search = $conn->prepare("INSERT INTO saved_searches (user_id, search_name, search_criteria, is_active) VALUES (?, ?, ?, 1) ON DUPLICATE KEY UPDATE search_criteria = ?, updated_at = NOW()");
    $search_name = "Search: " . ($search_query ?: $property_type ?: $location ?: 'Properties');
    $save_search->bind_param("isss", $user_id, $search_name, $search_data, $search_data);
    $save_search->execute();
    $save_search->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Properties - Real Estate</title>
    <link rel="stylesheet" href="../bootstrap-5.3.7-dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2563eb;
            --secondary-color: #1e40af;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --dark-color: #1f2937;
            --light-color: #f8fafc;
            --text-muted: #6b7280;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--light-color);
            color: var(--dark-color);
        }

        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: 260px;
            background: white;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            transition: all 0.3s ease;
            overflow-y: auto;
        }

        .sidebar-header {
            padding: 1.5rem;
            border-bottom: 1px solid #e5e7eb;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
        }

        .nav-link {
            display: flex;
            align-items: center;
            padding: 0.75rem 1.5rem;
            color: var(--text-muted);
            text-decoration: none;
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
        }

        .nav-link:hover,
        .nav-link.active {
            background: var(--light-color);
            color: var(--primary-color);
            border-left-color: var(--primary-color);
        }

        .nav-link i {
            width: 20px;
            margin-right: 0.75rem;
        }

        .main-content {
            margin-left: 260px;
            padding: 2rem;
            min-height: 100vh;
        }

        .search-filters {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            margin-bottom: 2rem;
        }

        .property-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s ease;
            height: 100%;
            position: relative;
        }

        .property-card:hover {
            transform: translateY(-5px);
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
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .featured-badge {
            position: absolute;
            top: 15px;
            left: 15px;
            background: var(--warning-color);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .favorite-btn {
            position: absolute;
            bottom: 15px;
            right: 15px;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: none;
            background: rgba(255, 255, 255, 0.9);
            color: var(--text-muted);
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .favorite-btn:hover,
        .favorite-btn.favorited {
            background: var(--danger-color);
            color: white;
        }

        .property-content {
            padding: 1.5rem;
        }

        .property-price {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }

        .property-features {
            display: flex;
            gap: 1rem;
            margin: 1rem 0;
            color: var(--text-muted);
        }

        .feature-item {
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .pagination-wrapper {
            margin-top: 3rem;
        }

        .results-summary {
            background: white;
            border-radius: 10px;
            padding: 1rem 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            .main-content {
                margin-left: 0;
                padding: 1rem;
            }
        }
    </style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
    <div class="sidebar-header">
        <div class="text-center">
            <h5 class="mb-0">Browse Properties</h5>
            <small>Find Your Dream Home</small>
        </div>
    </div>

    <nav class="sidebar-nav p-3">
        <a href="dashboard.php" class="nav-link">
            <i class="fas fa-tachometer-alt"></i> Dashboard
        </a>
        <a href="browse_properties.php" class="nav-link active">
            <i class="fas fa-search"></i> Browse Properties
        </a>
        <a href="favorites.php" class="nav-link">
            <i class="fas fa-heart"></i> My Favorites
        </a>
        <a href="saved_searches.php" class="nav-link">
            <i class="fas fa-bookmark"></i> Saved Searches
        </a>
        <a href="inquiries.php" class="nav-link">
            <i class="fas fa-envelope"></i> My Inquiries
        </a>
        <a href="../profile.php" class="nav-link">
            <i class="fas fa-user-cog"></i> Profile Settings
        </a>
        <hr class="my-3">
        <a href="../index.php" class="nav-link">
            <i class="fas fa-home"></i> Home
        </a>
        <a href="../contact.php" class="nav-link">
            <i class="fas fa-phone"></i> Contact Us
        </a>
        <a href="../logout.php" class="nav-link">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </nav>
</div>

<!-- Main Content -->
<div class="main-content">
    <!-- Search Filters -->
    <div class="search-filters">
        <form method="GET" id="searchForm">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Search Keywords</label>
                    <input type="text" class="form-control" name="search" value="<?= htmlspecialchars($search_query); ?>" placeholder="Property name, description...">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Property Type</label>
                    <select class="form-select" name="type">
                        <option value="">All Types</option>
                        <?php foreach ($property_types as $type): ?>
                            <option value="<?= htmlspecialchars($type['type_name']); ?>" <?= $property_type === $type['type_name'] ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($type['type_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Location</label>
                    <input type="text" class="form-control" name="location" value="<?= htmlspecialchars($location); ?>" placeholder="City, State, Address">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Min Price</label>
                    <input type="number" class="form-control" name="min_price" value="<?= $min_price > 0 ? $min_price : ''; ?>" placeholder="0">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Max Price</label>
                    <input type="number" class="form-control" name="max_price" value="<?= $max_price > 0 ? $max_price : ''; ?>" placeholder="No limit">
                </div>
                <div class="col-md-1">
                    <label class="form-label">Beds</label>
                    <select class="form-select" name="bedrooms">
                        <option value="0">Any</option>
                        <?php for ($i = 1; $i <= 6; $i++): ?>
                            <option value="<?= $i; ?>" <?= $bedrooms === $i ? 'selected' : ''; ?>><?= $i; ?>+</option>
                        <?php endfor; ?>
                    </select>
                </div>
            </div>
            <div class="row g-3 mt-2">
                <div class="col-md-2">
                    <label class="form-label">Baths</label>
                    <select class="form-select" name="bathrooms">
                        <option value="0">Any</option>
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <option value="<?= $i; ?>" <?= $bathrooms === $i ? 'selected' : ''; ?>><?= $i; ?>+</option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Sort By</label>
                    <select class="form-select" name="sort">
                        <option value="newest" <?= $sort === 'newest' ? 'selected' : ''; ?>>Newest First</option>
                        <option value="oldest" <?= $sort === 'oldest' ? 'selected' : ''; ?>>Oldest First</option>
                        <option value="price_low" <?= $sort === 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                        <option value="price_high" <?= $sort === 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
                        <option value="bedrooms" <?= $sort === 'bedrooms' ? 'selected' : ''; ?>>Most Bedrooms</option>
                    </select>
                </div>
                <div class="col-md-8 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search me-2"></i>Search
                    </button>
                    <a href="browse_properties.php" class="btn btn-outline-secondary">
                        <i class="fas fa-times me-2"></i>Clear
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- Results Summary -->
    <div class="results-summary">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h6 class="mb-0">
                    <i class="fas fa-list me-2"></i>
                    <?= number_format($total_results); ?> properties found
                    <?php if (!empty($search_query)): ?>
                        for "<?= htmlspecialchars($search_query); ?>"
                    <?php endif; ?>
                </h6>
                <?php if ($page > 1 || $total_pages > 1): ?>
                    <small class="text-muted">
                        Showing <?= number_format($offset + 1); ?>-<?= number_format(min($offset + $per_page, $total_results)); ?> of <?= number_format($total_results); ?> results
                    </small>
                <?php endif; ?>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-sm btn-outline-primary" onclick="toggleView('grid')" id="gridViewBtn">
                    <i class="fas fa-th-large"></i>
                </button>
                <button class="btn btn-sm btn-outline-primary" onclick="toggleView('list')" id="listViewBtn">
                    <i class="fas fa-list"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Properties Grid -->
    <div id="propertiesContainer" class="row g-4 mb-4">
        <?php if (empty($properties)): ?>
            <div class="col-12">
                <div class="text-center py-5">
                    <i class="fas fa-search fa-4x text-muted mb-3"></i>
                    <h4>No Properties Found</h4>
                    <p class="text-muted">Try adjusting your search criteria or browse all properties.</p>
                    <a href="browse_properties.php" class="btn btn-primary">Browse All Properties</a>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($properties as $property): ?>
                <div class="col-lg-4 col-md-6 property-item">
                    <div class="property-card">
                        <?php
                        $default_image = 'https://images.unsplash.com/photo-1564013799919-ab600027ffc6?w=400&h=250&fit=crop';
                        $property_image = !empty($property['images']) ? $property['images'] : $default_image;
                        ?>
                        <div class="property-image" style="background-image: url('<?= htmlspecialchars($property_image); ?>')">
                            <span class="property-badge"><?= htmlspecialchars($property['type_name']); ?></span>
                            <?php if ($property['is_featured']): ?>
                                <span class="featured-badge">Featured</span>
                            <?php endif; ?>
                            <button class="favorite-btn <?= $property['is_favorited'] ? 'favorited' : ''; ?>" 
                                    onclick="toggleFavorite(<?= $property['id']; ?>, this)">
                                <i class="fas fa-heart"></i>
                            </button>
                        </div>
                        <div class="property-content">
                            <div class="property-price">
                                <?= htmlspecialchars($property['currency']); ?> <?= number_format($property['amount']); ?>
                            </div>
                            <h6 class="mb-2"><?= htmlspecialchars($property['propertiesname']); ?></h6>
                            <p class="text-muted mb-2">
                                <i class="fas fa-map-marker-alt me-1"></i>
                                <?= htmlspecialchars($property['address'] ?: ($property['city'] . ', ' . $property['state'])); ?>
                            </p>
                            <div class="property-features">
                                <div class="feature-item">
                                    <i class="fas fa-bed"></i>
                                    <span><?= $property['bedrooms']; ?></span>
                                </div>
                                <div class="feature-item">
                                    <i class="fas fa-bath"></i>
                                    <span><?= $property['bathrooms']; ?></span>
                                </div>
                                <div class="feature-item">
                                    <i class="fas fa-ruler-combined"></i>
                                    <span><?= number_format($property['area_sqft']); ?> sq ft</span>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mt-3">
                                <span class="badge bg-success"><?= ucfirst($property['status']); ?></span>
                                <a href="../property_details.php?id=<?= $property['id']; ?>" class="btn btn-primary btn-sm">
                                    View Details
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
        <div class="pagination-wrapper">
            <nav aria-label="Properties pagination">
                <ul class="pagination justify-content-center">
                    <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">
                                <i class="fas fa-chevron-left"></i> Previous
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php
                    $start_page = max(1, $page - 2);
                    $end_page = min($total_pages, $page + 2);
                    
                    if ($start_page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => 1])); ?>">1</a>
                        </li>
                        <?php if ($start_page > 2): ?>
                            <li class="page-item disabled"><span class="page-link">...</span></li>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                        <li class="page-item <?= $i === $page ? 'active' : ''; ?>">
                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])); ?>"><?= $i; ?></a>
                        </li>
                    <?php endfor; ?>

                    <?php if ($end_page < $total_pages): ?>
                        <?php if ($end_page < $total_pages - 1): ?>
                            <li class="page-item disabled"><span class="page-link">...</span></li>
                        <?php endif; ?>
                        <li class="page-item">
                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $total_pages])); ?>"><?= $total_pages; ?></a>
                        </li>
                    <?php endif; ?>

                    <?php if ($page < $total_pages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">
                                Next <i class="fas fa-chevron-right"></i>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    <?php endif; ?>
</div>

<!-- Favorites handling -->
<script>
function toggleFavorite(propertyId, button) {
    fetch('../bookmark_handler.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `property_id=${propertyId}&action=toggle`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            button.classList.toggle('favorited');
            // Update favorites count in sidebar if needed
        } else {
            alert('Error updating favorite status');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error updating favorite status');
    });
}

function toggleView(viewType) {
    const container = document.getElementById('propertiesContainer');
    const gridBtn = document.getElementById('gridViewBtn');
    const listBtn = document.getElementById('listViewBtn');
    
    if (viewType === 'list') {
        container.className = 'row g-2 mb-4';
        container.querySelectorAll('.property-item').forEach(item => {
            item.className = 'col-12 property-item';
        });
        gridBtn.classList.remove('btn-primary');
        gridBtn.classList.add('btn-outline-primary');
        listBtn.classList.remove('btn-outline-primary');
        listBtn.classList.add('btn-primary');
    } else {
        container.className = 'row g-4 mb-4';
        container.querySelectorAll('.property-item').forEach(item => {
            item.className = 'col-lg-4 col-md-6 property-item';
        });
        listBtn.classList.remove('btn-primary');
        listBtn.classList.add('btn-outline-primary');
        gridBtn.classList.remove('btn-outline-primary');
        gridBtn.classList.add('btn-primary');
    }
}

// Initialize grid view as active
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('gridViewBtn').classList.add('btn-primary');
    document.getElementById('gridViewBtn').classList.remove('btn-outline-primary');
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>