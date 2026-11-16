<?php
include 'includes/auth.php';
include '../db.php';

$notices = $conn->query("SELECT * FROM notices ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="bn">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>নোটিশ বোর্ড</title>
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
.container { max-width: 900px; margin: 30px auto; padding: 0 20px; }
.page-head {
  display: flex; justify-content: space-between; align-items: center;
  margin-bottom: 20px; flex-wrap: wrap; gap: 10px;
}
.page-head h2 { color: #1e3a8a; font-size: 22px; }
.btn-add {
  background: #16a34a; color: white; text-decoration: none;
  padding: 10px 20px; border-radius: 10px; font-weight: 600;
  font-size: 14px;
}
.btn-add:hover { background: #15803d; }

.notice-card {
  background: white; border-radius: 15px; padding: 22px;
  margin-bottom: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.06);
  border-left: 5px solid #3b82f6;
}
.notice-card.inactive { border-left-color: #94a3b8; opacity: 0.7; }
.notice-head {
  display: flex; justify-content: space-between; align-items: center;
  flex-wrap: wrap; gap: 10px; margin-bottom: 10px;
}
.notice-head h3 { color: #1e3a8a; font-size: 17px; }
.badge {
  padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600;
}
.badge-on { background: #dcfce7; color: #16a34a; }
.badge-off { background: #fee2e2; color: #dc2626; }
.notice-msg {
  color: #475569; font-size: 15px; margin-bottom: 12px;
  line-height: 1.6;
}
.notice-meta { font-size: 12px; color: #94a3b8; margin-bottom: 12px; }
.actions a {
  padding: 7px 14px; border-radius: 8px; font-size: 13px;
  text-decoration: none; font-weight: 600; margin-right: 5px;
}
.btn-edit { background: #dbeafe; color: #1e40af; }
.btn-edit:hover { background: #bfdbfe; }
.btn-delete { background: #fee2e2; color: #dc2626; }
.btn-delete:hover { background: #fecaca; }

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
    <a href="logout.php">লগআউট</a>
  </div>
</div>

<div class="container">
  <div class="page-head">
    <h2>📢 নোটিশ বোর্ড</h2>
    <a href="notice_add.php" class="btn-add">+ নতুন নোটিশ</a>
  </div>

  <?php if ($notices->num_rows > 0): ?>
    <?php while ($n = $notices->fetch_assoc()): ?>
    <div class="notice-card <?= $n['active'] ? '' : 'inactive' ?>">
      <div class="notice-head">
        <h3><?= htmlspecialchars($n['title']) ?></h3>
        <?php if ($n['active']): ?>
          <span class="badge badge-on">✅ সক্রিয়</span>
        <?php else: ?>
          <span class="badge badge-off">❌ নিষ্ক্রিয়</span>
        <?php endif; ?>
      </div>
      <div class="notice-msg"><?= nl2br(htmlspecialchars($n['message'])) ?></div>
      <div class="notice-meta">
        🕐 <?= date('d M Y, h:i A', strtotime($n['created_at'])) ?>
      </div>
      <div class="actions">
        <a href="notice_edit.php?id=<?= $n['id'] ?>" class="btn-edit">এডিট</a>
        <a href="notice_delete.php?id=<?= $n['id'] ?>" class="btn-delete"
           onclick="return confirm('নোটিশটি ডিলিট করবেন?')">ডিলিট</a>
      </div>
    </div>
    <?php endwhile; ?>
  <?php else: ?>
    <div class="empty">
      <h3>এখনো কোনো নোটিশ নেই</h3>
      <p style="margin-top:10px;">"+ নতুন নোটিশ" বাটনে ক্লিক করুন</p>
    </div>
  <?php endif; ?>
</div>

</body>
</html>