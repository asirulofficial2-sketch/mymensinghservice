<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'db.php';

$search = trim($_GET['q'] ?? '');

$products = null;
$count = 0;

if ($search !== '') {
    $searchTerm = '%' . $search . '%';
    $stmt = $conn->prepare("SELECT p.*, c.name as cat_name, c.icon as cat_icon,
                            sc.name as subcat_name 
                            FROM products p 
                            LEFT JOIN shop_categories c ON p.category_id = c.id 
                            LEFT JOIN shop_subcategories sc ON p.subcategory_id = sc.id 
                            WHERE p.active=1 
                            AND (p.name LIKE ? OR p.description LIKE ? OR p.brand LIKE ? OR p.product_code LIKE ?)
                            ORDER BY p.id DESC");
    $stmt->bind_param("ssss", $searchTerm, $searchTerm, $searchTerm, $searchTerm);
    $stmt->execute();
    $products = $stmt->get_result();
    $count = $products->num_rows;
}

$shopNotice = null;
$noticeResult = $conn->query("SELECT * FROM shop_notices WHERE active=1 ORDER BY id DESC LIMIT 1");
if ($noticeResult && $noticeResult->num_rows > 0) {
    $shopNotice = $noticeResult->fetch_assoc();
}
?>
<!DOCTYPE html>
<html lang="bn">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>সার্চ: <?= htmlspecialchars($search) ?> — কেনাকাটা</title>
<style>
* { margin:0; padding:0; box-sizing:border-box; font-family:'Segoe UI',sans-serif; }
body { background: #f5f7fa; }
header {
  background: linear-gradient(135deg, #1e3a8a, #3b82f6);
  color: white; padding: 15px 25px;
  display: flex; justify-content: space-between; align-items: center;
  box-shadow: 0 2px 10px rgba(0,0,0,0.1);
  flex-wrap: wrap; gap: 10px;
  position: sticky; top: 0; z-index: 100;
}
header h1 { font-size: 20px; }
header nav { display: flex; gap: 8px; flex-wrap: wrap; }
header nav a {
  color: white; text-decoration: none;
  padding: 9px 16px; background: rgba(255,255,255,0.15);
  border-radius: 25px; font-size: 14px;
  transition: 0.3s; font-weight: 500;
}
header nav a:hover { background: rgba(255,255,255,0.3); }
header nav a.btn-shop { background: #8b5cf6; font-weight: 700; }
header nav a.btn-work { background: #16a34a; }
header nav a.btn-order { background: #fbbf24; color: #78350f; font-weight: 700; }
.shop-notice {
  background: linear-gradient(90deg, #ede9fe, #ddd6fe);
  color: #5b21b6; text-align: center;
  padding: 13px 20px; font-weight: 600;
  border-bottom: 2px solid #8b5cf6; font-size: 15px;
}
.search-hero {
  background: linear-gradient(135deg, #dcfce7, #f0fdf4);
  padding: 40px 20px 30px; text-align: center;
}
.search-hero h2 { font-size: 28px; color: #166534; margin-bottom: 20px; font-weight: 800; }
.search-form {
  max-width: 600px; margin: 0 auto;
  display: flex; gap: 10px; background: white;
  padding: 8px; border-radius: 50px;
  box-shadow: 0 6px 25px rgba(0,0,0,0.1);
}
.search-form input {
  flex: 1; border: none; padding: 15px 25px;
  font-size: 16px; font-family: inherit;
  border-radius: 40px; outline: none;
}
.search-form input:focus { background: #f8fafc; }
.search-form button {
  background: linear-gradient(135deg, #16a34a, #15803d);
  color: white; border: none;
  padding: 15px 30px; border-radius: 40px;
  font-size: 16px; font-weight: 700;
  cursor: pointer; font-family: inherit;
  white-space: nowrap;
}
.result-info {
  max-width: 1100px; margin: 30px auto 0;
  padding: 0 20px; color: #475569; font-size: 15px;
}
.result-info b { color: #1e3a8a; }
.products-section { max-width: 1100px; margin: 25px auto 0; padding: 0 20px 60px; }
.products-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
  gap: 20px;
}
.product-card {
  background: white; border-radius: 18px; overflow: hidden;
  text-decoration: none; color: #222;
  box-shadow: 0 4px 15px rgba(0,0,0,0.06);
  transition: 0.3s; border: 2px solid transparent; display: block;
}
.product-card:hover {
  transform: translateY(-8px); border-color: #3b82f6;
  box-shadow: 0 16px 35px rgba(59,130,246,0.2);
}
.product-card .p-img {
  height: 200px;
  background: linear-gradient(135deg, #dbeafe, #eff6ff);
  display: flex; align-items: center; justify-content: center;
  overflow: hidden;
}
.product-card .p-img img { width: 100%; height: 100%; object-fit: cover; transition: 0.35s; }
.product-card:hover .p-img img { transform: scale(1.08); }
.product-card .p-img .emoji { font-size: 70px; }
.product-card .p-body { padding: 15px 16px 18px; }
.product-card .p-body h4 {
  font-size: 16px; color: #1e3a8a; font-weight: 700;
  margin-bottom: 5px; min-height: 40px;
}
.product-card .p-code {
  background: #1e293b; color: #4ade80;
  padding: 3px 10px; border-radius: 8px;
  font-family: 'Courier New', monospace;
  font-size: 11px; font-weight: 700;
  display: inline-block; margin-bottom: 8px;
}
.product-card .p-cat {
  font-size: 11px; color: #64748b; background: #f1f5f9;
  padding: 3px 8px; border-radius: 10px;
  display: inline-block; margin-bottom: 10px;
}
.product-card .p-price { font-size: 18px; font-weight: 800; color: #16a34a; }
.product-card .p-price del {
  font-size: 13px; color: #94a3b8; font-weight: 400; margin-right: 6px;
}
.product-card .p-discount {
  display: inline-block; background: #dc2626; color: white;
  font-size: 11px; font-weight: 700; padding: 2px 8px;
  border-radius: 10px; margin-left: 6px;
}
.empty-result {
  background: white; max-width: 600px; margin: 40px auto;
  padding: 70px 30px; border-radius: 25px; text-align: center;
  box-shadow: 0 4px 20px rgba(0,0,0,0.06);
}
.empty-result .icon { font-size: 90px; display: block; margin-bottom: 20px; opacity: 0.4; }
.empty-result h3 { color: #1e3a8a; font-size: 22px; margin-bottom: 10px; }
.empty-result p { color: #64748b; margin-bottom: 25px; }
.empty-result a {
  display: inline-block; background: #2563eb; color: white;
  padding: 12px 28px; border-radius: 25px;
  text-decoration: none; font-weight: 600;
}
footer { background: #0f172a; color: #cbd5e1; text-align: center; padding: 30px 20px; }
footer p { margin-bottom: 8px; }
footer a { color: #60a5fa; text-decoration: none; }
.floating-call {
  position: fixed; bottom: 25px; right: 25px;
  background: #16a34a; color: white;
  width: 60px; height: 60px; border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-size: 26px; text-decoration: none;
  box-shadow: 0 8px 25px rgba(22,163,74,0.5); z-index: 999;
}
@media (max-width: 600px) {
  header h1 { font-size: 16px; }
  header nav a { padding: 7px 12px; font-size: 13px; }
  .search-hero h2 { font-size: 22px; }
  .search-form { flex-direction: column; padding: 12px; border-radius: 20px; }
  .search-form input { border-radius: 12px; background: #f8fafc; }
  .search-form button { border-radius: 12px; }
  .products-grid { grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 15px; }
  .product-card .p-img { height: 160px; }
}
</style>
</head>
<body>

<header>
  <h1><a href="index.php" style="color:white;text-decoration:none;">🏠 ময়মনসিংহ হোম সার্ভিস</a></h1>
  <nav>
    <a href="index.php" style="background: white; color: #1e3a8a; font-weight: 700;">🏠 হোম</a>
    <a href="book.php" class="btn-order">📞 সার্ভিস অর্ডার করুন</a>
    <a href="work.php" class="btn-work">💼 কাজ করতে চাই</a>
    <a href="shop.php" class="btn-shop">🛒 কেনাকাটা করুন</a>
  </nav>
</header>

<?php if ($shopNotice): ?>
<div class="shop-notice">
  🛒 <strong><?= htmlspecialchars($shopNotice['title']) ?>:</strong> 
  <?= htmlspecialchars($shopNotice['message']) ?>
</div>
<?php endif; ?>

<div class="search-hero">
  <h2>🔍 প্রোডাক্ট খুঁজুন</h2>
  <form action="shop_search.php" method="GET" class="search-form">
    <input type="text" name="q" placeholder="প্রোডাক্টের নাম বা কোড লিখুন..." 
           value="<?= htmlspecialchars($search) ?>" required autofocus>
    <button type="submit">🔍 সার্চ</button>
  </form>
</div>

<?php if ($search !== ''): ?>
  <div class="result-info">
    <?php if ($count > 0): ?>
      "<b><?= htmlspecialchars($search) ?></b>" এর জন্য <b><?= $count ?></b> টি প্রোডাক্ট পাওয়া গেছে
    <?php else: ?>
      "<b><?= htmlspecialchars($search) ?></b>" এর জন্য কোনো প্রোডাক্ট পাওয়া যায়নি
    <?php endif; ?>
  </div>

  <?php if ($count > 0): ?>
    <div class="products-section">
      <div class="products-grid">
        <?php while ($p = $products->fetch_assoc()): 
          $imgPath = 'uploads/shop_products/' . $p['image'];
          $hasImg = !empty($p['image']) && file_exists($imgPath);
          $discountPct = 0;
          if ($p['discount_price'] && $p['price'] > 0) {
              $discountPct = round((($p['price'] - $p['discount_price']) / $p['price']) * 100);
          }
        ?>
          <a href="product.php?id=<?= $p['id'] ?>" class="product-card">
            <div class="p-img">
              <?php if ($hasImg): ?>
                <img src="<?= htmlspecialchars($imgPath) ?>" alt="<?= htmlspecialchars($p['name']) ?>">
              <?php else: ?>
                <span class="emoji">📦</span>
              <?php endif; ?>
            </div>
            <div class="p-body">
              <h4><?= htmlspecialchars($p['name']) ?></h4>
              <?php if (!empty($p['product_code'])): ?>
                <div class="p-code">🆔 <?= htmlspecialchars($p['product_code']) ?></div>
              <?php endif; ?>
              <?php if ($p['cat_name']): ?>
                <div class="p-cat">
                  <?= htmlspecialchars(($p['cat_icon'] ?? '') . ' ' . $p['cat_name']) ?>
                </div>
              <?php endif; ?>
              <div class="p-price">
                <?php if ($p['discount_price']): ?>
                  <del>৳<?= number_format($p['price']) ?></del>
                  ৳<?= number_format($p['discount_price']) ?>
                  <?php if ($discountPct > 0): ?>
                    <span class="p-discount">-<?= $discountPct ?>%</span>
                  <?php endif; ?>
                <?php else: ?>
                  ৳<?= number_format($p['price']) ?>
                <?php endif; ?>
              </div>
            </div>
          </a>
        <?php endwhile; ?>
      </div>
    </div>
  <?php else: ?>
    <div class="empty-result">
      <span class="icon">🔍</span>
      <h3>কোনো প্রোডাক্ট পাওয়া যায়নি</h3>
      <p>"<?= htmlspecialchars($search) ?>" নামে বা কোডে কোনো প্রোডাক্ট নেই।</p>
      <a href="shop.php">← সব প্রোডাক্ট দেখুন</a>
    </div>
  <?php endif; ?>

<?php else: ?>
  <div class="empty-result">
    <span class="icon">🔍</span>
    <h3>সার্চ করুন</h3>
    <p>প্রোডাক্টের নাম বা কোড লিখে সার্চ করুন</p>
    <a href="shop.php">← সব প্রোডাক্ট দেখুন</a>
  </div>
<?php endif; ?>

<footer>
  <p>📍 ময়মনসিংহ, বাংলাদেশ</p>
  <p>📞 হটলাইন: <a href="tel:+8801729560032">০১৭২৯-৫৬০০৩২</a></p>
  <p style="margin-top:15px;font-size:13px;color:#64748b;">© <?= date('Y') ?> ময়মনসিংহ হোম সার্ভিস</p>
</footer>

<a href="tel:+8801729560032" class="floating-call" title="কল করুন">📞</a>

</body>
</html>