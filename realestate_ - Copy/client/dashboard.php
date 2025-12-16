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
$user_email = getUserEmail();

// Get user information
$user_info = null;
$stmt = $conn->prepare("SELECT u.*, r.role_name FROM users u JOIN roles r ON u.role_id = r.id WHERE u.id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $user_info = $result->fetch_assoc();
}
$stmt->close();

// Get user statistics with error handling
$stats = [
    'favorite_properties' => 0,
    'saved_searches' => 0,
    'inquiries_sent' => 0,
    'properties_viewed' => 0
];

// Favorite properties count (using bookmarks)
try {
    $fav_result = $conn->query("SELECT COUNT(*) as count FROM bookmarks WHERE user_id = $user_id");
    if ($fav_result) {
        $stats['favorite_properties'] = $fav_result->fetch_assoc()['count'];
    }
} catch (mysqli_sql_exception $e) {
    // Table doesn't exist - will be 0
    error_log("Bookmarks table error: " . $e->getMessage());
}

// Saved searches count
try {
    $search_result = $conn->query("SELECT COUNT(*) as count FROM saved_searches WHERE user_id = $user_id AND is_active = 1");
    if ($search_result) {
        $stats['saved_searches'] = $search_result->fetch_assoc()['count'];
    }
} catch (mysqli_sql_exception $e) {
    // Table doesn't exist - will be 0
    error_log("Saved searches table error: " . $e->getMessage());
}

// Inquiries sent count
try {
    $inquiry_result = $conn->query("SELECT COUNT(*) as count FROM inquiries WHERE client_id = $user_id");
    if ($inquiry_result) {
        $stats['inquiries_sent'] = $inquiry_result->fetch_assoc()['count'];
    }
} catch (mysqli_sql_exception $e) {
    // Table doesn't exist - will be 0
    error_log("Inquiries table error: " . $e->getMessage());
}

// Properties viewed count (unique)
try {
    $views_result = $conn->query("SELECT COUNT(DISTINCT property_id) as count FROM property_views WHERE user_id = $user_id");
    if ($views_result) {
        $stats['properties_viewed'] = $views_result->fetch_assoc()['count'];
    }
} catch (mysqli_sql_exception $e) {
    // Table doesn't exist - will be 0
    error_log("Property views table error: " . $e->getMessage());
}

// Get recent favorites with error handling
$recent_favorites = [];
try {
    $fav_query = "SELECT p.id, p.propertiesname, p.status, pr.amount, pr.currency, pt.type_name, l.city, l.region, p.images, b.created_at as favorited_at
    FROM bookmarks b
    JOIN properties p ON b.property_id = p.id
    JOIN prices pr ON p.price_id = pr.id
    JOIN property_types pt ON p.property_type_id = pt.id
    JOIN locations l ON p.location_id = l.id
    WHERE b.user_id = ?
    ORDER BY b.created_at DESC
    LIMIT 6";

    $stmt = $conn->prepare($fav_query);
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $recent_favorites[] = $row;
        }
        $stmt->close();
    }
} catch (mysqli_sql_exception $e) {
    // Bookmarks table doesn't exist
    error_log("Bookmarks query error: " . $e->getMessage());
}

// Get recent inquiries with error handling
$recent_inquiries = [];
try {
    $inquiry_query = "SELECT i.id, i.subject, i.message, i.status, i.created_at, p.propertiesname, p.id as property_id
    FROM inquiries i
    LEFT JOIN properties p ON i.property_id = p.id
    WHERE i.client_id = ?
    ORDER BY i.created_at DESC
    LIMIT 5";

    $stmt = $conn->prepare($inquiry_query);
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $recent_inquiries[] = $row;
        }
        $stmt->close();
    }
} catch (mysqli_sql_exception $e) {
    // Handle missing columns gracefully
    error_log("Inquiries query error: " . $e->getMessage());
    
    // Try a simpler query without subject column if it still doesn't exist
    try {
        $simple_query = "SELECT i.id, i.message, i.status, i.created_at, p.propertiesname, p.id as property_id
        FROM inquiries i
        LEFT JOIN properties p ON i.property_id = p.id
        WHERE i.client_id = ?
        ORDER BY i.created_at DESC
        LIMIT 5";
        
        $stmt = $conn->prepare($simple_query);
        if ($stmt) {
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $row['subject'] = 'General Inquiry'; // Default subject
                $recent_inquiries[] = $row;
            }
            $stmt->close();
        }
    } catch (mysqli_sql_exception $e2) {
        error_log("Fallback inquiries query also failed: " . $e2->getMessage());
    }
}

