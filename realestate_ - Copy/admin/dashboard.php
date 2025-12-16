<?php
session_start();
include('../Database/connection.php');
include('../includes/route.php');

// Check if user is admin
if (!isAdmin()) {
    header('Location: ../login.php');
    exit();
}

// Get statistics
$stats = [
    'total_properties' => 0,
    'available_properties' => 0,
    'sold_properties' => 0,
    'total_users' => 0,
    'total_agents' => 0,
    'total_inquiries' => 0,
    'pending_inquiries' => 0,
    'avg_price' => 0
];

// Get property statistics
$property_stats = $conn->query("SELECT 
    COUNT(*) as total_properties,
    SUM(CASE WHEN status='available' THEN 1 ELSE 0 END) as available_properties,
    SUM(CASE WHEN status='sold' THEN 1 ELSE 0 END) as sold_properties
    FROM properties");

if ($property_stats) {
    $stats = array_merge($stats, $property_stats->fetch_assoc());
    $property_stats->free();
}

// Get user statistics
$user_stats = $conn->query("SELECT 
    COUNT(*) as total_users,
    SUM(CASE WHEN r.role_name='agent' THEN 1 ELSE 0 END) as total_agents
    FROM users u 
    JOIN roles r ON u.role_id = r.id 
    WHERE u.is_active = 1");

if ($user_stats) {
    $user_data = $user_stats->fetch_assoc();
    $stats['total_users'] = $user_data['total_users'];
    $stats['total_agents'] = $user_data['total_agents'];
    $user_stats->free();
}

// Get inquiry statistics
$inquiry_stats = $conn->query("SELECT 
    COUNT(*) as total_inquiries,
    SUM(CASE WHEN status='pending' THEN 1 ELSE 0 END) as pending_inquiries
    FROM inquiries");

if ($inquiry_stats) {
    $inquiry_data = $inquiry_stats->fetch_assoc();
    $stats['total_inquiries'] = $inquiry_data['total_inquiries'];
    $stats['pending_inquiries'] = $inquiry_data['pending_inquiries'];
    $inquiry_stats->free();
}

// Get average price
$price_stats = $conn->query("SELECT AVG(pr.amount) as avg_price FROM properties p JOIN prices pr ON p.price_id = pr.id");
if ($price_stats) {
    $price_data = $price_stats->fetch_assoc();
    $stats['avg_price'] = $price_data['avg_price'] ? round($price_data['avg_price'], 2) : 0;
    $price_stats->free();
}

// Get recent properties
$recent_properties = [];
$recent_query = "SELECT p.id, p.propertiesname, p.status, pr.amount, pt.type_name, l.city, p.created_at
FROM properties p
JOIN prices pr ON p.price_id = pr.id
JOIN property_types pt ON p.property_type_id = pt.id
JOIN locations l ON p.location_id = l.id
ORDER BY p.created_at DESC
LIMIT 5";

if ($result = $conn->query($recent_query)) {
    $recent_properties = $result->fetch_all(MYSQLI_ASSOC);
    $result->free();
}

// Get recent inquiries
$recent_inquiries = [];
$inquiry_query = "SELECT i.id, i.message, i.status, i.created_at, p.propertiesname, u.first_name, u.last_name
FROM inquiries i
LEFT JOIN properties p ON i.property_id = p.id
LEFT JOIN users u ON i.client_id = u.id
ORDER BY i.created_at DESC
LIMIT 5";

if ($result = $conn->query($inquiry_query)) {
    $recent_inquiries = $result->fetch_all(MYSQLI_ASSOC);
    $result->free();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Real Estate</title>
    <link rel="stylesheet" href="../bootstrap-5.3.7-dist/css/bootstrap.min.css">
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

        /* Sidebar */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: 250px;
            background: white;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            transition: all 0.3s ease;
        }

        .sidebar-header {
            padding: 1.5rem;
            border-bottom: 1px solid #e5e7eb;
        }

        .sidebar-brand {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-color);
            text-decoration: none;
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
        }

        .nav-link:hover,
        .nav-link.active {
            background: var(--light-color);
            color: var(--primary-color);
        }

        .nav-link i {
            width: 20px;
            margin-right: 0.75rem;
        }

        /* Main Content */
        .main-content {
            margin-left: 250px;
            min-height: 100vh;
        }

        .top-navbar {
            background: white;
            padding: 1rem 2rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: between;
            align-items: center;
        }

        .content-area {
            padding: 2rem;
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
            margin-bottom: 1rem;
        }

        .stat-icon.primary { background: var(--primary-color); }
        .stat-icon.success { background: var(--success-color); }
        .stat-icon.warning { background: var(--warning-color); }
        .stat-icon.danger { background: var(--danger-color); }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--dark-color);
            margin-bottom: 0.25rem;
        }

        .stat-label {
            color: var(--text-muted);
            font-weight: 500;
        }

        .stat-change {
            font-size: 0.8rem;
            font-weight: 500;
            margin-top: 0.25rem;
        }

        /* Tables */
        .table-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }

        .table-card h5 {
            margin-bottom: 1rem;
            font-weight: 600;
        }

        .table {
            margin-bottom: 0;
        }

        .table th {
            border-top: none;
            font-weight: 600;
            color: var(--dark-color);
        }

        .badge {
            font-size: 0.75rem;
            padding: 0.5rem 0.75rem;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

<!-- Sidebar -->
<nav class="sidebar">
    <div class="sidebar-header">
        <a href="dashboard.php" class="sidebar-brand">
            <i class="fas fa-home me-2"></i>Real Estate Admin
        </a>
    </div>
    
    <div class="sidebar-nav">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a href="dashboard.php" class="nav-link active">
                    <i class="fas fa-tachometer-alt"></i>
                    Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a href="properties.php" class="nav-link">
                    <i class="fas fa-home"></i>
                    Properties
                </a>
            </li>
            <li class="nav-item">
                <a href="users.php" class="nav-link">
                    <i class="fas fa-users"></i>
                    Users
                </a>
            </li>
            <li class="nav-item">
                <a href="inquiries.php" class="nav-link">
                    <i class="fas fa-envelope"></i>
                    Inquiries
                </a>
            </li>
            <li class="nav-item">
                <a href="reports.php" class="nav-link">
                    <i class="fas fa-chart-bar"></i>
                    Reports
                </a>
            </li>
            <li class="nav-item">
                <a href="settings.php" class="nav-link">
                    <i class="fas fa-cog"></i>
                    Settings
                </a>
            </li>
            <li class="nav-item mt-3">
                <a href="../index.php" class="nav-link">
                    <i class="fas fa-external-link-alt"></i>
                    View Website
                </a>
            </li>
            <li class="nav-item">
                <a href="../logout.php" class="nav-link text-danger">
                    <i class="fas fa-sign-out-alt"></i>
                    Logout
                </a>
            </li>
        </ul>
    </div>
</nav>

<!-- Main Content -->
<div class="main-content">
    <!-- Top Navbar -->
    <div class="top-navbar">
        <div>
            <h4 class="mb-0">Dashboard</h4>
            <p class="text-muted mb-0">Welcome back, <?= htmlspecialchars($_SESSION['user_email']); ?>!</p>
        </div>
        <div class="d-flex align-items-center">
            <span class="text-muted me-3"><?= date('F j, Y'); ?></span>
            <div class="dropdown">
                <button class="btn btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    <i class="fas fa-user me-2"></i>Admin
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i>Profile</a></li>
                    <li><a class="dropdown-item" href="settings.php"><i class="fas fa-cog me-2"></i>Settings</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="../logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Content Area -->
    <div class="content-area">
        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon primary">
                    <i class="fas fa-home"></i>
                </div>
                <div class="stat-number"><?= $stats['total_properties']; ?></div>
                <div class="stat-label">Total Properties</div>
                <div class="stat-change text-success">+12% this month</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon success">
                    <i class="fas fa-key"></i>
                </div>
                <div class="stat-number"><?= $stats['available_properties']; ?></div>
                <div class="stat-label">Available Properties</div>
                <div class="stat-change text-success">+8% this month</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon warning">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-number"><?= $stats['total_users']; ?></div>
                <div class="stat-label">Total Users</div>
                <div class="stat-change text-warning">+5% this month</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon danger">
                    <i class="fas fa-envelope"></i>
                </div>
                <div class="stat-number"><?= $stats['pending_inquiries']; ?></div>
                <div class="stat-label">Pending Inquiries</div>
                <div class="stat-change text-info">Requires attention</div>
            </div>
        </div>

        <!-- Recent Properties -->
        <div class="table-card">
            <h5>Recent Properties</h5>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Property Name</th>
                            <th>Type</th>
                            <th>Location</th>
                            <th>Price</th>
                            <th>Status</th>
                            <th>Date Added</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_properties as $property): ?>
                            <tr>
                                <td><?= htmlspecialchars($property['propertiesname']); ?></td>
                                <td><?= htmlspecialchars($property['type_name']); ?></td>
                                <td><?= htmlspecialchars($property['city']); ?></td>
                                <td>$<?= number_format($property['amount']); ?></td>
                                <td>
                                    <span class="badge bg-<?= $property['status'] === 'available' ? 'success' : ($property['status'] === 'sold' ? 'danger' : 'warning'); ?>">
                                        <?= ucfirst($property['status']); ?>
                                    </span>
                                </td>
                                <td><?= date('M j, Y', strtotime($property['created_at'])); ?></td>
                                <td>
                                    <a href="edit_property.php?id=<?= $property['id']; ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Recent Inquiries -->
        <div class="table-card">
            <h5>Recent Inquiries</h5>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Property</th>
                            <th>Client</th>
                            <th>Message</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_inquiries as $inquiry): ?>
                            <tr>
                                <td><?= htmlspecialchars($inquiry['propertiesname'] ?: 'N/A'); ?></td>
                                <td><?= htmlspecialchars(($inquiry['first_name'] ?: 'Guest') . ' ' . ($inquiry['last_name'] ?: '')); ?></td>
                                <td><?= htmlspecialchars(substr($inquiry['message'], 0, 50)) . (strlen($inquiry['message']) > 50 ? '...' : ''); ?></td>
                                <td>
                                    <span class="badge bg-<?= $inquiry['status'] === 'pending' ? 'warning' : ($inquiry['status'] === 'responded' ? 'success' : 'secondary'); ?>">
                                        <?= ucfirst($inquiry['status']); ?>
                                    </span>
                                </td>
                                <td><?= date('M j, Y', strtotime($inquiry['created_at'])); ?></td>
                                <td>
                                    <a href="view_inquiry.php?id=<?= $inquiry['id']; ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="row">
            <div class="col-md-6">
                <div class="table-card">
                    <h5>Quick Actions</h5>
                    <div class="d-grid gap-2">
                        <a href="add_property.php" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Add New Property
                        </a>
                        <a href="users.php" class="btn btn-outline-primary">
                            <i class="fas fa-user-plus me-2"></i>Manage Users
                        </a>
                        <a href="inquiries.php" class="btn btn-outline-primary">
                            <i class="fas fa-envelope me-2"></i>View Inquiries
                        </a>
                        <a href="reports.php" class="btn btn-outline-primary">
                            <i class="fas fa-chart-bar me-2"></i>Generate Reports
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="table-card">
                    <h5>System Information</h5>
                    <div class="row g-3">
                        <div class="col-6">
                            <div class="text-center">
                                <div class="h4 text-primary"><?= $stats['total_agents']; ?></div>
                                <small class="text-muted">Active Agents</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="text-center">
                                <div class="h4 text-success">$<?= number_format($stats['avg_price']); ?></div>
                                <small class="text-muted">Average Price</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="text-center">
                                <div class="h4 text-warning"><?= $stats['total_inquiries']; ?></div>
                                <small class="text-muted">Total Inquiries</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="text-center">
                                <div class="h4 text-info"><?= $stats['sold_properties']; ?></div>
                                <small class="text-muted">Properties Sold</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
