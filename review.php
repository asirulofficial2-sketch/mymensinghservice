<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'db.php';

$type = $_GET['type'] ?? '';
$id = intval($_GET['id'] ?? 0);

if (!in_array($type, ['service', 'product']) || !$id) {
    die("ভুল লিংক। <a href='index.php'>হোমে যান</a>");
}

$success = '';
$data = null;
$alreadyReviewed = false;

if ($type === 'service') {
    $stmt = $conn->prepare("SELECT b.*, s.name as service_name, s.icon as service_icon 
                            FROM bookings b 
                            LEFT JOIN services s ON b.service_id = s.id 
                            WHERE b.id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $data = $stmt->get_result()->fetch_assoc();
    if (!$data) die("বুকিং পাওয়া যায়নি।");
    
    $check = $conn->prepare("SELECT id FROM service_reviews WHERE booking_id = ?");
    $check->bind_param("i", $id);
    $check->execute();
    if ($check->get_result()->num_rows > 0) $alreadyReviewed = true;
} else {
    $stmt = $conn->prepare("SELECT * FROM shop_orders WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $data = $stmt->get_result()->fetch_assoc();
    if (!$data) die("অর্ডার পাওয়া যায়নি।");
    
    $check = $conn->prepare("SELECT id FROM product_reviews WHERE order_id = ?");
    $check->bind_param("i", $id);
    $check->execute();
    if ($check->get_result()->num_rows > 0) $alreadyReviewed = true;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$alreadyReviewed) {
    $rating = intval($_POST['rating'] ?? 5);
    $review = trim($_POST['review'] ?? '');
    $name = trim($_POST['customer_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    
    if ($rating >= 1 && $rating <= 5) {
        if ($type === 'service') {
            $stmt = $conn->prepare("INSERT INTO service_reviews (booking_id, customer_name, phone, rating, review) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("issis", $id, $name, $phone, $rating, $review);
            $stmt->execute();
            $conn->query("UPDATE bookings SET reviewed=1 WHERE id=$id");
        } else {
            $items = json_decode($data['items'], true);
            $productId = $items[0]['id'] ?? 0;
            $stmt = $conn->prepare("INSERT INTO product_reviews (product_id, order_id, customer_name, phone, rating, review) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("iissis", $productId, $id, $name, $phone, $rating, $review);
            $stmt->execute();
            $conn->query("UPDATE shop_orders SET reviewed=1 WHERE id=$id");
        }
        $success = "ধন্যবাদ! আপনার রিভিউ জমা হয়েছে।";
        $alreadyReviewed = true;
    }
}
?>
<!DOCTYPE html>
<html lang="bn">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>রিভিউ দিন</title>
<style>
* { margin:0; padding:0; box-sizing:border-box; font-family:'Segoe UI',sans-serif; }
body {
  min-height: 100vh; background: linear-gradient(135deg, #dbeafe, #eff6ff);
  padding: 30px 20px; display: flex; align-items: center; justify-content: center;
}
.card {
  background: white; padding: 40px 30px; border-radius: 25px;
  max-width: 550px; width: 100%; box-shadow: 0 15px 50px rgba(0,0,0,0.1);
  text-align: center;
}
.icon { font-size: 70px; display: block; margin-bottom: 15px; }
h1 { color: #1e3a8a; font-size: 24px; margin-bottom: 10px; }
.subtitle { color: #64748b; font-size: 15px; margin-bottom: 25px; }
.service-info {
  background: linear-gradient(135deg, #dbeafe, #eff6ff);
  padding: 20px; border-radius: 15px; margin-bottom: 25px;
  border: 2px solid #3b82f6;
}
.service-info .s-icon { font-size: 45px; display: block; margin-bottom: 10px; }
.service-info h2 { color: #1e3a8a; font-size: 20px; }
.success-box {
  background: #dcfce7; border: 2px solid #16a34a;
  color: #166534; padding: 25px; border-radius: 15px;
  font-size: 16px; line-height: 1.7;
}
.success-box .big { font-size: 60px; display: block; margin-bottom: 15px; }
label { display: block; text-align: left; margin-bottom: 8px; font-weight: 600; color: #334155; font-size: 14px; }
input, textarea {
  width: 100%; padding: 13px 15px; border: 2px solid #e2e8f0;
  border-radius: 10px; font-size: 15px; font-family: inherit; margin-bottom: 18px;
}
input:focus, textarea:focus { outline: none; border-color: #3b82f6; }
textarea { min-height: 100px; resize: vertical; }
.stars {
  display: flex; gap: 10px; justify-content: center;
  margin: 15px 0 25px; font-size: 45px; cursor: pointer;
}
.star { color: #cbd5e1; transition: 0.2s; user-select: none; }
.star.active { color: #fbbf24; transform: scale(1.15); }
.btn {
  width: 100%; padding: 15px; background: linear-gradient(135deg, #16a34a, #15803d);
  color: white; border: none; border-radius: 12px; font-size: 17px;
  font-weight: 700; cursor: pointer; font-family: inherit;
}
.btn-home {
  display: inline-block; margin-top: 15px; color: #2563eb;
  text-decoration: none; font-weight: 600;
}
</style>
</head>
<body>
<div class="card">

<?php if ($success): ?>
  <div class="success-box">
    <span class="big">🎉</span>
    <h2 style="margin-bottom:10px;">ধন্যবাদ!</h2>
    <p><?= htmlspecialchars($success) ?></p>
    <a href="reviews.php" class="btn-home">সব রিভিউ দেখুন →</a>
  </div>
<?php elseif ($alreadyReviewed): ?>
  <span class="icon">✅</span>
  <h1>ইতিমধ্যে রিভিউ দিয়েছেন</h1>
  <p class="subtitle">আপনার রিভিউ আমাদের কাছে পৌঁছেছে। ধন্যবাদ!</p>
  <a href="reviews.php" class="btn-home">সব রিভিউ দেখুন →</a>
<?php else: ?>
  <span class="icon">⭐</span>
  <h1>আপনার মতামত দিন</h1>
  <p class="subtitle">আপনার রিভিউ আমাদের আরো ভালো হতে সাহায্য করবে</p>

  <?php if ($type === 'service'): ?>
    <div class="service-info">
      <span class="s-icon"><?= htmlspecialchars($data['service_icon'] ?? '🔧') ?></span>
      <h2><?= htmlspecialchars($data['service_name'] ?? 'সার্ভিস') ?></h2>
    </div>
  <?php else: ?>
    <div class="service-info">
      <span class="s-icon">🛒</span>
      <h2>অর্ডার #<?= $data['id'] ?></h2>
      <p>মোট: ৳<?= number_format($data['total']) ?></p>
    </div>
  <?php endif; ?>

  <form method="POST">
    <label>রেটিং দিন *</label>
    <input type="hidden" name="rating" id="ratingInput" value="5">
    <div class="stars" id="stars">
      <span class="star active" data-value="1">★</span>
      <span class="star active" data-value="2">★</span>
      <span class="star active" data-value="3">★</span>
      <span class="star active" data-value="4">★</span>
      <span class="star active" data-value="5">★</span>
    </div>

    <label>আপনার নাম (ঐচ্ছিক)</label>
    <input type="text" name="customer_name" value="<?= htmlspecialchars($data['customer_name'] ?? '') ?>">

    <label>মোবাইল (ঐচ্ছিক)</label>
    <input type="tel" name="phone" value="<?= htmlspecialchars($data['phone'] ?? '') ?>">

    <label>মন্তব্য (ঐচ্ছিক)</label>
    <textarea name="review" placeholder="আপনার অভিজ্ঞতা লিখুন..."></textarea>

    <button type="submit" class="btn">✅ রিভিউ জমা দিন</button>
  </form>
<?php endif; ?>

</div>

<script>
document.querySelectorAll('.star').forEach(star => {
    star.addEventListener('click', function() {
        const value = this.dataset.value;
        document.getElementById('ratingInput').value = value;
        document.querySelectorAll('.star').forEach(s => {
            if (s.dataset.value <= value) s.classList.add('active');
            else s.classList.remove('active');
        });
    });
});
</script>

</body>
</html>