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
                $size = trim($_POST['size']);
                $description = trim($_POST['description']);
                $images = trim($_POST['images']);
                
                if (empty($title) || empty($price) || empty($location) || empty($size)) {
                    $error = 'Please fill in all required fields';
                } else {
                    $stmt = $pdo->prepare("INSERT INTO sale_lands (title, price, location, size, description, images) VALUES (?, ?, ?, ?, ?, ?)");
                    if ($stmt->execute([$title, $price, $location, $size, $description, $images])) {
                        $message = 'Land for sale added successfully!';
                    } else {
                        $error = 'Failed to add land for sale';
                    }
                }
                break;
                
            case 'delete':
                $id = intval($_POST['id']);
                $stmt = $pdo->prepare("DELETE FROM sale_lands WHERE id = ?");
                if ($stmt->execute([$id])) {
                    $message = 'Land deleted successfully!';
                } else {
                    $error = 'Failed to delete land';
                }
                break;
                
            case 'update_status':
                $id = intval($_POST['id']);
                $status = $_POST['status'];
                $stmt = $pdo->prepare("UPDATE sale_lands SET status = ? WHERE id = ?");
                if ($stmt->execute([$status, $id])) {
                    $message = 'Status updated successfully!';
                } else {
                    $error = 'Failed to update status';
                }
                break;
        }
    }
}

// Get all land for sale
$stmt = $pdo->query("SELECT * FROM sale_lands ORDER BY created_at DESC");
$sale_lands = $stmt->fetchAll();
?>

<h1 class="page-title"><i class="fas fa-map"></i> Land for Sale Management</h1>

<?php if ($message): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<div class="card">
    <h2><i class="fas fa-plus"></i> Add New Land for Sale</h2>
    <form method="POST">
        <input type="hidden" name="action" value="add">
        <div class="form-row">
            <div class="form-group">
                <label for="title">Land Title *</label>
                <input type="text" id="title" name="title" placeholder="e.g., Prime Land in Kiambu" required>
            </div>
            <div class="form-group">
                <label for="price">Selling Price (KSH) *</label>
                <input type="number" id="price" name="price" placeholder="e.g., 5000000" step="0.01" required>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="location">Location *</label>
                <input type="text" id="location" name="location" placeholder="e.g., Kiambu, Central Kenya" required>
            </div>
            <div class="form-group">
                <label for="size">Land Size *</label>
                <input type="text" id="size" name="size" placeholder="e.g., 1 Acre, 50x100 ft" required>
            </div>
        </div>
        <div class="form-group">
            <label for="description">Description</label>
            <textarea id="description" name="description" placeholder="Describe the land features, accessibility, nearby amenities, etc."></textarea>
        </div>
        <div class="form-group">
            <label for="images">Image URLs (comma-separated)</label>
            <textarea id="images" name="images" placeholder="https://example.com/image1.jpg, https://example.com/image2.jpg"></textarea>
        </div>
        <button type="submit" class="btn"><i class="fas fa-plus"></i> Add Land for Sale</button>
    </form>
</div>

<div class="card">
    <h2><i class="fas fa-list"></i> All Land for Sale</h2>
    <?php if (empty($sale_lands)): ?>
        <p>No land for sale added yet.</p>
    <?php else: ?>
        <div class="properties-grid">
            <?php foreach ($sale_lands as $land): ?>
                <div class="property-card">
                    <div class="property-header">
                        <h3><?php echo htmlspecialchars($land['title']); ?></h3>
                        <span class="status-badge status-<?php echo $land['status']; ?>">
                            <?php echo ucfirst($land['status']); ?>
                        </span>
                    </div>
                    <div class="property-details">
                        <p><i class="fas fa-money-bill"></i> KSH <?php echo number_format($land['price']); ?></p>
                        <p><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($land['location']); ?></p>
                        <p><i class="fas fa-ruler"></i> Size: <?php echo htmlspecialchars($land['size']); ?></p>
                        <?php if ($land['description']): ?>
                            <p><i class="fas fa-info-circle"></i> <?php echo htmlspecialchars(substr($land['description'], 0, 100)); ?>...</p>
                        <?php endif; ?>
                        <p><i class="fas fa-calendar"></i> Added: <?php echo date('M j, Y', strtotime($land['created_at'])); ?></p>
                    </div>
                    <div class="property-actions">
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="update_status">
                            <input type="hidden" name="id" value="<?php echo $land['id']; ?>">
                            <select name="status" onchange="this.form.submit()">
                                <option value="available" <?php echo $land['status'] === 'available' ? 'selected' : ''; ?>>Available</option>
                                <option value="sold" <?php echo $land['status'] === 'sold' ? 'selected' : ''; ?>>Sold</option>
                            </select>
                        </form>
                        <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this land?')">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?php echo $land['id']; ?>">
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

.status-sold {
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