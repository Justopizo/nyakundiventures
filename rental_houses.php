<?php
$message = '';
$error = '';

// Handle rental request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'rent') {
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
        $stmt = $pdo->prepare("INSERT INTO orders (user_id, order_type, item_id, customer_name, customer_phone, customer_location, details) VALUES (?, 'rent_house', ?, ?, ?, ?, ?)");
        if ($stmt->execute([$_SESSION['user_id'], $house_id, $customer_name, $customer_phone, $customer_location, $details])) {
            $message = 'Rental request submitted successfully! We will contact you soon.';
        } else {
            $error = 'Failed to submit rental request. Please try again.';
        }
    }
}

// Get search parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$location_filter = isset($_GET['location']) ? trim($_GET['location']) : '';
$max_price = isset($_GET['max_price']) ? intval($_GET['max_price']) : 0;

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

$where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
$stmt = $pdo->prepare("SELECT * FROM rental_houses $where_clause ORDER BY created_at DESC");
$stmt->execute($params);
$rental_houses = $stmt->fetchAll();

// Get unique locations for filter
$stmt = $pdo->query("SELECT DISTINCT location FROM rental_houses WHERE status = 'available' ORDER BY location");
$locations = $stmt->fetchAll();

// Function to get first image from JSON or comma-separated string
function getFirstImage($images_data) {
    if (empty($images_data)) {
        return null;
    }
    
    // Try to decode as JSON first (new format)
    $decoded = json_decode($images_data, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded) && !empty($decoded)) {
        return $decoded[0];
    }
    
    // Fallback to comma-separated format (old format)
    $images_array = explode(',', $images_data);
    return !empty($images_array) ? trim($images_array[0]) : null;
}

// Function to get all images from JSON or comma-separated string
function getAllImages($images_data) {
    if (empty($images_data)) {
        return [];
    }
    
    // Try to decode as JSON first (new format)
    $decoded = json_decode($images_data, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
        return $decoded;
    }
    
    // Fallback to comma-separated format (old format)
    $images_array = explode(',', $images_data);
    return array_map('trim', array_filter($images_array));
}
?>

<h1 class="page-title"><i class="fas fa-home"></i> Houses for Rent</h1>

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
                <input type="number" name="max_price" placeholder="Max Price (KSH)" value="<?php echo $max_price > 0 ? $max_price : ''; ?>">
            </div>
            <div class="form-group">
                <button type="submit" class="btn-search"><i class="fas fa-search"></i> Search</button>
            </div>
        </div>
    </form>
</div>

<div class="properties-container">
    <?php if (empty($rental_houses)): ?>
        <div class="no-results">
            <i class="fas fa-home"></i>
            <h3>No rental properties found</h3>
            <p>Try adjusting your search criteria or check back later for new listings.</p>
        </div>
    <?php else: ?>
        <div class="properties-grid">
            <?php foreach ($rental_houses as $house): ?>
                <div class="property-card">
                    <?php 
                    $first_image = getFirstImage($house['images']);
                    if ($first_image): 
                    ?>
                        <div class="property-image">
                            <img src="<?php echo htmlspecialchars($first_image); ?>" 
                                 alt="<?php echo htmlspecialchars($house['title']); ?>" 
                                 onerror="this.src='/placeholder.svg?height=200&width=300'"
                                 loading="lazy">
                        </div>
                    <?php else: ?>
                        <div class="property-image">
                            <img src="/placeholder.svg?height=200&width=300" 
                                 alt="No image available" 
                                 loading="lazy">
                        </div>
                    <?php endif; ?>
                    
                    <div class="property-content">
                        <h3><?php echo htmlspecialchars($house['title']); ?></h3>
                        <div class="property-details">
                            <p class="price"><i class="fas fa-money-bill"></i> KSH <?php echo number_format($house['price']); ?>/month</p>
                            <p class="location"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($house['location']); ?></p>
                            <?php if ($house['description']): ?>
                                <p class="description"><?php echo htmlspecialchars(substr($house['description'], 0, 100)); ?>...</p>
                            <?php endif; ?>
                        </div>
                        <div class="property-actions">
                            <button class="btn btn-primary" onclick="openRentModal(<?php echo $house['id']; ?>, '<?php echo htmlspecialchars($house['title']); ?>', <?php echo $house['price']; ?>)">
                                <i class="fas fa-key"></i> Rent Now
                            </button>
                            <button class="btn btn-secondary" onclick="viewDetails(<?php echo $house['id']; ?>)">
                                <i class="fas fa-eye"></i> View Details
                            </button>
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

<!-- Rent Modal -->
<div id="rentModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2><i class="fas fa-key"></i> Rent Property</h2>
            <span class="close" onclick="closeRentModal()">&times;</span>
        </div>
        <form method="POST" id="rentForm">
            <input type="hidden" name="action" value="rent">
            <input type="hidden" name="house_id" id="modalHouseId">
            
            <div class="property-summary">
                <h3 id="modalPropertyTitle"></h3>
                <p id="modalPropertyPrice"></p>
            </div>
            
            <div class="form-group">
                <label for="customer_name">Full Name *</label>
                <input type="text" id="customer_name" name="customer_name" value="<?php echo htmlspecialchars($_SESSION['full_name']); ?>" required>
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
                <textarea id="details" name="details" placeholder="Any specific requirements or questions..."></textarea>
            </div>
            
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeRentModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Submit Request</button>
            </div>
        </form>
    </div>
