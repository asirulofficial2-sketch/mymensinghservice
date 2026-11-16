<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'db.php';

$services = $conn->query("SELECT * FROM services WHERE active=1 ORDER BY id ASC");
$notice = $conn->query("SELECT * FROM notices WHERE active=1 ORDER BY id DESC LIMIT 1")->fetch_assoc();
$marquee = $conn->query("SELECT * FROM marquee WHERE active=1 LIMIT 1")->fetch_assoc();

// ব্যানার লোড
$homeServiceBanners = $conn->query("SELECT * FROM banners WHERE section='home_service' AND active=1 ORDER BY sort_order, id");
$homeProductBanners = $conn->query("SELECT * FROM banners WHERE section='home_product' AND active=1 ORDER BY sort_order, id");

// শপ ক্যাটাগরি লোড
$shopCategories = $conn->query("SELECT * FROM shop_categories WHERE active=1 ORDER BY sort_order, id LIMIT 6");

// কাস্টমার রিভিউ লোড (ভালো রিভিউগুলো)
$featuredReviews = $conn->query("SELECT r.*, s.name as service_name, s.icon as service_icon 
                                 FROM service_reviews r 
                                 LEFT JOIN bookings b ON r.booking_id = b.id 
                                 LEFT JOIN services s ON b.service_id = s.id 
                                 WHERE r.rating >= 4 
                                 ORDER BY r.id DESC LIMIT 3");
?>
<!DOCTYPE html>
<html lang="bn">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>🌟 ময়মনসিংহ হোম সার্ভিস </title>
<style>
* { margin:0; padding:0; box-sizing:border-box; font-family:'Segoe UI',sans-serif; }
body { background: #f5f7fa; color: #222; }

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
.notice-bar {
  background: linear-gradient(90deg, #fef3c7, #fde68a);
  color: #92400e; text-align: center;
  padding: 13px 20px; font-weight: 600;
  border-bottom: 2px solid #fbbf24; font-size: 15px;
}

/* হিরো */
.hero {
  text-align: center; padding: 60px 20px;
  background: linear-gradient(135deg, #dbeafe 0%, #eff6ff 50%, #e0e7ff 100%);
}
.hero h2 { font-size: 34px; color: #1e3a8a; margin-bottom: 15px; font-weight: 800; }
.hero p { font-size: 16px; color: #475569; max-width: 650px; margin: 0 auto 25px; line-height: 1.7; }
.hero .btn {
  background: linear-gradient(135deg, #2563eb, #1e40af);
  color: white; padding: 15px 40px;
  text-decoration: none; border-radius: 35px; font-size: 16px;
  font-weight: 700; display: inline-block;
  box-shadow: 0 6px 20px rgba(37,99,235,0.35);
}

/* ব্যানার স্লাইডার */
.banner-section { max-width: 1200px; margin: 40px auto; padding: 0 20px; }
.banner-section h2 { color: #1e3a8a; font-size: 24px; margin-bottom: 20px; font-weight: 800; }
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
  background: linear-gradient(to right, rgba(0,0,0,0.8), transparent);
  display: flex; flex-direction: column; justify-content: center; padding: 30px;
}
.banner-slide .overlay h3 { color: white; font-size: 28px; font-weight: 800; margin-bottom: 10px; max-width: 500px; }
.banner-slide .overlay .btn {
  display: inline-block; background: linear-gradient(135deg, #16a34a, #15803d);
  color: white; padding: 10px 25px; border-radius: 30px;
  text-decoration: none; font-weight: 700; font-size: 14px; width: fit-content;
}

/* সেকশন টাইটেল */
.section-title { text-align: center; font-size: 26px; margin: 50px 0 10px; color: #1e3a8a; font-weight: 800; }
.section-sub { text-align: center; color: #64748b; margin-bottom: 30px; font-size: 15px; }

/* সার্ভিস লিস্ট */
.services { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; max-width: 1100px; margin: 0 auto; padding: 0 20px 40px; }
.service-card {
  background: white; border-radius: 20px; text-align: center; 
  box-shadow: 0 4px 18px rgba(0,0,0,0.06); text-decoration: none; color: #222;
  transition: 0.3s; border: 2px solid transparent; display: block; overflow: hidden;
}
.service-card:hover { transform: translateY(-8px); border-color: #dbeafe; box-shadow: 0 16px 40px rgba(59,130,246,0.2); }
.service-card .card-img { height: 150px; background: linear-gradient(135deg, #dbeafe, #eff6ff); display: flex; align-items: center; justify-content: center; overflow: hidden; }
.service-card .card-img img { width: 100%; height: 100%; object-fit: cover; }
.service-card .card-img .emoji { font-size: 60px; }
.service-card .card-body { padding: 15px; }
.service-card .card-body h3 { font-size: 17px; margin-bottom: 6px; color: #1e3a8a; font-weight: 700; }
.service-card .card-body p { font-size: 12px; color: #64748b; margin-bottom: 10px; line-height: 1.5; }
.service-card .price { display: inline-block; background: #fef3c7; color: #d97706; padding: 6px 14px; border-radius: 20px; font-weight: 700; font-size: 13px; }

/* কেনাকাটার ক্যাটাগরি */
.shop-cats { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 15px; max-width: 1100px; margin: 0 auto; padding: 0 20px 40px; }
.cat-card {
  background: white; border-radius: 15px; overflow: hidden;
  text-decoration: none; color: #222; box-shadow: 0 4px 15px rgba(0,0,0,0.06);
  transition: 0.3s; text-align: center; display: block;
}
.cat-card:hover { transform: translateY(-5px); box-shadow: 0 10px 25px rgba(139,92,246,0.2); }
.cat-card .cat-img { height: 120px; background: #f8fafc; display: flex; align-items: center; justify-content: center; overflow: hidden; }
.cat-card .cat-img img { width: 100%; height: 100%; object-fit: cover; }
.cat-card .cat-img .emoji { font-size: 50px; }
.cat-card h3 { font-size: 15px; padding: 12px 10px 5px; color: #1e3a8a; font-weight: 700; }
.cat-card .count { font-size: 11px; color: #94a3b8; padding-bottom: 12px; display: block; }

/* কাস্টমার রিভিউ */
.reviews-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; max-width: 1100px; margin: 0 auto; padding: 0 20px 40px; }
.review-box {
  background: white; padding: 25px; border-radius: 20px;
  box-shadow: 0 4px 15px rgba(0,0,0,0.06); position: relative;
}
.review-box .quote { font-size: 50px; color: #fbbf24; position: absolute; top: 10px; right: 20px; opacity: 0.3; }
.review-box .stars { color: #fbbf24; font-size: 18px; margin-bottom: 10px; }
.review-box p { color: #475569; font-size: 14px; line-height: 1.7; font-style: italic; margin-bottom: 15px; }
.review-box .customer { display: flex; align-items: center; gap: 10px; border-top: 1px solid #f1f5f9; padding-top: 15px; }
.review-box .avatar { width: 40px; height: 40px; border-radius: 50%; background: linear-gradient(135deg, #3b82f6, #8b5cf6); color: white; display: flex; align-items: center; justify-content: center; font-weight: bold; }
.review-box .name { font-weight: 700; color: #1e3a8a; font-size: 14px; }
.review-box .service-name { font-size: 12px; color: #64748b; }

/* জরুরি */
.emergency-wrap { padding: 20px; }
.emergency { background: linear-gradient(135deg, #dc2626, #ef4444); color: white; text-align: center; padding: 40px 25px; margin: 20px auto; max-width: 1100px; border-radius: 25px; box-shadow: 0 15px 40px rgba(220,38,38,0.3); }
.emergency h3 { font-size: 24px; margin-bottom: 10px; }
.emergency p { margin-bottom: 20px; font-size: 15px; }
.emergency a { display: inline-block; background: white; color: #dc2626; padding: 12px 30px; border-radius: 30px; text-decoration: none; font-weight: 700; font-size: 16px; }

footer { background: #0f172a; color: #cbd5e1; text-align: center; padding: 40px 20px; }
footer p { margin-bottom: 8px; }
footer a { color: #60a5fa; text-decoration: none; }

.floating-call { position: fixed; bottom: 25px; right: 25px; background: #16a34a; color: white; width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 26px; text-decoration: none; box-shadow: 0 8px 25px rgba(22,163,74,0.5); z-index: 999; }

@media (max-width: 600px) {
  header h1 { font-size: 16px; }
  header nav a { padding: 7px 12px; font-size: 13px; }
  .hero h2 { font-size: 24px; }
  .hero { padding: 40px 15px; }
  .banner-slider { height: 200px; }
  .banner-slide .overlay { padding: 15px; }
  .banner-slide .overlay h3 { font-size: 18px; }
  .banner-section h2 { font-size: 20px; }
  .section-title { font-size: 22px; }
}
</style>
</head>
<body>

<header>
  <h1>🌟 ময়মনসিংহ হোম সার্ভিস </h1>
  <nav>
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

<?php if ($notice): ?>
<div class="notice-bar">
  📢 <strong><?= htmlspecialchars($notice['title']) ?>:</strong> 
  <?= htmlspecialchars($notice['message']) ?>
</div>
<?php endif; ?>

<section class="hero">
  <h2>বাসার সব সমস্যার সমাধান এক ক্লিকে</h2>
  <p>ইলেকট্রিক, প্লাম্বিং, এসি, ফ্রিজ, টিভি, ঔষধ, অ্যাম্বুলেন্স — বাসায় এসে সার্ভিস</p>
  <a href="#services" class="btn">🚀 এখনই অর্ডার করুন</a>
</section>

<!-- সার্ভিস ব্যানার স্লাইডার -->
<?php if ($homeServiceBanners && $homeServiceBanners->num_rows > 0): 
  $serviceBannersArray = [];
  while ($b = $homeServiceBanners->fetch_assoc()) { $serviceBannersArray[] = $b; }
?>
<div class="banner-section">
  <h2>⚡ আমাদের সার্ভিস</h2>
  <div class="banner-slider" id="serviceSlider">
    <?php foreach ($serviceBannersArray as $i => $b): ?>
      <div class="banner-slide <?= $i === 0 ? 'active' : '' ?>">
        <img src="uploads/banners/<?= htmlspecialchars($b['image']) ?>" alt="">
        <?php if ($b['title'] || $b['link']): ?>
        <div class="overlay">
          <?php if ($b['title']): ?><h3><?= htmlspecialchars($b['title']) ?></h3><?php endif; ?>
          <?php if ($b['link']): ?><a href="<?= htmlspecialchars($b['link']) ?>" class="btn">⚡ এখনই অর্ডার করুন</a><?php endif; ?>
        </div>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>
</div>
<script>
(function() {
    const slider = document.getElementById('serviceSlider');
    if(!slider) return;
    const slides = slider.querySelectorAll('.banner-slide');
    const timer = <?= $serviceBannersArray[0]['slide_timer'] ?? 4000 ?>;
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

<!-- সার্ভিস লিস্ট -->
<h2 class="section-title" id="services">আমাদের সার্ভিস</h2>
<p class="section-sub">একটি সার্ভিস বেছে নিন — আমরা বাসায় এসে সমাধান করব</p>

<div class="services">
  <?php if ($services && $services->num_rows > 0): ?>
    <?php while ($s = $services->fetch_assoc()): 
      $imgPath = 'uploads/services/' . $s['image'];
      $hasImg = !empty($s['image']) && file_exists($imgPath);
    ?>
    <a href="book.php?service=<?= $s['id'] ?>" class="service-card">
      <div class="card-img">
        <?php if ($hasImg): ?>
          <img src="<?= htmlspecialchars($imgPath) ?>" alt="">
        <?php else: ?>
          <span class="emoji"><?= htmlspecialchars($s['icon'] ?? '🔧') ?></span>
        <?php endif; ?>
      </div>
      <div class="card-body">
        <h3><?= htmlspecialchars($s['name']) ?></h3>
        <p><?= htmlspecialchars($s['description']) ?></p>
        <span class="price">🛒 অর্ডার করুন</span>
      </div>
    </a>
    <?php endwhile; ?>
  <?php endif; ?>
</div>

<!-- প্রোডাক্ট ব্যানার স্লাইডার -->
<?php if ($homeProductBanners && $homeProductBanners->num_rows > 0): 
  $productBannersArray = [];
  while ($b = $homeProductBanners->fetch_assoc()) { $productBannersArray[] = $b; }
?>
<div class="banner-section">
  <h2>🛒 কেনাকাটা</h2>
  <div class="banner-slider" id="productSlider">
    <?php foreach ($productBannersArray as $i => $b): ?>
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
    const slider = document.getElementById('productSlider');
    if(!slider) return;
    const slides = slider.querySelectorAll('.banner-slide');
    const timer = <?= $productBannersArray[0]['slide_timer'] ?? 4000 ?>;
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

<!-- শপ ক্যাটাগরি -->
<h2 class="section-title">কেনাকাটার ক্যাটাগরি</h2>
<p class="section-sub">আপনার পছন্দের ক্যাটাগরি বেছে নিন</p>

<div class="shop-cats">
  <?php if ($shopCategories && $shopCategories->num_rows > 0): ?>
    <?php while ($c = $shopCategories->fetch_assoc()): 
      $catImgPath = 'uploads/shop_categories/' . $c['image'];
      $hasCatImg = !empty($c['image']) && file_exists($catImgPath);
      $count = $conn->query("SELECT COUNT(*) as cc FROM products WHERE category_id=" . $c['id'] . " AND active=1")->fetch_assoc()['cc'];
    ?>
    <a href="shop_category.php?id=<?= $c['id'] ?>" class="cat-card">
      <div class="cat-img">
        <?php if ($hasCatImg): ?>
          <img src="<?= htmlspecialchars($catImgPath) ?>" alt="">
        <?php else: ?>
          <span class="emoji"><?= htmlspecialchars($c['icon'] ?? '🛍️') ?></span>
        <?php endif; ?>
      </div>
      <h3><?= htmlspecialchars($c['name']) ?></h3>
      <span class="count"><?= $count ?> টি প্রোডাক্ট</span>
    </a>
    <?php endwhile; ?>
  <?php else: ?>
    <p style="text-align:center;padding:40px;">এখনো কোনো ক্যাটাগরি নেই।</p>
  <?php endif; ?>
</div>

<!-- কাস্টমার রিভিউ -->
<?php if ($featuredReviews && $featuredReviews->num_rows > 0): ?>
<h2 class="section-title">⭐ কাস্টমার রিভিউ</h2>
<p class="section-sub">আমাদের কাস্টমাররা যা বলেছেন</p>

<div class="reviews-grid">
  <?php while ($r = $featuredReviews->fetch_assoc()): ?>
    <div class="review-box">
      <span class="quote">"</span>
      <div class="stars"><?= str_repeat('★', $r['rating']) ?><?= str_repeat('☆', 5 - $r['rating']) ?></div>
      <p><?= nl2br(htmlspecialchars($r['review'] ?: 'খুব ভালো সার্ভিস পেয়েছি। ধন্যবাদ।')) ?></p>
      <div class="customer">
        <div class="avatar"><?= htmlspecialchars(mb_substr($r['customer_name'] ?: 'অ', 0, 1)) ?></div>
        <div>
          <div class="name"><?= htmlspecialchars($r['customer_name'] ?: 'গ্রাহক') ?></div>
          <div class="service-name"><?= htmlspecialchars(($r['service_icon'] ?? '🔧') . ' ' . ($r['service_name'] ?? 'সার্ভিস')) ?></div>
        </div>
      </div>
    </div>
  <?php endwhile; ?>
</div>
<?php endif; ?>



<footer>
  <p>📍 ময়মনসিংহ, বাংলাদেশ</p>
  <p>📞 হটলাইন: <a href="tel:+8801729560032">০১৭২৯-৫৬০০৩২</a></p>
  <p style="margin-top:15px;font-size:13px;color:#64748b;">© <?= date('Y') ?> ময়মনসিংহ সার্ভিস হাব</p>
</footer>

<a href="tel:+8801729560032" class="floating-call">📞</a>

</body>
</html>