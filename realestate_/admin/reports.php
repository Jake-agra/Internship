<?php
session_start();
include('../Database/connection.php');
include('../includes/route.php');
include('../includes/security.php');

// Check if user is admin
if (!isAdmin()) {
    header('Location: ../login.php');
    exit();
}

// Get time range
$time_range = $_GET['time_range'] ?? 'month';
$valid_ranges = ['week', 'month', 'quarter', 'year'];
if (!in_array($time_range, $valid_ranges)) {
    $time_range = 'month';
}

// Calculate date range
$date_from = match($time_range) {
    'week' => date('Y-m-d', strtotime('-7 days')),
    'month' => date('Y-m-d', strtotime('-30 days')),
    'quarter' => date('Y-m-d', strtotime('-90 days')),
    'year' => date('Y-m-d', strtotime('-365 days')),
};
$date_to = date('Y-m-d');

// 1. Get total statistics
$stats_query = "
    SELECT 
        (SELECT COUNT(*) FROM properties) as total_properties,
        (SELECT COUNT(*) FROM properties WHERE is_active = 1) as active_properties,
        (SELECT COUNT(*) FROM properties WHERE featured = 1) as featured_properties,
        (SELECT COUNT(*) FROM users WHERE role = 'agent') as total_agents,
        (SELECT COUNT(*) FROM users WHERE role = 'client') as total_clients,
        (SELECT COUNT(*) FROM inquiries) as total_inquiries,
        (SELECT COUNT(*) FROM inquiries WHERE created_at >= ?) as inquiries_period,
        (SELECT COUNT(*) FROM bookmarks) as total_bookmarks,
        (SELECT AVG(rating) FROM property_reviews) as avg_rating
";
$stats_stmt = $conn->prepare($stats_query);
$stats_stmt->bind_param("s", $date_from);
$stats_stmt->execute();
$stats = $stats_stmt->get_result()->fetch_assoc();
$stats_stmt->close();

// 2. Get property statistics
$property_stats_query = "
    SELECT 
        pt.type_name,
        COUNT(p.id) as count,
        AVG(pr.price) as avg_price,
        COUNT(CASE WHEN p.featured = 1 THEN 1 END) as featured_count
    FROM properties p
    LEFT JOIN property_types pt ON p.property_type_id = pt.id
    LEFT JOIN prices pr ON p.id = pr.property_id
    GROUP BY pt.id, pt.type_name
    ORDER BY count DESC
    LIMIT 10
";
$property_stats_result = $conn->query($property_stats_query);
$property_stats = $property_stats_result ? $property_stats_result->fetch_all(MYSQLI_ASSOC) : [];

// 3. Get inquiry trend data
$inquiry_trend_query = "
    SELECT 
        DATE(created_at) as date,
        COUNT(*) as count
    FROM inquiries
    WHERE created_at >= ?
    GROUP BY DATE(created_at)
    ORDER BY date ASC
";
$inquiry_trend_stmt = $conn->prepare($inquiry_trend_query);
$inquiry_trend_stmt->bind_param("s", $date_from);
$inquiry_trend_stmt->execute();
$inquiry_trends = $inquiry_trend_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$inquiry_trend_stmt->close();

// 4. Get inquiry status breakdown
$inquiry_status_query = "
    SELECT 
        status,
        COUNT(*) as count
    FROM inquiries
    WHERE created_at >= ?
    GROUP BY status
";
$inquiry_status_stmt = $conn->prepare($inquiry_status_query);
$inquiry_status_stmt->bind_param("s", $date_from);
$inquiry_status_stmt->execute();
$inquiry_statuses = $inquiry_status_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$inquiry_status_stmt->close();

// 5. Get top properties by inquiries
$top_properties_query = "
    SELECT 
        p.title,
        p.id,
        COUNT(i.id) as inquiry_count,
        COUNT(b.id) as bookmark_count,
        pr.price
    FROM properties p
    LEFT JOIN inquiries i ON p.id = i.property_id
    LEFT JOIN bookmarks b ON p.id = b.property_id
    LEFT JOIN prices pr ON p.id = pr.property_id
    WHERE p.created_at >= ?
    GROUP BY p.id
    ORDER BY inquiry_count DESC
    LIMIT 10
";
$top_properties_stmt = $conn->prepare($top_properties_query);
$top_properties_stmt->bind_param("s", $date_from);
$top_properties_stmt->execute();
$top_properties = $top_properties_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$top_properties_stmt->close();

// 6. Get agent performance
$agent_performance_query = "
    SELECT 
        CONCAT(u.first_name, ' ', u.last_name) as agent_name,
        u.id as agent_id,
        COUNT(DISTINCT p.id) as properties_listed,
        COUNT(DISTINCT i.id) as inquiries_received,
        COUNT(DISTINCT b.id) as bookmarks_received,
        COUNT(CASE WHEN i.created_at >= ? THEN 1 END) as recent_inquiries
    FROM users u
    LEFT JOIN properties p ON u.id = p.agent_id
    LEFT JOIN inquiries i ON p.id = i.property_id
    LEFT JOIN bookmarks b ON p.id = b.property_id
    WHERE u.role = 'agent'
    GROUP BY u.id, u.first_name, u.last_name
    ORDER BY properties_listed DESC
    LIMIT 10
";
$agent_perf_stmt = $conn->prepare($agent_performance_query);
$agent_perf_stmt->bind_param("s", $date_from);
$agent_perf_stmt->execute();
$agent_performance = $agent_perf_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$agent_perf_stmt->close();

// 7. Get review statistics
$review_stats_query = "
    SELECT 
        ROUND(AVG(rating), 2) as avg_rating,
        MIN(rating) as min_rating,
        MAX(rating) as max_rating,
        COUNT(*) as total_reviews,
        COUNT(CASE WHEN is_approved = 1 THEN 1 END) as approved_reviews,
        COUNT(CASE WHEN created_at >= ? THEN 1 END) as recent_reviews
    FROM property_reviews
";
$review_stats_stmt = $conn->prepare($review_stats_query);
$review_stats_stmt->bind_param("s", $date_from);
$review_stats_stmt->execute();
$review_stats = $review_stats_stmt->get_result()->fetch_assoc();
$review_stats_stmt->close();

?>
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