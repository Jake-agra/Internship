<?php
session_start();
include('../Database/connection.php');
include('../includes/route.php');
include('../includes/security.php');

// Enhanced security check
if (!security()->validateAdminAccess()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    security()->logSecurityEvent('UNAUTHORIZED_UPLOAD_ATTEMPT', [
        'user_id' => $_SESSION['user_id'] ?? 'anonymous'
    ], 'WARNING');
    exit();
}

// Enhanced Configuration
$upload_dir = '../uploads/properties/';
$thumbnail_dir = '../uploads/properties/thumbnails/';
$watermark_dir = '../uploads/properties/watermarked/';
$allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
$max_file_size = 10 * 1024 * 1024; // 10MB
$max_files = 20; // Increased for professional galleries
$thumbnail_sizes = [
    'small' => 150,
    'medium' => 300,
    'large' => 600
];

// Create upload directories if they don't exist
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}
if (!file_exists($thumbnail_dir)) {
    mkdir($thumbnail_dir, 0755, true);
}
if (!file_exists($watermark_dir)) {
    mkdir($watermark_dir, 0755, true);
}

// Function to generate unique filename
function generateUniqueFilename($original_name) {
    $extension = pathinfo($original_name, PATHINFO_EXTENSION);
    return uniqid() . '_' . time() . '.' . $extension;
}

// Function to resize image
function resizeImage($source, $destination, $max_width = 800, $max_height = 600, $quality = 85) {
    $image_info = getimagesize($source);
    if (!$image_info) return false;
    
    $source_width = $image_info[0];
    $source_height = $image_info[1];
    $mime_type = $image_info['mime'];
    
    // Calculate new dimensions
    $ratio = min($max_width / $source_width, $max_height / $source_height);
    $new_width = round($source_width * $ratio);
    $new_height = round($source_height * $ratio);
    
    // Create source image
    switch ($mime_type) {
        case 'image/jpeg':
            $source_image = imagecreatefromjpeg($source);
            break;
        case 'image/png':
            $source_image = imagecreatefrompng($source);
            break;
        case 'image/gif':
            $source_image = imagecreatefromgif($source);
            break;
        case 'image/webp':
            $source_image = imagecreatefromwebp($source);
            break;
        default:
            return false;
    }
    
    if (!$source_image) return false;
    
    // Create destination image
    $destination_image = imagecreatetruecolor($new_width, $new_height);
    
    // Preserve transparency for PNG and GIF
    if ($mime_type == 'image/png' || $mime_type == 'image/gif') {
        imagealphablending($destination_image, false);
        imagesavealpha($destination_image, true);
        $transparent = imagecolorallocatealpha($destination_image, 255, 255, 255, 127);
        imagefilledrectangle($destination_image, 0, 0, $new_width, $new_height, $transparent);
    }
    
    // Resize image
    imagecopyresampled($destination_image, $source_image, 0, 0, 0, 0, $new_width, $new_height, $source_width, $source_height);
    
    // Save image
    $result = false;
    switch ($mime_type) {
        case 'image/jpeg':
            $result = imagejpeg($destination_image, $destination, $quality);
            break;
        case 'image/png':
            $result = imagepng($destination_image, $destination, 9);
            break;
        case 'image/gif':
            $result = imagegif($destination_image, $destination);
            break;
        case 'image/webp':
            $result = imagewebp($destination_image, $destination, $quality);
            break;
    }
    
    // Clean up
    imagedestroy($source_image);
    imagedestroy($destination_image);
    
    return $result;
}

// Function to create multiple thumbnail sizes
function createThumbnails($source, $base_name, $thumbnail_dir, $sizes, $quality = 85) {
    $thumbnails = [];
    foreach ($sizes as $size_name => $size) {
        $thumbnail_path = $thumbnail_dir . $size_name . '_' . $base_name;
        if (resizeImage($source, $thumbnail_path, $size, $size, $quality)) {
            $thumbnails[$size_name] = 'uploads/properties/thumbnails/' . $size_name . '_' . $base_name;
        }
    }
    return $thumbnails;
}

// Function to add watermark
function addWatermark($source, $destination, $watermark_text = 'Real Estate Pro') {
    $image_info = getimagesize($source);
    if (!$image_info) return false;
    
    $source_width = $image_info[0];
    $source_height = $image_info[1];
    $mime_type = $image_info['mime'];
    
    // Create source image
    switch ($mime_type) {
        case 'image/jpeg':
            $source_image = imagecreatefromjpeg($source);
            break;
        case 'image/png':
            $source_image = imagecreatefrompng($source);
            break;
        case 'image/gif':
            $source_image = imagecreatefromgif($source);
            break;
        case 'image/webp':
            $source_image = imagecreatefromwebp($source);
            break;
        default:
            return false;
    }
    
    if (!$source_image) return false;
    
    // Add watermark
    $font_size = max(12, $source_width / 50);
    $text_color = imagecolorallocatealpha($source_image, 255, 255, 255, 50);
    $x = $source_width - (strlen($watermark_text) * $font_size * 0.6);
    $y = $source_height - 20;
    
    imagestring($source_image, 5, $x, $y, $watermark_text, $text_color);
    
    // Save watermarked image
    $result = false;
    switch ($mime_type) {
        case 'image/jpeg':
            $result = imagejpeg($source_image, $destination, 90);
            break;
        case 'image/png':
            $result = imagepng($source_image, $destination, 9);
            break;
        case 'image/gif':
            $result = imagegif($source_image, $destination);
            break;
        case 'image/webp':
            $result = imagewebp($source_image, $destination, 90);
            break;
    }
    
    imagedestroy($source_image);
    return $result;
}

