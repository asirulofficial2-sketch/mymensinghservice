<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'db.php';
$marquee = null;
$marqueeResult = $conn->query("SELECT * FROM marquee WHERE active=1 LIMIT 1");
if ($marqueeResult && $marqueeResult->num_rows > 0) {
    $marquee = $marqueeResult->fetch_assoc();
}
$cart = $_SESSION['shop_cart'] ?? [];
$cartItems = [];
$total = 0;

if (!empty($cart)) {
    $ids = array_keys($cart);
    $idList = implode(',', array_map('intval', $ids));
    $result = $conn->query("SELECT * FROM products WHERE id IN ($idList) AND active=1");
    
    while ($p = $result->fetch_assoc()) {
        $qty = $cart[$p['id']] ?? 1;
        $price = $p['discount_price'] ?: $p['price'];
        $subtotal = $price * $qty;
        $total += $subtotal;
        
        $cartItems[] = [
            'id' => $p['id'],
            'name' => $p['name'],
            'image' => $p['image'],
            'price' => $price,
            'qty' => $qty,
            'subtotal' => $subtotal,
            'size' => $p['size'] ?? '',
            'color' => $p['color'] ?? ''
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="bn">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>কার্ট — কেনাকাটা</title>
<style>
* { margin:0; padding:0; box-sizing:border-box; font-family:'Segoe UI',sans-serif; }
body { background: #f5f7fa; }

header {
  background: linear-gradient(135deg, #1e3a8a, #3b82f6);
  color: white; padding: 15px 25px;
  display: flex; justify-content: space-between; align-items: center;
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
header nav a.btn-shop:hover { background: #7c3aed; }
header nav a.btn-work { background: #16a34a; }
header nav a.btn-work:hover { background: #15803d; }
header nav a.btn-order { background: #fbbf24; color: #78350f; font-weight: 700; }
header nav a.btn-order:hover { background: #f59e0b; }

.container { max-width: 1000px; margin: 30px auto; padding: 0 20px 60px; }

.page-title {
  display: flex; justify-content: space-between; align-items: center;
  flex-wrap: wrap; gap: 10px;
  margin-bottom: 25px;
}
.page-title h2 { color: #1e3a8a; font-size: 26px; font-weight: 800; }
.page-title .count {
  background: #dbeafe; color: #1e40af;
  padding: 6px 14px; border-radius: 20px;
  font-weight: 700; font-size: 14px;
}

.cart-grid {
  display: grid; gap: 15px;
  margin-bottom: 25px;
}
.cart-item {
  background: white;
  border-radius: 15px;
  padding: 18px;
  box-shadow: 0 3px 12px rgba(0,0,0,0.06);
  display: grid;
  grid-template-columns: 100px 1fr auto;
  gap: 18px;
  align-items: center;
}
.cart-item .img-box {
  width: 100px; height: 100px;
  background: linear-gradient(135deg, #dbeafe, #eff6ff);
  border-radius: 12px;
  display: flex; align-items: center; justify-content: center;
  overflow: hidden;
  border: 2px solid #e2e8f0;
}
.cart-item .img-box img {
  width: 100%; height: 100%; object-fit: cover;
}
.cart-item .img-box .emoji { font-size: 45px; }

.cart-item .info h3 {
  color: #1e3a8a; font-size: 17px; font-weight: 700;
  margin-bottom: 6px;
}
.cart-item .info .meta {
  font-size: 12px; color: #64748b;
  margin-bottom: 8px;
}
.cart-item .info .meta span {
  background: #f1f5f9; padding: 3px 10px;
  border-radius: 10px; margin-right: 6px;
}
.cart-item .info .price {
  color: #16a34a; font-size: 18px; font-weight: 800;
}

.cart-item .controls {
  display: flex; flex-direction: column; align-items: flex-end; gap: 10px;
}

.qty-control {
  display: flex; align-items: center; gap: 0;
  border: 2px solid #e2e8f0;
  border-radius: 10px;
  overflow: hidden;
  background: white;
}
.qty-control a {
  padding: 8px 14px;
  text-decoration: none;
  color: #1e40af;
  font-size: 18px;
  font-weight: 700;
  background: #f8fafc;
  transition: 0.2s;
}
.qty-control a:hover { background: #dbeafe; }
.qty-control input {
  width: 50px; border: none; text-align: center;
  font-size: 15px; font-weight: 700; color: #1e3a8a;
  font-family: inherit;
  padding: 8px 0;
}
.qty-control input:focus { outline: none; }

.item-subtotal {
  font-size: 16px; font-weight: 800; color: #16a34a;
}

.remove-btn {
  background: #fee2e2; color: #dc2626;
  padding: 6px 12px; border-radius: 8px;
  font-size: 12px; font-weight: 700;
  text-decoration: none;
  transition: 0.2s;
}
.remove-btn:hover { background: #fecaca; }

.summary-box {
  background: white;
  border-radius: 20px;
  padding: 25px;
  box-shadow: 0 4px 20px rgba(0,0,0,0.08);
}
.summary-box .row {
  display: flex; justify-content: space-between;
  padding: 12px 0;
  border-bottom: 1px solid #f1f5f9;
  font-size: 15px; color: #475569;
}
.summary-box .row.total {
  border-bottom: none;
  border-top: 2px solid #1e3a8a;
  margin-top: 8px;
  padding-top: 18px;
  font-size: 22px; font-weight: 800;
  color: #1e3a8a;
}
.summary-box .row.total span:last-child { color: #16a34a; }

.checkout-btn {
  display: block;
  width: 100%;
  background: linear-gradient(135deg, #16a34a, #15803d);
  color: white;
  padding: 18px;
  border-radius: 15px;
  text-decoration: none;
  font-size: 18px; font-weight: 700;
  text-align: center;
  margin-top: 20px;
  box-shadow: 0 6px 20px rgba(22,163,74,0.35);
  transition: 0.3s;
}
.checkout-btn:hover {
  transform: translateY(-3px);
  box-shadow: 0 10px 30px rgba(22,163,74,0.5);
}

.continue-btn {
  display: block;
  width: 100%;
  background: #e2e8f0;
  color: #334155;
  padding: 14px;
  border-radius: 15px;
  text-decoration: none;
  font-size: 15px; font-weight: 600;
  text-align: center;
  margin-top: 10px;
  transition: 0.2s;
}
.continue-btn:hover { background: #cbd5e1; }

.empty-cart {
  background: white;
  padding: 70px 30px;
  border-radius: 25px;
  text-align: center;
  box-shadow: 0 4px 20px rgba(0,0,0,0.06);
}
.empty-cart .icon {
  font-size: 100px; display: block;
  margin-bottom: 20px;
  opacity: 0.4;
}
.empty-cart h3 {
  color: #1e3a8a; font-size: 24px;
  margin-bottom: 10px;
}
.empty-cart p {
  color: #64748b;
  margin-bottom: 25px;
  font-size: 15px;
}
.empty-cart a {
  display: inline-block;
  background: linear-gradient(135deg, #2563eb, #1e40af);
  color: white;
  padding: 14px 35px;
  border-radius: 30px;
  text-decoration: none;
  font-size: 16px; font-weight: 700;
  box-shadow: 0 6px 20px rgba(37,99,235,0.35);
  transition: 0.3s;
}
.empty-cart a:hover { transform: translateY(-3px); }

footer {
  background: #0f172a;
  color: #cbd5e1;
  text-align: center;
  padding: 30px 20px;
  margin-top: 40px;
}
footer p { margin-bottom: 8px; }
footer a { color: #60a5fa; text-decoration: none; }

@media (max-width: 700px) {
  .cart-item {
    grid-template-columns: 80px 1fr;
    gap: 12px;
  }
  .cart-item .img-box { width: 80px; height: 80px; }
  .cart-item .controls {
    grid-column: 1/-1;
    flex-direction: row;
    justify-content: space-between;
    align-items: center;
    width: 100%;
  }
  .page-title h2 { font-size: 22px; }
  header h1 { font-size: 16px; }
  header nav a { padding: 7px 12px; font-size: 13px; }
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
  </nav>
</header>
<?php if ($marquee): ?>
<div style="background: linear-gradient(90deg, #fef3c7, #fde68a, #fef3c7); color: #92400e; padding: 12px 0; font-weight: 700; font-size: 15px; border-bottom: 2px solid #fbbf24; overflow: hidden;">
  <marquee behavior="scroll" direction="left" scrollamount="6" onmouseover="this.stop()" onmouseout="this.start()">
    📢 <?= htmlspecialchars($marquee['message']) ?>
  </marquee>
</div>
<?php endif; ?>
<div class="container">

  <div class="page-title">
    <h2>🛒 আপনার কার্ট</h2>
    <?php if (count($cartItems) > 0): ?>
      <span class="count"><?= count($cartItems) ?> টি প্রোডাক্ট</span>
    <?php endif; ?>
  </div>

  <?php if (count($cartItems) > 0): ?>
    
    <div class="cart-grid">
      <?php foreach ($cartItems as $it): 
        $imgPath = 'uploads/shop_products/' . $it['image'];
        $hasImg = !empty($it['image']) && file_exists($imgPath);
      ?>
        <div class="cart-item">
          <div class="img-box">
            <?php if ($hasImg): ?>
              <img src="<?= htmlspecialchars($imgPath) ?>" alt="">
            <?php else: ?>
              <span class="emoji">📦</span>
            <?php endif; ?>
          </div>

          <div class="info">
            <h3><?= htmlspecialchars($it['name']) ?></h3>
            <div class="meta">
              <?php if ($it['size']): ?><span>📏 <?= htmlspecialchars($it['size']) ?></span><?php endif; ?>
              <?php if ($it['color']): ?><span>🎨 <?= htmlspecialchars($it['color']) ?></span><?php endif; ?>
            </div>
            <div class="price">৳<?= number_format($it['price']) ?></div>
          </div>

          <div class="controls">
            <div class="qty-control">
              <a href="cart_action.php?action=minus&id=<?= $it['id'] ?>">−</a>
              <input type="text" value="<?= $it['qty'] ?>" readonly>
              <a href="cart_action.php?action=plus&id=<?= $it['id'] ?>">+</a>
            </div>
            <div class="item-subtotal">৳<?= number_format($it['subtotal']) ?></div>
            <a href="cart_action.php?action=remove&id=<?= $it['id'] ?>" 
               class="remove-btn"
               onclick="return confirm('প্রোডাক্টটি কার্ট থেকে মুছে ফেলবেন?')">
              🗑 সরান
            </a>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="summary-box">
      <div class="row">
        <span>সাব-টোটাল</span>
        <span>৳<?= number_format($total) ?></span>
      </div>
      <div class="row">
        <span>ডেলিভারি চার্জ</span>
        <span style="color:#16a34a;">ফ্রি</span>
      </div>
      <div class="row total">
        <span>সর্বমোট</span>
        <span>৳<?= number_format($total) ?></span>
      </div>

      <a href="shop_order.php" class="checkout-btn">
        ✅ অর্ডার কনফার্ম করুন
      </a>
      <a href="shop.php" class="continue-btn">
        ← আরো কেনাকাটা করুন
      </a>
    </div>

  <?php else: ?>
    
    <div class="empty-cart">
      <span class="icon">🛒</span>
      <h3>আপনার কার্ট খালি</h3>
      <p>এখনো কোনো প্রোডাক্ট কার্টে যোগ করেননি</p>
      <a href="shop.php">🛍️ এখনই কেনাকাটা করুন</a>
    </div>

  <?php endif; ?>

</div>

<footer>
  <p>📍 ময়মনসিংহ, বাংলাদেশ</p>
  <p>📞 হটলাইন: <a href="tel:+8801729560032">০১৭২৯-৫৬০০৩২</a></p>
  <p style="margin-top:15px;font-size:13px;color:#64748b;">© <?= date('Y') ?> ময়মনসিংহ হোম সার্ভিস</p>
</footer>

</body>
</html>