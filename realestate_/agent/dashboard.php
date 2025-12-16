<?php
session_start();
include('../Database/connection.php');
include('../includes/route.php');
include('../includes/security.php');

// Check if user is logged in and is an agent
if (!isLoggedIn()) {
    header('Location: ../login.php');
    exit();
}

// Redirect non-agents to appropriate dashboard
$user_role = getUserRole();
if ($user_role === 'admin') {
    header('Location: ../admin/dashboard.php');
    exit();
} elseif ($user_role !== 'agent') {
    header('Location: ../client/dashboard.php');
    exit();
}

$user_id = getUserId();
$user_email = getUserEmail();

// Handle inquiry status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_inquiry_status'])) {
    $csrf = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!$csrf || !SecurityValidator::getInstance()->validateCSRFToken($csrf)) {
        $_SESSION['error_message'] = 'Invalid security token';
    } else {
        $inquiry_id = (int)$_POST['inquiry_id'];
        $new_status = $_POST['status'] ?? 'pending';
        
        // Validate status
        $allowed_statuses = ['pending', 'responded', 'rejected', 'closed'];
        if (!in_array($new_status, $allowed_statuses)) {
            $_SESSION['error_message'] = 'Invalid status';
        } else {
            // Update inquiry status
            $update_stmt = $conn->prepare("UPDATE inquiries SET status = ? WHERE id = ? AND property_id IN (SELECT id FROM properties WHERE user_id = ?)");
            $update_stmt->bind_param("sii", $new_status, $inquiry_id, $user_id);
            
            if ($update_stmt->execute()) {
                $_SESSION['success_message'] = 'Inquiry status updated successfully';
            } else {
                $_SESSION['error_message'] = 'Failed to update inquiry status';
            }
            $update_stmt->close();
        }
    }
    header('Location: dashboard.php');
    exit();
}

// Handle property feature toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_featured'])) {
    $csrf = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!$csrf || !SecurityValidator::getInstance()->validateCSRFToken($csrf)) {
        $_SESSION['error_message'] = 'Invalid security token';
    } else {
        $property_id = (int)$_POST['property_id'];
        
        // Toggle featured status
        $toggle_stmt = $conn->prepare("UPDATE properties SET is_featured = NOT is_featured WHERE id = ? AND user_id = ?");
        $toggle_stmt->bind_param("ii", $property_id, $user_id);
        
        if ($toggle_stmt->execute()) {
            $_SESSION['success_message'] = 'Property featured status updated';
        } else {
            $_SESSION['error_message'] = 'Failed to update property';
        }
        $toggle_stmt->close();
    }
    header('Location: dashboard.php');
    exit();
}

// Get agent information
$agent_info = null;
$stmt = $conn->prepare("SELECT u.*, r.role_name FROM users u JOIN roles r ON u.role_id = r.id WHERE u.id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $agent_info = $result->fetch_assoc();
}
$stmt->close();

// Get agent statistics
$stats = [
    'my_properties' => 0,
    'active_listings' => 0,
    'pending_inquiries' => 0,
    'total_views' => 0,
    'favorites_received' => 0,
    'this_month_inquiries' => 0
];

// Properties assigned to this agent (using user_id instead of agent_id)
$my_props_result = $conn->query("SELECT COUNT(*) as count FROM properties WHERE user_id = $user_id");
if ($my_props_result) {
    $stats['my_properties'] = $my_props_result->fetch_assoc()['count'];
}

// Active listings
$active_result = $conn->query("SELECT COUNT(*) as count FROM properties WHERE user_id = $user_id AND status = 'available'");
if ($active_result) {
    $stats['active_listings'] = $active_result->fetch_assoc()['count'];
}

// Pending inquiries for agent's properties
$pending_result = $conn->query("SELECT COUNT(*) as count FROM inquiries i JOIN properties p ON i.property_id = p.id WHERE p.user_id = $user_id AND i.status = 'pending'");
if ($pending_result) {
    $stats['pending_inquiries'] = $pending_result->fetch_assoc()['count'];
}

// Total property views (using property_views instead of property_visits)
$views_result = $conn->query("SELECT COUNT(*) as count FROM property_views pv JOIN properties p ON pv.property_id = p.id WHERE p.user_id = $user_id");
if ($views_result) {
    $stats['total_views'] = $views_result->fetch_assoc()['count'];
}

