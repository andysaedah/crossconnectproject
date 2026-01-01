<?php
/**
 * CrossConnect MY - File Upload Helper
 * Handles image uploads for churches and events with optimization
 */

require_once __DIR__ . '/paths.php';

define('UPLOAD_MAX_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'webp', 'gif']);
define('UPLOAD_BASE_PATH', __DIR__ . '/../uploads');
define('IMAGE_MAX_WIDTH', 1200);  // Max width for resizing
define('IMAGE_MAX_HEIGHT', 1200); // Max height for resizing
define('IMAGE_QUALITY', 80);      // JPEG quality (1-100)

/**
 * Upload and optimize an image file
 * 
 * @param array $file The $_FILES array element
 * @param string $type The type of upload ('church' or 'event')
 * @param int $id The ID of the record (used for naming)
 * @return array ['success' => bool, 'path' => string, 'error' => string]
 */
function uploadImage($file, $type, $id = null)
{
    // Validate type
    if (!in_array($type, ['church', 'event'])) {
        return ['success' => false, 'error' => 'Invalid upload type'];
    }

    // Check if file was uploaded
    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        return ['success' => false, 'error' => 'No file uploaded'];
    }

    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors = [
            UPLOAD_ERR_INI_SIZE => 'File exceeds upload limit',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds form limit',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
        ];
        return ['success' => false, 'error' => $errors[$file['error']] ?? 'Unknown upload error'];
    }

    // Check file size
    if ($file['size'] > UPLOAD_MAX_SIZE) {
        return ['success' => false, 'error' => 'File size exceeds 5MB limit'];
    }

    // Get file extension
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, ALLOWED_EXTENSIONS)) {
        return ['success' => false, 'error' => 'Invalid file type. Allowed: ' . implode(', ', ALLOWED_EXTENSIONS)];
    }

    // Verify it's actually an image
    $imageInfo = @getimagesize($file['tmp_name']);
    if ($imageInfo === false) {
        return ['success' => false, 'error' => 'Invalid image file'];
    }

    // Create upload directory if it doesn't exist
    $uploadDir = UPLOAD_BASE_PATH . '/' . $type;
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            return ['success' => false, 'error' => 'Failed to create upload directory'];
        }
    }

    // Generate unique filename (always save as jpg for better compression)
    $timestamp = time();
    $uniqueId = $id ?: uniqid();
    $filename = $type . '_' . $uniqueId . '_' . $timestamp . '.jpg';
    $filepath = $uploadDir . '/' . $filename;

    // Optimize and save image
    $optimizeResult = optimizeImage($file['tmp_name'], $filepath, $imageInfo[2]);
    if (!$optimizeResult['success']) {
        return $optimizeResult;
    }

    // Return relative path from webroot (include base path for subdirectory support)
    $relativePath = rtrim(getBasePath(), '/') . '/uploads/' . $type . '/' . $filename;

    return [
        'success' => true,
        'path' => $relativePath,
        'filename' => $filename,
        'full_path' => $filepath,
        'original_size' => $file['size'],
        'optimized_size' => filesize($filepath)
    ];
}

/**
 * Optimize image: resize and compress
 * 
 * @param string $sourcePath Path to source image
 * @param string $destPath Path to save optimized image
 * @param int $imageType IMAGETYPE_* constant
 * @return array ['success' => bool, 'error' => string]
 */
function optimizeImage($sourcePath, $destPath, $imageType)
{
    // Create image resource based on type
    switch ($imageType) {
        case IMAGETYPE_JPEG:
            $source = @imagecreatefromjpeg($sourcePath);
            break;
        case IMAGETYPE_PNG:
            $source = @imagecreatefrompng($sourcePath);
            break;
        case IMAGETYPE_GIF:
            $source = @imagecreatefromgif($sourcePath);
            break;
        case IMAGETYPE_WEBP:
            $source = @imagecreatefromwebp($sourcePath);
            break;
        default:
            return ['success' => false, 'error' => 'Unsupported image format'];
    }

    if (!$source) {
        return ['success' => false, 'error' => 'Failed to read image'];
    }

    // Get original dimensions
    $origWidth = imagesx($source);
    $origHeight = imagesy($source);

    // Calculate new dimensions (maintain aspect ratio)
    $newWidth = $origWidth;
    $newHeight = $origHeight;

    if ($origWidth > IMAGE_MAX_WIDTH || $origHeight > IMAGE_MAX_HEIGHT) {
        $ratio = min(IMAGE_MAX_WIDTH / $origWidth, IMAGE_MAX_HEIGHT / $origHeight);
        $newWidth = (int) ($origWidth * $ratio);
        $newHeight = (int) ($origHeight * $ratio);
    }

    // Create new image with new dimensions
    $resized = imagecreatetruecolor($newWidth, $newHeight);

    // Preserve transparency for PNG/GIF before converting to JPEG
    // Fill with white background
    $white = imagecolorallocate($resized, 255, 255, 255);
    imagefill($resized, 0, 0, $white);

    // Copy and resize
    imagecopyresampled(
        $resized,
        $source,
        0,
        0,
        0,
        0,
        $newWidth,
        $newHeight,
        $origWidth,
        $origHeight
    );

    // Save as JPEG with compression
    $result = imagejpeg($resized, $destPath, IMAGE_QUALITY);

    // Clean up
    imagedestroy($source);
    imagedestroy($resized);

    if (!$result) {
        return ['success' => false, 'error' => 'Failed to save optimized image'];
    }

    // Make sure file is readable
    chmod($destPath, 0644);

    return ['success' => true];
}

/**
 * Delete an uploaded image
 * 
 * @param string $path The relative path to the image
 * @return bool
 */
function deleteUploadedImage($path)
{
    if (empty($path)) {
        return false;
    }

    // Strip base path if present (e.g., /hebats/uploads/... -> /uploads/...)
    $basePath = rtrim(getBasePath(), '/');
    if (!empty($basePath) && strpos($path, $basePath) === 0) {
        $path = substr($path, strlen($basePath));
    }

    // Get full path
    $fullPath = __DIR__ . '/..' . $path;

    // Validate it's in our uploads directory
    $realPath = realpath($fullPath);
    $uploadsPath = realpath(UPLOAD_BASE_PATH);

    if ($realPath && $uploadsPath && strpos($realPath, $uploadsPath) === 0) {
        if (file_exists($realPath) && is_file($realPath)) {
            return unlink($realPath);
        }
    }

    return false;
}

/**
 * Get image URL with fallback
 * 
 * @param string|null $imagePath The stored image path
 * @param string $type Type for fallback placeholder
 * @return string
 */
function getImageUrl($imagePath, $type = 'church')
{
    if (!empty($imagePath)) {
        return $imagePath;
    }

    // Return placeholder based on type
    $placeholders = [
        'church' => '/images/placeholder-church.jpg',
        'event' => '/images/placeholder-event.jpg'
    ];

    return $placeholders[$type] ?? $placeholders['church'];
}
