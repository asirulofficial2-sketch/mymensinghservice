<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'includes/auth.php';
include '../db.php';

$msg = '';
$msgType = 'success';

// ============ নতুন ব্যানার যোগ ============
if (isset($_POST['add_banner'])) {
    $title = trim($_POST['title'] ?? '');
    $link = trim($_POST['link'] ?? '');
    $section = $_POST['section'] ?? 'home_service';
    $sort = intval($_POST['sort_order'] ?? 0);
    $slide_timer = intval($_POST['slide_timer'] ?? 4000);
    $is_fixed = isset($_POST['is_fixed']) ? 1 : 0;
    $active = isset($_POST['active']) ? 1 : 0;
    $image = null;
    
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg','jpeg','png','gif','webp'])) {
            $filename = 'banner_' . time() . '_' . rand(1000,9999) . '.' . $ext;
            $dir = __DIR__ . '/../uploads/banners/';
            if (!is_dir($dir)) mkdir($dir, 0755, true);
            if (move_uploaded_file($_FILES['image']['tmp_name'], $dir . $filename)) {
                $image = $filename;
            }
        }
    }
    
    if ($image) {
        $stmt = $conn->prepare("INSERT INTO banners (title, image, link, section, sort_order, slide_timer, is_fixed, active) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssiiii", $title, $image, $link, $section, $sort, $slide_timer, $is_fixed, $active);
        if ($stmt->execute()) {
            $msg = "✅ ব্যানার যোগ হয়েছে";
        } else {
            $msg = "❌ সমস্যা: " . $conn->error;
            $msgType = 'error';
        }
    } else {
        $msg = "❌ ছবি আপলোড ব্যর্থ হয়েছে।";
        $msgType = 'error';
    }
}

// ============ ব্যানার এডিট ============
if (isset($_POST['edit_banner'])) {
    $id = intval($_POST['banner_id']);
    $title = trim($_POST['title'] ?? '');
    $link = trim($_POST['link'] ?? '');
    $section = $_POST['section'] ?? 'home_service';
    $sort = intval($_POST['sort_order'] ?? 0);
    $slide_timer = intval($_POST['slide_timer'] ?? 4000);
    $is_fixed = isset($_POST['is_fixed']) ? 1 : 0;
    $active = isset($_POST['active']) ? 1 : 0;
    
    // যদি নতুন ছবি আপলোড করা হয়
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg','jpeg','png','gif','webp'])) {
            $filename = 'banner_' . time() . '_' . rand(1000,9999) . '.' . $ext;
            $dir = __DIR__ . '/../uploads/banners/';
            if (!is_dir($dir)) mkdir($dir, 0755, true);
            if (move_uploaded_file($_FILES['image']['tmp_name'], $dir . $filename)) {
                $old = $conn->query("SELECT image FROM banners WHERE id=$id")->fetch_assoc();
                if ($old && $old['image'] && file_exists($dir . $old['image'])) {
                    unlink($dir . $old['image']);
                }
                $stmt = $conn->prepare("UPDATE banners SET image=? WHERE id=?");
                $stmt->bind_param("si", $filename, $id);
                $stmt->execute();
            }
        }
    }
    
    $stmt = $conn->prepare("UPDATE banners SET title=?, link=?, section=?, sort_order=?, slide_timer=?, is_fixed=?, active=? WHERE id=?");
    $stmt->bind_param("sssiiiii", $title, $link, $section, $sort, $slide_timer, $is_fixed, $active, $id);
    if ($stmt->execute()) {
        $msg = "✅ ব্যানার আপডেট হয়েছে";
    } else {
        $msg = "❌ সমস্যা: " . $conn->error;
        $msgType = 'error';
    }
}

// ============ ব্যানার ডিলিট ============
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $row = $conn->query("SELECT image FROM banners WHERE id=$id")->fetch_assoc();
    if ($row && $row['image'] && file_exists('../uploads/banners/' . $row['image'])) {
        unlink('../uploads/banners/' . $row['image']);
    }
    $conn->query("DELETE FROM banners WHERE id=$id");
    header("Location: banners.php?msg=deleted");
    exit;
}

