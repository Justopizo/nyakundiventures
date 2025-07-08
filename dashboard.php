<?php
// Get user's order statistics
$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM orders WHERE user_id = ? AND status = 'pending'");
$stmt->execute([$user_id]);
$pending_orders = $stmt->fetch()['count'];

$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM orders WHERE user_id = ? AND status = 'completed'");
$stmt->execute([$user_id]);
$completed_orders = $stmt->fetch()['count'];

// Get available properties count
$stmt = $pdo->query("SELECT COUNT(*) as count FROM rental_houses WHERE status = 'available'");
$available_rentals = $stmt->fetch()['count'];

$stmt = $pdo->query("SELECT COUNT(*) as count FROM sale_houses WHERE status = 'available'");
$available_houses = $stmt->fetch()['count'];

$stmt = $pdo->query("SELECT COUNT(*) as count FROM sale_lands WHERE status = 'available'");
$available_lands = $stmt->fetch()['count'];

// Get recent orders
$stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$stmt->execute([$user_id]);
$recent_orders = $stmt->fetchAll();
?>

<h1 class="page-title"><i class="fas fa-tachometer-alt"></i> Welcome to Your Dashboard</h1>

<div class="welcome-card">
    <div class="welcome-content">
        <h2>Hello, <?php echo htmlspecialchars($_SESSION['full_name']); ?>!</h2>
        <p>Welcome to Nyakundi Ventures - your one-stop solution for real estate, waste management, and landscaping services.</p>
    </div>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <i class="fas fa-clock"></i>
        <h3><?php echo $pending_orders; ?></h3>
        <p>Pending Orders</p>
    </div>
    <div class="stat-card">
        <i class="fas fa-check-circle"></i>
        <h3><?php echo $completed_orders; ?></h3>
        <p>Completed Orders</p>
    </div>
    <div class="stat-card">
        <i class="fas fa-home"></i>
        <h3><?php echo $available_rentals; ?></h3>
        <p>Available Rentals</p>
    </div>
    <div class="stat-card">
        <i class="fas fa-building"></i>
        <h3><?php echo $available_houses; ?></h3>
        <p>Houses for Sale</p>
    </div>
</div>

<div class="quick-actions">
    <h2><i class="fas fa-bolt"></i> Quick Actions</h2>
    <div class="actions-grid">
        <a href="?page=rental_houses" class="action-card">
            <i class="fas fa-home"></i>
            <h3>Browse Rentals</h3>
            <p>Find your perfect rental home</p>
        </a>
        <a href="?page=sale_houses" class="action-card">
            <i class="fas fa-building"></i>
            <h3>Buy a House</h3>
            <p>Explore houses for sale</p>
        </a>
        <a href="?page=sale_lands" class="action-card">
            <i class="fas fa-map"></i>
            <h3>Buy Land</h3>
            <p>Invest in prime land</p>
        </a>
        <a href="?page=garbage_collection" class="action-card">
            <i class="fas fa-trash"></i>
            <h3>Garbage Collection</h3>
            <p>Schedule waste pickup</p>
        </a>
        <a href="?page=landscaping" class="action-card">
            <i class="fas fa-seedling"></i>
            <h3>Landscaping</h3>
            <p>Transform your space</p>
        </a>
        <a href="?page=my_orders" class="action-card">
            <i class="fas fa-shopping-cart"></i>
            <h3>My Orders</h3>
            <p>Track your requests</p>
        </a>
    </div>
</div>

<?php if (!empty($recent_orders)): ?>
<div class="recent-orders">
    <h2><i class="fas fa-history"></i> Recent Orders</h2>
    <div class="orders-list">
        <?php foreach ($recent_orders as $order): ?>
            <div class="order-item">
                <div class="order-info">
                    <h4>
                        <?php
                        $type_labels = [
                            'rent_house' => 'House Rental',
                            'buy_house' => 'House Purchase',
                            'buy_land' => 'Land Purchase',
                            'garbage_collection' => 'Garbage Collection',
                            'landscaping' => 'Landscaping'
                        ];
                        echo $type_labels[$order['order_type']] ?? $order['order_type'];
                        ?>
                    </h4>
                    <p><?php echo date('M j, Y', strtotime($order['created_at'])); ?></p>
                </div>
                <span class="status-badge status-<?php echo $order['status']; ?>">
                    <?php echo ucfirst($order['status']); ?>
                </span>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<style>
.welcome-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 30px;
    border-radius: 15px;
    margin-bottom: 30px;
}

.welcome-content h2 {
    margin-bottom: 10px;
    font-size: 1.8rem;
}

.welcome-content p {
    opacity: 0.9;
    font-size: 1.1rem;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 40px;
}

.stat-card {
    background: white;
    padding: 25px;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    text-align: center;
}

.stat-card i {
    font-size: 2.5rem;
    margin-bottom: 15px;
    color: #667eea;
}

.stat-card h3 {
    font-size: 2rem;
    margin-bottom: 5px;
    color: #333;
}

.stat-card p {
    color: #666;
    font-weight: 500;
}

.quick-actions {
    margin-bottom: 40px;
}

.quick-actions h2 {
    margin-bottom: 20px;
    color: #333;
}

.actions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
}

.action-card {
    background: white;
    padding: 25px;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    text-decoration: none;
    color: inherit;
    transition: transform 0.2s, box-shadow 0.2s;
    text-align: center;
}

.action-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 20px rgba(0,0,0,0.15);
}

.action-card i {
    font-size: 2.5rem;
    margin-bottom: 15px;
    color: #667eea;
}

.action-card h3 {
    margin-bottom: 10px;
    color: #333;
}

.action-card p {
    color: #666;
}

.recent-orders {
    background: white;
    padding: 25px;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.recent-orders h2 {
    margin-bottom: 20px;
    color: #333;
}

.orders-list {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.order-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 8px;
}

.order-info h4 {
    margin-bottom: 5px;
    color: #333;
}

.order-info p {
    color: #666;
    font-size: 0.9rem;
}

.status-badge {
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 500;
}

.status-pending {
    background-color: #fff3cd;
    color: #856404;
}

.status-completed {
    background-color: #d4edda;
    color: #155724;
}

.status-cancelled {
    background-color: #f8d7da;
    color: #721c24;
}

@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .actions-grid {
        grid-template-columns: 1fr;
    }
    
    .order-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
}
</style>