<?php
include 'includes/auth.php';
include '../db.php';

$msg = '';

// নতুন নোটিশ
if (isset($_POST['add_notice'])) {
    $title = trim($_POST['title'] ?? '');
    $message = trim($_POST['message'] ?? '');
    $active = isset($_POST['active']) ? 1 : 0;
    if ($title && $message) {
        $stmt = $conn->prepare("INSERT INTO shop_notices (title, message, active) VALUES (?, ?, ?)");
        $stmt->bind_param("ssi", $title, $message, $active);
        $stmt->execute();
        $msg = "✅ শপ নোটিশ যোগ হয়েছে";
    }
}

// এডিট
if (isset($_POST['edit_notice'])) {
    $id = intval($_POST['id']);
    $title = trim($_POST['title'] ?? '');
    $message = trim($_POST['message'] ?? '');
    $active = isset($_POST['active']) ? 1 : 0;
    if ($title && $message) {
        $stmt = $conn->prepare("UPDATE shop_notices SET title=?, message=?, active=? WHERE id=?");
        $stmt->bind_param("ssii", $title, $message, $active, $id);
        $stmt->execute();
        $msg = "✅ আপডেট হয়েছে";
    }
}

// ডিলিট
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM shop_notices WHERE id=$id");
    header("Location: shop_notices.php");
    exit;
}

// টগল
if (isset($_GET['toggle'])) {
    $id = intval($_GET['toggle']);
    $conn->query("UPDATE shop_notices SET active = NOT active WHERE id=$id");
    header("Location: shop_notices.php");
    exit;
}

$editData = null;
if (isset($_GET['edit'])) {
    $eid = intval($_GET['edit']);
    $editData = $conn->query("SELECT * FROM shop_notices WHERE id=$eid")->fetch_assoc();
}

