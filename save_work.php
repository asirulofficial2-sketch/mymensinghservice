<?php
include 'db.php';
include 'includes/gen_id.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: work.php");
    exit;
}

$category_id    = intval($_POST['category_id'] ?? 0);
$subcategory_id = intval($_POST['subcategory_id'] ?? 0);
$name           = trim($_POST['name'] ?? '');
$phone          = trim($_POST['phone'] ?? '');
$email          = trim($_POST['email'] ?? '');
$address        = trim($_POST['address'] ?? '');
$nid            = trim($_POST['nid'] ?? '');
$experience     = trim($_POST['experience'] ?? '');
$education      = trim($_POST['education'] ?? '');
$password       = $_POST['password'] ?? '';
$password2      = $_POST['password2'] ?? '';

if (!$category_id || !$subcategory_id || !$name || !$phone || !$address || !$nid || !$password) {
    header("Location: work.php?error=missing");
    exit;
}
if ($password !== $password2) {
    header("Location: work.php?error=mismatch");
    exit;
}
if (strlen($password) < 6) {
    header("Location: work.php?error=short");
    exit;
}

$check = $conn->prepare("SELECT id FROM technicians WHERE phone=?");
$check->bind_param("s", $phone);
$check->execute();
if ($check->get_result()->num_rows > 0) {
    header("Location: work.php?error=duplicate");
    exit;
}

function uploadImage($fileKey, $folder, $phone) {
    if (!isset($_FILES[$fileKey]) || $_FILES[$fileKey]['error'] !== UPLOAD_ERR_OK) {
        return null;
    }
    $ext = strtolower(pathinfo($_FILES[$fileKey]['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    if (!in_array($ext, $allowed)) return null;
    if ($_FILES[$fileKey]['size'] > 5 * 1024 * 1024) return null;

    $filename = $phone . '_' . $fileKey . '_' . time() . '.' . $ext;
    $dir = __DIR__ . '/uploads/' . $folder . '/';
    if (!is_dir($dir)) mkdir($dir, 0755, true);

    if (move_uploaded_file($_FILES[$fileKey]['tmp_name'], $dir . $filename)) {
        return $filename;
    }
    return null;
}

$photo     = uploadImage('photo', 'photos', $phone);
$nid_front = uploadImage('nid_front', 'nid_front', $phone);
$nid_back  = uploadImage('nid_back', 'nid_back', $phone);

if (!$photo || !$nid_front || !$nid_back) {
    header("Location: work.php?error=photo");
    exit;
}

$prefixMap = [1=>'T', 2=>'TU', 3=>'H', 4=>'N', 5=>'D'];
$prefix = $prefixMap[$category_id] ?? 'W';
$tech_id = generateTechId($conn, $prefix);

$hash = password_hash($password, PASSWORD_DEFAULT);

// ✅ সঠিক টাইপ স্ট্রিং: 10s + 2i + 2s = "ssssssssssiiss"
$stmt = $conn->prepare("INSERT INTO technicians 
    (tech_id, name, phone, email, password, address, nid, nid_front, nid_back, photo, 
     category_id, subcategory_id, experience, education, status) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')");

$stmt->bind_param("ssssssssssiiss", 
    $tech_id, $name, $phone, $email, $hash, $address, 
    $nid, $nid_front, $nid_back, $photo, 
    $category_id, $subcategory_id, $experience, $education
);

if ($stmt->execute()) {
    session_start();
    $_SESSION['new_tech_id'] = $tech_id;
    $_SESSION['new_tech_name'] = $name;
    header("Location: work_success.php");
    exit;
} else {
    die("ডাটাবেস সমস্যা: " . $stmt->error);
}
?>