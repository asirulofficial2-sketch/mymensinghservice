<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'includes/auth.php';
include '../db.php';

$services = $conn->query("SELECT * FROM services ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="bn">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>সার্ভিস ম্যানেজমেন্ট</title>
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
.container { max-width: 1200px; margin: 30px auto; padding: 0 20px; }
.page-head {
  display: flex; justify-content: space-between; align-items: center;
  margin-bottom: 20px; flex-wrap: wrap; gap: 10px;
}
.page-head h2 { color: #1e3a8a; font-size: 22px; }
.btn-add {
  background: #16a34a; color: white; text-decoration: none;
  padding: 10px 20px; border-radius: 10px; font-weight: 600; font-size: 14px;
}
.services-grid {
  display: grid; grid-template-columns: repeat(auto-fill, minmax(280px,1fr));
  gap: 20px;
}
.service-card {
  background: white; border-radius: 15px; overflow: hidden;
  box-shadow: 0 2px 10px rgba(0,0,0,0.06);
  transition: 0.3s;
}
.service-card:hover { transform: translateY(-5px); box-shadow: 0 10px 30px rgba(0,0,0,0.12); }
.service-image {
  height: 160px; background: linear-gradient(135deg, #dbeafe, #eff6ff);
  display: flex; align-items: center; justify-content: center;
  overflow: hidden; position: relative;
}
.service-image img {
  width: 100%; height: 100%; object-fit: cover;
}
.service-image .emoji { font-size: 70px; }
.service-body { padding: 15px; }
.service-body h3 { color: #1e3a8a; font-size: 17px; margin-bottom: 6px; }
.service-body p { color: #64748b; font-size: 13px; margin-bottom: 10px; line-height: 1.5; min-height: 38px; }
.service-body .price {
  display: inline-block; background: #dcfce7; color: #16a34a;
  padding: 5px 12px; border-radius: 20px;
  font-weight: 700; font-size: 13px;
}
.service-actions {
  display: flex; gap: 8px; padding: 12px 15px;
  border-top: 1px solid #f1f5f9;
}
.service-actions a {
  flex: 1; text-align: center;
  padding: 8px; border-radius: 8px;
  font-size: 13px; text-decoration: none; font-weight: 600;
}
.btn-edit { background: #dbeafe; color: #1e40af; }
.btn-delete { background: #fee2e2; color: #dc2626; }
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
  <div class="page-head">
    <h2>⚙️ সার্ভিস ম্যানেজমেন্ট</h2>
    <a href="service_add.php" class="btn-add">+ নতুন সার্ভিস</a>
  </div>

  <div class="services-grid">
    <?php if ($services && $services->num_rows > 0): ?>
      <?php while ($s = $services->fetch_assoc()): ?>
        <div class="service-card">
          <div class="service-image">
            <?php if (!empty($s['image']) && file_exists('../uploads/services/' . $s['image'])): ?>
              <img src="../uploads/services/<?= htmlspecialchars($s['image']) ?>" alt="">
            <?php else: ?>
              <span class="emoji"><?= htmlspecialchars($s['icon'] ?? '🔧') ?></span>
            <?php endif; ?>
          </div>
          <div class="service-body">
            <h3><?= htmlspecialchars($s['name']) ?></h3>
            <p><?= htmlspecialchars($s['description']) ?></p>
            <div class="price">
              <?= $s['min_price'] > 0 ? 'শুরু ৳' . number_format($s['min_price']) : 'ফ্রি কল' ?>
            </div>
          </div>
          <div class="service-actions">
            <a href="service_edit.php?id=<?= $s['id'] ?>" class="btn-edit">✏️ এডিট</a>
            <a href="service_delete.php?id=<?= $s['id'] ?>" class="btn-delete"
               onclick="return confirm('ডিলিট করবেন?')">🗑 ডিলিট</a>
          </div>
        </div>
      <?php endwhile; ?>
    <?php else: ?>
      <p style="padding:20px;">এখনো কোনো সার্ভিস নেই। <a href="service_add.php">যোগ করুন</a></p>
    <?php endif; ?>
  </div>
</div>

</body>
</html>