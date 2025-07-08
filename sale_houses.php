<?php
$message = '';
$error = '';

// Handle purchase request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'buy') {
    if (!isset($_SESSION['user_id'])) {
        $error = 'You must be logged in to make a purchase inquiry';
    } else {
        $house_id = intval($_POST['house_id']);
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

// Get search parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$location_filter = isset($_GET['location']) ? trim($_GET['location']) : '';
$max_price = isset($_GET['max_price']) ? floatval($_GET['max_price']) : 0;

// Build query
$where_conditions = ["status = 'available'"];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(title LIKE ? OR description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($location_filter)) {
    $where_conditions[] = "location LIKE ?";
    $params[] = "%$location_filter%";
}

if ($max_price > 0) {
    $where_conditions[] = "price <= ?";
    $params[] = $max_price;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get all available houses for sale
$stmt = $pdo->prepare("SELECT * FROM sale_houses $where_clause ORDER BY created_at DESC");
$stmt->execute($params);
$sale_houses = $stmt->fetchAll();

// Get unique locations for filter
$stmt = $pdo->query("SELECT DISTINCT location FROM sale_houses WHERE status = 'available' ORDER BY location");
$locations = $stmt->fetchAll();
?>

<h1 class="page-title"><i class="fas fa-home"></i> Houses for Sale</h1>

<?php if ($message): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<div class="search-filters">
    <form method="GET" class="filter-form">
        <div class="filter-row">
            <div class="form-group">
                <input type="text" name="search" placeholder="Search properties..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="form-group">
                <select name="location">
                    <option value="">All Locations</option>
                    <?php foreach ($locations as $loc): ?>
                        <option value="<?php echo htmlspecialchars($loc['location']); ?>" <?php echo $location_filter === $loc['location'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($loc['location']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <input type="number" name="max_price" placeholder="Max Price (KSH)" min="0" step="1000" value="<?php echo $max_price > 0 ? htmlspecialchars($max_price) : ''; ?>">
            </div>
            <div class="form-group">
                <button type="submit" class="btn-search"><i class="fas fa-search"></i> Search</button>
                <button type="button" class="btn-reset" onclick="window.location.href='sales_house.php'"><i class="fas fa-sync-alt"></i> Reset</button>
            </div>
        </div>
    </form>
</div>

<div class="properties-container">
    <?php if (empty($sale_houses)): ?>
        <div class="no-results">
            <i class="fas fa-home"></i>
            <h3>No houses for sale found</h3>
            <p>Try adjusting your search criteria or check back later for new listings.</p>
        </div>
    <?php else: ?>
        <div class="properties-grid">
            <?php foreach ($sale_houses as $house): ?>
                <div class="property-card">
                    <?php if (!empty($house['images'])): ?>
                        <div class="property-image">
                            <img src="<?php echo htmlspecialchars($house['images']); ?>" alt="<?php echo htmlspecialchars($house['title']); ?>" onerror="this.onerror=null;this.src='https://via.placeholder.com/600x400?text=House+Image'">
                        </div>
                    <?php else: ?>
                        <div class="property-image">
                            <img src="https://via.placeholder.com/600x400?text=No+Image" alt="No image available">
                        </div>
                    <?php endif; ?>
                    
                    <div class="property-content">
                        <div class="property-header">
                            <h3><?php echo htmlspecialchars($house['title']); ?></h3>
                            <span class="property-id">#<?php echo $house['id']; ?></span>
                        </div>
                        <div class="property-details">
                            <p class="price"><i class="fas fa-money-bill-wave"></i> KSH <?php echo number_format($house['price'], 2); ?></p>
                            <p class="location"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($house['location']); ?></p>
                            <?php if (!empty($house['description'])): ?>
                                <p class="description"><?php echo htmlspecialchars(substr($house['description'], 0, 100)); ?>...</p>
                            <?php endif; ?>
                            <p class="date-added"><i class="far fa-calendar-alt"></i> Listed: <?php echo date('M j, Y', strtotime($house['created_at'])); ?></p>
                        </div>
                        <div class="property-actions">
                            <button class="btn btn-primary" onclick="openBuyModal(<?php echo $house['id']; ?>, '<?php echo htmlspecialchars(addslashes($house['title'])); ?>', <?php echo $house['price']; ?>)">
                                <i class="fas fa-shopping-cart"></i> Buy Now
                            </button>
                            <a href="property_details.php?id=<?php echo $house['id']; ?>" class="btn btn-secondary">
                                <i class="fas fa-eye"></i> View Details
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Buy Modal -->
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

<style>
/* Base Styles */
.page-title {
    color: #2c3e50;
    margin-bottom: 20px;
    font-size: 2rem;
}

.page-title i {
    margin-right: 10px;
    color: #3498db;
}

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

/* Search Filters */
.search-filters {
    background: #ffffff;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 2px 15px rgba(0,0,0,0.08);
    margin-bottom: 30px;
}

.filter-row {
    display: grid;
    grid-template-columns: 2fr 1fr 1fr auto auto;
    gap: 15px;
    align-items: end;
}

@media (max-width: 992px) {
    .filter-row {
        grid-template-columns: 1fr 1fr;
    }
}

@media (max-width: 576px) {
    .filter-row {
        grid-template-columns: 1fr;
    }
}

.form-group {
    margin-bottom: 0;
}

.form-group input,
.form-group select {
    width: 100%;
    padding: 12px;
    border: 1px solid #e0e0e0;
    border-radius: 6px;
    font-size: 1rem;
    transition: border-color 0.3s;
}

.form-group input:focus,
.form-group select:focus {
    outline: none;
    border-color: #3498db;
    box-shadow: 0 0 0 3px rgba(52,152,219,0.1);
}

.btn-search,
.btn-reset {
    padding: 12px 20px;
    color: white;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 1rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: all 0.3s;
}

.btn-search {
    background: #3498db;
}

.btn-search:hover {
    background: #2980b9;
}

.btn-reset {
    background: #95a5a6;
}

.btn-reset:hover {
    background: #7f8c8d;
}

/* Properties Grid */
.properties-container {
    margin-top: 20px;
}

.properties-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 25px;
}

@media (max-width: 768px) {
    .properties-grid {
        grid-template-columns: 1fr;
    }
}

.property-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.08);
    overflow: hidden;
    transition: transform 0.3s, box-shadow 0.3s;
}

.property-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.12);
}

