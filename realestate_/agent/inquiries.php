<?php
session_start();
include('../Database/connection.php');
include('../includes/route.php');
include('../includes/security.php');

// Check if user is agent
if (!isAgent()) {
    header('Location: ../login.php');
    exit();
}

$user_id = getUserId();
$message = '';
$success = false;

// Handle inquiry status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_inquiry_status'])) {
    $csrf = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!$csrf || !SecurityValidator::getInstance()->validateCSRFToken($csrf)) {
        $message = 'Invalid security token';
    } else {
        $inquiry_id = (int)$_POST['inquiry_id'];
        $new_status = $_POST['status'] ?? 'pending';
        
        // Validate status
        $allowed_statuses = ['pending', 'responded', 'closed'];
        if (!in_array($new_status, $allowed_statuses)) {
            $message = 'Invalid status';
        } else {
            // Update inquiry status (only for agent's properties)
            $update_stmt = $conn->prepare("UPDATE inquiries SET status = ? WHERE id = ? AND property_id IN (SELECT id FROM properties WHERE user_id = ?)");
            $update_stmt->bind_param("sii", $new_status, $inquiry_id, $user_id);
            
            if ($update_stmt->execute()) {
                $success = true;
                $message = 'Inquiry status updated successfully';
            } else {
                $message = 'Failed to update inquiry status';
            }
            $update_stmt->close();
        }
    }
}

// Get filter parameters
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$property_filter = isset($_GET['property']) ? (int)$_GET['property'] : 0;

// Build inquiries query (only for agent's properties)
$inquiries_query = "SELECT i.id, i.message, i.name, i.email, i.phone, i.status, i.created_at,
                           p.propertiesname, p.id as property_id,
                           u.first_name, u.last_name, u.email as user_email
                    FROM inquiries i
                    JOIN properties p ON i.property_id = p.id
                    LEFT JOIN users u ON i.client_id = u.id
                    WHERE p.user_id = ?";

$types = 'i';
$values = [$user_id];

if ($status_filter !== '') {
    $inquiries_query .= " AND i.status = ?";
    $values[] = $status_filter;
    $types .= 's';
}

if ($property_filter > 0) {
    $inquiries_query .= " AND p.id = ?";
    $values[] = $property_filter;
    $types .= 'i';
}

$inquiries_query .= " ORDER BY i.created_at DESC LIMIT 50";

$inquiries = [];
if ($stmt = $conn->prepare($inquiries_query)) {
    if (!empty($values)) {
        $refs = [];
        foreach ($values as $key => $val) $refs[$key] = &$values[$key];
        array_unshift($refs, $types);
        call_user_func_array([$stmt, 'bind_param'], $refs);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $inquiries[] = $row;
    }
    $stmt->close();
}

// Get agent's properties for filter
$agent_properties = [];
$props_query = "SELECT id, propertiesname FROM properties WHERE user_id = ? ORDER BY propertiesname";
$props_stmt = $conn->prepare($props_query);
$props_stmt->bind_param("i", $user_id);
$props_stmt->execute();
$props_result = $props_stmt->get_result();
while ($row = $props_result->fetch_assoc()) {
    $agent_properties[] = $row;
}
$props_stmt->close();

// Get stats
$total_inquiries = count($inquiries);
$pending_inquiries = count(array_filter($inquiries, function($i) { return $i['status'] === 'pending'; }));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Inquiries - Agent Dashboard</title>
    <link rel="stylesheet" href="../bootstrap-5.3.7-dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --agent-color: #7c3aed;
            --primary-color: #2563eb;
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
        }

        .main-content {
            margin-left: 260px;
            min-height: 100vh;
            padding: 2rem;
        }

        .card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }

        .inquiry-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            margin-bottom: 1rem;
            border-left: 4px solid var(--agent-color);
            transition: transform 0.3s ease;
        }

        .inquiry-card:hover {
            transform: translateX(5px);
        }

        .form-control, .form-select {
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            padding: 12px 16px;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--agent-color);
            box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.1);
        }

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

<?php include('includes/agent_nav.php'); ?>

