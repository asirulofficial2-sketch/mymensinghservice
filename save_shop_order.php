<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: shop.php");
    exit;
}

$customer_name = trim($_POST['customer_name'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$address = trim($_POST['address'] ?? '');
$note = trim($_POST['note'] ?? '');

if (!$customer_name || !$phone || !$address) {
    die("সব তথ্য পূরণ করুন। <a href='shop_cart.php'>ফিরে যান</a>");
}

$cart = $_SESSION['shop_cart'] ?? [];
if (empty($cart)) {
    header("Location: shop_cart.php");
    exit;
}

$ids = array_keys($cart);
$idList = implode(',', array_map('intval', $ids));
$result = $conn->query("SELECT * FROM products WHERE id IN ($idList) AND active=1");

$items = [];
$total = 0;

while ($p = $result->fetch_assoc()) {
    $qty = $cart[$p['id']] ?? 1;
    $price = $p['discount_price'] ?: $p['price'];
    $subtotal = $price * $qty;
    $total += $subtotal;
    
    $items[] = [
        'id' => $p['id'],
        'name' => $p['name'],
        'price' => $price,
        'qty' => $qty,
        'subtotal' => $subtotal,
        'size' => $p['size'] ?? '',
        'color' => $p['color'] ?? ''
    ];
}

$itemsJson = json_encode($items, JSON_UNESCAPED_UNICODE);

$stmt = $conn->prepare("INSERT INTO shop_orders (customer_name, phone, address, items, total, note, status) VALUES (?, ?, ?, ?, ?, ?, 'pending')");

if (!$stmt) {
    die("Prepare ত্রুটি: " . $conn->error);
}

$stmt->bind_param("ssssds", $customer_name, $phone, $address, $itemsJson, $total, $note);

if ($stmt->execute()) {
    session_start();
    $order_id = $stmt->insert_id;
    $_SESSION['shop_cart'] = [];
    $_SESSION['last_shop_order'] = $order_id;
    $_SESSION['last_shop_order'] = $order_id;
header("Location: payment_methods.php?type=shop&id=" . $order_id);
exit;
    exit;
} else {
    die("সমস্যা: " . $stmt->error);
}
?>