.property-image {
    height: 220px;
    overflow: hidden;
}

.property-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.5s;
}

.property-card:hover .property-image img {
    transform: scale(1.05);
}

.property-content {
    padding: 20px;
}

.property-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.property-header h3 {
    margin: 0;
    color: #2c3e50;
    font-size: 1.25rem;
    font-weight: 600;
}

.property-id {
    font-size: 0.8rem;
    color: #7f8c8d;
    background: #f5f5f5;
    padding: 3px 8px;
    border-radius: 4px;
}

.property-details {
    margin-bottom: 15px;
}

.property-details p {
    margin: 8px 0;
    color: #34495e;
    display: flex;
    align-items: center;
    gap: 8px;
}

.property-details i {
    color: #3498db;
    width: 16px;
    text-align: center;
}

.price {
    font-size: 1.1rem;
    font-weight: 700;
    color: #27ae60 !important;
}

.location {
    color: #7f8c8d !important;
}

.description {
    color: #7f8c8d !important;
    line-height: 1.5;
}

.date-added {
    font-size: 0.85rem;
    color: #95a5a6 !important;
}

.property-actions {
    display: flex;
    gap: 10px;
    margin-top: 20px;
}

.btn {
    padding: 10px 15px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 0.9rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 5px;
    flex: 1;
    transition: all 0.3s;
    text-decoration: none;
}

.btn-primary {
    background: #3498db;
    color: white;
}

.btn-primary:hover {
    background: #2980b9;
}

.btn-secondary {
    background: #95a5a6;
    color: white;
}

.btn-secondary:hover {
    background: #7f8c8d;
}

/* No Results */
.no-results {
    text-align: center;
    padding: 60px 20px;
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 15px rgba(0,0,0,0.05);
}

.no-results i {
    font-size: 4rem;
    margin-bottom: 20px;
    color: #bdc3c7;
}

.no-results h3 {
    margin-bottom: 10px;
    color: #2c3e50;
    font-size: 1.5rem;
}

.no-results p {
    color: #7f8c8d;
    font-size: 1.1rem;
    max-width: 500px;
    margin: 0 auto;
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
    color: #2c3e50;
    font-size: 1.5rem;
}

.close {
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
    color: #95a5a6;
    transition: color 0.3s;
}

.close:hover {
    color: #e74c3c;
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
    color: #2c3e50;
    font-size: 1.2rem;
}

.property-summary p {
    color: #27ae60;
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
    color: #2c3e50;
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
    border-color: #3498db;
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
</style>

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