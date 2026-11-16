<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'db.php';

$categories = $conn->query("SELECT * FROM shop_categories WHERE active=1 ORDER BY sort_order, id");
$shopNotice = $conn->query("SELECT * FROM shop_notices WHERE active=1 ORDER BY id DESC LIMIT 1")->fetch_assoc();
$marquee = $conn->query("SELECT * FROM marquee WHERE active=1 LIMIT 1")->fetch_assoc();

// শপ পেজের ব্যানার লোড
$shopBanners = $conn->query("SELECT * FROM banners WHERE section='shop_page' AND active=1 ORDER BY sort_order, id");
?>
<!DOCTYPE html>
<html lang="bn">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>কেনাকাটা —  ময়মনসিংহ হোম সার্ভিস</title>
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
}
header nav a.btn-shop { background: #8b5cf6; font-weight: 700; }
header nav a.btn-work { background: #16a34a; }
header nav a.btn-order { background: #fbbf24; color: #78350f; font-weight: 700; }

.marquee-bar {
  background: linear-gradient(90deg, #fef3c7, #fde68a);
  color: #92400e; padding: 12px 0;
  font-weight: 700; font-size: 15px;
  border-bottom: 2px solid #fbbf24;
  overflow: hidden;
}
.shop-notice {
  background: linear-gradient(90deg, #ede9fe, #ddd6fe);
  color: #5b21b6; text-align: center;
  padding: 13px 20px; font-weight: 600;
  border-bottom: 2px solid #8b5cf6; font-size: 15px;
}

/* ব্যানার স্লাইডার */
.banner-section { max-width: 1200px; margin: 30px auto 0; padding: 0 20px; }
.banner-slider {
  position: relative; border-radius: 20px; overflow: hidden;
  box-shadow: 0 10px 30px rgba(0,0,0,0.1); height: 300px; background: #1e3a8a;
}
.banner-slide {
  position: absolute; inset: 0; opacity: 0; transition: opacity 0.8s ease;
  display: flex; align-items: center;
}
.banner-slide.active { opacity: 1; }
.banner-slide img { width: 100%; height: 100%; object-fit: cover; }
.banner-slide .overlay {
  position: absolute; inset: 0;
  background: linear-gradient(to right, rgba(0,0,0,0.7), transparent);
  display: flex; flex-direction: column; justify-content: center; padding: 30px;
}
.banner-slide .overlay h3 { color: white; font-size: 28px; font-weight: 800; margin-bottom: 15px; max-width: 500px; }
.banner-slide .overlay .btn {
  display: inline-block; background: linear-gradient(135deg, #16a34a, #15803d);
  color: white; padding: 10px 25px; border-radius: 30px;
  text-decoration: none; font-weight: 700; font-size: 14px; width: fit-content;
}

/* সার্চ বার */
.search-section { max-width: 1200px; margin: 25px auto 0; padding: 0 20px; }
.search-form-inner {
  display: flex; gap: 10px; background: white;
  padding: 8px; border-radius: 50px;
  box-shadow: 0 4px 15px rgba(0,0,0,0.08);
  max-width: 600px; margin: 0 auto;
}
.search-form-inner input {
  flex: 1; border: none; padding: 14px 22px;
  font-size: 15px; font-family: inherit;
  border-radius: 40px; outline: none;
}
.search-form-inner button {
  background: linear-gradient(135deg, #16a34a, #15803d);
  color: white; border: none;
  padding: 14px 28px; border-radius: 40px;
  font-size: 15px; font-weight: 700;
  cursor: pointer; font-family: inherit;
  white-space: nowrap;
}

/* হিরো */
.shop-hero {
  text-align: center; padding: 40px 20px 20px;
}
.shop-hero h2 { font-size: 30px; color: #166534; margin-bottom: 12px; font-weight: 800; }
.shop-hero p { font-size: 15px; color: #475569; }

.section-title { text-align: center; font-size: 24px; margin: 30px 0 10px; color: #1e3a8a; font-weight: 800; }
.section-sub { text-align: center; color: #64748b; margin-bottom: 30px; font-size: 15px; }

/* ক্যাটাগরি */
.cat-grid {
  display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
  gap: 20px; max-width: 1100px; margin: 0 auto; padding: 0 20px 60px;
}
.cat-card {
  background: white; border-radius: 18px;
  text-decoration: none; color: #222;
  box-shadow: 0 4px 15px rgba(0,0,0,0.06);
  transition: 0.3s; overflow: hidden;
  border: 2px solid transparent; display: block; text-align: center;
}
.cat-card:hover {
  transform: translateY(-8px); border-color: #8b5cf6;
  box-shadow: 0 16px 35px rgba(139,92,246,0.25);
}
.cat-card .cat-img {
  height: 140px; background: linear-gradient(135deg, #ede9fe, #f5f3ff);
  display: flex; align-items: center; justify-content: center; overflow: hidden;
}
.cat-card .cat-img img { width: 100%; height: 100%; object-fit: cover; }
.cat-card .cat-img .emoji { font-size: 65px; }
.cat-card .cat-body { padding: 15px; }
.cat-card .cat-body h3 { font-size: 17px; color: #1e3a8a; font-weight: 700; margin-bottom: 5px; }
.cat-card .cat-body p { font-size: 12px; color: #94a3b8; }

.empty {
  background: white; max-width: 600px; margin: 40px auto;
  padding: 70px 30px; border-radius: 20px; text-align: center;
  box-shadow: 0 4px 20px rgba(0,0,0,0.06);
}
.empty .icon { font-size: 80px; display: block; margin-bottom: 20px; }
.empty h3 { color: #1e3a8a; font-size: 22px; margin-bottom: 10px; }
.empty a {
  display: inline-block; background: #2563eb; color: white;
  padding: 12px 28px; border-radius: 25px;
  text-decoration: none; font-weight: 600;
}

footer { background: #0f172a; color: #cbd5e1; text-align: center; padding: 30px 20px; margin-top: 40px; }
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
  .shop-hero h2 { font-size: 24px; }
  .banner-slider { height: 200px; }
  .banner-slide .overlay { padding: 20px; }
  .banner-slide .overlay h3 { font-size: 20px; }
  .cat-grid { grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 15px; }
  .cat-card .cat-img { height: 120px; }
}
</style>
</head>
<body>

<header>
  <h1>🌟 ময়মনসিংহ সার্ভিস হাব</h1>
  <nav>
      <a href="index.php" style="background:#ef4444; font-weight:700;">🏠 হোম</a>
    <a href="book.php" class="btn-order">📞 সার্ভিস অর্ডার</a>
    <a href="work.php" class="btn-work">💼 কাজ করতে চাই</a>
    <a href="shop.php" class="btn-shop">🛒 কেনাকাটা</a>
    <a href="give_review.php" style="background:#f59e0b;color:white;font-weight:700;">⭐ রিভিউ</a>
  </nav>
</header>

<?php if ($marquee && $marquee['active']): ?>
<div class="marquee-bar">
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

<!-- ============ শপ পেজের ব্যানার স্লাইডার ============ -->
<?php if ($shopBanners && $shopBanners->num_rows > 0): 
  $shopBannersArray = [];
  while ($b = $shopBanners->fetch_assoc()) { $shopBannersArray[] = $b; }
?>
<div class="banner-section">
  <div class="banner-slider" id="shopSlider">
    <?php foreach ($shopBannersArray as $i => $b): ?>
      <div class="banner-slide <?= $i === 0 ? 'active' : '' ?>">
        <img src="uploads/banners/<?= htmlspecialchars($b['image']) ?>" alt="">
        <?php if ($b['title'] || $b['link']): ?>
        <div class="overlay">
          <?php if ($b['title']): ?><h3><?= htmlspecialchars($b['title']) ?></h3><?php endif; ?>
          <?php if ($b['link']): ?><a href="<?= htmlspecialchars($b['link']) ?>" class="btn">🛒 এখনই কিনুন</a><?php endif; ?>
        </div>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>
</div>
<script>
(function() {
    const slider = document.getElementById('shopSlider');
    if(!slider) return;
    const slides = slider.querySelectorAll('.banner-slide');
    const timer = <?= $shopBannersArray[0]['slide_timer'] ?? 4000 ?>;
    let current = 0;
    if (slides.length > 1) {
        setInterval(() => {
            slides[current].classList.remove('active');
            current = (current + 1) % slides.length;
            slides[current].classList.add('active');
        }, timer);
    }
})();
</script>
<?php endif; ?>

<!-- সার্চ বার -->
<div class="search-section">
  <form action="shop_search.php" method="GET" class="search-form-inner">
    <input type="text" name="q" placeholder="🔍 প্রোডাক্টের নাম বা কোড লিখুন...">
    <button type="submit">সার্চ</button>
  </form>
</div>

<div class="shop-hero">
  <h2>🛒 আমাদের অনলাইন দোকান</h2>
  <p>ক্যাটাগরি থেকে আপনার পছন্দের প্রোডাক্ট বেছে নিন — ঘরে বসেই অর্ডার করুন</p>
</div>

<h2 class="section-title">প্রোডাক্ট ক্যাটাগরি</h2>
<p class="section-sub">নিচের যেকোনো ক্যাটাগরিতে ক্লিক করুন</p>

<?php if ($categories && $categories->num_rows > 0): ?>
  <div class="cat-grid">
    <?php while ($c = $categories->fetch_assoc()): 
      $imgPath = 'uploads/shop_categories/' . $c['image'];
      $hasImg = !empty($c['image']) && file_exists($imgPath);
      $cid = $c['id'];
      $count = $conn->query("SELECT COUNT(*) as c FROM products WHERE category_id=$cid AND active=1")->fetch_assoc()['c'];
    ?>
      <a href="shop_category.php?id=<?= $c['id'] ?>" class="cat-card">
        <div class="cat-img">
          <?php if ($hasImg): ?>
            <img src="<?= htmlspecialchars($imgPath) ?>" alt="<?= htmlspecialchars($c['name']) ?>">
          <?php else: ?>
            <span class="emoji"><?= htmlspecialchars($c['icon'] ?? '🛍️') ?></span>
          <?php endif; ?>
        </div>
        <div class="cat-body">
          <h3><?= htmlspecialchars($c['name']) ?></h3>
          <p><?= $count ?> টি প্রোডাক্ট</p>
        </div>
      </a>
    <?php endwhile; ?>
  </div>
<?php else: ?>
  <div class="empty">
    <span class="icon">🛍️</span>
    <h3>এখনো কোনো ক্যাটাগরি নেই</h3>
    <a href="index.php">← হোমে ফিরে যান</a>
  </div>
<?php endif; ?>

<footer>
  <p>📍 ময়মনসিংহ, বাংলাদেশ</p>
  <p>📞 হটলাইন: <a href="tel:+8801729560032">০১৭২৯-৫৬০০৩২</a></p>
  <p style="margin-top:15px;font-size:13px;color:#64748b;">© <?= date('Y') ?> ময়মনসিংহ হোম সার্ভিস</p>
</footer>

<a href="tel:+8801729560032" class="floating-call">📞</a>

</body>
</html>