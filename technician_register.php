<?php
include 'db.php';
$services = $conn->query("SELECT * FROM services WHERE active=1 ORDER BY id ASC");
?>
<!DOCTYPE html>
<html lang="bn">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>টেকনিশিয়ান / ড্রাইভার রেজিস্ট্রেশন</title>
<style>
* { margin:0; padding:0; box-sizing:border-box; font-family:'Segoe UI',sans-serif; }
body { background: #f5f7fa; }
header {
  background: linear-gradient(135deg, #1e3a8a, #3b82f6);
  color: white; padding: 15px 25px;
  display: flex; justify-content: space-between; align-items: center;
  flex-wrap: wrap; gap: 10px;
}
header h1 { font-size: 18px; }
header a {
  color: white; text-decoration: none; background: rgba(255,255,255,0.2);
  padding: 8px 16px; border-radius: 20px; font-size: 14px;
  margin-left: 8px;
}
.hero {
  text-align: center; padding: 45px 20px 30px;
  background: linear-gradient(135deg, #dbeafe, #eff6ff);
}
.hero h2 { font-size: 30px; color: #1e3a8a; margin-bottom: 12px; }
.hero p { font-size: 15px; color: #475569; max-width: 600px; margin: 0 auto; }
.container { max-width: 650px; margin: 30px auto; padding: 0 20px 40px; }
.card {
  background: white; padding: 35px 30px; border-radius: 20px;
  box-shadow: 0 4px 20px rgba(0,0,0,0.08);
}
.card h3 { color: #1e3a8a; margin-bottom: 25px; font-size: 20px; text-align: center; }
label { display: block; margin-bottom: 6px; font-weight: 600; color: #334155; font-size: 14px; }
input, select, textarea {
  width: 100%; padding: 12px 14px; margin-bottom: 18px;
  border: 2px solid #e2e8f0; border-radius: 10px;
  font-size: 15px; font-family: inherit; background: white;
}
input:focus, select:focus, textarea:focus {
  outline: none; border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59,130,246,0.1);
}
textarea { min-height: 80px; resize: vertical; }
.info-box {
  background: #fef3c7; color: #92400e;
  padding: 14px 18px; border-radius: 12px;
  font-size: 13px; margin-bottom: 22px; line-height: 1.6;
  border-left: 4px solid #f59e0b;
}
.btn {
  width: 100%; padding: 15px;
  background: linear-gradient(135deg,#2563eb,#1e40af);
  color: white; border: none; border-radius: 12px;
  font-size: 17px; font-weight: 700; cursor: pointer;
  transition: 0.3s;
}
.btn:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(37,99,235,0.35); }
.error {
  background: #fee2e2; color: #dc2626;
  padding: 12px; border-radius: 10px; margin-bottom: 15px;
}
</style>
</head>
<body>

<header>
  <h1>🏠 ময়মনসিংহ হোম সার্ভিস</h1>
  <div>
    <a href="index.php">হোম</a>
  </div>
</header>

<div class="hero">
  <h2>👷 টেকনিশিয়ান / ড্রাইভার রেজিস্ট্রেশন</h2>
  <p>আমাদের সাথে কাজ করুন — ঘরে বসে কাজ পান</p>
</div>

<div class="container">
  <div class="card">
    <h3>📝 আপনার তথ্য দিন</h3>

    <div class="info-box">
      ⚠️ সঠিক তথ্য দিন। অ্যাডমিন যাচাই করে অনুমোদন দেবেন।<br>
      অনুমোদনের পর আপনি একটা ইউনিক ID পাবেন।
    </div>

    <?php if (isset($_GET['error'])): ?>
      <div class="error">
        <?php
        $err = $_GET['error'];
        if ($err === 'missing') echo "সব প্রয়োজনীয় তথ্য পূরণ করুন।";
        elseif ($err === 'duplicate') echo "এই ফোন নম্বর দিয়ে আগেই আবেদন করা হয়েছে।";
        else echo "সমস্যা হয়েছে, আবার চেষ্টা করুন।";
        ?>
      </div>
    <?php endif; ?>

    <form action="save_technician.php" method="POST">
      <label>পূর্ণ নাম *</label>
      <input type="text" name="name" required placeholder="আপনার নাম">

      <label>মোবাইল নম্বর *</label>
      <input type="tel" name="phone" required placeholder="01XXXXXXXXX"
             pattern="01[0-9]{9}" title="১১ ডিজিটের বাংলাদেশি নম্বর">

      <label>ইমেইল (ঐচ্ছিক)</label>
      <input type="email" name="email" placeholder="your@email.com">

      <label>ঠিকানা *</label>
      <textarea name="address" required placeholder="বাসা/রোড/এলাকা, ময়মনসিংহ"></textarea>

      <label>কাজের ধরন *</label>
      <select name="service_id" required>
        <option value="">-- বেছে নিন --</option>
        <option value="0">🚗 ড্রাইভার</option>
        <?php while ($s = $services->fetch_assoc()): ?>
          <option value="<?= $s['id'] ?>">
            <?= htmlspecialchars($s['icon'] . ' ' . $s['name']) ?>
          </option>
        <?php endwhile; ?>
      </select>

      <label>অভিজ্ঞতা *</label>
      <select name="experience" required>
        <option value="">-- বেছে নিন --</option>
        <option value="১ বছরের কম">১ বছরের কম</option>
        <option value="১-৩ বছর">১-৩ বছর</option>
        <option value="৩-৫ বছর">৩-৫ বছর</option>
        <option value="৫+ বছর">৫+ বছর</option>
      </select>

      <label>NID নম্বর (ঐচ্ছিক)</label>
      <input type="text" name="nid" placeholder="জাতীয় পরিচয়পত্র নম্বর">

      <button type="submit" class="btn">✅ আবেদন জমা দিন</button>
    </form>
  </div>
</div>

</body>
</html>