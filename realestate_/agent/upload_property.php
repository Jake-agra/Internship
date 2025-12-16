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
$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!$csrf || !SecurityValidator::getInstance()->validateCSRFToken($csrf)) {
        $error_message = 'Invalid security token. Please refresh and try again.';
    } else {
        $propertiesname = trim($_POST['propertiesname']);
        $description = trim($_POST['description']);
        $property_type_id = (int)$_POST['property_type_id'];
        $price_amount = (float)$_POST['price_amount'];
        $price_currency = $_POST['price_currency'] ?? 'USD';
        $price_type = $_POST['price_type'] ?? 'sale';
        $city = trim($_POST['city']);
        $region = trim($_POST['region'] ?? '');
        $country = trim($_POST['country'] ?? 'USA');
        $bedrooms = (int)($_POST['bedrooms'] ?? 0);
        $bathrooms = (int)($_POST['bathrooms'] ?? 0);
        $area_sqft = (int)($_POST['area_sqft'] ?? 0);
        $year_built = !empty($_POST['year_built']) ? (int)$_POST['year_built'] : null;
        $parking_spaces = (int)($_POST['parking_spaces'] ?? 0);
        $status = $_POST['status'] ?? 'pending'; // Agents submit as pending for admin approval
        $address_details = trim($_POST['address_details'] ?? '');
        $video_url = trim($_POST['video_url'] ?? '');
        $virtual_tour_url = trim($_POST['virtual_tour_url'] ?? '');
        
        // Validate required fields
        if (empty($propertiesname) || empty($description) || $property_type_id <= 0 || $price_amount <= 0 || empty($city)) {
            $error_message = 'Please fill in all required fields.';
        } else {
            try {
                $conn->begin_transaction();
                
                // Check if location exists or create new one
                $location_stmt = $conn->prepare("SELECT id FROM locations WHERE city = ? AND region = ? AND country = ?");
                $location_stmt->bind_param("sss", $city, $region, $country);
                $location_stmt->execute();
                $location_result = $location_stmt->get_result();
                
                if ($location_result->num_rows > 0) {
                    $location_id = $location_result->fetch_assoc()['id'];
                } else {
                    $location_insert = $conn->prepare("INSERT INTO locations (city, region, country) VALUES (?, ?, ?)");
                    $location_insert->bind_param("sss", $city, $region, $country);
                    $location_insert->execute();
                    $location_id = $conn->insert_id;
                    $location_insert->close();
                }
                $location_stmt->close();
                
                // Insert price
                $price_stmt = $conn->prepare("INSERT INTO prices (amount, currency, price_type) VALUES (?, ?, ?)");
                $price_stmt->bind_param("dss", $price_amount, $price_currency, $price_type);
                $price_stmt->execute();
                $price_id = $conn->insert_id;
                $price_stmt->close();
                
                // Generate slug
                $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $propertiesname)));
                
                // Insert property (agents submit as pending for admin approval)
                $property_stmt = $conn->prepare("INSERT INTO properties (propertiesname, slug, description, price_id, user_id, property_type_id, location_id, status, bedrooms, bathrooms, area_sqft, year_built, parking_spaces, is_featured, address_details, video_url, virtual_tour_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, ?, ?, ?)");
                $property_stmt->bind_param("sssiiiisiiiiisss", $propertiesname, $slug, $description, $price_id, $user_id, $property_type_id, $location_id, $status, $bedrooms, $bathrooms, $area_sqft, $year_built, $parking_spaces, $address_details, $video_url, $virtual_tour_url);
                $property_stmt->execute();
                $property_id = $conn->insert_id;
                $property_stmt->close();
                
                $conn->commit();
                $success_message = 'Property submitted successfully! It will be reviewed by an administrator before being published.';
                
                // Redirect to property management page
                header("Location: properties.php?success=1");
                exit();
                
            } catch (Exception $e) {
                $conn->rollback();
                $error_message = 'Error adding property: ' . $e->getMessage();
            }
        }
    }
}

