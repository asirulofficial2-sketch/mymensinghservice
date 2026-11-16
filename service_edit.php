<?php
include 'includes/auth.php';
include '../db.php';

$id = intval($_GET['id'] ?? 0);
$error = '';

$stmt = $conn->prepare("SELECT * FROM services WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$service = $stmt->get_result()->fetch_assoc();

if (!$service) {
    header("Location: services.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $icon = trim($_POST['icon'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    $price = floatval($_POST['min_price'] ?? 0);
    $active = isset($_POST['active']) ? 1 : 0;
    $problem_label = trim($_POST['problem_label'] ?? 'সমস্যার বিবরণ');
    $problem_placeholder = trim($_POST['problem_placeholder'] ?? '');
    $time_label = trim($_POST['time_label'] ?? 'কখন আসতে হবে?');
    $image = $service['image'];

    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
        if (in_array($ext, $allowed) && $_FILES['image']['size'] <= 3*1024*1024) {
            $filename = 'service_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
            $dir = __DIR__ . '/../uploads/services/';
            if (!is_dir($dir)) mkdir($dir, 0755, true);
            if (move_uploaded_file($_FILES['image']['tmp_name'], $dir . $filename)) {
                if ($image && file_exists($dir . $image)) unlink($dir . $image);
                $image = $filename;
            }
        }
    }

    if (isset($_POST['remove_image']) && $_POST['remove_image'] == '1') {
        if ($image && file_exists(__DIR__ . '/../uploads/services/' . $image)) {
            unlink(__DIR__ . '/../uploads/services/' . $image);
        }
        $image = null;
    }

    if ($name && !$error) {
        $upd = $conn->prepare("UPDATE services SET name=?, icon=?, image=?, description=?, min_price=?, active=?, problem_label=?, problem_placeholder=?, time_label=? WHERE id=?");
        $upd->bind_param("ssssdisssi", $name, $icon, $image, $desc, $price, $active, $problem_label, $problem_placeholder, $time_label, $id);
        if ($upd->execute()) {
            header("Location: services.php");
            exit;
        } else {
            $error = "সমস্যা: " . $conn->error;
        }
    } elseif (!$name) {
        $error = "নাম দিতে হবে";
    }
}
?>
<!DOCTYPE html>
<html lang="bn">
<head>
<meta charset="UTF-8">
<title>সার্ভিস এডিট</title>
<style>
* { margin:0; padding:0; box-sizing:border-box; font-family:'Segoe UI',sans-serif; }
body { background: #f5f7fa; }
.topbar {
  background: #1e3a8a; color: white; padding: 15px 25px;
  display: flex; justify-content: space-between; align-items: center;
}
.topbar h1 { font-size: 18px; }
.topbar a {
  color: white; text-decoration: none; background: rgba(255,255,255,0.2);
  padding: 8px 16px; border-radius: 20px; font-size: 14px;
}
.container { max-width: 700px; margin: 30px auto; padding: 0 20px 50px; }
.card {
  background: white; padding: 30px; border-radius: 15px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.06);
}
.card h2 { color: #1e3a8a; margin-bottom: 25px; }
label { display: block; margin-bottom: 8px; font-weight: 600; color: #334155; font-size: 14px; }
input[type=text], input[type=number], textarea {
  width: 100%; padding: 12px 14px; margin-bottom: 18px;
  border: 2px solid #e2e8f0; border-radius: 10px;
  font-size: 15px; font-family: inherit;
}
input:focus, textarea:focus { outline: none; border-color: #3b82f6; }
textarea { min-height: 70px; resize: vertical; }
.checkbox-wrap { display: flex; align-items: center; margin-bottom: 20px; }
.checkbox-wrap input { margin-right: 8px; width: 18px; height: 18px; }
.checkbox-wrap label { margin: 0; }

.section-head {
  background: linear-gradient(135deg, #eff6ff, #dbeafe);
  color: #1e40af;
  padding: 12px 16px; border-radius: 10px;
  font-weight: 700; font-size: 15px;
  margin: 25px 0 18px;
  border-left: 4px solid #3b82f6;
}
.hint { color: #64748b; font-size: 12px; margin-top: -14px; margin-bottom: 18px; display: block; }

.current-image {
  background: #f8fafc; padding: 20px;
  border-radius: 12px; margin-bottom: 18px;
  text-align: center; border: 2px dashed #e2e8f0;
}
.current-image img { max-width: 200px; border-radius: 12px; }
.current-image .no-img { color: #94a3b8; padding: 20px; }
.current-image .remove-btn {
  display: inline-block; margin-top: 10px;
  background: #fee2e2; color: #dc2626;
  padding: 8px 16px; border-radius: 10px;
  font-size: 13px; cursor: pointer;
  border: none; font-weight: 700;
}

.emoji-picker {
  display: flex; flex-wrap: wrap; gap: 8px;
  padding: 15px; background: #f8fafc;
  border-radius: 12px; margin-bottom: 18px;
  border: 2px dashed #e2e8f0;
}
.emoji-picker button {
  font-size: 26px; width: 48px; height: 48px;
  border: 2px solid transparent; background: white;
  border-radius: 10px; cursor: pointer;
}
.emoji-picker button:hover { border-color: #3b82f6; transform: scale(1.15); }

.file-upload {
  border: 3px dashed #cbd5e1; padding: 20px;
  border-radius: 15px; text-align: center;
  margin-bottom: 18px; background: #f8fafc;
  cursor: pointer; position: relative;
}
.file-upload:hover { border-color: #3b82f6; background: #eff6ff; }
.file-upload input[type=file] { position: absolute; inset: 0; opacity: 0; cursor: pointer; }
.file-upload .icon { font-size: 32px; display: block; margin-bottom: 6px; }
.file-upload .text { font-size: 13px; color: #475569; font-weight: 600; }
.file-upload .preview { margin-top: 12px; max-width: 150px; border-radius: 10px; display: none; }

.btn-group { display: flex; gap: 10px; margin-top: 25px; }
.btn {
  padding: 13px 26px; border-radius: 10px;
  font-size: 15px; font-weight: 700; cursor: pointer;
  text-decoration: none; border: none;
}
.btn-primary { background: #2563eb; color: white; }
.btn-secondary { background: #e2e8f0; color: #334155; }
.error { background: #fee2e2; color: #dc2626; padding: 14px; border-radius: 10px; margin-bottom: 18px; }
</style>
</head>
<body>

<div class="topbar">
  <h1>🛠️ অ্যাডমিন প্যানেল</h1>
  <a href="services.php">← ফিরে যান</a>
</div>

<div class="container">
  <div class="card">
    <h2>✏️ সার্ভিস এডিট</h2>

    <?php if ($error): ?>
      <div class="error">❌ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">

      <div class="section-head">📋 মূল তথ্য</div>

      <label>সার্ভিসের নাম <span style="color:red">*</span></label>
      <input type="text" name="name" required value="<?= htmlspecialchars($service['name']) ?>">

      <label>বর্তমান ছবি</label>
      <div class="current-image">
        <?php if ($service['image'] && file_exists('../uploads/services/' . $service['image'])): ?>
          <img src="../uploads/services/<?= htmlspecialchars($service['image']) ?>" alt="">
          <br>
          <button type="button" class="remove-btn" onclick="removeImage()">🗑 ছবি মুছে ফেলুন</button>
          <input type="hidden" name="remove_image" id="remove_image" value="0">
        <?php else: ?>
          <div class="no-img">📷 ছবি নেই</div>
        <?php endif; ?>
      </div>

      <label>নতুন ছবি</label>
      <div class="file-upload">
        <input type="file" name="image" accept="image/*" onchange="previewImage(this)">
        <span class="icon">🖼️</span>
        <span class="text">এখানে ক্লিক করে ছবি বাছুন</span>
        <img id="preview" class="preview" alt="">
      </div>

      <label>আইকন (ইমোজি)</label>
      <input type="text" name="icon" id="icon"
             style="font-size:24px;text-align:center;"
             value="<?= htmlspecialchars($service['icon'] ?? '') ?>">

      <div class="emoji-picker">
        <?php foreach (['⚡','🚿','❄️','📺','💊','🚑','📚','🚗','🏥','🧹','🔨','🎨','🔧','🛡️','📦','🌱','🍳','🧽','🎪','🎭','💒','🎈'] as $e): ?>
          <button type="button" onclick="pickEmoji('<?= $e ?>')"><?= $e ?></button>
        <?php endforeach; ?>
      </div>

      <label>বিবরণ</label>
      <textarea name="description"><?= htmlspecialchars($service['description'] ?? '') ?></textarea>

      <label>সর্বনিম্ন মূল্য (৳)</label>
      <input type="number" name="min_price" step="0.01" min="0"
             value="<?= htmlspecialchars($service['min_price']) ?>">

      <!-- ============ বুকিং ফর্মের লেখা ============ -->
      <div class="section-head">📝 বুকিং ফর্মে যা দেখাবে</div>

      <label>সমস্যার লেবেল</label>
      <input type="text" name="problem_label" 
             placeholder="যেমন: সমস্যার বিবরণ"
             value="<?= htmlspecialchars($service['problem_label'] ?? 'সমস্যার বিবরণ') ?>">
      <small class="hint">💡 কাস্টমার বুকিং ফর্মে এই লেখাটা দেখবে</small>

      <label>সমস্যার উদাহরণ (হালকা লেখা)</label>
      <input type="text" name="problem_placeholder" 
             placeholder="যেমন: ফ্যান ঘুরছে না"
             value="<?= htmlspecialchars($service['problem_placeholder'] ?? '') ?>">
      <small class="hint">💡 কাস্টমার ফাঁকা ঘরে এটা দেখবে</small>

      <label>সময়ের লেবেল</label>
      <input type="text" name="time_label" 
             placeholder="যেমন: কখন আসতে হবে?"
             value="<?= htmlspecialchars($service['time_label'] ?? 'কখন আসতে হবে?') ?>">
      <small class="hint">💡 সময় নেওয়ার ঘরের উপরে এই লেখা দেখাবে</small>

      <div class="checkbox-wrap" style="margin-top:20px;">
        <input type="checkbox" name="active" id="active" <?= $service['active'] ? 'checked' : '' ?>>
        <label for="active">সক্রিয়</label>
      </div>

      <div class="btn-group">
        <button type="submit" class="btn btn-primary">✅ আপডেট করুন</button>
        <a href="services.php" class="btn btn-secondary">বাতিল</a>
      </div>
    </form>
  </div>
</div>

<script>
function pickEmoji(e) { document.getElementById('icon').value = e; }
function previewImage(input) {
    const p = document.getElementById('preview');
    if (input.files && input.files[0]) {
        const r = new FileReader();
        r.onload = e => { p.src = e.target.result; p.style.display = 'block'; };
        r.readAsDataURL(input.files[0]);
    }
}
function removeImage() {
    if (confirm('ছবি মুছে ফেলবেন?')) {
        document.getElementById('remove_image').value = '1';
        const img = document.querySelector('.current-image img');
        if (img) img.style.display = 'none';
    }
}
</script>

</body>
</html>