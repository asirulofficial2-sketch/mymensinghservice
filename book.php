<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'db.php';

$service_id = intval($_GET['service'] ?? 0);

$selected = null;
if ($service_id) {
    $stmt = $conn->prepare("SELECT * FROM services WHERE id=? AND active=1");
    $stmt->bind_param("i", $service_id);
    $stmt->execute();
    $selected = $stmt->get_result()->fetch_assoc();
}

$allServices = $conn->query("SELECT * FROM services WHERE active=1 ORDER BY id");

$success = isset($_GET['success']);

$isMedicine = false;
if ($selected && (mb_strpos($selected['name'], 'ঔষধ') !== false || mb_strpos($selected['name'], 'ঔষুধ') !== false)) {
    $isMedicine = true;
}

$problemLabel = $selected['problem_label'] ?? 'সমস্যার বিবরণ';
$problemPlaceholder = $selected['problem_placeholder'] ?? 'আপনার সমস্যা বিস্তারিত লিখুন';
$timeLabel = $selected['time_label'] ?? 'কখন আসতে হবে?';

// সার্ভিস নোটিশ ও ব্যানার লোড
$notice = $conn->query("SELECT * FROM notices WHERE active=1 ORDER BY id DESC LIMIT 1")->fetch_assoc();
$marquee = $conn->query("SELECT * FROM marquee WHERE active=1 LIMIT 1")->fetch_assoc();
$serviceBanners = $conn->query("SELECT * FROM banners WHERE section='services_page' AND active=1 ORDER BY sort_order, id");
?>
<!DOCTYPE html>
<html lang="bn">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>সার্ভিস অর্ডার করুন — ময়মনসিংহ সার্ভিস হাব</title>
<!-- EmailJS লাইব্রেরি -->
<script src="https://cdn.jsdelivr.net/npm/@emailjs/browser@4/dist/email.min.js"></script>
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