// Favorites received on agent's properties (using bookmarks instead of favorites)
$favorites_result = $conn->query("SELECT COUNT(*) as count FROM bookmarks f JOIN properties p ON f.property_id = p.id WHERE p.user_id = $user_id");
if ($favorites_result) {
    $stats['favorites_received'] = $favorites_result->fetch_assoc()['count'];
}

// This month inquiries
$this_month_result = $conn->query("SELECT COUNT(*) as count FROM inquiries i JOIN properties p ON i.property_id = p.id WHERE p.user_id = $user_id AND MONTH(i.created_at) = MONTH(CURRENT_DATE()) AND YEAR(i.created_at) = YEAR(CURRENT_DATE())");
if ($this_month_result) {
    $stats['this_month_inquiries'] = $this_month_result->fetch_assoc()['count'];
}

// Get recent properties
$recent_properties = [];
$props_query = "SELECT p.id, p.propertiesname, p.status, p.created_at, p.images, pr.amount, pr.currency, pt.type_name, l.city, l.region 
FROM properties p
JOIN prices pr ON p.price_id = pr.id
JOIN property_types pt ON p.property_type_id = pt.id
JOIN locations l ON p.location_id = l.id
WHERE p.user_id = ?
ORDER BY p.created_at DESC
LIMIT 6";

$stmt = $conn->prepare($props_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $recent_properties[] = $row;
}
$stmt->close();

// Get recent inquiries
$recent_inquiries = [];
$inquiries_query = "SELECT i.id, i.message, i.status, i.created_at, i.client_id,
p.propertiesname, p.id as property_id,
u.first_name, u.last_name, u.email, u.phone
FROM inquiries i
JOIN properties p ON i.property_id = p.id
LEFT JOIN users u ON i.client_id = u.id
WHERE p.user_id = ?
ORDER BY i.created_at DESC
LIMIT 10";

$stmt = $conn->prepare($inquiries_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $recent_inquiries[] = $row;
}
$stmt->close();

