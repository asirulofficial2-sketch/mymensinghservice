<?php
include 'includes/auth.php';
include '../db.php';

$msg = '';
$msgType = 'success';

// ============ ক্যাটাগরি হ্যান্ডলিং ============
if (isset($_POST['add_cat'])) {
    $name = trim($_POST['cat_name'] ?? '');
    $icon = trim($_POST['cat_icon'] ?? '');
    $sort = intval($_POST['cat_sort'] ?? 0);
    $image = null;
    
    if (isset($_FILES['cat_image']) && $_FILES['cat_image']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['cat_image']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg','jpeg','png','gif','webp'])) {
            $filename = 'cat_' . time() . '_' . rand(1000,9999) . '.' . $ext;
            $dir = __DIR__ . '/../uploads/shop_categories/';
            if (!is_dir($dir)) mkdir($dir, 0755, true);
            if (move_uploaded_file($_FILES['cat_image']['tmp_name'], $dir . $filename)) {
                $image = $filename;
            }
        }
    }
    
    if ($name) {
        $stmt = $conn->prepare("INSERT INTO shop_categories (name, icon, image, sort_order) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sssi", $name, $icon, $image, $sort);
        $stmt->execute();
        $msg = "✅ ক্যাটাগরি যোগ হয়েছে";
    }
}

// ============ ক্যাটাগরি এডিট ============
if (isset($_POST['edit_cat'])) {
    $id = intval($_POST['cat_id']);
    $name = trim($_POST['cat_name'] ?? '');
    $icon = trim($_POST['cat_icon'] ?? '');
    $sort = intval($_POST['cat_sort'] ?? 0);
    $active = isset($_POST['cat_active']) ? 1 : 0;
    $remove_image = isset($_POST['remove_cat_image']) ? 1 : 0;
    
    // বর্তমান ছবি লোড
    $current = $conn->query("SELECT image FROM shop_categories WHERE id=$id")->fetch_assoc();
    $image = $current['image'] ?? null;
    
    // নতুন ছবি আপলোড হলে
    if (isset($_FILES['cat_image']) && $_FILES['cat_image']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['cat_image']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg','jpeg','png','gif','webp'])) {
            $filename = 'cat_' . time() . '_' . rand(1000,9999) . '.' . $ext;
            $dir = __DIR__ . '/../uploads/shop_categories/';
            if (move_uploaded_file($_FILES['cat_image']['tmp_name'], $dir . $filename)) {
                // পুরনো ছবি ডিলিট
                if ($image && file_exists($dir . $image)) {
                    unlink($dir . $image);
                }
                $image = $filename;
            }
        }
    }
    
    // ছবি মুছে ফেলা
    if ($remove_image) {
        if ($image && file_exists(__DIR__ . '/../uploads/shop_categories/' . $image)) {
            unlink(__DIR__ . '/../uploads/shop_categories/' . $image);
        }
        $image = null;
    }
    
    if ($name) {
        $stmt = $conn->prepare("UPDATE shop_categories SET name=?, icon=?, image=?, sort_order=?, active=? WHERE id=?");
        $stmt->bind_param("sssiii", $name, $icon, $image, $sort, $active, $id);
        $stmt->execute();
        $msg = "✅ ক্যাটাগরি আপডেট হয়েছে";
    }
}