$notices = $conn->query("SELECT * FROM shop_notices ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="bn">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>শপ নোটিশ বোর্ড</title>
<style>
* { margin:0; padding:0; box-sizing:border-box; font-family:'Segoe UI',sans-serif; }
body { background: #f5f7fa; }
.topbar {
  background: #7c3aed; color: white; padding: 15px 25px;
  display: flex; justify-content: space-between; align-items: center;
  flex-wrap: wrap; gap: 10px;
}
.topbar h1 { font-size: 18px; }
.topbar a {
  color: white; text-decoration: none; background: rgba(255,255,255,0.2);
  padding: 8px 16px; border-radius: 20px; font-size: 14px;
  margin-left: 8px;
}
.container { max-width: 900px; margin: 30px auto; padding: 0 20px 50px; }
h2.page-title { color: #7c3aed; font-size: 22px; margin-bottom: 20px; }

.msg-box {
  background: #dcfce7; border: 2px solid #16a34a;
  color: #166534; padding: 12px 20px;
  border-radius: 10px; margin-bottom: 20px; font-weight: 600;
}

.card {
  background: white; padding: 25px; border-radius: 15px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.06); margin-bottom: 20px;
}
.card h3 {
  color: #7c3aed; margin-bottom: 20px; font-size: 17px;
  padding-bottom: 12px; border-bottom: 2px solid #f1f5f9;
}

label { display: block; margin-bottom: 6px; font-weight: 600; color: #334155; font-size: 14px; }
input[type=text], textarea {
  width: 100%; padding: 12px 14px; margin-bottom: 15px;
  border: 2px solid #e2e8f0; border-radius: 10px;
  font-size: 15px; font-family: inherit;
}
input:focus, textarea:focus { outline: none; border-color: #7c3aed; }
textarea { min-height: 100px; resize: vertical; }

.checkbox-row {
  display: flex; align-items: center; gap: 8px;
  margin-bottom: 15px; font-size: 14px;
}
.btn {
  padding: 12px 24px; border-radius: 10px;
  font-size: 15px; font-weight: 700; cursor: pointer;
  border: none; font-family: inherit;
}
.btn-primary { background: #7c3aed; color: white; width: 100%; }
.btn-primary:hover { background: #6d28d9; }
.btn-update { background: #2563eb; color: white; width: 100%; }
.btn-cancel {
  background: #e2e8f0; color: #334155; text-decoration: none;
  display: inline-block; padding: 12px 24px; border-radius: 10px;
  font-weight: 700; text-align: center; margin-top: 8px;
}

.notice-item {
  background: #f8fafc; padding: 18px;
  border-radius: 12px; margin-bottom: 12px;
  border-left: 5px solid #7c3aed;
}
.notice-item.inactive { opacity: 0.5; border-left-color: #94a3b8; }
.notice-head {
  display: flex; justify-content: space-between;
  align-items: center; flex-wrap: wrap; gap: 10px; margin-bottom: 8px;
}
.notice-head h4 { color: #7c3aed; font-size: 16px; }
.badge { padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 700; }
.badge-on { background: #dcfce7; color: #16a34a; }
.badge-off { background: #fee2e2; color: #dc2626; }
.notice-msg {
  color: #475569; font-size: 14px; line-height: 1.7; margin-bottom: 10px;
}
.notice-actions { display: flex; gap: 8px; flex-wrap: wrap; }
.notice-actions a {
  padding: 7px 14px; border-radius: 8px;
  font-size: 13px; text-decoration: none; font-weight: 700;
}
.btn-edit { background: #dbeafe; color: #1e40af; }
.btn-del { background: #fee2e2; color: #dc2626; }
.btn-toggle { background: #fef3c7; color: #d97706; }

.empty {
  text-align: center; padding: 40px 20px; color: #64748b;
}
</style>
</head>
<body>

<div class="topbar">
  <h1>🛠️ অ্যাডমিন প্যানেল</h1>
  <div>
    <a href="dashboard.php">ড্যাশবোর্ড</a>
    <a href="notices.php">সার্ভিস নোটিশ</a>
    <a href="logout.php">লগআউট</a>
  </div>
</div>

<div class="container">
  <h2 class="page-title">🛒 শপ নোটিশ বোর্ড</h2>

  <?php if ($msg): ?>
    <div class="msg-box"><?= htmlspecialchars($msg) ?></div>
  <?php endif; ?>

  <div class="card">
    <h3><?= $editData ? '✏️ নোটিশ এডিট' : '➕ নতুন শপ নোটিশ' ?></h3>
    <form method="POST">
      <?php if ($editData): ?>
        <input type="hidden" name="id" value="<?= $editData['id'] ?>">
      <?php endif; ?>

      <label>টাইটেল *</label>
      <input type="text" name="title" required
             placeholder="যেমন: নতুন প্রোডাক্ট এসেছে"
             value="<?= htmlspecialchars($editData['title'] ?? '') ?>">

      <label>মেসেজ *</label>
      <textarea name="message" required 
                placeholder="বিস্তারিত লিখুন..."><?= htmlspecialchars($editData['message'] ?? '') ?></textarea>

      <div class="checkbox-row">
        <input type="checkbox" name="active" id="active" 
               <?= (!$editData || $editData['active']) ? 'checked' : '' ?>>
        <label for="active" style="margin:0;">সক্রিয় (সাইটে দেখাবে)</label>
      </div>

      <?php if ($editData): ?>
        <button type="submit" name="edit_notice" class="btn btn-update">💾 আপডেট</button>
        <a href="shop_notices.php" class="btn-cancel">বাতিল</a>
      <?php else: ?>
        <button type="submit" name="add_notice" class="btn btn-primary">➕ যোগ করুন</button>
      <?php endif; ?>
    </form>
  </div>

  <div class="card">
    <h3>📋 সব শপ নোটিশ</h3>
    <?php if ($notices->num_rows > 0): ?>
      <?php while ($n = $notices->fetch_assoc()): ?>
        <div class="notice-item <?= $n['active'] ? '' : 'inactive' ?>">
          <div class="notice-head">
            <h4><?= htmlspecialchars($n['title']) ?></h4>
            <span class="badge <?= $n['active'] ? 'badge-on' : 'badge-off' ?>">
              <?= $n['active'] ? '✅ সক্রিয়' : '❌ নিষ্ক্রিয়' ?>
            </span>
          </div>
          <div class="notice-msg"><?= nl2br(htmlspecialchars($n['message'])) ?></div>
          <div class="notice-actions">
            <a href="?edit=<?= $n['id'] ?>" class="btn-edit">✏️ এডিট</a>
            <a href="?toggle=<?= $n['id'] ?>" class="btn-toggle">
              <?= $n['active'] ? '🙈 নিষ্ক্রিয় করুন' : '👁️ সক্রিয় করুন' ?>
            </a>
            <a href="?delete=<?= $n['id'] ?>" class="btn-del"
               onclick="return confirm('ডিলিট করবেন?')">🗑 ডিলিট</a>
          </div>
        </div>
      <?php endwhile; ?>
    <?php else: ?>
      <div class="empty">📭 এখনো কোনো শপ নোটিশ নেই</div>
    <?php endif; ?>
  </div>
</div>

</body>
</html>