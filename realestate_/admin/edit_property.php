<?php
session_start();
include('../Database/connection.php');
include('../includes/route.php');

// Check if user is admin or agent
if (!isAdmin() && !isAgent()) {
    header('Location: ../login.php');
    exit();
}

$property_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$property_id) {
    header('Location: properties.php');
    exit();
}

// If user is agent, verify property ownership
$user_id = getUserId();
if (isAgent() && !isAdmin()) {
    $verify_stmt = $conn->prepare("SELECT id FROM properties WHERE id = ? AND user_id = ?");
    $verify_stmt->bind_param("ii", $property_id, $user_id);
    $verify_stmt->execute();
    $verify_result = $verify_stmt->get_result();
    
    if ($verify_result->num_rows === 0) {
        $verify_stmt->close();
        header('Location: ../agent/properties.php');
        exit();
    }
    $verify_stmt->close();
}

$message = '';
$success = false;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_property'])) {
    // [Update logic similar to add_property.php but for editing]
    $property_name = trim($_POST['property_name']);
    $description = trim($_POST['description']);
    $price_amount = (float)$_POST['price_amount'];
    // ... other fields
    
    // Update database logic here
    $success = true;
    $message = 'Property updated successfully!';
}

// Get property data
$stmt = $conn->prepare("
    SELECT p.*, pt.type_name, pr.amount, pr.currency, pr.price_type,
           l.city, l.region, l.country, l.postal_code
    FROM properties p
    LEFT JOIN property_types pt ON p.property_type_id = pt.id
    LEFT JOIN prices pr ON p.price_id = pr.id
    LEFT JOIN locations l ON p.location_id = l.id
    WHERE p.id = ?
");
$stmt->bind_param("i", $property_id);
$stmt->execute();
$property = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$property) {
    header('Location: properties.php');
    exit();
}

// Get property types
$property_types = [];
if ($type_result = $conn->query("SELECT id, type_name FROM property_types ORDER BY type_name")) {
    while ($r = $type_result->fetch_assoc()) {
        $property_types[] = $r;
    }
    $type_result->free();
}

