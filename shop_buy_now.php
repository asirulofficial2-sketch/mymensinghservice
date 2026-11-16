<?php
session_start();

$id = intval($_GET['id'] ?? 0);

if (!$id) {
    header("Location: shop.php");
    exit;
}

// কার্ট রিসেট করে শুধু এই প্রোডাক্ট রাখি
$_SESSION['shop_cart'] = [$id => 1];

// সরাসরি চেকআউটে পাঠাই
header("Location: shop_order.php");
exit;
?>