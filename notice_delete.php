<?php
include 'includes/auth.php';
include '../db.php';

$id = intval($_GET['id'] ?? 0);

if ($id) {
    $stmt = $conn->prepare("DELETE FROM notices WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
}

header("Location: notices.php");
exit;
?>