<?php
require_once 'config.php';

$message = '';
$error = '';

// Create uploads directory if it doesn't exist
$upload_dir = 'uploads/rental_houses/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $title = trim($_POST['title']);
                $price = floatval($_POST['price']);
                $location = trim($_POST['location']);
                $description = trim($_POST['description']);
                
                if (empty($title) || empty($price) || empty($location)) {
                    $error = 'Please fill in all required fields';
                } else {
                    // Handle file uploads
                    $uploaded_images = [];
                    
                    if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
                        $total_files = count($_FILES['images']['name']);
                        
                        for ($i = 0; $i < $total_files; $i++) {
                            if ($_FILES['images']['error'][$i] === UPLOAD_ERR_OK) {
                                $file_tmp = $_FILES['images']['tmp_name'][$i];
                                $file_name = $_FILES['images']['name'][$i];
                                $file_size = $_FILES['images']['size'][$i];
                                $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                                
                                // Validate file type
                                $allowed_types = ['jpg', 'jpeg', 'png', 'webp'];
                                if (!in_array($file_ext, $allowed_types)) {
                                    $error = 'Only JPG, JPEG, PNG, and WEBP files are allowed';
                                    break;
                                }
                                
                                // Validate file size (5MB max)
                                if ($file_size > 5 * 1024 * 1024) {
                                    $error = 'File size must be less than 5MB';
                                    break;
                                }
                                
                                // Generate unique filename
                                $new_filename = uniqid('rental_') . '_' . time() . '.' . $file_ext;
                                $upload_path = $upload_dir . $new_filename;
                                
                                // Process and compress image
                                if (compressAndSaveImage($file_tmp, $upload_path, $file_ext)) {
                                    $uploaded_images[] = $upload_path;
                                } else {
                                    $error = 'Failed to process image: ' . $file_name;
                                    break;
                                }
                            }
                        }
                    }
                    
                    if (empty($error)) {
                        $images_json = !empty($uploaded_images) ? json_encode($uploaded_images) : null;
                        
                        $stmt = $pdo->prepare("INSERT INTO rental_houses (title, price, location, description, images) VALUES (?, ?, ?, ?, ?)");
                        if ($stmt->execute([$title, $price, $location, $description, $images_json])) {
                            $message = 'Rental house added successfully!';
                        } else {
                            $error = 'Failed to add rental house';
                            // Clean up uploaded files on database error
                            foreach ($uploaded_images as $img_path) {
                                if (file_exists($img_path)) {
                                    unlink($img_path);
                                }
                            }
                        }
                    }
                }
                break;
                
            case 'delete':
                $id = intval($_POST['id']);
                
                // Get existing images to delete
                $stmt = $pdo->prepare("SELECT images FROM rental_houses WHERE id = ?");
                $stmt->execute([$id]);
                $house = $stmt->fetch();
                
                if ($house && $house['images']) {
                    $images = json_decode($house['images'], true);
                    if ($images) {
                        foreach ($images as $img_path) {
                            if (file_exists($img_path)) {
                                unlink($img_path);
                            }
                        }
                    }
                }
                
                $stmt = $pdo->prepare("DELETE FROM rental_houses WHERE id = ?");
                if ($stmt->execute([$id])) {
                    $message = 'Rental house deleted successfully!';
                } else {
                    $error = 'Failed to delete rental house';
                }
                break;
                
            case 'update_status':
                $id = intval($_POST['id']);
                $status = $_POST['status'];
                $stmt = $pdo->prepare("UPDATE rental_houses SET status = ? WHERE id = ?");
                if ($stmt->execute([$status, $id])) {
                    $message = 'Status updated successfully!';
                } else {
                    $error = 'Failed to update status';
                }
                break;
        }
    }
}

