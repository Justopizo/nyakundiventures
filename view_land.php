<?php
require_once 'config.php';

if (!isset($_GET['id'])) {
    header('Location: orders.php');
    exit;
}

$id = intval($_GET['id']);
$stmt = $pdo->prepare("SELECT * FROM sale_lands WHERE id = ?");
$stmt->execute([$id]);
$land = $stmt->fetch();

if (!$land) {
    header('Location: orders.php');
    exit;
}
?>

<h1 class="page-title">Land for Sale Details</h1>

<div class="card">
    <h2><?php echo htmlspecialchars($land['title']); ?></h2>
    
    <div class="property-details">
        <div class="property-image">
            <?php if ($land['images']): ?>
                <img src="<?php echo htmlspecialchars(json_decode($land['images'])[0]); ?>" alt="<?php echo htmlspecialchars($land['title']); ?>">
            <?php else: ?>
                <div class="no-image">No Image Available</div>
            <?php endif; ?>
        </div>
        
        <div class="property-info">
            <p><strong>Price:</strong> KSh <?php echo number_format($land['price'], 2); ?></p>
            <p><strong>Location:</strong> <?php echo htmlspecialchars($land['location']); ?></p>
            <p><strong>Size:</strong> <?php echo htmlspecialchars($land['size']); ?></p>
            <p><strong>Status:</strong> <?php echo ucfirst($land['status']); ?></p>
            
            <h3>Description</h3>
            <p><?php echo nl2br(htmlspecialchars($land['description'])); ?></p>
        </div>
    </div>
    
    <div class="action-buttons">
        <a href="orders.php" class="btn btn-back">
            <i class="fas fa-arrow-left"></i> Back to Orders
        </a>
    </div>
</div>

<!-- [Use the same CSS as in view_rental.php] -->
 <style>
.property-details {
    display: grid;
    grid-template-columns: 1fr 2fr;
    gap: 20px;
    margin: 20px 0;
}

.property-image img {
    width: 100%;
    max-height: 300px;
    object-fit: cover;
    border-radius: 8px;
}

.no-image {
    background: #f0f0f0;
    height: 200px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    color: #666;
}

.property-info {
    padding: 0 10px;
}

.action-buttons {
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid #eee;
}

.btn-back {
    background-color: #6c757d;
    color: white;
    text-decoration: none;
    padding: 8px 15px;
    border-radius: 4px;
    font-size: 0.9rem;
}

.btn-back:hover {
    background-color: #5a6268;
}

@media (max-width: 768px) {
    .property-details {
        grid-template-columns: 1fr;
    }
}
</style>