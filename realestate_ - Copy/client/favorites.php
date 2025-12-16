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

// Handle remove favorite action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'remove') {
    $property_id = (int)$_POST['property_id'];
    $remove_stmt = $conn->prepare("DELETE FROM favorites WHERE user_id = ? AND property_id = ?");
    $remove_stmt->bind_param("ii", $user_id, $property_id);
    $remove_stmt->execute();
    $remove_stmt->close();
    
    header('Location: favorites.php?removed=1');
    exit();
}

// Get sorting and filtering parameters
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';
$filter_type = isset($_GET['type']) ? $_GET['type'] : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 12;
$offset = ($page - 1) * $per_page;

// Build query conditions
$where_conditions = ["f.user_id = ?"];
$params = [$user_id];
$types = "i";

if (!empty($filter_type)) {
    $where_conditions[] = "pt.type_name = ?";
    $params[] = $filter_type;
    $types .= "s";
}

$where_clause = implode(' AND ', $where_conditions);

// Determine sort order
$order_by = "f.created_at DESC";
switch ($sort) {
    case 'price_low':
        $order_by = "pr.amount ASC";
        break;
    case 'price_high':
        $order_by = "pr.amount DESC";
        break;
    case 'newest':
        $order_by = "f.created_at DESC";
        break;
    case 'oldest':
        $order_by = "f.created_at ASC";
        break;
    case 'property_name':
        $order_by = "p.propertiesname ASC";
        break;
}

// Count total favorites
$count_query = "SELECT COUNT(*) as total
FROM favorites f
JOIN properties p ON f.property_id = p.id
JOIN prices pr ON p.price_id = pr.id
JOIN property_types pt ON p.property_type_id = pt.id
JOIN locations l ON p.location_id = l.id
WHERE $where_clause";

$count_stmt = $conn->prepare($count_query);
$count_stmt->bind_param($types, ...$params);
$count_stmt->execute();
$total_results = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_results / $per_page);
$count_stmt->close();

// Get favorite properties
$query = "SELECT f.id as favorite_id, f.created_at as favorited_at,
p.id, p.propertiesname, p.description, p.status, p.bedrooms, p.bathrooms, p.area_sqft, p.images, p.is_featured,
pr.amount, pr.currency, pt.type_name, l.city, l.state, l.address
FROM favorites f
JOIN properties p ON f.property_id = p.id
JOIN prices pr ON p.price_id = pr.id
JOIN property_types pt ON p.property_type_id = pt.id
JOIN locations l ON p.location_id = l.id
WHERE $where_clause
ORDER BY $order_by
LIMIT ? OFFSET ?";

$final_params = [...$params, $per_page, $offset];
$final_types = $types . "ii";

$stmt = $conn->prepare($query);
$stmt->bind_param($final_types, ...$final_params);
$stmt->execute();
$favorites = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get property types for filter
$types_result = $conn->query("SELECT DISTINCT pt.type_name FROM favorites f JOIN properties p ON f.property_id = p.id JOIN property_types pt ON p.property_type_id = pt.id WHERE f.user_id = $user_id ORDER BY pt.type_name");
$property_types = $types_result->fetch_all(MYSQLI_ASSOC);

// Get user statistics
$stats_query = "SELECT 
    COUNT(*) as total_favorites,
    AVG(pr.amount) as avg_price,
    MIN(pr.amount) as min_price,
    MAX(pr.amount) as max_price
FROM favorites f
JOIN properties p ON f.property_id = p.id
JOIN prices pr ON p.price_id = pr.id
WHERE f.user_id = ?";

