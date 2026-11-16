<?php
include 'includes/auth.php';
include '../db.php';

// Approve / Reject / Delete / Toggle
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $action = $_GET['action'];

    if ($action === 'approve') {
        $conn->query("UPDATE technicians SET status='approved' WHERE id=$id");
    } elseif ($action === 'reject') {
        $conn->query("UPDATE technicians SET status='rejected' WHERE id=$id");
    } elseif ($action === 'delete') {
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
    header("Location: technicians.php");
    exit;
}

// ============ ফিল্টার ============
$statusFilter = $_GET['status'] ?? 'all';
$catFilter = intval($_GET['category'] ?? 0);

// ক্যাটাগরি লিস্ট
$allCats = $conn->query("SELECT * FROM work_categories WHERE active=1 ORDER BY sort_order");

// প্রতিটা ক্যাটাগরির সংখ্যা
$catCounts = [];
$countResult = $conn->query("SELECT category_id, COUNT(*) as c FROM technicians GROUP BY category_id");
while ($r = $countResult->fetch_assoc()) {
    $catCounts[$r['category_id']] = $r['c'];
}

// স্ট্যাটাস কাউন্ট
$statusCounts = [];
foreach (['all','pending','approved','rejected'] as $s) {
    if ($s === 'all') {
        $statusCounts[$s] = $conn->query("SELECT COUNT(*) c FROM technicians")->fetch_assoc()['c'];
    } else {
        $statusCounts[$s] = $conn->query("SELECT COUNT(*) c FROM technicians WHERE status='$s'")->fetch_assoc()['c'];
    }
}

// মেইন কোয়েরি
$sql = "SELECT t.*, 
        c.name as cat_name, c.icon as cat_icon,
        sc.name as subcat_name
        FROM technicians t 
        LEFT JOIN work_categories c ON t.category_id = c.id 
        LEFT JOIN work_subcategories sc ON t.subcategory_id = sc.id
        WHERE 1=1";

if ($statusFilter !== 'all') {
    $sql .= " AND t.status = '" . $conn->real_escape_string($statusFilter) . "'";
}
if ($catFilter > 0) {
    $sql .= " AND t.category_id = " . $catFilter;
}
$sql .= " ORDER BY t.id DESC";

$techs = $conn->query($sql);

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
<title>সেবাদাতাগণ</title>
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

/* ক্যাটাগরি ট্যাব */
.cat-tabs {
  display: flex; flex-wrap: wrap; gap: 10px;
  margin-bottom: 25px;
  padding: 15px; background: white;
  border-radius: 15px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}
