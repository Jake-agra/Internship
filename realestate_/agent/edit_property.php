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
$property_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$property_id) {
    header('Location: properties.php');
    exit();
}

// Verify property belongs to this agent
$verify_stmt = $conn->prepare("SELECT id FROM properties WHERE id = ? AND user_id = ?");
$verify_stmt->bind_param("ii", $property_id, $user_id);
$verify_stmt->execute();
$verify_result = $verify_stmt->get_result();

if ($verify_result->num_rows === 0) {
    header('Location: properties.php');
    exit();
}
$verify_stmt->close();

$message = '';
$success = false;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_property'])) {
    $csrf = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!$csrf || !SecurityValidator::getInstance()->validateCSRFToken($csrf)) {
        $message = 'Invalid security token';
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
        $status = $_POST['status'] ?? 'pending';
        $address_details = trim($_POST['address_details'] ?? '');
        $video_url = trim($_POST['video_url'] ?? '');
        $virtual_tour_url = trim($_POST['virtual_tour_url'] ?? '');
        
        if (empty($propertiesname) || empty($description) || $property_type_id <= 0 || $price_amount <= 0 || empty($city)) {
            $message = 'Please fill in all required fields.';
        } else {
            try {
                $conn->begin_transaction();
                
                // Get current property to update price_id
                $current_prop = $conn->prepare("SELECT price_id, location_id FROM properties WHERE id = ? AND user_id = ?");
                $current_prop->bind_param("ii", $property_id, $user_id);
                $current_prop->execute();
                $current_result = $current_prop->get_result();
                $current_data = $current_result->fetch_assoc();
                $current_prop->close();
                
                // Update or create location
                $location_id = $current_data['location_id'];
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
                
                // Update price
                $price_id = $current_data['price_id'];
                $price_update = $conn->prepare("UPDATE prices SET amount = ?, currency = ?, price_type = ? WHERE id = ?");
                $price_update->bind_param("dssi", $price_amount, $price_currency, $price_type, $price_id);
                $price_update->execute();
                $price_update->close();
                
                // Generate slug
                $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $propertiesname)));
                
                // Update property (agents cannot change is_featured - only admins can)
                $property_update = $conn->prepare("UPDATE properties SET propertiesname = ?, slug = ?, description = ?, property_type_id = ?, location_id = ?, status = ?, bedrooms = ?, bathrooms = ?, area_sqft = ?, year_built = ?, parking_spaces = ?, address_details = ?, video_url = ?, virtual_tour_url = ? WHERE id = ? AND user_id = ?");
                $property_update->bind_param("sssiiisiiiiisssii", $propertiesname, $slug, $description, $property_type_id, $location_id, $status, $bedrooms, $bathrooms, $area_sqft, $year_built, $parking_spaces, $address_details, $video_url, $virtual_tour_url, $property_id, $user_id);
                $property_update->execute();
                $property_update->close();
                
                $conn->commit();
                $success = true;
                $message = 'Property updated successfully!';
                
            } catch (Exception $e) {
                $conn->rollback();
                $message = 'Error updating property: ' . $e->getMessage();
            }
        }
    }
}

// Get property data
$property_query = "SELECT p.*, pt.type_name, pr.amount, pr.currency, pr.price_type, pr.id as price_id,
                   l.city, l.region, l.country, l.postal_code
                   FROM properties p
                   LEFT JOIN property_types pt ON p.property_type_id = pt.id
                   LEFT JOIN prices pr ON p.price_id = pr.id
                   LEFT JOIN locations l ON p.location_id = l.id
                   WHERE p.id = ? AND p.user_id = ?";

$stmt = $conn->prepare($property_query);
$stmt->bind_param("ii", $property_id, $user_id);
$stmt->execute();
$property = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$property) {
    header('Location: properties.php');
    exit();
}

