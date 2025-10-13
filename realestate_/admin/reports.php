<?php
session_start();
include('../Database/connection.php');
include('../includes/route.php');

// Check if user is admin
if (!isAdmin()) {
    header('Location: ../login.php');
    exit();
}

// Date range for reports (default: last 30 days)
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Get comprehensive statistics
$stats = [];

// Property statistics
$property_stats = $conn->query("SELECT 
    COUNT(*) as total_properties,
    SUM(CASE WHEN status='available' THEN 1 ELSE 0 END) as available_properties,
    SUM(CASE WHEN status='sold' THEN 1 ELSE 0 END) as sold_properties,
    SUM(CASE WHEN status='rented' THEN 1 ELSE 0 END) as rented_properties,
    SUM(CASE WHEN is_featured=1 THEN 1 ELSE 0 END) as featured_properties
    FROM properties 
    WHERE created_at BETWEEN '$start_date' AND '$end_date 23:59:59'");

if ($property_stats) {
    $stats['properties'] = $property_stats->fetch_assoc();
    $property_stats->free();
}

// User statistics
$user_stats = $conn->query("SELECT 
    COUNT(*) as total_users,
    SUM(CASE WHEN r.role_name='agent' THEN 1 ELSE 0 END) as agents,
    SUM(CASE WHEN r.role_name='client' THEN 1 ELSE 0 END) as clients,
    SUM(CASE WHEN email_verified=1 THEN 1 ELSE 0 END) as verified_users
    FROM users u 
    JOIN roles r ON u.role_id = r.id 
    WHERE u.created_at BETWEEN '$start_date' AND '$end_date 23:59:59'");

if ($user_stats) {
    $stats['users'] = $user_stats->fetch_assoc();
    $user_stats->free();
}

// Inquiry statistics
$inquiry_stats = $conn->query("SELECT 
    COUNT(*) as total_inquiries,
    SUM(CASE WHEN status='pending' THEN 1 ELSE 0 END) as pending_inquiries,
    SUM(CASE WHEN status='responded' THEN 1 ELSE 0 END) as responded_inquiries
    FROM inquiries 
    WHERE created_at BETWEEN '$start_date' AND '$end_date 23:59:59'");

if ($inquiry_stats) {
    $stats['inquiries'] = $inquiry_stats->fetch_assoc();
    $inquiry_stats->free();
}

// Revenue statistics (based on sold properties)
$revenue_stats = $conn->query("SELECT 
    COUNT(*) as sales_count,
    SUM(pr.amount) as total_revenue,
    AVG(pr.amount) as avg_sale_price,
    MAX(pr.amount) as highest_sale,
    MIN(pr.amount) as lowest_sale
    FROM properties p
    JOIN prices pr ON p.price_id = pr.id
    WHERE p.status = 'sold' AND p.updated_at BETWEEN '$start_date' AND '$end_date 23:59:59'");

if ($revenue_stats) {
    $stats['revenue'] = $revenue_stats->fetch_assoc();
    $revenue_stats->free();
}

// Top performing agents
$top_agents = [];
$agent_query = "SELECT 
    u.first_name, u.last_name, u.email,
    COUNT(p.id) as properties_count,
    SUM(CASE WHEN p.status='sold' THEN 1 ELSE 0 END) as sales_count,
    AVG(pr.amount) as avg_property_value
    FROM users u
    JOIN roles r ON u.role_id = r.id
    LEFT JOIN properties p ON u.id = p.user_id
    LEFT JOIN prices pr ON p.price_id = pr.id
    WHERE r.role_name = 'agent' AND u.is_active = 1
    GROUP BY u.id
    ORDER BY sales_count DESC, properties_count DESC
    LIMIT 5";

if ($result = $conn->query($agent_query)) {
    $top_agents = $result->fetch_all(MYSQLI_ASSOC);
    $result->free();
}

// Popular property types
$property_types = [];
$type_query = "SELECT 
    pt.type_name,
    COUNT(p.id) as property_count,
    SUM(CASE WHEN p.status='sold' THEN 1 ELSE 0 END) as sold_count,
    AVG(pr.amount) as avg_price
    FROM property_types pt
    LEFT JOIN properties p ON pt.id = p.property_type_id
    LEFT JOIN prices pr ON p.price_id = pr.id
    WHERE p.created_at BETWEEN '$start_date' AND '$end_date 23:59:59'
    GROUP BY pt.id
    ORDER BY property_count DESC";

if ($result = $conn->query($type_query)) {
    $property_types = $result->fetch_all(MYSQLI_ASSOC);
    $result->free();
}

// Monthly trends (last 12 months)
$monthly_data = [];
$monthly_query = "SELECT 
    DATE_FORMAT(created_at, '%Y-%m') as month,
    COUNT(*) as properties_added,
    SUM(CASE WHEN status='sold' THEN 1 ELSE 0 END) as properties_sold
    FROM properties 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month DESC";

if ($result = $conn->query($monthly_query)) {
    $monthly_data = $result->fetch_all(MYSQLI_ASSOC);
    $result->free();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports & Analytics - Real Estate Admin</title>
    <link rel="stylesheet" href="../bootstrap-5.3.7-dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
            margin-left: 250px;
        }

        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: 250px;
            background: white;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
            z-index: 1000;
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

        .main-content {
            padding: 2rem;
        }

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

        .stat-header {
            display: flex;
            justify-content: between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            color: white;
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

        .chart-container {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }

        .table-container {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }

        .filter-form {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
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
                <a href="dashboard.php" class="nav-link">
                    <i class="fas fa-tachometer-alt"></i>Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a href="properties.php" class="nav-link">
                    <i class="fas fa-home"></i>Properties
                </a>
            </li>
            <li class="nav-item">
                <a href="users.php" class="nav-link">
                    <i class="fas fa-users"></i>Users
                </a>
            </li>
            <li class="nav-item">
                <a href="inquiries.php" class="nav-link">
                    <i class="fas fa-envelope"></i>Inquiries
                </a>
            </li>
            <li class="nav-item">
                <a href="reports.php" class="nav-link active">
                    <i class="fas fa-chart-bar"></i>Reports
                </a>
            </li>
            <li class="nav-item">
                <a href="settings.php" class="nav-link">
                    <i class="fas fa-cog"></i>Settings
                </a>
            </li>
            <li class="nav-item mt-3">
                <a href="../index.php" class="nav-link">
                    <i class="fas fa-external-link-alt"></i>View Website
                </a>
            </li>
            <li class="nav-item">
                <a href="../logout.php" class="nav-link text-danger">
                    <i class="fas fa-sign-out-alt"></i>Logout
                </a>
            </li>
        </ul>
    </div>
</nav>

<!-- Main Content -->
<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Reports & Analytics</h1>
            <p class="text-muted">Comprehensive insights into your real estate business</p>
        </div>
        <div>
            <button class="btn btn-primary" onclick="exportReport()">
                <i class="fas fa-download me-2"></i>Export Report
            </button>
        </div>
    </div>

    <!-- Date Range Filter -->
    <div class="filter-form">
        <h5 class="mb-3">Report Period</h5>
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <label for="start_date" class="form-label">Start Date</label>
                <input type="date" class="form-control" id="start_date" name="start_date" value="<?= $start_date; ?>">
            </div>
            <div class="col-md-4">
                <label for="end_date" class="form-label">End Date</label>
                <input type="date" class="form-control" id="end_date" name="end_date" value="<?= $end_date; ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">&nbsp;</label>
                <button type="submit" class="btn btn-primary d-block">
                    <i class="fas fa-filter me-2"></i>Apply Filter
                </button>
            </div>
        </form>
    </div>

    <!-- Statistics Grid -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-icon primary">
                    <i class="fas fa-home"></i>
                </div>
            </div>
            <div class="stat-number"><?= $stats['properties']['total_properties'] ?? 0; ?></div>
            <div class="stat-label">Total Properties</div>
            <small class="text-success">
                <i class="fas fa-check"></i> <?= $stats['properties']['available_properties'] ?? 0; ?> Available
            </small>
        </div>
        
        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-icon success">
                    <i class="fas fa-users"></i>
                </div>
            </div>
            <div class="stat-number"><?= $stats['users']['total_users'] ?? 0; ?></div>
            <div class="stat-label">New Users</div>
            <small class="text-info">
                <i class="fas fa-user-tie"></i> <?= $stats['users']['agents'] ?? 0; ?> Agents
            </small>
        </div>
        
        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-icon warning">
                    <i class="fas fa-envelope"></i>
                </div>
            </div>
            <div class="stat-number"><?= $stats['inquiries']['total_inquiries'] ?? 0; ?></div>
            <div class="stat-label">Total Inquiries</div>
            <small class="text-warning">
                <i class="fas fa-clock"></i> <?= $stats['inquiries']['pending_inquiries'] ?? 0; ?> Pending
            </small>
        </div>
        
        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-icon danger">
                    <i class="fas fa-dollar-sign"></i>
                </div>
            </div>
            <div class="stat-number">$<?= number_format($stats['revenue']['total_revenue'] ?? 0); ?></div>
            <div class="stat-label">Total Revenue</div>
            <small class="text-success">
                <i class="fas fa-chart-up"></i> <?= $stats['revenue']['sales_count'] ?? 0; ?> Sales
            </small>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row">
        <div class="col-md-8">
            <div class="chart-container">
                <h5 class="mb-3">Monthly Trends</h5>
                <canvas id="monthlyChart" height="100"></canvas>
            </div>
        </div>
        <div class="col-md-4">
            <div class="chart-container">
                <h5 class="mb-3">Property Types</h5>
                <canvas id="propertyTypeChart" height="200"></canvas>
            </div>
        </div>
    </div>

    <!-- Tables Row -->
    <div class="row">
        <div class="col-md-6">
            <div class="table-container">
                <h5 class="mb-3">Top Performing Agents</h5>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Agent</th>
                                <th>Properties</th>
                                <th>Sales</th>
                                <th>Avg Value</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($top_agents as $agent): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($agent['first_name'] . ' ' . $agent['last_name']); ?></strong><br>
                                        <small class="text-muted"><?= htmlspecialchars($agent['email']); ?></small>
                                    </td>
                                    <td><?= $agent['properties_count']; ?></td>
                                    <td><span class="badge bg-success"><?= $agent['sales_count']; ?></span></td>
                                    <td>$<?= number_format($agent['avg_property_value'] ?? 0); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="table-container">
                <h5 class="mb-3">Property Type Performance</h5>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Type</th>
                                <th>Listed</th>
                                <th>Sold</th>
                                <th>Avg Price</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($property_types as $type): ?>
                                <tr>
                                    <td><?= htmlspecialchars($type['type_name']); ?></td>
                                    <td><?= $type['property_count']; ?></td>
                                    <td><span class="badge bg-info"><?= $type['sold_count']; ?></span></td>
                                    <td>$<?= number_format($type['avg_price'] ?? 0); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Monthly Trends Chart
const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
const monthlyChart = new Chart(monthlyCtx, {
    type: 'line',
    data: {
        labels: <?= json_encode(array_column(array_reverse($monthly_data), 'month')); ?>,
        datasets: [{
            label: 'Properties Added',
            data: <?= json_encode(array_column(array_reverse($monthly_data), 'properties_added')); ?>,
            borderColor: '#2563eb',
            backgroundColor: 'rgba(37, 99, 235, 0.1)',
            tension: 0.4
        }, {
            label: 'Properties Sold',
            data: <?= json_encode(array_column(array_reverse($monthly_data), 'properties_sold')); ?>,
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
        }
    }
});

// Property Types Chart
const typeCtx = document.getElementById('propertyTypeChart').getContext('2d');
const propertyTypeChart = new Chart(typeCtx, {
    type: 'doughnut',
    data: {
        labels: <?= json_encode(array_column($property_types, 'type_name')); ?>,
        datasets: [{
            data: <?= json_encode(array_column($property_types, 'property_count')); ?>,
            backgroundColor: [
                '#2563eb',
                '#10b981',
                '#f59e0b',
                '#ef4444',
                '#8b5cf6',
                '#06b6d4'
            ]
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});

function exportReport() {
    // Simple export functionality
    const reportData = {
        period: '<?= $start_date; ?> to <?= $end_date; ?>',
        properties: <?= json_encode($stats['properties'] ?? []); ?>,
        users: <?= json_encode($stats['users'] ?? []); ?>,
        inquiries: <?= json_encode($stats['inquiries'] ?? []); ?>,
        revenue: <?= json_encode($stats['revenue'] ?? []); ?>
    };
    
    const dataStr = JSON.stringify(reportData, null, 2);
    const dataBlob = new Blob([dataStr], {type: 'application/json'});
    const url = URL.createObjectURL(dataBlob);
    const link = document.createElement('a');
    link.href = url;
    link.download = 'real_estate_report_<?= date("Y-m-d"); ?>.json';
    link.click();
    URL.revokeObjectURL(url);
}
</script>

</body>
</html>