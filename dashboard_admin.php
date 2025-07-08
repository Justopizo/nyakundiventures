<?php
// Get statistics
$stats = [];

// Count rental houses
$stmt = $pdo->query("SELECT COUNT(*) as count FROM rental_houses WHERE status = 'available'");
$stats['rental_houses'] = $stmt->fetch()['count'];

// Count houses for sale
$stmt = $pdo->query("SELECT COUNT(*) as count FROM sale_houses WHERE status = 'available'");
$stats['sale_houses'] = $stmt->fetch()['count'];

// Count land for sale
$stmt = $pdo->query("SELECT COUNT(*) as count FROM sale_lands WHERE status = 'available'");
$stats['sale_lands'] = $stmt->fetch()['count'];

// Count pending orders
$stmt = $pdo->query("SELECT COUNT(*) as count FROM orders WHERE status = 'pending'");
$stats['pending_orders'] = $stmt->fetch()['count'];

// Get recent orders
$stmt = $pdo->prepare("
    SELECT o.*, u.full_name, u.phone as user_phone 
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    ORDER BY o.created_at DESC 
    LIMIT 10
");
$stmt->execute();
$recent_orders = $stmt->fetchAll();
?>

<h1 class="page-title"><i class="fas fa-tachometer-alt"></i> Dashboard Overview</h1>

<div class="stats-grid">
    <div class="stat-card">
        <i class="fas fa-home"></i>
        <h3><?php echo $stats['rental_houses']; ?></h3>
        <p>Available Rentals</p>
    </div>
    <div class="stat-card">
        <i class="fas fa-building"></i>
        <h3><?php echo $stats['sale_houses']; ?></h3>
        <p>Houses for Sale</p>
    </div>
    <div class="stat-card">
        <i class="fas fa-map"></i>
        <h3><?php echo $stats['sale_lands']; ?></h3>
        <p>Land for Sale</p>
    </div>
    <div class="stat-card">
        <i class="fas fa-clock"></i>
        <h3><?php echo $stats['pending_orders']; ?></h3>
        <p>Pending Orders</p>
    </div>
</div>

<div class="card">
    <h2><i class="fas fa-list"></i> Recent Orders</h2>
    <?php if (empty($recent_orders)): ?>
        <p>No orders yet.</p>
    <?php else: ?>
        <table class="table">
            <thead>
                <tr>
                    <th>Customer</th>
                    <th>Order Type</th>
                    <th>Phone</th>
                    <th>Location</th>
                    <th>Status</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recent_orders as $order): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($order['full_name']); ?></td>
                        <td>
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
                        </td>
                        <td><?php echo htmlspecialchars($order['customer_phone']); ?></td>
                        <td><?php echo htmlspecialchars($order['customer_location']); ?></td>
                        <td>
                            <span class="badge badge-<?php echo $order['status']; ?>">
                                <?php echo ucfirst($order['status']); ?>
                            </span>
                        </td>
                        <td><?php echo date('M j, Y', strtotime($order['created_at'])); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<style>
.badge {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.8rem;
    font-weight: 500;
}

.badge-pending {
    background-color: #fff3cd;
    color: #856404;
}

.badge-completed {
    background-color: #d4edda;
    color: #155724;
}

.badge-cancelled {
    background-color: #f8d7da;
    color: #721c24;
}
</style>