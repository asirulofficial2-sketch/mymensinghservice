<?php
session_start();
$tech_id = $_SESSION['new_tech_id'] ?? '';
$tech_name = $_SESSION['new_tech_name'] ?? '';
unset($_SESSION['new_tech_id'], $_SESSION['new_tech_name']);
?>
<!DOCTYPE html>
<html lang="bn">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>আবেদন সফল</title>
<style>
* { margin:0; padding:0; box-sizing:border-box; font-family:'Segoe UI',sans-serif; }
body {
  min-height: 100vh; display: flex; align-items: center; justify-content: center;
  background: linear-gradient(135deg, #dbeafe, #eff6ff); padding: 20px;
}
.box {
  background: white; padding: 50px 35px; border-radius: 25px;
  box-shadow: 0 15px 50px rgba(0,0,0,0.1);
  max-width: 550px; width: 100%; text-align: center;
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
.name-display {
  color: #1e3a8a; font-size: 18px; font-weight: 700;
  margin-bottom: 15px;
}
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
  font-size: 26px; font-weight: 800; letter-spacing: 2px;
  font-family: 'Courier New', monospace;
}
.note {
  background: #fef3c7; color: #92400e;
  padding: 18px; border-radius: 12px;
  font-size: 13px; margin-bottom: 25px;
  line-height: 1.8; text-align: left;
}
.note b { color: #78350f; }
.btn {
  display: inline-block; background: #2563eb; color: white;
  padding: 14px 30px; border-radius: 25px;
  text-decoration: none; font-weight: 600;
  transition: 0.3s; margin: 0 5px 8px;
}
.btn:hover { background: #1e40af; transform: translateY(-2px); }
.btn-outline {
  background: white; color: #2563eb;
  border: 2px solid #2563eb;
}
.btn-outline:hover { background: #eff6ff; }
.screenshot-hint {
  background: #dbeafe; color: #1e40af;
  padding: 12px; border-radius: 10px;
  font-size: 13px; margin-bottom: 20px;
}
</style>
</head>
<body>
<div class="box">
  <span class="check">✅</span>
  <h1>আবেদন সফলভাবে জমা হয়েছে!</h1>

  <?php if ($tech_name): ?>
    <div class="name-display">ধন্যবাদ, <?= htmlspecialchars($tech_name) ?>!</div>
  <?php endif; ?>

  <?php if ($tech_id): ?>
  <p>আপনার ইউনিক আইডি নিচে দেওয়া হলো:</p>
  
  <div class="id-display">
    <div class="label">আপনার আইডি নম্বর</div>
    <div class="value"><?= htmlspecialchars($tech_id) ?></div>
  </div>

  <div class="screenshot-hint">
    📸 এই আইডি স্ক্রিনশট নিয়ে সংরক্ষণ করুন!
  </div>

  <div class="note">
    <b>📌 পরবর্তী ধাপ:</b><br>
    ১. অ্যাডমিন আপনার আবেদন যাচাই করবেন<br>
    ২. অনুমোদন পেলে আপনি কাজ পাওয়া শুরু করবেন<br>
    ৩. অনুমোদনের পর ফোনে জানানো হবে<br>
    ৪. লগইন করতে পারবেন এই আইডি / ফোন দিয়ে
  </div>
  <?php else: ?>
  <p>আপনার আবেদন জমা হয়েছে। অ্যাডমিন যাচাই করে অনুমোদন দেবেন।</p>
  <?php endif; ?>

  <div>
    <a href="work_login.php" class="btn">🔐 লগইন করুন</a>
    <a href="index.php" class="btn btn-outline">🏠 হোম</a>
  </div>
</div>
</body>
</html>