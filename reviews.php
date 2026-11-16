<?php
include 'includes/auth.php';
include '../db.php';

$tab = $_GET['tab'] ?? 'service';

if (isset($_GET['delete']) && isset($_GET['type'])) {
    $id = intval($_GET['delete']);
    $table = $_GET['type'] === 'service' ? 'service_reviews' : 'product_reviews';
    $conn->query("DELETE FROM $table WHERE id=$id");
    header("Location: reviews.php?tab=$tab");
    exit;
}

$serviceReviews = $conn->query("SELECT r.*, s.name as service_name 
                                FROM service_reviews r 
                                LEFT JOIN bookings b ON r.booking_id = b.id 
                                LEFT JOIN services s ON b.service_id = s.id 
                                ORDER BY r.id DESC");

$productReviews = $conn->query("SELECT r.*, p.name as product_name 
                                FROM product_reviews r 
                                LEFT JOIN products p ON r.product_id = p.id 
                                ORDER BY r.id DESC");

$avgService = $conn->query("SELECT AVG(rating) as avg_r, COUNT(*) as total FROM service_reviews")->fetch_assoc();
$avgProduct = $conn->query("SELECT AVG(rating) as avg_r, COUNT(*) as total FROM product_reviews")->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="bn">
<head>
<meta charset="UTF-8">
<title>রিভিউ ম্যানেজমেন্ট</title>
<style>
* { margin:0; padding:0; box-sizing:border-box; font-family:'Segoe UI',sans-serif; }
body { background: #f5f7fa; }
.topbar {
  background: #1e3a8a; color: white; padding: 15px 25px;
  display: flex; justify-content: space-between; align-items: center;
  flex-wrap: wrap; gap: 10px;
}
.topbar h1 { font-size: 18px; }
.topbar a {
  color: white; text-decoration: none; background: rgba(255,255,255,0.2);
  padding: 8px 16px; border-radius: 20px; font-size: 14px;
  margin-left: 8px;
}
.container { max-width: 1100px; margin: 30px auto; padding: 0 20px 50px; }
h2.page-title { color: #1e3a8a; font-size: 22px; margin-bottom: 20px; }
.stats-row {
  display: grid; grid-template-columns: repeat(auto-fit, minmax(200px,1fr));
  gap: 15px; margin-bottom: 25px;
}
.stat {
  background: white; padding: 20px; border-radius: 15px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.06);
  border-left: 5px solid #fbbf24;
}
.stat h3 { color: #64748b; font-size: 13px; margin-bottom: 8px; }
.stat .num { color: #1e3a8a; font-size: 28px; font-weight: bold; }
.tabs { display: flex; gap: 10px; margin-bottom: 20px; }
.tab {
  padding: 12px 24px; background: white; border-radius: 10px;
  text-decoration: none; color: #64748b; font-weight: 600;
  font-size: 14px; border: 2px solid #e2e8f0;
}
.tab.active {
  background: linear-gradient(135deg, #1e3a8a, #3b82f6);
  color: white; border-color: #1e3a8a;
}
.review-card {
  background: white; border-radius: 15px; padding: 20px;
  margin-bottom: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.06);
  border-left: 5px solid #fbbf24;
}
.review-head {
  display: flex; justify-content: space-between;
  align-items: center; flex-wrap: wrap; gap: 10px;
  margin-bottom: 10px;
}
.review-head h3 { color: #1e3a8a; font-size: 16px; }
.review-head .rating { color: #fbbf24; font-size: 20px; font-weight: 700; }
.review-body { color: #475569; font-size: 14px; line-height: 1.7; margin-bottom: 12px; }
.review-meta {
  font-size: 12px; color: #94a3b8;
  display: flex; gap: 15px; flex-wrap: wrap;
  padding-top: 10px; border-top: 1px solid #f1f5f9;
}
.btn-del {
  background: #fee2e2; color: #dc2626;
  padding: 6px 14px; border-radius: 8px;
  font-size: 12px; font-weight: 700;
  text-decoration: none; display: inline-block;
}
.empty {
  background: white; padding: 60px 20px; text-align: center;
  border-radius: 15px; color: #64748b;
}
</style>
</head>
<body>

<div class="topbar">
  <h1>🛠️ অ্যাডমিন প্যানেল</h1>
  <div>
    <a href="dashboard.php">ড্যাশবোর্ড</a>
    <a href="logout.php">লগআউট</a>
  </div>
</div>

<div class="container">
  <h2 class="page-title">⭐ রিভিউ ম্যানেজমেন্ট</h2>

  <div class="stats-row">
    <div class="stat">
      <h3>সার্ভিস রিভিউ</h3>
      <div class="num"><?= $avgService['total'] ?? 0 ?></div>
    </div>
    <div class="stat" style="border-color:#8b5cf6">
      <h3>প্রোডাক্ট রিভিউ</h3>
      <div class="num"><?= $avgProduct['total'] ?? 0 ?></div>
    </div>
  </div>

  <div class="tabs">
    <a href="?tab=service" class="tab <?= $tab === 'service' ? 'active' : '' ?>">🔧 সার্ভিস রিভিউ</a>
    <a href="?tab=product" class="tab <?= $tab === 'product' ? 'active' : '' ?>">🛒 প্রোডাক্ট রিভিউ</a>
  </div>

  <?php if ($tab === 'service'): ?>
    <?php if ($serviceReviews && $serviceReviews->num_rows > 0): ?>
      <?php while ($r = $serviceReviews->fetch_assoc()): ?>
        <div class="review-card">
          <div class="review-head">
            <h3>👤 <?= htmlspecialchars($r['customer_name'] ?: 'অজানা') ?></h3>
            <span class="rating"><?= str_repeat('★', $r['rating']) ?><?= str_repeat('☆', 5 - $r['rating']) ?></span>
          </div>
          <div class="review-body">
            <b>সার্ভিস:</b> <?= htmlspecialchars($r['service_name'] ?? 'N/A') ?><br>
            <?php if ($r['review']): ?><b>মন্তব্য:</b> <?= nl2br(htmlspecialchars($r['review'])) ?><?php endif; ?>
          </div>
          <div class="review-meta">
            <span>📞 <?= htmlspecialchars($r['phone'] ?: '—') ?></span>
            <span>🕐 <?= date('d M Y, h:i A', strtotime($r['created_at'])) ?></span>
          </div>
          <div style="margin-top:10px;">
            <a href="?delete=<?= $r['id'] ?>&type=service&tab=service" class="btn-del"
               onclick="return confirm('ডিলিট করবেন?')">🗑 ডিলিট</a>
          </div>
        </div>
      <?php endwhile; ?>
    <?php else: ?>
      <div class="empty"><h3>📭 এখনো কোনো সার্ভিস রিভিউ নেই</h3></div>
    <?php endif; ?>

  <?php else: ?>
    <?php if ($productReviews && $productReviews->num_rows > 0): ?>
      <?php while ($r = $productReviews->fetch_assoc()): ?>
        <div class="review-card" style="border-left-color:#8b5cf6">
          <div class="review-head">
            <h3>👤 <?= htmlspecialchars($r['customer_name'] ?: 'অজানা') ?></h3>
            <span class="rating"><?= str_repeat('★', $r['rating']) ?><?= str_repeat('☆', 5 - $r['rating']) ?></span>
          </div>
          <div class="review-body">
            <b>প্রোডাক্ট:</b> <?= htmlspecialchars($r['product_name'] ?? 'N/A') ?><br>
            <?php if ($r['review']): ?><b>মন্তব্য:</b> <?= nl2br(htmlspecialchars($r['review'])) ?><?php endif; ?>
          </div>
          <div class="review-meta">
            <span>📞 <?= htmlspecialchars($r['phone'] ?: '—') ?></span>
            <span>🕐 <?= date('d M Y, h:i A', strtotime($r['created_at'])) ?></span>
          </div>
          <div style="margin-top:10px;">
            <a href="?delete=<?= $r['id'] ?>&type=product&tab=product" class="btn-del"
               onclick="return confirm('ডিলিট করবেন?')">🗑 ডিলিট</a>
          </div>
        </div>
      <?php endwhile; ?>
    <?php else: ?>
      <div class="empty"><h3>📭 এখনো কোনো প্রোডাক্ট রিভিউ নেই</h3></div>
    <?php endif; ?>
  <?php endif; ?>
</div>

</body>
</html>