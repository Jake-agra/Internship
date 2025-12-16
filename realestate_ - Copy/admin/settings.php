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

// Handle settings update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_settings'])) {
    try {
        $settings = [
            'site_name' => trim($_POST['site_name']),
            'site_description' => trim($_POST['site_description']),
            'contact_email' => trim($_POST['contact_email']),
            'contact_phone' => trim($_POST['contact_phone']),
            'business_address' => trim($_POST['business_address']),
            'currency_symbol' => trim($_POST['currency_symbol']),
            'properties_per_page' => (int)$_POST['properties_per_page'],
            'max_image_size' => (int)$_POST['max_image_size'],
            'maintenance_mode' => isset($_POST['maintenance_mode']) ? 1 : 0,
            'user_registration' => isset($_POST['user_registration']) ? 1 : 0,
            'email_notifications' => isset($_POST['email_notifications']) ? 1 : 0,
            'google_analytics' => trim($_POST['google_analytics'])
        ];

        foreach ($settings as $key => $value) {
            $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value, updated_by) VALUES (?, ?, ?) 
                                   ON DUPLICATE KEY UPDATE setting_value = ?, updated_by = ?");
            $user_id = $_SESSION['user_id'];
            $stmt->bind_param("ssiss", $key, $value, $user_id, $value, $user_id);
            $stmt->execute();
            $stmt->close();
        }

        $success = true;
        $message = 'Settings updated successfully!';
    } catch (Exception $e) {
        $message = 'Error updating settings: ' . $e->getMessage();
    }
}

// Get current settings
$current_settings = [];
$settings_query = "SELECT setting_key, setting_value FROM settings";
if ($result = $conn->query($settings_query)) {
    while ($row = $result->fetch_assoc()) {
        $current_settings[$row['setting_key']] = $row['setting_value'];
    }
    $result->free();
}

// Default values
$defaults = [
    'site_name' => 'Real Estate Pro',
    'site_description' => 'Professional Real Estate Platform',
    'contact_email' => 'info@realestate.com',
    'contact_phone' => '+1 (555) 123-4567',
    'business_address' => '123 Main St, City, State 12345',
    'currency_symbol' => '$',
    'properties_per_page' => 12,
    'max_image_size' => 5,
    'maintenance_mode' => 0,
    'user_registration' => 1,
    'email_notifications' => 1,
    'google_analytics' => ''
];

foreach ($defaults as $key => $default) {
    if (!isset($current_settings[$key])) {
        $current_settings[$key] = $default;
    }
}

// Handle logo upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['site_logo'])) {
    $upload_dir = '../uploads/settings/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    $file = $_FILES['site_logo'];
    if ($file['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (in_array($file['type'], $allowed_types)) {
            $filename = 'logo_' . time() . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
            $filepath = $upload_dir . $filename;
            
            if (move_uploaded_file($file['tmp_name'], $filepath)) {
                $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value, updated_by) VALUES (?, ?, ?) 
                                       ON DUPLICATE KEY UPDATE setting_value = ?, updated_by = ?");
                $user_id = $_SESSION['user_id'];
                $logo_path = 'uploads/settings/' . $filename;
                $key = 'site_logo';
                $stmt->bind_param("ssiss", $key, $logo_path, $user_id, $logo_path, $user_id);
                $stmt->execute();
                $stmt->close();
                
                $current_settings['site_logo'] = $logo_path;
                $success = true;
                $message = 'Logo uploaded successfully!';
            }
        } else {
            $message = 'Invalid file type. Please upload a valid image.';
        }
    }
}

