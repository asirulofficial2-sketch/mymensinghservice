<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'includes/auth.php';
include '../db.php';

$msg = '';

// আপডেট
if (isset($_POST['update_marquee'])) {
    $message = trim($_POST['message'] ?? '');
    $active = isset($_POST['active']) ? 1 : 0;
    
    $check = $conn->query("SELECT COUNT(*) as c FROM marquee")->fetch_assoc()['c'];
    
    if ($check > 0) {
        $stmt = $conn->prepare("UPDATE marquee SET message=?, active=? WHERE id=1");
        $stmt->bind_param("si", $message, $active);
    } else {
        $stmt = $conn->prepare("INSERT INTO marquee (message, active) VALUES (?, ?)");
        $stmt->bind_param("si", $message, $active);
    }
    $stmt->execute();
    $msg = "✅ স্ক্রলিং টেক্সট আপডেট হয়েছে";
}

$marquee = $conn->query("SELECT * FROM marquee WHERE id=1")->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="bn">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>স্ক্রলিং টেক্সট</title>
<style>
* { margin:0; padding:0; box-sizing:border-box; font-family:'Segoe UI',sans-serif; }
body { background: #f5f7fa; }
.topbar { background: linear-gradient(135deg, #f59e0b, #d97706); color: white; padding: 15px 25px; display: flex; justify-content: space-between; align-items: center; }
.topbar h1 { font-size: 18px; }
.topbar a { color: white; text-decoration: none; background: rgba(255,255,255,0.2); padding: 8px 16px; border-radius: 20px; font-size: 14px; }
.container { max-width: 600px; margin: 40px auto; padding: 0 20px; }
.card { background: white; padding: 30px; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
h2 { color: #d97706; margin-bottom: 20px; font-size: 20px; }
.msg { background: #dcfce7; color: #16a34a; padding: 12px; border-radius: 10px; margin-bottom: 20px; font-weight: bold; }
label { display: block; margin-bottom: 8px; font-weight: 600; color: #334155; font-size: 14px; }
textarea { width: 100%; padding: 15px; border: 2px solid #e2e8f0; border-radius: 10px; min-height: 120px; font-size: 15px; font-family: inherit; margin-bottom: 15px; }
textarea:focus { outline: none; border-color: #d97706; }
.checkbox-row { display: flex; align-items: center; gap: 10px; margin-bottom: 20px; font-size: 15px; }
.checkbox-row input { width: 20px; height: 20px; margin: 0; }
.btn { width: 100%; padding: 15px; background: #d97706; color: white; border: none; border-radius: 10px; font-size: 16px; font-weight: bold; cursor: pointer; }
.btn:hover { background: #b45309; }
.preview { margin-top: 25px; background: #fef3c7; padding: 15px 0; border-radius: 10px; overflow: hidden; border: 2px solid #fbbf24; }
.preview-title { font-size: 13px; font-weight: bold; color: #92400e; padding: 0 15px 10px; }
</style>
</head>
<body>

<div class="topbar">
  <h1>🛠️ অ্যাডমিন প্যানেল</h1>
  <div>
    <a href="dashboard.php">ড্যাশবোর্ড</a>
    <a href="logout.php">লগআউট</a>
  </div>
</div>

<div class="container">
  <div class="card">
    <h2>📢 স্ক্রলিং টেক্সট ম্যানেজ</h2>
    
    <?php if ($msg): ?>
      <div class="msg"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>

    <form method="POST">
      <label>টেক্সট *</label>
      <textarea name="message" required placeholder="যেমন: 🏠 ময়মনসিংহ সার্ভিস হাব — বাসার সব সমস্যার সমাধান এক ক্লিকে! | 📞 হটলাইন: ০১৭২৯-৫৬০০৩২"><?= htmlspecialchars($marquee['message'] ?? '') ?></textarea>
      
      <div class="checkbox-row">
        <input type="checkbox" name="active" id="active" <?= (!$marquee || $marquee['active']) ? 'checked' : '' ?>>
        <label for="active" style="margin:0;">সক্রিয় (সাইটে দেখাবে)</label>
      </div>
      
      <button type="submit" name="update_marquee" class="btn">💾 আপডেট করুন</button>
    </form>

    <?php if (!empty($marquee['message'])): ?>
    <div class="preview">
      <div class="preview-title">📺 লাইভ প্রিভিউ:</div>
      <marquee behavior="scroll" direction="left" scrollamount="6">
        📢 <?= htmlspecialchars($marquee['message']) ?>
      </marquee>
    </div>
    <?php endif; ?>
  </div>
</div>

</body>
</html>