<div class="main-content">
    <?php if ($message): ?>
        <div class="alert <?= $success ? 'alert-success' : 'alert-danger'; ?> alert-dismissible fade show">
            <?= htmlspecialchars($message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2><i class="fas fa-envelope me-2" style="color: var(--agent-color);"></i>Client Inquiries</h2>
            <p class="text-muted">Manage inquiries from potential buyers and renters</p>
        </div>
    </div>

    <!-- Statistics -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card text-center">
                <h3 class="text-primary"><?= $total_inquiries; ?></h3>
                <p class="text-muted mb-0">Total Inquiries</p>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card text-center">
                <h3 class="text-warning"><?= $pending_inquiries; ?></h3>
                <p class="text-muted mb-0">Pending Inquiries</p>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card">
        <h5 class="mb-3">Filter Inquiries</h5>
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <label for="status" class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">All Statuses</option>
                    <option value="pending" <?= $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="responded" <?= $status_filter === 'responded' ? 'selected' : ''; ?>>Responded</option>
                    <option value="closed" <?= $status_filter === 'closed' ? 'selected' : ''; ?>>Closed</option>
                </select>
            </div>
            <div class="col-md-4">
                <label for="property" class="form-label">Property</label>
                <select name="property" class="form-select">
                    <option value="">All Properties</option>
                    <?php foreach ($agent_properties as $prop): ?>
                        <option value="<?= $prop['id']; ?>" <?= $property_filter == $prop['id'] ? 'selected' : ''; ?>>
                            <?= htmlspecialchars($prop['propertiesname']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4 d-grid">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search me-2"></i>Filter
                </button>
            </div>
        </form>
    </div>

    <!-- Inquiries List -->
    <div class="card">
        <h5 class="mb-3">Inquiries (<?= count($inquiries); ?>)</h5>
        
        <?php if (!empty($inquiries)): ?>
            <div class="row g-3">
                <?php foreach ($inquiries as $inquiry): ?>
                    <div class="col-12">
                        <div class="inquiry-card">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <h6 class="mb-1">
                                        <i class="fas fa-user me-2"></i>
                                        <?= htmlspecialchars($inquiry['name'] ?: ($inquiry['first_name'] . ' ' . $inquiry['last_name'])); ?>
                                    </h6>
                                    <p class="text-muted small mb-1">
                                        <i class="fas fa-envelope me-1"></i><?= htmlspecialchars($inquiry['email'] ?: $inquiry['user_email']); ?>
                                        <?php if ($inquiry['phone']): ?>
                                            <span class="ms-3"><i class="fas fa-phone me-1"></i><?= htmlspecialchars($inquiry['phone']); ?></span>
                                        <?php endif; ?>
                                    </p>
                                </div>
                                <span class="badge bg-<?= $inquiry['status'] === 'pending' ? 'warning' : ($inquiry['status'] === 'responded' ? 'success' : 'secondary'); ?>">
                                    <?= ucfirst($inquiry['status']); ?>
                                </span>
                            </div>
                            
                            <div class="mb-3">
                                <strong>Property:</strong> 
                                <a href="../property_details.php?id=<?= $inquiry['property_id']; ?>" target="_blank" class="text-decoration-none">
                                    <?= htmlspecialchars($inquiry['propertiesname']); ?>
                                </a>
                            </div>
                            
                            <div class="mb-3">
                                <strong>Message:</strong>
                                <p class="mb-0"><?= nl2br(htmlspecialchars($inquiry['message'])); ?></p>
                            </div>
                            
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-muted">
                                    <i class="fas fa-clock me-1"></i><?= date('M j, Y g:i A', strtotime($inquiry['created_at'])); ?>
                                </small>
                                <div class="btn-group">
                                    <form method="POST" style="display: inline;" class="me-2">
                                        <input type="hidden" name="csrf_token" value="<?= SecurityValidator::getInstance()->generateCSRFToken(); ?>">
                                        <input type="hidden" name="inquiry_id" value="<?= $inquiry['id']; ?>">
                                        <select name="status" class="form-select form-select-sm" onchange="this.form.submit()" style="width: auto; display: inline-block;">
                                            <option value="pending" <?= $inquiry['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                            <option value="responded" <?= $inquiry['status'] === 'responded' ? 'selected' : ''; ?>>Responded</option>
                                            <option value="closed" <?= $inquiry['status'] === 'closed' ? 'selected' : ''; ?>>Closed</option>
                                        </select>
                                        <input type="hidden" name="update_inquiry_status" value="1">
                                    </form>
                                    <a href="mailto:<?= htmlspecialchars($inquiry['email'] ?: $inquiry['user_email']); ?>?subject=Re: Inquiry about <?= urlencode($inquiry['propertiesname']); ?>" 
                                       class="btn btn-sm btn-primary">
                                        <i class="fas fa-reply me-1"></i>Reply
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="fas fa-envelope-open fa-3x text-muted mb-3"></i>
                <h5>No Inquiries Found</h5>
                <p class="text-muted">Inquiries from clients will appear here.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