.marquee-bar {
  background: linear-gradient(90deg, #fef3c7, #fde68a);
  color: #92400e; padding: 12px 0;
  font-weight: 700; font-size: 15px;
  border-bottom: 2px solid #fbbf24;
  overflow: hidden;
}
.service-notice {
  background: linear-gradient(90deg, #fef3c7, #fde68a);
  color: #92400e; text-align: center;
  padding: 13px 20px; font-weight: 600;
  border-bottom: 2px solid #fbbf24; font-size: 15px;
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

.hero {
  text-align: center; padding: 40px 20px 30px;
  background: linear-gradient(135deg, #dbeafe, #eff6ff);
}
.hero h2 { font-size: 32px; color: #1e3a8a; margin-bottom: 12px; font-weight: 800; }
.hero p { font-size: 16px; color: #475569; max-width: 600px; margin: 0 auto; }

.section-title { text-align: center; font-size: 26px; margin: 40px 0 10px; color: #1e3a8a; font-weight: 800; }
.section-sub { text-align: center; color: #64748b; margin-bottom: 35px; font-size: 15px; }

.services-list {
  max-width: 1100px; margin: 0 auto; padding: 0 20px 60px;
  display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 22px;
}
.service-item {
  background: white; border-radius: 20px; overflow: hidden;
  box-shadow: 0 4px 18px rgba(0,0,0,0.06); transition: 0.3s;
  border: 2px solid transparent; text-align: center;
}
.service-item:hover {
  transform: translateY(-8px); border-color: #3b82f6;
  box-shadow: 0 16px 40px rgba(59,130,246,0.2);
}
.service-item .s-img {
  height: 170px; background: linear-gradient(135deg, #dbeafe, #eff6ff);
  display: flex; align-items: center; justify-content: center; overflow: hidden;
}
.service-item .s-img img { width: 100%; height: 100%; object-fit: cover; }
.service-item .s-img .emoji { font-size: 75px; }
.service-item .s-body { padding: 20px 18px 25px; }
.service-item .s-body h3 { font-size: 20px; color: #1e3a8a; font-weight: 700; margin-bottom: 8px; }
.service-item .s-body p { font-size: 13px; color: #64748b; margin-bottom: 15px; line-height: 1.6; min-height: 42px; }
.btn-order-now {
  display: block; width: 100%; background: linear-gradient(135deg, #16a34a, #15803d);
  color: white; padding: 15px 20px; border-radius: 12px; text-decoration: none;
  font-size: 16px; font-weight: 700; text-align: center;
  box-shadow: 0 6px 20px rgba(22,163,74,0.35);
}

.form-container { max-width: 600px; margin: 30px auto; padding: 0 20px 60px; }
.card { background: white; padding: 30px; border-radius: 20px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
.selected-service {
  background: linear-gradient(135deg, #dbeafe, #eff6ff);
  padding: 20px; border-radius: 15px; margin-bottom: 25px;
  text-align: center; border: 2px solid #3b82f6;
}
.selected-service .icon { font-size: 50px; display: block; margin-bottom: 10px; }
.selected-service h2 { color: #1e3a8a; font-size: 22px; margin-bottom: 5px; }
.selected-service p { color: #475569; font-size: 14px; }
.selected-service .price { display: inline-block; margin-top: 10px; background: #16a34a; color: white; padding: 6px 16px; border-radius: 20px; font-weight: 600; font-size: 14px; }
label { display: block; margin-bottom: 6px; font-weight: 600; color: #334155; font-size: 14px; }
input, select, textarea { width: 100%; padding: 12px 14px; margin-bottom: 18px; border: 2px solid #e2e8f0; border-radius: 10px; font-size: 15px; font-family: inherit; background: white; }
input:focus, select:focus, textarea:focus { outline: none; border-color: #3b82f6; }
textarea { min-height: 90px; resize: vertical; }
.btn-submit { width: 100%; padding: 15px; background: linear-gradient(135deg, #2563eb, #1e40af); color: white; border: none; border-radius: 12px; font-size: 17px; font-weight: 700; cursor: pointer; }
.back-link { display: inline-block; background: #e2e8f0; color: #334155; padding: 12px 24px; border-radius: 10px; text-decoration: none; font-weight: 600; margin-top: 15px; text-align: center; width: 100%; }
.info-box { background: #fef3c7; color: #92400e; padding: 12px 15px; border-radius: 10px; font-size: 13px; margin-bottom: 20px; }
.hint-text { display: block; color: #64748b; font-size: 12px; margin-top: -14px; margin-bottom: 18px; }
.success-box { background: #dcfce7; border: 2px solid #16a34a; color: #166534; padding: 25px; border-radius: 15px; text-align: center; margin-bottom: 25px; }
.success-box .check { font-size: 50px; display: block; margin-bottom: 10px; }
.success-box h3 { font-size: 22px; margin-bottom: 10px; }
.success-box a { display: inline-block; margin-top: 15px; color: #2563eb; text-decoration: none; font-weight: 600; }
.prescription-section { background: linear-gradient(135deg, #eff6ff, #dbeafe); border: 2px dashed #3b82f6; padding: 20px; border-radius: 15px; margin-bottom: 20px; text-align: center; }
.prescription-section .title { color: #1e40af; font-weight: 700; font-size: 15px; margin-bottom: 12px; }
.file-upload { border: 2px dashed #93c5fd; padding: 20px; border-radius: 12px; background: white; cursor: pointer; position: relative; }
.file-upload input[type=file] { position: absolute; inset: 0; opacity: 0; cursor: pointer; }
.file-upload .up-icon { font-size: 40px; display: block; margin-bottom: 8px; }
.file-upload .up-text { font-size: 14px; color: #475569; font-weight: 600; }
.file-upload .preview { margin-top: 12px; max-width: 220px; border-radius: 10px; display: none; }
.file-upload .filename { color: #16a34a; font-weight: 600; font-size: 13px; margin-top: 10px; }
footer { background: #0f172a; color: #cbd5e1; text-align: center; padding: 30px 20px; margin-top: 40px; }
footer p { margin-bottom: 8px; }
footer a { color: #60a5fa; text-decoration: none; }
.floating-call { position: fixed; bottom: 25px; right: 25px; background: #16a34a; color: white; width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 26px; text-decoration: none; box-shadow: 0 8px 25px rgba(22,163,74,0.5); z-index: 999; }

@media (max-width: 600px) {
  header h1 { font-size: 16px; }
  header nav a { padding: 7px 12px; font-size: 13px; }
  .hero h2 { font-size: 24px; }
  .banner-slider { height: 200px; }
  .banner-slide .overlay { padding: 20px; }
  .banner-slide .overlay h3 { font-size: 20px; }
  .services-list { grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 15px; }
}
</style>
</head>
<body>

<header>
  <h1>🌟ময়মনসিংহ হোম সার্ভিস</h1>
  <nav>
    <a href="index.php" style="background:#ef4444; font-weight:700;">🏠 হোম</a>
    <a href="book.php" class="btn-order">📞 সার্ভিস অর্ডার</a>
    <a href="work.php" class="btn-work">💼 কাজ করতে চাই</a>
    <a href="shop.php" class="btn-shop">🛒 কেনাকাটা</a>
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
<div class="service-notice">
  📢 <strong><?= htmlspecialchars($notice['title']) ?>:</strong> 
  <?= htmlspecialchars($notice['message']) ?>
</div>
<?php endif; ?>

<!-- ============ সার্ভিস পেজের ব্যানার স্লাইডার ============ -->
<?php if ($serviceBanners && $serviceBanners->num_rows > 0): 
  $serviceBannersArray = [];
  while ($b = $serviceBanners->fetch_assoc()) { $serviceBannersArray[] = $b; }
?>
<div class="banner-section">
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

<?php if ($selected): ?>
  <div class="form-container">
    <div class="card">
      <?php if ($success): ?>
        <div class="success-box">
          <span class="check">✅</span>
          <h3>আপনার অর্ডার সফল হয়েছে!</h3>
          <p>আমরা শীঘ্রই আপনাকে কল করে সময় জানাবো।</p>
          <a href="index.php">← হোমে ফিরে যান</a>
        </div>
      <?php else: ?>
        <div class="selected-service">
          <span class="icon"><?= htmlspecialchars($selected['icon'] ?? '🔧') ?></span>
          <h2><?= htmlspecialchars($selected['name']) ?></h2>
          <p><?= htmlspecialchars($selected['description'] ?? '') ?></p>
          <?php if ($selected['min_price'] > 0): ?>
            <span class="price">শুরু ৳<?= number_format($selected['min_price']) ?></span>
          <?php else: ?>
            <span class="price" style="background:#f59e0b">ফ্রি কল</span>
          <?php endif; ?>
        </div>
        <div class="info-box">
          ⚠️ সঠিক তথ্য দিন — আমরা এই নম্বরে কল করে সেবা নিশ্চিত করব।
        </div>
        
        <!-- ফর্মে id="booking-form" যোগ করা হলো -->
        <form id="booking-form" enctype="multipart/form-data">
          <input type="hidden" name="service_id" value="<?= $selected['id'] ?>">
          <label>আপনার নাম *</label>
          <input type="text" name="customer_name" required placeholder="পূর্ণ নাম">
          <label>মোবাইল নম্বর *</label>
          <input type="tel" name="phone" required placeholder="01XXXXXXXXX" pattern="01[0-9]{9}">
          <label>ঠিকানা *</label>
          <textarea name="address" required placeholder="বাসা/রোড/এলাকা, ময়মনসিংহ"></textarea>
          <label><?= htmlspecialchars($problemLabel) ?></label>
          <textarea name="problem" placeholder="<?= htmlspecialchars($problemPlaceholder) ?>"></textarea>
          <?php if ($isMedicine): ?>
          <div class="prescription-section">
            <div class="title">📷 প্রেসক্রিপশনের ছবি (ঐচ্ছিক)</div>
            <div class="file-upload">
              <input type="file" name="prescription" id="prescription" accept="image/*" onchange="previewPrescription(this)">
              <span class="up-icon" id="presIcon">📄</span>
              <span class="up-text">এখানে ক্লিক করে প্রেসক্রিপশনের ছবি আপলোড করুন</span>
              <img id="presPreview" class="preview" alt="">
              <div class="filename" id="presFilename"></div>
            </div>
          </div>
          <?php endif; ?>
          <label>🕐 <?= htmlspecialchars($timeLabel) ?></label>
          <input type="text" name="preferred_time" required placeholder="যেমন: আজ বিকাল ৪টায়">
          <span class="hint-text">💡 নির্দিষ্ট সময় লিখুন</span>
          <button type="submit" class="btn-submit" id="submit-btn">✅ অর্ডার কনফার্ম করুন</button>
        </form>
        <a href="book.php" class="back-link">← সব সার্ভিস দেখুন</a>
      <?php endif; ?>
    </div>
  </div>
<?php else: ?>
  <div class="hero">
    <h2>📞 সার্ভিস অর্ডার করুন</h2>
    <p>নিচের যেকোনো সার্ভিসে ক্লিক করুন — আমরা বাসায় এসে সমাধান করব</p>
  </div>
  <h2 class="section-title">আমাদের সার্ভিস</h2>
  <p class="section-sub">"এখনই অর্ডার করুন" বাটনে ক্লিক করুন</p>
  <?php if ($allServices && $allServices->num_rows > 0): ?>
    <div class="services-list">
      <?php while ($s = $allServices->fetch_assoc()): 
        $imgPath = 'uploads/services/' . $s['image'];
        $hasImg = !empty($s['image']) && file_exists($imgPath);
      ?>
        <div class="service-item">
          <div class="s-img">
            <?php if ($hasImg): ?>
              <img src="<?= htmlspecialchars($imgPath) ?>" alt="">
            <?php else: ?>
              <span class="emoji"><?= htmlspecialchars($s['icon'] ?? '🔧') ?></span>
            <?php endif; ?>
          </div>
          <div class="s-body">
            <h3><?= htmlspecialchars($s['name']) ?></h3>
            <p><?= htmlspecialchars($s['description']) ?></p>
            <a href="book.php?service=<?= $s['id'] ?>" class="btn-order-now">⚡ এখনই অর্ডার করুন</a>
          </div>
        </div>
      <?php endwhile; ?>
    </div>
  <?php else: ?>
    <div style="text-align:center;padding:60px 20px;color:#64748b;">
      <p style="font-size:20px;">এখনো কোনো সার্ভিস যোগ করা হয়নি।</p>
    </div>
  <?php endif; ?>
<?php endif; ?>

<footer>
  <p>📍 ময়মনসিংহ, বাংলাদেশ</p>
  <p>📞 হটলাইন: <a href="tel:+8801729560032">০১৭২৯-৫৬০০৩২</a></p>
  <p style="margin-top:15px;font-size:13px;color:#64748b;">© <?= date('Y') ?> ময়মনসিংহ হোম সার্ভিস</p>
</footer>

<a href="tel:+8801729560032" class="floating-call">📞</a>

<script>
function previewPrescription(input) {
    const preview = document.getElementById('presPreview');
    const filename = document.getElementById('presFilename');
    const icon = document.getElementById('presIcon');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.style.display = 'block';
            icon.textContent = '✅';
            const size = (input.files[0].size / 1024).toFixed(1);
            filename.textContent = input.files[0].name + ' (' + size + ' KB)';
        };
        reader.readAsDataURL(input.files[0]);
    }
}

// ==========================================
// EmailJS এবং ডেটাবেজে ডেটা পাঠানোর স্ক্রিপ্ট
// ==========================================
emailjs.init("D55FkIbqmoEy-s9Ik"); // আপনার Public Key

document.getElementById('booking-form').addEventListener('submit', function(e) {
    e.preventDefault(); 
    
    const btn = document.getElementById('submit-btn');
    const originalText = btn.innerHTML;
    btn.innerHTML = '⏳ প্রসেস হচ্ছে...';
    btn.disabled = true;

    // ১. EmailJS দিয়ে মেইল পাঠানো (Form এর ডেটা দিয়ে)
    emailjs.sendForm('service_trrf2cc', 'template_fbshm89', this)
        .then(function() {
            // ২. মেইল সফল হলে ডেটাবেজে ডেটা সেভ করা
            const formData = new FormData(document.getElementById('booking-form'));
            
            fetch('save_booking.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                // সেভ সফল হলে success প্যারামিটার দিয়ে পেজ রিফ্রেশ করা
                window.location.href = 'book.php?success=1&service=<?= $service_id ?>';
            })
            .catch(error => {
                alert('সার্ভার সমস্যা! ডেটাবেজে সেভ হয়নি।');
                btn.innerHTML = originalText;
                btn.disabled = false;
            });

        }, function(error) {
            alert('মেইল পাঠাতে সমস্যা হয়েছে। আবার চেষ্টা করুন।\nError: ' + JSON.stringify(error));
            btn.innerHTML = originalText;
            btn.disabled = false;
        });
});
</script>

</body>
</html>