// Function to get image metadata
function getImageMetadata($file_path) {
    $metadata = [];
    if (function_exists('exif_read_data') && in_array(mime_content_type($file_path), ['image/jpeg', 'image/tiff'])) {
        $exif = @exif_read_data($file_path);
        if ($exif) {
            $metadata['camera'] = $exif['Model'] ?? null;
            $metadata['date_taken'] = $exif['DateTime'] ?? null;
            $metadata['width'] = $exif['COMPUTED']['Width'] ?? null;
            $metadata['height'] = $exif['COMPUTED']['Height'] ?? null;
        }
    }
    
    $image_info = getimagesize($file_path);
    if ($image_info) {
        $metadata['width'] = $image_info[0];
        $metadata['height'] = $image_info[1];
        $metadata['mime_type'] = $image_info['mime'];
    }
    
    return $metadata;
}

// Handle file upload with enhanced security
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['images'])) {
    $uploaded_files = [];
    $errors = [];
    
    // Validate CSRF token if present
    if (isset($_POST['csrf_token']) && !verify_csrf($_POST['csrf_token'])) {
        security()->logSecurityEvent('CSRF_TOKEN_VALIDATION_FAILED', [
            'action' => 'file_upload'
        ], 'WARNING');
        echo json_encode(['success' => false, 'message' => 'Invalid security token']);
        exit();
    }
    
    // Enhanced file validation
    $validation_errors = security()->validateMultipleFileUploads($_FILES['images']);
    if (!empty($validation_errors)) {
        echo json_encode([
            'success' => false,
            'message' => 'File validation failed',
            'errors' => $validation_errors
        ]);
        exit();
    }
    
    // Process each file with enhanced security
    for ($i = 0; $i < count($_FILES['images']['name']); $i++) {
        if ($_FILES['images']['error'][$i] === UPLOAD_ERR_OK) {
            $file_name = $_FILES['images']['name'][$i];
            $file_tmp = $_FILES['images']['tmp_name'][$i];
            $file_size = $_FILES['images']['size'][$i];
            $file_type = $_FILES['images']['type'][$i];
            
            // Generate secure filename
            $unique_filename = secure_filename($file_name, 'prop_');
            $upload_path = $upload_dir . $unique_filename;
            $watermark_path = $watermark_dir . $unique_filename;
            
            // Additional security checks
            if (!getimagesize($file_tmp)) {
                $errors[] = "File '{$file_name}' is not a valid image.";
                continue;
            }
            
            // Move uploaded file
            if (move_uploaded_file($file_tmp, $upload_path)) {
                // Resize main image
                if (resizeImage($upload_path, $upload_path, 1200, 900, 90)) {
                    // Create multiple thumbnail sizes
                    $thumbnails = createThumbnails($upload_path, $unique_filename, $thumbnail_dir, $thumbnail_sizes, 85);
                    
                    // Add watermark
                    addWatermark($upload_path, $watermark_path);
                    
                    // Get image metadata
                    $metadata = getImageMetadata($upload_path);
                    
                    $uploaded_files[] = [
                        'original_name' => $file_name,
                        'filename' => $unique_filename,
                        'path' => 'uploads/properties/' . $unique_filename,
                        'watermarked' => 'uploads/properties/watermarked/' . $unique_filename,
                        'thumbnails' => $thumbnails,
                        'metadata' => $metadata,
                        'size' => $file_size,
                        'type' => $file_type,
                        'upload_date' => date('Y-m-d H:i:s')
                    ];
                } else {
                    $errors[] = "Failed to process image '{$file_name}'";
                    unlink($upload_path); // Remove failed file
                }
            } else {
                $errors[] = "Failed to upload file '{$file_name}'";
            }
        } else {
            $errors[] = "Upload error for file '{$file_name}': " . $_FILES['images']['error'][$i];
        }
    }
    
    // Return response
    if (empty($errors)) {
        echo json_encode([
            'success' => true,
            'message' => 'Files uploaded successfully',
            'files' => $uploaded_files
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Upload completed with errors',
            'errors' => $errors,
            'files' => $uploaded_files
        ]);
    }
    exit();
}

// Handle file deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_file'])) {
    $filename = basename($_POST['filename']);
    $file_path = $upload_dir . $filename;
    $watermark_path = $watermark_dir . $filename;
    
    $deleted = false;
    if (file_exists($file_path)) {
        $deleted = unlink($file_path);
    }
    
    // Delete watermarked version
    if (file_exists($watermark_path)) {
        unlink($watermark_path);
    }
    
    // Delete all thumbnail sizes
    foreach ($thumbnail_sizes as $size_name => $size) {
        $thumbnail_path = $thumbnail_dir . $size_name . '_' . $filename;
        if (file_exists($thumbnail_path)) {
            unlink($thumbnail_path);
        }
    }
    
    if ($deleted) {
        echo json_encode(['success' => true, 'message' => 'File and all variants deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete file']);
    }
    exit();
}

// Handle property image association
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['associate_images'])) {
    $property_id = (int)$_POST['property_id'];
    $images = json_decode($_POST['images'], true);
    
    if (!$property_id || !$images) {
        echo json_encode(['success' => false, 'message' => 'Invalid data']);
        exit();
    }
    
    // Update property images
    $images_json = json_encode($images);
    $update_stmt = $conn->prepare("UPDATE properties SET images = ? WHERE id = ?");
    $update_stmt->bind_param("si", $images_json, $property_id);
    
    if ($update_stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Images associated with property successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to associate images']);
    }
    $update_stmt->close();
    exit();
}

// Default response
http_response_code(400);
echo json_encode(['success' => false, 'message' => 'Invalid request']);
?>
