<?php
$message = '';
$error = '';

// Handle garbage collection request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'request_collection') {
    $customer_name = trim($_POST['customer_name']);
    $customer_phone = trim($_POST['customer_phone']);
    $customer_location = trim($_POST['customer_location']);
    $garbage_type = trim($_POST['garbage_type']);
    $collection_date = trim($_POST['collection_date']);
    $details = trim($_POST['details']);
    
    if (empty($customer_name) || empty($customer_phone) || empty($customer_location) || empty($garbage_type)) {
        $error = 'Please fill in all required fields';
    } elseif (strlen($customer_phone) !== 10 || !ctype_digit($customer_phone)) {
        $error = 'Phone number must be exactly 10 digits';
    } else {
        $order_details = "Garbage Type: $garbage_type\n";
        if (!empty($collection_date)) {
            $order_details .= "Preferred Collection Date: $collection_date\n";
        }
        if (!empty($details)) {
            $order_details .= "Additional Details: $details";
        }
        
        $stmt = $pdo->prepare("INSERT INTO orders (user_id, order_type, customer_name, customer_phone, customer_location, details) VALUES (?, 'garbage_collection', ?, ?, ?, ?)");
        if ($stmt->execute([$_SESSION['user_id'], $customer_name, $customer_phone, $customer_location, $order_details])) {
            $message = 'Garbage collection request submitted successfully! We will contact you to schedule pickup.';
        } else {
            $error = 'Failed to submit garbage collection request. Please try again.';
        }
    }
}
?>

<h1 class="page-title"><i class="fas fa-trash"></i> Garbage Collection Service</h1>

<?php if ($message): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<div class="service-intro">
    <div class="intro-content">
        <h2><i class="fas fa-recycle"></i> Professional Waste Management</h2>
        <p>We provide reliable and eco-friendly garbage collection services for homes, offices, and commercial establishments. Our team ensures proper waste disposal and recycling.</p>
    </div>
</div>

<div class="service-features">
    <h2><i class="fas fa-star"></i> Our Services</h2>
    <div class="features-grid">
        <div class="feature-card">
            <i class="fas fa-home"></i>
            <h3>Residential Collection</h3>
            <p>Regular household waste pickup with flexible scheduling</p>
        </div>
        <div class="feature-card">
            <i class="fas fa-building"></i>
            <h3>Commercial Waste</h3>
            <p>Office and business waste management solutions</p>
        </div>
        <div class="feature-card">
            <i class="fas fa-recycle"></i>
            <h3>Recycling Services</h3>
            <p>Proper sorting and recycling of recyclable materials</p>
        </div>
        <div class="feature-card">
            <i class="fas fa-leaf"></i>
            <h3>Garden Waste</h3>
            <p>Collection and composting of organic garden waste</p>
        </div>
        <div class="feature-card">
            <i class="fas fa-tools"></i>
            <h3>Construction Debris</h3>
            <p>Safe disposal of construction and renovation waste</p>
        </div>
        <div class="feature-card">
            <i class="fas fa-clock"></i>
            <h3>Flexible Timing</h3>
            <p>Convenient pickup times that work with your schedule</p>
        </div>
    </div>
</div>

<div class="request-form-container">
    <h2><i class="fas fa-calendar-plus"></i> Request Garbage Collection</h2>
    <form method="POST" class="collection-form">
        <input type="hidden" name="action" value="request_collection">
        
        <div class="form-row">
            <div class="form-group">
                <label for="customer_name">Full Name *</label>
                <input type="text" id="customer_name" name="customer_name" value="<?php echo htmlspecialchars($_SESSION['full_name']); ?>" required>
            </div>
            <div class="form-group">
                <label for="customer_phone">Phone Number *</label>
                <input type="tel" id="customer_phone" name="customer_phone" placeholder="10-digit phone number" maxlength="10" required>
            </div>
        </div>
        
        <div class="form-group">
            <label for="customer_location">Collection Address *</label>
            <input type="text" id="customer_location" name="customer_location" placeholder="Full address where garbage should be collected" required>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label for="garbage_type">Type of Garbage *</label>
                <select id="garbage_type" name="garbage_type" required>
                    <option value="">Select garbage type</option>
                    <option value="household">Household Waste</option>
                    <option value="office">Office Waste</option>
                    <option value="garden">Garden/Organic Waste</option>
                    <option value="construction">Construction Debris</option>
                    <option value="recyclable">Recyclable Materials</option>
                    <option value="electronic">Electronic Waste</option>
                    <option value="mixed">Mixed Waste</option>
                    <option value="other">Other (specify in details)</option>
                </select>
            </div>
            <div class="form-group">
                <label for="collection_date">Preferred Collection Date</label>
                <input type="date" id="collection_date" name="collection_date" min="<?php echo date('Y-m-d'); ?>">
            </div>
        </div>
        
        <div class="form-group">
            <label for="details">Additional Details</label>
            <textarea id="details" name="details" placeholder="Describe the amount of garbage, any special requirements, access instructions, etc."></textarea>
        </div>
        
        <div class="pricing-info">
            <h3><i class="fas fa-money-bill"></i> Pricing Information</h3>
            <div class="pricing-grid">
                <div class="price-item">
                    <span class="service">Household Waste</span>
                    <span class="price">KSH 500 - 1,500</span>
                </div>
                <div class="price-item">
                    <span class="service">Office Waste</span>
                    <span class="price">KSH 800 - 2,500</span>
                </div>
                <div class="price-item">
                    <span class="service">Garden Waste</span>
                    <span class="price">KSH 600 - 2,000</span>
                </div>
                <div class="price-item">
                    <span class="service">Construction Debris</span>
                    <span class="price">KSH 2,000 - 10,000</span>
                </div>
            </div>
            <p class="pricing-note">*Prices vary based on quantity and location. Final quote will be provided after assessment.</p>
        </div>
        
        <button type="submit" class="btn btn-primary btn-large">
            <i class="fas fa-paper-plane"></i> Submit Collection Request
        </button>
    </form>
