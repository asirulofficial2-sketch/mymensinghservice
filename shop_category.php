<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include 'db.php';

// ক্যাটাগরি আইডি নেওয়া
$cat_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// ক্যাটাগরির তথ্য বের করা
$category = null;
if ($cat_id > 0) {
    $catQuery = $conn->query("SELECT * FROM shop_categories WHERE id = $cat_id AND active = 1");
    if ($catQuery && $catQuery->num_rows > 0) {
        $category = $catQuery->fetch_assoc();
    }
}

// ওই ক্যাটাগরির প্রোডাক্ট লোড করা
$products = null;
if ($category) {
    $products = $conn->query("SELECT * FROM products WHERE category_id = $cat_id AND active = 1 ORDER BY id DESC");
}

// মার্কি নোটিফিকেশন
$marquee = $conn->query("SELECT * FROM marquee WHERE active=1 LIMIT 1")->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="bn">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $category ? htmlspecialchars($category['name']) : 'ক্যাটাগরি' ?> — ময়মনসিংহ হোম সার্ভিস</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
* { margin:0; padding:0; box-sizing:border-box; font-family:'Segoe UI',sans-serif; }
body { background: #f5f7fa; }

header {
  background: linear-gradient(135deg, #1e3a8a, #3b82f6);
  color: white; padding: 15px 25px;
  display: flex; justify-content: space-between; align-items: center;
  flex-wrap: wrap; gap: 10px; position: sticky; top: 0; z-index: 100;
}
header h1 { font-size: 20px; }
header nav { display: flex; gap: 8px; flex-wrap: wrap; }
header nav a {
  color: white; text-decoration: none;
  padding: 9px 16px; background: rgba(255,255,255,0.15);
  border-radius: 25px; font-size: 14px; transition: 0.3s;
}
header nav a:hover { background: rgba(255,255,255,0.3); }
header nav a.btn-shop { background: #8b5cf6; font-weight: 700; }

.marquee-bar { background: #fef3c7; color: #92400e; padding: 12px 0; font-weight: 700; font-size: 15px; border-bottom: 2px solid #fbbf24; overflow: hidden; white-space: nowrap; }
.marquee-content { display: inline-block; animation: scroll-left 25s linear infinite; }
.marquee-content:hover { animation-play-state: paused; }
@keyframes scroll-left { 0% { transform: translateX(100vw); } 100% { transform: translateX(-100%); } }

.cat-header {
  background: linear-gradient(135deg, #dcfce7, #f0fdf4);
  padding: 40px 20px; text-align: center;
}
.cat-header h2 { font-size: 32px; color: #166534; font-weight: 800; margin-bottom: 10px; }
.cat-header p { color: #475569; font-size: 16px; }

.products-section { max-width: 1200px; margin: 40px auto; padding: 0 20px 60px; }
.products-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
  gap: 22px;
}
.product-card {
  background: white; border-radius: 18px; overflow: hidden;
  box-shadow: 0 4px 15px rgba(0,0,0,0.06); transition: 0.3s;
  border: 2px solid transparent; display: flex; flex-direction: column;
}
.product-card:hover {
  transform: translateY(-8px); border-color: #3b82f6;
  box-shadow: 0 16px 35px rgba(59,130,246,0.2);
}
.product-card .p-img {
  height: 220px; background: linear-gradient(135deg, #dbeafe, #eff6ff);
  display: flex; align-items: center; justify-content: center; overflow: hidden; position: relative;
}
.product-card .p-img img { width: 100%; height: 100%; object-fit: cover; transition: 0.35s; }
.product-card:hover .p-img img { transform: scale(1.08); }
.product-card .discount-badge {
  position: absolute; top: 10px; left: 10px;
  background: #dc2626; color: white; padding: 4px 10px; border-radius: 20px;
  font-size: 12px; font-weight: 700;
}
.product-card .p-body { padding: 18px; display: flex; flex-direction: column; flex: 1; }
.product-card .p-body h4 { font-size: 17px; color: #1e3a8a; font-weight: 700; margin-bottom: 8px; min-height: 45px; }
.product-card .p-price { font-size: 20px; font-weight: 800; color: #16a34a; margin-bottom: 15px; }
.product-card .p-price del { font-size: 14px; color: #94a3b8; font-weight: 400; margin-right: 6px; }
.product-card .btn-group { display: flex; gap: 8px; margin-top: auto; }
.btn-order {
  flex: 1; background: linear-gradient(135deg, #16a34a, #15803d);
  color: white; padding: 10px; border-radius: 10px; text-decoration: none;
  font-weight: 700; font-size: 14px; text-align: center; transition: 0.3s;
}
.btn-order:hover { background: #166534; }
.btn-cart {
  flex: 1; background: #f59e0b; color: white; padding: 10px; border-radius: 10px;
  border: none; font-weight: 700; font-size: 14px; cursor: pointer;
  font-family: inherit; transition: 0.3s; text-align: center;
}
.btn-cart:hover { background: #d97706; }

.empty-result {
  background: white; max-width: 600px; margin: 40px auto;
  padding: 60px 30px; border-radius: 20px; text-align: center;
  box-shadow: 0 4px 20px rgba(0,0,0,0.06);
}
.empty-result .icon { font-size: 80px; display: block; margin-bottom: 20px; opacity: 0.5; }
.empty-result h3 { color: #1e3a8a; font-size: 22px; margin-bottom: 10px; }
.empty-result a {
  display: inline-block; margin-top: 15px; background: #2563eb; color: white;
  padding: 12px 28px; border-radius: 25px; text-decoration: none; font-weight: 600;
}

footer { background: #0f172a; color: #cbd5e1; text-align: center; padding: 30px 20px; margin-top: 40px; }
footer p { margin-bottom: 8px; }
footer a { color: #60a5fa; text-decoration: none; }
.floating-call {
  position: fixed; bottom: 25px; right: 25px; background: #16a34a; color: white;
  width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center;
  justify-content: center; font-size: 26px; text-decoration: none;
  box-shadow: 0 8px 25px rgba(22,163,74,0.5); z-index: 999;
}

@media (max-width: 600px) {
  header h1 { font-size: 16px; }
  .cat-header h2 { font-size: 24px; }
  .products-grid { grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 15px; }
  .product-card .p-img { height: 160px; }
  .product-card .p-body h4 { font-size: 15px; min-height: 40px; }
  .product-card .p-price { font-size: 17px; }
  .product-card .btn-group { flex-direction: column; }
}
</style>
</head>
<body>

<header>
  <h1><a href="index.php" style="color:white;text-decoration:none;">🏠 ময়মনসিংহ হোম সার্ভিস</a></h1>
  <nav>
    <a href="index.php">🏠 হোম</a>
    <a href="book.php" style="background:#fbbf24; color:#78350f; font-weight:700;">📞 সার্ভিস</a>
    <a href="shop.php" class="btn-shop">🛒 কেনাকাটা</a>
  </nav>
</header>

<?php if ($marquee): ?>
<div class="marquee-bar">
  <div class="marquee-content">📢 <?= htmlspecialchars($marquee['message']) ?></div>
</div>
<?php endif; ?>

<?php if ($category): ?>
  <div class="cat-header">
    <h2><?= htmlspecialchars($category['name']) ?></h2>
    <p>এই ক্যাটাগরির সেরা প্রোডাক্টগুলো দেখুন</p>
  </div>

  <div class="products-section">
    <?php if ($products && $products->num_rows > 0): ?>
      <div class="products-grid">
        <?php while ($p = $products->fetch_assoc()): 
          $imgPath = 'uploads/shop_products/' . $p['image'];
          $hasImg = !empty($p['image']) && file_exists($imgPath);
          $discountPct = 0;
          if ($p['discount_price'] && $p['price'] > 0) {
              $discountPct = round((($p['price'] - $p['discount_price']) / $p['price']) * 100);
          }
          $currentPrice = $p['discount_price'] ? $p['discount_price'] : $p['price'];
        ?>
          <div class="product-card">
            <div class="p-img">
              <?php if ($hasImg): ?>
                <img src="<?= htmlspecialchars($imgPath) ?>" alt="<?= htmlspecialchars($p['name']) ?>">
              <?php else: ?>
                <span style="font-size:70px;">📦</span>
              <?php endif; ?>
              <?php if ($discountPct > 0): ?>
                <span class="discount-badge">-<?= $discountPct ?>%</span>
              <?php endif; ?>
            </div>
            <div class="p-body">
              <h4><?= htmlspecialchars($p['name']) ?></h4>
              <div class="p-price">
                <?php if ($p['discount_price']): ?>
                  <del>৳<?= number_format($p['price']) ?></del>
                <?php endif; ?>
                ৳<?= number_format($currentPrice) ?>
              </div>
              <div class="btn-group">
                <!-- অর্ডার করুন বাটন -->
                <a href="product.php?id=<?= $p['id'] ?>" class="btn-order">🛒 অর্ডার করুন</a>
                <!-- কার্টে যোগ করুন বাটন (এখানে আপনার কার্ট লজিক বসাতে হবে) -->
                <button class="btn-cart" onclick="addToCart(<?= $p['id'] ?>)">➕ কার্ট</button>
              </div>
            </div>
          </div>
        <?php endwhile; ?>
      </div>
    <?php else: ?>
      <div class="empty-result">
        <span class="icon">📦</span>
        <h3>এই ক্যাটাগরিতে কোনো প্রোডাক্ট নেই</h3>
        <a href="shop.php">← অন্য ক্যাটাগরি দেখুন</a>
      </div>
    <?php endif; ?>
  </div>
<?php else: ?>
  <div class="empty-result" style="margin-top: 60px;">
    <span class="icon">❌</span>
    <h3>ক্যাটাগরি পাওয়া যায়নি</h3>
    <a href="shop.php">← সব ক্যাটাগরি দেখুন</a>
  </div>
<?php endif; ?>

<footer>
  <p>📍 ময়মনসিংহ, বাংলাদেশ</p>
  <p>📞 হটলাইন: <a href="tel:+8801729560032">০১৭২৯-৫৬০০৩২</a></p>
  <p style="margin-top:15px;font-size:13px;color:#64748b;">© <?= date('Y') ?> ময়মনসিংহ হোম সার্ভিস</p>
</footer>

<a href="tel:+8801729560032" class="floating-call"><i class="fa-solid fa-phone"></i></a>

<script>
// কার্টে যোগ করার ফাংশন (আপনার প্রজেক্টে কার্ট সিস্টেম থাকলে সেখানে পাঠাবে)
function addToCart(productId) {
    // এখানে আপনার কার্টে যোগ করার লজিক বসাবেন (যেমন: fetch API বা location.href)
    alert("প্রোডাক্টটি কার্টে যোগ করা হয়েছে! (প্রোডাক্ট আইডি: " + productId + ")");
    
    // উদাহরণস্বরূপ, আপনি চাইলে সরাসরি কার্ট পেজে পাঠাতে পারেন:
    // window.location.href = "cart.php?add=" + productId;
}
</script>

</body>
</html>