// Get property types
$property_types = [];
if ($result = $conn->query("SELECT id, type_name FROM property_types WHERE is_active = 1 ORDER BY type_name")) {
    $property_types = $result->fetch_all(MYSQLI_ASSOC);
    $result->free();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Property - Agent Dashboard</title>
    <link rel="stylesheet" href="../bootstrap-5.3.7-dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2563eb;
            --secondary-color: #1e40af;
            --agent-color: #7c3aed;
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

        .sidebar-header {
            padding: 1.5rem;
            border-bottom: 1px solid #e5e7eb;
            background: linear-gradient(135deg, var(--agent-color), #8b5cf6);
            color: white;
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
            border-left: 3px solid transparent;
        }

        .nav-link:hover,
        .nav-link.active {
            background: var(--light-color);
            color: var(--agent-color);
            border-left-color: var(--agent-color);
        }

        .nav-link i {
            width: 20px;
            margin-right: 0.75rem;
        }

        .main-content {
            margin-left: 260px;
            min-height: 100vh;
            padding: 2rem;
        }

        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .form-control, .form-select {
            border-radius: 8px;
            border: 2px solid #e5e7eb;
            padding: 12px 15px;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--agent-color);
            box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.1);
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--agent-color), #8b5cf6);
            border: none;
            border-radius: 8px;
            padding: 12px 30px;
            font-weight: 500;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(124, 58, 237, 0.3);
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
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2><i class="fas fa-plus-circle me-2" style="color: var(--agent-color);"></i>Upload New Property</h2>
            <p class="text-muted">Add a new property listing to your portfolio</p>
        </div>
        <a href="properties.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i>Back to Properties
        </a>
    </div>

    <?php if ($success_message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($success_message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($error_message): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($error_message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <form method="POST" enctype="multipart/form-data" id="propertyForm">
                <input type="hidden" name="csrf_token" value="<?= SecurityValidator::getInstance()->generateCSRFToken(); ?>">
                
                <div class="row g-4">
                    <!-- Basic Information -->
                    <div class="col-12">
                        <h5 class="mb-3"><i class="fas fa-info-circle me-2"></i>Basic Information</h5>
                    </div>
                    
                    <div class="col-md-8">
                        <label for="propertiesname" class="form-label">Property Name *</label>
                        <input type="text" class="form-control" id="propertiesname" name="propertiesname" required>
                    </div>

                    <div class="col-md-4">
                        <label for="property_type_id" class="form-label">Property Type *</label>
                        <select class="form-select" id="property_type_id" name="property_type_id" required>
                            <option value="">Select Type</option>
                            <?php foreach ($property_types as $type): ?>
                                <option value="<?= $type['id']; ?>"><?= htmlspecialchars($type['type_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-12">
                        <label for="description" class="form-label">Description *</label>
                        <textarea class="form-control" id="description" name="description" rows="4" required></textarea>
                    </div>

                    <!-- Pricing -->
                    <div class="col-12">
                        <h5 class="mb-3 mt-4"><i class="fas fa-dollar-sign me-2"></i>Pricing</h5>
                    </div>

                    <div class="col-md-4">
                        <label for="price_amount" class="form-label">Price Amount *</label>
                        <input type="number" class="form-control" id="price_amount" name="price_amount" step="0.01" required>
                    </div>

                    <div class="col-md-4">
                        <label for="price_currency" class="form-label">Currency</label>
                        <select class="form-select" id="price_currency" name="price_currency">
                            <option value="USD">USD ($)</option>
                            <option value="EUR">EUR (€)</option>
                            <option value="GBP">GBP (£)</option>
                            <option value="CAD">CAD (C$)</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label for="price_type" class="form-label">Price Type</label>
                        <select class="form-select" id="price_type" name="price_type">
                            <option value="sale">For Sale</option>
                            <option value="rent_monthly">Monthly Rent</option>
                            <option value="rent_weekly">Weekly Rent</option>
                            <option value="rent_daily">Daily Rent</option>
                        </select>
                    </div>

                    <!-- Location -->
                    <div class="col-12">
                        <h5 class="mb-3 mt-4"><i class="fas fa-map-marker-alt me-2"></i>Location</h5>
                    </div>

                    <div class="col-md-4">
                        <label for="city" class="form-label">City *</label>
                        <input type="text" class="form-control" id="city" name="city" required>
                    </div>

                    <div class="col-md-4">
                        <label for="region" class="form-label">Region/State</label>
                        <input type="text" class="form-control" id="region" name="region">
                    </div>

                    <div class="col-md-4">
                        <label for="country" class="form-label">Country</label>
                        <input type="text" class="form-control" id="country" name="country" value="USA">
                    </div>

                    <div class="col-12">
                        <label for="address_details" class="form-label">Full Address</label>
                        <textarea class="form-control" id="address_details" name="address_details" rows="2"></textarea>
                    </div>

                    <!-- Property Details -->
                    <div class="col-12">
                        <h5 class="mb-3 mt-4"><i class="fas fa-home me-2"></i>Property Details</h5>
                    </div>

                    <div class="col-md-3">
                        <label for="bedrooms" class="form-label">Bedrooms</label>
                        <input type="number" class="form-control" id="bedrooms" name="bedrooms" min="0" value="0">
                    </div>

                    <div class="col-md-3">
                        <label for="bathrooms" class="form-label">Bathrooms</label>
                        <input type="number" class="form-control" id="bathrooms" name="bathrooms" min="0" value="0">
                    </div>

                    <div class="col-md-3">
                        <label for="area_sqft" class="form-label">Area (sqft)</label>
                        <input type="number" class="form-control" id="area_sqft" name="area_sqft" min="0">
                    </div>

                    <div class="col-md-3">
                        <label for="year_built" class="form-label">Year Built</label>
                        <input type="number" class="form-control" id="year_built" name="year_built" min="1800" max="<?= date('Y'); ?>">
                    </div>

                    <div class="col-md-4">
                        <label for="parking_spaces" class="form-label">Parking Spaces</label>
                        <input type="number" class="form-control" id="parking_spaces" name="parking_spaces" min="0" value="0">
                    </div>

                    <div class="col-md-4">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="pending" selected>Pending Approval</option>
                            <option value="available">Available</option>
                        </select>
                        <small class="text-muted">Note: Properties require admin approval before being published.</small>
                    </div>

                    <!-- Media URLs -->
                    <div class="col-12">
                        <h5 class="mb-3 mt-4"><i class="fas fa-video me-2"></i>Media</h5>
                    </div>

                    <div class="col-md-6">
                        <label for="video_url" class="form-label">Video URL (YouTube or direct link)</label>
                        <input type="url" class="form-control" id="video_url" name="video_url" placeholder="https://youtube.com/watch?v=...">
                    </div>

                    <div class="col-md-6">
                        <label for="virtual_tour_url" class="form-label">Virtual Tour URL</label>
                        <input type="url" class="form-control" id="virtual_tour_url" name="virtual_tour_url" placeholder="https://...">
                    </div>

                    <!-- Submit -->
                    <div class="col-12 mt-5">
                        <hr>
                        <div class="d-flex justify-content-end gap-2">
                            <a href="properties.php" class="btn btn-outline-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Submit Property for Review
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

