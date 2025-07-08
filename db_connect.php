<?php
$db_host = 'fdb1033.awardspace.net';
$db_name = '4656815_nyakundiventures';
$db_user = '4656815_nyakundiventures';
$db_pass = '3062@Justin';

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>