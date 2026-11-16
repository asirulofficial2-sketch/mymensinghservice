<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'includes/auth.php';
include '../db.php';

// ============ স্ট্যাটাস আপডেট ============
if (isset($_POST['update_status'])) {
    $bid = intval($_POST['booking_id']);
    $status = $_POST['status'];
    $allowed = ['pending','confirmed','on_the_way','completed','cancelled'];
    if (in_array($status, $allowed)) {
        $stmt = $conn->prepare("UPDATE bookings SET status=? WHERE id=?");
        
        $stmt->bind_param("si", $status, $bid);
        $stmt->execute();
    }
    header("Location: bookings.php");
    exit;
}

// ============ ডিলিট (রিভিউ ও প্রেসক্রিপশন সহ) ============
if (isset($_GET['delete'])) {
    $bid = intval($_GET['delete']);
    
    // ১. প্রেসক্রিপশন কলাম আছে কি না চেক করা
    $checkCol = $conn->query("SHOW COLUMNS FROM bookings LIKE 'prescription'");
    if ($checkCol && $checkCol->num_rows > 0) {
        $row = $conn->query("SELECT prescription FROM bookings WHERE id=$bid")->fetch_assoc();
        if ($row && !empty($row['prescription']) && file_exists('../uploads/prescriptions/' . $row['prescription'])) {
            unlink('../uploads/prescriptions/' . $row['prescription']);
        }
    }
    
    // ২. আগে রিভিউ ডিলিট
    $conn->query("DELETE FROM service_reviews WHERE booking_id=$bid");
    
    // ৩. তারপর বুকিং ডিলিট
    $conn->query("DELETE FROM bookings WHERE id=$bid");
    
    header("Location: bookings.php?msg=deleted");
    exit;
}

// ============ পেমেন্ট যাচাই ============
if (isset($_GET['verify_payment'])) {
    $bid = intval($_GET['verify_payment']);
    $conn->query("UPDATE bookings SET payment_status='paid' WHERE id=$bid");
    header("Location: bookings.php");
    exit;
}

// ============ ফিল্টার ============
$filter = $_GET['status'] ?? 'all';

$sql = "SELECT b.*, s.name as service_name, s.icon as service_icon 
        FROM bookings b 
        LEFT JOIN services s ON b.service_id = s.id";
if ($filter !== 'all') {
    $sql .= " WHERE b.status = '" . $conn->real_escape_string($filter) . "'";
}
$sql .= " ORDER BY b.id DESC";
$bookings = $conn->query($sql);

$counts = [];
foreach (['all','pending','confirmed','on_the_way','completed','cancelled'] as $s) {
    if ($s === 'all') {
        $counts[$s] = $conn->query("SELECT COUNT(*) as c FROM bookings")->fetch_assoc()['c'];
    } else {
        $counts[$s] = $conn->query("SELECT COUNT(*) as c FROM bookings WHERE status='$s'")->fetch_assoc()['c'];
    }
}

