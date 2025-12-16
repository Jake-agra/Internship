<?php
session_start();
include('../Database/connection.php');
include('../includes/route.php');
include('../includes/security.php');

// Ensure client access only
if (!isLoggedIn()) {
    header('Location: ../login.php');
    exit();
}

$user_role = getUserRole();
if ($user_role === 'admin') {
    header('Location: ../admin/dashboard.php');
    exit();
} elseif ($user_role === 'agent') {
    header('Location: ../agent/dashboard.php');
    exit();
}

$user_id = getUserId();
$message = '';
$message_type = '';

// Fetch current user data
$user_stmt = $conn->prepare("SELECT first_name, last_name, email, phone, profile_image FROM users WHERE id = ?");
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_data = $user_stmt->get_result()->fetch_assoc();
$user_stmt->close();

// Handle updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!$csrf || !SecurityValidator::getInstance()->validateCSRFToken($csrf)) {
        $message = "Invalid security token. Please try again.";
        $message_type = "danger";
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'update_profile') {
            $first_name = trim($_POST['first_name'] ?? '');
            $last_name  = trim($_POST['last_name'] ?? '');
            $phone      = trim($_POST['phone'] ?? '');
            $email      = trim($_POST['email'] ?? '');

            if (empty($first_name) || empty($last_name) || empty($email)) {
                $message = "First name, last name, and email are required.";
                $message_type = "danger";
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $message = "Please provide a valid email address.";
                $message_type = "danger";
            } else {
                // Ensure email uniqueness
                $check_stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                $check_stmt->bind_param("si", $email, $user_id);
                $check_stmt->execute();
                $check_stmt->store_result();

                if ($check_stmt->num_rows > 0) {
                    $message = "This email is already in use by another account.";
                    $message_type = "danger";
                } else {
                    $update_stmt = $conn->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ? WHERE id = ?");
                    $update_stmt->bind_param("ssssi", $first_name, $last_name, $email, $phone, $user_id);

                    if ($update_stmt->execute()) {
                        $message = "Profile updated successfully.";
                        $message_type = "success";

                        // Refresh local data
                        $user_data['first_name'] = $first_name;
                        $user_data['last_name'] = $last_name;
                        $user_data['email'] = $email;
                        $user_data['phone'] = $phone;
                    } else {
                        $message = "Failed to update profile. Please try again.";
                        $message_type = "danger";
                    }
                    $update_stmt->close();
                }
                $check_stmt->close();
            }
        } elseif ($action === 'change_password') {
            $current_password = $_POST['current_password'] ?? '';
            $new_password     = $_POST['new_password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';

            if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
                $message = "Please fill in all password fields.";
                $message_type = "danger";
            } elseif ($new_password !== $confirm_password) {
                $message = "New passwords do not match.";
                $message_type = "danger";
            } elseif (strlen($new_password) < 6) {
                $message = "New password must be at least 6 characters.";
                $message_type = "danger";
            } else {
                // Verify current password
                $pwd_stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
                $pwd_stmt->bind_param("i", $user_id);
                $pwd_stmt->execute();
                $pwd_result = $pwd_stmt->get_result()->fetch_assoc();
                $pwd_stmt->close();

                if (!$pwd_result || !password_verify($current_password, $pwd_result['password'])) {
                    $message = "Current password is incorrect.";
                    $message_type = "danger";
                } else {
                    $hashed = password_hash($new_password, PASSWORD_DEFAULT);
                    $update_pwd = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $update_pwd->bind_param("si", $hashed, $user_id);

                    if ($update_pwd->execute()) {
                        $message = "Password updated successfully.";
                        $message_type = "success";
                    } else {
                        $message = "Failed to update password. Please try again.";
                        $message_type = "danger";
                    }
                    $update_pwd->close();
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Settings - Real Estate</title>
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
            background: linear-gradient(135deg, #0ea5e9, var(--primary-color));
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

        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }

        .form-control, .form-select {
            border-radius: 10px;
            border: 2px solid #e5e7eb;
            padding: 12px 16px;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
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
            <i class="fas fa-user-circle fa-2x mb-2"></i>
            <h5 class="mb-0">Profile</h5>
            <small>Account Settings</small>
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
        <a href="saved_searches.php" class="nav-link">
            <i class="fas fa-bookmark"></i> Saved Searches
        </a>
        <a href="inquiries.php" class="nav-link">
            <i class="fas fa-envelope"></i> My Inquiries
        </a>
        <a href="profile.php" class="nav-link active">
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
    <?php if (!empty($message)): ?>
        <div class="alert alert-<?= $message_type ?: 'info'; ?> alert-dismissible fade show" role="alert">
            <i class="fas fa-<?= $message_type === 'success' ? 'check-circle' : ($message_type === 'danger' ? 'exclamation-triangle' : 'info-circle'); ?> me-2"></i>
            <?= htmlspecialchars($message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- Profile Info -->
        <div class="col-lg-7">
            <div class="card p-4">
                <h4 class="mb-3"><i class="fas fa-id-card me-2 text-primary"></i>Profile Information</h4>
                <form method="POST">
                    <input type="hidden" name="action" value="update_profile">
                    <input type="hidden" name="csrf_token" value="<?= SecurityValidator::getInstance()->generateCSRFToken(); ?>">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">First Name</label>
                            <input type="text" class="form-control" name="first_name" value="<?= htmlspecialchars($user_data['first_name'] ?? ''); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Last Name</label>
                            <input type="text" class="form-control" name="last_name" value="<?= htmlspecialchars($user_data['last_name'] ?? ''); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" value="<?= htmlspecialchars($user_data['email'] ?? ''); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phone</label>
                            <input type="tel" class="form-control" name="phone" value="<?= htmlspecialchars($user_data['phone'] ?? ''); ?>" placeholder="Optional">
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Save Changes
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Security -->
        <div class="col-lg-5">
            <div class="card p-4 mb-4">
                <h4 class="mb-3"><i class="fas fa-lock me-2 text-danger"></i>Change Password</h4>
                <form method="POST">
                    <input type="hidden" name="action" value="change_password">
                    <input type="hidden" name="csrf_token" value="<?= SecurityValidator::getInstance()->generateCSRFToken(); ?>">
                    <div class="mb-3">
                        <label class="form-label">Current Password</label>
                        <input type="password" class="form-control" name="current_password" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">New Password</label>
                        <input type="password" class="form-control" name="new_password" minlength="6" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Confirm New Password</label>
                        <input type="password" class="form-control" name="confirm_password" minlength="6" required>
                    </div>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-key me-2"></i>Update Password
                    </button>
                </form>
            </div>

            <div class="card p-4">
                <h5 class="mb-3"><i class="fas fa-shield-alt me-2 text-success"></i>Security Tips</h5>
                <ul class="text-muted mb-0">
                    <li>Use a strong password with numbers and symbols.</li>
                    <li>Do not share your account credentials.</li>
                    <li>Update your password regularly.</li>
                    <li>Contact support if you notice unusual activity.</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>

