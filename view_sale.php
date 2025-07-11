<?php
require_once 'config.php';

if (!isset($_GET['id'])) {
    header('Location: orders.php');
    exit;
}

$id = intval($_GET['id']);
$stmt = $pdo->prepare("SELECT * FROM sale_houses WHERE id = ?");
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
    <title><?php echo htmlspecialchars($house['title']); ?> - House Details</title>
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
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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

        .house-title {
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
            align-items: center;
            justify-content: center;
            color: #666;
            font-size: 1.2rem;
            border-radius: 15px;
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
            border-left: 4px solid #667eea;
        }

        .info-item i {
            margin-right: 15px;
            color: #667eea;
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
            color: #27ae60;
            font-weight: bold;
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

        .status.sold {
            background: #f8d7da;
            color: #721c24;
        }

        .status.pending {
            background: #fff3cd;
            color: #856404;
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
            color: #667eea;
        }

        .description-text {
            line-height: 1.8;
            color: #555;
            font-size: 1.1rem;
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

            .house-title {
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

            .btn {
                padding: 12px 25px;
                font-size: 0.9rem;
            }
        }

        @media (max-width: 480px) {
            .page-title {
                font-size: 1.5rem;
            }

            .house-title {
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
            <i class="fas fa-home"></i> House for Sale Details
        </h1>
        
        <div class="card">
            <h2 class="house-title"><?php echo htmlspecialchars($house['title']); ?></h2>
            
            <div class="property-details">
                <div class="property-image">
                    <?php if ($house['images']): ?>
                        <img src="<?php echo htmlspecialchars(json_decode($house['images'])[0]); ?>" 
                             alt="<?php echo htmlspecialchars($house['title']); ?>">
                    <?php else: ?>
                        <div class="no-image">
                            <i class="fas fa-image"></i> No Image Available
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="property-info">
                    <div class="info-item">
                        <i class="fas fa-tag"></i>
                        <strong>Price:</strong>
                        <span class="price">KSh <?php echo number_format($house['price'], 2); ?></span>
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
            </div>
        </div>
    </div>
</body>
</html>