$statusLabels = [
    'pending' => ['⏳ অপেক্ষমাণ', '#f59e0b'],
    'confirmed' => ['✅ নিশ্চিত', '#3b82f6'],
    'on_the_way' => ['🚗 পথে', '#8b5cf6'],
    'completed' => ['🎉 সম্পন্ন', '#16a34a'],
    'cancelled' => ['❌ বাতিল', '#dc2626'],
];
?>
<!DOCTYPE html>
<html lang="bn">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>বুকিং ম্যানেজমেন্ট</title>
<style>
* { margin:0; padding:0; box-sizing:border-box; font-family:'Segoe UI',sans-serif; }
body { background: #f5f7fa; }
.topbar {
  background: #1e3a8a; color: white; padding: 15px 25px;
  display: flex; justify-content: space-between; align-items: center;
  flex-wrap: wrap; gap: 10px;
}
.topbar h1 { font-size: 18px; }
.topbar a {
  color: white; text-decoration: none; background: rgba(255,255,255,0.2);
  padding: 8px 16px; border-radius: 20px; font-size: 14px;
  margin-left: 8px;
}
.topbar a:hover { background: rgba(255,255,255,0.35); }
.container { max-width: 1200px; margin: 30px auto; padding: 0 20px; }
h2.page-title { color: #1e3a8a; font-size: 22px; margin-bottom: 20px; }
.msg-box { background: #dcfce7; border: 2px solid #16a34a; color: #166534; padding: 12px 20px; border-radius: 10px; margin-bottom: 20px; font-weight: 600; }
.filters { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 20px; }
.filter-btn {
  padding: 8px 16px; border-radius: 20px; text-decoration: none;
  font-size: 13px; font-weight: 600; background: white;
  color: #475569; border: 2px solid #e2e8f0;
}
.filter-btn:hover { border-color: #3b82f6; color: #1e3a8a; }
.filter-btn.active { background: #1e3a8a; color: white; border-color: #1e3a8a; }
.booking-card {
  background: white; border-radius: 15px; padding: 22px;
  margin-bottom: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.06);
  border-left: 5px solid #94a3b8;
}
.booking-head {
  display: flex; justify-content: space-between; align-items: center;
  flex-wrap: wrap; gap: 10px; margin-bottom: 15px;
}
.booking-head h3 { color: #1e3a8a; font-size: 17px; }
.booking-head .right { display: flex; gap: 8px; flex-wrap: wrap; }
.service-badge {
  background: #dbeafe; color: #1e40af;
  padding: 5px 12px; border-radius: 20px;
  font-size: 13px; font-weight: 600;
}
.status-badge {
  display: inline-block; padding: 5px 12px; border-radius: 20px;
  font-size: 13px; font-weight: 700; color: white;
}
.booking-body {
  display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 12px; margin-bottom: 15px;
}
.field { font-size: 14px; color: #475569; }
.field b { color: #1e293b; display: block; margin-bottom: 2px; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; }
.prescription-box {
  background: linear-gradient(135deg, #eff6ff, #dbeafe);
  border: 2px solid #3b82f6;
  padding: 15px; border-radius: 12px; margin-bottom: 15px;
}
.prescription-box .label {
  font-size: 13px; font-weight: 700; color: #1e40af;
  margin-bottom: 10px; display: flex; align-items: center; gap: 6px;
}
.prescription-box .img-wrap { display: inline-block; cursor: pointer; }
.prescription-box img {
  max-width: 180px; max-height: 180px;
  border-radius: 10px; border: 2px solid white;
  box-shadow: 0 4px 12px rgba(0,0,0,0.15);
  transition: 0.2s;
}
.prescription-box img:hover { transform: scale(1.05); }
.prescription-box .hint { font-size: 11px; color: #64748b; margin-top: 8px; }
.actions { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; }
.actions form { display: flex; gap: 8px; align-items: center; }
.actions select {
  padding: 8px 12px; border: 2px solid #e2e8f0; border-radius: 8px;
  font-size: 13px; font-family: inherit;
}
.actions button {
  padding: 8px 16px; background: #2563eb; color: white;
  border: none; border-radius: 8px; font-weight: 600;
  font-size: 13px; cursor: pointer;
}
.actions button:hover { background: #1e40af; }
.btn-call {
  background: #16a34a; color: white; text-decoration: none;
  padding: 8px 16px; border-radius: 8px; font-weight: 600;
  font-size: 13px;
}
.btn-call:hover { background: #15803d; }
.btn-del {
  background: #fee2e2; color: #dc2626; text-decoration: none;
  padding: 8px 16px; border-radius: 8px; font-weight: 600;
  font-size: 13px;
}
.btn-del:hover { background: #fecaca; }
.btn-review {
  background: #fef3c7; color: #d97706; text-decoration: none;
  padding: 8px 16px; border-radius: 8px; font-weight: 700;
  font-size: 13px;
}
.btn-review:hover { background: #fde68a; }
.empty {
  background: white; padding: 60px 20px; text-align: center;
  border-radius: 15px; color: #64748b;
}
.img-modal {
  display: none; position: fixed; inset: 0;
  background: rgba(0,0,0,0.9); z-index: 9999;
  align-items: center; justify-content: center;
  padding: 20px; cursor: pointer;
}
.img-modal.active { display: flex; }
.img-modal img { max-width: 95%; max-height: 95%; border-radius: 10px; }
.img-modal .close-x {
  position: absolute; top: 20px; right: 30px;
  color: white; font-size: 40px; font-weight: 300; cursor: pointer;
}
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
  <h2 class="page-title">📋 বুকিং ম্যানেজমেন্ট</h2>

  <?php if(isset($_GET['msg']) && $_GET['msg'] == 'deleted'): ?>
    <div class="msg-box">✅ বুকিংটি সফলভাবে ডিলিট করা হয়েছে!</div>
  <?php endif; ?>

  <div class="filters">
    <a href="?status=all" class="filter-btn <?= $filter==='all'?'active':'' ?>">সব (<?= $counts['all'] ?>)</a>
    <?php foreach ($statusLabels as $key => $info): ?>
      <a href="?status=<?= $key ?>" class="filter-btn <?= $filter===$key?'active':'' ?>">
        <?= $info[0] ?> (<?= $counts[$key] ?>)
      </a>
    <?php endforeach; ?>
  </div>

  <?php if ($bookings && $bookings->num_rows > 0): ?>
    <?php while ($b = $bookings->fetch_assoc()): 
      $st = $statusLabels[$b['status']] ?? ['অজানা', '#94a3b8'];
      $reviewed = $conn->query("SELECT id FROM service_reviews WHERE booking_id=" . $b['id'])->num_rows > 0;
    ?>
    <div class="booking-card" style="border-left-color: <?= $st[1] ?>">
      <div class="booking-head">
        <div><h3>#<?= $b['id'] ?> — 👤 <?= htmlspecialchars($b['customer_name']) ?></h3></div>
        <div class="right">
          <span class="service-badge"><?= htmlspecialchars(($b['service_icon'] ?? '🔧') . ' ' . ($b['service_name'] ?? 'N/A')) ?></span>
          <span class="status-badge" style="background:<?= $st[1] ?>"><?= $st[0] ?></span>
        </div>
      </div>

      <div class="booking-body">
        <div class="field"><b>📞 ফোন</b><?= htmlspecialchars($b['phone']) ?></div>
        <div class="field"><b>📍 ঠিকানা</b><?= htmlspecialchars($b['address']) ?></div>
        <div class="field"><b>⏰ সময়</b><?= htmlspecialchars($b['preferred_time']) ?></div>
        <div class="field"><b>💰 সম্ভাব্য মূল্য</b>৳<?= number_format($b['total']) ?></div>
        <?php if (!empty($b['problem'])): ?>
        <div class="field" style="grid-column: 1/-1;"><b>📝 সমস্যা</b><?= nl2br(htmlspecialchars($b['problem'])) ?></div>
        <?php endif; ?>
        <div class="field"><b>🕐 বুকিং সময়</b><?= date('d M Y, h:i A', strtotime($b['created_at'])) ?></div>
      </div>

      <?php if (!empty($b['payment_method'])): ?>
      <div class="field" style="grid-column: 1/-1; background: #fef3c7; padding: 12px; border-radius: 10px; border-left: 4px solid #f59e0b; margin-bottom: 15px;">
        <b>💳 পেমেন্ট তথ্য</b>
        <div style="margin-top: 5px; font-size: 14px;">
          <span style="background: <?= $b['payment_status'] == 'paid' ? '#dcfce7' : '#fee2e2' ?>; color: <?= $b['payment_status'] == 'paid' ? '#16a34a' : '#dc2626' ?>; padding: 4px 12px; border-radius: 20px; font-weight: 700; font-size: 12px;">
            <?= $b['payment_status'] == 'paid' ? '✅ পরিশোধিত' : '⏳ যাচাই বাকি' ?>
          </span><br><br>
          <b>মেথড:</b> <?= htmlspecialchars($b['payment_method']) ?> |
          <b>নম্বর:</b> <?= htmlspecialchars($b['payment_number']) ?> |
          <b>TrxID:</b> <code style="background: #1e293b; color: #4ade80; padding: 2px 8px; border-radius: 5px;"><?= htmlspecialchars($b['transaction_id']) ?></code>
        </div>
          
        <?php if ($b['payment_status'] != 'paid'): ?>
          <div style="margin-top: 10px;">
            <a href="?verify_payment=<?= $b['id'] ?>" style="background: #16a34a; color: white; padding: 6px 14px; border-radius: 8px; text-decoration: none; font-size: 12px; font-weight: 700;" onclick="return confirm('পেমেন্ট যাচাই হয়েছে?')">✅ পেমেন্ট যাচাই করুন</a>
          </div>
        <?php endif; ?>
      </div>
      <?php endif; ?>

      <?php if (!empty($b['prescription']) && file_exists('../uploads/prescriptions/' . $b['prescription'])): ?>
        <div class="prescription-box">
          <div class="label">📷 ডাক্তারের প্রেসক্রিপশন</div>
          <div class="img-wrap" onclick="showImg('../uploads/prescriptions/<?= htmlspecialchars($b['prescription']) ?>')">
            <img src="../uploads/prescriptions/<?= htmlspecialchars($b['prescription']) ?>" alt="Prescription">
          </div>
          <div class="hint">💡 ছবিতে ক্লিক করলে বড় হয়ে দেখাবে</div>
        </div>
      <?php endif; ?>

      <div class="actions">
        <form method="POST">
          <input type="hidden" name="booking_id" value="<?= $b['id'] ?>">
          <select name="status">
            <?php foreach ($statusLabels as $key => $info): ?>
              <option value="<?= $key ?>" <?= $b['status']===$key?'selected':'' ?>><?= $info[0] ?></option>
            <?php endforeach; ?>
          </select>
          <button type="submit" name="update_status">আপডেট</button>
        </form>

        <a href="tel:<?= htmlspecialchars($b['phone']) ?>" class="btn-call">📞 কল করুন</a>

        <?php if ($b['status'] === 'completed' && !$reviewed): ?>
          <a href="../give_review.php?booking=<?= $b['id'] ?>" target="_blank" class="btn-review">⭐ রিভিউ লিংক</a>
        <?php elseif ($reviewed): ?>
          <span class="btn-review" style="cursor:default; background:#dcfce7; color:#16a34a;">✅ রিভিউ দিয়েছে</span>
        <?php endif; ?>

        <a href="?delete=<?= $b['id'] ?>" class="btn-del" onclick="return confirm('বুকিংটি ডিলিট করবেন? প্রেসক্রিপশন ছবি ও রিভিউও মুছে যাবে।')">🗑 ডিলিট</a>
      </div>
    </div>
    <?php endwhile; ?>
  <?php else: ?>
    <div class="empty">
      <h3>📭 এই ফিল্টারে কোনো বুকিং নেই</h3>
      <p style="margin-top:10px;">কাস্টমার বুকিং দিলে এখানে দেখা যাবে।</p>
    </div>
  <?php endif; ?>
</div>

<div class="img-modal" id="imgModal" onclick="hideImg()">
  <span class="close-x">✕</span>
  <img id="modalImg" src="" alt="">
</div>

<script>
function showImg(src) {
    document.getElementById('modalImg').src = src;
    document.getElementById('imgModal').classList.add('active');
}
function hideImg() {
    document.getElementById('imgModal').classList.remove('active');
}
</script>

</body>
</html>