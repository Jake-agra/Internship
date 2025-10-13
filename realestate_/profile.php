<?php
session_start();
include('./Database/connection.php');
include('./includes/route.php');

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$message = '';
$success = false;

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $phone = trim($_POST['phone']);
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (empty($first_name) || empty($last_name) || empty($phone)) {
        $message = 'Please fill in all required fields.';
    } else {
        // Check if password change is requested
        if (!empty($new_password)) {
            if (empty($current_password)) {
                $message = 'Please enter your current password to change it.';
            } elseif ($new_password !== $confirm_password) {
                $message = 'New passwords do not match.';
            } elseif (strlen($new_password) < 6) {
                $message = 'New password must be at least 6 characters long.';
            } else {
                // Verify current password
                $check_stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
                $check_stmt->bind_param("i", $user_id);
                $check_stmt->execute();
                $result = $check_stmt->get_result();
                $user = $result->fetch_assoc();
                $check_stmt->close();
                
                if (!password_verify($current_password, $user['password'])) {
                    $message = 'Current password is incorrect.';
                } else {
                    // Update with new password
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $update_stmt = $conn->prepare("UPDATE users SET first_name = ?, last_name = ?, phone = ?, password = ? WHERE id = ?");
                    $update_stmt->bind_param("ssssi", $first_name, $last_name, $phone, $hashed_password, $user_id);
                    
                    if ($update_stmt->execute()) {
                        $success = true;
                        $message = 'Profile updated successfully!';
                    } else {
                        $message = 'Failed to update profile. Please try again.';
                    }
                    $update_stmt->close();
                }
            }
        } else {
            // Update without password change
            $update_stmt = $conn->prepare("UPDATE users SET first_name = ?, last_name = ?, phone = ? WHERE id = ?");
            $update_stmt->bind_param("sssi", $first_name, $last_name, $phone, $user_id);
            
            if ($update_stmt->execute()) {
                $success = true;
                $message = 'Profile updated successfully!';
            } else {
                $message = 'Failed to update profile. Please try again.';
            }
            $update_stmt->close();
        }
    }
}

// Get user information
$user_query = "SELECT u.*, r.role_name FROM users u JOIN roles r ON u.role_id = r.id WHERE u.id = ?";
$stmt = $conn->prepare($user_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

// Get user's saved properties (bookmarks)
$bookmarks = [];
$bookmark_query = "SELECT p.id, p.propertiesname, p.description, pr.amount, pr.currency, pt.type_name, l.city, l.country, p.created_at
FROM bookmarks b
JOIN properties p ON b.property_id = p.id
JOIN prices pr ON p.price_id = pr.id
JOIN property_types pt ON p.property_type_id = pt.id
JOIN locations l ON p.location_id = l.id
WHERE b.user_id = ? AND p.status = 'available'
ORDER BY b.created_at DESC";

// Check if bookmarks table exists, if not create it
$table_check = $conn->query("SHOW TABLES LIKE 'bookmarks'");
if ($table_check->num_rows == 0) {
    $create_bookmarks = "CREATE TABLE bookmarks (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        property_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE,
        UNIQUE KEY unique_bookmark (user_id, property_id)
    )";
    $conn->query($create_bookmarks);
}

$bookmark_stmt = $conn->prepare($bookmark_query);
$bookmark_stmt->bind_param("i", $user_id);
$bookmark_stmt->execute();
$bookmark_result = $bookmark_stmt->get_result();
$bookmarks = $bookmark_result->fetch_all(MYSQLI_ASSOC);
$bookmark_stmt->close();

// Get user's inquiries
$inquiries = [];
$inquiry_query = "SELECT i.id, i.message, i.status, i.created_at, p.propertiesname, p.id as property_id
FROM inquiries i
LEFT JOIN properties p ON i.property_id = p.id
WHERE i.client_id = ?
ORDER BY i.created_at DESC";

