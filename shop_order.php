<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include 'db.php';

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
            'subtotal' => $subtotal
        ];
    }
}

if (count($cartItems) == 0) {
    header("Location: shop_cart.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="bn">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>অর্ডার কনফার্ম — কেনাকাটা</title>
<style>
* { margin:0; padding:0; box-sizing:border-box; font-family:'Segoe UI',sans-serif; }
body { background: #f5f7fa; }

header {
  background: linear-gradient(135deg, #1e3a8a, #3b82f6);
  color: white; padding: 15px 25px;
  display: flex; justify-content: space-between; align-items: center;
  flex-wrap: wrap; gap: 10px;
}
header h1 { font-size: 20px; }
header nav { display: flex; gap: 8px; flex-wrap: wrap; }
header nav a {
  color: white; text-decoration: none;
  padding: 9px 16px; background: rgba(255,255,255,0.15);
  border-radius: 25px; font-size: 14px;
}
header nav a.btn-shop { background: #8b5cf6; font-weight: 700; }
header nav a.btn-work { background: #16a34a; }
header nav a.btn-order { background: #fbbf24; color: #78350f; font-weight: 700; }

.container { max-width: 900px; margin: 30px auto; padding: 0 20px 60px; }
.page-title {
  color: #1e3a8a; font-size: 26px; font-weight: 800;
  margin-bottom: 25px;
}

.order-grid {
  display: grid;
  grid-template-columns: 1fr 340px;
  gap: 25px;
  align-items: start;
}
@media (max-width: 800px) {
  .order-grid { grid-template-columns: 1fr; }
}

.card {
  background: white;
  border-radius: 20px;
  padding: 25px;
  box-shadow: 0 4px 20px rgba(0,0,0,0.06);
  margin-bottom: 20px;
}
.card h3 {
  color: #1e3a8a;
  font-size: 18px;
  margin-bottom: 20px;
  padding-bottom: 12px;
  border-bottom: 2px solid #f1f5f9;
  font-weight: 700;
}

label {
  display: block;
  margin-bottom: 6px;
  font-weight: 600;
  color: #334155;
  font-size: 14px;
}
label .req { color: #dc2626; }

input, textarea {
  width: 100%;
  padding: 13px 15px;
  margin-bottom: 18px;
  border: 2px solid #e2e8f0;
  border-radius: 10px;
  font-size: 15px;
  font-family: inherit;
  background: white;
}
input:focus, textarea:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59,130,246,0.1);
}
textarea { min-height: 80px; resize: vertical; }

/* অর্ডার সামারি */
.summary-item {
  display: flex;
  justify-content: space-between;
  padding: 12px 0;
  border-bottom: 1px solid #f1f5f9;
  font-size: 14px;
  color: #475569;
  gap: 10px;
}
.summary-item .name { flex: 1; font-weight: 600; color: #1e3a8a; }
.summary-item .qty { color: #64748b; font-size: 13px; }
.summary-item .price { color: #16a34a; font-weight: 700; white-space: nowrap; }

.total-row {
  display: flex; justify-content: space-between;
  padding: 15px 0 0;
  border-top: 2px solid #1e3a8a;
  margin-top: 10px;
  font-size: 20px; font-weight: 800;
  color: #1e3a8a;
}
.total-row .amt { color: #16a34a; }

.submit-btn {
  width: 100%;
  background: linear-gradient(135deg, #16a34a, #15803d);
  color: white;
  padding: 18px;
  border: none;
  border-radius: 15px;
  font-size: 18px;
  font-weight: 700;
  cursor: pointer;
  font-family: inherit;
  margin-top: 20px;
  box-shadow: 0 6px 20px rgba(22,163,74,0.35);
  transition: 0.3s;
}
.submit-btn:hover {
  transform: translateY(-3px);
  box-shadow: 0 10px 30px rgba(22,163,74,0.5);
}

.payment-info {
  background: #fef3c7;
  color: #92400e;
  padding: 15px;
  border-radius: 12px;
  font-size: 13px;
  margin-top: 15px;
  line-height: 1.7;
  border-left: 4px solid #f59e0b;
}

footer {
  background: #0f172a;
  color: #cbd5e1;
  text-align: center;
  padding: 30px 20px;
  margin-top: 40px;
}
footer p { margin-bottom: 8px; }
footer a { color: #60a5fa; text-decoration: none; }

@media (max-width: 600px) {
  header h1 { font-size: 16px; }
  header nav a { padding: 7px 12px; font-size: 13px; }
  .page-title { font-size: 22px; }
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

<div class="container">
  <h2 class="page-title">📝 অর্ডার কনফার্ম করুন</h2>

  <form action="save_shop_order.php" method="POST">
    <div class="order-grid">
      
      <!-- বাম পাশ: কাস্টমার তথ্য -->
      <div>
        <div class="card">
          <h3>👤 আপনার তথ্য</h3>

          <label>পূর্ণ নাম <span class="req">*</span></label>
          <input type="text" name="customer_name" required placeholder="আপনার নাম">

          <label>মোবাইল নম্বর <span class="req">*</span></label>
          <input type="tel" name="phone" required placeholder="01XXXXXXXXX"
                 pattern="01[0-9]{9}">

          <label>সম্পূর্ণ ঠিকানা <span class="req">*</span></label>
          <textarea name="address" required placeholder="বাসা/রোড/এলাকা, থানা, জেলা"></textarea>

          <label>অতিরিক্ত নোট (ঐচ্ছিক)</label>
          <textarea name="note" placeholder="বিশেষ কিছু জানাতে চাইলে লিখুন"></textarea>
        </div>
      </div>

      <!-- ডান পাশ: অর্ডার সামারি -->
      <div>
        <div class="card">
          <h3>📦 অর্ডার সামারি</h3>

          <?php foreach ($cartItems as $it): ?>
            <div class="summary-item">
              <span class="name"><?= htmlspecialchars($it['name']) ?>
                <span class="qty">× <?= $it['qty'] ?></span>
              </span>
              <span class="price">৳<?= number_format($it['subtotal']) ?></span>
            </div>
          <?php endforeach; ?>

          <div class="total-row">
            <span>সর্বমোট</span>
            <span class="amt">৳<?= number_format($total) ?></span>
          </div>

          <button type="submit" class="submit-btn">
            ✅ অর্ডার কনফার্ম করুন
          </button>

          <div class="payment-info">
            💰 <b>পেমেন্ট:</b> ক্যাশ অন ডেলিভারি<br>
            📞 অর্ডার কনফার্ম করতে আমরা কল করব
          </div>
        </div>
      </div>

    </div>
  </form>
</div>

<footer>
  <p>📍 ময়মনসিংহ, বাংলাদেশ</p>
  <p>📞 হটলাইন: <a href="tel:+8801729560032">০১৭২৯-৫৬০০৩২</a></p>
  <p style="margin-top:15px;font-size:13px;color:#64748b;">© <?= date('Y') ?> ময়মনসিংহ হোম সার্ভিস</p>
</footer>

</body>
</html>