// ============ স্ট্যাটাস টগল ============
if (isset($_GET['toggle'])) {
    $id = intval($_GET['toggle']);
    $conn->query("UPDATE banners SET active = NOT active WHERE id=$id");
    header("Location: banners.php");
    exit;
}

// ============ ফিক্স টগল ============
if (isset($_GET['toggle_fix'])) {
    $id = intval($_GET['toggle_fix']);
    $conn->query("UPDATE banners SET is_fixed = NOT is_fixed WHERE id=$id");
    header("Location: banners.php");
    exit;
}

if (isset($_GET['msg']) && $_GET['msg'] == 'deleted') {
    $msg = "✅ ব্যানার ডিলিট হয়েছে";
}

// ============ এডিট ডাটা লোড ============
$editBanner = null;
if (isset($_GET['edit'])) {
    $eid = intval($_GET['edit']);
    $editBanner = $conn->query("SELECT * FROM banners WHERE id=$eid")->fetch_assoc();
}

$banners = $conn->query("SELECT * FROM banners ORDER BY section, sort_order, id");
?>
<!DOCTYPE html>
<html lang="bn">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ব্যানার ম্যানেজমেন্ট</title>
<style>
* { margin:0; padding:0; box-sizing:border-box; font-family:'Segoe UI',sans-serif; }
body { background: #f5f7fa; }
.topbar { background: linear-gradient(135deg, #7c3aed, #8b5cf6); color: white; padding: 15px 25px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; }
.topbar h1 { font-size: 18px; }
.topbar a { color: white; text-decoration: none; background: rgba(255,255,255,0.2); padding: 8px 16px; border-radius: 20px; font-size: 14px; margin-left: 8px; }
.container { max-width: 1200px; margin: 30px auto; padding: 0 20px 50px; }
h2.page-title { color: #7c3aed; font-size: 22px; margin-bottom: 20px; }
.msg-box { background: #dcfce7; border: 2px solid #16a34a; color: #166534; padding: 12px 20px; border-radius: 10px; margin-bottom: 20px; font-weight: 600; }
.msg-box.error { background: #fee2e2; border-color: #dc2626; color: #dc2626; }
.card { background: white; padding: 25px; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.06); margin-bottom: 25px; }
.card h3 { color: #7c3aed; margin-bottom: 20px; font-size: 17px; padding-bottom: 12px; border-bottom: 2px solid #f1f5f9; }
label { display: block; margin-bottom: 6px; font-weight: 600; color: #334155; font-size: 13px; }
input[type=text], input[type=number], select, input[type=file] { width: 100%; padding: 10px 14px; margin-bottom: 15px; border: 2px solid #e2e8f0; border-radius: 10px; font-size: 14px; font-family: inherit; background: white; }
input:focus, select:focus { outline: none; border-color: #7c3aed; }
.form-row-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px; }
.checkbox-row { display: flex; align-items: center; gap: 8px; margin-bottom: 15px; font-size: 14px; background: #f8fafc; padding: 10px; border-radius: 8px; }
.checkbox-row input { width: 18px; height: 18px; margin: 0; }
.btn { padding: 12px 24px; border-radius: 10px; font-size: 15px; font-weight: 700; cursor: pointer; border: none; font-family: inherit; width: 100%; }
.btn-primary { background: #7c3aed; color: white; }
.btn-primary:hover { background: #6d28d9; }
.btn-cancel { background: #e2e8f0; color: #334155; display: block; text-align: center; margin-top: 10px; text-decoration: none; }
.section-group { margin-bottom: 35px; }
.section-title { background: #1e3a8a; color: white; padding: 12px 18px; border-radius: 10px; font-weight: 700; font-size: 15px; margin-bottom: 15px; }
.banner-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px,1fr)); gap: 18px; }
.banner-card { background: white; border-radius: 12px; overflow: hidden; border: 2px solid #e2e8f0; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
.banner-card.inactive { opacity: 0.55; }
.banner-card img { width: 100%; height: 140px; object-fit: cover; display: block; }
.banner-card .info { padding: 12px 15px; }
.banner-card .info h4 { font-size: 15px; color: #1e3a8a; margin-bottom: 6px; font-weight: 700; }
.banner-card .info .meta { font-size: 11px; color: #64748b; display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 8px; }
.banner-card .info .meta span { background: #f1f5f9; padding: 3px 8px; border-radius: 10px; font-weight: 600; }
.banner-card .info .link { font-size: 11px; color: #7c3aed; word-break: break-all; margin-bottom: 8px; }
.banner-card .actions { display: flex; gap: 6px; padding: 10px 15px; border-top: 1px solid #e2e8f0; }
.banner-card .actions a, .banner-card .actions button { flex: 1; text-align: center; padding: 7px; border-radius: 6px; font-size: 12px; text-decoration: none; font-weight: 700; border: none; cursor: pointer; font-family: inherit; }
.btn-toggle { background: #fef3c7; color: #d97706; }
.btn-toggle.active { background: #dcfce7; color: #16a34a; }
.btn-fix { background: #dbeafe; color: #1e40af; }
.btn-fix.active { background: #bfdbfe; color: #1e3a8a; }
.btn-edit { background: #ede9fe; color: #6d28d9; }
.btn-del { background: #fee2e2; color: #dc2626; }
.empty { background: #f8fafc; border: 2px dashed #cbd5e1; padding: 30px; border-radius: 10px; text-align: center; color: #94a3b8; font-size: 14px; }
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
  <h2 class="page-title">🎬 ব্যানার স্লাইডার ম্যানেজমেন্ট</h2>

  <?php if ($msg): ?>
    <div class="msg-box <?= $msgType === 'error' ? 'error' : '' ?>">
      <?= htmlspecialchars($msg) ?>
    </div>
  <?php endif; ?>

  <div class="card">
    <h3><?= $editBanner ? '✏️ ব্যানার এডিট করুন' : '➕ নতুন ব্যানার যোগ করুন' ?></h3>
    <form method="POST" enctype="multipart/form-data">
      <?php if ($editBanner): ?>
          <input type="hidden" name="banner_id" value="<?= $editBanner['id'] ?>">
      <?php endif; ?>

      <label>কোথায় দেখাবে? *</label>
      <select name="section" required>
        <option value="home_service" <?= (isset($editBanner['section']) && $editBanner['section'] == 'home_service') ? 'selected' : '' ?>>🏠 হোম পেজ - সার্ভিস স্লাইডার</option>
        <option value="home_product" <?= (isset($editBanner['section']) && $editBanner['section'] == 'home_product') ? 'selected' : '' ?>>🏠 হোম পেজ - প্রোডাক্ট স্লাইডার</option>
        <option value="services_page" <?= (isset($editBanner['section']) && $editBanner['section'] == 'services_page') ? 'selected' : '' ?>>📞 সার্ভিস অর্ডার পেজ - স্লাইডার</option>
        <option value="shop_page" <?= (isset($editBanner['section']) && $editBanner['section'] == 'shop_page') ? 'selected' : '' ?>>🛒 শপ পেজ - স্লাইডার</option>
      </select>
      
      <label>টাইটেল</label>
      <input type="text" name="title" placeholder="যেমন: ইলেকট্রিক সার্ভিস" value="<?= htmlspecialchars($editBanner['title'] ?? '') ?>">
      
      <label>লিংক</label>
      <input type="text" name="link" placeholder="যেমন: book.php?service=1" value="<?= htmlspecialchars($editBanner['link'] ?? '') ?>">
      
      <div class="form-row-3">
        <div>
            <label>ক্রম</label>
            <input type="number" name="sort_order" value="<?= htmlspecialchars($editBanner['sort_order'] ?? '0') ?>">
        </div>
        <div>
            <label>স্লাইড টাইম (ms)</label>
            <input type="number" name="slide_timer" value="<?= htmlspecialchars($editBanner['slide_timer'] ?? '4000') ?>" min="1000">
        </div>
        <div>
            <label>ছবি <?= $editBanner ? '(নতুন দিলে বদলে যাবে)' : '*' ?></label>
            <input type="file" name="image" accept="image/*" <?= $editBanner ? '' : 'required' ?>>
        </div>
      </div>

      <div class="checkbox-row">
        <input type="checkbox" name="is_fixed" id="is_fixed" <?= (isset($editBanner['is_fixed']) && $editBanner['is_fixed']) ? 'checked' : '' ?>>
        <label for="is_fixed" style="margin:0;">📌 এই ব্যানারটি ফিক্স করুন (স্লাইড হবে না)</label>
      </div>
      <div class="checkbox-row">
        <input type="checkbox" name="active" id="active" <?= (!isset($editBanner['active']) || $editBanner['active']) ? 'checked' : '' ?>>
        <label for="active" style="margin:0;">✅ সক্রিয়</label>
      </div>

      <?php if ($editBanner): ?>
          <button type="submit" name="edit_banner" class="btn btn-primary">💾 আপডেট করুন</button>
          <a href="banners.php" class="btn-cancel">বাতিল করুন</a>
      <?php else: ?>
          <button type="submit" name="add_banner" class="btn btn-primary">➕ যোগ করুন</button>
      <?php endif; ?>
    </form>
  </div>

  <?php 
  $sections = [
    'home_service' => '🏠 হোম পেজ - সার্ভিস স্লাইডার',
    'home_product' => '🏠 হোম পেজ - প্রোডাক্ট স্লাইডার',
    'services_page' => '📞 সার্ভিস অর্ডার পেজ - স্লাইডার',
    'shop_page' => '🛒 শপ পেজ - স্লাইডার',
  ];
  $allBanners = [];
  $banners->data_seek(0);
  while ($b = $banners->fetch_assoc()) { $allBanners[$b['section']][] = $b; }
  ?>

  <?php foreach ($sections as $key => $label): ?>
    <div class="section-group">
      <div class="section-title"><?= $label ?></div>
      <?php if (!empty($allBanners[$key])): ?>
        <div class="banner-grid">
          <?php foreach ($allBanners[$key] as $b): ?>
            <div class="banner-card <?= $b['active'] ? '' : 'inactive' ?>">
              <img src="../uploads/banners/<?= htmlspecialchars($b['image']) ?>" alt="">
              <div class="info">
                <h4><?= htmlspecialchars($b['title'] ?: 'শিরোনামহীন') ?></h4>
                <div class="meta">
                  <span>⏱️ <?= $b['slide_timer'] ?>ms</span>
                  <span>#<?= $b['sort_order'] ?></span>
                  <?php if ($b['is_fixed']): ?><span style="background:#dbeafe; color:#1e40af;">📌 ফিক্স</span><?php endif; ?>
                </div>
                <div class="link"><?= htmlspecialchars($b['link'] ?: 'লিংক নেই') ?></div>
              </div>
              <div class="actions">
                <a href="?edit=<?= $b['id'] ?>" class="btn-edit">✏️ এডিট</a>
                <a href="?toggle_fix=<?= $b['id'] ?>" class="btn-fix <?= $b['is_fixed'] ? 'active' : '' ?>">📌</a>
                <a href="?toggle=<?= $b['id'] ?>" class="btn-toggle <?= $b['active'] ? 'active' : '' ?>"><?= $b['active'] ? '👁️' : '🙈' ?></a>
                <a href="?delete=<?= $b['id'] ?>" class="btn-del" onclick="return confirm('এই ব্যানারটি ডিলিট করবেন?')">🗑</a>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <div class="empty">এই সেকশনে এখনো কোনো ব্যানার নেই</div>
      <?php endif; ?>
    </div>
  <?php endforeach; ?>

</div>
</body>
</html>