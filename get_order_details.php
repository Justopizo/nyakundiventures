<?php
session_start();
require_once 'config.php'; // Make sure this includes your database connection

if (!isset($_SESSION['user_id'])) {
    die('<div class="error-message">Unauthorized access</div>');
}

$order_id = $_GET['order_id'] ?? 0;

// Verify the order belongs to the current user
$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
$stmt->execute([$order_id, $_SESSION['user_id']]);
$order = $stmt->fetch();

if (!$order) {
    die('<div class="error-message">Order not found or access denied</div>');
}

// Get additional details based on order type
$item_details = '';
if ($order['item_id']) {
    switch ($order['order_type']) {
        case 'rent_house':
            $stmt = $pdo->prepare("SELECT title, price, location FROM rental_houses WHERE id = ?");
            $table = 'rental_houses';
            $type_label = 'House Rental';
            break;
        case 'buy_house':
            $stmt = $pdo->prepare("SELECT title, price, location FROM sale_houses WHERE id = ?");
            $table = 'sale_houses';
            $type_label = 'House Purchase';
            break;
        case 'buy_land':
            $stmt = $pdo->prepare("SELECT title, price, location, size FROM sale_lands WHERE id = ?");
            $table = 'sale_lands';
            $type_label = 'Land Purchase';
            break;
        default:
            $stmt = null;
    }

    if ($stmt) {
        $stmt->execute([$order['item_id']]);
        $item = $stmt->fetch();
        if ($item) {
            $item_details = '<div class="order-item-details">';
            $item_details .= '<h4><i class="fas fa-home"></i> ' . htmlspecialchars($item['title']) . '</h4>';
            $item_details .= '<p><strong>Price:</strong> KSh ' . number_format($item['price'], 2) . '</p>';
            $item_details .= '<p><strong>Location:</strong> ' . htmlspecialchars($item['location']) . '</p>';
            if (isset($item['size'])) {
                $item_details .= '<p><strong>Size:</strong> ' . htmlspecialchars($item['size']) . '</p>';
            }
            $item_details .= '</div>';
        }
    }
}

// Prepare status display
$status_class = 'status-' . $order['status'];
$status_icon = [
    'pending' => 'fas fa-clock',
    'completed' => 'fas fa-check-circle',
    'cancelled' => 'fas fa-times-circle'
][$order['status']];

// Prepare the response HTML
?>
<div class="modal-body">
    <div class="order-header">
        <h2>Order #<?php echo $order['id']; ?></h2>
        <span class="status-badge <?php echo $status_class; ?>">
            <i class="<?php echo $status_icon; ?>"></i>
            <?php echo ucfirst($order['status']); ?>
        </span>
    </div>

    <div class="order-info-section">
        <h3><i class="fas fa-info-circle"></i> Order Information</h3>
        <div class="info-grid">
            <div class="info-item">
                <span class="info-label">Order Type:</span>
                <span class="info-value"><?php echo $type_label ?? ucfirst(str_replace('_', ' ', $order['order_type'])); ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Date Placed:</span>
                <span class="info-value"><?php echo date('F j, Y \a\t g:i a', strtotime($order['created_at'])); ?></span>
            </div>
        </div>
    </div>

    <?php if (!empty($item_details)): ?>
        <div class="order-info-section">
            <h3><i class="fas fa-home"></i> Property Details</h3>
            <?php echo $item_details; ?>
        </div>
    <?php endif; ?>

    <div class="order-info-section">
        <h3><i class="fas fa-user"></i> Customer Information</h3>
        <div class="info-grid">
            <div class="info-item">
                <span class="info-label">Full Name:</span>
                <span class="info-value"><?php echo htmlspecialchars($order['customer_name']); ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Phone Number:</span>
                <span class="info-value"><?php echo htmlspecialchars($order['customer_phone']); ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Location:</span>
                <span class="info-value"><?php echo htmlspecialchars($order['customer_location']); ?></span>
            </div>
        </div>
    </div>

    <?php if (!empty($order['details'])): ?>
        <div class="order-info-section">
            <h3><i class="fas fa-comment-alt"></i> Additional Details</h3>
            <div class="additional-details">
                <?php echo nl2br(htmlspecialchars($order['details'])); ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="modal-footer">
        <button class="btn btn-primary" onclick="window.print()">
            <i class="fas fa-print"></i> Print Details
        </button>
        <button class="btn btn-secondary" onclick="closeOrderDetailsModal()">
            <i class="fas fa-times"></i> Close
        </button>
    </div>
</div>

<style>
.modal-body {
    padding: 20px;
}

.order-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 1px solid #eee;
}

.order-header h2 {
    margin: 0;
    color: #333;
}

.status-badge {
    padding: 8px 15px;
    border-radius: 20px;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    gap: 8px;
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

.order-info-section {
    margin-bottom: 25px;
    padding-bottom: 15px;
    border-bottom: 1px solid #f0f0f0;
}

.order-info-section h3 {
    margin-top: 0;
    margin-bottom: 15px;
    color: #444;
    display: flex;
    align-items: center;
    gap: 10px;
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 15px;
}

.info-item {
    margin-bottom: 10px;
}

.info-label {
    font-weight: 600;
    color: #555;
    display: block;
    margin-bottom: 3px;
}

.info-value {
    color: #333;
    word-break: break-word;
}

.order-item-details {
    background: #f9f9f9;
    padding: 15px;
    border-radius: 8px;
    margin-top: 10px;
}

.order-item-details h4 {
    margin-top: 0;
    margin-bottom: 10px;
    color: #333;
}

.additional-details {
    background: #f9f9f9;
    padding: 15px;
    border-radius: 8px;
    white-space: pre-wrap;
}

.modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    padding-top: 15px;
    margin-top: 20px;
    border-top: 1px solid #eee;
}

.btn {
    padding: 10px 20px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
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

.error-message {
    color: #721c24;
    background-color: #f8d7da;
    border-color: #f5c6cb;
    padding: 15px;
    border-radius: 4px;
    margin: 20px;
}
</style>