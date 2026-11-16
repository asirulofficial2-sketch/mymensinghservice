<?php
include 'includes/auth.php';
include '../db.php';

// স্ট্যাটাস আপডেট
if (isset($_POST['update_status'])) {
    $oid = intval($_POST['order_id']);
    $status = $_POST['status'];
    $allowed = ['pending','confirmed','shipped','delivered','cancelled'];
    if (in_array($status, $allowed)) {
        $stmt = $conn->prepare("UPDATE shop_orders SET status=? WHERE id=?");
        $stmt->bind_param("si", $status, $oid);
        $stmt->execute();
    }
    header("Location: shop_orders.php");
    exit;
}

// ডিলিট
// ============ ডিলিট (রিভিউ সহ) ============
if (isset($_GET['verify_payment'])) {
    $oid = intval($_GET['verify_payment']);
    $conn->query("UPDATE shop_orders SET payment_status='paid' WHERE id=$oid");
    header("Location: shop_orders.php");
    exit;
}
if (isset($_GET['delete'])) {
    $oid = intval($_GET['delete']);
    
    // আগে রিভিউ ডিলিট
    $conn->query("DELETE FROM product_reviews WHERE order_id=$oid");
    
    // তারপর অর্ডার ডিলিট
    $conn->query("DELETE FROM shop_orders WHERE id=$oid");
    
    header("Location: shop_orders.php");
    exit;
}

$filter = $_GET['status'] ?? 'all';

$sql = "SELECT * FROM shop_orders";
if ($filter !== 'all') {
    $sql .= " WHERE status = '" . $conn->real_escape_string($filter) . "'";
}
$sql .= " ORDER BY id DESC";
$orders = $conn->query($sql);

$counts = [];
foreach (['all','pending','confirmed','shipped','delivered','cancelled'] as $s) {
    if ($s === 'all') {
        $counts[$s] = $conn->query("SELECT COUNT(*) as c FROM shop_orders")->fetch_assoc()['c'];
    } else {
        $counts[$s] = $conn->query("SELECT COUNT(*) as c FROM shop_orders WHERE status='$s'")->fetch_assoc()['c'];
    }
}

