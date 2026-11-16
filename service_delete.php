<?php
include 'includes/auth.php';
include '../db.php';

$id = intval($_GET['id'] ?? 0);

if ($id) {
    // ছবিও ডিলিট করি
    $row = $conn->query("SELECT image FROM services WHERE id=$id")->fetch_assoc();
    if ($row && $row['image']) {
        $path = __DIR__ . '/../uploads/services/' . $row['image'];
        if (file_exists($path)) unlink($path);
    }
    
    $stmt = $conn->prepare("DELETE FROM services WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
}

header("Location: services.php");
exit;
?>