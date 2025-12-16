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
        case 'send_inquiry':
            $property_id = isset($_POST['property_id']) && !empty($_POST['property_id']) ? (int)$_POST['property_id'] : null;
            $subject = trim($_POST['subject']);
            $inquiry_message = trim($_POST['message']);
            $phone = trim($_POST['phone']);
            
            if (!empty($subject) && !empty($inquiry_message)) {
                $insert_stmt = $conn->prepare("INSERT INTO inquiries (client_id, property_id, subject, message, phone, status) VALUES (?, ?, ?, ?, ?, 'pending')");
                $insert_stmt->bind_param("iisss", $user_id, $property_id, $subject, $inquiry_message, $phone);
                if ($insert_stmt->execute()) {
                    // Send email notification
                    try {
                        require_once '../includes/EmailService.php';
                        $emailService = new EmailService();
                        
                        // Get user information for email
                        $user_stmt = $conn->prepare("SELECT first_name, last_name, email FROM users WHERE id = ?");
                        $user_stmt->bind_param("i", $user_id);
                        $user_stmt->execute();
                        $user_data = $user_stmt->get_result()->fetch_assoc();
                        $user_stmt->close();
                        
                        $user_name = $user_data['first_name'] . ' ' . $user_data['last_name'];
                        $user_email = $user_data['email'];
                        
                        // Prepare inquiry details
                        $inquiry_details = "Subject: " . $subject . "\n\n" . $inquiry_message;
                        if ($phone) {
                            $inquiry_details .= "\n\nPhone: " . $phone;
                        }
                        if ($property_id) {
                            $prop_stmt = $conn->prepare("SELECT propertiesname FROM properties WHERE id = ?");
                            $prop_stmt->bind_param("i", $property_id);
                            $prop_stmt->execute();
                            $prop_data = $prop_stmt->get_result()->fetch_assoc();
                            $prop_stmt->close();
                            if ($prop_data) {
                                $inquiry_details .= "\n\nProperty: " . $prop_data['propertiesname'];
                            }
                        }
                        
                        // Send email notification
                        $emailService->sendContactForm($user_name, $user_email, $phone, $inquiry_details);
                    } catch (Exception $e) {
                        // Log email error but don't fail the inquiry submission
                        error_log("Email notification failed for inquiry: " . $e->getMessage());
                    }
                    
                    $message = "Your inquiry has been sent successfully!";
                    $message_type = "success";
                } else {
                    $message = "Error sending inquiry. Please try again.";
                    $message_type = "danger";
                }
                $insert_stmt->close();
            } else {
                $message = "Please fill in all required fields.";
                $message_type = "danger";
            }
            break;
            
        case 'delete_inquiry':
            $inquiry_id = (int)$_POST['inquiry_id'];
            $delete_stmt = $conn->prepare("DELETE FROM inquiries WHERE id = ? AND client_id = ?");
            $delete_stmt->bind_param("ii", $inquiry_id, $user_id);
            if ($delete_stmt->execute()) {
                $message = "Inquiry deleted successfully!";
                $message_type = "success";
            } else {
                $message = "Error deleting inquiry.";
                $message_type = "danger";
            }
            $delete_stmt->close();
            break;
    }
}

// Get inquiries with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

$query = "SELECT i.id, i.subject, i.message, i.phone, i.status, i.created_at,
p.id as property_id, p.propertiesname, p.images,
pr.amount, pr.currency, l.city, l.state
FROM inquiries i
LEFT JOIN properties p ON i.property_id = p.id
LEFT JOIN prices pr ON p.price_id = pr.id
LEFT JOIN locations l ON p.location_id = l.id
WHERE i.client_id = ?
ORDER BY i.created_at DESC
LIMIT ? OFFSET ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("iii", $user_id, $per_page, $offset);
$stmt->execute();
$inquiries = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get total count
$count_result = $conn->query("SELECT COUNT(*) as total FROM inquiries WHERE client_id = $user_id");
$total_results = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_results / $per_page);

// Get statistics
$stats_query = "SELECT 
    COUNT(*) as total_inquiries,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_inquiries,
    SUM(CASE WHEN status = 'responded' THEN 1 ELSE 0 END) as responded_inquiries,
    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_inquiries
FROM inquiries WHERE client_id = ?";

$stats_stmt = $conn->prepare($stats_query);
$stats_stmt->bind_param("i", $user_id);
$stats_stmt->execute();
$stats = $stats_stmt->get_result()->fetch_assoc();
$stats_stmt->close();