</div>

<!-- Property Details Modal -->
<div id="detailsModal" class="modal">
    <div class="modal-content modal-large">
        <div class="modal-header">
            <h2><i class="fas fa-info-circle"></i> Property Details</h2>
            <span class="close" onclick="closeDetailsModal()">&times;</span>
        </div>
        <div id="propertyDetails">
            <!-- Property details will be loaded here -->
        </div>
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
    grid-template-columns: 2fr 1fr 1fr auto;
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

.btn-search {
    padding: 12px 20px;
    background: #667eea;
    color: white;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 1rem;
}

.btn-search:hover {
    background: #5a6fd8;
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
    background: #f8f9fa;
    display: flex;
    align-items: center;
    justify-content: center;
}

.property-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: opacity 0.3s ease;
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
}
</style>

<script>
function openRentModal(houseId, title, price) {
    document.getElementById('modalHouseId').value = houseId;
    document.getElementById('modalPropertyTitle').textContent = title;
    document.getElementById('modalPropertyPrice').textContent = 'KSH ' + price.toLocaleString() + '/month';
    document.getElementById('rentModal').style.display = 'block';
}

function closeRentModal() {
    document.getElementById('rentModal').style.display = 'none';
}

function viewDetails(houseId) {
    // Show loading message
    document.getElementById('detailsModal').style.display = 'block';
    document.getElementById('propertyDetails').innerHTML = '<div style="padding: 20px; text-align: center;"><i class="fas fa-spinner fa-spin" style="font-size: 24px;"></i><p>Loading property details...</p></div>';
    
    // Fetch property details via AJAX
    fetch('get_property_details.php?house_id=' + houseId)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const house = data.house;
                let imagesHtml = '';
                
                if (house.images) {
                    // Handle both JSON and comma-separated formats
                    let imageList = [];
                    try {
                        // Try to parse as JSON first
                        imageList = JSON.parse(house.images);
                    } catch (e) {
                        // Fallback to comma-separated
                        imageList = house.images.split(',').map(img => img.trim());
                    }
                    
                    if (imageList.length > 0) {
                        imagesHtml = '<div class="property-gallery" style="margin-bottom: 20px;">';
                        imageList.forEach(img => {
                            if (img) {
                                imagesHtml += `<img src="${img}" alt="Property Image" style="max-width: 100%; margin-bottom: 10px; border-radius: 8px;" onerror="this.src='/placeholder.svg?height=300&width=450'" loading="lazy">`;
                            }
                        });
                        imagesHtml += '</div>';
                    }
                }
                
                document.getElementById('propertyDetails').innerHTML = `
                    <div style="padding: 20px;">
                        <h3>${house.title}</h3>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 20px; flex-wrap: wrap; gap: 10px;">
                            <p style="font-size: 1.2rem; color: #28a745; font-weight: bold;">
                                <i class="fas fa-money-bill"></i> KSH ${parseInt(house.price).toLocaleString()}/month
                            </p>
                            <p style="font-size: 1.1rem;">
                                <i class="fas fa-map-marker-alt"></i> ${house.location}
                            </p>
                        </div>
                        
                        ${imagesHtml}
                        
                        <div style="margin-top: 20px;">
                            <h4>Description</h4>
                            <p style="line-height: 1.6;">${house.description || 'No description provided.'}</p>
                        </div>
                        
                        <div style="margin-top: 30px; text-align: center;">
                            <button class="btn btn-primary" onclick="openRentModal(${house.id}, '${house.title.replace(/'/g, "\\'")}', ${house.price})">
                                <i class="fas fa-key"></i> Rent This Property
                            </button>
                        </div>
                    </div>
                `;
            } else {
                document.getElementById('propertyDetails').innerHTML = `
                    <div style="padding: 20px; color: #721c24;">
                        <p>Error loading property details. Please try again.</p>
                    </div>
                `;
            }
        })
        .catch(error => {
            document.getElementById('propertyDetails').innerHTML = `
                <div style="padding: 20px; color: #721c24;">
                    <p>Failed to load property details. Please check your connection and try again.</p>
                </div>
            `;
            console.error('Error:', error);
        });
}

function closeDetailsModal() {
    document.getElementById('detailsModal').style.display = 'none';
}

// Phone number validation
document.getElementById('customer_phone').addEventListener('input', function(e) {
    this.value = this.value.replace(/[^0-9]/g, '');
});

// Close modals when clicking outside
window.onclick = function(event) {
    const rentModal = document.getElementById('rentModal');
    const detailsModal = document.getElementById('detailsModal');
    
    if (event.target === rentModal) {
        closeRentModal();
    }
    if (event.target === detailsModal) {
        closeDetailsModal();
    }
}
</script>