$statusLabels = [
    'pending' => ['⏳ অপেক্ষমাণ', '#f59e0b'],
    'confirmed' => ['✅ নিশ্চিত', '#3b82f6'],
    'shipped' => ['🚚 পাঠানো হয়েছে', '#8b5cf6'],
    'delivered' => ['🎉 ডেলিভারি সম্পন্ন', '#16a34a'],
    'cancelled' => ['❌ বাতিল', '#dc2626'],
];
?>
<!DOCTYPE html>
<html lang="bn">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>শপ অর্ডার ম্যানেজমেন্ট</title>
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
.container { max-width: 1200px; margin: 30px auto; padding: 0 20px; }
h2.page-title { color: #1e3a8a; font-size: 22px; margin-bottom: 20px; }

.filters { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 20px; }
.filter-btn {
  padding: 8px 16px; border-radius: 20px; text-decoration: none;
  font-size: 13px; font-weight: 600; background: white;
  color: #475569; border: 2px solid #e2e8f0;
}
.filter-btn.active { background: #1e3a8a; color: white; border-color: #1e3a8a; }

.order-card {
  background: white; border-radius: 15px; padding: 22px;
  margin-bottom: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.06);
  border-left: 5px solid #94a3b8;
}
.order-head {
  display: flex; justify-content: space-between; align-items: center;
  flex-wrap: wrap; gap: 10px; margin-bottom: 15px;
  padding-bottom: 12px; border-bottom: 2px solid #f1f5f9;
}
.order-head h3 { color: #1e3a8a; font-size: 18px; }
.order-head .right { display: flex; gap: 8px; flex-wrap: wrap; }
.status-badge {
  display: inline-block; padding: 5px 12px; border-radius: 20px;
  font-size: 13px; font-weight: 700; color: white;
}
.order-body {
  display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 12px; margin-bottom: 15px;
}
.field { font-size: 14px; color: #475569; }
.field b { color: #1e293b; display: block; margin-bottom: 2px; font-size: 12px; text-transform: uppercase; }

/* অর্ডার আইটেম */
.items-box {
  background: #f8fafc;
  border-radius: 12px;
  padding: 15px;
  margin-bottom: 15px;
}
.items-box h4 {
  color: #1e3a8a;
  font-size: 14px;
  margin-bottom: 10px;
  font-weight: 700;
}
.item-row {
  background: white;
  padding: 10px 15px;
  border-radius: 8px;
  margin-bottom: 6px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 10px;
  font-size: 13px;
}
.item-row .item-name { color: #1e3a8a; font-weight: 600; }
.item-row .item-info { color: #64748b; font-size: 12px; }
.item-row .item-price { color: #16a34a; font-weight: 700; }

.total-line {
  display: flex; justify-content: space-between;
  padding: 12px 15px; background: #dcfce7;
  border-radius: 10px; font-weight: 700;
  color: #166534; font-size: 16px; margin-top: 8px;
}

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
.btn-call {
  background: #16a34a; color: white; text-decoration: none;
  padding: 8px 16px; border-radius: 8px; font-weight: 600;
  font-size: 13px;
}
.btn-del {
  background: #fee2e2; color: #dc2626; text-decoration: none;
  padding: 8px 16px; border-radius: 8px; font-weight: 600;
  font-size: 13px;
}

.empty {
  background: white; padding: 60px 20px; text-align: center;
  border-radius: 15px; color: #64748b;
}
</style>
</head>
<body>

<div class="topbar">
  <h1>🛠️ অ্যাডমিন প্যানেল</h1>
  <div>
    <a href="dashboard.php">ড্যাশবোর্ড</a>
    <a href="shop_products.php">প্রোডাক্ট</a>
    <a href="logout.php">লগআউট</a>
  </div>
</div>

<div class="container">
  <h2 class="page-title">🛒 শপ অর্ডার ম্যানেজমেন্ট</h2>

  <div class="filters">
    <a href="?status=all" class="filter-btn <?= $filter==='all'?'active':'' ?>">
      সব (<?= $counts['all'] ?>)
    </a>
    <?php foreach ($statusLabels as $key => $info): ?>
      <a href="?status=<?= $key ?>" class="filter-btn <?= $filter===$key?'active':'' ?>">
        <?= $info[0] ?> (<?= $counts[$key] ?>)
      </a>
    <?php endforeach; ?>
  </div>

  <?php if ($orders->num_rows > 0): ?>
    <?php while ($o = $orders->fetch_assoc()): 
      $st = $statusLabels[$o['status']] ?? ['অজানা', '#94a3b8'];
      $items = json_decode($o['items'], true) ?: [];
    ?>
    <div class="order-card" style="border-left-color: <?= $st[1] ?>">
      <div class="order-head">
        <h3>#<?= $o['id'] ?> — 👤 <?= htmlspecialchars($o['customer_name']) ?></h3>
        <div class="right">
          <span class="status-badge" style="background:<?= $st[1] ?>"><?= $st[0] ?></span>
        </div>
      </div>

      <div class="order-body">
        <div class="field"><b>📞 ফোন</b><?= htmlspecialchars($o['phone']) ?></div>
          <?php if (!empty($o['payment_method'])): ?>
<div class="field" style="grid-column: 1/-1; background: #fef3c7; padding: 12px; border-radius: 10px; border-left: 4px solid #f59e0b;">
  <b>💳 পেমেন্ট তথ্য</b>
  <div style="margin-top: 5px; font-size: 14px;">
    <span style="background: <?= $o['payment_status'] == 'paid' ? '#dcfce7' : '#fee2e2' ?>; color: <?= $o['payment_status'] == 'paid' ? '#16a34a' : '#dc2626' ?>; padding: 4px 12px; border-radius: 20px; font-weight: 700; font-size: 12px;">
      <?= $o['payment_status'] == 'paid' ? '✅ পরিশোধিত' : '⏳ যাচাই বাকি' ?>
    </span>
    <br><br>
    <b>মেথড:</b> <?= htmlspecialchars($o['payment_method']) ?> |
    <b>TrxID:</b> <code style="background: #1e293b; color: #4ade80; padding: 2px 8px; border-radius: 5px;"><?= htmlspecialchars($o['transaction_id']) ?></code>
  </div>
  <?php if ($o['payment_status'] != 'paid'): ?>
    <div style="margin-top: 10px;">
      <a href="?verify_payment=<?= $o['id'] ?>" 
         style="background: #16a34a; color: white; padding: 6px 14px; border-radius: 8px; text-decoration: none; font-size: 12px; font-weight: 700;"
         onclick="return confirm('পেমেন্ট যাচাই হয়েছে?')">
        ✅ পেমেন্ট যাচাই করুন
      </a>
    </div>
  <?php endif; ?>
</div>
<?php endif; ?>
        <div class="field"><b>📍 ঠিকানা</b><?= htmlspecialchars($o['address']) ?></div>
        <div class="field"><b>🕐 অর্ডার সময়</b><?= date('d M Y, h:i A', strtotime($o['created_at'])) ?></div>
          
        <div class="field"><b>💰 মোট</b>৳<?= number_format($o['total']) ?></div>
        <?php if (!empty($o['note'])): ?>
          <div class="field" style="grid-column: 1/-1;">
            <b>📝 নোট</b><?= nl2br(htmlspecialchars($o['note'])) ?>
          </div>
        <?php endif; ?>
      </div>

      <?php if (count($items) > 0): ?>
        <div class="items-box">
          <h4>📦 অর্ডারের প্রোডাক্টসমূহ</h4>
          <?php foreach ($items as $it): ?>
            <div class="item-row">
              <div>
                <div class="item-name"><?= htmlspecialchars($it['name'] ?? 'N/A') ?></div>
                <?php if (!empty($it['size']) || !empty($it['color'])): ?>
                  <div class="item-info">
                    <?php if (!empty($it['size'])) echo 'সাইজ: ' . htmlspecialchars($it['size']); ?>
                    <?php if (!empty($it['color'])) echo ' | রঙ: ' . htmlspecialchars($it['color']); ?>
                  </div>
                <?php endif; ?>
              </div>
              <div>
                <span class="item-info">৳<?= number_format($it['price'] ?? 0) ?> × <?= $it['qty'] ?? 1 ?></span>
                <span class="item-price">= ৳<?= number_format(($it['price'] ?? 0) * ($it['qty'] ?? 1)) ?></span>
              </div>
            </div>
          <?php endforeach; ?>
          <div class="total-line">
            <span>মোট</span>
            <span>৳<?= number_format($o['total']) ?></span>
          </div>
        </div>
      <?php endif; ?>

      <div class="actions">
        <form method="POST">
          <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
          <select name="status">
            <?php foreach ($statusLabels as $key => $info): ?>
              <option value="<?= $key ?>" <?= $o['status']===$key?'selected':'' ?>>
                <?= $info[0] ?>
              </option>
            <?php endforeach; ?>
          </select>
          <button type="submit" name="update_status">আপডেট</button>
        </form>

        <a href="tel:<?= htmlspecialchars($o['phone']) ?>" class="btn-call">📞 কল</a>
          
<?php 
$reviewed = $conn->query("SELECT id FROM product_reviews WHERE order_id=" . $o['id'])->num_rows > 0;
?>
<?php if ($o['status'] === 'delivered' && !$reviewed): ?>
  <a href="../give_review.php?order=<?= $o['id'] ?>" target="_blank" 
     style="background:#fef3c7;color:#d97706;padding:8px 16px;border-radius:8px;text-decoration:none;font-size:13px;font-weight:700;">
    ⭐ রিভিউ লিংক
  </a>
<?php elseif ($reviewed): ?>
  <span style="background:#dcfce7;color:#16a34a;padding:8px 16px;border-radius:8px;font-size:13px;font-weight:700;">
    ✅ রিভিউ দিয়েছে
  </span>
<?php endif; ?>
        <a href="?delete=<?= $o['id'] ?>" class="btn-del"
           onclick="return confirm('অর্ডারটি ডিলিট করবেন?')">🗑 ডিলিট</a>
      </div>
    </div>
    <?php endwhile; ?>
  <?php else: ?>
    <div class="empty">
      <h3>📭 কোনো অর্ডার নেই</h3>
      <p style="margin-top:10px;">কাস্টমার অর্ডার করলে এখানে দেখা যাবে।</p>
    </div>
  <?php endif; ?>
</div>

</body>
</html>
