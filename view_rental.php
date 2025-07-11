<?php
require_once 'config.php';

if (!isset($_GET['id'])) {
    header('Location: orders.php');
    exit;
}

$id = intval($_GET['id']);
$stmt = $pdo->prepare("SELECT * FROM rental_houses WHERE id = ?");
$stmt->execute([$id]);
$house = $stmt->fetch();

if (!$house) {
    header('Location: orders.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($house['title']); ?> - Rental Details</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background: linear-gradient(135deg, #ff9a9e 0%, #fecfef 50%, #fecfef 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .page-title {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            color: white;
            padding: 30px;
            margin: 0;
            font-size: 2.5rem;
            text-align: center;
            font-weight: 300;
            letter-spacing: 1px;
        }

        .card {
            padding: 40px;
        }

        .rental-title {
            font-size: 2.2rem;
            color: #2c3e50;
            margin-bottom: 30px;
            text-align: center;
            font-weight: 600;
        }

        .property-details {
            display: grid;
            grid-template-columns: 1fr 1.5fr;
            gap: 40px;
            margin: 30px 0;
        }

        .property-image {
            position: relative;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }

        .property-image img {
            width: 100%;
            height: 350px;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .property-image:hover img {
            transform: scale(1.05);
        }

        .no-image {
            background: linear-gradient(135deg, #f0f0f0 0%, #e0e0e0 100%);
            height: 350px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: #666;
            font-size: 1.2rem;
            border-radius: 15px;
        }

        .no-image i {
            font-size: 3rem;
            margin-bottom: 10px;
            color: #999;
        }

        .property-info {
            padding: 0 20px;
        }

        .info-item {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 10px;
            border-left: 4px solid #e74c3c;
        }

        .info-item i {
            margin-right: 15px;
            color: #e74c3c;
            font-size: 1.2rem;
            width: 20px;
        }

        .info-item strong {
            color: #2c3e50;
            margin-right: 10px;
            min-width: 80px;
        }

        .price {
            font-size: 1.8rem;
            color: #e74c3c;
            font-weight: bold;
        }

        .monthly-label {
            font-size: 1rem;
            color: #7f8c8d;
            font-weight: normal;
            margin-left: 5px;
        }

        .status {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 25px;
            font-size: 0.9rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status.available {
            background: #d4edda;
            color: #155724;
        }

        .status.rented {
            background: #f8d7da;
            color: #721c24;
        }

        .status.pending {
            background: #fff3cd;
            color: #856404;
        }

        .rental-features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }

        .feature-badge {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            color: white;
            padding: 10px 15px;
            border-radius: 25px;
            text-align: center;
            font-size: 0.9rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .feature-badge i {
            margin-right: 8px;
        }

        .description-section {
            margin-top: 40px;
            padding: 30px;
            background: #f8f9fa;
            border-radius: 15px;
            border: 1px solid #e9ecef;
        }

        .description-section h3 {
            color: #2c3e50;
            margin-bottom: 20px;
            font-size: 1.5rem;
            display: flex;
            align-items: center;
        }

        .description-section h3 i {
            margin-right: 10px;
            color: #e74c3c;
        }

        .description-text {
            line-height: 1.8;
            color: #555;
            font-size: 1.1rem;
        }

        .rental-highlights {
            background: linear-gradient(135deg, #fff5f5 0%, #ffe8e8 100%);
            padding: 25px;
            border-radius: 15px;
            margin-top: 30px;
            border: 2px solid #fecaca;
        }

        .rental-highlights h4 {
            color: #e74c3c;
            margin-bottom: 15px;
            font-size: 1.3rem;
            display: flex;
            align-items: center;
        }

        .rental-highlights h4 i {
            margin-right: 10px;
        }

        .highlight-list {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 10px;
        }

        .highlight-item {
            display: flex;
            align-items: center;
            color: #2c3e50;
            font-weight: 500;
        }

        .highlight-item i {
            color: #e74c3c;
            margin-right: 8px;
            width: 16px;
        }

        .action-buttons {
            margin-top: 40px;
            padding-top: 30px;
            border-top: 2px solid #e9ecef;
            text-align: center;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            text-decoration: none;
            padding: 15px 30px;
            border-radius: 50px;
            font-size: 1rem;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            margin: 0 10px;
        }

        .btn i {
            margin-right: 10px;
        }

        .btn-back {
            background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
            color: white;
        }

        .btn-back:hover {
            background: linear-gradient(135deg, #5a6268 0%, #343a40 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.15);
        }

        .btn-contact {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            color: white;
        }

        .btn-contact:hover {
            background: linear-gradient(135deg, #c0392b 0%, #a93226 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(231,76,60,0.3);
        }

        @media (max-width: 768px) {
            body {
                padding: 10px;
            }

            .page-title {
                font-size: 2rem;
                padding: 20px;
            }

            .card {
                padding: 20px;
            }

            .rental-title {
                font-size: 1.8rem;
            }

            .property-details {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .property-image img,
            .no-image {
                height: 250px;
            }

            .property-info {
                padding: 0;
            }

            .info-item {
                flex-direction: column;
                align-items: flex-start;
                text-align: left;
            }

            .info-item i {
                margin-bottom: 5px;
            }

            .description-section {
                padding: 20px;
                margin-top: 20px;
            }

            .rental-highlights {
                padding: 20px;
            }

            .btn {
                padding: 12px 25px;
                font-size: 0.9rem;
                margin: 5px;
            }

            .rental-features {
                grid-template-columns: 1fr;
            }

            .highlight-list {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 480px) {
            .page-title {
                font-size: 1.5rem;
            }

            .rental-title {
                font-size: 1.5rem;
            }

            .price {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="page-title">
            <i class="fas fa-key"></i> Rental House Details
        </h1>
        
        <div class="card">
            <h2 class="rental-title"><?php echo htmlspecialchars($house['title']); ?></h2>
            
            <div class="property-details">
                <div class="property-image">
                    <?php if ($house['images']): ?>
                        <img src="<?php echo htmlspecialchars(json_decode($house['images'])[0]); ?>" 
                             alt="<?php echo htmlspecialchars($house['title']); ?>">
                    <?php else: ?>
                        <div class="no-image">
                            <i class="fas fa-image"></i>
                            No Image Available
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="property-info">
                    <div class="info-item">
                        <i class="fas fa-money-bill-wave"></i>
                        <strong>Rent:</strong>
                        <span class="price">
                            KSh <?php echo number_format($house['price'], 2); ?>
                            <span class="monthly-label">per month</span>
                        </span>
                    </div>
                    
                    <div class="info-item">
                        <i class="fas fa-map-marker-alt"></i>
                        <strong>Location:</strong>
                        <span><?php echo htmlspecialchars($house['location']); ?></span>
                    </div>
                    
                    <div class="info-item">
                        <i class="fas fa-info-circle"></i>
                        <strong>Status:</strong>
                        <span class="status <?php echo strtolower($house['status']); ?>">
                            <?php echo ucfirst($house['status']); ?>
                        </span>
                    </div>

                    <div class="rental-features">
                        <div class="feature-badge">
                            <i class="fas fa-wifi"></i> WiFi Ready
                        </div>
                        <div class="feature-badge">
                            <i class="fas fa-parking"></i> Parking
                        </div>
                        <div class="feature-badge">
                            <i class="fas fa-shield-alt"></i> Secure
                        </div>
                        <div class="feature-badge">
                            <i class="fas fa-tools"></i> Furnished
                        </div>
                    </div>
                </div>
            </div>

            <div class="rental-highlights">
                <h4><i class="fas fa-star"></i> Rental Highlights</h4>
                <div class="highlight-list">
                    <div class="highlight-item">
                        <i class="fas fa-check"></i> Monthly Payment
                    </div>
                    <div class="highlight-item">
                        <i class="fas fa-check"></i> Maintenance Included
                    </div>
                    <div class="highlight-item">
                        <i class="fas fa-check"></i> 24/7 Support
                    </div>
                    <div class="highlight-item">
                        <i class="fas fa-check"></i> Flexible Terms
                    </div>
                    <div class="highlight-item">
                        <i class="fas fa-check"></i> Prime Location
                    </div>
                    <div class="highlight-item">
                        <i class="fas fa-check"></i> Move-in Ready
                    </div>
                </div>
            </div>
            
            <div class="description-section">
                <h3><i class="fas fa-file-alt"></i> Description</h3>
                <div class="description-text">
                    <?php echo nl2br(htmlspecialchars($house['description'])); ?>
                </div>
            </div>
            
            <div class="action-buttons">
                <a href="orders.php" class="btn btn-back">
                    <i class="fas fa-arrow-left"></i> Back to Orders
                </a>
                <a href="#" class="btn btn-contact">
                    <i class="fas fa-phone"></i> Contact Owner
                </a>
            </div>
        </div>
    </div>
</body>
</html>