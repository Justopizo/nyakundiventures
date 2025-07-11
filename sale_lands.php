<?php
$message = '';
$error = '';

// Handle purchase request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'buy_land') {
    $land_id = intval($_POST['land_id']);
    $customer_name = trim($_POST['customer_name']);
    $customer_phone = trim($_POST['customer_phone']);
    $customer_location = trim($_POST['customer_location']);
    $details = trim($_POST['details']);
    
    if (empty($customer_name) || empty($customer_phone) || empty($customer_location)) {
        $error = 'Please fill in all required fields';
    } elseif (strlen($customer_phone) !== 10 || !ctype_digit($customer_phone)) {
        $error = 'Phone number must be exactly 10 digits';
    } else {
        $stmt = $pdo->prepare("INSERT INTO orders (user_id, order_type, item_id, customer_name, customer_phone, customer_location, details) VALUES (?, 'buy_land', ?, ?, ?, ?, ?)");
        if ($stmt->execute([$_SESSION['user_id'], $land_id, $customer_name, $customer_phone, $customer_location, $details])) {
            $message = 'Land purchase inquiry submitted successfully! We will contact you soon.';
        } else {
            $error = 'Failed to submit land purchase inquiry. Please try again.';
        }
    }
}

// Check if we're viewing a single land detail
$land_detail = null;
$view_id = null;
if (isset($_GET['view']) && is_numeric($_GET['view'])) {
    $view_id = intval($_GET['view']);
    $stmt = $pdo->prepare("SELECT * FROM sale_lands WHERE id = ? AND status = 'available'");
    $stmt->execute([$view_id]);
    $land_detail = $stmt->fetch();
}

// Get search parameters (preserve them for back navigation)
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$location_filter = isset($_GET['location']) ? trim($_GET['location']) : '';
$max_price = isset($_GET['max_price']) ? intval($_GET['max_price']) : 0;
$size_filter = isset($_GET['size']) ? trim($_GET['size']) : '';

// Build back URL with preserved search parameters
$back_params = [];
if (!empty($search)) $back_params['search'] = $search;
if (!empty($location_filter)) $back_params['location'] = $location_filter;
if ($max_price > 0) $back_params['max_price'] = $max_price;
if (!empty($size_filter)) $back_params['size'] = $size_filter;
$back_url = '?' . http_build_query($back_params);

// Get listings only if not viewing details
if (!$land_detail) {
    // Build query for listings
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
    
    if (!empty($size_filter)) {
        $where_conditions[] = "size LIKE ?";
        $params[] = "%$size_filter%";
    }
    
    $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
    $stmt = $pdo->prepare("SELECT * FROM sale_lands $where_clause ORDER BY created_at DESC");
    $stmt->execute($params);
    $sale_lands = $stmt->fetchAll();
    
    // Get unique locations for filter
    $stmt = $pdo->query("SELECT DISTINCT location FROM sale_lands WHERE status = 'available' ORDER BY location");
    $locations = $stmt->fetchAll();
}

// Function to build view URL with preserved search parameters
function buildViewUrl($land_id, $search_params) {
    $params = $search_params;
    $params['view'] = $land_id;
    return '?' . http_build_query($params);
}

// Function to get optimized image URL
function getOptimizedImageUrl($image_path, $width = 800, $height = 600) {
    // If it's already a placeholder, return as is
    if (strpos($image_path, 'placeholder.svg') !== false) {
        return $image_path;
    }
    
    // For real images, return a placeholder first then load the actual image
    return '/placeholder.svg?height='.$height.'&width='.$width;
}
?>

<h1 class="page-title"><i class="fas fa-map"></i> Land for Sale</h1>

