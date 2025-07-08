<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: index.php");
    exit;
}

// Handle house/land uploads
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['upload_house_rent'])) {
    $price = $_POST['price'];
    $location = $_POST['location'];
    $description = $_POST['description'];
    $image = $_FILES['image']['name'];
    $target = "uploads/" . basename($image);
    
    if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
        $stmt = $pdo->prepare("INSERT INTO houses_rent (price, location, description, image) VALUES (?, ?, ?, ?)");
        $stmt->execute([$price, $location, $description, $image]);
        $success = "House for rent uploaded successfully.";
    } else {
        $error = "Failed to upload image.";
    }
}

// Similar logic for houses_sale, lands_sale (omitted for brevity, follows same pattern)

// Fetch data
$houses_rent = $pdo->query("SELECT * FROM houses_rent")->fetchAll();
$houses_sale = $pdo->query("SELECT * FROM houses_sale")->fetchAll();
$lands_sale = $pdo->query("SELECT * FROM lands_sale")->fetchAll();
$garbage_orders = $pdo->query("SELECT * FROM garbage_orders")->fetchAll();
$landscaping_orders = $pdo->query("SELECT * FROM landscaping_orders")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Nyakundi Ventures</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        body { font-family: 'Arial', sans-serif; }
        .sidebar { transition: transform 0.3s ease; }
        .sidebar-hidden { transform: translateX(-100%); }
        @media (min-width: 768px) { .sidebar-hidden { transform: translateX(0); } }
        .content-section { display: none; }
        .content-section.active { display: block; }
    </style>
</head>
<body class="bg-gray-100">
    <!-- Sidebar -->
    <div class="fixed inset-y-0 left-0 w-64 bg-blue-800 text-white sidebar md:static md:w-64 z-50" id="sidebar">
        <div class="p-4 text-2xl font-bold text-center border-b">Nyakundi Ventures</div>
        <div class="p-4 text-sm text-center">Building Your Future, Greening Your Present</div>
        <nav class="mt-4">
            <a href="#" class="block py-2 px-4 hover:bg-blue-700 sidebar-link" data-target="dashboard"><i class="fas fa-tachometer-alt mr-2"></i> Dashboard</a>
            <a href="#" class="block py-2 px-4 hover:bg-blue-700 sidebar-link" data-target="upload-rent"><i class="fas fa-home mr-2"></i> Upload House (Rent)</a>
            <a href="#" class="block py-2 px-4 hover:bg-blue-700 sidebar-link" data-target="upload-sale"><i class="fas fa-house-user mr-2"></i> Upload House (Sale)</a>
            <a href="#" class="block py-2 px-4 hover:bg-blue-700 sidebar-link" data-target="upload-land"><i class="fas fa-mountain mr-2"></i> Upload Land</a>
            <a href="#" class="block py-2 px-4 hover:bg-blue-700 sidebar-link" data-target="view-orders"><i class="fas fa-list mr-2"></i> View Orders</a>
            <a href="index.php?logout" class="block py-2 px-4 hover:bg-blue-700"><i class="fas fa-sign-out-alt mr-2"></i> Logout</a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="md:ml-64 p-4">
        <button class="md:hidden mb-4 p-2 bg-blue-800 text-white rounded" id="toggleSidebar"><i class="fas fa-bars"></i></button>
        
        <!-- Error/Success Messages -->
        <?php if (isset($error)): ?>
            <div class="bg-red-500 text-white p-4 rounded mb-4"><?php echo $error; ?></div>
        <?php endif; ?>
        <?php if (isset($success)): ?>
            <div class="bg-green-500 text-white p-4 rounded mb-4"><?php echo $success; ?></div>
        <?php endif; ?>

        <!-- Admin Dashboard -->
        <div id="dashboard" class="content-section active">
            <h2 class="text-2xl font-bold mb-4">Admin Dashboard</h2>
            <p>Welcome, Admin! Manage houses, lands, and orders from here.</p>
        </div>

        <!-- Upload House (Rent) -->
        <div id="upload-rent" class="content-section">
            <h2 class="text-2xl font-bold mb-4">Upload House for Rent</h2>
            <form method="POST" enctype="multipart/form-data" class="space-y-4 max-w-md">
                <div>
                    <label for="price" class="block text-sm font-medium">Price (KSH)</label>
                    <input type="number" name="price" id="price" placeholder="Enter price" class="w-full p-2 border rounded" required>
                </div>
                <div>
                    <label for="location" class="block text-sm font-medium">Location</label>
                    <input type="text" name="location" id="location" placeholder="Enter location" class="w-full p-2 border rounded" required>
                </div>
                <div>
                    <label for="description" class="block text-sm font-medium">Description</label>
                    <textarea name="description" id="description" placeholder="Enter description" class="w-full p-2 border rounded" required></textarea>
                </div>
                <div>
                    <label for="image" class="block text-sm font-medium">Image</label>
                    <input type="file" name="image" id="image" class="w-full p-2 border rounded" required>
                </div>
                <button type="submit" name="upload_house_rent" class="w-full p-2 bg-blue-800 text-white rounded hover:bg-blue-700">Upload</button>
            </form>
        </div>

        <!-- View Orders -->
        <div id="view-orders" class="content-section">
            <h2 class="text-2xl font-bold mb-4">Customer Orders</h2>
            <h3 class="text-xl font-bold mt-4">Garbage Collection Orders</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <?php foreach ($garbage_orders as $order): ?>
                    <div class="bg-white p-4 rounded shadow">
                        <p><strong>Phone:</strong> <?php echo htmlspecialchars($order['phone']); ?></p>
                        <p><strong>Location:</strong> <?php echo htmlspecialchars($order['location']); ?></p>
                        <p><strong>Garbage Type:</strong> <?php echo htmlspecialchars($order['garbage_type']); ?></p>
                        <p><strong>Status:</strong> <?php echo htmlspecialchars($order['status']); ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
            <h3 class="text-xl font-bold mt-4">Landscaping Orders</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <?php foreach ($landscaping_orders as $order): ?>
                    <div class="bg-white p-4 rounded shadow">
                        <p><strong>Phone:</strong> <?php echo htmlspecialchars($order['phone']); ?></p>
                        <p><strong>Location:</strong> <?php echo htmlspecialchars($order['location']); ?></p>
                        <p><strong>Description:</strong> <?php echo htmlspecialchars($order['description']); ?></p>
                        <p><strong>Status:</strong> <?php echo htmlspecialchars($order['status']); ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <script>
        // Sidebar toggle for mobile
        const toggleSidebar = document.getElementById('toggleSidebar');
        const sidebar = document.getElementById('sidebar');
        toggleSidebar.addEventListener('click', () => {
            sidebar.classList.toggle('sidebar-hidden');
        });

        // Sidebar navigation
        const sidebarLinks = document.querySelectorAll('.sidebar-link');
        const contentSections = document.querySelectorAll('.content-section');
        sidebarLinks.forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const target = link.getAttribute('data-target');
                contentSections.forEach(section => {
                    section.classList.remove('active');
                    if (section.id === target) {
                        section.classList.add('active');
                    }
                });
                if (window.innerWidth < 768) {
                    sidebar.classList.add('sidebar-hidden');
                }
            });
        });
    </script>
</body>
</html>