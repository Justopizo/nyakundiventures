<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $phone = $_POST['phone'];
    $location = $_POST['location'];
    
    if (!preg_match('/^\d{10}$/', $phone)) {
        $error = "Phone number must be 10 digits";
    } else {
        if (isset($_POST['garbage_order'])) {
            $garbage_type = $_POST['garbage_type'];
            $stmt = $pdo->prepare("INSERT INTO garbage_orders (user_id, phone, location, garbage_type) VALUES (?, ?, ?, ?)");
            $stmt->execute([$_SESSION['user_id'], $phone, $location, $garbage_type]);
            $success = "Garbage collection order placed successfully.";
        } elseif (isset($_POST['landscaping_order'])) {
            $description = $_POST['description'];
            $stmt = $pdo->prepare("INSERT INTO landscaping_orders (user_id, phone, location, description) VALUES (?, ?, ?, ?)");
            $stmt->execute([$_SESSION['user_id'], $phone, $location, $description]);
            $success = "Landscaping order placed successfully.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation - Nyakundi Ventures</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Arial', sans-serif; }
    </style>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">
    <div class="bg-white p-8 rounded shadow max-w-md w-full">
        <?php if (isset($error)): ?>
            <div class="bg-red-500 text-white p-4 rounded mb-4"><?php echo $error; ?></div>
        <?php endif; ?>
        <?php if (isset($success)): ?>
            <div class="bg-green-500 text-white p-4 rounded mb-4"><?php echo $success; ?></div>
        <?php endif; ?>
        <p class="text-center"><a href="index.php" class="text-blue-800 hover:underline">Back to Dashboard</a></p>
    </div>
</body>
</html>