// Get properties for new inquiry form
$properties_result = $conn->query("SELECT id, propertiesname FROM properties WHERE status = 'available' ORDER BY propertiesname LIMIT 50");
$properties = $properties_result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Inquiries - Real Estate</title>
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

        .inquiry-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            margin-bottom: 1.5rem;
            transition: transform 0.3s ease;
            border-left: 4px solid transparent;
        }

        .inquiry-card:hover {
            transform: translateY(-2px);
        }

        .inquiry-card.pending {
            border-left-color: var(--warning-color);
        }

        .inquiry-card.responded {
            border-left-color: var(--primary-color);
        }

        .inquiry-card.completed {
            border-left-color: var(--success-color);
        }

        .new-inquiry-form {
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

        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .status-pending {
            background-color: #fef3c7;
            color: #92400e;
        }

        .status-responded {
            background-color: #dbeafe;
            color: #1e40af;
        }

        .status-completed {
            background-color: #d1fae5;
            color: #065f46;
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
            <i class="fas fa-envelope fa-2x mb-2"></i>
            <h5 class="mb-0">My Inquiries</h5>
            <small>Property Inquiries</small>
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
        <a href="inquiries.php" class="nav-link active">
            <i class="fas fa-envelope"></i> My Inquiries
        </a>
        <a href="profile.php" class="nav-link">
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
            <h2><i class="fas fa-envelope me-3 text-info"></i>My Inquiries</h2>
            <p class="text-muted mb-0">Track your property inquiries and responses</p>
        </div>
        <button class="btn btn-primary" data-bs-toggle="collapse" data-bs-target="#newInquiryForm">
            <i class="fas fa-plus me-2"></i>Send New Inquiry
        </button>
    </div>

    <!-- Statistics Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="stats-card">
                <div class="stat-number text-info"><?= $stats['total_inquiries']; ?></div>
                <div class="text-muted">Total Inquiries</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card">
                <div class="stat-number text-warning"><?= $stats['pending_inquiries']; ?></div>
                <div class="text-muted">Pending</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card">
                <div class="stat-number text-primary"><?= $stats['responded_inquiries']; ?></div>
                <div class="text-muted">Responded</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card">
                <div class="stat-number text-success"><?= $stats['completed_inquiries']; ?></div>
                <div class="text-muted">Completed</div>
            </div>
        </div>
    </div>

    <!-- New Inquiry Form -->
    <div class="collapse" id="newInquiryForm">
        <div class="new-inquiry-form">
            <h5 class="mb-3"><i class="fas fa-plus-circle me-2"></i>Send New Inquiry</h5>
            <form method="POST">
                <input type="hidden" name="action" value="send_inquiry">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Property <small class="text-muted">(Optional)</small></label>
                        <select class="form-select" name="property_id">
                            <option value="">General Inquiry</option>
                            <?php foreach ($properties as $property): ?>
                                <option value="<?= $property['id']; ?>">
                                    <?= htmlspecialchars($property['propertiesname']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Your Phone <small class="text-muted">(Optional)</small></label>
                        <input type="tel" class="form-control" name="phone" placeholder="Your phone number">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Subject <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="subject" placeholder="Brief description of your inquiry" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Message <span class="text-danger">*</span></label>
                    <textarea class="form-control" name="message" rows="4" placeholder="Please provide details about your inquiry..." required></textarea>
                </div>
                <div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane me-2"></i>Send Inquiry
                    </button>
                    <button type="button" class="btn btn-secondary" data-bs-toggle="collapse" data-bs-target="#newInquiryForm">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Inquiries List -->
    <?php if (empty($inquiries)): ?>
        <div class="empty-state">
            <i class="fas fa-envelope-open"></i>
            <h4>No Inquiries Yet</h4>
            <p class="text-muted mb-4">Start by sending inquiries about properties you're interested in.</p>
            <button class="btn btn-primary btn-lg" data-bs-toggle="collapse" data-bs-target="#newInquiryForm">
                <i class="fas fa-plus me-2"></i>Send Your First Inquiry
            </button>
        </div>
    <?php else: ?>
        <div class="row">
            <div class="col-12">
                <?php foreach ($inquiries as $inquiry): ?>
                    <div class="inquiry-card <?= $inquiry['status']; ?>">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h6 class="mb-0"><?= htmlspecialchars($inquiry['subject']); ?></h6>
                                    <span class="status-badge status-<?= $inquiry['status']; ?>">
                                        <?= ucfirst($inquiry['status']); ?>
                                    </span>
                                </div>
                                
                                <p class="text-muted mb-2"><?= htmlspecialchars(substr($inquiry['message'], 0, 150)); ?><?= strlen($inquiry['message']) > 150 ? '...' : ''; ?></p>
                                
                                <?php if ($inquiry['propertiesname']): ?>
                                    <div class="d-flex align-items-center bg-light rounded p-2 mb-2">
                                        <div class="me-3">
                                            <strong><?= htmlspecialchars($inquiry['propertiesname']); ?></strong><br>
                                            <small class="text-muted"><?= htmlspecialchars($inquiry['city'] . ', ' . $inquiry['state']); ?></small>
                                            <?php if ($inquiry['amount']): ?>
                                                <br><small class="text-primary"><?= htmlspecialchars($inquiry['currency']); ?> <?= number_format($inquiry['amount']); ?></small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="d-flex justify-content-between align-items-center mt-3">
                                    <small class="text-muted">
                                        <i class="fas fa-calendar me-1"></i>
                                        <?= date('M j, Y \a\t g:i A', strtotime($inquiry['created_at'])); ?>
                                        <?php if ($inquiry['phone']): ?>
                                            • <i class="fas fa-phone me-1"></i><?= htmlspecialchars($inquiry['phone']); ?>
                                        <?php endif; ?>
                                    </small>
                                    <div class="d-flex gap-2">
                                        <?php if ($inquiry['property_id']): ?>
                                            <a href="../property_details.php?id=<?= $inquiry['property_id']; ?>" class="btn btn-sm btn-outline-info">
                                                <i class="fas fa-home me-1"></i>View Property
                                            </a>
                                        <?php endif; ?>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this inquiry?')">
                                            <input type="hidden" name="action" value="delete_inquiry">
                                            <input type="hidden" name="inquiry_id" value="<?= $inquiry['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                <i class="fas fa-trash me-1"></i>Delete
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="d-flex justify-content-center mt-4">
                <nav aria-label="Inquiries pagination">
                    <ul class="pagination">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $page - 1; ?>">
                                    <i class="fas fa-chevron-left"></i> Previous
                                </a>
                            </li>
                        <?php endif; ?>

                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                            <li class="page-item <?= $i === $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?= $i; ?>"><?= $i; ?></a>
                            </li>
                        <?php endfor; ?>

                        <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $page + 1; ?>">
                                    Next <i class="fas fa-chevron-right"></i>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>