// Parse current images
$current_images = [];
if (!empty($property['images'])) {
    $current_images = json_decode($property['images'], true) ?: [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Property - Admin Panel</title>
    <link rel="stylesheet" href="../bootstrap-5.3.7-dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../css/main.css">
    
    <style>
        /* Image Upload Styles */
        .upload-area {
            border: 3px dashed var(--border-color);
            border-radius: var(--radius-lg);
            padding: 3rem;
            text-align: center;
            background: var(--light-color);
            transition: all var(--transition-normal);
            cursor: pointer;
        }

        .upload-area.dragover {
            border-color: var(--primary-color);
            background: rgba(37, 99, 235, 0.05);
        }

        .image-preview {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 2rem;
        }

        .image-item {
            position: relative;
            border-radius: var(--radius-md);
            overflow: hidden;
            box-shadow: var(--shadow-md);
            background: var(--white-color);
        }

        .image-item img {
            width: 100%;
            height: 150px;
            object-fit: cover;
        }

        .image-controls {
            position: absolute;
            top: 0.5rem;
            right: 0.5rem;
            display: flex;
            gap: 0.25rem;
        }

        .image-btn {
            background: rgba(0, 0, 0, 0.7);
            color: white;
            border: none;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all var(--transition-fast);
        }

        .image-btn:hover {
            background: rgba(0, 0, 0, 0.9);
        }

        .main-image-badge {
            position: absolute;
            top: 0.5rem;
            left: 0.5rem;
            background: var(--warning-color);
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: var(--radius-sm);
            font-size: 0.75rem;
            font-weight: 600;
        }
    </style>
</head>
<body>

<!-- Sidebar Navigation -->
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
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a href="properties.php" class="nav-link active">
                    <i class="fas fa-building"></i> Properties
                </a>
            </li>
            <li class="nav-item">
                <a href="image_manager.php?property_id=<?= $property_id; ?>" class="nav-link">
                    <i class="fas fa-images"></i> Manage Images
                </a>
            </li>
            <li class="nav-item">
                <a href="add_property.php" class="nav-link">
                    <i class="fas fa-plus"></i> Add Property
                </a>
            </li>
            <li class="nav-item">
                <a href="users.php" class="nav-link">
                    <i class="fas fa-users"></i> Users
                </a>
            </li>
            <li class="nav-item">
                <a href="../logout.php" class="nav-link text-danger">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </li>
        </ul>
    </div>
</nav>

<!-- Main Content -->
<div class="main-content">
    <!-- Top Navbar -->
    <div class="top-navbar">
        <div>
            <h4 class="mb-0">Edit Property</h4>
            <p class="text-muted mb-0">Update property information and manage images</p>
        </div>
        <div class="d-flex align-items-center">
            <a href="image_manager.php?property_id=<?= $property_id; ?>" class="btn btn-outline-primary me-2">
                <i class="fas fa-images me-2"></i>Advanced Image Manager
            </a>
            <a href="properties.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back
            </a>
        </div>
    </div>

    <!-- Content Area -->
    <div class="content-area">
        <?php if ($message): ?>
            <div class="alert <?= $success ? 'alert-success' : 'alert-danger'; ?> alert-dismissible fade show">
                <?= htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Property Images Section -->
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="mb-4"><i class="fas fa-images me-2"></i>Property Images</h5>

                <!-- Upload Area -->
                <div class="upload-area" id="uploadArea">
                    <i class="fas fa-cloud-upload-alt" style="font-size: 3rem; color: var(--text-muted); margin-bottom: 1rem;"></i>
                    <h5>Upload Property Images</h5>
                    <p class="text-muted mb-3">Drag and drop images here or click to browse</p>
                    <input type="file" id="imageInput" accept="image/*" multiple style="display: none;">
                    <button type="button" class="btn btn-primary" onclick="document.getElementById('imageInput').click();">
                        <i class="fas fa-upload me-2"></i>Choose Images
                    </button>
                    <div class="mt-2">
                        <small class="text-muted">Supports: JPEG, PNG, GIF, WebP • Max size: 5MB per file</small>
                    </div>
                </div>

                <!-- Current Images -->
                <div id="imagePreview" class="image-preview">
                    <?php foreach ($current_images as $index => $image): ?>
                        <div class="image-item" data-filename="<?= htmlspecialchars($image['filename']); ?>">
                            <?php if ($index === 0): ?>
                                <div class="main-image-badge">Main Image</div>
                            <?php endif; ?>
                            <img src="../<?= htmlspecialchars($image['path']); ?>" alt="Property Image">
                            <div class="image-controls">
                                <?php if ($index !== 0): ?>
                                    <button type="button" class="image-btn" onclick="setMainImage('<?= htmlspecialchars($image['filename']); ?>')" title="Set as main">
                                        <i class="fas fa-star"></i>
                                    </button>
                                <?php endif; ?>
                                <button type="button" class="image-btn" onclick="deleteImage('<?= htmlspecialchars($image['filename']); ?>')" title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Save Images Button -->
                <div class="mt-3 text-end">
                    <button type="button" id="saveImages" class="btn btn-success" onclick="savePropertyImages()" style="display: none;">
                        <i class="fas fa-save me-2"></i>Save Image Changes
                    </button>
                </div>
            </div>
        </div>

        <!-- Property Details Form -->
        <form method="POST">
            <div class="card">
                <div class="card-body">
                    <h5 class="mb-4"><i class="fas fa-info-circle me-2"></i>Property Information</h5>
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label for="property_name" class="form-label">Property Name *</label>
                            <input type="text" class="form-control" id="property_name" name="property_name" 
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
                        <div class="col-md-4">
                            <label for="price_amount" class="form-label">Price Amount *</label>
                            <input type="number" class="form-control" id="price_amount" name="price_amount" 
                                   step="0.01" min="0" value="<?= $property['amount']; ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label for="city" class="form-label">City *</label>
                            <input type="text" class="form-control" id="city" name="city" 
                                   value="<?= htmlspecialchars($property['city']); ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label for="region" class="form-label">Region/State *</label>
                            <input type="text" class="form-control" id="region" name="region" 
                                   value="<?= htmlspecialchars($property['region']); ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label for="bedrooms" class="form-label">Bedrooms</label>
                            <input type="number" class="form-control" id="bedrooms" name="bedrooms" 
                                   min="0" value="<?= $property['bedrooms']; ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="bathrooms" class="form-label">Bathrooms</label>
                            <input type="number" class="form-control" id="bathrooms" name="bathrooms" 
                                   min="0" step="0.5" value="<?= $property['bathrooms']; ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="area_sqft" class="form-label">Area (sq ft)</label>
                            <input type="number" class="form-control" id="area_sqft" name="area_sqft" 
                                   min="0" value="<?= $property['area_sqft']; ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="1" <?= $property['status'] == 1 ? 'selected' : ''; ?>>Available</option>
                                <option value="0" <?= $property['status'] == 0 ? 'selected' : ''; ?>>Unavailable</option>
                                <option value="2" <?= $property['status'] == 2 ? 'selected' : ''; ?>>Sold/Rented</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="is_featured" name="is_featured" 
                                       <?= $property['is_featured'] ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="is_featured">Featured Property</label>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="d-flex justify-content-between">
                                <a href="properties.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-times me-2"></i>Cancel
                                </a>
                                <button type="submit" name="update_property" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Update Property
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
let uploadedImages = [];
let propertyImages = <?= json_encode($current_images); ?>;

// File upload handling
document.getElementById('imageInput').addEventListener('change', handleFileUpload);

// Drag and drop
const uploadArea = document.getElementById('uploadArea');
uploadArea.addEventListener('dragover', (e) => {
    e.preventDefault();
    uploadArea.classList.add('dragover');
});

uploadArea.addEventListener('dragleave', () => {
    uploadArea.classList.remove('dragover');
});

uploadArea.addEventListener('drop', (e) => {
    e.preventDefault();
    uploadArea.classList.remove('dragover');
    handleFileUpload({ target: { files: e.dataTransfer.files } });
});

function handleFileUpload(event) {
    const files = Array.from(event.target.files);
    if (files.length === 0) return;

    const formData = new FormData();
    files.forEach(file => {
        formData.append('images[]', file);
    });

    // Upload files
    fetch('upload_handler.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            data.files.forEach(file => {
                uploadedImages.push(file);
                addImageToPreview(file);
            });
            document.getElementById('saveImages').style.display = 'block';
            showToast('Files uploaded successfully!', 'success');
        } else {
            showToast(data.message || 'Upload failed', 'error');
        }
    })
    .catch(error => {
        showToast('Upload error: ' + error.message, 'error');
    });
}

