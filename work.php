<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'db.php';
$marquee = null;
$marqueeResult = $conn->query("SELECT * FROM marquee WHERE active=1 LIMIT 1");
if ($marqueeResult && $marqueeResult->num_rows > 0) {
    $marquee = $marqueeResult->fetch_assoc();
}
$categories = $conn->query("SELECT * FROM work_categories WHERE active=1 ORDER BY sort_order");
?>
<!DOCTYPE html>
<html lang="bn">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>কাজ করতে চাই — রেজিস্ট্রেশন</title>
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
  transition: 0.3s; font-weight: 500;
}
header nav a:hover { background: rgba(255,255,255,0.3); }
header nav a.btn-shop { background: #8b5cf6; font-weight: 700; }
header nav a.btn-shop:hover { background: #7c3aed; }
header nav a.btn-work { background: #16a34a; }
header nav a.btn-work:hover { background: #15803d; }
header nav a.btn-order { background: #fbbf24; color: #78350f; font-weight: 700; }
header nav a.btn-order:hover { background: #f59e0b; }

.hero {
  text-align: center; padding: 45px 20px 30px;
  background: linear-gradient(135deg, #dbeafe, #eff6ff);
}
.hero h2 { font-size: 30px; color: #1e3a8a; margin-bottom: 12px; }
.hero p { font-size: 15px; color: #475569; max-width: 600px; margin: 0 auto; }

.container { max-width: 700px; margin: 30px auto; padding: 0 20px 50px; }
.card {
  background: white; padding: 35px 30px; border-radius: 20px;
  box-shadow: 0 4px 20px rgba(0,0,0,0.08);
}
.card h3 { color: #1e3a8a; margin-bottom: 25px; font-size: 20px; text-align: center; }

.section-title {
  background: #eff6ff; color: #1e40af;
  padding: 12px 16px; border-radius: 10px;
  font-weight: 700; font-size: 14px;
  margin: 25px 0 15px; border-left: 4px solid #3b82f6;
  display: flex; justify-content: space-between; align-items: center;
  gap: 10px; flex-wrap: wrap;
}
.section-title:first-of-type { margin-top: 0; }

.admin-link {
  background: #16a34a;
  color: white;
  padding: 6px 14px;
  border-radius: 8px;
  text-decoration: none;
  font-size: 12px;
  font-weight: 600;
  transition: 0.2s;
  white-space: nowrap;
}
.admin-link:hover { background: #15803d; }

label { display: block; margin-bottom: 6px; font-weight: 600; color: #334155; font-size: 14px; }
label .req { color: #dc2626; }
input[type=text], input[type=tel], input[type=email], input[type=password], select, textarea {
  width: 100%; padding: 12px 14px; margin-bottom: 18px;
  border: 2px solid #e2e8f0; border-radius: 10px;
  font-size: 15px; font-family: inherit; background: white;
}
input:focus, select:focus, textarea:focus {
  outline: none; border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59,130,246,0.1);
}
textarea { min-height: 80px; resize: vertical; }

.file-upload {
  border: 2px dashed #cbd5e1; padding: 18px;
  border-radius: 10px; text-align: center; margin-bottom: 18px;
  background: #f8fafc; cursor: pointer;
  transition: 0.2s;
}
.file-upload:hover { border-color: #3b82f6; background: #eff6ff; }
.file-upload input[type=file] { display: none; }
.file-upload .icon { font-size: 30px; margin-bottom: 6px; display: block; }
.file-upload .text { font-size: 13px; color: #64748b; }
.file-upload .filename { color: #16a34a; font-weight: 600; font-size: 13px; margin-top: 6px; }

.info-box {
  background: #fef3c7; color: #92400e;
  padding: 14px 18px; border-radius: 12px;
  font-size: 13px; margin-bottom: 22px; line-height: 1.6;
  border-left: 4px solid #f59e0b;
}
.btn {
  width: 100%; padding: 16px;
  background: linear-gradient(135deg,#16a34a,#15803d);
  color: white; border: none; border-radius: 12px;
  font-size: 17px; font-weight: 700; cursor: pointer;
  transition: 0.3s;
}
.btn:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(22,163,74,0.35); }
.error {
  background: #fee2e2; color: #dc2626;
  padding: 12px; border-radius: 10px; margin-bottom: 15px;
  font-size: 14px;
}
.links { text-align: center; margin-top: 20px; font-size: 14px; color: #64748b; }
.links a { color: #2563eb; text-decoration: none; font-weight: 600; }

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
  .hero h2 { font-size: 24px; }
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
  <nav>
    <a href="book.php" class="btn-order">📞 সার্ভিস অর্ডার করুন</a>
    <a href="work.php" class="btn-work">💼 কাজ করতে চাই</a>
    <a href="shop.php" class="btn-shop">🛒 কেনাকাটা করুন</a>
      <a href="give_review.php" style="background:#f59e0b; color:white; font-weight:700;">⭐ রিভিউ দিন</a>
  </nav>
</header>
    <?php if ($marquee): ?>
<div style="background: linear-gradient(90deg, #fef3c7, #fde68a, #fef3c7); color: #92400e; padding: 12px 0; font-weight: 700; font-size: 15px; border-bottom: 2px solid #fbbf24; overflow: hidden;">
  <marquee behavior="scroll" direction="left" scrollamount="6" onmouseover="this.stop()" onmouseout="this.start()">
    📢 <?= htmlspecialchars($marquee['message']) ?>
  </marquee>
</div>
<?php endif; ?>

<div class="hero">
  <h2>💼 কাজ করতে চাই</h2>
  <p>আমাদের সাথে কাজ করুন — ঘরে বসে কাজ পান</p>
</div>

<div class="container">
  <div class="card">
    <h3>📝 আবেদন ফর্ম</h3>

    <div class="info-box">
      ⚠️ সঠিক তথ্য দিন। অ্যাডমিন যাচাই করে অনুমোদন দেবেন।<br>
      সব <span style="color:#dc2626">*</span> চিহ্নিত ফিল্ড অবশ্যই পূরণ করতে হবে।
    </div>

    <?php if (isset($_GET['error'])): ?>
      <div class="error">
        <?php
        $err = $_GET['error'];
        if ($err === 'missing') echo "সব প্রয়োজনীয় তথ্য পূরণ করুন।";
        elseif ($err === 'duplicate') echo "এই ফোন নম্বর দিয়ে আগেই আবেদন করা হয়েছে।";
        elseif ($err === 'mismatch') echo "দুইবার পাসওয়ার্ড এক হয়নি।";
        elseif ($err === 'short') echo "পাসওয়ার্ড কমপক্ষে ৬ অক্ষরের হতে হবে।";
        elseif ($err === 'photo') echo "ছবি আপলোডে সমস্যা হয়েছে। আবার চেষ্টা করুন।";
        else echo "সমস্যা হয়েছে, আবার চেষ্টা করুন।";
        ?>
      </div>
    <?php endif; ?>

    <form action="save_work.php" method="POST" enctype="multipart/form-data">

      <div class="section-title">
        <span>🎯 কাজের ধরন</span>
      </div>

      <label>প্রধান ক্যাটাগরি <span class="req">*</span></label>
      <select name="category_id" id="category_id" required>
        <option value="">-- ক্যাটাগরি বাছুন --</option>
        <?php 
        $categories->data_seek(0);
        while ($c = $categories->fetch_assoc()): 
        ?>
          <option value="<?= $c['id'] ?>">
            <?= htmlspecialchars(($c['icon'] ?? '') . ' ' . $c['name']) ?>
          </option>
        <?php endwhile; ?>
      </select>

      <label>সাব-ক্যাটাগরি <span class="req">*</span></label>
      <select name="subcategory_id" id="subcategory_id" required>
        <option value="">-- আগে ক্যাটাগরি বাছুন --</option>
      </select>

      <div class="section-title">👤 ব্যক্তিগত তথ্য</div>

      <label>পূর্ণ নাম <span class="req">*</span></label>
      <input type="text" name="name" required placeholder="আপনার পূর্ণ নাম">

      <label>মোবাইল নম্বর <span class="req">*</span> (এটা দিয়ে লগইন করবেন)</label>
      <input type="tel" name="phone" required placeholder="01XXXXXXXXX"
             pattern="01[0-9]{9}">

      <label>ইমেইল (ঐচ্ছিক)</label>
      <input type="email" name="email" placeholder="your@email.com">

      <label>ঠিকানা <span class="req">*</span></label>
      <textarea name="address" required placeholder="বাসা/রোড/এলাকা, ময়মনসিংহ"></textarea>

      <div class="section-title">🔐 পাসওয়ার্ড</div>

      <label>পাসওয়ার্ড <span class="req">*</span> (কমপক্ষে ৬ অক্ষর)</label>
      <input type="password" name="password" required minlength="6">

      <label>পাসওয়ার্ড আবার লিখুন <span class="req">*</span></label>
      <input type="password" name="password2" required minlength="6">

      <div class="section-title">🆔 পরিচয়পত্র (NID)</div>

      <label>NID নম্বর <span class="req">*</span></label>
      <input type="text" name="nid" required placeholder="জাতীয় পরিচয়পত্র নম্বর">

      <label>NID সামনের ছবি <span class="req">*</span></label>
      <label class="file-upload" for="nid_front">
        <input type="file" name="nid_front" id="nid_front" accept="image/*" required 
               onchange="showFileName(this)">
        <span class="icon">📷</span>
        <span class="text">NID সামনের দিক — ছবি আপলোড করুন</span>
        <div class="filename"></div>
      </label>

      <label>NID পিছনের ছবি <span class="req">*</span></label>
      <label class="file-upload" for="nid_back">
        <input type="file" name="nid_back" id="nid_back" accept="image/*" required 
               onchange="showFileName(this)">
        <span class="icon">📷</span>
        <span class="text">NID পিছনের দিক — ছবি আপলোড করুন</span>
        <div class="filename"></div>
      </label>

      <div class="section-title">📸 প্রোফাইল ছবি</div>

      <label class="file-upload" for="photo">
        <input type="file" name="photo" id="photo" accept="image/*" required 
               onchange="showFileName(this)">
        <span class="icon">👤</span>
        <span class="text">আপনার স্পষ্ট ছবি আপলোড করুন</span>
        <div class="filename"></div>
      </label>

      <div class="section-title">💼 অভিজ্ঞতা ও শিক্ষা</div>

      <label>অভিজ্ঞতা</label>
      <select name="experience">
        <option value="">-- বেছে নিন --</option>
        <option value="১ বছরের কম">১ বছরের কম</option>
        <option value="১-৩ বছর">১-৩ বছর</option>
        <option value="৩-৫ বছর">৩-৫ বছর</option>
        <option value="৫+ বছর">৫+ বছর</option>
      </select>

      <label>শিক্ষাগত যোগ্যতা (টিউটরদের জন্য প্রযোজ্য)</label>
      <input type="text" name="education" placeholder="যেমন: স্নাতক, এইচএসসি">

      <button type="submit" class="btn">✅ আবেদন জমা দিন</button>
    </form>

    <div class="links">
      ইতিমধ্যে রেজিস্ট্রেশন করেছেন? <a href="work_login.php">লগইন করুন</a>
    </div>
  </div>
</div>

<footer>
  <p>📍 ময়মনসিংহ, বাংলাদেশ</p>
  <p>📞 হটলাইন: <a href="tel:+8801729560032">০১৭২৯-৫৬০০৩২</a></p>
  <p style="margin-top:15px;font-size:13px;color:#64748b;">© <?= date('Y') ?> ময়মনসিংহ হোম সার্ভিস</p>
</footer>

<script>
document.getElementById('category_id').addEventListener('change', function() {
    const catId = this.value;
    const subSelect = document.getElementById('subcategory_id');

    if (!catId) {
        subSelect.innerHTML = '<option value="">-- আগে ক্যাটাগরি বাছুন --</option>';
        return;
    }

    subSelect.innerHTML = '<option value="">-- লোড হচ্ছে... --</option>';

    fetch('get_subcategories.php?category_id=' + catId)
        .then(r => r.json())
        .then(data => {
            let html = '<option value="">-- সাব-ক্যাটাগরি বাছুন --</option>';
            data.forEach(item => {
                html += `<option value="${item.id}">${item.name}</option>`;
            });
            subSelect.innerHTML = html;
        })
        .catch(err => {
            subSelect.innerHTML = '<option value="">-- লোড ব্যর্থ --</option>';
        });
});

function showFileName(input) {
    const parent = input.closest('.file-upload');
    const nameDiv = parent.querySelector('.filename');
    if (input.files && input.files[0]) {
        const size = (input.files[0].size / 1024).toFixed(1);
        nameDiv.textContent = '✅ ' + input.files[0].name + ' (' + size + ' KB)';
    }
}
</script>

</body>
</html>