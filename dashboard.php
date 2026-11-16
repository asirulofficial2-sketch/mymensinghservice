<?php
include 'includes/auth.php';
include '../db.php';

// ============ পরিসংখ্যান ============
$totalServices = $conn->query("SELECT COUNT(*) as c FROM services")->fetch_assoc()['c'];
$totalBookings = $conn->query("SELECT COUNT(*) as c FROM bookings")->fetch_assoc()['c'];
$pendingBookings = $conn->query("SELECT COUNT(*) as c FROM bookings WHERE status='pending'")->fetch_assoc()['c'];
$totalNotices = $conn->query("SELECT COUNT(*) as c FROM notices")->fetch_assoc()['c'];
$totalTechs = $conn->query("SELECT COUNT(*) as c FROM technicians")->fetch_assoc()['c'];
$pendingTechs = $conn->query("SELECT COUNT(*) as c FROM technicians WHERE status='pending'")->fetch_assoc()['c'];
$approvedTechs = $conn->query("SELECT COUNT(*) as c FROM technicians WHERE status='approved'")->fetch_assoc()['c'];

$totalCategories = 0;
$totalSubcategories = 0;
$r = $conn->query("SELECT COUNT(*) as c FROM work_categories");
if ($r) $totalCategories = $r->fetch_assoc()['c'];
$r = $conn->query("SELECT COUNT(*) as c FROM work_subcategories");
if ($r) $totalSubcategories = $r->fetch_assoc()['c'];

$totalShopCats = 0;
$totalProducts = 0;
$totalShopOrders = 0;
$pendingShopOrders = 0;
$totalBanners = 0;
$totalReviews = 0;

$r = $conn->query("SELECT COUNT(*) as c FROM shop_categories");
if ($r) $totalShopCats = $r->fetch_assoc()['c'];

$r = $conn->query("SELECT COUNT(*) as c FROM products");
if ($r) $totalProducts = $r->fetch_assoc()['c'];

$r = $conn->query("SELECT COUNT(*) as c FROM shop_orders");
if ($r) $totalShopOrders = $r->fetch_assoc()['c'];

$r = $conn->query("SELECT COUNT(*) as c FROM shop_orders WHERE status='pending'");
if ($r) $pendingShopOrders = $r->fetch_assoc()['c'];

$r = $conn->query("SELECT COUNT(*) as c FROM banners");
if ($r) $totalBanners = $r->fetch_assoc()['c'];

$r = $conn->query("SELECT COUNT(*) as c FROM service_reviews");
if ($r) $totalReviews = $r->fetch_assoc()['c'];

// ============ নতুন: আয়ের হিসাব ============
$serviceIncome = $conn->query("SELECT SUM(total) as s FROM bookings WHERE status='completed'")->fetch_assoc()['s'] ?? 0;
$shopIncome = $conn->query("SELECT SUM(total) as s FROM shop_orders WHERE status='delivered'")->fetch_assoc()['s'] ?? 0;
$totalIncome = $serviceIncome + $shopIncome;

