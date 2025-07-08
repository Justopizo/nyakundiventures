<?php
$message = '';
$error = '';

// Handle landscaping request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'request_landscaping') {
    $customer_name = trim($_POST['customer_name']);
    $customer_phone = trim($_POST['customer_phone']);
    $customer_location = trim($_POST['customer_location']);
    $service_type = trim($_POST['service_type']);
    $property_size = trim($_POST['property_size']);
    $budget_range = trim($_POST['budget_range']);
    $preferred_date = trim($_POST['preferred_date']);
    $details = trim($_POST['details']);
    
    if (empty($customer_name) || empty($customer_phone) || empty($customer_location) || empty($service_type)) {
        $error = 'Please fill in all required fields';
    } elseif (strlen($customer_phone) !== 10 || !ctype_digit($customer_phone)) {
        $error = 'Phone number must be exactly 10 digits';
    } else {
        $order_details = "Service Type: $service_type\n";
        if (!empty($property_size)) {
            $order_details .= "Property Size: $property_size\n";
        }
        if (!empty($budget_range)) {
            $order_details .= "Budget Range: $budget_range\n";
        }
        if (!empty($preferred_date)) {
            $order_details .= "Preferred Start Date: $preferred_date\n";
        }
        if (!empty($details)) {
            $order_details .= "Additional Details: $details";
        }
        
        $stmt = $pdo->prepare("INSERT INTO orders (user_id, order_type, customer_name, customer_phone, customer_location, details) VALUES (?, 'landscaping', ?, ?, ?, ?)");
        if ($stmt->execute([$_SESSION['user_id'], $customer_name, $customer_phone, $customer_location, $order_details])) {
            $message = 'Landscaping service request submitted successfully! Our team will contact you for consultation.';
        } else {
            $error = 'Failed to submit landscaping request. Please try again.';
        }
    }
}
?>

<h1 class="page-title"><i class="fas fa-seedling"></i> Professional Landscaping Services</h1>

<?php if ($message): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<div class="service-intro">
    <div class="intro-content">
        <h2><i class="fas fa-leaf"></i> Transform Your Outdoor Space</h2>
        <p>Create beautiful, sustainable landscapes that enhance your property's value and provide a peaceful environment. Our expert team designs and maintains gardens that reflect your vision and lifestyle.</p>
    </div>
</div>

<div class="service-gallery">
    <h2><i class="fas fa-images"></i> Our Work</h2>
    <div class="gallery-grid">
        <div class="gallery-item">
            <img src="/placeholder.svg?height=200&width=300" alt="Garden Design">
            <div class="gallery-overlay">
                <h3>Garden Design</h3>
            </div>
        </div>
        <div class="gallery-item">
            <img src="/placeholder.svg?height=200&width=300" alt="Lawn Installation">
            <div class="gallery-overlay">
                <h3>Lawn Installation</h3>
            </div>
        </div>
        <div class="gallery-item">
            <img src="/placeholder.svg?height=200&width=300" alt="Tree Planting">
            <div class="gallery-overlay">
                <h3>Tree Planting</h3>
            </div>
        </div>
        <div class="gallery-item">
            <img src="/placeholder.svg?height=200&width=300" alt="Irrigation Systems">
            <div class="gallery-overlay">
                <h3>Irrigation Systems</h3>
            </div>
        </div>
    </div>
</div>

<div class="service-features">
    <h2><i class="fas fa-tools"></i> Our Landscaping Services</h2>
    <div class="features-grid">
        <div class="feature-card">
            <i class="fas fa-pencil-ruler"></i>
            <h3>Landscape Design</h3>
            <p>Custom landscape design tailored to your space, preferences, and budget</p>
        </div>
        <div class="feature-card">
            <i class="fas fa-seedling"></i>
            <h3>Garden Installation</h3>
            <p>Professional planting of flowers, shrubs, and trees with proper soil preparation</p>
        </div>
        <div class="feature-card">
            <i class="fas fa-tint"></i>
            <h3>Irrigation Systems</h3>
            <p>Efficient watering systems to keep your landscape healthy and thriving</p>
        </div>
        <div class="feature-card">
            <i class="fas fa-cut"></i>
            <h3>Lawn Care</h3>
            <p>Regular mowing, fertilizing, and maintenance to keep your lawn pristine</p>
        </div>
        <div class="feature-card">
            <i class="fas fa-tree"></i>
            <h3>Tree Services</h3>
            <p>Tree planting, pruning, and removal by certified arborists</p>
        </div>
        <div class="feature-card">
            <i class="fas fa-hammer"></i>
            <h3>Hardscaping</h3>
            <p>Patios, walkways, retaining walls, and other structural elements</p>
        </div>
    </div>
</div>

