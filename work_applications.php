<?php
include 'includes/auth.php';
include '../db.php';

// Approve / Reject / Delete
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $action = $_GET['action'];

    if ($action === 'approve') {
        $conn->query("UPDATE technicians SET status='approved' WHERE id=$id");
    } elseif ($action === 'reject') {
        $conn->query("UPDATE technicians SET status='rejected' WHERE id=$id");
    } elseif ($action === 'delete') {
        // ছবিগুলোও ডিলিট
        $row = $conn->query("SELECT photo, nid_front, nid_back FROM technicians WHERE id=$id")->fetch_assoc();
        if ($row) {
            @unlink("../uploads/photos/" . $row['photo']);
            @unlink("../uploads/nid_front/" . $row['nid_front']);
            @unlink("../uploads/nid_back/" . $row['nid_back']);
        }
        $conn->query("DELETE FROM technicians WHERE id=$id");
    } elseif ($action === 'toggle') {
        $conn->query("UPDATE technicians SET available = NOT available WHERE id=$id");
    }
    header("Location: work_applications.php");
    exit;
}

// ফিল্টার
$filter = $_GET['status'] ?? 'all';
$sql = "SELECT t.*, 
        c.name as cat_name, c.icon as cat_icon,
        sc.name as subcat_name
        FROM technicians t 
        LEFT JOIN work_categories c ON t.category_id = c.id 
        LEFT JOIN work_subcategories sc ON t.subcategory_id = sc.id";

if ($filter !== 'all') {
    $sql .= " WHERE t.status = '" . $conn->real_escape_string($filter) . "'";
}
$sql .= " ORDER BY t.id DESC";
$techs = $conn->query($sql);

$counts = [];
foreach (['all','pending','approved','rejected'] as $s) {
    if ($s === 'all') {
        $counts[$s] = $conn->query("SELECT COUNT(*) c FROM technicians")->fetch_assoc()['c'];
    } else {
        $counts[$s] = $conn->query("SELECT COUNT(*) c FROM technicians WHERE status='$s'")->fetch_assoc()['c'];
    }
}

$statusInfo = [
    'pending' => ['⏳ অপেক্ষমাণ', '#f59e0b'],
    'approved' => ['✅ অনুমোদিত', '#16a34a'],
    'rejected' => ['❌ বাতিল', '#dc2626'],
];
?>
<!DOCTYPE html>
<html lang="bn">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>কাজের আবেদনসমূহ</title>
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