<?php if ($message): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<?php if ($land_detail): ?>
    <!-- Land Detail View -->
    <div class="land-detail-container">
        <button class="btn btn-back" onclick="goBackToListings()">
            <i class="fas fa-arrow-left"></i> Back to Listings
        </button>
        
        <div class="land-detail">
            <div class="land-gallery">
                <?php if ($land_detail['images']): ?>
                    <?php $images = array_filter(array_map('trim', explode(',', $land_detail['images']))); ?>
                    <div class="main-image">
                        <img id="mainImage" 
                             src="<?php echo getOptimizedImageUrl($images[0], 800, 600); ?>" 
                             alt="<?php echo htmlspecialchars($land_detail['title']); ?>"
                             data-src="<?php echo htmlspecialchars($images[0]); ?>"
                             onerror="this.src='/placeholder.svg?height=600&width=800'">
                    </div>
                    <?php if (count($images) > 1): ?>
                        <div class="thumbnail-container">
                            <?php foreach ($images as $index => $image): ?>
                                <div class="thumbnail" onclick="changeMainImage('<?php echo htmlspecialchars($image); ?>')">
                                    <img src="<?php echo getOptimizedImageUrl($image, 200, 150); ?>" 
                                         alt="Thumbnail <?php echo $index + 1; ?>"
                                         data-src="<?php echo htmlspecialchars($image); ?>"
                                         onerror="this.src='/placeholder.svg?height=150&width=200'">
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="main-image">
                        <img src="/placeholder.svg?height=600&width=800" alt="No image available">
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="land-info">
                <h2><?php echo htmlspecialchars($land_detail['title']); ?></h2>
                <div class="price-section">
                    <span class="price">KSH <?php echo number_format($land_detail['price']); ?></span>
                    <?php if ($land_detail['price_per_unit']): ?>
                        <span class="price-per-unit">(<?php echo htmlspecialchars($land_detail['price_per_unit']); ?>)</span>
                    <?php endif; ?>
                </div>
                
                <div class="meta-info">
                    <div class="meta-item">
                        <i class="fas fa-map-marker-alt"></i>
                        <span><?php echo htmlspecialchars($land_detail['location']); ?></span>
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-ruler-combined"></i>
                        <span><?php echo htmlspecialchars($land_detail['size']); ?></span>
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-layer-group"></i>
                        <span>Reference: #<?php echo htmlspecialchars($land_detail['id']); ?></span>
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-calendar-alt"></i>
                        <span>Listed: <?php echo date('M d, Y', strtotime($land_detail['created_at'])); ?></span>
                    </div>
                </div>
                
                <div class="description">
                    <h3>Description</h3>
                    <p><?php echo nl2br(htmlspecialchars($land_detail['description'])); ?></p>
                </div>
                
                <div class="features">
                    <h3>Features</h3>
                    <ul>
                        <?php if ($land_detail['features']): ?>
                            <?php $features = array_filter(array_map('trim', explode(',', $land_detail['features']))); ?>
                            <?php foreach ($features as $feature): ?>
                                <li><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($feature); ?></li>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <li><i class="fas fa-info-circle"></i> No specific features listed</li>
                        <?php endif; ?>
                    </ul>
                </div>
                
                <div class="land-actions">
                    <button class="btn btn-primary" onclick="openBuyLandModal(<?php echo $land_detail['id']; ?>, '<?php echo htmlspecialchars($land_detail['title'], ENT_QUOTES); ?>', <?php echo $land_detail['price']; ?>, '<?php echo htmlspecialchars($land_detail['size'], ENT_QUOTES); ?>')">
                        <i class="fas fa-shopping-cart"></i> Buy Now
                    </button>
                    <a href="tel:0742907335" class="btn btn-call">
                        <i class="fas fa-phone"></i> Call Seller
                    </a>
                    <button class="btn btn-secondary" onclick="shareProperty()">
                        <i class="fas fa-share-alt"></i> Share
                    </button>
                </div>
            </div>
        </div>
    </div>