// Get recommended properties (based on user's favorites and searches)
$recommended_properties = [];
$rec_query = "SELECT DISTINCT p.id, p.propertiesname, p.status, pr.amount, pr.currency, pt.type_name, l.city, l.region, p.images, p.bedrooms, p.bathrooms, p.area_sqft
FROM properties p
JOIN prices pr ON p.price_id = pr.id
JOIN property_types pt ON p.property_type_id = pt.id
JOIN locations l ON p.location_id = l.id
WHERE p.status = 'available' 
AND p.id NOT IN (SELECT property_id FROM bookmarks WHERE user_id = ?)
ORDER BY p.is_featured DESC, p.created_at DESC
LIMIT 8";

$stmt = $conn->prepare($rec_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $recommended_properties[] = $row;
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Dashboard - Real Estate</title>
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
        }

        .sidebar-header {
            padding: 1.5rem;
            border-bottom: 1px solid #e5e7eb;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
        }

        .user-info {
            text-align: center;
        }

        .user-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 0.5rem;
            font-size: 1.5rem;
        }

        .sidebar-nav {
            padding: 1rem 0;
        }

        .nav-item {
            margin: 0.25rem 0;
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

        .welcome-card {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .property-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s ease;
            height: 100%;
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
            top: 10px;
            right: 10px;
            background: var(--primary-color);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .property-content {
            padding: 1.5rem;
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
        <div class="user-info">
            <div class="user-avatar">
                <i class="fas fa-user"></i>
            </div>
            <h6 class="mb-0"><?= htmlspecialchars($user_info['first_name'] . ' ' . $user_info['last_name']); ?></h6>
            <small>Client Account</small>
        </div>
    </div>

    <nav class="sidebar-nav">
        <a href="dashboard.php" class="nav-link active">
            <i class="fas fa-tachometer-alt"></i> Dashboard
        </a>
        <a href="browse_properties.php" class="nav-link">
            <i class="fas fa-search"></i> Browse Properties
        </a>
        <a href="favorites.php" class="nav-link">
            <i class="fas fa-heart"></i> My Favorites
            <span class="badge bg-primary ms-auto"><?= $stats['favorite_properties']; ?></span>
        </a>
        <a href="saved_searches.php" class="nav-link">
            <i class="fas fa-bookmark"></i> Saved Searches
            <span class="badge bg-secondary ms-auto"><?= $stats['saved_searches']; ?></span>
        </a>
        <a href="inquiries.php" class="nav-link">
            <i class="fas fa-envelope"></i> My Inquiries
            <span class="badge bg-info ms-auto"><?= $stats['inquiries_sent']; ?></span>
        </a>
        <a href="profile.php" class="nav-link">
            <i class="fas fa-user-cog"></i> Profile Settings
        </a>
        <hr class="my-3">
        <a href="../property.php" class="nav-link">
            <i class="fas fa-home"></i> View All Properties
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
    <!-- Welcome Section -->
    <div class="welcome-card">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h2>Welcome back, <?= htmlspecialchars($user_info['first_name']); ?>!</h2>
                <p class="mb-0">Ready to find your dream property? Explore our latest listings and manage your saved favorites.</p>
            </div>
            <div class="col-md-4 text-end">
                <a href="browse_properties.php" class="btn btn-light btn-lg">
                    <i class="fas fa-search me-2"></i>Browse Properties
                </a>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="stat-card text-center">
                <div class="stat-number text-primary"><?= $stats['favorite_properties']; ?></div>
                <div class="text-muted">Favorite Properties</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card text-center">
                <div class="stat-number text-success"><?= $stats['saved_searches']; ?></div>
                <div class="text-muted">Saved Searches</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card text-center">
                <div class="stat-number text-info"><?= $stats['inquiries_sent']; ?></div>
                <div class="text-muted">Inquiries Sent</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card text-center">
                <div class="stat-number text-warning"><?= $stats['properties_viewed']; ?></div>
                <div class="text-muted">Properties Viewed</div>
            </div>
        </div>
    </div>

    <!-- Recent Favorites -->
    <?php if (!empty($recent_favorites)): ?>
    <div class="mb-5">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4><i class="fas fa-heart me-2 text-danger"></i>Your Recent Favorites</h4>
            <a href="favorites.php" class="btn btn-outline-primary">View All</a>
        </div>
        <div class="row g-4">
            <?php foreach (array_slice($recent_favorites, 0, 3) as $property): ?>
                <div class="col-md-4">
                    <div class="property-card">
                        <?php
                        $default_image = 'https://images.unsplash.com/photo-1564013799919-ab600027ffc6?w=400&h=200&fit=crop';
                        $property_image = !empty($property['images']) ? $property['images'] : $default_image;
                        ?>
                        <div class="property-image" style="background-image: url('<?= htmlspecialchars($property_image); ?>')">
                            <span class="property-badge"><?= htmlspecialchars($property['type_name']); ?></span>
                        </div>
                        <div class="property-content">
                            <h6 class="mb-2"><?= htmlspecialchars($property['propertiesname']); ?></h6>
                            <p class="text-muted mb-2">
                                <i class="fas fa-map-marker-alt me-1"></i>
                                <?= htmlspecialchars($property['city'] . ', ' . $property['state']); ?>
                            </p>
                            <div class="d-flex justify-content-between align-items-center">
                                <strong class="text-primary"><?= htmlspecialchars($property['currency']); ?> <?= number_format($property['amount']); ?></strong>
                                <a href="../property_details.php?id=<?= $property['id']; ?>" class="btn btn-sm btn-primary">View</a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Recent Inquiries -->
    <?php if (!empty($recent_inquiries)): ?>
    <div class="mb-5">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4><i class="fas fa-envelope me-2 text-info"></i>Recent Inquiries</h4>
            <a href="inquiries.php" class="btn btn-outline-primary">View All</a>
        </div>
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Property</th>
                                        <th>Subject</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach (array_slice($recent_inquiries, 0, 5) as $inquiry): ?>
                                        <tr>
                                            <td>
                                                <?php if ($inquiry['propertiesname']): ?>
                                                    <a href="../property_details.php?id=<?= $inquiry['property_id']; ?>" class="text-decoration-none">
                                                        <?= htmlspecialchars($inquiry['propertiesname']); ?>
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-muted">General Inquiry</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= htmlspecialchars($inquiry['subject'] ?: 'Property Inquiry'); ?></td>
                                            <td>
                                                <span class="badge bg-<?= $inquiry['status'] === 'pending' ? 'warning' : ($inquiry['status'] === 'completed' ? 'success' : 'info'); ?>">
                                                    <?= ucfirst($inquiry['status']); ?>
                                                </span>
                                            </td>
                                            <td><?= date('M j, Y', strtotime($inquiry['created_at'])); ?></td>
                                            <td>
                                                <a href="inquiries.php?view=<?= $inquiry['id']; ?>" class="btn btn-sm btn-outline-primary">View</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Recommended Properties -->
    <?php if (!empty($recommended_properties)): ?>
    <div class="mb-5">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4><i class="fas fa-star me-2 text-warning"></i>Recommended for You</h4>
            <a href="../property.php" class="btn btn-outline-primary">Browse All</a>
        </div>
        <div class="row g-4">
            <?php foreach (array_slice($recommended_properties, 0, 4) as $property): ?>
                <div class="col-md-3">
                    <div class="property-card">
                        <?php
                        $default_image = 'https://images.unsplash.com/photo-1564013799919-ab600027ffc6?w=300&h=200&fit=crop';
                        $property_image = !empty($property['images']) ? $property['images'] : $default_image;
                        ?>
                        <div class="property-image" style="background-image: url('<?= htmlspecialchars($property_image); ?>')">
                            <span class="property-badge"><?= htmlspecialchars($property['type_name']); ?></span>
                        </div>
                        <div class="property-content">
                            <h6 class="mb-2"><?= htmlspecialchars($property['propertiesname']); ?></h6>
                            <p class="text-muted small mb-2">
                                <i class="fas fa-map-marker-alt me-1"></i>
                                <?= htmlspecialchars($property['city'] . ', ' . $property['state']); ?>
                            </p>
                            <div class="small text-muted mb-2">
                                <i class="fas fa-bed me-1"></i><?= $property['bedrooms']; ?> beds
                                <i class="fas fa-bath ms-2 me-1"></i><?= $property['bathrooms']; ?> baths
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <strong class="text-primary small"><?= htmlspecialchars($property['currency']); ?> <?= number_format($property['amount']); ?></strong>
                                <a href="../property_details.php?id=<?= $property['id']; ?>" class="btn btn-sm btn-primary">View</a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Quick Actions -->
    <div class="row g-4">
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="fas fa-search fa-3x text-primary mb-3"></i>
                    <h5>Start New Search</h5>
                    <p class="text-muted">Find properties that match your criteria with our advanced search filters.</p>
                    <a href="../property.php" class="btn btn-primary">Search Properties</a>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="fas fa-envelope fa-3x text-info mb-3"></i>
                    <h5>Contact Our Team</h5>
                    <p class="text-muted">Have questions? Our real estate experts are here to help you find the perfect property.</p>
                    <a href="../contact.php" class="btn btn-info">Contact Us</a>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>