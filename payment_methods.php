<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'db.php';

$type = $_GET['type'] ?? 'shop'; // shop বা service
$id = intval($_GET['id'] ?? 0);

if (!$id) {
    die("ভুল লিংক। <a href='index.php'>হোমে যান</a>");
}

$data = null;

if ($type === 'shop') {
    $stmt = $conn->prepare("SELECT * FROM shop_orders WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $data = $stmt->get_result()->fetch_assoc();
} else {
    $stmt = $conn->prepare("SELECT b.*, s.name as service_name, s.icon as service_icon 
                            FROM bookings b 
                            LEFT JOIN services s ON b.service_id = s.id 
                            WHERE b.id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $data = $stmt->get_result()->fetch_assoc();
}

if (!$data) {
    die("অর্ডার পাওয়া যায়নি।");
}

$success = '';
$error = '';

// ============ পেমেন্ট সাবমিট ============
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $method = trim($_POST['payment_method'] ?? '');
    $trx = trim($_POST['transaction_id'] ?? '');
    
    if (!$method || !$trx) {
        $error = "পেমেন্ট মেথড ও TrxID দুটোই দিতে হবে";
    } else {
        if ($type === 'shop') {
            $stmt = $conn->prepare("UPDATE shop_orders SET payment_method=?, payment_number='01729560032', transaction_id=?, payment_status='pending' WHERE id=?");
            $stmt->bind_param("ssi", $method, $trx, $id);
        } else {
            $stmt = $conn->prepare("UPDATE bookings SET payment_method=?, payment_number='01729560032', transaction_id=?, payment_status='pending' WHERE id=?");
            $stmt->bind_param("ssi", $method, $trx, $id);
        }
        $stmt->execute();
        $success = "✅ আপনার পেমেন্ট তথ্য জমা হয়েছে!<br>অ্যাডমিন যাচাই করে অর্ডার কনফার্ম করবে।";
    }
}

$total = $data['total'] ?? 0;
?>
<!DOCTYPE html>
<html lang="bn">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>পেমেন্ট করুন</title>
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
header a {
  color: white; text-decoration: none; background: rgba(255,255,255,0.15);
  padding: 9px 16px; border-radius: 25px; font-size: 14px;
}

.hero {
  text-align: center; padding: 40px 20px 25px;
  background: linear-gradient(135deg, #dcfce7, #f0fdf4);
}
.hero h2 { font-size: 28px; color: #166534; margin-bottom: 10px; font-weight: 800; }
.hero p { font-size: 15px; color: #475569; }

.container { max-width: 700px; margin: 30px auto; padding: 0 20px 60px; }

.card {
  background: white; padding: 30px; border-radius: 20px;
  box-shadow: 0 4px 20px rgba(0,0,0,0.08); margin-bottom: 20px;
}

.order-info {
  background: linear-gradient(135deg, #dbeafe, #eff6ff);
  padding: 20px; border-radius: 15px; margin-bottom: 25px;
  border: 2px solid #3b82f6; text-align: center;
}
.order-info .label {
  font-size: 13px; color: #64748b; margin-bottom: 5px;
}
.order-info .value {
  font-size: 26px; font-weight: 800; color: #1e3a8a;
}

.amount-box {
  background: linear-gradient(135deg, #fef3c7, #fde68a);
  padding: 25px; border-radius: 15px; text-align: center;
  margin-bottom: 25px; border: 2px solid #f59e0b;
}
.amount-box .label { font-size: 14px; color: #92400e; margin-bottom: 8px; }
.amount-box .amount { font-size: 42px; font-weight: 800; color: #92400e; }

h3 {
  color: #1e3a8a; font-size: 18px; margin-bottom: 20px;
  padding-bottom: 12px; border-bottom: 2px solid #f1f5f9;
}

/* পেমেন্ট মেথড */
.methods {
  display: grid; grid-template-columns: repeat(3, 1fr);
  gap: 12px; margin-bottom: 25px;
}
.method-card {
  background: white; padding: 20px 15px;
  border: 3px solid #e2e8f0; border-radius: 15px;
  text-align: center; cursor: pointer;
  transition: 0.3s;
}
.method-card:hover {
  border-color: #3b82f6;
  transform: translateY(-3px);
}
.method-card.selected {
  border-color: #16a34a;
  background: #f0fdf4;
  box-shadow: 0 4px 15px rgba(22,163,74,0.2);
}
.method-card input {
  display: none;
}
.method-card .logo {
  font-size: 40px; margin-bottom: 8px; display: block;
}
.method-card .name {
  font-size: 14px; font-weight: 700; color: #1e3a8a;
}
.method-card .number {
  font-size: 12px; color: #16a34a; font-weight: 600;
  margin-top: 5px;
}

/* পাঠানোর নির্দেশনা */
.steps-box {
  background: #fef3c7; color: #92400e;
  padding: 18px; border-radius: 12px;
  margin-bottom: 20px; line-height: 1.9;
  border-left: 4px solid #f59e0b;
  font-size: 14px;
}
.steps-box b { color: #78350f; }

label { display: block; margin-bottom: 8px; font-weight: 600; color: #334155; font-size: 14px; }
input[type=text], input[type=tel] {
  width: 100%; padding: 14px 16px; margin-bottom: 18px;
  border: 2px solid #e2e8f0; border-radius: 10px;
  font-size: 15px; font-family: inherit;
}
input:focus { outline: none; border-color: #3b82f6; }

.btn {
  width: 100%; padding: 16px;
  background: linear-gradient(135deg, #16a34a, #15803d);
  color: white; border: none; border-radius: 12px;
  font-size: 17px; font-weight: 700; cursor: pointer;
  transition: 0.3s; font-family: inherit;
}
.btn:hover { transform: translateY(-3px); box-shadow: 0 10px 25px rgba(22,163,74,0.4); }

.success-box {
  background: #dcfce7; border: 2px solid #16a34a;
  color: #166534; padding: 30px; border-radius: 15px;
  text-align: center;
}
.success-box .big { font-size: 70px; display: block; margin-bottom: 15px; }
.success-box h3 { color: #166534; font-size: 22px; border: none; padding: 0; margin-bottom: 10px; }
.success-box a {
  display: inline-block; margin-top: 15px; color: #2563eb;
  text-decoration: none; font-weight: 700;
}

.error-box {
  background: #fee2e2; color: #dc2626;
  padding: 14px; border-radius: 10px;
  margin-bottom: 20px; font-weight: 600;
}

footer {
  background: #0f172a; color: #cbd5e1; text-align: center;
  padding: 30px 20px;
}
footer a { color: #60a5fa; text-decoration: none; }

@media (max-width: 600px) {
  header h1 { font-size: 16px; }
  .methods { grid-template-columns: 1fr; }
  .hero h2 { font-size: 24px; }
  .amount-box .amount { font-size: 34px; }
}
</style>
</head>
<body>

<header>
  <h1>🏠 ময়মনসিংহ হোম সার্ভিস</h1>
  <a href="index.php">← হোমে ফিরুন</a>
</header>

<div class="hero">
  <h2>💳 পেমেন্ট করুন</h2>
  <p>নিচের যেকোনো মাধ্যমে টাকা পাঠান, তারপর TrxID সাবমিট করুন</p>
</div>

<div class="container">

<?php if ($success): ?>
  <div class="card">
    <div class="success-box">
      <span class="big">🎉</span>
      <h3>পেমেন্ট তথ্য জমা হয়েছে!</h3>
      <p><?= $success ?></p>
      <a href="index.php">← হোমে ফিরুন</a>
    </div>
  </div>

<?php else: ?>

  <div class="card">
    
    <?php if ($error): ?>
      <div class="error-box">❌ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="order-info">
      <div class="label">অর্ডার নম্বর</div>
      <div class="value">#<?= $data['id'] ?></div>
    </div>

    <div class="amount-box">
      <div class="label">💰 আপনাকে পাঠাতে হবে</div>
      <div class="amount">৳<?= number_format($total) ?></div>
    </div>

    <h3>📱 পেমেন্ট মেথড বাছুন</h3>

    <form method="POST" id="paymentForm">
      
      <div class="methods">
        <label class="method-card" onclick="selectMethod(this, 'bKash')">
          <input type="radio" name="payment_method" value="bKash" required>
          <span class="logo">💗</span>
          <div class="name">বিকাশ</div>
          <div class="number">01729560032</div>
        </label>
        
        <label class="method-card" onclick="selectMethod(this, 'Nagad')">
          <input type="radio" name="payment_method" value="Nagad">
          <span class="logo">🟠</span>
          <div class="name">নগদ</div>
          <div class="number">01729560032</div>
        </label>
        
        <label class="method-card" onclick="selectMethod(this, 'Rocket')">
          <input type="radio" name="payment_method" value="Rocket">
          <span class="logo">🚀</span>
          <div class="name">রকেট</div>
          <div class="number">01729560032</div>
        </label>
      </div>

      <div class="steps-box">
        <b>📌 কীভাবে পাঠাবেন:</b><br>
        ১. উপরের যেকোনো একটা সিলেক্ট করুন<br>
        ২. সেই অ্যাপে <b>01729560032</b> নাম্বারে <b>৳<?= number_format($total) ?></b> Send Money করুন<br>
        ৩. ট্রানজেকশনের শেষে <b>TrxID</b> (যেমন: 8N7A2K9L) পাবেন<br>
        ৪. নিচে সেই TrxID লিখে সাবমিট করুন
      </div>

      <label>🆔 ট্রানজেকশন আইডি (TrxID) *</label>
      <input type="text" name="transaction_id" required 
             placeholder="যেমন: 8N7A2K9L"
             style="text-transform: uppercase;">

      <button type="submit" class="btn">✅ পেমেন্ট কনফার্ম করুন</button>
    </form>

  </div>

<?php endif; ?>

</div>

<footer>
  <p>📍 ময়মনসিংহ, বাংলাদেশ</p>
  <p>📞 হটলাইন: <a href="tel:+8801729560032">০১৭২৯-৫৬০০৩২</a></p>
  <p style="margin-top:15px;font-size:13px;color:#64748b;">© <?= date('Y') ?> ময়মনসিংহ হোম সার্ভিস</p>
</footer>

<script>
function selectMethod(el, method) {
    document.querySelectorAll('.method-card').forEach(c => c.classList.remove('selected'));
    el.classList.add('selected');
}
</script>

</body>
</html>