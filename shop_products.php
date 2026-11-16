<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include 'includes/auth.php';
include '../db.php';

$error = '';
$success = '';

// ============ প্রোডাক্ট ডিলিট ============
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $row = $conn->query("SELECT image, image2, image3 FROM products WHERE id=$id")->fetch_assoc();
    if ($row) {
        foreach (['image', 'image2', 'image3'] as $img) {
            if ($row[$img] && file_exists('../uploads/shop_products/' . $row[$img])) {
                unlink('../uploads/shop_products/' . $row[$img]);
            }
        }
    }
    $conn->query("DELETE FROM products WHERE id=$id");
    header("Location: shop_products.php?deleted=1");
    exit;
}

// ============ প্রোডাক্ট টগল ============
if (isset($_GET['toggle'])) {
    $id = intval($_GET['toggle']);
    $conn->query("UPDATE products SET active = NOT active WHERE id=$id");
    header("Location: shop_products.php");
    exit;
}

// ============ প্রোডাক্ট যোগ ============
function uploadProductImage($fileKey) {
    if (!isset($_FILES[$fileKey]) || $_FILES[$fileKey]['error'] !== UPLOAD_ERR_OK) return null;
    $ext = strtolower(pathinfo($_FILES[$fileKey]['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    if (!in_array($ext, $allowed)) return null;
    $filename = 'product_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
    $dir = __DIR__ . '/../uploads/shop_products/';
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    if (move_uploaded_file($_FILES[$fileKey]['tmp_name'], $dir . $filename)) return $filename;
    return null;
}

if (isset($_POST['add_product'])) {
    $product_code = trim($_POST['product_code'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $discount = !empty($_POST['discount_price']) ? floatval($_POST['discount_price']) : null;
    $cat_id = intval($_POST['category_id'] ?? 0);
    $subcat_id = intval($_POST['subcategory_id'] ?? 0);
    $size = trim($_POST['size'] ?? '');
    $color = trim($_POST['color'] ?? '');
    $brand = trim($_POST['brand'] ?? '');
    $stock = intval($_POST['stock'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $active = isset($_POST['active']) ? 1 : 0;

    if ($name && $price > 0) {
        if (empty($product_code)) {
            $lastId = $conn->query("SELECT MAX(id) as mid FROM products")->fetch_assoc()['mid'];
            $product_code = 'MHS-' . str_pad(($lastId + 1) + 1000, 4, '0', STR_PAD_LEFT);
        }

        $image = uploadProductImage('image');
        $image2 = uploadProductImage('image2');
        $image3 = uploadProductImage('image3');

        $stmt = $conn->prepare("INSERT INTO products (product_code, name, image, image2, image3, price, discount_price, category_id, subcategory_id, size, color, brand, stock, description, active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssddiisssisi", 
            $product_code, $name, $image, $image2, $image3, $price, $discount, 
            $cat_id, $subcat_id, $size, $color, $brand, $stock, $description, $active
        );

        if ($stmt->execute()) {
            $success = "✅ প্রোডাক্ট যোগ হয়েছে (কোড: $product_code)";
        } else {
            $error = "সমস্যা: " . $conn->error;
        }
    } else {
        $error = "নাম ও মূল্য দিতে হবে";
    }
}

$products = $conn->query("SELECT p.*, c.name as cat_name, c.icon as cat_icon, sc.name as subcat_name 
                          FROM products p 
                          LEFT JOIN shop_categories c ON p.category_id = c.id 
                          LEFT JOIN shop_subcategories sc ON p.subcategory_id = sc.id 
                          ORDER BY p.id DESC");
$categories = $conn->query("SELECT * FROM shop_categories WHERE active=1 ORDER BY sort_order");

if (isset($_GET['deleted'])) $success = "✅ প্রোডাক্টটি ডিলিট হয়েছে!";
?>
<!DOCTYPE html>
<html lang="bn">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>প্রোডাক্ট ম্যানেজমেন্ট</title>
<style>
* { margin:0; padding:0; box-sizing:border-box; font-family:'Segoe UI',sans-serif; }
body { background: #f5f7fa; }
.topbar { background: #1e3a8a; color: white; padding: 15px 25px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; }
.topbar h1 { font-size: 18px; }
.topbar a { color: white; text-decoration: none; background: rgba(255,255,255,0.2); padding: 8px 16px; border-radius: 20px; font-size: 14px; margin-left: 8px; }
.container { max-width: 1200px; margin: 30px auto; padding: 0 20px 50px; }
.page-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; flex-wrap: wrap; gap: 10px; }
.page-head h2 { color: #1e3a8a; font-size: 22px; }
.btn-add { background: #16a34a; color: white; text-decoration: none; padding: 12px 22px; border-radius: 10px; font-weight: 700; font-size: 14px; border: none; cursor: pointer; }
.modal-bg { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: flex-start; justify-content: center; padding: 30px 20px; overflow-y: auto; }
.modal-bg.active { display: flex; }
.modal { background: white; padding: 30px; border-radius: 20px; max-width: 750px; width: 100%; box-shadow: 0 20px 60px rgba(0,0,0,0.3); }
.modal h3 { color: #1e3a8a; margin-bottom: 20px; font-size: 20px; padding-bottom: 12px; border-bottom: 2px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; }
.modal .close-btn { background: #fee2e2; color: #dc2626; border: none; width: 36px; height: 36px; border-radius: 50%; font-size: 20px; cursor: pointer; font-weight: 700; }
.section-head { background: #eff6ff; color: #1e40af; padding: 10px 14px; border-radius: 8px; font-weight: 700; font-size: 13px; margin: 20px 0 12px; border-left: 3px solid #3b82f6; }
.section-head:first-of-type { margin-top: 0; }
label { display: block; margin-bottom: 6px; font-weight: 600; color: #334155; font-size: 13px; }
input[type=text], input[type=number], input[type=file], select, textarea { width: 100%; padding: 10px 12px; margin-bottom: 15px; border: 2px solid #e2e8f0; border-radius: 10px; font-size: 14px; font-family: inherit; background: white; }
input:focus, select:focus, textarea:focus { outline: none; border-color: #3b82f6; }
textarea { min-height: 70px; resize: vertical; }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.form-row-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px; }
@media (max-width: 600px) { .form-row, .form-row-3 { grid-template-columns: 1fr; } }
.checkbox-row { display: flex; align-items: center; gap: 8px; margin-bottom: 15px; font-size: 14px; }
.checkbox-row input { width: 18px; height: 18px; margin: 0; }
.btn { padding: 12px 25px; border-radius: 10px; font-size: 15px; font-weight: 700; cursor: pointer; border: none; font-family: inherit; }
.btn-primary { background: #16a34a; color: white; }
.btn-cancel { background: #e2e8f0; color: #334155; }
.products-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px,1fr)); gap: 20px; }
.product-card { background: white; border-radius: 15px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.06); transition: 0.3s; }
.product-card:hover { transform: translateY(-3px); box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
.product-img { height: 180px; background: linear-gradient(135deg, #dbeafe, #eff6ff); display: flex; align-items: center; justify-content: center; overflow: hidden; position: relative; }
.product-img img { width: 100%; height: 100%; object-fit: cover; }
.product-img .no-img { font-size: 60px; color: #94a3b8; }
.product-info { padding: 15px; }
.product-info h3 { color: #1e3a8a; font-size: 16px; margin-bottom: 5px; }
.product-code-badge { background: #1e293b; color: #4ade80; padding: 3px 10px; border-radius: 8px; font-family: 'Courier New', monospace; font-size: 12px; font-weight: 700; display: inline-block; margin-bottom: 8px; }
.product-info .cat { font-size: 11px; color: #64748b; margin-bottom: 8px; background: #f1f5f9; padding: 3px 8px; border-radius: 10px; display: inline-block; }
.product-info .price { font-weight: 700; color: #16a34a; font-size: 15px; }
.product-info .price del { color: #94a3b8; font-weight: 400; font-size: 12px; margin-right: 5px; }
.product-info .info-line { font-size: 12px; color: #64748b; margin-top: 5px; }
.product-actions { display: flex; gap: 6px; padding: 12px 15px; border-top: 1px solid #f1f5f9; flex-wrap: wrap; }
.product-actions a { flex: 1; text-align: center; min-width: 60px; padding: 8px; border-radius: 8px; font-size: 12px; text-decoration: none; font-weight: 600; }
.btn-edit { background: #dbeafe; color: #1e40af; }
.btn-toggle-on { background: #dcfce7; color: #16a34a; }
.btn-toggle-off { background: #fef3c7; color: #d97706; }
.btn-del { background: #fee2e2; color: #dc2626; }
.status-badge { position: absolute; top: 10px; right: 10px; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; }
.status-on { background: #dcfce7; color: #16a34a; }
.status-off { background: #fee2e2; color: #dc2626; }
.empty { background: white; padding: 80px 20px; text-align: center; border-radius: 15px; color: #64748b; }
.empty h3 { color: #94a3b8; margin-bottom: 15px; font-size: 20px; }
.alert { padding: 14px 20px; border-radius: 10px; margin-bottom: 20px; font-weight: 600; }
.alert-success { background: #dcfce7; color: #166534; border: 2px solid #16a34a; }
.alert-error { background: #fee2e2; color: #dc2626; border: 2px solid #dc2626; }
</style>
</head>
<body>

<div class="topbar">
  <h1>🛠️ অ্যাডমিন প্যানেল</h1>
  <div>
    <a href="dashboard.php">ড্যাশবোর্ড</a>
    <a href="shop_categories.php">ক্যাটাগরি</a>
    <a href="logout.php">লগআউট</a>
  </div>
</div>

<div class="container">
  <div class="page-head">
    <h2>📦 প্রোডাক্ট ম্যানেজমেন্ট</h2>
    <button class="btn-add" onclick="openModal()">➕ নতুন প্রোডাক্ট</button>
  </div>

  <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
  <?php if ($error): ?><div class="alert alert-error">❌ <?= htmlspecialchars($error) ?></div><?php endif; ?>

  <?php if ($products && $products->num_rows > 0): ?>
    <div class="products-grid">
      <?php while ($p = $products->fetch_assoc()): ?>
        <div class="product-card">
          <div class="product-img">
            <?php if ($p['image'] && file_exists('../uploads/shop_products/' . $p['image'])): ?>
              <img src="../uploads/shop_products/<?= htmlspecialchars($p['image']) ?>" alt="">
            <?php else: ?>
              <span class="no-img">📦</span>
            <?php endif; ?>
            <span class="status-badge <?= $p['active'] ? 'status-on' : 'status-off' ?>">
              <?= $p['active'] ? '✅ সক্রিয়' : '❌ নিষ্ক্রিয়' ?>
            </span>
          </div>
          <div class="product-info">
            <h3><?= htmlspecialchars($p['name']) ?></h3>
            <?php if (!empty($p['product_code'])): ?>
              <div class="product-code-badge">🆔 <?= htmlspecialchars($p['product_code']) ?></div>
            <?php endif; ?>
            <?php if ($p['cat_name']): ?>
              <div class="cat">
                <?= htmlspecialchars(($p['cat_icon'] ?? '') . ' ' . $p['cat_name']) ?>
                <?php if ($p['subcat_name']): ?> › <?= htmlspecialchars($p['subcat_name']) ?><?php endif; ?>
              </div>
            <?php endif; ?>
            <div class="price">
              <?php if ($p['discount_price']): ?>
                <del>৳<?= number_format($p['price']) ?></del>
                ৳<?= number_format($p['discount_price']) ?>
              <?php else: ?>
                ৳<?= number_format($p['price']) ?>
              <?php endif; ?>
            </div>
            <?php if ($p['stock'] !== null): ?>
              <div class="info-line">📦 স্টক: <?= $p['stock'] ?></div>
            <?php endif; ?>
          </div>
          <div class="product-actions">
            <a href="?toggle=<?= $p['id'] ?>" class="btn-<?= $p['active'] ? 'toggle-on' : 'toggle-off' ?>">
              <?= $p['active'] ? '👁️' : '🙈' ?>
            </a>
            <!-- এডিট বাটন - shop_product_edit.php তে যাবে -->
            <a href="shop_product_edit.php?id=<?= $p['id'] ?>" class="btn-edit">✏️ এডিট</a>
            <a href="?delete=<?= $p['id'] ?>" class="btn-del" onclick="return confirm('ডিলিট করবেন?')">🗑</a>
          </div>
        </div>
      <?php endwhile; ?>
    </div>
  <?php else: ?>
    <div class="empty">
      <h3>📭 এখনো কোনো প্রোডাক্ট নেই</h3>
      <p style="margin-bottom:20px;">উপরে "➕ নতুন প্রোডাক্ট" ক্লিক করুন।</p>
      <button class="btn-add" onclick="openModal()">➕ প্রথম প্রোডাক্ট যোগ করুন</button>
    </div>
  <?php endif; ?>
</div>

<!-- Add Modal -->
<div class="modal-bg" id="addModal">
  <div class="modal">
    <h3>➕ নতুন প্রোডাক্ট <button class="close-btn" onclick="closeModal()">✕</button></h3>
    <form method="POST" enctype="multipart/form-data">
      <div class="section-head">📋 মূল তথ্য</div>
      <label>প্রোডাক্ট কোড (ঐচ্ছিক)</label>
      <input type="text" name="product_code" placeholder="MHS-1001">
      <label>প্রোডাক্টের নাম *</label>
      <input type="text" name="name" required>
      <div class="form-row">
        <div><label>মূল্য (৳) *</label><input type="number" name="price" step="0.01" required></div>
        <div><label>ডিসকাউন্ট মূল্য (৳)</label><input type="number" name="discount_price" step="0.01"></div>
      </div>
      <div class="section-head">📂 ক্যাটাগরি</div>
      <div class="form-row">
        <div>
          <label>প্রধান ক্যাটাগরি</label>
          <select name="category_id" id="category_id">
            <option value="">-- ক্যাটাগরি --</option>
            <?php $categories->data_seek(0); while ($c = $categories->fetch_assoc()): ?>
              <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
            <?php endwhile; ?>
          </select>
        </div>
        <div>
          <label>সাব-ক্যাটাগরি</label>
          <select name="subcategory_id" id="subcategory_id">
            <option value="">-- আগে ক্যাটাগরি বাছুন --</option>
          </select>
        </div>
      </div>
      <div class="section-head">🏷️ বিস্তারিত</div>
      <div class="form-row-3">
        <div><label>সাইজ</label><input type="text" name="size"></div>
        <div><label>রঙ</label><input type="text" name="color"></div>
        <div><label>ব্র্যান্ড</label><input type="text" name="brand"></div>
      </div>
      <label>স্টক</label><input type="number" name="stock" value="0">
      <label>বিবরণ</label><textarea name="description"></textarea>
      <div class="section-head">📷 ছবি</div>
      <label>মূল ছবি</label><input type="file" name="image" accept="image/*">
      <label>২য় ছবি</label><input type="file" name="image2" accept="image/*">
      <label>৩য় ছবি</label><input type="file" name="image3" accept="image/*">
      <div class="checkbox-row">
        <input type="checkbox" name="active" id="active" checked>
        <label for="active" style="margin:0;">সক্রিয়</label>
      </div>
      <div style="display:flex;gap:10px;margin-top:20px;">
        <button type="submit" name="add_product" class="btn btn-primary" style="flex:1;">✅ সেভ করুন</button>
        <button type="button" class="btn btn-cancel" onclick="closeModal()">বাতিল</button>
      </div>
    </form>
  </div>
</div>

<script>
function openModal() { document.getElementById('addModal').classList.add('active'); }
function closeModal() { document.getElementById('addModal').classList.remove('active'); }
document.getElementById('category_id').addEventListener('change', function() {
    const catId = this.value;
    const subSelect = document.getElementById('subcategory_id');
    if (!catId) { subSelect.innerHTML = '<option value="">-- আগে ক্যাটাগরি বাছুন --</option>'; return; }
    subSelect.innerHTML = '<option value="">-- লোড হচ্ছে... --</option>';
    fetch('get_shop_subcategories.php?category_id=' + catId)
        .then(r => r.json())
        .then(data => {
            let html = '<option value="">-- সাব-ক্যাটাগরি --</option>';
            data.forEach(item => { html += `<option value="${item.id}">${item.name}</option>`; });
            subSelect.innerHTML = html;
        })
        .catch(() => { subSelect.innerHTML = '<option value="">-- লোড ব্যর্থ --</option>'; });
});
</script>
</body>
</html>