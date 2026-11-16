<?php
include 'db.php';
include 'includes/gen_id.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: technician_register.php");
    exit;
}

$name = trim($_POST['name'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$email = trim($_POST['email'] ?? '');
$address = trim($_POST['address'] ?? '');
$service_id = intval($_POST['service_id'] ?? 0);
$experience = trim($_POST['experience'] ?? '');
$nid = trim($_POST['nid'] ?? '');

if (!$name || !$phone || !$address || !$experience) {
    header("Location: technician_register.php?error=missing");
    exit;
}

// ডুপ্লিকেট ফোন চেক
$check = $conn->prepare("SELECT id FROM technicians WHERE phone=?");
$check->bind_param("s", $phone);
$check->execute();
if ($check->get_result()->num_rows > 0) {
    header("Location: technician_register.php?error=duplicate");
    exit;
}

// ইউনিক ID জেনারেট
$type = getPrefix($service_id);
$tech_id = generateTechId($conn, $type);

// সেভ
$stmt = $conn->prepare("INSERT INTO technicians (tech_id, name, phone, email, address, service_id, experience, nid, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
$stmt->bind_param("sssssiss", $tech_id, $name, $phone, $email, $address, $service_id, $experience, $nid);

if ($stmt->execute()) {
    session_start();
    $_SESSION['new_tech_id'] = $tech_id;
    header("Location: technician_success.php");
    exit;
} else {
    die("সমস্যা: " . $conn->error);
}
?>