</div>

<div class="contact-info">
    <h2><i class="fas fa-phone"></i> Need Immediate Service?</h2>
    <div class="contact-grid">
        <div class="contact-item">
            <i class="fas fa-phone"></i>
            <div>
                <h3>Call Us</h3>
                <p>0742907335 / 0745763322</p>
            </div>
        </div>
        <div class="contact-item">
            <i class="fas fa-envelope"></i>
            <div>
                <h3>Email Us</h3>
                <p>jukebrikwa3@gmail.com</p>
            </div>
        </div>
        <div class="contact-item">
            <i class="fas fa-clock"></i>
            <div>
                <h3>Service Hours</h3>
                <p>Monday - Saturday: 7:00 AM - 6:00 PM</p>
            </div>
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

.service-intro {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    color: white;
    padding: 40px;
    border-radius: 15px;
    margin-bottom: 30px;
    text-align: center;
}

.intro-content h2 {
    margin-bottom: 15px;
    font-size: 2rem;
}

.intro-content p {
    font-size: 1.1rem;
    opacity: 0.9;
    max-width: 800px;
    margin: 0 auto;
}

.service-features {
    margin-bottom: 40px;
}

.service-features h2 {
    margin-bottom: 30px;
    color: #333;
    text-align: center;
}

.features-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 25px;
}

.feature-card {
    background: white;
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    text-align: center;
    transition: transform 0.2s;
}

.feature-card:hover {
    transform: translateY(-5px);
}

.feature-card i {
    font-size: 3rem;
    color: #28a745;
    margin-bottom: 20px;
}

.feature-card h3 {
    margin-bottom: 15px;
    color: #333;
}

.feature-card p {
    color: #666;
    line-height: 1.6;
}

.request-form-container {
    background: white;
    padding: 30px;
    border-radius: 15px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    margin-bottom: 40px;
}

.request-form-container h2 {
    margin-bottom: 30px;
    color: #333;
    text-align: center;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.form-group {
    margin-bottom: 25px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
    color: #333;
}

.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 12px;
    border: 1px solid #ddd;
    border-radius: 8px;
    font-size: 1rem;
    transition: border-color 0.3s;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    outline: none;
    border-color: #28a745;
}

.form-group textarea {
    height: 100px;
    resize: vertical;
}

.pricing-info {
    background: #f8f9fa;
    padding: 25px;
    border-radius: 10px;
    margin: 30px 0;
}

.pricing-info h3 {
    margin-bottom: 20px;
    color: #333;
    text-align: center;
}

.pricing-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-bottom: 15px;
}

.price-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 15px;
    background: white;
    border-radius: 6px;
    border-left: 4px solid #28a745;
}

.service {
    font-weight: 500;
    color: #333;
}

.price {
    font-weight: 600;
    color: #28a745;
}

.pricing-note {
    text-align: center;
    color: #666;
    font-style: italic;
    font-size: 0.9rem;
}

.btn-large {
    width: 100%;
    padding: 15px;
    font-size: 1.1rem;
    background: #28a745;
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    transition: background-color 0.3s;
}

.btn-large:hover {
    background: #218838;
}

.contact-info {
    background: white;
    padding: 30px;
    border-radius: 15px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.contact-info h2 {
    margin-bottom: 30px;
    color: #333;
    text-align: center;
}

.contact-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 30px;
}

.contact-item {
    display: flex;
    align-items: center;
    gap: 20px;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 10px;
}

.contact-item i {
    font-size: 2rem;
    color: #28a745;
}

.contact-item h3 {
    margin-bottom: 5px;
    color: #333;
}

.contact-item p {
    color: #666;
    margin: 0;
}

@media (max-width: 768px) {
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .features-grid {
        grid-template-columns: 1fr;
    }
    
    .pricing-grid {
        grid-template-columns: 1fr;
    }
    
    .contact-grid {
        grid-template-columns: 1fr;
    }
    
    .service-intro {
        padding: 30px 20px;
    }
    
    .intro-content h2 {
        font-size: 1.5rem;
    }
}
</style>

<script>
// Phone number validation
document.getElementById('customer_phone').addEventListener('input', function(e) {
    this.value = this.value.replace(/[^0-9]/g, '');
});

// Set minimum date to today
document.getElementById('collection_date').min = new Date().toISOString().split('T')[0];
</script>