<div class="request-form-container">
    <h2><i class="fas fa-calendar-plus"></i> Request Landscaping Service</h2>
    <form method="POST" class="landscaping-form">
        <input type="hidden" name="action" value="request_landscaping">
        
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
            <label for="customer_location">Property Address *</label>
            <input type="text" id="customer_location" name="customer_location" placeholder="Full address where landscaping service is needed" required>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label for="service_type">Service Type *</label>
                <select id="service_type" name="service_type" required>
                    <option value="">Select service type</option>
                    <option value="landscape_design">Landscape Design</option>
                    <option value="garden_installation">Garden Installation</option>
                    <option value="lawn_installation">Lawn Installation</option>
                    <option value="irrigation_system">Irrigation System</option>
                    <option value="tree_planting">Tree Planting</option>
                    <option value="hardscaping">Hardscaping</option>
                    <option value="maintenance">Regular Maintenance</option>
                    <option value="renovation">Landscape Renovation</option>
                    <option value="consultation">Design Consultation</option>
                    <option value="other">Other (specify in details)</option>
                </select>
            </div>
            <div class="form-group">
                <label for="property_size">Property Size</label>
                <select id="property_size" name="property_size">
                    <option value="">Select property size</option>
                    <option value="small">Small (Under 1/4 acre)</option>
                    <option value="medium">Medium (1/4 - 1/2 acre)</option>
                    <option value="large">Large (1/2 - 1 acre)</option>
                    <option value="very_large">Very Large (Over 1 acre)</option>
                </select>
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label for="budget_range">Budget Range (KSH)</label>
                <select id="budget_range" name="budget_range">
                    <option value="">Select budget range</option>
                    <option value="under_50k">Under 50,000</option>
                    <option value="50k_100k">50,000 - 100,000</option>
                    <option value="100k_250k">100,000 - 250,000</option>
                    <option value="250k_500k">250,000 - 500,000</option>
                    <option value="500k_1m">500,000 - 1,000,000</option>
                    <option value="over_1m">Over 1,000,000</option>
                </select>
            </div>
            <div class="form-group">
                <label for="preferred_date">Preferred Start Date</label>
                <input type="date" id="preferred_date" name="preferred_date" min="<?php echo date('Y-m-d'); ?>">
            </div>
        </div>
        
        <div class="form-group">
            <label for="details">Project Details</label>
            <textarea id="details" name="details" placeholder="Describe your vision, specific requirements, preferred plants, style preferences, etc."></textarea>
        </div>
        
        <div class="service-process">
            <h3><i class="fas fa-list-ol"></i> Our Process</h3>
            <div class="process-steps">
                <div class="step">
                    <div class="step-number">1</div>
                    <div class="step-content">
                        <h4>Consultation</h4>
                        <p>Free on-site consultation to understand your needs and vision</p>
                    </div>
                </div>
                <div class="step">
                    <div class="step-number">2</div>
                    <div class="step-content">
                        <h4>Design</h4>
                        <p>Custom landscape design with detailed plans and plant selections</p>
                    </div>
                </div>
                <div class="step">
                    <div class="step-number">3</div>
                    <div class="step-content">
                        <h4>Installation</h4>
                        <p>Professional installation by our experienced landscaping team</p>
                    </div>
                </div>
                <div class="step">
                    <div class="step-number">4</div>
                    <div class="step-content">
                        <h4>Maintenance</h4>
                        <p>Ongoing care and maintenance to keep your landscape beautiful</p>
                    </div>
                </div>
            </div>
        </div>
        
        <button type="submit" class="btn btn-primary btn-large">
            <i class="fas fa-paper-plane"></i> Request Landscaping Service
        </button>
    </form>
</div>

<div class="contact-info">
    <h2><i class="fas fa-phone"></i> Ready to Transform Your Space?</h2>
    <div class="contact-grid">
        <div class="contact-item">
            <i class="fas fa-phone"></i>
            <div>
                <h3>Call for Consultation</h3>
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
            <i class="fas fa-calendar"></i>
            <div>
                <h3>Free Consultation</h3>
                <p>Schedule your free on-site consultation today</p>
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

.service-gallery {
    margin-bottom: 40px;
}

.service-gallery h2 {
    margin-bottom: 30px;
    color: #333;
    text-align: center;
}

.gallery-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
}

.gallery-item {
    position: relative;
    border-radius: 10px;
    overflow: hidden;
    height: 200px;
}

.gallery-item img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.gallery-overlay {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    background: linear-gradient(transparent, rgba(0,0,0,0.8));
    color: white;
    padding: 20px;
    text-align: center;
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

.service-process {
    background: #f8f9fa;
    padding: 25px;
    border-radius: 10px;
    margin: 30px 0;
}

.service-process h3 {
    margin-bottom: 25px;
    color: #333;
    text-align: center;
}

.process-steps {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
}

.step {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 15px;
    background: white;
    border-radius: 8px;
}

.step-number {
    width: 40px;
    height: 40px;
    background: #28a745;
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    flex-shrink: 0;
}

.step-content h4 {
    margin-bottom: 5px;
    color: #333;
}

.step-content p {
    color: #666;
    font-size: 0.9rem;
    margin: 0;
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
    
    .gallery-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .process-steps {
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

@media (max-width: 480px) {
    .gallery-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
// Phone number validation
document.getElementById('customer_phone').addEventListener('input', function(e) {
    this.value = this.value.replace(/[^0-9]/g, '');
});

// Set minimum date to today
document.getElementById('preferred_date').min = new Date().toISOString().split('T')[0];
</script>