$inquiry_stmt = $conn->prepare($inquiry_query);
$inquiry_stmt->bind_param("i", $user_id);
$inquiry_stmt->execute();
$inquiry_result = $inquiry_stmt->get_result();
$inquiries = $inquiry_result->fetch_all(MYSQLI_ASSOC);
$inquiry_stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Real Estate</title>
    <link rel="stylesheet" href="bootstrap-5.3.7-dist/css/bootstrap.min.css">
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

        /* Navigation */
        .navbar {
            background: rgba(255, 255, 255, 0.95) !important;
            backdrop-filter: blur(10px);
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
            color: var(--primary-color) !important;
        }

        .nav-link {
            font-weight: 500;
            color: var(--dark-color) !important;
            transition: color 0.3s ease;
        }

        .nav-link:hover {
            color: var(--primary-color) !important;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
            border-radius: 8px;
            padding: 10px 20px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(37, 99, 235, 0.3);
        }

        .profile-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 3rem 0;
            margin-bottom: 3rem;
        }

        .profile-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            margin-bottom: 2rem;
            border: none;
        }

        .nav-tabs {
            border-bottom: 2px solid #e5e7eb;
        }

        .nav-tabs .nav-link {
            border: none;
            color: var(--text-muted);
            font-weight: 500;
            padding: 1rem 1.5rem;
            border-radius: 8px;
            margin-right: 0.5rem;
        }

        .nav-tabs .nav-link.active {
            background: var(--primary-color);
            color: white;
        }

        .property-bookmark {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            padding: 1.5rem;
            margin-bottom: 1rem;
            transition: transform 0.3s ease;
        }

        .property-bookmark:hover {
            transform: translateY(-5px);
        }

        .inquiry-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            padding: 1.5rem;
            margin-bottom: 1rem;
            border-left: 4px solid var(--primary-color);
        }

        .stats-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            padding: 1.5rem;
            text-align: center;
            margin-bottom: 1rem;
        }

        .stats-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-color);
        }

        .stats-label {
            color: var(--text-muted);
            font-weight: 500;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(37, 99, 235, 0.3);
        }

        /* Profile Header */
        .profile-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 3rem 0;
            margin-top: 76px;
        }

        .profile-avatar {
            width: 120px;
            height: 120px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            margin: 0 auto 1rem;
            border: 4px solid rgba(255, 255, 255, 0.3);
        }

        /* Profile Content */
        .profile-content {
            padding: 2rem 0;
        }

        .profile-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
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

        /* Property Cards */
        .property-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            height: 100%;
        }

        .property-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
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
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .property-content {
            padding: 1.5rem;
        }

        .property-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--dark-color);
        }

        .property-location {
            color: var(--text-muted);
            margin-bottom: 1rem;
            font-size: 0.9rem;
        }

        .property-price {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }

        /* Tabs */
        .nav-tabs {
            border-bottom: 2px solid #e5e7eb;
        }

        .nav-tabs .nav-link {
            border: none;
            color: var(--text-muted);
            font-weight: 500;
            padding: 1rem 1.5rem;
        }

        .nav-tabs .nav-link.active {
            color: var(--primary-color);
            border-bottom: 2px solid var(--primary-color);
            background: none;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .profile-header {
                padding: 2rem 0;
            }
            
            .profile-card {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>

<!-- Navigation -->
<nav class="navbar navbar-expand-lg navbar-light fixed-top">
    <div class="container">
        <a href="index.php" class="navbar-brand">
            <i class="fas fa-home me-2"></i>Real Estate
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="index.php">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="property.php">Properties</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="index.php#about">About</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="contact.php">Contact</a>
                </li>
            </ul>

            <!-- User authentication section -->
            <ul class="navbar-nav align-items-center ms-auto">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-user-circle me-2"></i>
                        <?= htmlspecialchars($_SESSION['user_email']); ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                        <li><a class="dropdown-item active" href="profile.php"><i class="fas fa-user me-2"></i> My Profile</a></li>
                        <li><a class="dropdown-item" href="property.php"><i class="fas fa-home me-2"></i> Properties</a></li>
                        <li><a class="dropdown-item" href="bookmarks.php"><i class="fas fa-bookmark me-2"></i> Bookmarks</a></li>
                        <?php if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin'): ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="admin/dashboard.php"><i class="fas fa-cog me-2"></i> Admin Panel</a></li>
                        <?php endif; ?>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- Profile Header -->
<section class="profile-header">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <div class="profile-avatar">
                    <i class="fas fa-user"></i>
                </div>
                <h1 class="text-center mb-2"><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h1>
                <p class="text-center mb-0"><?= htmlspecialchars($user['email']); ?></p>
                <p class="text-center">
                    <span class="badge bg-light text-dark"><?= ucfirst($user['role_name']); ?></span>
                </p>
            </div>
            <div class="col-lg-4 text-lg-end">
                <div class="text-white">
                    <h4 class="mb-0">Member Since</h4>
                    <p class="mb-0"><?= date('F Y', strtotime($user['created_at'])); ?></p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Profile Content -->
<section class="profile-content">
    <div class="container">
        <!-- Tabs -->
        <ul class="nav nav-tabs" id="profileTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="profile-tab" data-bs-toggle="tab" data-bs-target="#profile" type="button" role="tab">
                    <i class="fas fa-user me-2"></i>Profile Information
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="bookmarks-tab" data-bs-toggle="tab" data-bs-target="#bookmarks" type="button" role="tab">
                    <i class="fas fa-bookmark me-2"></i>Saved Properties
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="inquiries-tab" data-bs-toggle="tab" data-bs-target="#inquiries" type="button" role="tab">
                    <i class="fas fa-envelope me-2"></i>My Inquiries
                </button>
            </li>
        </ul>

        <div class="tab-content" id="profileTabsContent">
            <!-- Profile Information Tab -->
            <div class="tab-pane fade show active" id="profile" role="tabpanel">
                <div class="profile-card">
                    <h4 class="mb-4">Update Profile Information</h4>
                    
                    <?php if ($message): ?>
                        <div class="alert <?= $success ? 'alert-success' : 'alert-danger'; ?>">
                            <?= htmlspecialchars($message); ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="first_name" class="form-label">First Name *</label>
                                <input type="text" class="form-control" id="first_name" name="first_name" 
                                       value="<?= htmlspecialchars($user['first_name']); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="last_name" class="form-label">Last Name *</label>
                                <input type="text" class="form-control" id="last_name" name="last_name" 
                                       value="<?= htmlspecialchars($user['last_name']); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="email" value="<?= htmlspecialchars($user['email']); ?>" disabled>
                                <small class="text-muted">Email cannot be changed</small>
                            </div>
                            <div class="col-md-6">
                                <label for="phone" class="form-label">Phone Number *</label>
                                <input type="tel" class="form-control" id="phone" name="phone" 
                                       value="<?= htmlspecialchars($user['phone']); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="role" class="form-label">Account Type</label>
                                <input type="text" class="form-control" value="<?= ucfirst($user['role_name']); ?>" disabled>
                            </div>
                            <div class="col-md-6">
                                <label for="member_since" class="form-label">Member Since</label>
                                <input type="text" class="form-control" value="<?= date('F j, Y', strtotime($user['created_at'])); ?>" disabled>
                            </div>
                        </div>

                        <hr class="my-4">
                        <h5 class="mb-3">Change Password</h5>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="current_password" class="form-label">Current Password</label>
                                <input type="password" class="form-control" id="current_password" name="current_password">
                            </div>
                            <div class="col-md-4">
                                <label for="new_password" class="form-label">New Password</label>
                                <input type="password" class="form-control" id="new_password" name="new_password" minlength="6">
                            </div>
                            <div class="col-md-4">
                                <label for="confirm_password" class="form-label">Confirm New Password</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" minlength="6">
                            </div>
                        </div>
                        <small class="text-muted">Leave password fields empty if you don't want to change your password.</small>

                        <div class="mt-4">
                            <button type="submit" name="update_profile" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Update Profile
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Saved Properties Tab -->
            <div class="tab-pane fade" id="bookmarks" role="tabpanel">
                <div class="profile-card">
                    <h4 class="mb-4">Saved Properties</h4>
                    
                    <?php if (empty($bookmarks)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-bookmark fa-3x text-muted mb-3"></i>
                            <h5>No Saved Properties</h5>
                            <p class="text-muted">You haven't saved any properties yet.</p>
                            <a href="property.php" class="btn btn-primary">Browse Properties</a>
                        </div>
                    <?php else: ?>
                        <div class="row g-4">
                            <?php foreach ($bookmarks as $property): ?>
                                <div class="col-lg-4 col-md-6">
                                    <div class="property-card">
                                        <div class="property-image" style="background-image: url('https://images.unsplash.com/photo-1564013799919-ab600027ffc6?w=400&h=300&fit=crop')">
                                            <span class="property-badge"><?= htmlspecialchars($property['type_name']); ?></span>
                                        </div>
                                        <div class="property-content">
                                            <h5 class="property-title"><?= htmlspecialchars($property['propertiesname']); ?></h5>
                                            <p class="property-location">
                                                <i class="fas fa-map-marker-alt me-1"></i>
                                                <?= htmlspecialchars($property['city'] . ', ' . $property['country']); ?>
                                            </p>
                                            <div class="property-price">
                                                <?= htmlspecialchars($property['currency']); ?> <?= number_format($property['amount']); ?>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <small class="text-muted">Saved <?= date('M j, Y', strtotime($property['created_at'])); ?></small>
                                                <a href="property_details.php?id=<?= $property['id']; ?>" class="btn btn-primary btn-sm">
                                                    <i class="fas fa-eye me-1"></i>View
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Inquiries Tab -->
            <div class="tab-pane fade" id="inquiries" role="tabpanel">
                <div class="profile-card">
                    <h4 class="mb-4">My Inquiries</h4>
                    
                    <?php if (empty($inquiries)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-envelope fa-3x text-muted mb-3"></i>
                            <h5>No Inquiries Yet</h5>
                            <p class="text-muted">You haven't made any property inquiries yet.</p>
                            <a href="property.php" class="btn btn-primary">Browse Properties</a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Property</th>
                                        <th>Message</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($inquiries as $inquiry): ?>
                                        <tr>
                                            <td>
                                                <?php if ($inquiry['property_id']): ?>
                                                    <a href="property_details.php?id=<?= $inquiry['property_id']; ?>" class="text-decoration-none">
                                                        <?= htmlspecialchars($inquiry['propertiesname']); ?>
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-muted">Property not found</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= htmlspecialchars(substr($inquiry['message'], 0, 50)) . (strlen($inquiry['message']) > 50 ? '...' : ''); ?></td>
                                            <td>
                                                <span class="badge bg-<?= $inquiry['status'] === 'pending' ? 'warning' : ($inquiry['status'] === 'responded' ? 'success' : 'secondary'); ?>">
                                                    <?= ucfirst($inquiry['status']); ?>
                                                </span>
                                            </td>
                                            <td><?= date('M j, Y', strtotime($inquiry['created_at'])); ?></td>
                                            <td>
                                                <?php if ($inquiry['property_id']): ?>
                                                    <a href="property_details.php?id=<?= $inquiry['property_id']; ?>" class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Footer -->
<footer class="bg-dark text-white py-5 mt-5">
    <div class="container">
        <div class="row">
            <div class="col-lg-4 mb-4">
                <h5 class="mb-3">
                    <i class="fas fa-home me-2"></i>Real Estate
                </h5>
                <p class="text-muted">Your trusted partner in finding the perfect property.</p>
            </div>
            <div class="col-lg-8 text-lg-end">
                <p class="text-muted mb-0">© <?= date('Y'); ?> Real Estate. All rights reserved.</p>
            </div>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
