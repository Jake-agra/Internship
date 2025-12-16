<?php
session_start();

// Set secure session parameters
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Strict');
include('../Database/connection.php');
include('../includes/route.php');
include('../includes/security.php');

// Generate or validate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('CSRF token validation failed');
    }
}

// Check if user is admin
if (!isAdmin()) {
    header('Location: ../login.php');
    exit();
}

$message = '';
$success = false;

// Handle user actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $user_id = filter_var($_POST['user_id'], FILTER_VALIDATE_INT);
        $action = filter_var($_POST['action'], FILTER_SANITIZE_STRING);
        
        // Validate action type
        if (!$user_id || !in_array($action, ['activate', 'deactivate', 'change_role', 'delete'])) {
            die('Invalid action parameters');
        }
        
        switch ($action) {
            case 'activate':
                $stmt = $conn->prepare("UPDATE users SET is_active = 1 WHERE id = ?");
                $stmt->bind_param("i", $user_id);
                if ($stmt->execute()) {
                    $success = true;
                    $message = 'User activated successfully!';
                } else {
                    $message = 'Failed to activate user.';
                }
                $stmt->close();
                break;
                
            case 'deactivate':
                $stmt = $conn->prepare("UPDATE users SET is_active = 0 WHERE id = ?");
                $stmt->bind_param("i", $user_id);
                if ($stmt->execute()) {
                    $success = true;
                    $message = 'User deactivated successfully!';
                } else {
                    $message = 'Failed to deactivate user.';
                }
                $stmt->close();
                break;
                
            case 'change_role':
                $new_role = (int)$_POST['new_role'];
                $stmt = $conn->prepare("UPDATE users SET role_id = ? WHERE id = ?");
                $stmt->bind_param("ii", $new_role, $user_id);
                if ($stmt->execute()) {
                    $success = true;
                    $message = 'User role updated successfully!';
                } else {
                    $message = 'Failed to update user role.';
                }
                $stmt->close();
                break;
                
            case 'delete':
                // Only allow deletion if user has no associated properties or inquiries
                $check_stmt = $conn->prepare("SELECT COUNT(*) as count FROM properties WHERE user_id = ?");
                $check_stmt->bind_param("i", $user_id);
                $check_stmt->execute();
                $result = $check_stmt->get_result();
                $row = $result->fetch_assoc();
                
                if ($row['count'] > 0) {
                    $message = 'Cannot delete user: User has associated properties.';
                } else {
                    $delete_stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role_id != 1");
                    $delete_stmt->bind_param("i", $user_id);
                    if ($delete_stmt->execute() && $delete_stmt->affected_rows > 0) {
                        $success = true;
                        $message = 'User deleted successfully!';
                    } else {
                        $message = 'Failed to delete user or user is an admin.';
                    }
                    $delete_stmt->close();
                }
                $check_stmt->close();
                break;
        }
    }
}

// Get and sanitize filter parameters
$role_filter = isset($_GET['role']) ? filter_var($_GET['role'], FILTER_VALIDATE_INT) : 0;
$status_filter = isset($_GET['status']) ? filter_var($_GET['status'], FILTER_SANITIZE_STRING) : '';
$search = isset($_GET['search']) ? filter_var(trim($_GET['search']), FILTER_SANITIZE_STRING) : '';

// Validate status filter values
if ($status_filter && !in_array($status_filter, ['active', 'inactive'])) {
    $status_filter = '';
}
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 20;

// Build query
$where_conditions = [];
$params = [];
$types = '';

if ($role_filter > 0) {
    $where_conditions[] = "u.role_id = ?";
    $params[] = $role_filter;
    $types .= 'i';
}

if ($status_filter !== '') {
    $where_conditions[] = "u.is_active = ?";
    $params[] = $status_filter === 'active' ? 1 : 0;
    $types .= 'i';
}

