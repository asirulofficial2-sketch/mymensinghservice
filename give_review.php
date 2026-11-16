<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'db.php';

$phone = trim($_GET['phone'] ?? '');
$booking_id = intval($_GET['booking'] ?? 0);
$order_id = intval($_GET['order'] ?? 0);
$success = '';

// ============ সার্ভিস বুকিং থেকে রিভিউ ============
if ($booking_id && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $rating = intval($_POST['rating'] ?? 5);
    $review = trim($_POST['review'] ?? '');
    
    $check = $conn->prepare("SELECT id FROM service_reviews WHERE booking_id = ?");
    $check->bind_param("i", $booking_id);
    $check->execute();
    
    if ($check->get_result()->num_rows == 0 && $rating >= 1 && $rating <= 5) {
        $b = $conn->query("SELECT * FROM bookings WHERE id=$booking_id")->fetch_assoc();
        $stmt = $conn->prepare("INSERT INTO service_reviews (booking_id, customer_name, phone, rating, review) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issis", $booking_id, $b['customer_name'], $b['phone'], $rating, $review);
        $stmt->execute();
        $conn->query("UPDATE bookings SET reviewed=1 WHERE id=$booking_id");
        $success = "ধন্যবাদ! আপনার রিভিউ জমা হয়েছে।";
    }
}

// ============ শপ অর্ডার থেকে রিভিউ ============
if ($order_id && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $rating = intval($_POST['rating'] ?? 5);
    $review = trim($_POST['review'] ?? '');
    
    $check = $conn->prepare("SELECT id FROM product_reviews WHERE order_id = ?");
    $check->bind_param("i", $order_id);
    $check->execute();
    
    if ($check->get_result()->num_rows == 0 && $rating >= 1 && $rating <= 5) {
        $o = $conn->query("SELECT * FROM shop_orders WHERE id=$order_id")->fetch_assoc();
        $items = json_decode($o['items'], true);
        $productId = $items[0]['id'] ?? 0;
        $stmt = $conn->prepare("INSERT INTO product_reviews (product_id, order_id, customer_name, phone, rating, review) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iissis", $productId, $order_id, $o['customer_name'], $o['phone'], $rating, $review);
        $stmt->execute();
        $conn->query("UPDATE shop_orders SET reviewed=1 WHERE id=$order_id");
        $success = "ধন্যবাদ! আপনার রিভিউ জমা হয়েছে।";
    }
}

$myBookings = null;
$myOrders = null;
$searched = false;

