<?php
session_start();
include('../Database/connection.php');
include('../includes/route.php');

// Check if user is admin
if (!isAdmin()) {
    header('Location: ../login.php');
    exit();
}

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['property_images'])) {
    $property_id = (int)$_POST['property_id'];
    $uploaded_files = [];
    $errors = [];
    
    // Create upload directory if it doesn't exist
    $upload_dir = '../uploads/properties/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    // Process each uploaded file
    foreach ($_FILES['property_images']['tmp_name'] as $key => $tmp_name) {
        if ($_FILES['property_images']['error'][$key] === UPLOAD_ERR_OK) {
            $file_name = $_FILES['property_images']['name'][$key];
            $file_size = $_FILES['property_images']['size'][$key];
            $file_tmp = $_FILES['property_images']['tmp_name'][$key];
            $file_type = $_FILES['property_images']['type'][$key];
            
            // Validate file type
            $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
            if (!in_array($file_type, $allowed_types)) {
                $errors[] = "Invalid file type for $file_name. Only JPEG, PNG, GIF, and WebP are allowed.";
                continue;
            }
            
            // Validate file size (max 5MB)
            if ($file_size > 5 * 1024 * 1024) {
                $errors[] = "File $file_name is too large. Maximum size is 5MB.";
                continue;
            }
            
            // Generate unique filename
            $file_extension = pathinfo($file_name, PATHINFO_EXTENSION);
            $new_filename = 'property_' . $property_id . '_' . time() . '_' . rand(1000, 9999) . '.' . $file_extension;
            $file_path = $upload_dir . $new_filename;
            
            // Move uploaded file
            if (move_uploaded_file($file_tmp, $file_path)) {
                // Create thumbnail
                $thumbnail_path = createThumbnail($file_path, $upload_dir . 'thumbs/');
                
                // Save to database
                $stmt = $conn->prepare("INSERT INTO property_images (property_id, image_path, thumbnail_path, image_type) VALUES (?, ?, ?, 'interior')");
                $relative_path = 'uploads/properties/' . $new_filename;
                $relative_thumb = $thumbnail_path ? 'uploads/properties/thumbs/' . basename($thumbnail_path) : null;
                $stmt->bind_param("iss", $property_id, $relative_path, $relative_thumb);
                
                if ($stmt->execute()) {
                    $uploaded_files[] = $new_filename;
                } else {
                    $errors[] = "Failed to save $file_name to database.";
                    unlink($file_path); // Remove uploaded file if database insert fails
                }
                $stmt->close();
            } else {
                $errors[] = "Failed to upload $file_name.";
            }
        }
    }
    
    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode([
        'success' => empty($errors),
        'uploaded_files' => $uploaded_files,
        'errors' => $errors,
        'message' => empty($errors) ? 'Images uploaded successfully!' : 'Some files failed to upload.'
    ]);
    exit();
}

