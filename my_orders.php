<?php
$user_id = $_SESSION['user_id'];

// Get filter parameters
$filter_status = isset($_GET['status']) ? $_GET['status'] : 'all';
$filter_type = isset($_GET['type']) ? $_GET['type'] : 'all';

// Build query
$where_conditions = ["user_id = ?"];
$params = [$user_id];

if ($filter_status !== 'all') {
    $where_conditions[] = "status = ?";
    $params[] = $filter_status;
}

if ($filter_type !== 'all') {
    $where_conditions[] = "order_type = ?";
    $params[] = $filter_type;
}

$where_clause = 'WHERE ' . implode(' AND ', $where_conditions);

$stmt = $pdo->prepare("SELECT * FROM orders $where_clause ORDER BY created_at DESC");
$stmt->execute($params);
$orders = $stmt->fetchAll();

// Get order statistics
$stmt = $pdo->prepare("SELECT status, COUNT(*) as count FROM orders WHERE user_id = ? GROUP BY status");
$stmt->execute([$user_id]);
$stats = [];
while ($row = $stmt->fetch()) {
    $stats[$row['status']] = $row['count'];
}
?>

<h1 class="page-title"><i class="fas fa-shopping-cart"></i> My Orders</h1>

<div class="order-stats">
    <div class="stat-card">
        <i class="fas fa-clock"></i>
        <h3><?php echo $stats['pending'] ?? 0; ?></h3>
        <p>Pending Orders</p>
    </div>
    <div class="stat-card">
        <i class="fas fa-check-circle"></i>
        <h3><?php echo $stats['completed'] ?? 0; ?></h3>
        <p>Completed Orders</p>
    </div>
    <div class="stat-card">
        <i class="fas fa-times-circle"></i>
        <h3><?php echo $stats['cancelled'] ?? 0; ?></h3>
        <p>Cancelled Orders</p>
    </div>
    <div class="stat-card">
        <i class="fas fa-list"></i>
        <h3><?php echo count($orders); ?></h3>
        <p>Total Orders</p>
    </div>
</div>

<div class="filters-container">
    <form method="GET" class="filter-form">
        <div class="filter-row">
            <div class="form-group">
                <label for="status">Filter by Status</label>
                <select id="status" name="status">
                    <option value="all" <?php echo $filter_status === 'all' ? 'selected' : ''; ?>>All Status</option>
                    <option value="pending" <?php echo $filter_status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="completed" <?php echo $filter_status === 'completed' ? 'selected' : ''; ?>>Completed</option>
                    <option value="cancelled" <?php echo $filter_status === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                </select>
            </div>
            <div class="form-group">
                <label for="type">Filter by Type</label>
                <select id="type" name="type">
                    <option value="all" <?php echo $filter_type === 'all' ? 'selected' : ''; ?>>All Types</option>
                    <option value="rent_house" <?php echo $filter_type === 'rent_house' ? 'selected' : ''; ?>>House Rental</option>
                    <option value="buy_house" <?php echo $filter_type === 'buy_house' ? 'selected' : ''; ?>>House Purchase</option>
                    <option value="buy_land" <?php echo $filter_type === 'buy_land' ? 'selected' : ''; ?>>Land Purchase</option>
                    <option value="garbage_collection" <?php echo $filter_type === 'garbage_collection' ? 'selected' : ''; ?>>Garbage Collection</option>
                    <option value="landscaping" <?php echo $filter_type === 'landscaping' ? 'selected' : ''; ?>>Landscaping</option>
                </select>
            </div>
            <div class="form-group">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-filter"></i> Apply Filters
                </button>
            </div>
        </div>
    </form>
</div>

