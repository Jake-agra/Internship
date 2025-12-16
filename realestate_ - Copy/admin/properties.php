<?php
session_start();
include('../Database/connection.php');
include('../includes/route.php');

// Check if user is admin
if (!isAdmin()) {
    header('Location: ../login.php');
    exit();
}

$message = '';
$success = false;

// Handle property deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_property'])) {
    $property_id = (int)$_POST['property_id'];
    
    // Delete property (cascade will handle related records)
    $delete_stmt = $conn->prepare("DELETE FROM properties WHERE id = ?");
    $delete_stmt->bind_param("i", $property_id);
    
    if ($delete_stmt->execute()) {
        $success = true;
        $message = 'Property deleted successfully!';
    } else {
        $message = 'Failed to delete property. Please try again.';
    }
    $delete_stmt->close();
}

// Handle property status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $property_id = (int)$_POST['property_id'];
    $new_status = $_POST['status'];
    
    $update_stmt = $conn->prepare("UPDATE properties SET status = ? WHERE id = ?");
    $update_stmt->bind_param("si", $new_status, $property_id);
    
    if ($update_stmt->execute()) {
        $success = true;
        $message = 'Property status updated successfully!';
    } else {
        $message = 'Failed to update property status. Please try again.';
    }
    $update_stmt->close();
}

// Handle featured toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_featured'])) {
    $property_id = (int)$_POST['property_id'];
    $is_featured = (int)$_POST['is_featured'];
    
    $update_stmt = $conn->prepare("UPDATE properties SET is_featured = ? WHERE id = ?");
    $update_stmt->bind_param("ii", $is_featured, $property_id);
    
    if ($update_stmt->execute()) {
        $success = true;
        $message = 'Property featured status updated successfully!';
    } else {
        $message = 'Failed to update featured status. Please try again.';
    }
    $update_stmt->close();
}

// Get filter parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$type_filter = isset($_GET['type']) ? (int)$_GET['type'] : 0;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;

// Build query
$sql = "SELECT p.id, p.propertiesname, p.description, p.status, p.is_featured, p.views_count, p.created_at, p.updated_at,
               pr.amount as price, pr.currency, pr.price_type,
               pt.type_name as property_type,
               l.city, l.region, l.country,
               u.first_name as agent_first_name, u.last_name as agent_last_name
        FROM properties p
        JOIN prices pr ON p.price_id = pr.id
        JOIN property_types pt ON p.property_type_id = pt.id
        JOIN locations l ON p.location_id = l.id
        JOIN users u ON p.user_id = u.id
        WHERE 1=1";

$types = '';
$values = [];

// Add search filter
if ($search !== '') {
    $sql .= " AND (p.propertiesname LIKE ? OR p.description LIKE ? OR l.city LIKE ? OR l.region LIKE ?)";
    $like = "%{$search}%";
    for ($i = 0; $i < 4; $i++) $values[] = $like;
    $types .= str_repeat('s', 4);
}

// Add status filter
if ($status_filter !== '') {
    $sql .= " AND p.status = ?";
    $values[] = $status_filter;
    $types .= 's';
}

// Add type filter
if ($type_filter > 0) {
    $sql .= " AND p.property_type_id = ?";
    $values[] = $type_filter;
    $types .= 'i';
}

$sql .= " ORDER BY p.created_at DESC";

// Get total count
$count_sql = str_replace("SELECT p.id, p.propertiesname, p.description, p.status, p.is_featured, p.views_count, p.created_at, p.updated_at, pr.amount as price, pr.currency, pr.price_type, pt.type_name as property_type, l.city, l.region, l.country, u.first_name as agent_first_name, u.last_name as agent_last_name", "SELECT COUNT(*) as total", $sql);

$total_properties = 0;
if ($count_stmt = $conn->prepare($count_sql)) {
    if (!empty($values)) {
        array_unshift($values, $types);
        $refs = [];
        foreach ($values as $key => $val) $refs[$key] = &$values[$key];
        call_user_func_array([$count_stmt, 'bind_param'], $refs);
    }
    $count_stmt->execute();
    $count_result = $count_stmt->get_result();
    if ($count_result) {
        $total_properties = $count_result->fetch_assoc()['total'];
        $count_result->free();
    }
    $count_stmt->close();
}