// ============ ক্যাটাগরি ডিলিট ============
if (isset($_GET['del_cat'])) {
    $id = intval($_GET['del_cat']);
    
    // প্রোডাক্ট আছে কি চেক
    $pCount = $conn->query("SELECT COUNT(*) as c FROM products WHERE category_id=$id")->fetch_assoc()['c'];
    
    if ($pCount > 0) {
        $msg = "❌ এই ক্যাটাগরিতে $pCount টি প্রোডাক্ট আছে! প্রথমে প্রোডাক্ট ডিলিট করুন বা অন্য ক্যাটাগরিতে সরান।";
        $msgType = 'error';
    } else {
        // ছবি ডিলিট
        $old = $conn->query("SELECT image FROM shop_categories WHERE id=$id")->fetch_assoc();
        if ($old && $old['image'] && file_exists('../uploads/shop_categories/' . $old['image'])) {
            unlink('../uploads/shop_categories/' . $old['image']);
        }
        // সাব-ক্যাটাগরি ডিলিট
        $subs = $conn->query("SELECT id FROM shop_subcategories WHERE category_id=$id");
        while ($s = $subs->fetch_assoc()) {
            $conn->query("DELETE FROM shop_subcategories WHERE id=" . $s['id']);
        }
        // ক্যাটাগরি ডিলিট
        $conn->query("DELETE FROM shop_categories WHERE id=$id");
        $msg = "✅ ক্যাটাগরি ও এর সাব-ক্যাটাগরি ডিলিট হয়েছে";
    }
    
    header("Location: shop_categories.php?msg=" . urlencode($msg) . "&type=" . $msgType);
    exit;
}

// ============ ক্যাটাগরির ছবি ডিলিট (আলাদা) ============
if (isset($_GET['del_cat_img'])) {
    $id = intval($_GET['del_cat_img']);
    $old = $conn->query("SELECT image FROM shop_categories WHERE id=$id")->fetch_assoc();
    if ($old && $old['image'] && file_exists('../uploads/shop_categories/' . $old['image'])) {
        unlink('../uploads/shop_categories/' . $old['image']);
    }
    $conn->query("UPDATE shop_categories SET image=NULL WHERE id=$id");
    header("Location: shop_categories.php?msg=" . urlencode('✅ ক্যাটাগরির ছবি ডিলিট হয়েছে'));
    exit;
}

// ============ সাব-ক্যাটাগরি হ্যান্ডলিং ============
if (isset($_POST['add_sub'])) {
    $cat_id = intval($_POST['sub_cat_id']);
    $name = trim($_POST['sub_name'] ?? '');
    $sort = intval($_POST['sub_sort'] ?? 0);
    if ($name && $cat_id) {
        $stmt = $conn->prepare("INSERT INTO shop_subcategories (category_id, name, sort_order) VALUES (?, ?, ?)");
        $stmt->bind_param("isi", $cat_id, $name, $sort);
        $stmt->execute();
        $msg = "✅ সাব-ক্যাটাগরি যোগ হয়েছে";
    }
}

if (isset($_POST['edit_sub'])) {
    $id = intval($_POST['sub_id']);
    $name = trim($_POST['sub_name'] ?? '');
    $sort = intval($_POST['sub_sort'] ?? 0);
    $active = isset($_POST['sub_active']) ? 1 : 0;
    if ($name) {
        $stmt = $conn->prepare("UPDATE shop_subcategories SET name=?, sort_order=?, active=? WHERE id=?");
        $stmt->bind_param("siii", $name, $sort, $active, $id);
        $stmt->execute();
        $msg = "✅ সাব-ক্যাটাগরি আপডেট হয়েছে";
    }
}

if (isset($_GET['del_sub'])) {
    $id = intval($_GET['del_sub']);
    $pCount = $conn->query("SELECT COUNT(*) as c FROM products WHERE subcategory_id=$id")->fetch_assoc()['c'];
    if ($pCount > 0) {
        $msg = "❌ এই সাব-ক্যাটাগরিতে $pCount টি প্রোডাক্ট আছে!";
        $msgType = 'error';
    } else {
        $conn->query("DELETE FROM shop_subcategories WHERE id=$id");
        $msg = "✅ সাব-ক্যাটাগরি ডিলিট হয়েছে";
    }
    header("Location: shop_categories.php?msg=" . urlencode($msg) . "&type=" . $msgType);
    exit;
}

// URL থেকে মেসেজ
if (isset($_GET['msg'])) {
    $msg = $_GET['msg'];
    $msgType = $_GET['type'] ?? 'success';
}

// ডাটা লোড
$categories = $conn->query("SELECT * FROM shop_categories ORDER BY sort_order, id");

