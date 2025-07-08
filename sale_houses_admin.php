<?php
$message = '';
$error = '';

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
                    // Handle image upload
                    $imagePath = null;
                    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                        $uploadDir = 'uploads/rental_houses/';
                        if (!file_exists($uploadDir)) {
                            mkdir($uploadDir, 0777, true);
                        }
                        
                        $fileExtension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                        $fileName = uniqid() . '.' . $fileExtension;
                        $destination = $uploadDir . $fileName;
                        
                        // Check if image file is actual image
                        $check = getimagesize($_FILES['image']['tmp_name']);
                        if ($check !== false) {
                            if (move_uploaded_file($_FILES['image']['tmp_name'], $destination)) {
                                $imagePath = $destination;
                            } else {
                                $error = 'Failed to upload image';
                            }
                        } else {
                            $error = 'File is not an image';
                        }
                    }
                    
                    if (empty($error)) {
                        $stmt = $pdo->prepare("INSERT INTO rental_houses (title, price, location, description, images) VALUES (?, ?, ?, ?, ?)");
                        if ($stmt->execute([$title, $price, $location, $description, $imagePath])) {
                            $message = 'Rental house added successfully!';
                        } else {
                            $error = 'Failed to add rental house';
                        }
                    }
                }
                break;
                
            case 'delete':
                $id = intval($_POST['id']);
                // First get image path to delete the file
                $stmt = $pdo->prepare("SELECT images FROM rental_houses WHERE id = ?");
                $stmt->execute([$id]);
                $house = $stmt->fetch();
                
                if ($house['images'] && file_exists($house['images'])) {
                    unlink($house['images']);
                }
                
                $stmt = $pdo->prepare("DELETE FROM rental_houses WHERE id = ?");
                if ($stmt->execute([$id])) {
                    $message = 'House deleted successfully!';
                } else {
                    $error = 'Failed to delete house';
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

// Get all rental houses
$stmt = $pdo->query("SELECT * FROM rental_houses ORDER BY created_at DESC");
$rental_houses = $stmt->fetchAll();
?>

<h1 class="page-title"><i class="fas fa-building"></i> Rental Houses Management</h1>

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
                <input type="text" id="title" name="title" placeholder="e.g., 4 Bedroom House in Karen" required>
            </div>
            <div class="form-group">
                <label for="price">Monthly Rent (KSH) *</label>
                <input type="number" id="price" name="price" placeholder="e.g., 50000" step="0.01" required>
            </div>
        </div>
        <div class="form-group">
            <label for="location">Location *</label>
            <input type="text" id="location" name="location" placeholder="e.g., Karen, Nairobi" required>
        </div>
        <div class="form-group">
            <label for="description">Description</label>
            <textarea id="description" name="description" placeholder="Describe the house features, bedrooms, bathrooms, compound, etc."></textarea>
        </div>
        <div class="form-group">
            <label for="image">House Image</label>
            <input type="file" id="image" name="image" accept="image/*">
        </div>
        <button type="submit" class="btn"><i class="fas fa-plus"></i> Add Rental House</button>
    </form>
</div>

<div class="card">
    <h2><i class="fas fa-list"></i> All Rental Houses</h2>
    <?php if (empty($rental_houses)): ?>
        <p>No rental houses added yet.</p>
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
                        <div class="property-image">
                            <img src="<?php echo htmlspecialchars($house['images']); ?>" alt="<?php echo htmlspecialchars($house['title']); ?>" style="max-width: 100%; height: auto; border-radius: 4px; margin-bottom: 10px;">
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
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="update_status">
                            <input type="hidden" name="id" value="<?php echo $house['id']; ?>">
                            <select name="status" onchange="this.form.submit()">
                                <option value="available" <?php echo $house['status'] === 'available' ? 'selected' : ''; ?>>Available</option>
                                <option value="rented" <?php echo $house['status'] === 'rented' ? 'selected' : ''; ?>>Rented</option>
                            </select>
                        </form>
                        <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this property?')">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?php echo $house['id']; ?>">
                            <button type="submit" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<style>
.alert {
    padding: 15px;
    margin-bottom: 20px;
    border-radius: 5px;
}

.alert-success {
    background-color: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.alert-error {
    background-color: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
}

.properties-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.property-card {
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 15px;
    background: #f9f9f9;
}

.property-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.property-header h3 {
    margin: 0;
    color: #333;
    font-size: 1.1rem;
}

.status-badge {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.8rem;
    font-weight: 500;
}

.status-available {
    background-color: #d4edda;
    color: #155724;
}

.status-rented {
    background-color: #f8d7da;
    color: #721c24;
}

.property-details p {
    margin: 8px 0;
    color: #666;
}

.property-details i {
    margin-right: 8px;
    color: #667eea;
    width: 15px;
}

.property-actions {
    margin-top: 15px;
    display: flex;
    gap: 10px;
    align-items: center;
}

.property-actions select {
    padding: 5px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.btn-sm {
    padding: 5px 10px;
    font-size: 0.8rem;
}

@media (max-width: 768px) {
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .properties-grid {
        grid-template-columns: 1fr;
    }
}
</style>