// Add pagination
$offset = ($page - 1) * $per_page;
$sql .= " LIMIT $per_page OFFSET $offset";

// Execute main query
$properties = [];
if ($stmt = $conn->prepare($sql)) {
    if (!empty($values)) {
        array_unshift($values, $types);
        $refs = [];
        foreach ($values as $key => $val) $refs[$key] = &$values[$key];
        call_user_func_array([$stmt, 'bind_param'], $refs);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result) {
        $properties = $result->fetch_all(MYSQLI_ASSOC);
        $result->free();
    }
    $stmt->close();
}

// Get property types for filter
$property_types = [];
if ($type_result = $conn->query("SELECT id, type_name FROM property_types ORDER BY type_name")) {
    while ($r = $type_result->fetch_assoc()) {
        $property_types[] = $r;
    }
    $type_result->free();
}

// Calculate pagination
$total_pages = ceil($total_properties / $per_page);
$start_page = max(1, $page - 2);
$end_page = min($total_pages, $page + 2);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Properties - Admin Panel</title>
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

        /* Cards */
        .card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }

        .form-control, .form-select {
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            padding: 12px 16px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        /* Tables */
        .table {
            margin-bottom: 0;
        }

        .table th {
            border-top: none;
            font-weight: 600;
            color: var(--dark-color);
            background: var(--light-color);
        }

        .badge {
            font-size: 0.75rem;
            padding: 0.5rem 0.75rem;
        }

        /* Buttons */
        .btn-sm {
            padding: 0.375rem 0.75rem;
            font-size: 0.875rem;
        }

        /* Pagination */
        .pagination {
            justify-content: center;
            margin-top: 2rem;
        }

        .page-link {
            border: none;
            color: var(--primary-color);
            padding: 0.75rem 1rem;
            margin: 0 0.25rem;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .page-link:hover {
            background: var(--primary-color);
            color: white;
        }

        .page-item.active .page-link {
            background: var(--primary-color);
            border-color: var(--primary-color);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .main-content {
                margin-left: 0;
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
                <a href="dashboard.php" class="nav-link">
                    <i class="fas fa-tachometer-alt"></i>
                    Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a href="properties.php" class="nav-link active">
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
            <h4 class="mb-0">Manage Properties</h4>
            <p class="text-muted mb-0">Add, edit, and manage property listings</p>
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
        <?php if ($message): ?>
            <div class="alert <?= $success ? 'alert-success' : 'alert-danger'; ?> alert-dismissible fade show">
                <?= htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Quick Actions -->
        <div class="card">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Property Management</h5>
                <div>
                    <a href="image_manager.php" class="btn btn-outline-primary me-2">
                        <i class="fas fa-images me-2"></i>Image Manager
                    </a>
                    <a href="add_property.php" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Add New Property
                    </a>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="card">
            <h5 class="mb-3">Filter Properties</h5>
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label for="search" class="form-label">Search</label>
                    <input type="text" name="search" value="<?= htmlspecialchars($search); ?>" class="form-control" placeholder="Property name, description, location">
                </div>
                <div class="col-md-3">
                    <label for="status" class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All Statuses</option>
                        <option value="available" <?= $status_filter === 'available' ? 'selected' : ''; ?>>Available</option>
                        <option value="sold" <?= $status_filter === 'sold' ? 'selected' : ''; ?>>Sold</option>
                        <option value="rented" <?= $status_filter === 'rented' ? 'selected' : ''; ?>>Rented</option>
                        <option value="pending" <?= $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="inactive" <?= $status_filter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="type" class="form-label">Property Type</label>
                    <select name="type" class="form-select">
                        <option value="">All Types</option>
                        <?php foreach ($property_types as $type): ?>
                            <option value="<?= $type['id']; ?>" <?= $type_filter == $type['id'] ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($type['type_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 d-grid">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search me-2"></i>Filter
                    </button>
                </div>
            </form>
        </div>

        <!-- Properties Table -->
        <div class="card">
            <h5 class="mb-3">Properties (<?= $total_properties; ?> total)</h5>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Property</th>
                            <th>Type</th>
                            <th>Location</th>
                            <th>Price</th>
                            <th>Agent</th>
                            <th>Status</th>
                            <th>Featured</th>
                            <th>Views</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($properties as $property): ?>
                            <tr>
                                <td>
                                    <div>
                                        <strong><?= htmlspecialchars($property['propertiesname']); ?></strong>
                                        <br>
                                        <small class="text-muted"><?= htmlspecialchars(substr($property['description'], 0, 50)) . (strlen($property['description']) > 50 ? '...' : ''); ?></small>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-info"><?= htmlspecialchars($property['property_type']); ?></span>
                                </td>
                                <td>
                                    <?= htmlspecialchars($property['city'] . ', ' . $property['region']); ?>
                                </td>
                                <td>
                                    <strong><?= htmlspecialchars($property['currency']); ?> <?= number_format($property['price']); ?></strong>
                                    <br>
                                    <small class="text-muted"><?= htmlspecialchars($property['price_type']); ?></small>
                                </td>
                                <td>
                                    <?= htmlspecialchars($property['agent_first_name'] . ' ' . $property['agent_last_name']); ?>
                                </td>
                                <td>
                                    <span class="badge bg-<?= $property['status'] === 'available' ? 'success' : ($property['status'] === 'sold' ? 'danger' : 'warning'); ?>">
                                        <?= ucfirst($property['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="property_id" value="<?= $property['id']; ?>">
                                        <input type="hidden" name="is_featured" value="<?= $property['is_featured'] ? 0 : 1; ?>">
                                        <button type="submit" name="toggle_featured" class="btn btn-sm <?= $property['is_featured'] ? 'btn-warning' : 'btn-outline-warning'; ?>">
                                            <i class="fas fa-star"></i>
                                        </button>
                                    </form>
                                </td>
                                <td>
                                    <span class="badge bg-secondary"><?= $property['views_count']; ?></span>
                                </td>
                                <td>
                                    <?= date('M j, Y', strtotime($property['created_at'])); ?>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="../property_details.php?id=<?= $property['id']; ?>" class="btn btn-sm btn-outline-primary" target="_blank">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="edit_property.php?id=<?= $property['id']; ?>" class="btn btn-sm btn-outline-success">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button type="button" class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#statusModal<?= $property['id']; ?>">
                                            <i class="fas fa-cog"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?= $property['id']; ?>">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <!-- Status Update Modal -->
                            <div class="modal fade" id="statusModal<?= $property['id']; ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Update Property Status</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <form method="POST">
                                            <div class="modal-body">
                                                <input type="hidden" name="property_id" value="<?= $property['id']; ?>">
                                                <div class="mb-3">
                                                    <label for="status" class="form-label">Status</label>
                                                    <select name="status" class="form-select" required>
                                                        <option value="available" <?= $property['status'] === 'available' ? 'selected' : ''; ?>>Available</option>
                                                        <option value="sold" <?= $property['status'] === 'sold' ? 'selected' : ''; ?>>Sold</option>
                                                        <option value="rented" <?= $property['status'] === 'rented' ? 'selected' : ''; ?>>Rented</option>
                                                        <option value="pending" <?= $property['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                        <option value="inactive" <?= $property['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" name="update_status" class="btn btn-primary">Update Status</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <!-- Delete Confirmation Modal -->
                            <div class="modal fade" id="deleteModal<?= $property['id']; ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Delete Property</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <p>Are you sure you want to delete this property?</p>
                                            <p><strong><?= htmlspecialchars($property['propertiesname']); ?></strong></p>
                                            <p class="text-danger">This action cannot be undone!</p>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="property_id" value="<?= $property['id']; ?>">
                                                <button type="submit" name="delete_property" class="btn btn-danger">Delete Property</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <nav aria-label="Property pagination" class="mt-4">
                    <ul class="pagination">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">
                                    <i class="fas fa-chevron-left"></i> Previous
                                </a>
                            </li>
                        <?php endif; ?>

                        <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                            <li class="page-item <?= $i == $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])); ?>">
                                    <?= $i; ?>
                                </a>
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
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