$editCat = null;
if (isset($_GET['edit_cat'])) {
    $eid = intval($_GET['edit_cat']);
    $editCat = $conn->query("SELECT * FROM shop_categories WHERE id=$eid")->fetch_assoc();
}
$editSub = null;
if (isset($_GET['edit_sub'])) {
    $eid = intval($_GET['edit_sub']);
    $editSub = $conn->query("SELECT * FROM shop_subcategories WHERE id=$eid")->fetch_assoc();
}
?>
<!DOCTYPE html>
<html lang="bn">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>শপ ক্যাটাগরি ম্যানেজমেন্ট</title>
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
.container { max-width: 1200px; margin: 30px auto; padding: 0 20px 50px; }
h2.page-title { color: #1e3a8a; font-size: 22px; margin-bottom: 20px; }

.msg-box {
  padding: 14px 20px;
  border-radius: 10px;
  margin-bottom: 20px;
  font-weight: 600;
  display: flex; justify-content: space-between; align-items: center;
}
.msg-success { background: #dcfce7; color: #166534; border: 2px solid #16a34a; }
.msg-error { background: #fee2e2; color: #dc2626; border: 2px solid #dc2626; }
.msg-box .close-x {
  cursor: pointer; font-size: 20px;
  font-weight: 700; opacity: 0.6;
}
.msg-box .close-x:hover { opacity: 1; }

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
.form-group select,
.form-group input[type=file] {
  width: 100%; padding: 10px 14px;
  border: 2px solid #e2e8f0; border-radius: 10px;
  font-size: 14px; font-family: inherit;
}
.form-group input:focus, .form-group select:focus { outline: none; border-color: #3b82f6; }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.checkbox-row {
  display: flex; align-items: center; gap: 8px;
  margin-bottom: 15px; font-size: 14px;
}
.btn {
  padding: 11px 22px; border-radius: 10px;
  font-size: 14px; font-weight: 700; cursor: pointer;
  border: none; font-family: inherit;
}
.btn-primary { background: #16a34a; color: white; width: 100%; }
.btn-primary:hover { background: #15803d; }
.btn-update { background: #2563eb; color: white; width: 100%; }
.btn-update:hover { background: #1e40af; }
.btn-cancel {
  background: #e2e8f0; color: #334155; text-decoration: none;
  display: inline-block; padding: 11px 22px; border-radius: 10px;
  font-size: 14px; font-weight: 700; text-align: center; margin-top: 8px;
}

/* বর্তমান ছবি বক্স */
.current-image-box {
  background: #f8fafc;
  padding: 15px;
  border-radius: 12px;
  text-align: center;
  margin-bottom: 15px;
  border: 2px dashed #e2e8f0;
}
.current-image-box img {
  max-width: 120px;
  border-radius: 10px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.1);
  margin-bottom: 10px;
}
.current-image-box .btn-remove-img {
  display: inline-block;
  background: #fee2e2;
  color: #dc2626;
  padding: 8px 16px;
  border-radius: 8px;
  font-size: 13px;
  font-weight: 700;
  text-decoration: none;
  transition: 0.2s;
}
.current-image-box .btn-remove-img:hover { background: #fecaca; }
.current-image-box .no-img {
  color: #94a3b8;
  font-size: 14px;
  padding: 15px;
}

.cat-item {
  background: #f8fafc; padding: 18px;
  border-radius: 12px; margin-bottom: 12px;
  border-left: 5px solid #3b82f6;
}
.cat-header {
  display: flex; justify-content: space-between;
  align-items: center; margin-bottom: 12px;
  flex-wrap: wrap; gap: 10px;
}
.cat-header .name { 
  font-weight: 700; color: #1e3a8a; font-size: 16px; 
  display: flex; align-items: center; gap: 10px;
  flex-wrap: wrap;
}
.cat-header .name img { 
  width: 50px; height: 50px; 
  border-radius: 10px; 
  object-fit: cover;
  border: 2px solid #e2e8f0;
}
.cat-header .name .emoji { font-size: 30px; }
.cat-header .actions { display: flex; gap: 8px; flex-wrap: wrap; }
.cat-header .actions a {
  padding: 8px 14px; border-radius: 8px;
  font-size: 13px; text-decoration: none; font-weight: 700;
  transition: 0.2s;
}
.btn-edit-sm { background: #dbeafe; color: #1e40af; }
.btn-edit-sm:hover { background: #bfdbfe; }
.btn-del-sm { background: #fee2e2; color: #dc2626; }
.btn-del-sm:hover { background: #fecaca; }
.btn-del-img-sm { background: #fef3c7; color: #d97706; }
.btn-del-img-sm:hover { background: #fde68a; }

.sub-list { list-style: none; padding-left: 15px; border-left: 2px dashed #cbd5e1; margin-top: 10px; }
.sub-list li {
  padding: 10px 12px; font-size: 14px;
  color: #475569; display: flex;
  justify-content: space-between; align-items: center;
  background: white; border-radius: 8px;
  margin-bottom: 8px; flex-wrap: wrap; gap: 8px;
  border-left: 3px solid #94a3b8;
}
.sub-list li .sub-name { font-weight: 600; color: #334155; }
.sub-list li .sub-actions { display: flex; gap: 5px; }
.sub-list li .sub-actions a {
  padding: 5px 10px; border-radius: 6px;
  font-size: 12px; text-decoration: none; font-weight: 600;
}

.add-sub-form {
  display: flex; gap: 8px; margin-top: 12px;
  padding: 12px; background: #eff6ff;
  border-radius: 10px; flex-wrap: wrap;
}
.add-sub-form input {
  flex: 1; min-width: 150px; padding: 9px 13px;
  border: 2px solid #e2e8f0; border-radius: 8px;
  font-size: 13px; font-family: inherit;
}
.add-sub-form button {
  padding: 9px 18px; background: #2563eb;
  color: white; border: none; border-radius: 8px;
  font-size: 13px; font-weight: 700; cursor: pointer;
}
.inactive { opacity: 0.5; }
.badge-inactive {
  background: #fee2e2; color: #dc2626;
  padding: 3px 10px; border-radius: 10px;
  font-size: 11px; font-weight: 700;
}
.product-count {
  background: #dcfce7; color: #16a34a;
  padding: 3px 10px; border-radius: 10px;
  font-size: 11px; font-weight: 700;
}
.product-count.zero { background: #fee2e2; color: #dc2626; }
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
  <h2 class="page-title">🛒 শপ ক্যাটাগরি ম্যানেজমেন্ট</h2>

  <?php if ($msg): ?>
    <div class="msg-box <?= $msgType === 'error' ? 'msg-error' : 'msg-success' ?>">
      <span><?= htmlspecialchars($msg) ?></span>
      <span class="close-x" onclick="this.parentElement.style.display='none'">✕</span>
    </div>
  <?php endif; ?>

  <div class="grid-2">
    <!-- ক্যাটাগরি ফর্ম -->
    <div class="card">
      <h3><?= $editCat ? '✏️ ক্যাটাগরি এডিট' : '➕ নতুন ক্যাটাগরি' ?></h3>
      <form method="POST" enctype="multipart/form-data">
        <?php if ($editCat): ?>
          <input type="hidden" name="cat_id" value="<?= $editCat['id'] ?>">
        <?php endif; ?>
        
        <div class="form-group">
          <label>ক্যাটাগরির নাম *</label>
          <input type="text" name="cat_name" required
                 placeholder="যেমন: পোশাক"
                 value="<?= htmlspecialchars($editCat['name'] ?? '') ?>">
        </div>
        
        <div class="form-row">
          <div class="form-group">
            <label>আইকন (ইমোজি)</label>
            <input type="text" name="cat_icon" placeholder="👕"
                   value="<?= htmlspecialchars($editCat['icon'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label>ক্রম</label>
            <input type="number" name="cat_sort" value="<?= htmlspecialchars($editCat['sort_order'] ?? '0') ?>">
          </div>
        </div>
        
        <?php if ($editCat): ?>
          <!-- বর্তমান ছবি -->
          <div class="form-group">
            <label>বর্তমান ছবি</label>
            <div class="current-image-box">
              <?php if ($editCat['image'] && file_exists('../uploads/shop_categories/' . $editCat['image'])): ?>
                <img src="../uploads/shop_categories/<?= htmlspecialchars($editCat['image']) ?>" alt="">
                <br>
                <a href="?del_cat_img=<?= $editCat['id'] ?>" 
                   class="btn-remove-img"
                   onclick="return confirm('ছবি ডিলিট করবেন?')">
                  🗑 ছবি ডিলিট করুন
                </a>
              <?php else: ?>
                <div class="no-img">📷 কোনো ছবি নেই</div>
              <?php endif; ?>
            </div>
          </div>
        <?php endif; ?>
        
        <div class="form-group">
          <label><?= $editCat ? 'নতুন ছবি (পুরনো বদলাতে)' : 'ক্যাটাগরির ছবি' ?></label>
          <input type="file" name="cat_image" accept="image/*">
        </div>
        
        <?php if ($editCat): ?>
          <div class="checkbox-row">
            <input type="checkbox" name="cat_active" id="cat_active" <?= $editCat['active'] ? 'checked' : '' ?>>
            <label for="cat_active" style="margin:0;">সক্রিয়</label>
          </div>
          <button type="submit" name="edit_cat" class="btn btn-update">💾 আপডেট</button>
          <a href="shop_categories.php" class="btn-cancel">বাতিল</a>
        <?php else: ?>
          <button type="submit" name="add_cat" class="btn btn-primary">➕ যোগ করুন</button>
        <?php endif; ?>
      </form>
    </div>

    <!-- সাব-ক্যাটাগরি ফর্ম -->
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
                $catList = $conn->query("SELECT * FROM shop_categories ORDER BY sort_order");
                while ($c = $catList->fetch_assoc()): 
              ?>
                <option value="<?= $c['id'] ?>"><?= htmlspecialchars(($c['icon'] ?? '') . ' ' . $c['name']) ?></option>
              <?php endwhile; ?>
            </select>
          </div>
        <?php endif; ?>
        
        <div class="form-group">
          <label>সাব-ক্যাটাগরির নাম *</label>
          <input type="text" name="sub_name" required
                 placeholder="যেমন: শার্ট"
                 value="<?= htmlspecialchars($editSub['name'] ?? '') ?>">
        </div>
        
        <div class="form-group">
          <label>ক্রম</label>
          <input type="number" name="sub_sort" value="<?= htmlspecialchars($editSub['sort_order'] ?? '0') ?>">
        </div>
        
        <?php if ($editSub): ?>
          <div class="checkbox-row">
            <input type="checkbox" name="sub_active" id="sub_active" <?= $editSub['active'] ? 'checked' : '' ?>>
            <label for="sub_active" style="margin:0;">সক্রিয়</label>
          </div>
          <button type="submit" name="edit_sub" class="btn btn-update">💾 আপডেট</button>
          <a href="shop_categories.php" class="btn-cancel">বাতিল</a>
        <?php else: ?>
          <button type="submit" name="add_sub" class="btn btn-primary">➕ যোগ করুন</button>
        <?php endif; ?>
      </form>
    </div>
  </div>

  <!-- সব ক্যাটাগরি -->
  <div class="card">
    <h3>📋 সব ক্যাটাগরি ও সাব-ক্যাটাগরি</h3>
    
    <?php if ($categories->num_rows > 0): ?>
      <?php while ($cat = $categories->fetch_assoc()): 
        $cid = $cat['id'];
        $pCount = $conn->query("SELECT COUNT(*) as c FROM products WHERE category_id=$cid")->fetch_assoc()['c'];
        $hasImage = $cat['image'] && file_exists('../uploads/shop_categories/' . $cat['image']);
      ?>
        <div class="cat-item <?= $cat['active'] ? '' : 'inactive' ?>">
          <div class="cat-header">
            <div class="name">
              <?php if ($hasImage): ?>
                <img src="../uploads/shop_categories/<?= htmlspecialchars($cat['image']) ?>" alt="">
              <?php else: ?>
                <span class="emoji"><?= htmlspecialchars($cat['icon'] ?? '🛍️') ?></span>
              <?php endif; ?>
              <?= htmlspecialchars($cat['name']) ?>
              
              <span class="product-count <?= $pCount == 0 ? 'zero' : '' ?>">
                <?= $pCount ?> প্রোডাক্ট
              </span>
              
              <?php if (!$cat['active']): ?>
                <span class="badge-inactive">নিষ্ক্রিয়</span>
              <?php endif; ?>
            </div>
            <div class="actions">
              <?php if ($hasImage): ?>
                <a href="?del_cat_img=<?= $cat['id'] ?>" class="btn-del-img-sm"
                   onclick="return confirm('ক্যাটাগরির ছবি ডিলিট করবেন?')">
                  🖼️ ছবি ডিলিট
                </a>
              <?php endif; ?>
              <a href="?edit_cat=<?= $cat['id'] ?>" class="btn-edit-sm">✏️ এডিট</a>
              <a href="?del_cat=<?= $cat['id'] ?>" class="btn-del-sm"
                 onclick="return confirm('<?= $pCount > 0 ? "এই ক্যাটাগরিতে $pCount টি প্রোডাক্ট আছে! ডিলিট করতে পারবেন না।" : "ক্যাটাগরি এবং এর সব সাব-ক্যাটাগরি ডিলিট হবে। নিশ্চিত?" ?>')">
                 🗑 ডিলিট
              </a>
            </div>
          </div>
          
          <?php 
            $subs = $conn->query("SELECT * FROM shop_subcategories WHERE category_id=$cid ORDER BY sort_order, id");
          ?>
          
          <?php if ($subs->num_rows > 0): ?>
            <ul class="sub-list">
              <?php while ($sub = $subs->fetch_assoc()): 
                $sid = $sub['id'];
                $subPCount = $conn->query("SELECT COUNT(*) as c FROM products WHERE subcategory_id=$sid")->fetch_assoc()['c'];
              ?>
                <li class="<?= $sub['active'] ? '' : 'inactive' ?>">
                  <span class="sub-name">
                    └ <?= htmlspecialchars($sub['name']) ?>
                    <span class="product-count <?= $subPCount == 0 ? 'zero' : '' ?>">
                      <?= $subPCount ?>
                    </span>
                  </span>
                  <span class="sub-actions">
                    <a href="?edit_sub=<?= $sub['id'] ?>" class="btn-edit-sm">✏️ এডিট</a>
                    <a href="?del_sub=<?= $sub['id'] ?>" class="btn-del-sm"
                       onclick="return confirm('<?= $subPCount > 0 ? "এই সাব-ক্যাটাগরিতে $subPCount টি প্রোডাক্ট আছে!" : "সাব-ক্যাটাগরি ডিলিট করবেন?" ?>')">
                       🗑 ডিলিট
                    </a>
                  </span>
                </li>
              <?php endwhile; ?>
            </ul>
          <?php endif; ?>
          
          <form method="POST" class="add-sub-form">
            <input type="hidden" name="sub_cat_id" value="<?= $cat['id'] ?>">
            <input type="text" name="sub_name" required placeholder="নতুন সাব-ক্যাটাগরি...">
            <input type="number" name="sub_sort" value="0" style="max-width:80px;">
            <button type="submit" name="add_sub">➕ যোগ</button>
          </form>
        </div>
      <?php endwhile; ?>
    <?php else: ?>
      <p style="text-align:center;padding:30px;color:#64748b;">
        এখনো কোনো ক্যাটাগরি নেই। উপরে যোগ করুন।
      </p>
    <?php endif; ?>
  </div>
</div>

</body>
</html>