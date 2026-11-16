<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'db.php';
$marquee = null;
$marqueeResult = $conn->query("SELECT * FROM marquee WHERE active=1 LIMIT 1");
if ($marqueeResult && $marqueeResult->num_rows > 0) {
    $marquee = $marqueeResult->fetch_assoc();
}
$id = intval($_GET['id'] ?? 0);

$stmt = $conn->prepare("SELECT p.*, c.name as cat_name, c.icon as cat_icon,
                        sc.name as subcat_name 
                        FROM products p 
                        LEFT JOIN shop_categories c ON p.category_id = c.id 
                        LEFT JOIN shop_subcategories sc ON p.subcategory_id = sc.id 
                        WHERE p.id=? AND p.active=1");
$stmt->bind_param("i", $id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();

if (!$product) {
    header("Location: shop.php");
    exit;
}

$discountPct = 0;
if ($product['discount_price'] && $product['price'] > 0) {
    $discountPct = round((($product['price'] - $product['discount_price']) / $product['price']) * 100);
}

$images = [];
foreach (['image', 'image2', 'image3'] as $imgKey) {
    if (!empty($product[$imgKey]) && file_exists('uploads/shop_products/' . $product[$imgKey])) {
        $images[] = 'uploads/shop_products/' . $product[$imgKey];
    }
}

$cartCount = 0;
if (!empty($_SESSION['shop_cart'])) {
    $cartCount = array_sum($_SESSION['shop_cart']);
}

$related = $conn->query("SELECT * FROM products 
                         WHERE category_id=" . intval($product['category_id']) . " 
                         AND id != $id AND active=1 
                         ORDER BY RAND() LIMIT 4");

$msg = $_GET['msg'] ?? '';
$shopNotice = $conn->query("SELECT * FROM shop_notices WHERE active=1 ORDER BY id DESC LIMIT 1")->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="bn">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($product['name']) ?> — কেনাকাটা</title>
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
header nav { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; }
header nav a {
  color: white; text-decoration: none;
  padding: 9px 16px; background: rgba(255,255,255,0.15);
  border-radius: 25px; font-size: 14px;
  transition: 0.3s; font-weight: 500;
}
header nav a:hover { background: rgba(255,255,255,0.3); }
header nav a.btn-shop { background: #8b5cf6; font-weight: 700; }
header nav a.btn-shop:hover { background: #7c3aed; }
header nav a.btn-work { background: #16a34a; }
header nav a.btn-work:hover { background: #15803d; }
header nav a.btn-order { background: #fbbf24; color: #78350f; font-weight: 700; }
header nav a.btn-order:hover { background: #f59e0b; }
header nav a.btn-cart { background: #dc2626; font-weight: 700; }
header nav a.btn-cart:hover { background: #b91c1c; }
header nav .cart-badge {
  background: white; color: #dc2626;
  padding: 1px 7px; border-radius: 10px;
  font-size: 11px; font-weight: 800; margin-left: 4px;
}

.shop-notice {
  background: linear-gradient(90deg, #ede9fe, #ddd6fe);
  color: #5b21b6; text-align: center;
  padding: 13px 20px; font-weight: 600;
  border-bottom: 2px solid #8b5cf6; font-size: 15px;
}

.success-msg {
  background: #dcfce7; color: #166534;
  padding: 15px 25px; text-align: center;
  font-weight: 700; border-bottom: 3px solid #16a34a; font-size: 15px;
}
.success-msg a { color: #166534; text-decoration: underline; }

.breadcrumb {
  background: white; padding: 15px 25px;
  font-size: 14px; color: #64748b;
  border-bottom: 1px solid #e2e8f0;
}
.breadcrumb a { color: #1e40af; text-decoration: none; font-weight: 600; }
.breadcrumb a:hover { text-decoration: underline; }
.breadcrumb span { margin: 0 8px; color: #cbd5e1; }

.container { max-width: 1200px; margin: 30px auto; padding: 0 20px; }
.product-main {
  background: white; border-radius: 20px;
  box-shadow: 0 4px 20px rgba(0,0,0,0.06);
  padding: 30px;
  display: grid; grid-template-columns: 1fr 1fr;
  gap: 40px; margin-bottom: 30px;
}
.gallery { display: flex; flex-direction: column; gap: 15px; }
.main-img {
  height: 400px;
  background: linear-gradient(135deg, #dbeafe, #eff6ff);
  border-radius: 15px;
  display: flex; align-items: center; justify-content: center;
  overflow: hidden;
  border: 2px solid #e2e8f0; position: relative;
}
.main-img img { width: 100%; height: 100%; object-fit: contain; }
.main-img .no-img { font-size: 100px; color: #94a3b8; }
.main-img .discount-badge {
  position: absolute; top: 15px; left: 15px;
  background: #dc2626; color: white;
  padding: 8px 15px; border-radius: 20px;
  font-size: 14px; font-weight: 700;
  box-shadow: 0 4px 12px rgba(220,38,38,0.4);
}
.thumbnails { display: flex; gap: 10px; flex-wrap: wrap; }
.thumb {
  width: 80px; height: 80px;
  border-radius: 10px; overflow: hidden;
  border: 3px solid transparent;
  cursor: pointer; transition: 0.2s; background: #f8fafc;
}
.thumb img { width: 100%; height: 100%; object-fit: cover; }
.thumb:hover { border-color: #93c5fd; }
.thumb.active { border-color: #3b82f6; }

.info { padding: 10px 0; }
.info .cat-badge {
  display: inline-block; background: #dbeafe; color: #1e40af;
  padding: 5px 14px; border-radius: 20px;
  font-size: 13px; font-weight: 600; margin-bottom: 15px;
}
.info h1 {
  font-size: 28px; color: #1e3a8a; font-weight: 800;
  margin-bottom: 15px; line-height: 1.4;
}
.info .price-section {
  background: #f8fafc; padding: 20px;
  border-radius: 15px; margin-bottom: 20px;
}
.info .price {
  font-size: 32px; font-weight: 800; color: #16a34a;
  display: flex; align-items: center; gap: 10px; flex-wrap: wrap;
}
.info .price del { font-size: 20px; color: #94a3b8; font-weight: 400; }
.info .price .discount-tag {
  background: #dc2626; color: white;
  padding: 4px 12px; border-radius: 12px;
  font-size: 14px; font-weight: 700;
}
.info .stock-info {
  display: flex; gap: 8px; align-items: center;
  font-size: 14px; color: #166534;
  margin-top: 10px; font-weight: 600;
}
.info .stock-info.out { color: #dc2626; }

.details {
  background: #f8fafc; padding: 20px;
  border-radius: 15px; margin-bottom: 20px;
}
.details .row {
  display: flex; padding: 8px 0;
  border-bottom: 1px solid #e2e8f0; font-size: 15px;
}
.details .row:last-child { border-bottom: none; }
.details .row .label { font-weight: 600; color: #475569; min-width: 110px; }

.actions { display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 20px; }
.btn-cart, .btn-buy {
  flex: 1; min-width: 160px;
  padding: 18px 22px; border-radius: 15px;
  text-decoration: none; font-size: 16px; font-weight: 700;
  text-align: center;
  display: inline-flex; align-items: center; justify-content: center;
  gap: 8px; transition: 0.3s;
  border: none; cursor: pointer; font-family: inherit;
}
.btn-cart {
  background: linear-gradient(135deg, #f59e0b, #d97706);
  color: white;
  box-shadow: 0 6px 20px rgba(245,158,11,0.35);
}
.btn-cart:hover { transform: translateY(-3px); box-shadow: 0 10px 25px rgba(245,158,11,0.5); }
.btn-cart:disabled { background: #cbd5e1; cursor: not-allowed; box-shadow: none; transform: none; }
.btn-buy {
  background: linear-gradient(135deg, #16a34a, #15803d);
  color: white;
  box-shadow: 0 6px 20px rgba(22,163,74,0.4);
  font-size: 17px;
}
.btn-buy:hover { transform: translateY(-3px); box-shadow: 0 12px 30px rgba(22,163,74,0.55); }

.description-box {
  background: white; border-radius: 20px;
  padding: 30px; box-shadow: 0 4px 20px rgba(0,0,0,0.06);
  margin-bottom: 30px;
}
.description-box h2 {
  color: #1e3a8a; font-size: 22px; margin-bottom: 15px;
  padding-bottom: 12px; border-bottom: 2px solid #f1f5f9;
}
.description-box .content {
  font-size: 15px; color: #475569; line-height: 1.9; white-space: pre-wrap;
}

.related-box h2 { color: #1e3a8a; font-size: 22px; margin-bottom: 20px; font-weight: 700; }
.related-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: 18px;
}
.related-card {
  background: white; border-radius: 15px;
  overflow: hidden; text-decoration: none; color: #222;
  box-shadow: 0 4px 15px rgba(0,0,0,0.06);
  transition: 0.3s; border: 2px solid transparent; display: block;
}
.related-card:hover {
  transform: translateY(-5px); border-color: #3b82f6;
  box-shadow: 0 10px 25px rgba(59,130,246,0.15);
}
.related-card .r-img {
  height: 150px;
  background: linear-gradient(135deg, #dbeafe, #eff6ff);
  display: flex; align-items: center; justify-content: center;
  overflow: hidden;
}
.related-card .r-img img { width: 100%; height: 100%; object-fit: cover; }
.related-card .r-img .emoji { font-size: 55px; }
.related-card .r-body { padding: 12px 15px 15px; }
.related-card .r-body h4 {
  font-size: 14px; color: #1e3a8a; font-weight: 700;
  margin-bottom: 6px; min-height: 36px;
}
.related-card .r-body .r-price { font-size: 15px; font-weight: 700; color: #16a34a; }

footer {
  background: #0f172a; color: #cbd5e1;
  text-align: center; padding: 30px 20px; margin-top: 40px;
}
footer p { margin-bottom: 8px; }
footer a { color: #60a5fa; text-decoration: none; }

.floating-call {
  position: fixed; bottom: 25px; right: 25px;
  background: #16a34a; color: white;
  width: 60px; height: 60px; border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-size: 26px; text-decoration: none;
  box-shadow: 0 8px 25px rgba(22,163,74,0.5);
  z-index: 999;
}
.floating-call:hover { transform: scale(1.1); }

@media (max-width: 800px) {
  .product-main { grid-template-columns: 1fr; gap: 20px; padding: 20px; }
  .main-img { height: 300px; }
  .info h1 { font-size: 22px; }
  .info .price { font-size: 26px; }
}
@media (max-width: 600px) {
  header h1 { font-size: 16px; }
  header nav a { padding: 7px 12px; font-size: 13px; }
  .breadcrumb { font-size: 12px; padding: 12px 15px; }
  .btn-cart, .btn-buy { min-width: 100%; }
}
</style>
</head>
<body>

<header>
  <h1><a href="index.php" style="color:white;text-decoration:none;display:flex;align-items:center;gap:8px;">🏠 ময়মনসিংহ হোম সার্ভিস</a></h1>
  <nav>
    <a href="book.php" class="btn-order">📞 সার্ভিস অর্ডার করুন</a>
    <a href="work.php" class="btn-work">💼 কাজ করতে চাই</a>
    <a href="shop.php" class="btn-shop">🛒 কেনাকাটা করুন</a>
    <?php if ($cartCount > 0): ?>
      <a href="shop_cart.php" class="btn-cart">
        🛒 কার্ট<span class="cart-badge"><?= $cartCount ?></span>
      </a>
    <?php endif; ?>
  </nav>
</header>
<?php if ($marquee): ?>
<div style="background: linear-gradient(90deg, #fef3c7, #fde68a, #fef3c7); color: #92400e; padding: 12px 0; font-weight: 700; font-size: 15px; border-bottom: 2px solid #fbbf24; overflow: hidden;">
  <marquee behavior="scroll" direction="left" scrollamount="6" onmouseover="this.stop()" onmouseout="this.start()">
    📢 <?= htmlspecialchars($marquee['message']) ?>
  </marquee>
</div>
<?php endif; ?>
<?php if ($shopNotice): ?>
<div class="shop-notice">
  🛒 <strong><?= htmlspecialchars($shopNotice['title']) ?>:</strong> 
  <?= htmlspecialchars($shopNotice['message']) ?>
</div>
<?php endif; ?>

<?php if ($msg === 'added'): ?>
<div class="success-msg">
  ✅ প্রোডাক্ট কার্টে যোগ করা হয়েছে! <a href="shop_cart.php">কার্ট দেখুন →</a>
</div>
<?php endif; ?>

<div class="breadcrumb">
  <a href="shop.php">🛒 দোকান</a>
  <span>›</span>
  <a href="shop_category.php?id=<?= $product['category_id'] ?>">
    <?= htmlspecialchars(($product['cat_icon'] ?? '') . ' ' . $product['cat_name']) ?>
  </a>
  <span>›</span>
  <?= htmlspecialchars($product['name']) ?>
</div>

<div class="container">
  <div class="product-main">
    
    <div class="gallery">
      <div class="main-img">
        <?php if (count($images) > 0): ?>
          <img id="mainImg" src="<?= htmlspecialchars($images[0]) ?>" alt="">
        <?php else: ?>
          <span class="no-img">📦</span>
        <?php endif; ?>
        <?php if ($discountPct > 0): ?>
          <span class="discount-badge">-<?= $discountPct ?>% ছাড়</span>
        <?php endif; ?>
      </div>
      
      <?php if (count($images) > 1): ?>
        <div class="thumbnails">
          <?php foreach ($images as $i => $img): ?>
            <div class="thumb <?= $i == 0 ? 'active' : '' ?>" 
                 onclick="changeImg('<?= htmlspecialchars($img) ?>', this)">
              <img src="<?= htmlspecialchars($img) ?>" alt="">
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <div class="info">
      <?php if ($product['cat_name']): ?>
        <div class="cat-badge">
          <?= htmlspecialchars(($product['cat_icon'] ?? '') . ' ' . $product['cat_name']) ?>
          <?php if ($product['subcat_name']): ?>
            › <?= htmlspecialchars($product['subcat_name']) ?>
          <?php endif; ?>
        </div>
      <?php endif; ?>
      
      <h1><?= htmlspecialchars($product['name']) ?></h1>
      
      <div class="price-section">
        <div class="price">
          <?php if ($product['discount_price']): ?>
            <del>৳<?= number_format($product['price']) ?></del>
            ৳<?= number_format($product['discount_price']) ?>
            <?php if ($discountPct > 0): ?>
              <span class="discount-tag">-<?= $discountPct ?>%</span>
            <?php endif; ?>
          <?php else: ?>
            ৳<?= number_format($product['price']) ?>
          <?php endif; ?>
        </div>
        
        <?php if ($product['stock'] > 0): ?>
          <div class="stock-info">✅ স্টকে আছে (<?= $product['stock'] ?> টি)</div>
        <?php else: ?>
          <div class="stock-info out">❌ স্টক শেষ</div>
        <?php endif; ?>
      </div>

      <?php if ($product['size'] || $product['color'] || $product['brand']): ?>
        <div class="details">
          <?php if ($product['size']): ?>
            <div class="row">
              <span class="label">📏 সাইজ:</span>
              <span class="val"><?= htmlspecialchars($product['size']) ?></span>
            </div>
          <?php endif; ?>
          <?php if ($product['color']): ?>
            <div class="row">
              <span class="label">🎨 রঙ:</span>
              <span class="val"><?= htmlspecialchars($product['color']) ?></span>
            </div>
          <?php endif; ?>
          <?php if ($product['brand']): ?>
            <div class="row">
              <span class="label">🏷️ ব্র্যান্ড:</span>
              <span class="val"><?= htmlspecialchars($product['brand']) ?></span>
            </div>
          <?php endif; ?>
        </div>
      <?php endif; ?>

      <div class="actions">
        <?php if ($product['stock'] > 0): ?>
          <a href="cart_action.php?action=add&id=<?= $product['id'] ?>&redirect=product.php%3Fid%3D<?= $product['id'] ?>%26msg%3Dadded" 
             class="btn-cart">
            🛒 কার্টে যোগ করুন
          </a>
          <a href="shop_buy_now.php?id=<?= $product['id'] ?>" class="btn-buy">
            ⚡ এখনই অর্ডার করুন
          </a>
        <?php else: ?>
          <button class="btn-cart" disabled>❌ স্টক নেই</button>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <?php if ($product['description']): ?>
    <div class="description-box">
      <h2>📝 বিবরণ</h2>
      <div class="content"><?= nl2br(htmlspecialchars($product['description'])) ?></div>
    </div>
  <?php endif; ?>

  <?php if ($related->num_rows > 0): ?>
    <div class="related-box">
      <h2>🎁 আরো প্রোডাক্ট দেখুন</h2>
      <div class="related-grid">
        <?php while ($r = $related->fetch_assoc()): 
          $rImg = 'uploads/shop_products/' . $r['image'];
          $rHasImg = !empty($r['image']) && file_exists($rImg);
        ?>
          <a href="product.php?id=<?= $r['id'] ?>" class="related-card">
            <div class="r-img">
              <?php if ($rHasImg): ?>
                <img src="<?= htmlspecialchars($rImg) ?>" alt="">
              <?php else: ?>
                <span class="emoji">📦</span>
              <?php endif; ?>
            </div>
            <div class="r-body">
              <h4><?= htmlspecialchars($r['name']) ?></h4>
              <div class="r-price">
                <?php if ($r['discount_price']): ?>
                  ৳<?= number_format($r['discount_price']) ?>
                <?php else: ?>
                  ৳<?= number_format($r['price']) ?>
                <?php endif; ?>
              </div>
            </div>
          </a>
        <?php endwhile; ?>
      </div>
    </div>
  <?php endif; ?>
</div>

<footer>
  <p>📍 ময়মনসিংহ, বাংলাদেশ</p>
  <p>📞 হটলাইন: <a href="tel:+8801729560032">০১৭২৯-৫৬০০৩২</a></p>
  <p style="margin-top:15px;font-size:13px;color:#64748b;">© <?= date('Y') ?> ময়মনসিংহ হোম সার্ভিস</p>
</footer>

<a href="tel:+8801729560032" class="floating-call" title="কল করুন">📞</a>

<script>
function changeImg(src, el) {
    document.getElementById('mainImg').src = src;
    document.querySelectorAll('.thumb').forEach(t => t.classList.remove('active'));
    el.classList.add('active');
}
</script>

</body>
</html>