<div class="orders-container">
    <?php if (empty($orders)): ?>
        <div class="no-orders">
            <i class="fas fa-shopping-cart"></i>
            <h3>No orders found</h3>
            <p>You haven't placed any orders yet. Start exploring our services!</p>
            <div class="quick-links">
                <a href="?page=rental_houses" class="btn btn-primary">Browse Rentals</a>
                <a href="?page=sale_houses" class="btn btn-secondary">Buy Houses</a>
                <a href="?page=garbage_collection" class="btn btn-success">Garbage Collection</a>
            </div>
        </div>
    <?php else: ?>
        <div class="orders-list">
            <?php foreach ($orders as $order): ?>
                <div class="order-card">
                    <div class="order-header">
                        <div class="order-info">
                            <h3>Order #<?php echo $order['id']; ?></h3>
                            <span class="order-type">
                                <?php
                                $type_labels = [
                                    'rent_house' => 'House Rental',
                                    'buy_house' => 'House Purchase',
                                    'buy_land' => 'Land Purchase',
                                    'garbage_collection' => 'Garbage Collection',
                                    'landscaping' => 'Landscaping Service'
                                ];
                                echo $type_labels[$order['order_type']] ?? $order['order_type'];
                                ?>
                            </span>
                        </div>
                        <div class="order-meta">
                            <span class="status-badge status-<?php echo $order['status']; ?>">
                                <?php echo ucfirst($order['status']); ?>
                            </span>
                            <span class="order-date">
                                <?php echo date('M j, Y', strtotime($order['created_at'])); ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="order-details">
                        <div class="detail-row">
                            <span class="label">Customer Name:</span>
                            <span class="value"><?php echo htmlspecialchars($order['customer_name']); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="label">Phone:</span>
                            <span class="value"><?php echo htmlspecialchars($order['customer_phone']); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="label">Location:</span>
                            <span class="value"><?php echo htmlspecialchars($order['customer_location']); ?></span>
                        </div>
                        <?php if ($order['details']): ?>
                            <div class="detail-row">
                                <span class="label">Details:</span>
                                <span class="value"><?php echo nl2br(htmlspecialchars($order['details'])); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="order-actions">
                        <?php if ($order['status'] === 'pending'): ?>
                            <span class="status-info">
                                <i class="fas fa-clock"></i> We will contact you soon to discuss your request
                            </span>
                        <?php elseif ($order['status'] === 'completed'): ?>
                            <span class="status-info">
                                <i class="fas fa-check-circle"></i> Order completed successfully
                            </span>
                        <?php else: ?>
                            <span class="status-info">
                                <i class="fas fa-times-circle"></i> Order was cancelled
                            </span>
                        <?php endif; ?>
                        
                        <div class="action-buttons">
                            <a href="tel:0742907335" class="btn btn-call">
                                <i class="fas fa-phone"></i> Call Us
                            </a>
                            <button class="btn btn-secondary" onclick="viewOrderDetails(<?php echo $order['id']; ?>)">
                                <i class="fas fa-eye"></i> View Details
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Order Details Modal -->
<div id="orderDetailsModal" class="modal">
    <div class="modal-content modal-large">
        <div class="modal-header">
            <h2><i class="fas fa-info-circle"></i> Order Details</h2>
            <span class="close" onclick="closeOrderDetailsModal()">&times;</span>
        </div>
        <div id="orderDetailsContent">
            <!-- Order details will be loaded here -->
        </div>
    </div>
</div>

<style>
.order-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
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

.filters-container {
    background: white;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    margin-bottom: 30px;
}

.filter-row {
    display: grid;
    grid-template-columns: 1fr 1fr auto;
    gap: 20px;
    align-items: end;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 500;
    color: #333;
}

.form-group select {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 1rem;
}

.btn {
    padding: 10px 20px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    text-decoration: none;
    font-size: 0.9rem;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.2s;
}

.btn-primary {
    background: #667eea;
    color: white;
}

.btn-primary:hover {
    background: #5a6fd8;
}

.btn-secondary {
    background: #6c757d;
    color: white;
}

.btn-secondary:hover {
    background: #5a6268;
}

.btn-success {
    background: #28a745;
    color: white;
}

.btn-success:hover {
    background: #218838;
}

.btn-call {
    background: #17a2b8;
    color: white;
}

.btn-call:hover {
    background: #138496;
}

