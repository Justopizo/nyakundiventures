<?php
session_start();
require_once 'config.php'; // Make sure this contains your database connection

// Check if ID parameter exists
if (!isset($_GET['id'])) {
    header("Location: sales_house.php");
    exit();
}

$house_id = intval($_GET['id']);

// Fetch the specific house details
$stmt = $pdo->prepare("SELECT * FROM sale_houses WHERE id = ?");
$stmt->execute([$house_id]);
$house = $stmt->fetch();

// If house doesn't exist, redirect back
if (!$house) {
    header("Location: sales_house.php");
    exit();
}

// Handle purchase request (same as in sales_house.php)
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'buy') {
    if (!isset($_SESSION['user_id'])) {
        $error = 'You must be logged in to make a purchase inquiry';
    } else {
        $customer_name = trim($_POST['customer_name']);
        $customer_phone = trim($_POST['customer_phone']);
        $customer_location = trim($_POST['customer_location']);
        $details = trim($_POST['details']);
        
        if (empty($customer_name) || empty($customer_phone) || empty($customer_location)) {
            $error = 'Please fill in all required fields';
        } elseif (strlen($customer_phone) !== 10 || !ctype_digit($customer_phone)) {
            $error = 'Phone number must be exactly 10 digits';
        } else {
            $stmt = $pdo->prepare("INSERT INTO orders (user_id, order_type, item_id, customer_name, customer_phone, customer_location, details) VALUES (?, 'buy_house', ?, ?, ?, ?, ?)");
            if ($stmt->execute([$_SESSION['user_id'], $house_id, $customer_name, $customer_phone, $customer_location, $details])) {
                $message = 'Purchase inquiry submitted successfully! We will contact you soon.';
            } else {
                $error = 'Failed to submit purchase inquiry. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($house['title']); ?> - Nyakundi Ventures</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #2980b9;
            --success-color: #27ae60;
            --danger-color: #e74c3c;
            --light-color: #ecf0f1;
            --dark-color: #2c3e50;
            --gray-color: #95a5a6;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #f5f7fa;
            color: #333;
            line-height: 1.6;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
        }
        
        .back-link i {
            margin-right: 5px;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
        
        .property-details-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 20px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .property-header {
            padding: 20px;
            border-bottom: 1px solid #eee;
        }
        
        .property-header h1 {
            font-size: 1.8rem;
            color: var(--dark-color);
            margin-bottom: 5px;
        }
        
        .property-id {
            font-size: 0.9rem;
            color: var(--gray-color);
        }
        
        .property-gallery {
            position: relative;
        }
        
        .main-image {
            width: 100%;
            height: 500px;
            object-fit: cover;
        }
        
        .property-content {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
            padding: 30px;
        }
        
        @media (max-width: 768px) {
            .property-content {
                grid-template-columns: 1fr;
            }
        }
        
        .property-info {
            padding-right: 20px;
        }
        
        .price-section {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 25px;
        }
        
        .price {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--success-color);
        }
        
        .status-badge {
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
        }
        
        .status-available {
            background-color: rgba(39, 174, 96, 0.1);
            color: var(--success-color);
        }
        
        .status-sold {
            background-color: rgba(231, 76, 60, 0.1);
            color: var(--danger-color);
        }
        
        .details-list {
            list-style: none;
            margin-bottom: 30px;
        }
        
        .details-list li {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            font-size: 1.05rem;
        }
        
        .details-list i {
            width: 25px;
            color: var(--primary-color);
            font-size: 1.1rem;
            margin-right: 10px;
        }
        
        .description {
            margin-top: 30px;
        }
        
        .description h2 {
            font-size: 1.4rem;
            margin-bottom: 15px;
            color: var(--dark-color);
        }
        
        .description p {
            white-space: pre-line;
            line-height: 1.8;
        }
        
        .sidebar {
            background: #f9f9f9;
            padding: 25px;
            border-radius: 8px;
            height: fit-content;
        }
        
        .sidebar h3 {
            margin-bottom: 20px;
            color: var(--dark-color);
            font-size: 1.3rem;
        }
        
        .action-buttons {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 12px 20px;
            border-radius: 6px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.3s;
            border: none;
            font-size: 1rem;
            gap: 8px;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }
        
        .btn-primary:hover {
            background-color: var(--secondary-color);
        }
        
        .btn-secondary {
            background-color: white;
            color: var(--primary-color);
            border: 1px solid var(--primary-color);
        }
        
        .btn-secondary:hover {
            background-color: #f0f8ff;
        }
        
        .btn-call {
            background-color: var(--success-color);
            color: white;
        }
        
        .btn-call:hover {
            background-color: #219653;
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
            overflow: auto;
            opacity: 0;
            transition: opacity 0.3s;
        }
        
        .modal.show {
            opacity: 1;
        }
        
        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 0;
            border-radius: 10px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 5px 30px rgba(0,0,0,0.3);
            transform: translateY(-20px);
            transition: transform 0.3s;
        }
        
        .modal.show .modal-content {
            transform: translateY(0);
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
            color: var(--dark-color);
            font-size: 1.5rem;
        }
        
        .close {
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            color: var(--gray-color);
            transition: color 0.3s;
        }
        
        .close:hover {
            color: var(--danger-color);
        }
        
        .modal form {
            padding: 20px;
        }
        
        .property-summary {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .property-summary h3 {
            margin-bottom: 5px;
            color: var(--dark-color);
            font-size: 1.2rem;
        }
        
        .property-summary p {
            color: var(--success-color);
            font-weight: 600;
            font-size: 1.1rem;
            margin: 0;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--dark-color);
        }
        
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(52,152,219,0.1);
        }
        
        .form-group textarea {
            height: 100px;
            resize: vertical;
        }
        
        .modal-actions {
            display: flex;
            gap: 15px;
            justify-content: flex-end;
            margin-top: 30px;
        }
        
        /* Alerts */
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            text-align: center;
            font-size: 1rem;
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
    </style>
</head>
<body>
    <div class="container">
        <a href="sales_house.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to Properties
        </a>
        
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <div class="property-details-container">
            <div class="property-header">
                <h1><?php echo htmlspecialchars($house['title']); ?></h1>
                <span class="property-id">Property ID: #<?php echo $house['id']; ?></span>
            </div>
            
            <div class="property-gallery">
                <?php if (!empty($house['images'])): ?>
                    <img src="<?php echo htmlspecialchars($house['images']); ?>" alt="<?php echo htmlspecialchars($house['title']); ?>" class="main-image" onerror="this.src='https://via.placeholder.com/800x500?text=Property+Image+Not+Available'">
                <?php else: ?>
                    <img src="https://via.placeholder.com/800x500?text=No+Image+Available" alt="No image available" class="main-image">
                <?php endif; ?>
            </div>
            
            <div class="property-content">
                <div class="property-info">
                    <div class="price-section">
                        <span class="price">KSH <?php echo number_format($house['price'], 2); ?></span>
                        <span class="status-badge status-<?php echo $house['status']; ?>">
                            <?php echo ucfirst($house['status']); ?>
                        </span>
                    </div>
                    
                    <ul class="details-list">
                        <li>
                            <i class="fas fa-map-marker-alt"></i>
                            <span><?php echo htmlspecialchars($house['location']); ?></span>
                        </li>
                        <li>
                            <i class="far fa-calendar-alt"></i>
                            <span>Listed: <?php echo date('F j, Y', strtotime($house['created_at'])); ?></span>
                        </li>
                    </ul>
                    
                    <div class="description">
                        <h2>Property Description</h2>
                        <?php if (!empty($house['description'])): ?>
                            <p><?php echo nl2br(htmlspecialchars($house['description'])); ?></p>
                        <?php else: ?>
                            <p>No detailed description available for this property.</p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="sidebar">
                    <h3>Interested in this property?</h3>
                    <div class="action-buttons">
                        <button class="btn btn-primary" onclick="openBuyModal(<?php echo $house['id']; ?>, '<?php echo htmlspecialchars(addslashes($house['title'])); ?>', <?php echo $house['price']; ?>)">
                            <i class="fas fa-shopping-cart"></i> Make an Offer
                        </button>
                        <a href="tel:0742907335" class="btn btn-call">
                            <i class="fas fa-phone"></i> Call Agent
                        </a>
                        <a href="mailto:info@nyakundiventures.com" class="btn btn-secondary">
                            <i class="fas fa-envelope"></i> Email Inquiry
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Buy Modal (same as in sales_house.php) -->
    <div id="buyModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-shopping-cart"></i> Purchase Inquiry</h2>
                <span class="close" onclick="closeBuyModal()">&times;</span>
            </div>
            <form method="POST" id="buyForm">
                <input type="hidden" name="action" value="buy">
                <input type="hidden" name="house_id" id="modalHouseId">
                
                <div class="property-summary">
                    <h3 id="modalPropertyTitle"></h3>
                    <p id="modalPropertyPrice"></p>
                </div>
                
                <div class="form-group">
                    <label for="customer_name">Full Name *</label>
                    <input type="text" id="customer_name" name="customer_name" value="<?php echo isset($_SESSION['full_name']) ? htmlspecialchars($_SESSION['full_name']) : ''; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="customer_phone">Phone Number *</label>
                    <input type="tel" id="customer_phone" name="customer_phone" placeholder="10-digit phone number" maxlength="10" required value="<?php echo isset($_SESSION['phone']) ? htmlspecialchars($_SESSION['phone']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="customer_location">Your Location *</label>
                    <input type="text" id="customer_location" name="customer_location" placeholder="Your current address" required>
                </div>
                
                <div class="form-group">
                    <label for="details">Additional Details</label>
                    <textarea id="details" name="details" placeholder="Any specific requirements, financing needs, or questions..."></textarea>
                </div>
                
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeBuyModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Submit Inquiry</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
    // Open buy modal with property details
    function openBuyModal(houseId, title, price) {
        document.getElementById('modalHouseId').value = houseId;
        document.getElementById('modalPropertyTitle').textContent = title;
        document.getElementById('modalPropertyPrice').textContent = 'KSH ' + price.toLocaleString();
        
        const modal = document.getElementById('buyModal');
        modal.style.display = 'block';
        setTimeout(() => {
            modal.classList.add('show');
        }, 10);
    }
    
    // Close buy modal
    function closeBuyModal() {
        const modal = document.getElementById('buyModal');
        modal.classList.remove('show');
        setTimeout(() => {
            modal.style.display = 'none';
        }, 300);
    }
    
    // Close modal when clicking outside
    window.onclick = function(event) {
        const modal = document.getElementById('buyModal');
        if (event.target === modal) {
            closeBuyModal();
        }
    }
    
    // Phone number validation
    document.getElementById('customer_phone')?.addEventListener('input', function(e) {
        this.value = this.value.replace(/[^0-9]/g, '');
        if (this.value.length > 10) {
            this.value = this.value.slice(0, 10);
        }
    });
    
    // Form validation
    document.getElementById('buyForm')?.addEventListener('submit', function(e) {
        const phone = document.getElementById('customer_phone');
        if (phone && phone.value.length !== 10) {
            e.preventDefault();
            alert('Phone number must be exactly 10 digits');
            phone.focus();
        }
    });
    </script>
</body>
</html>