.cat-tab {
  display: inline-flex; align-items: center; gap: 8px;
  padding: 12px 20px; border-radius: 30px;
  text-decoration: none; font-size: 14px; font-weight: 700;
  background: #f1f5f9; color: #475569;
  border: 2px solid transparent;
  transition: 0.2s;
}
.cat-tab:hover {
  background: #e0e7ff; color: #1e40af;
  transform: translateY(-2px);
}
.cat-tab.active {
  background: linear-gradient(135deg, #1e3a8a, #3b82f6);
  color: white;
  box-shadow: 0 4px 15px rgba(30,58,138,0.35);
}
.cat-tab .count {
  background: rgba(255,255,255,0.3);
  padding: 2px 8px; border-radius: 10px;
  font-size: 12px;
}
.cat-tab:not(.active) .count {
  background: #cbd5e1; color: #334155;
}
.cat-tab .icon { font-size: 20px; }

/* স্ট্যাটাস ফিল্টার */
.filters { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 20px; }
.filter-btn {
  padding: 8px 16px; border-radius: 20px; text-decoration: none;
  font-size: 13px; font-weight: 600; background: white;
  color: #475569; border: 2px solid #e2e8f0;
}
.filter-btn.active { background: #1e3a8a; color: white; border-color: #1e3a8a; }

/* টেক কার্ড */
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
  width: 80px; height: 80px; border-radius: 50%;
  object-fit: cover; border: 3px solid #3b82f6;
  cursor: pointer; transition: 0.2s;
}
.tech-photo:hover { transform: scale(1.05); }
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
.id-photos .photo-box { flex: 1; min-width: 200px; }
.id-photos .photo-box .label {
  font-size: 12px; color: #64748b; margin-bottom: 6px;
  text-transform: uppercase; font-weight: 600;
}
.id-photos .photo-box img {
  width: 100%; max-width: 300px; border-radius: 10px;
  border: 2px solid #e2e8f0; cursor: pointer;
}

.actions { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; }
.actions a {
  padding: 9px 18px; border-radius: 8px; font-size: 13px;
  text-decoration: none; font-weight: 600;
  display: inline-flex; align-items: center; gap: 5px;
}
.btn-approve { background: #16a34a; color: white; }
.btn-reject { background: #f59e0b; color: white; }
.btn-call { background: #0ea5e9; color: white; }
.btn-delete { background: #fee2e2; color: #dc2626; }
.btn-available { background: #dcfce7; color: #16a34a; }
.btn-unavailable { background: #fee2e2; color: #dc2626; }

.empty {
  background: white; padding: 60px 20px; text-align: center;
  border-radius: 15px; color: #64748b;
}
.empty h3 { color: #94a3b8; margin-bottom: 10px; }
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
<div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; margin-bottom: 20px;">
  <h2 class="page-title" style="margin: 0;">👷 সব সেবাদাতা</h2>
  <div style="display: flex; gap: 8px; flex-wrap: wrap;">
    <a href="work_categories.php" 
       style="background: #16a34a; color: white; text-decoration: none; padding: 10px 18px; border-radius: 10px; font-weight: 600; font-size: 14px; display: inline-flex; align-items: center; gap: 6px;">
      ⚙️ ক্যাটাগরি ম্যানেজ
    </a>
  </div>
</div>

  <!-- ============ ক্যাটাগরি ট্যাব ============ -->
  <div class="cat-tabs">
    <a href="?category=0&status=<?= $statusFilter ?>" 
       class="cat-tab <?= $catFilter == 0 ? 'active' : '' ?>">
      <span class="icon">📋</span>
      সব সেবাদাতা
      <span class="count"><?= $statusCounts['all'] ?></span>
    </a>
    
    <?php 
      $allCats->data_seek(0);
      while ($cat = $allCats->fetch_assoc()): 
        $cnt = $catCounts[$cat['id']] ?? 0;
    ?>
      <a href="?category=<?= $cat['id'] ?>&status=<?= $statusFilter ?>" 
         class="cat-tab <?= $catFilter == $cat['id'] ? 'active' : '' ?>">
        <span class="icon"><?= htmlspecialchars($cat['icon']) ?></span>
        <?= htmlspecialchars($cat['name']) ?>
        <span class="count"><?= $cnt ?></span>
      </a>
    <?php endwhile; ?>
  </div>

  <!-- ============ স্ট্যাটাস ফিল্টার ============ -->
  <div class="filters">
    <a href="?category=<?= $catFilter ?>&status=all" 
       class="filter-btn <?= $statusFilter==='all'?'active':'' ?>">
      সব (<?= $statusCounts['all'] ?>)
    </a>
    <a href="?category=<?= $catFilter ?>&status=pending" 
       class="filter-btn <?= $statusFilter==='pending'?'active':'' ?>">
      ⏳ অপেক্ষমাণ (<?= $statusCounts['pending'] ?>)
    </a>
    <a href="?category=<?= $catFilter ?>&status=approved" 
       class="filter-btn <?= $statusFilter==='approved'?'active':'' ?>">
      ✅ অনুমোদিত (<?= $statusCounts['approved'] ?>)
    </a>
    <a href="?category=<?= $catFilter ?>&status=rejected" 
       class="filter-btn <?= $statusFilter==='rejected'?'active':'' ?>">
      ❌ বাতিল (<?= $statusCounts['rejected'] ?>)
    </a>
  </div>

  <!-- ============ কার্ড লিস্ট ============ -->
  <?php if ($techs && $techs->num_rows > 0): ?>
    <?php while ($t = $techs->fetch_assoc()): 
      $st = $statusInfo[$t['status']] ?? ['অজানা', '#94a3b8'];
      $photoPath = '../uploads/photos/' . $t['photo'];
      if (!file_exists($photoPath) || !$t['photo']) {
          $photoPath = 'https://ui-avatars.com/api/?name=' . urlencode($t['name']) . '&size=160&background=16a34a&color=fff&bold=true';
      }
    ?>
    <div class="tech-card" style="border-left-color: <?= $st[1] ?>">
      <div class="tech-head">
        <div class="tech-head-left">
          <img src="<?= htmlspecialchars($photoPath) ?>" 
               class="tech-photo" alt="photo"
               onclick="window.open(this.src, '_blank')">
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
      <h3>📭 এই ফিল্টারে কোনো সেবাদাতা নেই</h3>
      <p style="margin-top:10px;">
        <?php if ($catFilter > 0): ?>
          এই ক্যাটাগরিতে এখনো কোনো আবেদন আসেনি।
        <?php else: ?>
          এখনো কোনো আবেদন আসেনি।
        <?php endif; ?>
      </p>
    </div>
  <?php endif; ?>
</div>

</body>
</html>