.no-orders {
    text-align: center;
    padding: 60px 20px;
    background: white;
    border-radius: 15px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.no-orders i {
    font-size: 4rem;
    color: #ddd;
    margin-bottom: 20px;
}

.no-orders h3 {
    margin-bottom: 15px;
    color: #333;
}

.no-orders p {
    color: #666;
    margin-bottom: 30px;
}

.quick-links {
    display: flex;
    gap: 15px;
    justify-content: center;
    flex-wrap: wrap;
}

.orders-list {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.order-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    overflow: hidden;
    transition: transform 0.2s;
}

.order-card:hover {
    transform: translateY(-2px);
}

.order-header {
    background: #f8f9fa;
    padding: 20px;
    border-bottom: 1px solid #eee;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.order-info h3 {
    margin-bottom: 5px;
    color: #333;
}

.order-type {
    background: #e9ecef;
    color: #495057;
    padding: 4px 12px;
    border-radius: 15px;
    font-size: 0.8rem;
    font-weight: 500;
}

.order-meta {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 8px;
}

.status-badge {
    padding: 6px 12px;
    border-radius: 15px;
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

.order-date {
    color: #666;
    font-size: 0.9rem;
}

.order-details {
    padding: 20px;
}

.detail-row {
    display: flex;
    margin-bottom: 12px;
    align-items: flex-start;
}

.detail-row .label {
    font-weight: 500;
    color: #333;
    min-width: 120px;
    flex-shrink: 0;
}

.detail-row .value {
    color: #666;
    flex: 1;
}

.order-actions {
    background: #f8f9fa;
    padding: 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.status-info {
    color: #666;
    font-size: 0.9rem;
    display: flex;
    align-items: center;
    gap: 8px;
}

.action-buttons {
    display: flex;
    gap: 10px;
}

/* Modal Styles */
.modal {
    display: none;
    position: fixed;
    z-index: 2000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
}

.modal-content {
    background-color: white;
    margin: 5% auto;
    padding: 0;
    border-radius: 10px;
    width: 90%;
    max-width: 600px;
    max-height: 90vh;
    overflow-y: auto;
}

.modal-large {
    max-width: 800px;
}

.modal-header {
    padding: 20px;
    border-bottom: 1px solid #eee;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h2 {
    margin: 0;
    color: #333;
}

.close {
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
    color: #aaa;
}

.close:hover {
    color: #000;
}

@media (max-width: 768px) {
    .filter-row {
        grid-template-columns: 1fr;
        gap: 15px;
    }
    
    .order-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
    }
    
    .order-meta {
        align-items: flex-start;
    }
    
    .order-actions {
        flex-direction: column;
        gap: 15px;
        align-items: stretch;
    }
    
    .action-buttons {
        justify-content: center;
    }
    
    .quick-links {
        flex-direction: column;
        align-items: center;
    }
    
    .detail-row {
        flex-direction: column;
        gap: 5px;
    }
    
    .detail-row .label {
        min-width: auto;
        font-weight: 600;
    }
}
</style>

<script>
function viewOrderDetails(orderId) {
    document.getElementById('orderDetailsModal').style.display = 'block';
    document.getElementById('orderDetailsContent').innerHTML = `
        <div style="padding: 40px; text-align: center;">
            <i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: #667eea;"></i>
            <p style="margin-top: 15px;">Loading order details...</p>
        </div>
    `;
    
    // Make AJAX request to fetch order details
    fetch(`get_order_details.php?order_id=${orderId}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.text();
        })
        .then(data => {
            document.getElementById('orderDetailsContent').innerHTML = data;
        })
        .catch(error => {
            document.getElementById('orderDetailsContent').innerHTML = `
                <div class="error-message" style="padding: 20px;">
                    <h3><i class="fas fa-exclamation-triangle"></i> Error</h3>
                    <p>Failed to load order details. Please try again.</p>
                    <p>${error.message}</p>
                </div>
            `;
        });
}

function closeOrderDetailsModal() {
    document.getElementById('orderDetailsModal').style.display = 'none';
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('orderDetailsModal');
    if (event.target === modal) {
        closeOrderDetailsModal();
    }
}
</script>