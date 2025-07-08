<?php
require_once 'config.php';

$message = '';
$error = '';

// Handle order status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_status') {
        $order_id = intval($_POST['order_id']);
        $status = $_POST['status'];
        
        $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
        if ($stmt->execute([$status, $order_id])) {
            $message = 'Order status updated successfully!';
        } else {
            $error = 'Failed to update order status';
        }
    }
}

// Get filter parameters
$filter_type = isset($_GET['type']) ? $_GET['type'] : 'all';
$filter_status = isset($_GET['status']) ? $_GET['status'] : 'all';

// Build query
$where_conditions = [];
$params = [];

if ($filter_type !== 'all') {
    $where_conditions[] = "o.order_type = ?";
    $params[] = $filter_type;
}

if ($filter_status !== 'all') {
    $where_conditions[] = "o.status = ?";
    $params[] = $filter_status;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

$stmt = $pdo->prepare("
    SELECT o.*, u.full_name, u.email, u.phone as user_phone 
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    $where_clause
    ORDER BY o.created_at DESC
");
$stmt->execute($params);
$orders = $stmt->fetchAll();

// Function to get item details based on order type
function getOrderItemDetails($pdo, $order_type, $item_id) {
    if (!$item_id) return null;
    
    switch ($order_type) {
        case 'rent_house':
            $stmt = $pdo->prepare("SELECT * FROM rental_houses WHERE id = ?");
            break;
        case 'buy_house':
            $stmt = $pdo->prepare("SELECT * FROM sale_houses WHERE id = ?");
            break;
        case 'buy_land':
            $stmt = $pdo->prepare("SELECT * FROM sale_lands WHERE id = ?");
            break;
        default:
            return null;
    }
    
    $stmt->execute([$item_id]);
    return $stmt->fetch();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
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

        .filter-form .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr auto;
            gap: 15px;
            align-items: end;
        }

        .orders-container {
            display: grid;
            gap: 20px;
            margin-top: 20px;
        }

        .order-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            background: #f9f9f9;
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }

        .order-info h3 {
            margin: 0 0 5px 0;
            color: #333;
        }

        .order-type {
            background-color: #e9ecef;
            color: #495057;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 4px;
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

        .order-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        .customer-info,
        .order-specifics {
            background: white;
            padding: 15px;
            border-radius: 6px;
            border: 1px solid #e9ecef;
        }

        .customer-info h4,
        .order-specifics h4 {
            margin: 0 0 10px 0;
            color: #667eea;
            font-size: 1rem;
        }

        .customer-info p,
        .order-specifics p {
            margin: 5px 0;
            color: #666;
        }

        .item-details {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px dashed #ddd;
        }

        .item-details h5 {
            margin: 0 0 10px 0;
            color: #555;
            font-size: 0.9rem;
        }

        .order-meta {
            grid-column: 1 / -1;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }

        .order-actions {
            display: flex;
            gap: 15px;
            align-items: center;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }

        .order-actions select {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background: white;
        }

        .btn-call {
            background-color: #28a745;
            color: white;
            text-decoration: none;
            padding: 8px 15px;
            border-radius: 4px;
            font-size: 0.9rem;
        }

        .btn-call:hover {
            background-color: #218838;
        }

        .btn-view {
            background-color: #17a2b8;
            color: white;
            text-decoration: none;
            padding: 8px 15px;
            border-radius: 4px;
            font-size: 0.9rem;
        }

        .btn-view:hover {
            background-color: #138496;
        }

        @media (max-width: 768px) {
            .filter-form .form-row {
                grid-template-columns: 1fr;
            }
            
            .order-details {
                grid-template-columns: 1fr;
            }
            
            .order-actions {
                flex-direction: column;
                align-items: stretch;
            }
            
            .order-actions select,
            .btn-call,
            .btn-view {
                width: 100%;
                text-align: center;
            }
        }

        .card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .page-title {
            color: #333;
            margin-bottom: 20px;
        }

        .btn {
            padding: 8px 15px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .btn:hover {
            background-color: #0069d9;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }

        .form-group select,
        .form-group input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="page-title"><i class="fas fa-shopping-cart"></i> Orders Management</h1>

        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="card">
            <h2><i class="fas fa-filter"></i> Filter Orders</h2>
            <form method="GET" class="filter-form">
                <div class="form-row">
                    <div class="form-group">
                        <label for="type">Order Type</label>
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
                        <label for="status">Status</label>
                        <select id="status" name="status">
                            <option value="all" <?php echo $filter_status === 'all' ? 'selected' : ''; ?>>All Status</option>
                            <option value="pending" <?php echo $filter_status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="completed" <?php echo $filter_status === 'completed' ? 'selected' : ''; ?>>Completed</option>
                            <option value="cancelled" <?php echo $filter_status === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <button type="submit" class="btn"><i class="fas fa-search"></i> Filter</button>
                    </div>
                </div>
            </form>
        </div>

        <div class="card">
            <h2><i class="fas fa-list"></i> All Orders (<?php echo count($orders); ?>)</h2>
            <?php if (empty($orders)): ?>
                <p>No orders found.</p>
            <?php else: ?>
                <div class="orders-container">
                    <?php foreach ($orders as $order): 
                        $item_details = getOrderItemDetails($pdo, $order['order_type'], $order['item_id']);
                    ?>
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
                                            'landscaping' => 'Landscaping'
                                        ];
                                        echo $type_labels[$order['order_type']] ?? $order['order_type'];
                                        ?>
                                    </span>
                                </div>
                                <span class="status-badge status-<?php echo $order['status']; ?>">
                                    <?php echo ucfirst($order['status']); ?>
                                </span>
                            </div>
                            
                            <div class="order-details">
                                <div class="customer-info">
                                    <h4><i class="fas fa-user"></i> Customer Information</h4>
                                    <p><strong>Name:</strong> <?php echo htmlspecialchars($order['full_name']); ?></p>
                                    <p><strong>Email:</strong> <?php echo htmlspecialchars($order['email']); ?></p>
                                    <p><strong>Phone:</strong> <?php echo htmlspecialchars($order['user_phone']); ?></p>
                                    <?php if (isset($order['customer_location'])): ?>
                                        <p><strong>Location:</strong> <?php echo htmlspecialchars($order['customer_location']); ?></p>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="order-specifics">
                                    <h4><i class="fas fa-info-circle"></i> Order Details</h4>
                                    
                                    <?php if (isset($order['details']) && $order['details']): ?>
                                        <p><?php echo nl2br(htmlspecialchars($order['details'])); ?></p>
                                    <?php endif; ?>
                                    
                                    <?php if ($item_details): ?>
                                        <div class="item-details">
                                            <h5>Item Details:</h5>
                                            <?php if ($order['order_type'] === 'rent_house' || $order['order_type'] === 'buy_house'): ?>
                                                <p><strong>Property:</strong> <?php echo htmlspecialchars($item_details['title']); ?></p>
                                                <p><strong>Price:</strong> KSh <?php echo number_format($item_details['price'], 2); ?></p>
                                                <p><strong>Location:</strong> <?php echo htmlspecialchars($item_details['location']); ?></p>
                                                <?php if (isset($item_details['description'])): ?>
                                                    <p><strong>Description:</strong> <?php echo htmlspecialchars($item_details['description']); ?></p>
                                                <?php endif; ?>
                                            <?php elseif ($order['order_type'] === 'buy_land'): ?>
                                                <p><strong>Land Title:</strong> <?php echo htmlspecialchars($item_details['title']); ?></p>
                                                <p><strong>Price:</strong> KSh <?php echo number_format($item_details['price'], 2); ?></p>
                                                <p><strong>Location:</strong> <?php echo htmlspecialchars($item_details['location']); ?></p>
                                                <?php if (isset($item_details['size'])): ?>
                                                    <p><strong>Size:</strong> <?php echo htmlspecialchars($item_details['size']); ?></p>
                                                <?php endif; ?>
                                                <?php if (isset($item_details['description'])): ?>
                                                    <p><strong>Description:</strong> <?php echo htmlspecialchars($item_details['description']); ?></p>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="order-meta">
                                    <p><strong>Order Date:</strong> <?php echo date('M j, Y g:i A', strtotime($order['created_at'])); ?></p>
                                    <?php if ($order['status'] === 'completed' || $order['status'] === 'cancelled'): ?>
                                        <p><strong>Updated At:</strong> <?php echo date('M j, Y g:i A', strtotime($order['updated_at'] ?? $order['created_at'])); ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="order-actions">
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="update_status">
                                    <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                    <select name="status" onchange="this.form.submit()">
                                        <option value="pending" <?php echo $order['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                        <option value="completed" <?php echo $order['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                        <option value="cancelled" <?php echo $order['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                    </select>
                                </form>
                                <a href="tel:<?php echo $order['user_phone']; ?>" class="btn btn-call">
                                    <i class="fas fa-phone"></i> Call Customer
                                </a>
                                <?php if ($item_details): ?>
                                    <?php
                                    // Determine the correct view page based on order type
                                    $view_pages = [
                                        'rent_house' => 'view_rental.php',
                                        'buy_house' => 'view_sale.php',
                                        'buy_land' => 'view_land.php'
                                    ];
                                    if (isset($view_pages[$order['order_type']])) {
                                        $view_page = $view_pages[$order['order_type']] . '?id=' . $item_details['id'];
                                    ?>
                                    <a href="<?php echo $view_page; ?>" class="btn btn-view">
                                        <i class="fas fa-eye"></i> View Item
                                    </a>
                                    <?php } ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>