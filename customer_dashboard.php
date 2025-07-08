<?php
require_once 'config.php';
requireLogin();

// Ensure user is not admin
if (!isLoggedIn()) {
    redirect('index.php');
}

if (isAdmin()) {
    redirect('index.php'); // or log out the user and show warning
}


$current_page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Dashboard - Nyakundi Ventures</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            background-color: #f5f5f5;
        }

        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 250px;
            height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            transform: translateX(-100%);
            transition: transform 0.3s ease;
            z-index: 1000;
        }

        .sidebar.active {
            transform: translateX(0);
        }

        .sidebar-header {
            padding: 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .sidebar-header h2 {
            font-size: 1.2rem;
            margin-bottom: 5px;
        }

        .sidebar-header p {
            font-size: 0.8rem;
            opacity: 0.8;
        }

        .sidebar-menu {
            list-style: none;
            padding: 20px 0;
        }

        .sidebar-menu li {
            margin-bottom: 5px;
        }

        .sidebar-menu a {
            display: block;
            padding: 15px 20px;
            color: white;
            text-decoration: none;
            transition: background-color 0.3s;
        }

        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            background-color: rgba(255,255,255,0.1);
        }

        .sidebar-menu i {
            margin-right: 10px;
            width: 20px;
        }

        .main-content {
            margin-left: 0;
            transition: margin-left 0.3s ease;
        }

        .main-content.shifted {
            margin-left: 250px;
        }

        .header {
            background: white;
            padding: 15px 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .menu-toggle {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #333;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .logout-btn {
            background: #dc3545;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
        }

        .content {
            padding: 20px;
        }

        .page-title {
            margin-bottom: 20px;
            color: #333;
        }

        .overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 999;
            display: none;
        }

        .overlay.active {
            display: block;
        }

        .footer {
            background: #333;
            color: white;
            padding: 40px 20px 20px;
            margin-top: 50px;
        }

        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
        }

        .footer-section h3 {
            margin-bottom: 15px;
            color: #667eea;
        }

        .footer-section p,
        .footer-section a {
            color: #ccc;
            text-decoration: none;
            margin-bottom: 8px;
            display: block;
        }

        .footer-section a:hover {
            color: #667eea;
        }

        .footer-bottom {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #555;
            color: #999;
        }

        @media (min-width: 768px) {
            .sidebar {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 250px;
            }
            
            .menu-toggle {
                display: none;
            }
        }

        @media (max-width: 767px) {
            .user-info span {
                display: none;
            }
            
            .footer-content {
                grid-template-columns: 1fr;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="overlay" id="overlay"></div>
    
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h2><i class="fas fa-home"></i> Nyakundi Ventures</h2>
            <p>Customer Portal</p>
        </div>
        <ul class="sidebar-menu">
            <li><a href="?page=dashboard" class="<?php echo $current_page === 'dashboard' ? 'active' : ''; ?>"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            <li><a href="?page=rental_houses" class="<?php echo $current_page === 'rental_houses' ? 'active' : ''; ?>"><i class="fas fa-home"></i> Houses for Rent</a></li>
            <li><a href="?page=sale_houses" class="<?php echo $current_page === 'sale_houses' ? 'active' : ''; ?>"><i class="fas fa-building"></i> Houses for Sale</a></li>
            <li><a href="?page=sale_lands" class="<?php echo $current_page === 'sale_lands' ? 'active' : ''; ?>"><i class="fas fa-map"></i> Land for Sale</a></li>
            <li><a href="?page=garbage_collection" class="<?php echo $current_page === 'garbage_collection' ? 'active' : ''; ?>"><i class="fas fa-trash"></i> Garbage Collection</a></li>
            <li><a href="?page=landscaping" class="<?php echo $current_page === 'landscaping' ? 'active' : ''; ?>"><i class="fas fa-seedling"></i> Landscaping</a></li>
            <li><a href="?page=my_orders" class="<?php echo $current_page === 'my_orders' ? 'active' : ''; ?>"><i class="fas fa-shopping-cart"></i> My Orders</a></li>
        </ul>
    </div>

    <div class="main-content" id="mainContent">
        <div class="header">
            <button class="menu-toggle" id="menuToggle">
                <i class="fas fa-bars"></i>
            </button>
            <div class="user-info">
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>

        <div class="content">
            <?php
            switch($current_page) {
                case 'dashboard':
                    include 'dashboard.php';
                    break;
                case 'rental_houses':
                    include 'rental_houses.php';
                    break;
                case 'sale_houses':
                    include 'sale_houses.php';
                    break;
                case 'sale_lands':
                    include 'sale_lands.php';
                    break;
                case 'garbage_collection':
                    include 'garbage_collection.php';
                    break;
                case 'landscaping':
                    include 'landscaping.php';
                    break;
                case 'my_orders':
                    include 'my_orders.php';
                    break;
                default:
                    include 'dashboard.php';
            }
            ?>
        </div>

        <footer class="footer">
            <div class="footer-content">
                <div class="footer-section">
                    <h3><i class="fas fa-home"></i> Nyakundi Ventures</h3>
                    <p>"Building Dreams, Creating Homes"</p>
                    <p>Your trusted partner in real estate, waste management, and landscaping services.</p>
                </div>
                <div class="footer-section">
                    <h3><i class="fas fa-phone"></i> Contact Us</h3>
                    <p><i class="fas fa-envelope"></i> jukebrikwa3@gmail.com</p>
                    <p><i class="fas fa-phone"></i> 0742907335</p>
                    <p><i class="fas fa-phone"></i> 0745763322</p>
                </div>
                <div class="footer-section">
                    <h3><i class="fas fa-info-circle"></i> About Us</h3>
                    <p>We specialize in:</p>
                    <p>• House Rentals & Sales</p>
                    <p>• Land Sales</p>
                    <p>• Garbage Collection</p>
                    <p>• Professional Landscaping</p>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2024 Nyakundi Ventures. All rights reserved.</p>
                <p>Developed by Justin Ratemo Software Solutions | Contact: 0793031269</p>
            </div>
        </footer>
    </div>

    <script>
        const menuToggle = document.getElementById('menuToggle');
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.getElementById('mainContent');
        const overlay = document.getElementById('overlay');

        menuToggle.addEventListener('click', function() {
            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');
        });

        overlay.addEventListener('click', function() {
            sidebar.classList.remove('active');
            overlay.classList.remove('active');
        });

        // Close sidebar on window resize if mobile
        window.addEventListener('resize', function() {
            if (window.innerWidth >= 768) {
                sidebar.classList.remove('active');
                overlay.classList.remove('active');
            }
        });
    </script>
</body>
</html>