// Get monthly performance data
$monthly_data = [];
for ($i = 5; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $month_name = date('M', strtotime("-$i months"));
    
    $month_inquiries = $conn->query("SELECT COUNT(*) as count FROM inquiries i JOIN properties p ON i.property_id = p.id WHERE p.user_id = $user_id AND DATE_FORMAT(i.created_at, '%Y-%m') = '$month'")->fetch_assoc()['count'];
    $month_views = $conn->query("SELECT COUNT(*) as count FROM property_views pv JOIN properties p ON pv.property_id = p.id WHERE p.user_id = $user_id AND DATE_FORMAT(pv.viewed_at, '%Y-%m') = '$month'")->fetch_assoc()['count'];
    
    $monthly_data[] = [
        'month' => $month_name,
        'inquiries' => $month_inquiries,
        'views' => $month_views
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agent Dashboard - Real Estate</title>
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
            --agent-color: #7c3aed;
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
            background: linear-gradient(135deg, var(--agent-color), #8b5cf6);
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
            color: var(--agent-color);
            border-left-color: var(--agent-color);
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
            background: linear-gradient(135deg, var(--agent-color), #8b5cf6);
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
            text-align: center;
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
            height: 180px;
            background-size: cover;
            background-position: center;
            position: relative;
        }

        .status-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .status-available {
            background: var(--success-color);
            color: white;
        }

        .status-sold {
            background: var(--danger-color);
            color: white;
        }

        .status-pending {
            background: var(--warning-color);
            color: white;
        }

        .inquiry-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            margin-bottom: 1rem;
            border-left: 4px solid var(--agent-color);
        }

        .chart-container {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
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
                <i class="fas fa-user-tie"></i>
            </div>
            <h6 class="mb-0"><?= htmlspecialchars($agent_info['first_name'] . ' ' . $agent_info['last_name']); ?></h6>
            <small>Real Estate Agent</small>
        </div>
    </div>

    <nav class="sidebar-nav p-3">
        <a href="dashboard.php" class="nav-link active">
            <i class="fas fa-tachometer-alt"></i> Dashboard
        </a>
        <a href="properties.php" class="nav-link">
            <i class="fas fa-home"></i> My Properties
        </a>
        <a href="upload_property.php" class="nav-link">
            <i class="fas fa-plus"></i> Upload Property
        </a>
        <a href="inquiries.php" class="nav-link">
            <i class="fas fa-envelope"></i> Client Inquiries
            <?php if ($stats['pending_inquiries'] > 0): ?>
                <span class="badge bg-warning ms-auto"><?= $stats['pending_inquiries']; ?></span>
            <?php endif; ?>
        </a>
        <a href="../profile.php" class="nav-link">
            <i class="fas fa-user-cog"></i> Profile Settings
        </a>
        <hr class="my-3">
        <a href="../index.php" class="nav-link">
            <i class="fas fa-home"></i> Public Site
        </a>
        <a href="../property.php" class="nav-link">
            <i class="fas fa-search"></i> All Properties
        </a>
        <a href="../contact.php" class="nav-link">
            <i class="fas fa-phone"></i> Contact
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
                <h2>Welcome back, Agent <?= htmlspecialchars($agent_info['first_name']); ?>!</h2>
                <p class="mb-0">Manage your properties, track inquiries, and grow your real estate business.</p>
            </div>
            <div class="col-md-4 text-end">
                <a href="upload_property.php" class="btn btn-light btn-lg">
                    <i class="fas fa-plus me-2"></i>Add New Property
                </a>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row g-4 mb-4">
        <div class="col-lg-2 col-md-4 col-sm-6">
            <div class="stat-card">
                <div class="stat-number text-primary"><?= $stats['my_properties']; ?></div>
                <div class="text-muted">My Properties</div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6">
            <div class="stat-card">
                <div class="stat-number text-success"><?= $stats['active_listings']; ?></div>
                <div class="text-muted">Active Listings</div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6">
            <div class="stat-card">
                <div class="stat-number text-warning"><?= $stats['pending_inquiries']; ?></div>
                <div class="text-muted">Pending Inquiries</div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6">
            <div class="stat-card">
                <div class="stat-number text-info"><?= $stats['total_views']; ?></div>
                <div class="text-muted">Total Views</div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6">
            <div class="stat-card">
                <div class="stat-number text-danger"><?= $stats['favorites_received']; ?></div>
                <div class="text-muted">Favorites</div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6">
            <div class="stat-card">
                <div class="stat-number" style="color: var(--agent-color);"><?= $stats['this_month_inquiries']; ?></div>
                <div class="text-muted">This Month</div>
            </div>
        </div>
    </div>

    <!-- Recent Properties and Inquiries -->
    <div class="row g-4 mb-4">
        <!-- Recent Properties -->
        <div class="col-lg-8">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4><i class="fas fa-home me-2" style="color: var(--agent-color);"></i>Recent Properties</h4>
                <a href="properties.php" class="btn btn-outline-primary btn-sm">View All</a>
            </div>
            
            <?php if (!empty($recent_properties)): ?>
                <div class="row g-3">
                    <?php foreach (array_slice($recent_properties, 0, 6) as $property): ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="property-card">
                                <?php
                                $default_image = 'https://images.unsplash.com/photo-1564013799919-ab600027ffc6?w=300&h=180&fit=crop';
                                $property_image = !empty($property['images']) ? $property['images'] : $default_image;
                                ?>
                                <div class="property-image" style="background-image: url('<?= htmlspecialchars($property_image); ?>')">
                                    <span class="status-badge status-<?= $property['status']; ?>">
                                        <?= ucfirst($property['status']); ?>
                                    </span>
                                </div>
                                <div class="p-3">
                                    <h6 class="mb-2"><?= htmlspecialchars($property['propertiesname']); ?></h6>
                                    <p class="text-muted mb-2">
                                        <i class="fas fa-map-marker-alt me-1"></i>
                                        <?= htmlspecialchars($property['city'] . ', ' . $property['region']); ?>
                                    </p>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <strong class="text-primary"><?= htmlspecialchars($property['currency']); ?> <?= number_format($property['amount']); ?></strong>
                                        <small class="text-muted"><?= date('M j', strtotime($property['created_at'])); ?></small>
                                    </div>
                                    <div class="mt-2">
                                        <a href="../property_details.php?id=<?= $property['id']; ?>" class="btn btn-sm btn-outline-primary me-2">View</a>
                                        <a href="edit_property.php?id=<?= $property['id']; ?>" class="btn btn-sm btn-outline-secondary">Edit</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-4">
                    <i class="fas fa-home fa-3x text-muted mb-3"></i>
                    <h5>No Properties Yet</h5>
                    <p class="text-muted">Start by adding your first property listing.</p>
                    <a href="upload_property.php" class="btn btn-primary">Add Property</a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Recent Inquiries -->
        <div class="col-lg-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4><i class="fas fa-envelope me-2 text-info"></i>Recent Inquiries</h4>
                <a href="inquiries.php" class="btn btn-outline-primary btn-sm">View All</a>
            </div>
            
            <div style="max-height: 500px; overflow-y: auto;">
                <?php if (!empty($recent_inquiries)): ?>
                    <?php foreach (array_slice($recent_inquiries, 0, 8) as $inquiry): ?>
                        <div class="inquiry-card">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <small class="fw-bold"><?= htmlspecialchars($inquiry['first_name'] . ' ' . $inquiry['last_name']); ?></small>
                                <span class="badge bg-<?= $inquiry['status'] === 'pending' ? 'warning' : ($inquiry['status'] === 'completed' ? 'success' : 'info'); ?>">
                                    <?= ucfirst($inquiry['status']); ?>
                                </span>
                            </div>
                            <h6 class="mb-1">Property Inquiry</h6>
                            <p class="text-muted small mb-2">
                                RE: <?= htmlspecialchars($inquiry['propertiesname']); ?>
                            </p>
                            <p class="mb-2"><?= htmlspecialchars(substr($inquiry['message'], 0, 80)); ?>...</p>
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-muted"><?= date('M j, g:i A', strtotime($inquiry['created_at'])); ?></small>
                                <a href="inquiries.php?view=<?= $inquiry['id']; ?>" class="btn btn-sm btn-outline-primary">View</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-envelope-open fa-3x text-muted mb-3"></i>
                        <h6>No Inquiries Yet</h6>
                        <p class="text-muted small">Inquiries from clients will appear here.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Performance Chart -->
    <div class="row">
        <div class="col-12">
            <div class="chart-container">
                <h5 class="mb-3"><i class="fas fa-chart-line me-2 text-success"></i>Performance Overview (Last 6 Months)</h5>
                <canvas id="performanceChart" height="100"></canvas>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row g-4 mt-2">
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="fas fa-plus fa-3x text-primary mb-3"></i>
                    <h6>Add New Property</h6>
                    <p class="text-muted small">List a new property for sale or rent</p>
                    <a href="upload_property.php" class="btn btn-primary btn-sm">Add Property</a>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="fas fa-chart-bar fa-3x text-success mb-3"></i>
                    <h6>View Analytics</h6>
                    <p class="text-muted small">Track performance and insights</p>
                    <a href="analytics.php" class="btn btn-success btn-sm">View Analytics</a>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="fas fa-users fa-3x text-info mb-3"></i>
                    <h6>Manage Clients</h6>
                    <p class="text-muted small">View and contact your clients</p>
                    <a href="clients.php" class="btn btn-info btn-sm">View Clients</a>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="fas fa-cog fa-3x text-secondary mb-3"></i>
                    <h6>Settings</h6>
                    <p class="text-muted small">Update your profile and preferences</p>
                    <a href="../profile.php" class="btn btn-secondary btn-sm">Settings</a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Performance Chart
const ctx = document.getElementById('performanceChart').getContext('2d');
const monthlyData = <?= json_encode($monthly_data); ?>;

new Chart(ctx, {
    type: 'line',
    data: {
        labels: monthlyData.map(item => item.month),
        datasets: [{
            label: 'Inquiries',
            data: monthlyData.map(item => item.inquiries),
            borderColor: '#7c3aed',
            backgroundColor: 'rgba(124, 58, 237, 0.1)',
            tension: 0.4
        }, {
            label: 'Property Views',
            data: monthlyData.map(item => item.views),
            borderColor: '#10b981',
            backgroundColor: 'rgba(16, 185, 129, 0.1)',
            tension: 0.4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true
            }
        },
        plugins: {
            legend: {
                position: 'top',
            }
        }
    }
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>