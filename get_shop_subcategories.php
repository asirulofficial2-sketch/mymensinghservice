<?php
include '../db.php';

header('Content-Type: application/json; charset=utf-8');

$category_id = intval($_GET['category_id'] ?? 0);

if (!$category_id) {
    echo json_encode([]);
    exit;
}

$stmt = $conn->prepare("SELECT id, name FROM shop_subcategories 
                        WHERE category_id = ? AND active = 1 
                        ORDER BY sort_order, id");
$stmt->bind_param("i", $category_id);
$stmt->execute();
$result = $stmt->get_result();

$subs = [];
while ($row = $result->fetch_assoc()) {
    $subs[] = [
        'id' => $row['id'],
        'name' => $row['name']
    ];
}

echo json_encode($subs, JSON_UNESCAPED_UNICODE);
?>