// Function to compress and save images
function compressAndSaveImage($source, $destination, $ext, $quality = 85) {
    switch ($ext) {
        case 'jpg':
        case 'jpeg':
            $image = imagecreatefromjpeg($source);
            break;
        case 'png':
            $image = imagecreatefrompng($source);
            break;
        case 'webp':
            $image = imagecreatefromwebp($source);
            break;
        default:
            return false;
    }
    
    if (!$image) {
        return false;
    }
    
    // Get original dimensions
    $width = imagesx($image);
    $height = imagesy($image);
    
    // Calculate new dimensions (max 1920x1080 for HD quality)
    $max_width = 1920;
    $max_height = 1080;
    
    if ($width > $max_width || $height > $max_height) {
        $ratio = min($max_width / $width, $max_height / $height);
        $new_width = round($width * $ratio);
        $new_height = round($height * $ratio);
        
        // Create new image with new dimensions
        $new_image = imagecreatetruecolor($new_width, $new_height);
        
        // Preserve transparency for PNG
        if ($ext === 'png') {
            imagealphablending($new_image, false);
            imagesavealpha($new_image, true);
        }
        
        // Resize image
        imagecopyresampled($new_image, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
        imagedestroy($image);
        $image = $new_image;
    }
    
    // Save compressed image
    $result = false;
    switch ($ext) {
        case 'jpg':
        case 'jpeg':
            $result = imagejpeg($image, $destination, $quality);
            break;
        case 'png':
            $result = imagepng($image, $destination, 9);
            break;
        case 'webp':
            $result = imagewebp($image, $destination, $quality);
            break;
    }
    
    imagedestroy($image);
    return $result;
}

// Get all rental houses
$stmt = $pdo->query("SELECT * FROM rental_houses ORDER BY created_at DESC");
$rental_houses = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rental Houses Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        .page-title {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            color: white;
            padding: 30px;
            margin-bottom: 30px;
            border-radius: 15px;
            font-size: 2.5rem;
            text-align: center;
            font-weight: 300;
            letter-spacing: 1px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }

        .page-title i {
            margin-right: 15px;
        }

        .alert {
            padding: 15px 20px;
            margin-bottom: 20px;
            border-radius: 10px;
            font-weight: 500;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .alert-success {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            color: #155724;
            border-left: 4px solid #28a745;
        }

        .alert-error {
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
            color: #721c24;
            border-left: 4px solid #dc3545;
        }

        .card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .card h2 {
            color: #2c3e50;
            margin-bottom: 25px;
            font-size: 1.8rem;
            display: flex;
            align-items: center;
        }

        .card h2 i {
            margin-right: 10px;
            color: #667eea;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #2c3e50;
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }

        .file-upload-area {
            border: 2px dashed #667eea;
            border-radius: 10px;
            padding: 30px;
            text-align: center;
            background: #f8f9ff;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .file-upload-area:hover {
            background: #f0f2ff;
            border-color: #5a67d8;
        }

        .file-upload-area.dragover {
            background: #e6f3ff;
            border-color: #3182ce;
        }

        .file-upload-area i {
            font-size: 3rem;
            color: #667eea;
            margin-bottom: 15px;
        }

        .file-upload-text {
            color: #4a5568;
            font-size: 1.1rem;
            margin-bottom: 10px;
        }

        .file-upload-hint {
            color: #718096;
            font-size: 0.9rem;
        }

        #images {
            display: none;
        }

        .image-preview {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }

        .preview-item {
            position: relative;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .preview-item img {
            width: 100%;
            height: 120px;
            object-fit: cover;
        }

        .preview-item .remove-btn {
            position: absolute;
            top: 5px;
            right: 5px;
            background: #dc3545;
            color: white;
            border: none;
            border-radius: 50%;
            width: 25px;
            height: 25px;
            cursor: pointer;
            font-size: 0.8rem;
        }

        .btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            text-decoration: none;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.3);
        }

        .btn i {
            margin-right: 8px;
        }

        .btn-danger {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
        }

        .btn-sm {
            padding: 8px 15px;
            font-size: 0.9rem;
        }

        .properties-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
            gap: 25px;
            margin-top: 25px;
        }

        .property-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }

        .property-card:hover {
            transform: translateY(-5px);
        }

        .property-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }

        .property-header h3 {
            margin: 0;
            color: #2c3e50;
            font-size: 1.2rem;
            flex: 1;
            margin-right: 10px;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-available {
            background: #d4edda;
            color: #155724;
        }

        .status-rented {
            background: #f8d7da;
            color: #721c24;
        }

        .property-details p {
            margin: 10px 0;
            color: #555;
            display: flex;
            align-items: center;
        }

        .property-details i {
            margin-right: 10px;
            color: #667eea;
            width: 18px;
            text-align: center;
        }

        .property-images {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
            gap: 8px;
            margin: 15px 0;
        }

        .property-images img {
            width: 100%;
            height: 60px;
            object-fit: cover;
            border-radius: 6px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .property-actions {
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #e9ecef;
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .property-actions select {
            padding: 8px 12px;
            border: 2px solid #e9ecef;
            border-radius: 6px;
            font-size: 0.9rem;
            cursor: pointer;
        }

        @media (max-width: 768px) {
            body {
                padding: 10px;
            }

            .page-title {
                font-size: 2rem;
                padding: 20px;
            }

            .card {
                padding: 20px;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .properties-grid {
                grid-template-columns: 1fr;
            }

            .property-actions {
                flex-direction: column;
                align-items: stretch;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="page-title">
            <i class="fas fa-home"></i> Rental Houses Management
        </h1>

        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="card">
            <h2><i class="fas fa-plus"></i> Add New Rental House</h2>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="title">Property Title *</label>
                        <input type="text" id="title" name="title" placeholder="e.g., 2 Bedroom Apartment in Kilimani" required>
                    </div>
                    <div class="form-group">
                        <label for="price">Monthly Rent (KSH) *</label>
                        <input type="number" id="price" name="price" placeholder="e.g., 25000" step="0.01" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="location">Location *</label>
                    <input type="text" id="location" name="location" placeholder="e.g., Kilimani, Nairobi" required>
                </div>
                
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" placeholder="Describe the property features, amenities, etc."></textarea>
                </div>
                
                <div class="form-group">
                    <label>Property Images</label>
                    <div class="file-upload-area" onclick="document.getElementById('images').click()">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <div class="file-upload-text">Click to upload images or drag and drop</div>
                        <div class="file-upload-hint">Supports: JPG, PNG, WEBP (Max 5MB each, HD quality maintained)</div>
                    </div>
                    <input type="file" id="images" name="images[]" multiple accept="image/*">
                    <div id="imagePreview" class="image-preview"></div>
                </div>
                
                <button type="submit" class="btn">
                    <i class="fas fa-plus"></i> Add Rental House
                </button>
            </form>
        </div>

        <div class="card">
            <h2><i class="fas fa-list"></i> All Rental Houses</h2>
            <?php if (empty($rental_houses)): ?>
                <p style="text-align: center; color: #666; font-size: 1.1rem; padding: 40px;">
                    <i class="fas fa-home" style="font-size: 3rem; display: block; margin-bottom: 15px; color: #ccc;"></i>
                    No rental houses added yet.
                </p>
            <?php else: ?>
                <div class="properties-grid">
                    <?php foreach ($rental_houses as $house): ?>
                        <div class="property-card">
                            <div class="property-header">
                                <h3><?php echo htmlspecialchars($house['title']); ?></h3>
                                <span class="status-badge status-<?php echo $house['status']; ?>">
                                    <?php echo ucfirst($house['status']); ?>
                                </span>
                            </div>
                            
                            <?php if ($house['images']): ?>
                                <div class="property-images">
                                    <?php 
                                    $images = json_decode($house['images'], true);
                                    if ($images) {
                                        foreach (array_slice($images, 0, 4) as $img_path) {
                                            if (file_exists($img_path)) {
                                                echo '<img src="' . htmlspecialchars($img_path) . '" alt="Property Image">';
                                            }
                                        }
                                    }
                                    ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="property-details">
                                <p><i class="fas fa-money-bill"></i> KSH <?php echo number_format($house['price']); ?>/month</p>
                                <p><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($house['location']); ?></p>
                                <?php if ($house['description']): ?>
                                    <p><i class="fas fa-info-circle"></i> <?php echo htmlspecialchars(substr($house['description'], 0, 100)); ?>...</p>
                                <?php endif; ?>
                                <p><i class="fas fa-calendar"></i> Added: <?php echo date('M j, Y', strtotime($house['created_at'])); ?></p>
                            </div>
                            
                            <div class="property-actions">
                                <form method="POST" style="flex: 1;">
                                    <input type="hidden" name="action" value="update_status">
                                    <input type="hidden" name="id" value="<?php echo $house['id']; ?>">
                                    <select name="status" onchange="this.form.submit()" style="width: 100%;">
                                        <option value="available" <?php echo $house['status'] === 'available' ? 'selected' : ''; ?>>Available</option>
                                        <option value="rented" <?php echo $house['status'] === 'rented' ? 'selected' : ''; ?>>Rented</option>
                                    </select>
                                </form>
                                
                                <form method="POST" onsubmit="return confirm('Are you sure you want to delete this property?')">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo $house['id']; ?>">
                                    <button type="submit" class="btn btn-danger btn-sm">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // File upload handling
        const fileInput = document.getElementById('images');
        const uploadArea = document.querySelector('.file-upload-area');
        const imagePreview = document.getElementById('imagePreview');
        let selectedFiles = [];

        // Click to upload
        fileInput.addEventListener('change', handleFiles);

        // Drag and drop
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
            const files = Array.from(e.dataTransfer.files);
            handleFileSelection(files);
        });

        function handleFiles(e) {
            const files = Array.from(e.target.files);
            handleFileSelection(files);
        }

        function handleFileSelection(files) {
            selectedFiles = [...selectedFiles, ...files];
            updateFileInput();
            displayImagePreviews();
        }

        function updateFileInput() {
            const dt = new DataTransfer();
            selectedFiles.forEach(file => dt.items.add(file));
            fileInput.files = dt.files;
        }

        function displayImagePreviews() {
            imagePreview.innerHTML = '';
            selectedFiles.forEach((file, index) => {
                if (file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        const previewItem = document.createElement('div');
                        previewItem.className = 'preview-item';
                        previewItem.innerHTML = `
                            <img src="${e.target.result}" alt="Preview">
                            <button type="button" class="remove-btn" onclick="removeFile(${index})">×</button>
                        `;
                        imagePreview.appendChild(previewItem);
                    };
                    reader.readAsDataURL(file);
                }
            });
        }

        function removeFile(index) {
            selectedFiles.splice(index, 1);
            updateFileInput();
            displayImagePreviews();
        }
    </script>
</body>
</html>