function addImageToPreview(file) {
    const preview = document.getElementById('imagePreview');
    const imageItem = document.createElement('div');
    imageItem.className = 'image-item';
    imageItem.dataset.filename = file.filename;
    
    imageItem.innerHTML = `
        <img src="${file.path}" alt="Property Image">
        <div class="image-controls">
            <button type="button" class="image-btn" onclick="setMainImage('${file.filename}')" title="Set as main">
                <i class="fas fa-star"></i>
            </button>
            <button type="button" class="image-btn" onclick="deleteImage('${file.filename}')" title="Delete">
                <i class="fas fa-trash"></i>
            </button>
        </div>
    `;
    
    preview.appendChild(imageItem);
}

function deleteImage(filename) {
    if (!confirm('Are you sure you want to delete this image?')) return;

    // Remove from arrays
    uploadedImages = uploadedImages.filter(img => img.filename !== filename);
    propertyImages = propertyImages.filter(img => img.filename !== filename);

    // Remove from DOM
    const imageItem = document.querySelector(`[data-filename="${filename}"]`);
    if (imageItem) imageItem.remove();

    // Show save button
    document.getElementById('saveImages').style.display = 'block';

    // Delete from server
    fetch('upload_handler.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `delete_file=1&filename=${filename}`
    });

    showToast('Image deleted', 'success');
}

function setMainImage(filename) {
    // Update arrays to put selected image first
    const allImages = [...propertyImages, ...uploadedImages];
    const selectedIndex = allImages.findIndex(img => img.filename === filename);
    if (selectedIndex > -1) {
        const selectedImage = allImages.splice(selectedIndex, 1)[0];
        allImages.unshift(selectedImage);
        
        // Update display
        updateImagePreview(allImages);
        document.getElementById('saveImages').style.display = 'block';
        showToast('Main image updated', 'success');
    }
}

function updateImagePreview(images) {
    const preview = document.getElementById('imagePreview');
    preview.innerHTML = '';
    
    images.forEach((image, index) => {
        const imageItem = document.createElement('div');
        imageItem.className = 'image-item';
        imageItem.dataset.filename = image.filename;
        
        imageItem.innerHTML = `
            ${index === 0 ? '<div class="main-image-badge">Main Image</div>' : ''}
            <img src="${image.path}" alt="Property Image">
            <div class="image-controls">
                ${index !== 0 ? `<button type="button" class="image-btn" onclick="setMainImage('${image.filename}')" title="Set as main"><i class="fas fa-star"></i></button>` : ''}
                <button type="button" class="image-btn" onclick="deleteImage('${image.filename}')" title="Delete"><i class="fas fa-trash"></i></button>
            </div>
        `;
        
        preview.appendChild(imageItem);
    });
}

function savePropertyImages() {
    const allImages = [...propertyImages, ...uploadedImages];
    
    fetch('upload_handler.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `associate_images=1&property_id=<?= $property_id; ?>&images=${encodeURIComponent(JSON.stringify(allImages))}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            propertyImages = allImages;
            uploadedImages = [];
            document.getElementById('saveImages').style.display = 'none';
            showToast('Images saved successfully!', 'success');
        } else {
            showToast(data.message || 'Failed to save images', 'error');
        }
    })
    .catch(error => {
        showToast('Save error: ' + error.message, 'error');
    });
}

function showToast(message, type) {
    // Simple toast notification
    const toast = document.createElement('div');
    toast.className = `alert alert-${type === 'success' ? 'success' : 'danger'} position-fixed`;
    toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999;';
    toast.innerHTML = `${message} <button type="button" class="btn-close" onclick="this.parentElement.remove()"></button>`;
    document.body.appendChild(toast);
    
    setTimeout(() => toast.remove(), 5000);
}
</script>
</body>
</html>