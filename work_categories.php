<?php
include 'includes/auth.php';
include '../db.php';

// ============ ক্যাটাগরি হ্যান্ডলিং ============
$msg = '';

// নতুন ক্যাটাগরি যোগ
if (isset($_POST['add_category'])) {
    $name = trim($_POST['cat_name'] ?? '');
    $icon = trim($_POST['cat_icon'] ?? '');
    $sort = intval($_POST['cat_sort'] ?? 0);
    if ($name) {
        $stmt = $conn->prepare("INSERT INTO work_categories (name, icon, sort_order) VALUES (?, ?, ?)");
        $stmt->bind_param("ssi", $name, $icon, $sort);
        $stmt->execute();
        $msg = "✅ ক্যাটাগরি যোগ হয়েছে";
    }
}

// ক্যাটাগরি এডিট
if (isset($_POST['edit_category'])) {
    $id = intval($_POST['cat_id']);
    $name = trim($_POST['cat_name'] ?? '');
    $icon = trim($_POST['cat_icon'] ?? '');
    $sort = intval($_POST['cat_sort'] ?? 0);
    $active = isset($_POST['cat_active']) ? 1 : 0;
    if ($name && $id) {
        $stmt = $conn->prepare("UPDATE work_categories SET name=?, icon=?, sort_order=?, active=? WHERE id=?");
        $stmt->bind_param("ssiii", $name, $icon, $sort, $active, $id);
        $stmt->execute();
        $msg = "✅ ক্যাটাগরি আপডেট হয়েছে";
    }
}

// ক্যাটাগরি ডিলিট
if (isset($_GET['del_cat'])) {
    $id = intval($_GET['del_cat']);
    $conn->query("DELETE FROM work_subcategories WHERE category_id=$id");
    $conn->query("DELETE FROM work_categories WHERE id=$id");
    header("Location: work_categories.php?msg=deleted");
    exit;
}

// ============ সাব-ক্যাটাগরি হ্যান্ডলিং ============

// নতুন সাব-ক্যাটাগরি
if (isset($_POST['add_sub'])) {
    $cat_id = intval($_POST['sub_cat_id']);
    $name = trim($_POST['sub_name'] ?? '');
    $sort = intval($_POST['sub_sort'] ?? 0);
    if ($name && $cat_id) {
        $stmt = $conn->prepare("INSERT INTO work_subcategories (category_id, name, sort_order) VALUES (?, ?, ?)");
        $stmt->bind_param("isi", $cat_id, $name, $sort);
        $stmt->execute();
        $msg = "✅ সাব-ক্যাটাগরি যোগ হয়েছে";
    }
}

// সাব-ক্যাটাগরি এডিট
if (isset($_POST['edit_sub'])) {
    $id = intval($_POST['sub_id']);
    $name = trim($_POST['sub_name'] ?? '');
    $sort = intval($_POST['sub_sort'] ?? 0);
    $active = isset($_POST['sub_active']) ? 1 : 0;
    if ($name && $id) {
        $stmt = $conn->prepare("UPDATE work_subcategories SET name=?, sort_order=?, active=? WHERE id=?");
        $stmt->bind_param("siii", $name, $sort, $active, $id);
        $stmt->execute();
        $msg = "✅ সাব-ক্যাটাগরি আপডেট হয়েছে";
    }
}

// সাব-ক্যাটাগরি ডিলিট
if (isset($_GET['del_sub'])) {
    $id = intval($_GET['del_sub']);
    $conn->query("DELETE FROM work_subcategories WHERE id=$id");
    header("Location: work_categories.php?msg=deleted");
    exit;
}

// ============ ডাটা লোড ============
$categories = $conn->query("SELECT * FROM work_categories ORDER BY sort_order, id");