<?php else: ?>
    <!-- Land Listing View -->
    <div class="search-filters">
        <form method="GET" class="filter-form">
            <div class="filter-row">
                <div class="form-group">
                    <input type="text" name="search" placeholder="Search land..." value="<?php echo htmlspecialchars($search); ?>">
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
                    <input type="text" name="size" placeholder="Size (e.g., 1 Acre)" value="<?php echo htmlspecialchars($size_filter); ?>">
                </div>
                <div class="form-group">
                    <input type="number" name="max_price" placeholder="Max Price (KSH)" value="<?php echo $max_price > 0 ? $max_price : ''; ?>">
                </div>
                <div class="form-group">
                    <button type="submit" class="btn-search"><i class="fas fa-search"></i> Search</button>
                    <?php if (!empty($search) || !empty($location_filter) || $max_price > 0 || !empty($size_filter)): ?>
                        <a href="?" class="btn-clear"><i class="fas fa-times"></i> Clear</a>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>

    <div class="properties-container">
        <?php if (empty($sale_lands)): ?>
            <div class="no-results">
                <i class="fas fa-map"></i>
                <h3>No land for sale found</h3>
                <p>Try adjusting your search criteria or check back later for new listings.</p>
                <?php if (!empty($search) || !empty($location_filter) || $max_price > 0 || !empty($size_filter)): ?>
                    <a href="?" class="btn btn-primary">View All Listings</a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="results-info">
                <p>Found <?php echo count($sale_lands); ?> land<?php echo count($sale_lands) !== 1 ? 's' : ''; ?> for sale</p>
            </div>
            <div class="properties-grid">
                <?php foreach ($sale_lands as $land): ?>
                    <div class="property-card">
                        <?php if ($land['images']): ?>
                            <?php $images = array_filter(array_map('trim', explode(',', $land['images']))); ?>
                            <div class="property-image">
                                <img src="<?php echo getOptimizedImageUrl($images[0], 400, 300); ?>" 
                                     alt="<?php echo htmlspecialchars($land['title']); ?>" 
                                     data-src="<?php echo htmlspecialchars($images[0]); ?>"
                                     onerror="this.src='/placeholder.svg?height=300&width=400'">
                                <?php if (count($images) > 1): ?>
                                    <div class="image-count">
                                        <i class="fas fa-images"></i> <?php echo count($images); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <div class="property-image">
                                <img src="/placeholder.svg?height=300&width=400" alt="No image available">
                            </div>
                        <?php endif; ?>
                        
                        <div class="property-content">
                            <h3><?php echo htmlspecialchars($land['title']); ?></h3>
                            <div class="property-details">
                                <p class="price"><i class="fas fa-money-bill"></i> KSH <?php echo number_format($land['price']); ?></p>
                                <p class="location"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($land['location']); ?></p>
                                <p class="size"><i class="fas fa-ruler"></i> Size: <?php echo htmlspecialchars($land['size']); ?></p>
                                <?php if ($land['description']): ?>
                                    <p class="description"><?php echo htmlspecialchars(substr($land['description'], 0, 100)); ?>...</p>
                                <?php endif; ?>
                            </div>
                            <div class="property-actions">
                                <button class="btn btn-primary" onclick="openBuyLandModal(<?php echo $land['id']; ?>, '<?php echo htmlspecialchars($land['title'], ENT_QUOTES); ?>', <?php echo $land['price']; ?>, '<?php echo htmlspecialchars($land['size'], ENT_QUOTES); ?>')">
                                    <i class="fas fa-shopping-cart"></i> Buy Now
                                </button>
                                <a href="<?php echo buildViewUrl($land['id'], compact('search', 'location_filter', 'max_price', 'size_filter')); ?>" class="btn btn-secondary">
                                    <i class="fas fa-eye"></i> View Details
                                </a>
                                <a href="tel:0742907335" class="btn btn-call">
                                    <i class="fas fa-phone"></i> Call
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<!-- Buy Land Modal -->
<div id="buyLandModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2><i class="fas fa-map"></i> Land Purchase Inquiry</h2>
            <span class="close" onclick="closeBuyLandModal()">&times;</span>
        </div>
        <form method="POST" id="buyLandForm">
            <input type="hidden" name="action" value="buy_land">
            <input type="hidden" name="land_id" id="modalLandId">
            
            <div class="property-summary">
                <h3 id="modalLandTitle"></h3>
                <p id="modalLandPrice"></p>
                <p id="modalLandSize"></p>
            </div>
            
            <div class="form-group">
                <label for="customer_name">Full Name *</label>
                <input type="text" id="customer_name" name="customer_name" value="<?php echo isset($_SESSION['full_name']) ? htmlspecialchars($_SESSION['full_name']) : ''; ?>" required>
            </div>
            
            <div class="form-group">
                <label for="customer_phone">Phone Number *</label>
                <input type="tel" id="customer_phone" name="customer_phone" placeholder="10-digit phone number" maxlength="10" required>
            </div>
            
            <div class="form-group">
                <label for="customer_location">Your Location *</label>
                <input type="text" id="customer_location" name="customer_location" placeholder="Your current address" required>
            </div>
            
            <div class="form-group">
                <label for="details">Additional Details</label>
                <textarea id="details" name="details" placeholder="Intended use of land, financing needs, timeline, or any questions..."></textarea>
            </div>
            
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeBuyLandModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Submit Inquiry</button>
            </div>
        </form>
    </div>
</div>

<style>
.alert {
    padding: 15px;
    margin-bottom: 20px;
    border-radius: 8px;
    text-align: center;
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

.search-filters {
    background: white;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    margin-bottom: 30px;
}

.filter-row {
    display: grid;
    grid-template-columns: 2fr 1fr 1fr 1fr auto;
    gap: 15px;
    align-items: end;
}

.form-group input,
.form-group select {
    width: 100%;
    padding: 12px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 1rem;
}

.btn-search, .btn-clear {
    padding: 12px 20px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 1rem;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    margin-right: 10px;
}

.btn-search {
    background: #667eea;
    color: white;
}

.btn-search:hover {
    background: #5a6fd8;
}

.btn-clear {
    background: #dc3545;
    color: white;
}

.btn-clear:hover {
    background: #c82333;
}

.results-info {
    margin-bottom: 20px;
    color: #666;
    font-size: 0.9rem;
}

.properties-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 25px;
}