// Function to create thumbnail
function createThumbnail($source_path, $thumb_dir) {
    if (!is_dir($thumb_dir)) {
        mkdir($thumb_dir, 0755, true);
    }
    
    $filename = basename($source_path);
    $thumb_path = $thumb_dir . 'thumb_' . $filename;
    
    // Get image info
    $image_info = getimagesize($source_path);
    if (!$image_info) return false;
    
    $width = $image_info[0];
    $height = $image_info[1];
    $type = $image_info[2];
    
    // Create image resource based on type
    switch ($type) {
        case IMAGETYPE_JPEG:
            $source = imagecreatefromjpeg($source_path);
            break;
        case IMAGETYPE_PNG:
            $source = imagecreatefrompng($source_path);
            break;
        case IMAGETYPE_GIF:
            $source = imagecreatefromgif($source_path);
            break;
        case IMAGETYPE_WEBP:
            $source = imagecreatefromwebp($source_path);
            break;
        default:
            return false;
    }
    
    // Calculate thumbnail dimensions (max 300x200)
    $thumb_width = 300;
    $thumb_height = 200;
    
    if ($width > $height) {
        $new_width = $thumb_width;
        $new_height = intval($height * $thumb_width / $width);
    } else {
        $new_height = $thumb_height;
        $new_width = intval($width * $thumb_height / $height);
    }
    
    // Create thumbnail
    $thumbnail = imagecreatetruecolor($new_width, $new_height);
    
    // Preserve transparency for PNG and GIF
    if ($type == IMAGETYPE_PNG || $type == IMAGETYPE_GIF) {
        imagealphablending($thumbnail, false);
        imagesavealpha($thumbnail, true);
        $transparent = imagecolorallocatealpha($thumbnail, 255, 255, 255, 127);
        imagefilledrectangle($thumbnail, 0, 0, $new_width, $new_height, $transparent);
    }
    
    imagecopyresampled($thumbnail, $source, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
    
    // Save thumbnail
    $success = false;
    switch ($type) {
        case IMAGETYPE_JPEG:
            $success = imagejpeg($thumbnail, $thumb_path, 85);
            break;
        case IMAGETYPE_PNG:
            $success = imagepng($thumbnail, $thumb_path);
            break;
        case IMAGETYPE_GIF:
            $success = imagegif($thumbnail, $thumb_path);
            break;
        case IMAGETYPE_WEBP:
            $success = imagewebp($thumbnail, $thumb_path, 85);
            break;
    }
    
    imagedestroy($source);
    imagedestroy($thumbnail);
    
    return $success ? $thumb_path : false;
}

// Get property ID from URL
$property_id = isset($_GET['property_id']) ? (int)$_GET['property_id'] : 0;
if ($property_id <= 0) {
    header('Location: properties.php');
    exit();
}

// Get property info
$property = null;
$stmt = $conn->prepare("SELECT p.id, p.propertiesname FROM properties p WHERE p.id = ?");
$stmt->bind_param("i", $property_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $property = $result->fetch_assoc();
} else {
    header('Location: properties.php');
    exit();
}
$stmt->close();

// Get existing images
$images = [];
$stmt = $conn->prepare("SELECT id, image_path, thumbnail_path, image_type, sort_order FROM property_images WHERE property_id = ? ORDER BY sort_order, id");
$stmt->bind_param("i", $property_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $images[] = $row;
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Image Manager - <?= htmlspecialchars($property['propertiesname']); ?></title>
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

        .upload-zone {
            border: 3px dashed #cbd5e1;
            border-radius: 15px;
            padding: 3rem;
            text-align: center;
            transition: all 0.3s ease;
            background: white;
        }

        .upload-zone:hover {
            border-color: var(--primary-color);
            background-color: #f8fafc;
        }

        .upload-zone.drag-over {
            border-color: var(--primary-color);
            background-color: #eff6ff;
        }

        .image-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 2rem;
        }

        .image-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .image-card:hover {
            transform: translateY(-5px);
        }

        .image-preview {
            width: 100%;
            height: 150px;
            object-fit: cover;
        }

        .image-actions {
            padding: 1rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
            border-radius: 8px;
            padding: 12px 30px;
            font-weight: 500;
        }

        .progress {
            height: 8px;
            border-radius: 4px;
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
                <h2>Image Manager</h2>
                <p class="text-muted"><?= htmlspecialchars($property['propertiesname']); ?></p>
            </div>
            <a href="properties.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back to Properties
            </a>
        </div>

        <!-- Upload Zone -->
        <div class="upload-zone" id="uploadZone">
            <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-3"></i>
            <h4>Drag & Drop Images Here</h4>
            <p class="text-muted">or click to select files</p>
            <input type="file" id="fileInput" name="property_images[]" multiple accept="image/*" style="display: none;">
            <button type="button" class="btn btn-primary" onclick="document.getElementById('fileInput').click()">
                <i class="fas fa-plus me-2"></i>Select Images
            </button>
            <div class="mt-3">
                <small class="text-muted">Supported formats: JPEG, PNG, GIF, WebP (Max 5MB each)</small>
            </div>
        </div>

        <!-- Progress Bar -->
        <div id="uploadProgress" class="mt-3" style="display: none;">
            <div class="progress">
                <div class="progress-bar" role="progressbar" style="width: 0%"></div>
            </div>
            <p class="text-center mt-2 mb-0"><span id="progressText">Uploading...</span></p>
        </div>

        <!-- Alert Messages -->
        <div id="alertContainer"></div>

        <!-- Existing Images -->
        <?php if (!empty($images)): ?>
            <div class="mt-5">
                <h4><i class="fas fa-images me-2"></i>Existing Images (<?= count($images); ?>)</h4>
                <div class="image-grid">
                    <?php foreach ($images as $image): ?>
                        <div class="image-card" data-image-id="<?= $image['id']; ?>">
                            <img src="../<?= htmlspecialchars($image['thumbnail_path'] ?: $image['image_path']); ?>" 
                                 alt="Property Image" class="image-preview">
                            <div class="image-actions">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="badge bg-secondary"><?= ucfirst($image['image_type']); ?></span>
                                    <div>
                                        <button class="btn btn-sm btn-outline-primary me-1" onclick="viewImage('<?= htmlspecialchars($image['image_path']); ?>')">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger" onclick="deleteImage(<?= $image['id']; ?>)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="text-center mt-5 py-5">
                <i class="fas fa-images fa-3x text-muted mb-3"></i>
                <h4>No Images Uploaded</h4>
                <p class="text-muted">Upload some images to showcase this property</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Image View Modal -->
<div class="modal fade" id="imageModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Property Image</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <img id="modalImage" src="" alt="Property Image" class="img-fluid">
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
const propertyId = <?= $property_id; ?>;
const uploadZone = document.getElementById('uploadZone');
const fileInput = document.getElementById('fileInput');
const uploadProgress = document.getElementById('uploadProgress');
const progressBar = document.querySelector('.progress-bar');
const progressText = document.getElementById('progressText');
const alertContainer = document.getElementById('alertContainer');

// Drag and drop functionality
uploadZone.addEventListener('dragover', (e) => {
    e.preventDefault();
    uploadZone.classList.add('drag-over');
});

uploadZone.addEventListener('dragleave', () => {
    uploadZone.classList.remove('drag-over');
});

uploadZone.addEventListener('drop', (e) => {
    e.preventDefault();
    uploadZone.classList.remove('drag-over');
    const files = e.dataTransfer.files;
    uploadFiles(files);
});

fileInput.addEventListener('change', (e) => {
    uploadFiles(e.target.files);
});

function uploadFiles(files) {
    if (files.length === 0) return;
    
    const formData = new FormData();
    formData.append('property_id', propertyId);
    
    for (let i = 0; i < files.length; i++) {
        formData.append('property_images[]', files[i]);
    }
    
    uploadProgress.style.display = 'block';
    progressBar.style.width = '0%';
    progressText.textContent = 'Uploading...';
    
    const xhr = new XMLHttpRequest();
    
    xhr.upload.addEventListener('progress', (e) => {
        if (e.lengthComputable) {
            const percentComplete = Math.round((e.loaded / e.total) * 100);
            progressBar.style.width = percentComplete + '%';
            progressText.textContent = `Uploading... ${percentComplete}%`;
        }
    });
    
    xhr.addEventListener('load', () => {
        uploadProgress.style.display = 'none';
        
        try {
            const response = JSON.parse(xhr.responseText);
            
            if (response.success) {
                showAlert('success', response.message);
                setTimeout(() => {
                    location.reload();
                }, 1500);
            } else {
                showAlert('danger', response.message + '<br>' + response.errors.join('<br>'));
            }
        } catch (e) {
            showAlert('danger', 'An error occurred while uploading images.');
        }
    });
    
    xhr.addEventListener('error', () => {
        uploadProgress.style.display = 'none';
        showAlert('danger', 'Upload failed. Please try again.');
    });
    
    xhr.open('POST', 'image_manager.php');
    xhr.send(formData);
}

function showAlert(type, message) {
    const alert = document.createElement('div');
    alert.className = `alert alert-${type} alert-dismissible fade show mt-3`;
    alert.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    alertContainer.appendChild(alert);
    
    setTimeout(() => {
        alert.remove();
    }, 5000);
}

function viewImage(imagePath) {
    document.getElementById('modalImage').src = '../' + imagePath;
    new bootstrap.Modal(document.getElementById('imageModal')).show();
}

function deleteImage(imageId) {
    if (confirm('Are you sure you want to delete this image?')) {
        // Implementation for deleting image
        console.log('Delete image:', imageId);
    }
}
</script>

</body>
</html>