// Get system information
$system_info = [
    'php_version' => phpversion(),
    'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
    'mysql_version' => $conn->server_info,
    'upload_max_filesize' => ini_get('upload_max_filesize'),
    'post_max_size' => ini_get('post_max_size'),
    'memory_limit' => ini_get('memory_limit'),
    'max_execution_time' => ini_get('max_execution_time')
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Real Estate Admin</title>
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

        .settings-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }

        .form-section {
            margin-bottom: 2rem;
            padding-bottom: 2rem;
            border-bottom: 1px solid #e5e7eb;
        }

        .form-section:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }

        .section-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
        }

        .section-title i {
            margin-right: 0.5rem;
            color: var(--primary-color);
        }

        .form-control, .form-select {
            border-radius: 10px;
            border: 2px solid #e5e7eb;
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .btn {
            border-radius: 10px;
            padding: 10px 20px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(37, 99, 235, 0.3);
        }

        .alert {
            border-radius: 10px;
            border: none;
        }

        .system-info {
            background: var(--light-color);
            border-radius: 10px;
            padding: 1rem;
            margin-top: 1rem;
        }

        .info-row {
            display: flex;
            justify-content: between;
            padding: 0.5rem 0;
            border-bottom: 1px solid #e5e7eb;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .current-logo {
            max-width: 200px;
            max-height: 100px;
            object-fit: contain;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            padding: 10px;
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
                <a href="reports.php" class="nav-link">
                    <i class="fas fa-chart-bar"></i>Reports
                </a>
            </li>
            <li class="nav-item">
                <a href="settings.php" class="nav-link active">
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
            <h1 class="h3 mb-0">System Settings</h1>
            <p class="text-muted">Configure your real estate platform settings</p>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="alert <?= $success ? 'alert-success' : 'alert-danger'; ?> alert-dismissible fade show">
            <i class="fas <?= $success ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?> me-2"></i>
            <?= htmlspecialchars($message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="settings-card">
        <form method="POST" enctype="multipart/form-data">
            <!-- General Settings -->
            <div class="form-section">
                <h3 class="section-title">
                    <i class="fas fa-cog"></i>General Settings
                </h3>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="site_name" class="form-label">Site Name</label>
                        <input type="text" class="form-control" id="site_name" name="site_name" 
                               value="<?= htmlspecialchars($current_settings['site_name']); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="currency_symbol" class="form-label">Currency Symbol</label>
                        <input type="text" class="form-control" id="currency_symbol" name="currency_symbol" 
                               value="<?= htmlspecialchars($current_settings['currency_symbol']); ?>" required>
                    </div>
                    <div class="col-12">
                        <label for="site_description" class="form-label">Site Description</label>
                        <textarea class="form-control" id="site_description" name="site_description" rows="3"><?= htmlspecialchars($current_settings['site_description']); ?></textarea>
                    </div>
                </div>
            </div>

            <!-- Logo Upload -->
            <div class="form-section">
                <h3 class="section-title">
                    <i class="fas fa-image"></i>Site Logo
                </h3>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="site_logo" class="form-label">Upload New Logo</label>
                        <input type="file" class="form-control" id="site_logo" name="site_logo" accept="image/*">
                        <small class="form-text text-muted">Recommended size: 300x100 pixels. Supported formats: JPG, PNG, GIF, WebP</small>
                    </div>
                    <div class="col-md-6">
                        <?php if (!empty($current_settings['site_logo'])): ?>
                            <label class="form-label">Current Logo</label><br>
                            <img src="../<?= htmlspecialchars($current_settings['site_logo']); ?>" alt="Current Logo" class="current-logo">
                        <?php else: ?>
                            <label class="form-label">No logo uploaded</label>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Contact Information -->
            <div class="form-section">
                <h3 class="section-title">
                    <i class="fas fa-address-card"></i>Contact Information
                </h3>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="contact_email" class="form-label">Contact Email</label>
                        <input type="email" class="form-control" id="contact_email" name="contact_email" 
                               value="<?= htmlspecialchars($current_settings['contact_email']); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="contact_phone" class="form-label">Contact Phone</label>
                        <input type="text" class="form-control" id="contact_phone" name="contact_phone" 
                               value="<?= htmlspecialchars($current_settings['contact_phone']); ?>">
                    </div>
                    <div class="col-12">
                        <label for="business_address" class="form-label">Business Address</label>
                        <textarea class="form-control" id="business_address" name="business_address" rows="2"><?= htmlspecialchars($current_settings['business_address']); ?></textarea>
                    </div>
                </div>
            </div>

            <!-- Application Settings -->
            <div class="form-section">
                <h3 class="section-title">
                    <i class="fas fa-sliders-h"></i>Application Settings
                </h3>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="properties_per_page" class="form-label">Properties Per Page</label>
                        <select class="form-select" id="properties_per_page" name="properties_per_page">
                            <option value="6" <?= $current_settings['properties_per_page'] == 6 ? 'selected' : ''; ?>>6</option>
                            <option value="12" <?= $current_settings['properties_per_page'] == 12 ? 'selected' : ''; ?>>12</option>
                            <option value="18" <?= $current_settings['properties_per_page'] == 18 ? 'selected' : ''; ?>>18</option>
                            <option value="24" <?= $current_settings['properties_per_page'] == 24 ? 'selected' : ''; ?>>24</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="max_image_size" class="form-label">Max Image Size (MB)</label>
                        <select class="form-select" id="max_image_size" name="max_image_size">
                            <option value="2" <?= $current_settings['max_image_size'] == 2 ? 'selected' : ''; ?>>2 MB</option>
                            <option value="5" <?= $current_settings['max_image_size'] == 5 ? 'selected' : ''; ?>>5 MB</option>
                            <option value="10" <?= $current_settings['max_image_size'] == 10 ? 'selected' : ''; ?>>10 MB</option>
                            <option value="20" <?= $current_settings['max_image_size'] == 20 ? 'selected' : ''; ?>>20 MB</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- System Options -->
            <div class="form-section">
                <h3 class="section-title">
                    <i class="fas fa-toggle-on"></i>System Options
                </h3>
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="maintenance_mode" name="maintenance_mode" 
                                   <?= $current_settings['maintenance_mode'] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="maintenance_mode">
                                Maintenance Mode
                            </label>
                            <small class="form-text text-muted d-block">Enable to show maintenance page to visitors</small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="user_registration" name="user_registration" 
                                   <?= $current_settings['user_registration'] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="user_registration">
                                Allow User Registration
                            </label>
                            <small class="form-text text-muted d-block">Allow new users to register accounts</small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="email_notifications" name="email_notifications" 
                                   <?= $current_settings['email_notifications'] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="email_notifications">
                                Email Notifications
                            </label>
                            <small class="form-text text-muted d-block">Send email notifications for inquiries and updates</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Analytics -->
            <div class="form-section">
                <h3 class="section-title">
                    <i class="fas fa-chart-line"></i>Analytics & Tracking
                </h3>
                <div class="row g-3">
                    <div class="col-12">
                        <label for="google_analytics" class="form-label">Google Analytics Tracking ID</label>
                        <input type="text" class="form-control" id="google_analytics" name="google_analytics" 
                               value="<?= htmlspecialchars($current_settings['google_analytics']); ?>" 
                               placeholder="G-XXXXXXXXXX">
                        <small class="form-text text-muted">Enter your Google Analytics 4 tracking ID</small>
                    </div>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="text-end">
                <button type="submit" name="update_settings" class="btn btn-primary btn-lg">
                    <i class="fas fa-save me-2"></i>Save Settings
                </button>
            </div>
        </form>
    </div>

    <!-- System Information -->
    <div class="settings-card">
        <h3 class="section-title">
            <i class="fas fa-server"></i>System Information
        </h3>
        <div class="system-info">
            <div class="info-row">
                <strong>PHP Version:</strong>
                <span><?= $system_info['php_version']; ?></span>
            </div>
            <div class="info-row">
                <strong>Server Software:</strong>
                <span><?= $system_info['server_software']; ?></span>
            </div>
            <div class="info-row">
                <strong>MySQL Version:</strong>
                <span><?= $system_info['mysql_version']; ?></span>
            </div>
            <div class="info-row">
                <strong>Upload Max Filesize:</strong>
                <span><?= $system_info['upload_max_filesize']; ?></span>
            </div>
            <div class="info-row">
                <strong>Post Max Size:</strong>
                <span><?= $system_info['post_max_size']; ?></span>
            </div>
            <div class="info-row">
                <strong>Memory Limit:</strong>
                <span><?= $system_info['memory_limit']; ?></span>
            </div>
            <div class="info-row">
                <strong>Max Execution Time:</strong>
                <span><?= $system_info['max_execution_time']; ?>s</span>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>