if ($phone && !$booking_id && !$order_id) {
    $searched = true;
    
    $stmt = $conn->prepare("SELECT b.*, s.name as service_name, s.icon as service_icon 
                            FROM bookings b 
                            LEFT JOIN services s ON b.service_id = s.id 
                            WHERE b.phone = ? AND b.status = 'completed' 
                            ORDER BY b.id DESC");
    $stmt->bind_param("s", $phone);
    $stmt->execute();
    $myBookings = $stmt->get_result();
    
    $stmt = $conn->prepare("SELECT * FROM shop_orders WHERE phone = ? AND status = 'delivered' ORDER BY id DESC");
    $stmt->bind_param("s", $phone);
    $stmt->execute();
    $myOrders = $stmt->get_result();
}

$showBooking = null;
$showOrder = null;

if ($booking_id) {
    $showBooking = $conn->query("SELECT b.*, s.name as service_name, s.icon as service_icon FROM bookings b LEFT JOIN services s ON b.service_id = s.id WHERE b.id=$booking_id")->fetch_assoc();
}
if ($order_id) {
    $showOrder = $conn->query("SELECT * FROM shop_orders WHERE id=$order_id")->fetch_assoc();
}
?>
<!DOCTYPE html>
<html lang="bn">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>রিভিউ দিন — ময়মনসিংহ সার্ভিস হাব</title>
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
header nav a.btn-home { background: #ef4444; font-weight: 700; }

.hero {
  text-align: center; padding: 45px 20px 30px;
  background: linear-gradient(135deg, #fef3c7, #fde68a);
}
.hero h2 { font-size: 30px; color: #92400e; margin-bottom: 12px; font-weight: 800; }
.hero p { font-size: 15px; color: #78350f; max-width: 600px; margin: 0 auto; }

.container { max-width: 700px; margin: 30px auto; padding: 0 20px 60px; }
.card {
  background: white; padding: 35px 30px; border-radius: 20px;
  box-shadow: 0 4px 20px rgba(0,0,0,0.08); margin-bottom: 20px;
}
.card h3 { color: #1e3a8a; margin-bottom: 20px; font-size: 20px; text-align: center; }
.card h4 { color: #1e3a8a; margin: 20px 0 12px; font-size: 16px; }

label { display: block; margin-bottom: 8px; font-weight: 600; color: #334155; font-size: 14px; }
input[type=text], input[type=tel], textarea {
  width: 100%; padding: 14px 16px;
  border: 2px solid #e2e8f0; border-radius: 10px;
  font-size: 15px; font-family: inherit; margin-bottom: 18px;
}
input:focus, textarea:focus { outline: none; border-color: #3b82f6; }
textarea { min-height: 100px; resize: vertical; }

.btn {
  width: 100%; padding: 15px;
  background: linear-gradient(135deg, #2563eb, #1e40af);
  color: white; border: none; border-radius: 12px;
  font-size: 17px; font-weight: 700; cursor: pointer;
  font-family: inherit; margin-bottom: 10px;
}
.btn:hover { transform: translateY(-2px); }
.btn-green { background: linear-gradient(135deg, #16a34a, #15803d); }
.btn-home-red { 
  background: linear-gradient(135deg, #ef4444, #dc2626); 
  text-decoration: none; display: block; text-align: center;
}
.btn-review {
  display: inline-block; background: linear-gradient(135deg, #f59e0b, #d97706);
  color: white; text-decoration: none;
  padding: 12px 24px; border-radius: 10px;
  font-weight: 700; font-size: 14px;
}

.info-box {
  background: #eff6ff; border-left: 4px solid #3b82f6;
  padding: 15px 18px; border-radius: 10px;
  font-size: 14px; color: #1e40af;
  margin-bottom: 20px; line-height: 1.7;
}

.item-card {
  background: #f8fafc; padding: 18px; border-radius: 12px;
  margin-bottom: 15px; border-left: 4px solid #fbbf24;
  display: flex; justify-content: space-between;
  align-items: center; flex-wrap: wrap; gap: 12px;
}
.item-card .info h4 { margin: 0 0 5px; font-size: 16px; color: #1e3a8a; }
.item-card .info p { font-size: 13px; color: #64748b; }
.item-card .badge {
  display: inline-block; background: #dcfce7; color: #16a34a;
  padding: 4px 12px; border-radius: 20px;
  font-size: 12px; font-weight: 700; margin-top: 5px;
}
.item-card .badge.reviewed { background: #dbeafe; color: #1e40af; }
.item-card .badge.pending { background: #fef3c7; color: #d97706; }

.stars {
  display: flex; gap: 10px; justify-content: center;
  margin: 15px 0 25px; font-size: 50px; cursor: pointer;
}
.star { color: #cbd5e1; transition: 0.2s; user-select: none; }
.star:hover, .star.active { color: #fbbf24; transform: scale(1.15); }

.success-box {
  background: #dcfce7; border: 2px solid #16a34a;
  color: #166534; padding: 30px;
  border-radius: 15px; text-align: center;
}
.success-box .big { font-size: 70px; display: block; margin-bottom: 15px; }
.success-box h3 { color: #166534; font-size: 22px; margin-bottom: 10px; }
.success-box a {
  display: inline-block; margin-top: 15px;
  color: #2563eb; text-decoration: none; font-weight: 700;
}

.service-preview {
  background: linear-gradient(135deg, #dbeafe, #eff6ff);
  padding: 20px; border-radius: 15px;
  text-align: center; margin-bottom: 25px;
  border: 2px solid #3b82f6;
}
.service-preview .icon { font-size: 55px; display: block; margin-bottom: 10px; }
.service-preview h2 { color: #1e3a8a; font-size: 22px; margin-bottom: 5px; }

.empty-msg {
  text-align: center; padding: 40px 20px;
  background: #fef3c7; border-radius: 15px;
  color: #92400e; font-size: 15px; line-height: 1.8;
}

footer { background: #0f172a; color: #cbd5e1; text-align: center; padding: 30px 20px; }
footer p { margin-bottom: 8px; }
footer a { color: #60a5fa; text-decoration: none; }

@media (max-width: 600px) {
  header h1 { font-size: 16px; }
  header nav a { padding: 7px 12px; font-size: 13px; }
  .hero h2 { font-size: 24px; }
  .stars { font-size: 40px; gap: 5px; }
}
</style>
</head>
<body>

<header>
  <h1>🌟 ময়মনসিংহ সার্ভিস হাব</h1>
  <nav>
    <a href="book.php" class="btn-order">📞 সার্ভিস অর্ডার</a>
    <a href="work.php" class="btn-work">💼 কাজ করতে চাই</a>
    <a href="shop.php" class="btn-shop">🛒 কেনাকাটা</a>
    <a href="index.php" class="btn-home">🏠 হোম</a>
  </nav>
</header>

<div class="hero">
  <h2>⭐ রিভিউ দিন</h2>
  <p>আপনার মতামত আমাদের আরো ভালো হতে সাহায্য করবে</p>
</div>

<div class="container">

<?php if ($success): ?>
  <div class="card">
    <div class="success-box">
      <span class="big">🎉</span>
      <h3>ধন্যবাদ!</h3>
      <p><?= htmlspecialchars($success) ?></p>
      <a href="reviews.php">সব রিভিউ দেখুন →</a>
    </div>
    <!-- হোম পেজ বাটন -->
    <a href="index.php" class="btn btn-home-red" style="margin-top:20px;">🏠 হোম পেজে ফিরে যান</a>
  </div>

<?php elseif ($showBooking || $showOrder): ?>
  <div class="card">
    
    <?php if ($showBooking): ?>
      <?php 
        $alreadyReviewed = $conn->query("SELECT id FROM service_reviews WHERE booking_id=$booking_id")->num_rows > 0;
      ?>
      
      <?php if ($alreadyReviewed): ?>
        <div class="success-box">
          <span class="big">✅</span>
          <h3>ইতিমধ্যে রিভিউ দিয়েছেন</h3>
          <a href="reviews.php">সব রিভিউ দেখুন →</a>
        </div>
        <a href="index.php" class="btn btn-home-red" style="margin-top:20px;">🏠 হোম পেজে ফিরে যান</a>
      <?php else: ?>
        <div class="service-preview">
          <span class="icon"><?= htmlspecialchars($showBooking['service_icon'] ?? '🔧') ?></span>
          <h2><?= htmlspecialchars($showBooking['service_name'] ?? 'সার্ভিস') ?></h2>
          <p style="color:#64748b; font-size:14px;"><?= htmlspecialchars($showBooking['problem'] ?? '') ?></p>
        </div>
        
        <form method="POST">
          <label style="text-align:center; font-size:16px; margin-bottom:12px;">
            আপনার রেটিং কেমন?
          </label>
          <input type="hidden" name="rating" id="ratingInput" value="5">
          <div class="stars" id="stars">
            <span class="star active" data-value="1">★</span>
            <span class="star active" data-value="2">★</span>
            <span class="star active" data-value="3">★</span>
            <span class="star active" data-value="4">★</span>
            <span class="star active" data-value="5">★</span>
          </div>
          
          <label>মন্তব্য (ঐচ্ছিক)</label>
          <textarea name="review" placeholder="আপনার অভিজ্ঞতা লিখুন..."></textarea>
          
          <button type="submit" class="btn btn-green">✅ রিভিউ জমা দিন</button>
        </form>
        <a href="index.php" class="btn btn-home-red">🏠 হোম পেজে ফিরে যান</a>
      <?php endif; ?>

    <?php elseif ($showOrder): ?>
      <?php 
        $alreadyReviewed = $conn->query("SELECT id FROM product_reviews WHERE order_id=$order_id")->num_rows > 0;
      ?>
      
      <?php if ($alreadyReviewed): ?>
        <div class="success-box">
          <span class="big">✅</span>
          <h3>ইতিমধ্যে রিভিউ দিয়েছেন</h3>
          <a href="reviews.php">সব রিভিউ দেখুন →</a>
        </div>
        <a href="index.php" class="btn btn-home-red" style="margin-top:20px;">🏠 হোম পেজে ফিরে যান</a>
      <?php else: ?>
        <div class="service-preview">
          <span class="icon">🛒</span>
          <h2>অর্ডার #<?= $showOrder['id'] ?></h2>
          <p style="color:#64748b; font-size:14px;">মোট: ৳<?= number_format($showOrder['total']) ?></p>
        </div>
        
        <form method="POST">
          <label style="text-align:center; font-size:16px; margin-bottom:12px;">
            প্রোডাক্ট কেমন লেগেছে?
          </label>
          <input type="hidden" name="rating" id="ratingInput" value="5">
          <div class="stars" id="stars">
            <span class="star active" data-value="1">★</span>
            <span class="star active" data-value="2">★</span>
            <span class="star active" data-value="3">★</span>
            <span class="star active" data-value="4">★</span>
            <span class="star active" data-value="5">★</span>
          </div>
          
          <label>মন্তব্য (ঐচ্ছিক)</label>
          <textarea name="review" placeholder="আপনার অভিজ্ঞতা লিখুন..."></textarea>
          
          <button type="submit" class="btn btn-green">✅ রিভিউ জমা দিন</button>
        </form>
        <a href="index.php" class="btn btn-home-red">🏠 হোম পেজে ফিরে যান</a>
      <?php endif; ?>

    <?php endif; ?>
  </div>

<?php else: ?>
  <div class="card">
    <h3>📱 আপনার মোবাইল নম্বর দিন</h3>
    
    <div class="info-box">
      💡 <b>যে নম্বর দিয়ে সার্ভিস বুক বা কেনাকাটা করেছিলেন</b> — সেটাই দিন।
    </div>
    
    <form method="GET">
      <label>মোবাইল নম্বর *</label>
      <input type="tel" name="phone" required 
             placeholder="01XXXXXXXXX" pattern="01[0-9]{9}"
             value="<?= htmlspecialchars($phone) ?>">
      
      <button type="submit" class="btn">🔍 খুঁজুন</button>
    </form>
    <a href="index.php" class="btn btn-home-red" style="margin-top:10px;">🏠 হোম পেজে ফিরে যান</a>
  </div>

  <?php if ($searched): ?>
    <div class="card">
      <h3>📋 আপনার কাজের তালিকা</h3>
      
      <?php if (($myBookings && $myBookings->num_rows > 0) || ($myOrders && $myOrders->num_rows > 0)): ?>
        
        <?php if ($myBookings && $myBookings->num_rows > 0): ?>
          <h4>🔧 সার্ভিস বুকিং</h4>
          <?php while ($b = $myBookings->fetch_assoc()): 
            $reviewed = $conn->query("SELECT id FROM service_reviews WHERE booking_id=" . $b['id'])->num_rows > 0;
          ?>
            <div class="item-card">
              <div class="info">
                <h4><?= htmlspecialchars(($b['service_icon'] ?? '🔧') . ' ' . ($b['service_name'] ?? 'সার্ভিস')) ?></h4>
                <p><?= htmlspecialchars($b['problem'] ?? '') ?></p>
                <?php if ($reviewed): ?>
                  <span class="badge reviewed">✅ রিভিউ দিয়েছেন</span>
                <?php else: ?>
                  <span class="badge pending">⏳ রিভিউ বাকি</span>
                <?php endif; ?>
              </div>
              <?php if (!$reviewed): ?>
                <a href="give_review.php?booking=<?= $b['id'] ?>" class="btn-review">⭐ রিভিউ দিন</a>
              <?php endif; ?>
            </div>
          <?php endwhile; ?>
        <?php endif; ?>
        
        <?php if ($myOrders && $myOrders->num_rows > 0): ?>
          <h4>🛒 শপ অর্ডার</h4>
          <?php while ($o = $myOrders->fetch_assoc()): 
            $reviewed = $conn->query("SELECT id FROM product_reviews WHERE order_id=" . $o['id'])->num_rows > 0;
          ?>
            <div class="item-card" style="border-left-color:#8b5cf6">
              <div class="info">
                <h4>🛒 অর্ডার #<?= $o['id'] ?></h4>
                <p>মোট: ৳<?= number_format($o['total']) ?> • <?= date('d M Y', strtotime($o['created_at'])) ?></p>
                <?php if ($reviewed): ?>
                  <span class="badge reviewed">✅ রিভিউ দিয়েছেন</span>
                <?php else: ?>
                  <span class="badge pending">⏳ রিভিউ বাকি</span>
                <?php endif; ?>
              </div>
              <?php if (!$reviewed): ?>
                <a href="give_review.php?order=<?= $o['id'] ?>" class="btn-review">⭐ রিভিউ দিন</a>
              <?php endif; ?>
            </div>
          <?php endwhile; ?>
        <?php endif; ?>
        
      <?php else: ?>
        <div class="empty-msg">
          <b>😔 কোনো কাজ পাওয়া যায়নি</b><br>
          এই নম্বরে কোনো সম্পন্ন কাজ বা ডেলিভারি হওয়া অর্ডার নেই।
        </div>
      <?php endif; ?>
    </div>
  <?php endif; ?>

<?php endif; ?>

</div>

<footer>
    <!-- ============ অন্য কাস্টমারদের রিভিউ ============ -->
<?php
// ভালো রিভিউগুলো লোড করি (৪ বা ৫ তারা)
$otherReviews = $conn->query("SELECT r.*, s.name as service_name, s.icon as service_icon 
                              FROM service_reviews r 
                              LEFT JOIN bookings b ON r.booking_id = b.id 
                              LEFT JOIN services s ON b.service_id = s.id 
                              WHERE r.rating >= 4 
                              ORDER BY r.id DESC LIMIT 4");
?>
<?php if ($otherReviews && $otherReviews->num_rows > 0): ?>
<div style="max-width: 800px; margin: 40px auto 0; padding: 0 20px;">
  <h3 style="text-align: center; color: #1e3a8a; font-size: 22px; margin-bottom: 20px; font-weight: 800;">
    ⭐ আমাদের অন্যান্য কাস্টমারদের মতামত
  </h3>
  
  <div style="display: grid; gap: 15px;">
    <?php while ($r = $otherReviews->fetch_assoc()): ?>
      <div style="background: white; padding: 20px; border-radius: 15px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); border-left: 4px solid #fbbf24;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
          <div style="display: flex; align-items: center; gap: 10px;">
            <div style="width: 40px; height: 40px; border-radius: 50%; background: linear-gradient(135deg, #3b82f6, #8b5cf6); color: white; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 16px;">
              <?= htmlspecialchars(mb_substr($r['customer_name'] ?: 'অ', 0, 1)) ?>
            </div>
            <div>
              <div style="font-weight: 700; color: #1e3a8a; font-size: 15px;">
                <?= htmlspecialchars($r['customer_name'] ?: 'গ্রাহক') ?>
                <span style="background: #dcfce7; color: #16a34a; padding: 2px 8px; border-radius: 10px; font-size: 10px; margin-left: 5px;">✅ যাচাইকৃত</span>
              </div>
              <div style="font-size: 12px; color: #94a3b8; margin-top: 2px;">
                <?= htmlspecialchars(($r['service_icon'] ?? '🔧') . ' ' . ($r['service_name'] ?? 'সার্ভিস')) ?>
              </div>
            </div>
          </div>
          <div style="color: #fbbf24; font-size: 16px; letter-spacing: 1px;">
            <?= str_repeat('★', $r['rating']) ?>
          </div>
        </div>
        <?php if ($r['review']): ?>
          <p style="color: #475569; font-size: 14px; font-style: italic; line-height: 1.6;">
            "<?= nl2br(htmlspecialchars($r['review'])) ?>"
          </p>
        <?php endif; ?>
      </div>
    <?php endwhile; ?>
  </div>
</div>
<?php endif; ?>
  <p>📍 ময়মনসিংহ, বাংলাদেশ</p>
  <p>📞 হটলাইন: <a href="tel:+8801729560032">০১৭২৯-৫৬০০৩২</a></p>
  <p style="margin-top:15px;font-size:13px;color:#64748b;">© <?= date('Y') ?> ময়মনসিংহ সার্ভিস হাব</p>
</footer>

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