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

// Handle actions
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'delete':
            $search_id = (int)$_POST['search_id'];
            $delete_stmt = $conn->prepare("DELETE FROM saved_searches WHERE id = ? AND user_id = ?");
            $delete_stmt->bind_param("ii", $search_id, $user_id);
            if ($delete_stmt->execute()) {
                $message = "Saved search deleted successfully!";
                $message_type = "success";
            } else {
                $message = "Error deleting saved search.";
                $message_type = "danger";
            }
            $delete_stmt->close();
            break;
            
        case 'toggle_active':
            $search_id = (int)$_POST['search_id'];
            $is_active = (int)$_POST['is_active'];
            $toggle_stmt = $conn->prepare("UPDATE saved_searches SET is_active = ? WHERE id = ? AND user_id = ?");
            $toggle_stmt->bind_param("iii", $is_active, $search_id, $user_id);
            if ($toggle_stmt->execute()) {
                $message = $is_active ? "Search alert activated!" : "Search alert deactivated!";
                $message_type = "success";
            } else {
                $message = "Error updating search alert.";
                $message_type = "danger";
            }
            $toggle_stmt->close();
            break;
            
        case 'save_new':
            $search_name = trim($_POST['search_name']);
            $search_criteria = json_encode($_POST['criteria']);
            
            if (!empty($search_name)) {
                $save_stmt = $conn->prepare("INSERT INTO saved_searches (user_id, search_name, search_criteria, is_active) VALUES (?, ?, ?, 1)");
                $save_stmt->bind_param("iss", $user_id, $search_name, $search_criteria);
                if ($save_stmt->execute()) {
                    $message = "New search saved successfully!";
                    $message_type = "success";
                } else {
                    $message = "Error saving search.";
                    $message_type = "danger";
                }
                $save_stmt->close();
            }
            break;
    }
}

// Get saved searches
$searches_query = "SELECT * FROM saved_searches WHERE user_id = ? ORDER BY is_active DESC, created_at DESC";
$searches_stmt = $conn->prepare($searches_query);
$searches_stmt->bind_param("i", $user_id);
$searches_stmt->execute();
$saved_searches = $searches_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$searches_stmt->close();

// Get statistics
$stats = [
    'total_searches' => count($saved_searches),
    'active_alerts' => 0,
    'inactive_searches' => 0
];

foreach ($saved_searches as $search) {
    if ($search['is_active']) {
        $stats['active_alerts']++;
    } else {
        $stats['inactive_searches']++;
    }
}

// Get property types for new search form
$types_result = $conn->query("SELECT DISTINCT type_name FROM property_types ORDER BY type_name");
$property_types = $types_result->fetch_all(MYSQLI_ASSOC);

