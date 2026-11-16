<?php
include 'includes/auth.php';
include '../db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $message = trim($_POST['message'] ?? '');
    $active = isset($_POST['active']) ? 1 : 0;

    if ($title && $message) {
        $stmt = $conn->prepare("INSERT INTO notices (title, message, active) VALUES (?, ?, ?)");
        $stmt->bind_param("ssi", $title, $message, $active);
        if ($stmt->execute()) {
            header("Location: notices.php");
            exit;
        } else {
            $error = "সমস্যা: " . $conn->error;
        }
    } else {
        $error = "টাইটেল ও মেসেজ দুটোই দিতে হবে";
    }
}
?>
<!DOCTYPE html>
<html lang="bn">
<head>
<meta charset="UTF-8">
<title>নতুন নোটিশ</title>
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
.container { max-width: 600px; margin: 30px auto; padding: 0 20px; }
.card {
  background: white; padding: 30px; border-radius: 15px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.06);
}
.card h2 { color: #1e3a8a; margin-bottom: 25px; }
label { display: block; margin-bottom: 6px; font-weight: 600; color: #334155; font-size: 14px; }
input[type=text], textarea {
  width: 100%; padding: 12px 14px; margin-bottom: 18px;
  border: 2px solid #e2e8f0; border-radius: 10px;
  font-size: 15px; font-family: inherit;
}
input:focus, textarea:focus { outline: none; border-color: #3b82f6; }
textarea { min-height: 120px; resize: vertical; }
.checkbox-wrap { display: flex; align-items: center; margin-bottom: 20px; }
.checkbox-wrap input { margin-right: 8px; width: 18px; height: 18px; }
.checkbox-wrap label { margin: 0; }
.btn-group { display: flex; gap: 10px; }
.btn {
  padding: 12px 24px; border-radius: 10px;
  font-size: 15px; font-weight: 600; cursor: pointer;
  text-decoration: none; border: none;
}
.btn-primary { background: #2563eb; color: white; }
.btn-primary:hover { background: #1e40af; }
.btn-secondary { background: #e2e8f0; color: #334155; }
.btn-secondary:hover { background: #cbd5e1; }
.error { background: #fee2e2; color: #dc2626; padding: 12px; border-radius: 8px; margin-bottom: 15px; }
</style>
</head>
<body>

<div class="topbar">
  <h1>🛠️ অ্যাডমিন প্যানেল</h1>
  <a href="notices.php">← ফিরে যান</a>
</div>

<div class="container">
  <div class="card">
    <h2>📢 নতুন নোটিশ</h2>

    <?php if ($error): ?>
      <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
      <label>টাইটেল *</label>
      <input type="text" name="title" required placeholder="যেমন: ২৪/৭ সেবা চালু"
             value="<?= htmlspecialchars($_POST['title'] ?? '') ?>">

      <label>মেসেজ *</label>
      <textarea name="message" required placeholder="বিস্তারিত লিখুন..."><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>

      <div class="checkbox-wrap">
        <input type="checkbox" name="active" id="active" checked>
        <label for="active">সক্রিয় (সাইটে দেখাবে)</label>
      </div>

      <div class="btn-group">
        <button type="submit" class="btn btn-primary">সেভ করুন</button>
        <a href="notices.php" class="btn btn-secondary">বাতিল</a>
      </div>
    </form>
  </div>
</div>

</body>
</html>