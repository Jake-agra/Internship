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

// Handle inquiry actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $inquiry_id = (int)$_POST['inquiry_id'];
        $action = $_POST['action'];
        
        switch ($action) {
            case 'mark_read':
                $stmt = $conn->prepare("UPDATE inquiries SET status = 'responded' WHERE id = ?");
                $stmt->bind_param("i", $inquiry_id);
                if ($stmt->execute()) {
                    $success = true;
                    $message = 'Inquiry marked as read!';
                }
                $stmt->close();
                break;
                
            case 'delete':
                $stmt = $conn->prepare("DELETE FROM inquiries WHERE id = ?");
                $stmt->bind_param("i", $inquiry_id);
                if ($stmt->execute()) {
                    $success = true;
                    $message = 'Inquiry deleted successfully!';
                }
                $stmt->close();
                break;
        }
    }
}

// Get inquiries (using correct column names: phone and email, not contact_phone and contact_email)
$inquiries_query = "SELECT i.id, i.message, i.phone, i.email, i.name, i.status, i.created_at,
                           p.propertiesname, p.id as property_id,
                           u.first_name, u.last_name
                    FROM inquiries i
                    LEFT JOIN properties p ON i.property_id = p.id
                    LEFT JOIN users u ON i.client_id = u.id
                    ORDER BY i.created_at DESC
                    LIMIT 50";

$inquiries = $conn->query($inquiries_query)->fetch_all(MYSQLI_ASSOC);

// Get contact messages if table exists
$contact_messages = [];
$table_check = $conn->query("SHOW TABLES LIKE 'contact_messages'");
if ($table_check->num_rows > 0) {
    $contact_query = "SELECT id, name, email, phone, subject, message, status, created_at
                      FROM contact_messages
                      ORDER BY created_at DESC
                      LIMIT 50";
    $contact_messages = $conn->query($contact_query)->fetch_all(MYSQLI_ASSOC);
}

// Get stats
$total_inquiries = $conn->query("SELECT COUNT(*) as count FROM inquiries")->fetch_assoc()['count'];
$pending_inquiries = $conn->query("SELECT COUNT(*) as count FROM inquiries WHERE status = 'pending'")->fetch_assoc()['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inquiries Management - Admin Dashboard</title>
    <link rel="stylesheet" href="../bootstrap-5.3.7-dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
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
        .main-content {
            margin-left: 250px;
            min-height: 100vh;
        }
        .sidebar-header {
            padding: 1.5rem;
            border-bottom: 1px solid #e5e7eb;
        }
        .sidebar-nav {
            padding: 1rem 0;
        }
        .nav-link {
            display: flex;
            align-items: center;
            padding: 0.75rem 1.5rem;
            color: #6b7280;
            text-decoration: none;
        }
        .nav-link.active {
            background: #f8fafc;
            color: #2563eb;
        }
        .nav-link i {
            width: 20px;
            margin-right: 0.75rem;
        }
        .top-navbar {
            background: white;
            padding: 1rem 2rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        .content-area {
            padding: 2rem;
        }
        .table-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
    </style>
</head>
<body>

<!-- Sidebar -->
<nav class="sidebar">
    <div class="sidebar-header">
        <a href="dashboard.php" class="navbar-brand text-decoration-none">
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
                <a href="inquiries.php" class="nav-link active">
                    <i class="fas fa-envelope"></i>Inquiries
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
    <!-- Top Navbar -->
    <div class="top-navbar">
        <h4 class="mb-0">Inquiries Management</h4>
        <p class="text-muted mb-0">Manage property inquiries and contact messages</p>
    </div>

    <!-- Content Area -->
    <div class="content-area">
        <?php if ($message): ?>
            <div class="alert <?= $success ? 'alert-success' : 'alert-danger'; ?> alert-dismissible fade show">
                <?= htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Statistics -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="stat-card">
                    <h3 class="text-primary"><?= $total_inquiries; ?></h3>
                    <p class="text-muted">Total Inquiries</p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="stat-card">
                    <h3 class="text-warning"><?= $pending_inquiries; ?></h3>
                    <p class="text-muted">Pending Inquiries</p>
                </div>
            </div>
        </div>

        <!-- Property Inquiries -->
        <div class="table-card">
            <h5 class="mb-3">Property Inquiries</h5>
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
                        <?php foreach ($inquiries as $inquiry): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($inquiry['propertiesname'] ?: 'Property Deleted'); ?></strong>
                                </td>
                                <td>
                                    <div><?= htmlspecialchars(($inquiry['name'] ?: ($inquiry['first_name'] ?: 'Guest')) . ' ' . ($inquiry['last_name'] ?: '')); ?></div>
                                    <small class="text-muted"><?= htmlspecialchars($inquiry['email'] ?: ($inquiry['first_name'] ? 'User Account' : 'Guest')); ?></small>
                                </td>
                                <td>
                                    <div style="max-width: 300px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                        <?= htmlspecialchars($inquiry['message']); ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-<?= $inquiry['status'] === 'pending' ? 'warning' : 'success'; ?>">
                                        <?= ucfirst($inquiry['status']); ?>
                                    </span>
                                </td>
                                <td><?= date('M j, Y', strtotime($inquiry['created_at'])); ?></td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <?php if ($inquiry['status'] === 'pending'): ?>
                                            <button class="btn btn-sm btn-outline-success" onclick="markAsRead(<?= $inquiry['id']; ?>)">
                                                <i class="fas fa-check"></i>
                                            </button>
                                        <?php endif; ?>
                                        <button class="btn btn-sm btn-outline-danger" onclick="deleteInquiry(<?= $inquiry['id']; ?>)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Contact Messages -->
        <?php if (!empty($contact_messages)): ?>
            <div class="table-card">
                <h5 class="mb-3">Contact Messages</h5>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Subject</th>
                                <th>Message</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($contact_messages as $contact): ?>
                                <tr>
                                    <td>
                                        <div><?= htmlspecialchars($contact['name']); ?></div>
                                        <small class="text-muted"><?= htmlspecialchars($contact['email']); ?></small>
                                    </td>
                                    <td><?= htmlspecialchars($contact['subject']); ?></td>
                                    <td>
                                        <div style="max-width: 300px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                            <?= htmlspecialchars($contact['message']); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= $contact['status'] === 'new' ? 'warning' : ($contact['status'] === 'responded' ? 'success' : 'info'); ?>">
                                            <?= ucfirst($contact['status']); ?>
                                        </span>
                                    </td>
                                    <td><?= date('M j, Y', strtotime($contact['created_at'])); ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-info" onclick="viewContact(<?= $contact['id']; ?>)">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Hidden form for actions -->
<form id="actionForm" method="POST" style="display: none;">
    <input type="hidden" name="inquiry_id" id="actionInquiryId">
    <input type="hidden" name="action" id="actionType">
</form>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function markAsRead(inquiryId) {
        if (confirm('Mark this inquiry as read?')) {
            document.getElementById('actionInquiryId').value = inquiryId;
            document.getElementById('actionType').value = 'mark_read';
            document.getElementById('actionForm').submit();
        }
    }

    function deleteInquiry(inquiryId) {
        if (confirm('Are you sure you want to delete this inquiry? This action cannot be undone.')) {
            document.getElementById('actionInquiryId').value = inquiryId;
            document.getElementById('actionType').value = 'delete';
            document.getElementById('actionForm').submit();
        }
    }

    function viewContact(contactId) {
        alert('Contact details view - Feature to be implemented');
    }
</script>
</body>
</html>