$stats_stmt = $conn->prepare($stats_query);
$stats_stmt->bind_param("i", $user_id);
$stats_stmt->execute();
$stats = $stats_stmt->get_result()->fetch_assoc();
$stats_stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Favorites - Real Estate</title>
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
            background: linear-gradient(135deg, var(--danger-color), #dc2626);
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

        .stats-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            text-align: center;
            transition: transform 0.3s ease;
        }

        .stats-card:hover {
            transform: translateY(-5px);
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .filters-section {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
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
            height: 200px;
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

        .favorited-date {
            position: absolute;
            bottom: 15px;
            left: 15px;
            background: rgba(0, 0, 0, 0.7);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.7rem;
        }

        .remove-favorite {
            position: absolute;
            bottom: 15px;
            right: 15px;
            width: 35px;
            height: 35px;
            border-radius: 50%;
            border: none;
            background: var(--danger-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .remove-favorite:hover {
            background: #dc2626;
            transform: scale(1.1);
        }

        .property-content {
            padding: 1.5rem;
        }

        .property-price {
            font-size: 1.4rem;
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

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }

        .empty-state i {
            font-size: 4rem;
            color: var(--text-muted);
            margin-bottom: 1.5rem;
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
            <i class="fas fa-heart fa-2x mb-2"></i>
            <h5 class="mb-0">My Favorites</h5>
            <small>Saved Properties</small>
        </div>
    </div>

    <nav class="sidebar-nav p-3">
        <a href="dashboard.php" class="nav-link">
            <i class="fas fa-tachometer-alt"></i> Dashboard
        </a>
        <a href="browse_properties.php" class="nav-link">
            <i class="fas fa-search"></i> Browse Properties
        </a>
        <a href="favorites.php" class="nav-link active">
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
    <!-- Success message -->
    <?php if (isset($_GET['removed'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            Property removed from favorites successfully!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2><i class="fas fa-heart me-3 text-danger"></i>My Favorite Properties</h2>
            <p class="text-muted mb-0">Properties you've saved for later viewing</p>
        </div>
        <a href="browse_properties.php" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>Find More Properties
        </a>
    </div>

    <!-- Statistics Cards -->
    <?php if ($total_results > 0): ?>
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stat-number text-danger"><?= $stats['total_favorites']; ?></div>
                    <div class="text-muted">Total Favorites</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stat-number text-success">$<?= number_format($stats['avg_price']); ?></div>
                    <div class="text-muted">Average Price</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stat-number text-info">$<?= number_format($stats['min_price']); ?></div>
                    <div class="text-muted">Lowest Price</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stat-number text-warning">$<?= number_format($stats['max_price']); ?></div>
                    <div class="text-muted">Highest Price</div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Filters and Sorting -->
    <?php if ($total_results > 0): ?>
        <div class="filters-section">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Filter by Type</label>
                    <select class="form-select" name="type">
                        <option value="">All Types</option>
                        <?php foreach ($property_types as $type): ?>
                            <option value="<?= htmlspecialchars($type['type_name']); ?>" <?= $filter_type === $type['type_name'] ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($type['type_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Sort By</label>
                    <select class="form-select" name="sort">
                        <option value="newest" <?= $sort === 'newest' ? 'selected' : ''; ?>>Recently Added</option>
                        <option value="oldest" <?= $sort === 'oldest' ? 'selected' : ''; ?>>Oldest First</option>
                        <option value="price_low" <?= $sort === 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                        <option value="price_high" <?= $sort === 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
                        <option value="property_name" <?= $sort === 'property_name' ? 'selected' : ''; ?>>Property Name</option>
                    </select>
                </div>
                <div class="col-md-6 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter me-2"></i>Apply Filters
                    </button>
                    <a href="favorites.php" class="btn btn-outline-secondary">
                        <i class="fas fa-times me-2"></i>Clear
                    </a>
                </div>
            </form>
        </div>

        <!-- Results Summary -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h6 class="mb-0">
                    <i class="fas fa-heart me-2"></i>
                    <?= number_format($total_results); ?> favorite properties
                </h6>
                <?php if ($page > 1 || $total_pages > 1): ?>
                    <small class="text-muted">
                        Showing <?= number_format($offset + 1); ?>-<?= number_format(min($offset + $per_page, $total_results)); ?> of <?= number_format($total_results); ?> favorites
                    </small>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Favorites Grid -->
    <?php if (empty($favorites)): ?>
        <div class="empty-state">
            <i class="fas fa-heart-broken"></i>
            <h4>No Favorite Properties Yet</h4>
            <p class="text-muted mb-4">Start browsing properties and save the ones you like by clicking the heart icon.</p>
            <a href="browse_properties.php" class="btn btn-primary btn-lg">
                <i class="fas fa-search me-2"></i>Browse Properties
            </a>
        </div>
    <?php else: ?>
        <div class="row g-4 mb-4">
            <?php foreach ($favorites as $property): ?>
                <div class="col-lg-4 col-md-6">
                    <div class="property-card">
                        <?php
                        $default_image = 'https://images.unsplash.com/photo-1564013799919-ab600027ffc6?w=400&h=200&fit=crop';
                        $property_image = !empty($property['images']) ? $property['images'] : $default_image;
                        ?>
                        <div class="property-image" style="background-image: url('<?= htmlspecialchars($property_image); ?>')">
                            <span class="property-badge"><?= htmlspecialchars($property['type_name']); ?></span>
                            <?php if ($property['is_featured']): ?>
                                <span class="featured-badge">Featured</span>
                            <?php endif; ?>
                            <div class="favorited-date">
                                <i class="fas fa-heart me-1"></i>
                                <?= date('M j, Y', strtotime($property['favorited_at'])); ?>
                            </div>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Remove this property from favorites?')">
                                <input type="hidden" name="action" value="remove">
                                <input type="hidden" name="property_id" value="<?= $property['id']; ?>">
                                <button type="submit" class="remove-favorite" title="Remove from favorites">
                                    <i class="fas fa-times"></i>
                                </button>
                            </form>
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
                                <div class="d-flex gap-2">
                                    <a href="../property_details.php?id=<?= $property['id']; ?>" class="btn btn-primary btn-sm">
                                        View Details
                                    </a>
                                    <a href="../contact.php?property=<?= $property['id']; ?>" class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-envelope"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="d-flex justify-content-center">
                <nav aria-label="Favorites pagination">
                    <ul class="pagination">
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
                        
                        for ($i = $start_page; $i <= $end_page; $i++): ?>
                            <li class="page-item <?= $i === $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])); ?>"><?= $i; ?></a>
                            </li>
                        <?php endfor; ?>

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
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>