<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include 'includes/auth.php';
include '../db.php';

$id = intval($_GET['id'] ?? 0);
$error = '';
$success = '';

$stmt = $conn->prepare("SELECT * FROM products WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();

if (!$product) {
    header("Location: shop_products.php");
    exit;
}

function uploadProductImage($fileKey) {
    if (!isset($_FILES[$fileKey]) || $_FILES[$fileKey]['error'] !== UPLOAD_ERR_OK) return null;
    $ext = strtolower(pathinfo($_FILES[$fileKey]['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg','jpeg','png','gif','webp'])) return null;
    if ($_FILES[$fileKey]['size'] > 3*1024*1024) return null;
    $filename = 'product_' . time() . '_' . rand(1000,9999) . '.' . $ext;
    $dir = __DIR__ . '/../uploads/shop_products/';
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    if (move_uploaded_file($_FILES[$fileKey]['tmp_name'], $dir . $filename)) return $filename;
    return null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_code = trim($_POST['product_code'] ?? $product['product_code']);
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

    if (empty($product_code)) {
        $lastId = $conn->query("SELECT MAX(id) as mid FROM products")->fetch_assoc()['mid'];
        $product_code = 'MHS-' . str_pad(($lastId + 1) + 1000, 4, '0', STR_PAD_LEFT);
    }

    $image = $product['image'];
    $image2 = $product['image2'];
    $image3 = $product['image3'];

    // নতুন ছবি আপলোড হলে
    $newImg = uploadProductImage('image');
    if ($newImg) {
        if ($image && file_exists(__DIR__ . '/../uploads/shop_products/' . $image)) unlink(__DIR__ . '/../uploads/shop_products/' . $image);
        $image = $newImg;
    }
    $newImg2 = uploadProductImage('image2');
    if ($newImg2) {
        if ($image2 && file_exists(__DIR__ . '/../uploads/shop_products/' . $image2)) unlink(__DIR__ . '/../uploads/shop_products/' . $image2);
        $image2 = $newImg2;
    }
    $newImg3 = uploadProductImage('image3');
    if ($newImg3) {
        if ($image3 && file_exists(__DIR__ . '/../uploads/shop_products/' . $image3)) unlink(__DIR__ . '/../uploads/shop_products/' . $image3);
        $image3 = $newImg3;
    }

    // ছবি মুছে ফেলার চেক
    if (isset($_POST['remove_image']) && $_POST['remove_image'] == '1') {
        if ($image && file_exists(__DIR__ . '/../uploads/shop_products/' . $image)) unlink(__DIR__ . '/../uploads/shop_products/' . $image);
        $image = null;
    }
    if (isset($_POST['remove_image2']) && $_POST['remove_image2'] == '1') {
        if ($image2 && file_exists(__DIR__ . '/../uploads/shop_products/' . $image2)) unlink(__DIR__ . '/../uploads/shop_products/' . $image2);
        $image2 = null;
    }
    if (isset($_POST['remove_image3']) && $_POST['remove_image3'] == '1') {
        if ($image3 && file_exists(__DIR__ . '/../uploads/shop_products/' . $image3)) unlink(__DIR__ . '/../uploads/shop_products/' . $image3);
        $image3 = null;
    }

    if ($name && $price > 0) {
        $upd = $conn->prepare("UPDATE products SET product_code=?, name=?, image=?, image2=?, image3=?, price=?, discount_price=?, category_id=?, subcategory_id=?, size=?, color=?, brand=?, stock=?, description=?, active=? WHERE id=?");
        $upd->bind_param("sssssddiisssisii", 
            $product_code, $name, $image, $image2, $image3, $price, $discount, 
            $cat_id, $subcat_id, $size, $color, $brand, $stock, $description, $active, $id
        );

        if ($upd->execute()) {
            $success = "✅ আপডেট সফল হয়েছে!";
            // আপডেট হওয়া ডেটা আবার লোড করি
            $stmt = $conn->prepare("SELECT * FROM products WHERE id=?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $product = $stmt->get_result()->fetch_assoc();
        } else {
            $error = "সমস্যা: " . $conn->error;
        }
    } else {
        $error = "নাম ও মূল্য দিতে হবে";
    }
}

$categories = $conn->query("SELECT * FROM shop_categories WHERE active=1 ORDER BY sort_order");
$subs = [];
if ($product['category_id']) {
    $cid = intval($product['category_id']);
    $r = $conn->query("SELECT id, name FROM shop_subcategories WHERE category_id=$cid ORDER BY sort_order");
    while ($x = $r->fetch_assoc()) $subs[] = $x;
}
?>
<!DOCTYPE html>
<html lang="bn">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>প্রোডাক্ট এডিট</title>
<style>
* { margin:0; padding:0; box-sizing:border-box; font-family:'Segoe UI',sans-serif; }
body { background: #f5f7fa; }
.topbar { background: #1e3a8a; color: white; padding: 15px 25px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; }
.topbar h1 { font-size: 18px; }
.topbar a { color: white; text-decoration: none; background: rgba(255,255,255,0.2); padding: 8px 16px; border-radius: 20px; font-size: 14px; margin-left: 8px; }
.container { max-width: 800px; margin: 30px auto; padding: 0 20px 50px; }
.card { background: white; padding: 30px; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.06); }
.card h2 { color: #1e3a8a; margin-bottom: 20px; font-size: 20px; }
.section-head { background: #eff6ff; color: #1e40af; padding: 10px 14px; border-radius: 8px; font-weight: 700; font-size: 13px; margin: 20px 0 12px; border-left: 3px solid #3b82f6; }
label { display: block; margin-bottom: 6px; font-weight: 600; color: #334155; font-size: 13px; }
input[type=text], input[type=number], input[type=file], select, textarea { width: 100%; padding: 10px 12px; margin-bottom: 15px; border: 2px solid #e2e8f0; border-radius: 10px; font-size: 14px; font-family: inherit; background: white; }
input:focus, select:focus, textarea:focus { outline: none; border-color: #3b82f6; }
textarea { min-height: 70px; resize: vertical; }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.form-row-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px; }
.checkbox-row { display: flex; align-items: center; gap: 8px; margin-bottom: 15px; font-size: 14px; }
.checkbox-row input { width: 18px; height: 18px; margin: 0; }
.img-box { border: 2px dashed #cbd5e1; padding: 15px; border-radius: 12px; margin-bottom: 12px; text-align: center; background: #f8fafc; }
.img-box img { max-width: 150px; border-radius: 10px; }
.img-box .no-img { color: #94a3b8; font-size: 13px; padding: 20px; }
.img-box .remove-btn { display: inline-block; margin-top: 8px; background: #fee2e2; color: #dc2626; padding: 6px 12px; border-radius: 8px; font-size: 12px; cursor: pointer; border: none; font-weight: 700; font-family: inherit; }
.btn-group { display: flex; gap: 10px; margin-top: 25px; }
.btn { padding: 13px 26px; border-radius: 10px; font-size: 15px; font-weight: 700; cursor: pointer; text-decoration: none; border: none; font-family: inherit; }
.btn-primary { background: #2563eb; color: white; flex: 1; }
.btn-secondary { background: #e2e8f0; color: #334155; }
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
    <a href="shop_products.php">← প্রোডাক্ট</a>
    <a href="logout.php">লগআউট</a>
  </div>
</div>

<div class="container">
  <div class="card">
    <h2>✏️ প্রোডাক্ট এডিট — <?= htmlspecialchars($product['name']) ?></h2>

    <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-error">❌ <?= htmlspecialchars($error) ?></div><?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
      
      <div class="section-head">📋 মূল তথ্য</div>
      <label>প্রোডাক্ট কোড</label>
      <input type="text" name="product_code" value="<?= htmlspecialchars($product['product_code'] ?? '') ?>">
      <label>প্রোডাক্টের নাম *</label>
      <input type="text" name="name" required value="<?= htmlspecialchars($product['name']) ?>">
      <div class="form-row">
        <div><label>মূল্য (৳) *</label><input type="number" name="price" step="0.01" required value="<?= htmlspecialchars($product['price']) ?>"></div>
        <div><label>ডিসকাউন্ট মূল্য (৳)</label><input type="number" name="discount_price" step="0.01" value="<?= htmlspecialchars($product['discount_price'] ?? '') ?>"></div>
      </div>

      <div class="section-head">📂 ক্যাটাগরি</div>
      <div class="form-row">
        <div>
          <label>প্রধান ক্যাটাগরি</label>
          <select name="category_id" id="category_id">
            <option value="">-- ক্যাটাগরি --</option>
            <?php $categories->data_seek(0); while ($c = $categories->fetch_assoc()): ?>
              <option value="<?= $c['id'] ?>" <?= $product['category_id']==$c['id']?'selected':'' ?>>
                <?= htmlspecialchars($c['name']) ?>
              </option>
            <?php endwhile; ?>
          </select>
        </div>
        <div>
          <label>সাব-ক্যাটাগরি</label>
          <select name="subcategory_id" id="subcategory_id">
            <option value="">-- সাব-ক্যাটাগরি --</option>
            <?php foreach ($subs as $s): ?>
              <option value="<?= $s['id'] ?>" <?= $product['subcategory_id']==$s['id']?'selected':'' ?>>
                <?= htmlspecialchars($s['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="section-head">🏷️ বিস্তারিত</div>
      <div class="form-row-3">
        <div><label>সাইজ</label><input type="text" name="size" value="<?= htmlspecialchars($product['size'] ?? '') ?>"></div>
        <div><label>রঙ</label><input type="text" name="color" value="<?= htmlspecialchars($product['color'] ?? '') ?>"></div>
        <div><label>ব্র্যান্ড</label><input type="text" name="brand" value="<?= htmlspecialchars($product['brand'] ?? '') ?>"></div>
      </div>
      <label>স্টক সংখ্যা</label>
      <input type="number" name="stock" min="0" value="<?= htmlspecialchars($product['stock']) ?>">
      <label>বিবরণ</label>
      <textarea name="description"><?= htmlspecialchars($product['description'] ?? '') ?></textarea>

      <div class="section-head">📷 ছবি</div>
      
      <label>মূল ছবি</label>
      <div class="img-box">
        <?php if ($product['image'] && file_exists('../uploads/shop_products/' . $product['image'])): ?>
          <img src="../uploads/shop_products/<?= htmlspecialchars($product['image']) ?>" alt="">
          <br>
          <button type="button" class="remove-btn" onclick="rmImg('remove_image', this)">🗑 মুছুন</button>
          <input type="hidden" name="remove_image" id="remove_image" value="0">
        <?php else: ?>
          <div class="no-img">📷 ছবি নেই</div>
        <?php endif; ?>
      </div>
      <input type="file" name="image" accept="image/*">

      <label>২য় ছবি</label>
      <div class="img-box">
        <?php if ($product['image2'] && file_exists('../uploads/shop_products/' . $product['image2'])): ?>
          <img src="../uploads/shop_products/<?= htmlspecialchars($product['image2']) ?>" alt="">
          <br>
          <button type="button" class="remove-btn" onclick="rmImg('remove_image2', this)">🗑 মুছুন</button>
          <input type="hidden" name="remove_image2" id="remove_image2" value="0">
        <?php else: ?>
          <div class="no-img">📷 ছবি নেই</div>
        <?php endif; ?>
      </div>
      <input type="file" name="image2" accept="image/*">

      <label>৩য় ছবি</label>
      <div class="img-box">
        <?php if ($product['image3'] && file_exists('../uploads/shop_products/' . $product['image3'])): ?>
          <img src="../uploads/shop_products/<?= htmlspecialchars($product['image3']) ?>" alt="">
          <br>
          <button type="button" class="remove-btn" onclick="rmImg('remove_image3', this)">🗑 মুছুন</button>
          <input type="hidden" name="remove_image3" id="remove_image3" value="0">
        <?php else: ?>
          <div class="no-img">📷 ছবি নেই</div>
        <?php endif; ?>
      </div>
      <input type="file" name="image3" accept="image/*">

      <div class="checkbox-row" style="margin-top:15px;">
        <input type="checkbox" name="active" id="active" <?= $product['active'] ? 'checked' : '' ?>>
        <label for="active" style="margin:0;">সক্রিয় (সাইটে দেখাবে)</label>
      </div>

      <div class="btn-group">
        <button type="submit" class="btn btn-primary">✅ আপডেট করুন</button>
        <a href="shop_products.php" class="btn btn-secondary">বাতিল</a>
      </div>
    </form>
  </div>
</div>

<script>
document.getElementById('category_id').addEventListener('change', function() {
    const catId = this.value;
    const subSelect = document.getElementById('subcategory_id');
    if (!catId) { subSelect.innerHTML = '<option value="">-- সাব-ক্যাটাগরি --</option>'; return; }
    subSelect.innerHTML = '<option value="">-- লোড হচ্ছে... --</option>';
    fetch('get_shop_subcategories.php?category_id=' + catId)
        .then(r => r.json())
        .then(data => {
            let html = '<option value="">-- সাব-ক্যাটাগরি --</option>';
            data.forEach(i => html += `<option value="${i.id}">${i.name}</option>`);
            subSelect.innerHTML = html;
        });
});

function rmImg(fieldId, btn) {
    if (confirm('ছবি মুছে ফেলবেন?')) {
        document.getElementById(fieldId).value = '1';
        const box = btn.parentElement;
        const img = box.querySelector('img');
        if (img) img.style.display = 'none';
        btn.style.display = 'none';
    }
}
</script>
</body>
</html>