// Get locations for new search form
$locations_result = $conn->query("SELECT DISTINCT city, state FROM locations ORDER BY city");
$locations = $locations_result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Saved Searches - Real Estate</title>
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
            background: linear-gradient(135deg, var(--secondary-color), #3b82f6);
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

        .search-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            margin-bottom: 1.5rem;
            transition: transform 0.3s ease;
            border-left: 4px solid transparent;
        }

        .search-card:hover {
            transform: translateY(-2px);
        }

        .search-card.active {
            border-left-color: var(--success-color);
        }

        .search-card.inactive {
            border-left-color: var(--text-muted);
            opacity: 0.7;
        }

        .search-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--dark-color);
        }

        .search-criteria {
            background: var(--light-color);
            border-radius: 10px;
            padding: 1rem;
            margin: 1rem 0;
        }

        .criteria-item {
            display: inline-block;
            background: var(--primary-color);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.8rem;
            margin: 0.25rem;
        }

        .alert-toggle {
            margin-left: auto;
        }

        .new-search-form {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            margin-bottom: 2rem;
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
            <i class="fas fa-bookmark fa-2x mb-2"></i>
            <h5 class="mb-0">Saved Searches</h5>
            <small>Search Alerts</small>
        </div>
    </div>

    <nav class="sidebar-nav p-3">
        <a href="dashboard.php" class="nav-link">
            <i class="fas fa-tachometer-alt"></i> Dashboard
        </a>
        <a href="browse_properties.php" class="nav-link">
            <i class="fas fa-search"></i> Browse Properties
        </a>
        <a href="favorites.php" class="nav-link">
            <i class="fas fa-heart"></i> My Favorites
        </a>
        <a href="saved_searches.php" class="nav-link active">
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
    <!-- Messages -->
    <?php if (!empty($message)): ?>
        <div class="alert alert-<?= $message_type; ?> alert-dismissible fade show" role="alert">
            <i class="fas fa-<?= $message_type === 'success' ? 'check-circle' : 'exclamation-triangle'; ?> me-2"></i>
            <?= htmlspecialchars($message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2><i class="fas fa-bookmark me-3 text-primary"></i>Saved Searches</h2>
            <p class="text-muted mb-0">Manage your search alerts and find new properties automatically</p>
        </div>
        <button class="btn btn-primary" data-bs-toggle="collapse" data-bs-target="#newSearchForm">
            <i class="fas fa-plus me-2"></i>Create New Search
        </button>
    </div>

    <!-- Statistics Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="stats-card">
                <div class="stat-number text-primary"><?= $stats['total_searches']; ?></div>
                <div class="text-muted">Total Searches</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stats-card">
                <div class="stat-number text-success"><?= $stats['active_alerts']; ?></div>
                <div class="text-muted">Active Alerts</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stats-card">
                <div class="stat-number text-warning"><?= $stats['inactive_searches']; ?></div>
                <div class="text-muted">Inactive Searches</div>
            </div>
        </div>
    </div>

    <!-- New Search Form -->
    <div class="collapse" id="newSearchForm">
        <div class="new-search-form">
            <h5 class="mb-3"><i class="fas fa-plus-circle me-2"></i>Create New Saved Search</h5>
            <form method="POST">
                <input type="hidden" name="action" value="save_new">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Search Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="search_name" placeholder="e.g., 3BR Houses in Downtown" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Property Type</label>
                        <select class="form-select" name="criteria[property_type]">
                            <option value="">All Types</option>
                            <?php foreach ($property_types as $type): ?>
                                <option value="<?= htmlspecialchars($type['type_name']); ?>">
                                    <?= htmlspecialchars($type['type_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Location</label>
                        <input type="text" class="form-control" name="criteria[location]" placeholder="City, State">
                    </div>
                </div>
                <div class="row g-3 mt-2">
                    <div class="col-md-3">
                        <label class="form-label">Min Price</label>
                        <input type="number" class="form-control" name="criteria[min_price]" placeholder="0">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Max Price</label>
                        <input type="number" class="form-control" name="criteria[max_price]" placeholder="No limit">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Min Bedrooms</label>
                        <select class="form-select" name="criteria[bedrooms]">
                            <option value="">Any</option>
                            <?php for ($i = 1; $i <= 6; $i++): ?>
                                <option value="<?= $i; ?>"><?= $i; ?>+</option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Min Bathrooms</label>
                        <select class="form-select" name="criteria[bathrooms]">
                            <option value="">Any</option>
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <option value="<?= $i; ?>"><?= $i; ?>+</option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>
                <div class="mt-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Save Search
                    </button>
                    <button type="button" class="btn btn-secondary" data-bs-toggle="collapse" data-bs-target="#newSearchForm">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Saved Searches List -->
    <?php if (empty($saved_searches)): ?>
        <div class="empty-state">
            <i class="fas fa-bookmark"></i>
            <h4>No Saved Searches Yet</h4>
            <p class="text-muted mb-4">Create saved searches to get notified when new properties match your criteria.</p>
            <button class="btn btn-primary btn-lg" data-bs-toggle="collapse" data-bs-target="#newSearchForm">
                <i class="fas fa-plus me-2"></i>Create Your First Search
            </button>
        </div>
    <?php else: ?>
        <div class="row">
            <div class="col-12">
                <?php foreach ($saved_searches as $search): ?>
                    <?php
                    $criteria = json_decode($search['search_criteria'], true);
                    $is_active = $search['is_active'];
                    ?>
                    <div class="search-card <?= $is_active ? 'active' : 'inactive'; ?>">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="flex-grow-1">
                                <div class="search-title">
                                    <?= htmlspecialchars($search['search_name']); ?>
                                    <?php if ($is_active): ?>
                                        <span class="badge bg-success ms-2">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary ms-2">Inactive</span>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="text-muted mb-2">
                                    <i class="fas fa-calendar me-1"></i>
                                    Created on <?= date('M j, Y', strtotime($search['created_at'])); ?>
                                    <?php if ($search['updated_at'] !== $search['created_at']): ?>
                                        • Updated <?= date('M j, Y', strtotime($search['updated_at'])); ?>
                                    <?php endif; ?>
                                </div>

                                <div class="search-criteria">
                                    <h6 class="mb-2">Search Criteria:</h6>
                                    <?php if (!empty($criteria)): ?>
                                        <?php foreach ($criteria as $key => $value): ?>
                                            <?php if (!empty($value)): ?>
                                                <span class="criteria-item">
                                                    <?= ucfirst(str_replace('_', ' ', $key)); ?>: <?= htmlspecialchars($value); ?>
                                                </span>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <span class="text-muted">No specific criteria set</span>
                                    <?php endif; ?>
                                </div>

                                <div class="mt-3">
                                    <a href="browse_properties.php?<?= http_build_query($criteria ?: []); ?>" class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-search me-1"></i>Run Search
                                    </a>
                                    <a href="browse_properties.php?<?= http_build_query($criteria ?: []); ?>&save_results=1" class="btn btn-outline-info btn-sm">
                                        <i class="fas fa-eye me-1"></i>View Results
                                    </a>
                                </div>
                            </div>

                            <div class="alert-toggle d-flex flex-column gap-2">
                                <!-- Toggle Active/Inactive -->
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="toggle_active">
                                    <input type="hidden" name="search_id" value="<?= $search['id']; ?>">
                                    <input type="hidden" name="is_active" value="<?= $is_active ? 0 : 1; ?>">
                                    <button type="submit" class="btn btn-sm <?= $is_active ? 'btn-warning' : 'btn-success'; ?>">
                                        <i class="fas fa-<?= $is_active ? 'pause' : 'play'; ?> me-1"></i>
                                        <?= $is_active ? 'Deactivate' : 'Activate'; ?>
                                    </button>
                                </form>

                                <!-- Delete -->
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this saved search?')">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="search_id" value="<?= $search['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        <i class="fas fa-trash me-1"></i>Delete
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Search Tips -->
        <div class="mt-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title"><i class="fas fa-lightbulb me-2 text-warning"></i>Search Tips</h6>
                    <ul class="mb-0">
                        <li><strong>Active alerts</strong> will notify you when new properties match your criteria</li>
                        <li><strong>Run searches</strong> regularly to find newly listed properties</li>
                        <li><strong>Update criteria</strong> by creating new searches with refined filters</li>
                        <li><strong>Deactivate searches</strong> temporarily if you want to pause alerts</li>
                    </ul>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>