// Get property types
$property_types = [];
if ($type_result = $conn->query("SELECT id, type_name FROM property_types WHERE is_active = 1 ORDER BY type_name")) {
    $property_types = $type_result->fetch_all(MYSQLI_ASSOC);
    $type_result->free();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Property - Agent Dashboard</title>
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
            <h2><i class="fas fa-edit me-2" style="color: var(--agent-color);"></i>Edit Property</h2>
            <p class="text-muted">Update property information</p>
        </div>
        <a href="properties.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i>Back to Properties
        </a>
    </div>

    <?php if ($message): ?>
        <div class="alert <?= $success ? 'alert-success' : 'alert-danger'; ?> alert-dismissible fade show" role="alert">
            <i class="fas fa-<?= $success ? 'check-circle' : 'exclamation-circle'; ?> me-2"></i><?= htmlspecialchars($message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <form method="POST" id="propertyForm">
                <input type="hidden" name="csrf_token" value="<?= SecurityValidator::getInstance()->generateCSRFToken(); ?>">
                
                <div class="row g-4">
                    <!-- Basic Information -->
                    <div class="col-12">
                        <h5 class="mb-3"><i class="fas fa-info-circle me-2"></i>Basic Information</h5>
                    </div>
                    
                    <div class="col-md-8">
                        <label for="propertiesname" class="form-label">Property Name *</label>
                        <input type="text" class="form-control" id="propertiesname" name="propertiesname" 
                               value="<?= htmlspecialchars($property['propertiesname']); ?>" required>
                    </div>

                    <div class="col-md-4">
                        <label for="property_type_id" class="form-label">Property Type *</label>
                        <select class="form-select" id="property_type_id" name="property_type_id" required>
                            <option value="">Select Type</option>
                            <?php foreach ($property_types as $type): ?>
                                <option value="<?= $type['id']; ?>" <?= $property['property_type_id'] == $type['id'] ? 'selected' : ''; ?>>
                                    <?= htmlspecialchars($type['type_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-12">
                        <label for="description" class="form-label">Description *</label>
                        <textarea class="form-control" id="description" name="description" rows="4" required><?= htmlspecialchars($property['description']); ?></textarea>
                    </div>

                    <!-- Pricing -->
                    <div class="col-12">
                        <h5 class="mb-3 mt-4"><i class="fas fa-dollar-sign me-2"></i>Pricing</h5>
                    </div>

                    <div class="col-md-4">
                        <label for="price_amount" class="form-label">Price Amount *</label>
                        <input type="number" class="form-control" id="price_amount" name="price_amount" 
                               value="<?= htmlspecialchars($property['amount']); ?>" step="0.01" required>
                    </div>

                    <div class="col-md-4">
                        <label for="price_currency" class="form-label">Currency</label>
                        <select class="form-select" id="price_currency" name="price_currency">
                            <option value="USD" <?= $property['currency'] == 'USD' ? 'selected' : ''; ?>>USD ($)</option>
                            <option value="EUR" <?= $property['currency'] == 'EUR' ? 'selected' : ''; ?>>EUR (€)</option>
                            <option value="GBP" <?= $property['currency'] == 'GBP' ? 'selected' : ''; ?>>GBP (£)</option>
                            <option value="CAD" <?= $property['currency'] == 'CAD' ? 'selected' : ''; ?>>CAD (C$)</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label for="price_type" class="form-label">Price Type</label>
                        <select class="form-select" id="price_type" name="price_type">
                            <option value="sale" <?= $property['price_type'] == 'sale' ? 'selected' : ''; ?>>For Sale</option>
                            <option value="rent_monthly" <?= $property['price_type'] == 'rent_monthly' ? 'selected' : ''; ?>>Monthly Rent</option>
                            <option value="rent_weekly" <?= $property['price_type'] == 'rent_weekly' ? 'selected' : ''; ?>>Weekly Rent</option>
                            <option value="rent_daily" <?= $property['price_type'] == 'rent_daily' ? 'selected' : ''; ?>>Daily Rent</option>
                        </select>
                    </div>

                    <!-- Location -->
                    <div class="col-12">
                        <h5 class="mb-3 mt-4"><i class="fas fa-map-marker-alt me-2"></i>Location</h5>
                    </div>

                    <div class="col-md-4">
                        <label for="city" class="form-label">City *</label>
                        <input type="text" class="form-control" id="city" name="city" 
                               value="<?= htmlspecialchars($property['city']); ?>" required>
                    </div>

                    <div class="col-md-4">
                        <label for="region" class="form-label">Region/State</label>
                        <input type="text" class="form-control" id="region" name="region" 
                               value="<?= htmlspecialchars($property['region'] ?? ''); ?>">
                    </div>

                    <div class="col-md-4">
                        <label for="country" class="form-label">Country</label>
                        <input type="text" class="form-control" id="country" name="country" 
                               value="<?= htmlspecialchars($property['country'] ?? 'USA'); ?>">
                    </div>

                    <div class="col-12">
                        <label for="address_details" class="form-label">Full Address</label>
                        <textarea class="form-control" id="address_details" name="address_details" rows="2"><?= htmlspecialchars($property['address_details'] ?? ''); ?></textarea>
                    </div>

                    <!-- Property Details -->
                    <div class="col-12">
                        <h5 class="mb-3 mt-4"><i class="fas fa-home me-2"></i>Property Details</h5>
                    </div>

                    <div class="col-md-3">
                        <label for="bedrooms" class="form-label">Bedrooms</label>
                        <input type="number" class="form-control" id="bedrooms" name="bedrooms" 
                               value="<?= htmlspecialchars($property['bedrooms']); ?>" min="0">
                    </div>

                    <div class="col-md-3">
                        <label for="bathrooms" class="form-label">Bathrooms</label>
                        <input type="number" class="form-control" id="bathrooms" name="bathrooms" 
                               value="<?= htmlspecialchars($property['bathrooms']); ?>" min="0">
                    </div>

                    <div class="col-md-3">
                        <label for="area_sqft" class="form-label">Area (sqft)</label>
                        <input type="number" class="form-control" id="area_sqft" name="area_sqft" 
                               value="<?= htmlspecialchars($property['area_sqft']); ?>" min="0">
                    </div>

                    <div class="col-md-3">
                        <label for="year_built" class="form-label">Year Built</label>
                        <input type="number" class="form-control" id="year_built" name="year_built" 
                               value="<?= htmlspecialchars($property['year_built'] ?? ''); ?>" min="1800" max="<?= date('Y'); ?>">
                    </div>

                    <div class="col-md-4">
                        <label for="parking_spaces" class="form-label">Parking Spaces</label>
                        <input type="number" class="form-control" id="parking_spaces" name="parking_spaces" 
                               value="<?= htmlspecialchars($property['parking_spaces']); ?>" min="0">
                    </div>

                    <div class="col-md-4">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="available" <?= $property['status'] == 'available' ? 'selected' : ''; ?>>Available</option>
                            <option value="pending" <?= $property['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="sold" <?= $property['status'] == 'sold' ? 'selected' : ''; ?>>Sold</option>
                            <option value="rented" <?= $property['status'] == 'rented' ? 'selected' : ''; ?>>Rented</option>
                            <option value="inactive" <?= $property['status'] == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>

                    <!-- Media URLs -->
                    <div class="col-12">
                        <h5 class="mb-3 mt-4"><i class="fas fa-video me-2"></i>Media</h5>
                    </div>

                    <div class="col-md-6">
                        <label for="video_url" class="form-label">Video URL</label>
                        <input type="url" class="form-control" id="video_url" name="video_url" 
                               value="<?= htmlspecialchars($property['video_url'] ?? ''); ?>" placeholder="https://youtube.com/watch?v=...">
                    </div>

                    <div class="col-md-6">
                        <label for="virtual_tour_url" class="form-label">Virtual Tour URL</label>
                        <input type="url" class="form-control" id="virtual_tour_url" name="virtual_tour_url" 
                               value="<?= htmlspecialchars($property['virtual_tour_url'] ?? ''); ?>" placeholder="https://...">
                    </div>

                    <!-- Submit -->
                    <div class="col-12 mt-5">
                        <hr>
                        <div class="d-flex justify-content-end gap-2">
                            <a href="properties.php" class="btn btn-outline-secondary">Cancel</a>
                            <button type="submit" name="update_property" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Update Property
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