.property-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    overflow: hidden;
    transition: transform 0.2s, box-shadow 0.2s;
}

.property-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}

.property-image {
    height: 200px;
    overflow: hidden;
    position: relative;
}

.property-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s;
}

.property-card:hover .property-image img {
    transform: scale(1.05);
}

.image-count {
    position: absolute;
    top: 10px;
    right: 10px;
    background: rgba(0,0,0,0.7);
    color: white;
    padding: 5px 10px;
    border-radius: 15px;
    font-size: 0.8rem;
}

.property-content {
    padding: 20px;
}

.property-content h3 {
    margin-bottom: 15px;
    color: #333;
    font-size: 1.2rem;
}

.property-details p {
    margin-bottom: 8px;
    color: #666;
}

.property-details i {
    margin-right: 8px;
    color: #667eea;
    width: 15px;
}

.price {
    font-size: 1.1rem;
    font-weight: 600;
    color: #28a745 !important;
}

.size {
    font-weight: 500;
    color: #495057 !important;
}

.property-actions {
    display: flex;
    gap: 10px;
    margin-top: 20px;
    flex-wrap: wrap;
}

.btn {
    padding: 8px 15px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    text-decoration: none;
    font-size: 0.9rem;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    flex: 1;
    justify-content: center;
    min-width: 80px;
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

.btn-call {
    background: #28a745;
    color: white;
}

.btn-call:hover {
    background: #218838;
}

.no-results {
    text-align: center;
    padding: 60px 20px;
    color: #666;
}

.no-results i {
    font-size: 4rem;
    margin-bottom: 20px;
    color: #ddd;
}

.no-results h3 {
    margin-bottom: 10px;
    color: #333;
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
    max-width: 500px;
    max-height: 90vh;
    overflow-y: auto;
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
    color: #333;
}

.property-summary p {
    color: #28a745;
    font-weight: 600;
    font-size: 1.1rem;
    margin-bottom: 5px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 500;
    color: #333;
}

.form-group input,
.form-group textarea {
    width: 100%;
    padding: 12px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 1rem;
}

.form-group textarea {
    height: 80px;
    resize: vertical;
}

.modal-actions {
    display: flex;
    gap: 15px;
    justify-content: flex-end;
    margin-top: 30px;
}

/* Land Detail Styles */
.land-detail-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.btn-back {
    background: #6c757d;
    color: white;
    padding: 10px 15px;
    margin-bottom: 20px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

.btn-back:hover {
    background: #5a6268;
}

.land-detail {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 30px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    padding: 30px;
}

.land-gallery {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.main-image {
    height: 400px;
    overflow: hidden;
    border-radius: 8px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.main-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s;
}

.main-image:hover img {
    transform: scale(1.02);
}

.thumbnail-container {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 10px;
}

.thumbnail {
    height: 100px;
    overflow: hidden;
    border-radius: 6px;
    cursor: pointer;
    transition: transform 0.2s;
    border: 2px solid transparent;
}

.thumbnail:hover {
    transform: scale(1.05);
    border-color: #667eea;
}

.thumbnail img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.land-info {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.price-section {
    display: flex;
    align-items: center;
    gap: 10px;
}

.price {
    font-size: 1.8rem;
    font-weight: 700;
    color: #28a745;
}

.price-per-unit {
    color: #6c757d;
    font-size: 1rem;
}

.meta-info {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 15px;
    padding: 15px 0;
    border-top: 1px solid #eee;
    border-bottom: 1px solid #eee;
}

.meta-item {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #495057;
}

.meta-item i {
    color: #667eea;
}

.description {
    line-height: 1.6;
}

.features ul {
    list-style: none;
    padding: 0;
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 10px;
}

.features li {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #495057;
}

.features i {
    color: #28a745;
}

.land-actions {
    display: flex;
    gap: 15px;
    margin-top: 20px;
}

@media (max-width: 768px) {
    .filter-row {
        grid-template-columns: 1fr;
    }
    
    .properties-grid {
        grid-template-columns: 1fr;
    }
    
    .property-actions {
        flex-direction: column;
    }
    
    .modal-content {
        width: 95%;
        margin: 2% auto;
    }
    
    .land-detail {
        grid-template-columns: 1fr;
    }
    
    .main-image {
        height: 300px;
    }
    
    .features ul {
        grid-template-columns: 1fr;
    }
    
    .land-actions {
        flex-direction: column;
    }
    
    .meta-info {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
// Store search parameters for back navigation
const searchParams = {
    search: '<?php echo addslashes($search); ?>',
    location: '<?php echo addslashes($location_filter); ?>',
    max_price: '<?php echo $max_price; ?>',
    size: '<?php echo addslashes($size_filter); ?>'
};

function openBuyLandModal(landId, title, price, size) {
    document.getElementById('modalLandId').value = landId;
    document.getElementById('modalLandTitle').textContent = title;
    document.getElementById('modalLandPrice').textContent = 'KSH ' + price.toLocaleString();
    document.getElementById('modalLandSize').textContent = 'Size: ' + size;
    document.getElementById('buyLandModal').style.display = 'block';
}

function closeBuyLandModal() {
    document.getElementById('buyLandModal').style.display = 'none';
}

function goBackToListings() {
    const params = new URLSearchParams();
    if (searchParams.search) params.set('search', searchParams.search);
    if (searchParams.location) params.set('location', searchParams.location);
    if (searchParams.max_price && searchParams.max_price > 0) params.set('max_price', searchParams.max_price);
    if (searchParams.size) params.set('size', searchParams.size);
    
    const url = params.toString() ? '?' + params.toString() : '?';
    window.location.href = url;
}

function changeMainImage(imageSrc) {
    const mainImage = document.getElementById('mainImage');
    if (mainImage) {
        // Show loading state
        mainImage.src = '/placeholder.svg?height=600&width=800';
        
        // Load the actual image
        const img = new Image();
        img.onload = function() {
            mainImage.src = imageSrc;
        };
        img.onerror = function() {
            mainImage.src = '/placeholder.svg?height=600&width=800';
        };
        img.src = imageSrc;
    }
}

function shareProperty() {
    if (navigator.share) {
        navigator.share({
            title: document.querySelector('.land-info h2').textContent,
            text: 'Check out this land for sale',
            url: window.location.href
        });
    } else {
        // Fallback: copy to clipboard
        navigator.clipboard.writeText(window.location.href).then(() => {
            alert('Link copied to clipboard!');
        });
    }
}

// Phone number validation
document.getElementById('customer_phone').addEventListener('input', function(e) {
    this.value = this.value.replace(/[^0-9]/g, '');
});

// Close modal when clicking outside
window.onclick = function(event) {
    const buyLandModal = document.getElementById('buyLandModal');
    if (event.target === buyLandModal) {
        closeBuyLandModal();
    }
}

// Image loading optimization
document.addEventListener('DOMContentLoaded', function() {
    // Lazy load images with IntersectionObserver
    if ('IntersectionObserver' in window) {
        const lazyImages = document.querySelectorAll('img[data-src]');
        
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    const src = img.getAttribute('data-src');
                    
                    // Load the actual image
                    const newImg = new Image();
                    newImg.onload = function() {
                        img.src = src;
                        img.removeAttribute('data-src');
                    };
                    newImg.onerror = function() {
                        img.src = '/placeholder.svg?height=' + img.height + '&width=' + img.width;
                    };
                    newImg.src = src;
                    
                    observer.unobserve(img);
                }
            });
        });
        
        lazyImages.forEach(img => {
            imageObserver.observe(img);
        });
    } else {
        // Fallback for browsers without IntersectionObserver
        const lazyImages = document.querySelectorAll('img[data-src]');
        lazyImages.forEach(img => {
            const src = img.getAttribute('data-src');
            img.src = src;
            img.onerror = function() {
                this.src = '/placeholder.svg?height=' + this.height + '&width=' + this.width;
            };
        });
    }
    
    // Preload main image on detail view
    const mainImage = document.getElementById('mainImage');
    if (mainImage && mainImage.hasAttribute('data-src')) {
        const src = mainImage.getAttribute('data-src');
        const img = new Image();
        img.onload = function() {
            mainImage.src = src;
        };
        img.onerror = function() {
            mainImage.src = '/placeholder.svg?height=600&width=800';
        };
        img.src = src;
    }
});

// Form validation
document.getElementById('buyLandForm').addEventListener('submit', function(e) {
    const phone = document.getElementById('customer_phone').value;
    if (phone.length !== 10 || !/^\d+$/.test(phone)) {
        e.preventDefault();
        alert('Please enter a valid 10-digit phone number');
        return false;
    }
});
</script>