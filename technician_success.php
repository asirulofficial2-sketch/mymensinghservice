<?php
session_start();
$tech_id = $_SESSION['new_tech_id'] ?? '';
unset($_SESSION['new_tech_id']);
?>
<!DOCTYPE html>
<html lang="bn">
<head>
<meta charset="UTF-8">
<title>আবেদন সফল</title>
<style>
* { margin:0; padding:0; box-sizing:border-box; font-family:'Segoe UI',sans-serif; }
body {
  min-height: 100vh; display: flex; align-items: center; justify-content: center;
  background: linear-gradient(135deg, #dbeafe, #eff6ff); padding: 20px;
}
.box {
  background: white; padding: 50px 40px; border-radius: 25px;
  box-shadow: 0 15px 50px rgba(0,0,0,0.1);
  max-width: 550px; text-align: center;
}
.check {
  font-size: 80px; display: block; margin-bottom: 20px;
  animation: pop 0.6s ease;
}
@keyframes pop {
  0% { transform: scale(0); }
  60% { transform: scale(1.2); }
  100% { transform: scale(1); }
}
h1 { color: #16a34a; margin-bottom: 15px; font-size: 26px; }
p { color: #475569; line-height: 1.7; margin-bottom: 20px; font-size: 15px; }
.id-display {
  background: linear-gradient(135deg, #1e3a8a, #3b82f6);
  color: white; padding: 25px; border-radius: 18px;
  margin: 25px 0; box-shadow: 0 10px 30px rgba(30,58,138,0.3);
}
.id-display .label {
  font-size: 13px; opacity: 0.9; margin-bottom: 8px;
  letter-spacing: 1px; text-transform: uppercase;
}
.id-display .value {
  font-size: 28px; font-weight: 800; letter-spacing: 2px;
  font-family: 'Courier New', monospace;
}
.note {
  background: #fef3c7; color: #92400e;
  padding: 15px; border-radius: 12px;
  font-size: 13px; margin-bottom: 25px;
  line-height: 1.7; text-align: left;
}
.btn {
  display: inline-block; background: #2563eb; color: white;
  padding: 13px 30px; border-radius: 25px;
  text-decoration: none; font-weight: 600;
  transition: 0.3s;
}
.btn:hover { background: #1e40af; transform: translateY(-2px); }
</style>
</head>
<body>
<div class="box">
  <span class="check">✅</span>
  <h1>আবেদন সফলভাবে জমা হয়েছে!</h1>

  <?php if ($tech_id): ?>
  <p>আপনার ইউনিক ID নিচে দেওয়া হলো — <b>এটা সংরক্ষণ করুন!</b></p>
  
  <div class="id-display">
    <div class="label">আপনার টেকনিশিয়ান আইডি</div>
    <div class="value"><?= htmlspecialchars($tech_id) ?></div>
  </div>

  <div class="note">
    <b>📌 গুরুত্বপূর্ণ:</b><br>
    • অ্যাডমিন অনুমোদন দেওয়ার পর আপনি কাজ পাবেন<br>
    • ID টা স্ক্রিনশট নিয়ে বা লিখে রাখুন<br>
    • অনুমোদনের পর আপনাকে ফোনে জানানো হবে
  </div>
  <?php else: ?>
  <p>আপনার আবেদন জমা হয়েছে। অ্যাডমিন যাচাই করে অনুমোদন দেবেন।</p>
  <?php endif; ?>

  <a href="index.php" class="btn">🏠 হোমে ফিরে যান</a>
</div>
</body>
</html>