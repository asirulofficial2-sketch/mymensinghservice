<?php
session_start();
$order_id = $_SESSION['last_shop_order'] ?? '';
unset($_SESSION['last_shop_order']);
?>
<!DOCTYPE html>
<html lang="bn">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>অর্ডার সফল</title>
<style>
* { margin:0; padding:0; box-sizing:border-box; font-family:'Segoe UI',sans-serif; }
body {
  min-height: 100vh; display: flex; align-items: center; justify-content: center;
  background: linear-gradient(135deg, #dbeafe, #eff6ff); padding: 20px;
}
.box {
  background: white; padding: 50px 40px; border-radius: 25px;
  box-shadow: 0 15px 50px rgba(0,0,0,0.1);
  max-width: 550px; width: 100%; text-align: center;
}
.check {
  font-size: 90px; display: block; margin-bottom: 20px;
  animation: pop 0.6s ease;
}
@keyframes pop {
  0% { transform: scale(0); }
  60% { transform: scale(1.2); }
  100% { transform: scale(1); }
}
h1 { color: #16a34a; margin-bottom: 15px; font-size: 28px; font-weight: 800; }
p { color: #475569; line-height: 1.8; margin-bottom: 15px; font-size: 15px; }
.order-id {
  background: linear-gradient(135deg, #1e3a8a, #3b82f6);
  color: white; padding: 22px; border-radius: 15px;
  margin: 25px 0; box-shadow: 0 10px 30px rgba(30,58,138,0.3);
}
.order-id .label {
  font-size: 13px; opacity: 0.9; letter-spacing: 1px;
  margin-bottom: 8px; text-transform: uppercase;
}
.order-id .value {
  font-size: 30px; font-weight: 800;
  font-family: 'Courier New', monospace; letter-spacing: 2px;
}
.note-box {
  background: #fef3c7; color: #92400e;
  padding: 20px; border-radius: 12px;
  font-size: 13.5px; text-align: left;
  line-height: 1.9; margin-bottom: 25px;
  border-left: 4px solid #f59e0b;
}
.btn {
  display: inline-block; background: #2563eb; color: white;
  padding: 14px 32px; border-radius: 30px;
  text-decoration: none; font-weight: 700; font-size: 15px;
  margin: 5px; transition: 0.3s;
}
.btn:hover { transform: translateY(-3px); }
.btn-green { background: linear-gradient(135deg, #16a34a, #15803d); }
</style>
</head>
<body>
<div class="box">
  <span class="check">✅</span>
  <h1>অর্ডার সফল হয়েছে!</h1>
  <p>আপনার অর্ডার আমাদের কাছে পৌঁছেছে।</p>

  <?php if ($order_id): ?>
  <div class="order-id">
    <div class="label">আপনার অর্ডার নম্বর</div>
    <div class="value">#<?= htmlspecialchars($order_id) ?></div>
  </div>
  <?php endif; ?>

  <div class="note-box">
    <b>📌 পরবর্তী ধাপ:</b><br>
    ১. আমরা শীঘ্রই আপনাকে ফোন করে অর্ডার কনফার্ম করব<br>
    ২. ডেলিভারি ঠিকানা যাচাই করব<br>
    ৩. ক্যাশ অন ডেলিভারি — পণ্য হাতে পেয়ে টাকা দিবেন<br>
    ৪. ডেলিভারি চার্জ ফ্রি
  </div>

  <div>
    <a href="shop.php" class="btn btn-green">🛍️ আরো কেনাকাটা</a>
    <a href="index.php" class="btn">🏠 হোম</a>
  </div>
</div>
</body>
</html>