.tech-card {
  background: white; border-radius: 15px; padding: 22px;
  margin-bottom: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.06);
  border-left: 5px solid #94a3b8;
}
.tech-head {
  display: flex; justify-content: space-between; align-items: center;
  flex-wrap: wrap; gap: 10px; margin-bottom: 15px;
}
.tech-head-left {
  display: flex; align-items: center; gap: 15px;
}
.tech-photo {
  width: 70px; height: 70px; border-radius: 50%;
  object-fit: cover; border: 3px solid #3b82f6;
}
.tech-name-id h3 { color: #1e3a8a; font-size: 18px; margin-bottom: 5px; }
.id-badge {
  display: inline-block;
  background: #1e293b; color: #4ade80;
  padding: 4px 10px; border-radius: 6px;
  font-family: 'Courier New', monospace;
  font-size: 12px; font-weight: 700;
}
.badges { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; }
.badge { padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; color: white; }
.badge.service { background: #dbeafe; color: #1e40af; }

.tech-body {
  display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: 12px; margin-bottom: 15px;
  padding-top: 15px; border-top: 1px solid #f1f5f9;
}
.field { font-size: 14px; color: #475569; }
.field b { color: #1e293b; display: block; margin-bottom: 2px; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; }

.id-photos {
  display: flex; gap: 15px; margin-bottom: 15px; flex-wrap: wrap;
}
.id-photos .photo-box {
  flex: 1; min-width: 200px;
}
.id-photos .photo-box .label {
  font-size: 12px; color: #64748b; margin-bottom: 6px;
  text-transform: uppercase; font-weight: 600;
}
.id-photos .photo-box img {
  width: 100%; max-width: 250px;
  border-radius: 10px;
  border: 2px solid #e2e8f0;
  cursor: pointer;
  transition: 0.2s;
}
.id-photos .photo-box img:hover {
  border-color: #3b82f6;
  transform: scale(1.02);
}

.actions { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; }
.actions a {
  padding: 9px 18px; border-radius: 8px; font-size: 13px;
  text-decoration: none; font-weight: 600;
  display: inline-flex; align-items: center; gap: 5px;
  transition: 0.2s;
}
.btn-approve { background: #16a34a; color: white; }
.btn-approve:hover { background: #15803d; transform: translateY(-2px); }
.btn-reject { background: #f59e0b; color: white; }
.btn-reject:hover { background: #d97706; transform: translateY(-2px); }
.btn-call { background: #0ea5e9; color: white; }
.btn-call:hover { background: #0284c7; }
.btn-delete { background: #fee2e2; color: #dc2626; }
.btn-delete:hover { background: #fecaca; }
.btn-available { background: #dcfce7; color: #16a34a; }
.btn-available:hover { background: #bbf7d0; }
.btn-unavailable { background: #fee2e2; color: #dc2626; }

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
  <h2 class="page-title">💼 কাজের আবেদনসমূহ</h2>

  <div class="filters">
    <a href="?status=all" class="filter-btn <?= $filter==='all'?'active':'' ?>">
      সব (<?= $counts['all'] ?>)
    </a>
    <a href="?status=pending" class="filter-btn <?= $filter==='pending'?'active':'' ?>">
      ⏳ অপেক্ষমাণ (<?= $counts['pending'] ?>)
    </a>
    <a href="?status=approved" class="filter-btn <?= $filter==='approved'?'active':'' ?>">
      ✅ অনুমোদিত (<?= $counts['approved'] ?>)
    </a>
    <a href="?status=rejected" class="filter-btn <?= $filter==='rejected'?'active':'' ?>">
      ❌ বাতিল (<?= $counts['rejected'] ?>)
    </a>
  </div>

  <?php if ($techs->num_rows > 0): ?>
    <?php while ($t = $techs->fetch_assoc()): 
      $st = $statusInfo[$t['status']] ?? ['অজানা', '#94a3b8'];
      $photoPath = '../uploads/photos/' . $t['photo'];
      if (!file_exists($photoPath) || !$t['photo']) {
          $photoPath = 'https://ui-avatars.com/api/?name=' . urlencode($t['name']) . '&size=150&background=16a34a&color=fff&bold=true';
      }
    ?>
    <div class="tech-card" style="border-left-color: <?= $st[1] ?>">
      <div class="tech-head">
        <div class="tech-head-left">
          <img src="<?= htmlspecialchars($photoPath) ?>" class="tech-photo" alt="photo">
          <div class="tech-name-id">
            <h3>👤 <?= htmlspecialchars($t['name']) ?></h3>
            <span class="id-badge"><?= htmlspecialchars($t['tech_id']) ?></span>
          </div>
        </div>
        <div class="badges">
          <?php if ($t['cat_name']): ?>
            <span class="badge service">
              <?= htmlspecialchars(($t['cat_icon'] ?? '') . ' ' . $t['cat_name']) ?>
              <?php if ($t['subcat_name']): ?>
                — <?= htmlspecialchars($t['subcat_name']) ?>
              <?php endif; ?>
            </span>
          <?php endif; ?>
          <span class="badge" style="background:<?= $st[1] ?>"><?= $st[0] ?></span>
        </div>
      </div>

      <div class="tech-body">
        <div class="field"><b>📞 ফোন</b><?= htmlspecialchars($t['phone']) ?></div>
        <div class="field"><b>✉️ ইমেইল</b><?= htmlspecialchars($t['email'] ?: '—') ?></div>
        <div class="field"><b>🆔 NID</b><?= htmlspecialchars($t['nid'] ?: '—') ?></div>
        <div class="field"><b>📍 ঠিকানা</b><?= htmlspecialchars($t['address']) ?></div>
        <div class="field"><b>💼 অভিজ্ঞতা</b><?= htmlspecialchars($t['experience'] ?: '—') ?></div>
        <div class="field"><b>🎓 শিক্ষা</b><?= htmlspecialchars($t['education'] ?: '—') ?></div>
        <div class="field"><b>🕐 আবেদনের সময়</b><?= date('d M Y, h:i A', strtotime($t['created_at'])) ?></div>
      </div>

      <div class="id-photos">
        <?php if ($t['nid_front'] && file_exists('../uploads/nid_front/' . $t['nid_front'])): ?>
          <div class="photo-box">
            <div class="label">🆔 NID সামনে</div>
            <a href="../uploads/nid_front/<?= htmlspecialchars($t['nid_front']) ?>" target="_blank">
              <img src="../uploads/nid_front/<?= htmlspecialchars($t['nid_front']) ?>" alt="NID Front">
            </a>
          </div>
        <?php endif; ?>
        
        <?php if ($t['nid_back'] && file_exists('../uploads/nid_back/' . $t['nid_back'])): ?>
          <div class="photo-box">
            <div class="label">🆔 NID পিছনে</div>
            <a href="../uploads/nid_back/<?= htmlspecialchars($t['nid_back']) ?>" target="_blank">
              <img src="../uploads/nid_back/<?= htmlspecialchars($t['nid_back']) ?>" alt="NID Back">
            </a>
          </div>
        <?php endif; ?>
      </div>

      <div class="actions">
        <?php if ($t['status'] === 'pending'): ?>
          <a href="?action=approve&id=<?= $t['id'] ?>" class="btn-approve"
             onclick="return confirm('অনুমোদন করবেন?')">✅ অনুমোদন করুন</a>
          <a href="?action=reject&id=<?= $t['id'] ?>" class="btn-reject"
             onclick="return confirm('বাতিল করবেন?')">❌ বাতিল</a>
        <?php elseif ($t['status'] === 'approved'): ?>
          <a href="?action=toggle&id=<?= $t['id'] ?>" 
             class="<?= $t['available'] ? 'btn-available' : 'btn-unavailable' ?>">
            <?= $t['available'] ? '🟢 উপলব্ধ' : '🔴 অনুপলব্ধ' ?>
          </a>
        <?php elseif ($t['status'] === 'rejected'): ?>
          <a href="?action=approve&id=<?= $t['id'] ?>" class="btn-approve">
            🔄 পুনরায় অনুমোদন
          </a>
        <?php endif; ?>

        <a href="tel:<?= htmlspecialchars($t['phone']) ?>" class="btn-call">📞 কল</a>
        <a href="?action=delete&id=<?= $t['id'] ?>" class="btn-delete"
           onclick="return confirm('ডিলিট করবেন? ছবিও মুছে যাবে।')">🗑 ডিলিট</a>
      </div>
    </div>
    <?php endwhile; ?>
  <?php else: ?>
    <div class="empty">
      <h3>এই ফিল্টারে কোনো আবেদন নেই</h3>
    </div>
  <?php endif; ?>
</div>

</body>
</html>