// ============ নতুন: প্রোডাক্ট লিস্ট ============
$productsList = $conn->query("SELECT p.*, c.name as cat_name, c.icon as cat_icon 
                              FROM products p 
                              LEFT JOIN shop_categories c ON p.category_id = c.id 
                              ORDER BY p.stock ASC, p.id DESC LIMIT 10");
?>
<!DOCTYPE html>
<html lang="bn">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>অ্যাডমিন ড্যাশবোর্ড</title>
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
.topbar a:hover { background: rgba(255,255,255,0.35); }
.container { max-width: 1200px; margin: 30px auto; padding: 0 20px 50px; }

/* ===== স্ট্যাটস ===== */
.stats {
  display: grid; grid-template-columns: repeat(auto-fit, minmax(160px,1fr));
  gap: 15px; margin-bottom: 30px;
}
.stat {
  background: white; padding: 22px; border-radius: 15px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.06);
  border-left: 5px solid #3b82f6;
}
.stat h3 { font-size: 13px; color: #64748b; margin-bottom: 8px; }
.stat .num { font-size: 30px; font-weight: bold; color: #1e3a8a; }
.stat .sub { font-size: 11px; color: #94a3b8; margin-top: 4px; }

/* ===== সেকশন টাইটেল ===== */
.section-title {
  color: #1e3a8a; font-size: 18px; margin-bottom: 15px;
  margin-top: 35px;
  padding-bottom: 10px;
  border-bottom: 2px solid #e2e8f0;
  display: flex; align-items: center; gap: 8px;
}

/* ===== মেনু ===== */
.menu {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px,1fr));
  gap: 18px;
}
.menu-card {
  background: white; padding: 28px 18px; border-radius: 15px;
  text-align: center; text-decoration: none; color: #222;
  box-shadow: 0 2px 10px rgba(0,0,0,0.06);
  transition: 0.3s; border: 2px solid transparent;
  position: relative;
}
.menu-card:hover {
  transform: translateY(-5px);
  border-color: #3b82f6;
  box-shadow: 0 10px 30px rgba(59,130,246,0.2);
}
.menu-card .ic { font-size: 38px; display: block; margin-bottom: 10px; }
.menu-card h3 { color: #1e3a8a; font-size: 15px; font-weight: 700; }

.menu-card .notify-dot {
  position: absolute; top: 15px; right: 15px;
  background: #f59e0b; color: white;
  font-size: 12px; font-weight: 700;
  min-width: 22px; height: 22px;
  border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  padding: 0 6px;
  box-shadow: 0 3px 8px rgba(245,158,11,0.5);
}

/* ===== শপ সেকশন ===== */
.shop-section {
  background: linear-gradient(135deg, #f0fdf4, #dcfce7);
  padding: 25px;
  border-radius: 20px;
  margin-bottom: 20px;
  border: 3px solid #16a34a;
}
.shop-section .section-title {
  margin-top: 0;
  border-bottom-color: #86efac;
  color: #166534;
  font-size: 20px;
  font-weight: 800;
}
.shop-section .menu-card { border: 2px solid #bbf7d0; }
.shop-section .menu-card:hover {
  border-color: #16a34a;
  box-shadow: 0 10px 30px rgba(22,163,74,0.2);
}
.shop-section .menu-card h3 { color: #166534; }

/* ===== স্পেশাল সেকশন ===== */
.special-section {
  background: linear-gradient(135deg, #fef3c7, #fde68a);
  padding: 25px;
  border-radius: 20px;
  margin-bottom: 20px;
  border: 3px solid #f59e0b;
}
.special-section .section-title {
  margin-top: 0;
  border-bottom-color: #fbbf24;
  color: #92400e;
  font-size: 20px;
  font-weight: 800;
}
.special-section .menu-card { border: 2px solid #fde047; }
.special-section .menu-card:hover {
  border-color: #f59e0b;
  box-shadow: 0 10px 30px rgba(245,158,11,0.2);
}
.special-section .menu-card h3 { color: #92400e; }

/* ===== নতুন: আয় রিপোর্ট ===== */
.income-banner {
  background: linear-gradient(135deg, #16a34a, #15803d);
  color: white; padding: 30px; border-radius: 20px;
  text-align: center; margin-bottom: 25px;
  box-shadow: 0 10px 30px rgba(22,163,74,0.3);
}
.income-banner h3 { font-size: 16px; opacity: 0.9; margin-bottom: 10px; }
.income-banner .amount { font-size: 42px; font-weight: 800; }
.income-banner .breakdown {
  margin-top: 15px; display: flex; justify-content: center; gap: 15px;
  flex-wrap: wrap; font-size: 14px;
}
.income-banner .breakdown span {
  background: rgba(255,255,255,0.2); padding: 6px 18px; border-radius: 20px;
}

/* ===== নতুন: প্রোডাক্ট টেবিল ===== */
.table-card {
  background: white; border-radius: 15px; overflow: hidden;
  box-shadow: 0 2px 10px rgba(0,0,0,0.06); margin-bottom: 20px;
}
table { width: 100%; border-collapse: collapse; }
th {
  background: #eff6ff; color: #1e40af;
  padding: 14px 15px; text-align: left; font-size: 13px;
  font-weight: 700;
}
td {
  padding: 12px 15px; border-bottom: 1px solid #f1f5f9;
  font-size: 13px; color: #475569;
}
td img {
  width: 40px; height: 40px; border-radius: 8px; object-fit: cover;
}
td.amount { font-weight: 700; color: #16a34a; }
.stock-badge {
  padding: 3px 10px; border-radius: 10px; font-weight: 700; font-size: 12px;
}
.stock-ok { background: #dcfce7; color: #16a34a; }
.stock-low { background: #fef3c7; color: #d97706; }
.stock-empty { background: #fee2e2; color: #dc2626; }

.btn-view-all {
  display: inline-block; background: #3b82f6; color: white;
  padding: 12px 30px; border-radius: 25px; text-decoration: none;
  font-weight: 700; margin-top: 10px;
}
</style>
</head>
<body>

<div class="topbar">
  <h1>🛠️ অ্যাডমিন ড্যাশবোর্ড</h1>
  <div>
    স্বাগতম, <b><?= htmlspecialchars($_SESSION['admin_name']) ?></b>
    <a href="logout.php">লগআউট</a>
  </div>
</div>

<div class="container">

  <!-- ============ স্ট্যাটস ============ -->
  <div class="stats">
    <div class="stat">
      <h3>মোট সার্ভিস</h3>
      <div class="num"><?= $totalServices ?></div>
    </div>
    <div class="stat" style="border-color:#16a34a">
      <h3>মোট বুকিং</h3>
      <div class="num"><?= $totalBookings ?></div>
      <div class="sub">অপেক্ষমাণ: <?= $pendingBookings ?></div>
    </div>
    <div class="stat" style="border-color:#8b5cf6">
      <h3>মোট সেবাদাতা</h3>
      <div class="num"><?= $totalTechs ?></div>
      <div class="sub">অনুমোদিত: <?= $approvedTechs ?> | অপেক্ষমাণ: <?= $pendingTechs ?></div>
    </div>
    <div class="stat" style="border-color:#f59e0b">
      <h3>ক্যাটাগরি</h3>
      <div class="num"><?= $totalCategories ?></div>
      <div class="sub">সাব-ক্যাটাগরি: <?= $totalSubcategories ?></div>
    </div>
    <div class="stat" style="border-color:#ec4899">
      <h3>নোটিশ</h3>
      <div class="num"><?= $totalNotices ?></div>
    </div>
    <div class="stat" style="border-color:#06b6d4">
      <h3>মোট প্রোডাক্ট</h3>
      <div class="num"><?= $totalProducts ?></div>
      <div class="sub">শপ ক্যাটাগরি: <?= $totalShopCats ?></div>
    </div>
    <div class="stat" style="border-color:#f97316">
      <h3>শপ অর্ডার</h3>
      <div class="num"><?= $totalShopOrders ?></div>
      <div class="sub">অপেক্ষমাণ: <?= $pendingShopOrders ?></div>
    </div>
  </div>

  <!-- ============ শপ সেকশন ============ -->
  <div class="shop-section">
    <h2 class="section-title">🛒 অনলাইন দোকান</h2>
    <div class="menu">
      <a href="shop_products.php" class="menu-card">
        <span class="ic">📦</span>
        <h3>প্রোডাক্ট ম্যানেজমেন্ট</h3>
      </a>
      <a href="shop_categories.php" class="menu-card">
        <span class="ic">🛒</span>
        <h3>শপ ক্যাটাগরি</h3>
      </a>
      <a href="shop_orders.php" class="menu-card">
        <span class="ic">📋</span>
        <h3>শপ অর্ডার</h3>
        <?php if ($pendingShopOrders > 0): ?>
          <span class="notify-dot"><?= $pendingShopOrders ?></span>
        <?php endif; ?>
      </a>
      <a href="shop_notices.php" class="menu-card">
        <span class="ic">📢</span>
        <h3>শপ নোটিশ</h3>
      </a>
    </div>
  </div>

  <!-- ============ সার্ভিস সেকশন ============ -->
  <h2 class="section-title">⚙️ সার্ভিস ও বুকিং</h2>
  <div class="menu">
    <a href="work_applications.php" class="menu-card">
      <span class="ic">💼</span>
      <h3>কাজের আবেদন</h3>
      <?php if ($pendingTechs > 0): ?>
        <span class="notify-dot"><?= $pendingTechs ?></span>
      <?php endif; ?>
    </a>
    <a href="technicians.php" class="menu-card">
      <span class="ic">👷</span>
      <h3>সেবাদাতাগণ</h3>
    </a>
    <a href="work_categories.php" class="menu-card">
      <span class="ic">📁</span>
      <h3>ক্যাটাগরি ম্যানেজমেন্ট</h3>
    </a>
    <a href="services.php" class="menu-card">
      <span class="ic">🔧</span>
      <h3>সার্ভিস ম্যানেজমেন্ট</h3>
    </a>
    <a href="bookings.php" class="menu-card">
      <span class="ic">📋</span>
      <h3>বুকিং দেখুন</h3>
      <?php if ($pendingBookings > 0): ?>
        <span class="notify-dot"><?= $pendingBookings ?></span>
      <?php endif; ?>
    </a>
    <a href="notices.php" class="menu-card">
      <span class="ic">📢</span>
      <h3>নোটিশ বোর্ড</h3>
    </a>
  </div>

  <!-- ============ স্পেশাল সেকশন ============ -->
  <div class="special-section" style="margin-top: 30px;">
    <h2 class="section-title">🎬 সাইট কাস্টমাইজেশন</h2>
    <div class="menu">
      <a href="banners.php" class="menu-card">
        <span class="ic">🎬</span>
        <h3>ব্যানার স্লাইডার</h3>
      </a>
      <a href="marquee.php" class="menu-card">
        <span class="ic">📢</span>
        <h3>স্ক্রলিং টেক্সট</h3>
      </a>
      <a href="reviews.php" class="menu-card">
        <span class="ic">⭐</span>
        <h3>কাস্টমার রিভিউ</h3>
      </a>
    </div>
  </div>

  <!-- ============ নতুন: আয় রিপোর্ট ============ -->
  <h2 class="section-title">💰 আয়ের হিসাব</h2>
  <div class="income-banner">
    <h3>মোট আয় (সম্পন্ন কাজ)</h3>
    <div class="amount">৳<?= number_format($totalIncome) ?></div>
    <div class="breakdown">
      <span>🔧 সার্ভিস: ৳<?= number_format($serviceIncome) ?></span>
      <span>🛒 শপ: ৳<?= number_format($shopIncome) ?></span>
    </div>
  </div>

  <!-- ============ নতুন: প্রোডাক্ট লিস্ট ============ -->
  <h2 class="section-title">📦 বর্তমান প্রোডাক্ট লিস্ট</h2>
  <div class="table-card">
    <table>
      <thead>
        <tr>
          <th>ছবি</th>
          <th>প্রোডাক্ট</th>
          <th>ক্যাটাগরি</th>
          <th>দাম</th>
          <th>স্টক</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($productsList && $productsList->num_rows > 0): ?>
          <?php while ($p = $productsList->fetch_assoc()): 
            $imgPath = '../uploads/shop_products/' . $p['image'];
            $hasImg = !empty($p['image']) && file_exists($imgPath);
            
            $stockClass = 'stock-ok';
            $stockText = $p['stock'] . ' টি';
            if ($p['stock'] == 0) { $stockClass = 'stock-empty'; $stockText = 'শেষ'; }
            elseif ($p['stock'] < 5) { $stockClass = 'stock-low'; }
          ?>
            <tr>
              <td>
                <?php if ($hasImg): ?>
                  <img src="<?= htmlspecialchars($imgPath) ?>" alt="">
                <?php else: ?>
                  <span style="font-size:24px;">📦</span>
                <?php endif; ?>
              </td>
              <td><b><?= htmlspecialchars($p['name']) ?></b></td>
              <td>
                <?php if ($p['cat_name']): ?>
                  <?= htmlspecialchars(($p['cat_icon'] ?? '') . ' ' . $p['cat_name']) ?>
                <?php else: ?>
                  —
                <?php endif; ?>
              </td>
              <td class="amount">
                ৳<?= number_format($p['discount_price'] ?: $p['price']) ?>
              </td>
              <td>
                <span class="stock-badge <?= $stockClass ?>"><?= $stockText ?></span>
              </td>
            </tr>
          <?php endwhile; ?>
        <?php else: ?>
          <tr><td colspan="5" style="text-align:center;padding:30px;">কোনো প্রোডাক্ট নেই</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <div style="text-align:center;">
    <a href="shop_products.php" class="btn-view-all">
      সব প্রোডাক্ট দেখুন →
    </a>
  </div>

</div>

</body>
</html>