// এডিট মোড
$editCat = null;
if (isset($_GET['edit_cat'])) {
    $eid = intval($_GET['edit_cat']);
    $editCat = $conn->query("SELECT * FROM work_categories WHERE id=$eid")->fetch_assoc();
}
$editSub = null;
if (isset($_GET['edit_sub'])) {
    $eid = intval($_GET['edit_sub']);
    $editSub = $conn->query("SELECT * FROM work_subcategories WHERE id=$eid")->fetch_assoc();
}
?>
<!DOCTYPE html>
<html lang="bn">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ক্যাটাগরি ম্যানেজমেন্ট</title>
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
.container { max-width: 1100px; margin: 30px auto; padding: 0 20px 50px; }
h2.page-title { color: #1e3a8a; font-size: 22px; margin-bottom: 20px; }

.msg-box {
  background: #dcfce7; border: 2px solid #16a34a;
  color: #166534; padding: 12px 20px;
  border-radius: 10px; margin-bottom: 20px; font-weight: 600;
}

.grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 25px; }
@media (max-width: 800px) { .grid-2 { grid-template-columns: 1fr; } }

.card {
  background: white; padding: 25px; border-radius: 15px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.06); margin-bottom: 20px;
}
.card h3 {
  color: #1e3a8a; margin-bottom: 20px; font-size: 17px;
  padding-bottom: 12px; border-bottom: 2px solid #f1f5f9;
}

