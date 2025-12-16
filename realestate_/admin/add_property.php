<?php
session_start();
include('../Database/connection.php');
include('../includes/route.php');

// Check if user is admin or agent
if (!isAdmin() && !isAgent()) {
    header('Location: ../login.php');
    exit();
}

$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $propertiesname = trim($_POST['propertiesname']);
    $description = trim($_POST['description']);
    $property_type_id = (int)$_POST['property_type_id'];
    $price_amount = (float)$_POST['price_amount'];
    $price_currency = $_POST['price_currency'];
    $price_type = $_POST['price_type'];
    $city = trim($_POST['city']);
    $region = trim($_POST['region']);
    $country = trim($_POST['country']);
    $bedrooms = (int)$_POST['bedrooms'];
    $bathrooms = (int)$_POST['bathrooms'];
    $area_sqft = (int)$_POST['area_sqft'];
    $year_built = (int)$_POST['year_built'];
    $parking_spaces = (int)$_POST['parking_spaces'];
    $status = $_POST['status'];
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    $address_details = trim($_POST['address_details']);
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
            
            // Insert property with video URLs
            $property_stmt = $conn->prepare("INSERT INTO properties (propertiesname, slug, description, price_id, user_id, property_type_id, location_id, status, bedrooms, bathrooms, area_sqft, year_built, parking_spaces, is_featured, address_details, video_url, virtual_tour_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $property_stmt->bind_param("sssiiiisiiiiisss", $propertiesname, $slug, $description, $price_id, $_SESSION['user_id'], $property_type_id, $location_id, $status, $bedrooms, $bathrooms, $area_sqft, $year_built, $parking_spaces, $is_featured, $address_details, $video_url, $virtual_tour_url);
            $property_stmt->execute();
            $property_id = $conn->insert_id;
            $property_stmt->close();
            
            $conn->commit();
            $success_message = 'Property added successfully!';
            
        } catch (Exception $e) {
            $conn->rollback();
            $error_message = 'Error adding property: ' . $e->getMessage();
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
    <title>Add Property - Admin Panel</title>
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

        .main-content {
            margin-left: 250px;
            min-height: 100vh;
        }

        .content-area {
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
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
            border-radius: 8px;
            padding: 12px 30px;
            font-weight: 500;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(37, 99, 235, 0.3);
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

<?php include('includes/admin_nav.php'); ?>

<div class="main-content">
    <div class="content-area">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2>Add New Property</h2>
                <p class="text-muted">Create a new property listing</p>
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
                <form method="POST" enctype="multipart/form-data">
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
                                <option value="available">Available</option>
                                <option value="pending">Pending</option>
                                <option value="sold">Sold</option>
                                <option value="rented">Rented</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>

                        <!-- Media Section -->
                        <div class="col-12">
                            <h5 class="mb-3 mt-4"><i class="fas fa-video me-2"></i>Media</h5>
                        </div>

                        <div class="col-12">
                            <label for="video_url" class="form-label">Video Tour URL</label>
                            <input type="url" class="form-control" id="video_url" name="video_url" placeholder="https://www.youtube.com/watch?v=... or https://example.com/video.mp4">
                            <small class="text-muted">YouTube URL or direct video file URL (MP4)</small>
                        </div>

                        <div class="col-12">
                            <label for="virtual_tour_url" class="form-label">Virtual Tour URL (360°)</label>
                            <input type="url" class="form-control" id="virtual_tour_url" name="virtual_tour_url" placeholder="https://example.com/virtual-tour">
                            <small class="text-muted">Matterport or other 360° virtual tour platform URL</small>
                        </div>

                        <div class="col-md-4">
                            <div class="form-check form-switch mt-4">
                                <input class="form-check-input" type="checkbox" id="is_featured" name="is_featured">
                                <label class="form-check-label" for="is_featured">
                                    <i class="fas fa-star text-warning me-1"></i>Featured Property
                                </label>
                            </div>
                        </div>

                        <!-- Submit -->
                        <div class="col-12 mt-5">
                            <hr>
                            <div class="d-flex justify-content-end gap-2">
                                <a href="properties.php" class="btn btn-outline-secondary">Cancel</a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Add Property
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>