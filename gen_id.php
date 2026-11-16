<?php
/**
 * টেকনিশিয়ান/টিউটর/ড্রাইভার ইউনিক ID জেনারেট
 * ফরম্যাট: MHS-T-2026-0001, MHS-TU-2026-0001, MHS-D-2026-0001
 */
function generateTechId($conn, $type = 'T') {
    $year = date('Y');
    $prefix = "MHS-{$type}-{$year}-";

    $stmt = $conn->prepare("SELECT tech_id FROM technicians WHERE tech_id LIKE ? ORDER BY id DESC LIMIT 1");
    $like = $prefix . '%';
    $stmt->bind_param("s", $like);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $last = $result->fetch_assoc()['tech_id'];
        $lastNum = intval(substr($last, -4));
        $newNum = $lastNum + 1;
    } else {
        $newNum = 1;
    }

    return $prefix . str_pad($newNum, 4, '0', STR_PAD_LEFT);
}
?>