.form-group { margin-bottom: 15px; }
.form-group label {
  display: block; font-size: 13px; font-weight: 600;
  color: #475569; margin-bottom: 6px;
}
.form-group input[type=text],
.form-group input[type=number],
.form-group select {
  width: 100%; padding: 10px 14px;
  border: 2px solid #e2e8f0; border-radius: 10px;
  font-size: 14px; font-family: inherit;
}
.form-group input:focus, .form-group select:focus {
  outline: none; border-color: #3b82f6;
}
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.checkbox-row {
  display: flex; align-items: center; gap: 8px;
  margin-bottom: 15px; font-size: 14px;
}
.btn {
  padding: 11px 22px; border-radius: 10px;
  font-size: 14px; font-weight: 700; cursor: pointer;
  border: none; font-family: inherit; transition: 0.2s;
}
.btn-primary { background: #16a34a; color: white; width: 100%; }
.btn-primary:hover { background: #15803d; }
.btn-update { background: #2563eb; color: white; width: 100%; }
.btn-update:hover { background: #1e40af; }
.btn-cancel {
  background: #e2e8f0; color: #334155; text-decoration: none;
  display: inline-block; padding: 11px 22px; border-radius: 10px;
  font-size: 14px; font-weight: 700; text-align: center;
}

.cat-list {
  list-style: none;
}
.cat-item {
  background: #f8fafc; padding: 15px;
  border-radius: 12px; margin-bottom: 10px;
  border-left: 4px solid #3b82f6;
}
.cat-header {
  display: flex; justify-content: space-between;
  align-items: center; margin-bottom: 10px;
  flex-wrap: wrap; gap: 10px;
}
.cat-header .name {
  font-weight: 700; color: #1e3a8a; font-size: 15px;
}
.cat-header .actions {
  display: flex; gap: 6px;
}
.cat-header .actions a {
  padding: 5px 10px; border-radius: 6px;
  font-size: 12px; text-decoration: none;
  font-weight: 600;
}
.btn-edit-sm { background: #dbeafe; color: #1e40af; }
.btn-del-sm { background: #fee2e2; color: #dc2626; }

.sub-list {
  list-style: none; padding-left: 15px;
  border-left: 2px dashed #cbd5e1; margin-top: 8px;
}
.sub-list li {
  padding: 8px 10px; font-size: 13px;
  color: #475569; display: flex;
  justify-content: space-between; align-items: center;
  background: white; border-radius: 8px;
  margin-bottom: 6px;
  flex-wrap: wrap; gap: 8px;
}
.sub-list .sub-actions { display: flex; gap: 5px; }
.sub-list .sub-actions a {
  padding: 3px 8px; border-radius: 5px;
  font-size: 11px; text-decoration: none;
  font-weight: 600;
}

.add-sub-form {
  display: flex; gap: 8px; margin-top: 12px;
  padding: 10px; background: #eff6ff;
  border-radius: 8px; flex-wrap: wrap;
}
.add-sub-form input {
  flex: 1; min-width: 150px;
  padding: 8px 12px; border: 2px solid #e2e8f0;
  border-radius: 8px; font-size: 13px;
  font-family: inherit;
}
.add-sub-form button {
  padding: 8px 16px; background: #2563eb;
  color: white; border: none; border-radius: 8px;
  font-size: 13px; font-weight: 600; cursor: pointer;
}

.inactive { opacity: 0.5; }
.badge-inactive {
  background: #fee2e2; color: #dc2626;
  padding: 2px 8px; border-radius: 10px;
  font-size: 11px; font-weight: 600;
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
  <h2 class="page-title">📁 কাজের ক্যাটাগরি ম্যানেজমেন্ট</h2>

  <?php if ($msg || isset($_GET['msg'])): ?>
    <div class="msg-box"><?= htmlspecialchars($msg ?: '✅ সম্পন্ন হয়েছে') ?></div>
  <?php endif; ?>

  <div class="grid-2">

    <!-- ============ ক্যাটাগরি ফর্ম ============ -->
    <div class="card">
      <h3><?= $editCat ? '✏️ ক্যাটাগরি এডিট' : '➕ নতুন ক্যাটাগরি' ?></h3>
      
      <form method="POST">
        <?php if ($editCat): ?>
          <input type="hidden" name="cat_id" value="<?= $editCat['id'] ?>">
        <?php endif; ?>

        <div class="form-group">
          <label>ক্যাটাগরির নাম *</label>
          <input type="text" name="cat_name" required
                 placeholder="যেমন: টেকনিশিয়ান"
                 value="<?= htmlspecialchars($editCat['name'] ?? '') ?>">
        </div>

        <div class="form-row">
          <div class="form-group">
            <label>আইকন (ইমোজি)</label>
            <input type="text" name="cat_icon" 
                   placeholder="🔧"
                   value="<?= htmlspecialchars($editCat['icon'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label>ক্রম</label>
            <input type="number" name="cat_sort" 
                   value="<?= htmlspecialchars($editCat['sort_order'] ?? '0') ?>">
          </div>
        </div>

        <?php if ($editCat): ?>
          <div class="checkbox-row">
            <input type="checkbox" name="cat_active" id="cat_active"
                   <?= $editCat['active'] ? 'checked' : '' ?>>
            <label for="cat_active" style="margin:0;">সক্রিয়</label>
          </div>
          <button type="submit" name="edit_category" class="btn btn-update">💾 আপডেট করুন</button>
          <a href="work_categories.php" class="btn-cancel" style="margin-top:8px;display:block;">বাতিল</a>
        <?php else: ?>
          <button type="submit" name="add_category" class="btn btn-primary">➕ যোগ করুন</button>
        <?php endif; ?>
      </form>
    </div>

    <!-- ============ সাব-ক্যাটাগরি ফর্ম ============ -->
    <div class="card">
      <h3><?= $editSub ? '✏️ সাব-ক্যাটাগরি এডিট' : '➕ নতুন সাব-ক্যাটাগরি' ?></h3>
      
      <form method="POST">
        <?php if ($editSub): ?>
          <input type="hidden" name="sub_id" value="<?= $editSub['id'] ?>">
        <?php else: ?>
          <div class="form-group">
            <label>কোন ক্যাটাগরির আওতায়? *</label>
            <select name="sub_cat_id" required>
              <option value="">-- ক্যাটাগরি বাছুন --</option>
              <?php 
                $catList = $conn->query("SELECT * FROM work_categories ORDER BY sort_order");
                while ($c = $catList->fetch_assoc()): 
              ?>
                <option value="<?= $c['id'] ?>">
                  <?= htmlspecialchars(($c['icon'] ?? '') . ' ' . $c['name']) ?>
                </option>
              <?php endwhile; ?>
            </select>
          </div>
        <?php endif; ?>

        <div class="form-group">
          <label>সাব-ক্যাটাগরির নাম *</label>
          <input type="text" name="sub_name" required
                 placeholder="যেমন: ইলেকট্রিশিয়ান"
                 value="<?= htmlspecialchars($editSub['name'] ?? '') ?>">
        </div>

        <div class="form-group">
          <label>ক্রম</label>
          <input type="number" name="sub_sort" 
                 value="<?= htmlspecialchars($editSub['sort_order'] ?? '0') ?>">
        </div>

        <?php if ($editSub): ?>
          <div class="checkbox-row">
            <input type="checkbox" name="sub_active" id="sub_active"
                   <?= $editSub['active'] ? 'checked' : '' ?>>
            <label for="sub_active" style="margin:0;">সক্রিয়</label>
          </div>
          <button type="submit" name="edit_sub" class="btn btn-update">💾 আপডেট করুন</button>
          <a href="work_categories.php" class="btn-cancel" style="margin-top:8px;display:block;">বাতিল</a>
        <?php else: ?>
          <button type="submit" name="add_sub" class="btn btn-primary">➕ যোগ করুন</button>
        <?php endif; ?>
      </form>
    </div>

  </div>

  <!-- ============ সব ক্যাটাগরি ও সাব-ক্যাটাগরি ============ -->
  <div class="card">
    <h3>📋 সব ক্যাটাগরি ও সাব-ক্যাটাগরি</h3>

    <ul class="cat-list">
      <?php while ($cat = $categories->fetch_assoc()): ?>
        <li class="cat-item <?= $cat['active'] ? '' : 'inactive' ?>">
          <div class="cat-header">
            <span class="name">
              <?= htmlspecialchars(($cat['icon'] ?? '') . ' ' . $cat['name']) ?>
              <?php if (!$cat['active']): ?>
                <span class="badge-inactive">নিষ্ক্রিয়</span>
              <?php endif; ?>
            </span>
            <div class="actions">
              <a href="?edit_cat=<?= $cat['id'] ?>" class="btn-edit-sm">✏️ এডিট</a>
              <a href="?del_cat=<?= $cat['id'] ?>" class="btn-del-sm"
                 onclick="return confirm('ক্যাটাগরি এবং এর সব সাব-ক্যাটাগরি ডিলিট হবে। নিশ্চিত?')">
                 🗑 ডিলিট
              </a>
            </div>
          </div>

          <?php 
            $cid = $cat['id'];
            $subs = $conn->query("SELECT * FROM work_subcategories WHERE category_id=$cid ORDER BY sort_order, id");
          ?>
          
          <?php if ($subs->num_rows > 0): ?>
          <ul class="sub-list">
            <?php while ($sub = $subs->fetch_assoc()): ?>
              <li class="<?= $sub['active'] ? '' : 'inactive' ?>">
                <span>
                  └ <?= htmlspecialchars($sub['name']) ?>
                  <?php if (!$sub['active']): ?>
                    <span class="badge-inactive">নিষ্ক্রিয়</span>
                  <?php endif; ?>
                </span>
                <span class="sub-actions">
                  <a href="?edit_sub=<?= $sub['id'] ?>" class="btn-edit-sm">✏️</a>
                  <a href="?del_sub=<?= $sub['id'] ?>" class="btn-del-sm"
                     onclick="return confirm('সাব-ক্যাটাগরি ডিলিট করবেন?')">🗑</a>
                </span>
              </li>
            <?php endwhile; ?>
          </ul>
          <?php endif; ?>

          <!-- ইনলাইন সাব-ক্যাটাগরি অ্যাড -->
          <form method="POST" class="add-sub-form">
            <input type="hidden" name="sub_cat_id" value="<?= $cat['id'] ?>">
            <input type="text" name="sub_name" required placeholder="নতুন সাব-ক্যাটাগরি...">
            <input type="number" name="sub_sort" value="0" style="max-width:80px;">
            <button type="submit" name="add_sub">➕ যোগ</button>
          </form>

        </li>
      <?php endwhile; ?>
    </ul>
  </div>

</div>

</body>
</html>