if ($search !== '') {
    $where_conditions[] = "(u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ?)";
    $search_term = "%{$search}%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= 'sss';
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get total count
$count_query = "SELECT COUNT(*) as total FROM users u JOIN roles r ON u.role_id = r.id {$where_clause}";
$count_stmt = $conn->prepare($count_query);
if (!empty($params)) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$total_users = $count_stmt->get_result()->fetch_assoc()['total'];
$count_stmt->close();

// Get users
$offset = ($page - 1) * $per_page;
$users_query = "SELECT u.id, u.first_name, u.last_name, u.email, u.phone, u.is_active, u.created_at, r.role_name,
                COUNT(DISTINCT p.id) as property_count,
                COUNT(DISTINCT i.id) as inquiry_count
                FROM users u 
                JOIN roles r ON u.role_id = r.id 
                LEFT JOIN properties p ON u.id = p.user_id
                LEFT JOIN inquiries i ON u.id = i.client_id
                {$where_clause}
                GROUP BY u.id
                ORDER BY u.created_at DESC 
                LIMIT {$per_page} OFFSET {$offset}";

$users_stmt = $conn->prepare($users_query);
if (!empty($params)) {
    $users_stmt->bind_param($types, ...$params);
}
$users_stmt->execute();
$users = $users_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$users_stmt->close();

// Get roles for filter
$roles = [];
$roles_result = $conn->query("SELECT id, role_name FROM roles ORDER BY role_name");
while ($role = $roles_result->fetch_assoc()) {
    $roles[] = $role;
}

// Calculate pagination
$total_pages = ceil($total_users / $per_page);
?>

<?php
// Set security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - Admin Dashboard</title>
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

        .page-header {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }

        .table-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
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

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--primary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
        }

        /* Filter Section */
        .filter-section {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }

        .form-control, .form-select {
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            padding: 10px 12px;
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
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
                <a href="properties.php" class="nav-link">
                    <i class="fas fa-home"></i>
                    Properties
                </a>
            </li>
            <li class="nav-item">
                <a href="users.php" class="nav-link active">
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
            <h4 class="mb-0">User Management</h4>
            <p class="text-muted mb-0">Manage user accounts, roles, and permissions</p>
        </div>
        <div class="d-flex align-items-center">
            <span class="text-muted me-3"><?= date('F j, Y'); ?></span>
            <div class="dropdown">
                <button class="btn btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    <i class="fas fa-user me-2"></i>Admin
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="../profile.php"><i class="fas fa-user me-2"></i>Profile</a></li>
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

        <!-- Page Header -->
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h2 class="mb-2">User Management</h2>
                    <p class="text-muted mb-0">Total Users: <?= $total_users; ?></p>
                </div>
                <div class="col-md-4 text-md-end">
                    <a href="add_user.php" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Add New User
                    </a>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="filter-section">
            <h5 class="mb-3">Filter Users</h5>
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label for="role" class="form-label">Role</label>
                    <select name="role" id="role" class="form-select">
                        <option value="">All Roles</option>
                        <?php foreach ($roles as $role): ?>
                            <option value="<?= $role['id']; ?>" <?= $role_filter == $role['id'] ? 'selected' : ''; ?>>
                                <?= ucfirst($role['role_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="status" class="form-label">Status</label>
                    <select name="status" id="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="active" <?= $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?= $status_filter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="search" class="form-label">Search</label>
                    <input type="text" name="search" id="search" class="form-control" 
                           placeholder="Name or email..." value="<?= htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search me-2"></i>Filter
                    </button>
                </div>
            </form>
        </div>

        <!-- Users Table -->
        <div class="table-card">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Properties</th>
                            <th>Inquiries</th>
                            <th>Joined</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="user-avatar me-3">
                                            <?= strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)); ?>
                                        </div>
                                        <div>
                                            <div class="fw-bold"><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></div>
                                            <small class="text-muted"><?= htmlspecialchars($user['email']); ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-<?= $user['role_name'] === 'admin' ? 'danger' : ($user['role_name'] === 'agent' ? 'warning' : 'info'); ?>">
                                        <?= ucfirst($user['role_name']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-<?= $user['is_active'] ? 'success' : 'secondary'; ?>">
                                        <?= $user['is_active'] ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </td>
                                <td><?= $user['property_count']; ?></td>
                                <td><?= $user['inquiry_count']; ?></td>
                                <td><?= date('M j, Y', strtotime($user['created_at'])); ?></td>
                                <td>
                                    <div class="dropdown">
                                        <button class="btn btn-outline-primary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                            Actions
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li><a class="dropdown-item" href="edit_user.php?id=<?= $user['id']; ?>">
                                                <i class="fas fa-edit me-2"></i>Edit
                                            </a></li>
                                            <?php if ($user['is_active']): ?>
                                                <li><a class="dropdown-item" href="#" onclick="confirmAction(<?= $user['id']; ?>, 'deactivate')">
                                                    <i class="fas fa-ban me-2"></i>Deactivate
                                                </a></li>
                                            <?php else: ?>
                                                <li><a class="dropdown-item" href="#" onclick="confirmAction(<?= $user['id']; ?>, 'activate')">
                                                    <i class="fas fa-check me-2"></i>Activate
                                                </a></li>
                                            <?php endif; ?>
                                            <?php if ($user['role_name'] !== 'admin'): ?>
                                                <li><hr class="dropdown-divider"></li>
                                                <li><a class="dropdown-item text-danger" href="#" onclick="confirmAction(<?= $user['id']; ?>, 'delete')">
                                                    <i class="fas fa-trash me-2"></i>Delete
                                                </a></li>
                                            <?php endif; ?>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <nav aria-label="User pagination" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">
                                    Previous
                                </a>
                            </li>
                        <?php endif; ?>

                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                            <li class="page-item <?= $i == $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])); ?>">
                                    <?= $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>

                        <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">
                                    Next
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Hidden form for actions -->
<form id="actionForm" method="POST" style="display: none;">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']); ?>">
    <input type="hidden" name="user_id" id="actionUserId">
    <input type="hidden" name="action" id="actionType">
</form>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function confirmAction(userId, action) {
        let message = '';
        
        switch (action) {
            case 'activate':
                message = 'Are you sure you want to activate this user?';
                break;
            case 'deactivate':
                message = 'Are you sure you want to deactivate this user?';
                break;
            case 'delete':
                message = 'Are you sure you want to delete this user? This action cannot be undone.';
                break;
        }
        
        if (confirm(message)) {
            document.getElementById('actionUserId').value = userId;
            document.getElementById('actionType').value = action;
            document.getElementById('actionForm').submit();
        }
    }
</script>
</body>
</html>