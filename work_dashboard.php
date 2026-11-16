<?php
session_start();
if (!isset($_SESSION['work_id'])) {
    header("Location: work_login.php");
    exit;
}
include 'db.php';

$id = $_SESSION['work_id'];

$stmt = $conn->prepare("SELECT t.*, 
                        c.name as cat_name, c.icon as cat_icon,
                        sc.name as subcat_name
                        FROM technicians t 
                        LEFT JOIN work_categories c ON t.category_id = c.id 
                        LEFT JOIN work_subcategories sc ON t.subcategory_id = sc.id 
                        WHERE t.id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$me = $stmt->get_result()->fetch_assoc();

if (!$me) {
    session_destroy();
    header("Location: work_login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="bn">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>আমার ড্যাশবোর্ড</title>
<style>
* { margin:0; padding:0; box-sizing:border-box; font-family:'Segoe UI',sans-serif; }
body { background: #f5f7fa; }
.topbar {
  background: linear-gradient(135deg,#16a34a,#15803d);
  color: white; padding: 18px 25px;
  display: flex; justify-content: space-between; align-items: center;
  flex-wrap: wrap; gap: 10px;
}
.topbar h1 { font-size: 18px; }
.topbar a {
  color: white; text-decoration: none; background: rgba(255,255,255,0.2);
  padding: 8px 16px; border-radius: 20px; font-size: 14px;
}
.container { max-width: 900px; margin: 30px auto; padding: 0 20px; }

.profile-card {
  background: white; padding: 30px; border-radius: 20px;
  box-shadow: 0 4px 20px rgba(0,0,0,0.08);
  margin-bottom: 25px;
  display: grid;
  grid-template-columns: 140px 1fr;
  gap: 25px;
  align-items: center;
}
.profile-photo {
  width: 140px; height: 140px; border-radius: 50%;
  object-fit: cover;
  border: 4px solid #16a34a;
  box-shadow: 0 4px 15px rgba(22,163,74,0.3);
}
.profile-info h2 {
  color: #1e3a8a; font-size: 22px; margin-bottom: 8px;
}
.profile-info .id-badge {
  display: inline-block;
  background: #1e293b; color: #4ade80;
  padding: 6px 14px; border-radius: 8px;
  font-family: 'Courier New', monospace;
  font-size: 15px; font-weight: 700;
  margin-bottom: 10px;
}
.profile-info .role {
  display: inline-block;
  background: #dcfce7; color: #166534;
  padding: 5px 14px; border-radius: 20px;
  font-size: 13px; font-weight: 600;
  margin-left: 8px;
}

.info-grid {
  display: grid; grid-template-columns: repeat(auto-fit, minmax(200px,1fr));
  gap: 15px; margin-bottom: 25px;
}
.info-card {
  background: white; padding: 20px; border-radius: 15px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.06);
}
.info-card .label {
  font-size: 12px; color: #64748b;
  text-transform: uppercase; letter-spacing: 0.5px;
  margin-bottom: 6px;
}
.info-card .value { font-size: 16px; font-weight: 700; color: #1e3a8a; }

.status-badge {
  display: inline-block; padding: 8px 16px; border-radius: 20px;
  font-size: 14px; font-weight: 700; color: white; background: #16a34a;
}
.status-badge.pending { background: #f59e0b; }
.status-badge.rejected { background: #dc2626; }

.section-title {
  color: #1e3a8a; font-size: 20px; margin-bottom: 15px;
  margin-top: 30px;
}
.empty-msg {
  background: white; padding: 40px 20px;
  border-radius: 15px; text-align: center; color: #64748b;
}

@media (max-width: 600px) {
  .profile-card { grid-template-columns: 1fr; text-align: center; }
  .profile-photo { margin: 0 auto; }
}
</style>
</head>
<body>

<div class="topbar">
  <h1>💼 আমার ড্যাশবোর্ড</h1>
  <a href="work_logout.php">লগআউট</a>
</div>

<div class="container">

  <div class="profile-card">
    <?php 
      $photoPath = 'uploads/photos/' . $me['photo'];
      if (!file_exists($photoPath) || !$me['photo']) {
          $photoPath = 'https://ui-avatars.com/api/?name=' . urlencode($me['name']) . '&size=200&background=16a34a&color=fff&bold=true';
      }
    ?>
    <img src="<?= htmlspecialchars($photoPath) ?>" 
         alt="<?= htmlspecialchars($me['name']) ?>" 
         class="profile-photo">
    
    <div class="profile-info">
      <h2><?= htmlspecialchars($me['name']) ?></h2>
      <div class="id-badge"><?= htmlspecialchars($me['tech_id']) ?></div>
      <span class="role">
        <?= htmlspecialchars(($me['cat_icon'] ?? '') . ' ' . ($me['cat_name'] ?? '')) ?>
        <?php if ($me['subcat_name']): ?>
          — <?= htmlspecialchars($me['subcat_name']) ?>
        <?php endif; ?>
      </span>
    </div>
  </div>

  <div class="info-grid">
    <div class="info-card">
      <div class="label">📞 ফোন</div>
      <div class="value"><?= htmlspecialchars($me['phone']) ?></div>
    </div>
    <div class="info-card">
      <div class="label">✉️ ইমেইল</div>
      <div class="value"><?= htmlspecialchars($me['email'] ?: '—') ?></div>
    </div>
    <div class="info-card">
      <div class="label">💼 অভিজ্ঞতা</div>
      <div class="value"><?= htmlspecialchars($me['experience'] ?: '—') ?></div>
    </div>
    <div class="info-card">
      <div class="label">✅ স্ট্যাটাস</div>
      <div class="value">
        <span class="status-badge <?= $me['status'] ?>">
          <?php
            if ($me['status'] === 'approved') echo '✅ অনুমোদিত';
            elseif ($me['status'] === 'pending') echo '⏳ অপেক্ষমাণ';
            else echo '❌ বাতিল';
          ?>
        </span>
      </div>
    </div>
    <div class="info-card">
      <div class="label">📍 ঠিকানা</div>
      <div class="value" style="font-size:14px;"><?= htmlspecialchars($me['address']) ?></div>
    </div>
    <div class="info-card">
      <div class="label">🎯 মোট কাজ</div>
      <div class="value"><?= $me['total_jobs'] ?></div>
    </div>
  </div>

  <h2 class="section-title">📋 আমার কাজসমূহ</h2>
  <div class="empty-msg">
    <?php if ($me['status'] === 'pending'): ?>
      ⏳ আপনার আবেদন পর্যালোচনাধীন আছে। অনুমোদনের পর কাজ দেখতে পাবেন।
    <?php elseif ($me['status'] === 'rejected'): ?>
      ❌ আপনার আবেদন বাতিল করা হয়েছে।
    <?php else: ?>
      এখনো কোনো কাজ অ্যাসাইন করা হয়নি।<br>
      <small style="color:#94a3b8">অ্যাডমিন কাজ দিলে এখানে দেখা যাবে।</small>
    <?php endif; ?>
  </div>

</div>

</body>
</html>