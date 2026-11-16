<?php
session_start(); // সেশন শুরু করা আবশ্যক
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}

// ডাটা নিই
$service_id = intval($_POST['service_id'] ?? 0);
$customer_name = trim($_POST['customer_name'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$address = trim($_POST['address'] ?? '');
$problem = trim($_POST['problem'] ?? '');
$preferred_time = trim($_POST['preferred_time'] ?? 'যেকোনো সময়');

// ভ্যালিডেশন
if (!$service_id || !$customer_name || !$phone || !$address) {
    die("সব তথ্য পূরণ করুন। <a href='book.php'>ফিরে যান</a>");
}

// সার্ভিসের মূল্য বের করি
$stmt = $conn->prepare("SELECT min_price FROM services WHERE id=?");
$stmt->bind_param("i", $service_id);
$stmt->execute();
$svc = $stmt->get_result()->fetch_assoc();
$total = $svc['min_price'] ?? 0;

// বুকিং সেভ করি
$stmt = $conn->prepare("INSERT INTO bookings (customer_name, phone, address, service_id, problem, preferred_time, total) VALUES (?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("sssisss", $customer_name, $phone, $address, $service_id, $problem, $preferred_time, $total);

if ($stmt->execute()) {
    $booking_id = $stmt->insert_id; // বুকিং সেভ হওয়ার পর ID নেওয়া
    $_SESSION['last_booking'] = $booking_id;
    
    // সফলভাবে সেভ হলে পেমেন্ট পেজে নিয়ে যাবে
    header("Location: payment_methods.php?type=service&id=" . $booking_id);
    exit;
} else {
    die("সমস্যা হয়েছে: " . $conn->error);
}
?>