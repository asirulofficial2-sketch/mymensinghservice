<?php
session_start();

if (isset($_SESSION['admin_id'])) {
    header("Location: dashboard.php");
    exit;
}

include '../db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = trim($_POST['username'] ?? '');
    $pass = $_POST['password'] ?? '';

    if ($user && $pass) {
        $stmt = $conn->prepare("SELECT * FROM admins WHERE username = ?");
        $stmt->bind_param("s", $user);
        $stmt->execute();
        $result = $stmt->get_result();
        $admin = $result->fetch_assoc();

        if ($admin && password_verify($pass, $admin['password'])) {
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_name'] = $admin['username'];
            header("Location: dashboard.php");
            exit;
        } else {
            $error = 'ভুল ইউজারনেম বা পাসওয়ার্ড';
        }
    } else {
        $error = 'সব ফিল্ড পূরণ করুন';
    }
}
?>
<!DOCTYPE html>
<html lang="bn">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>অ্যাডমিন লগইন</title>
<style>
* { margin:0; padding:0; box-sizing:border-box; font-family:'Segoe UI',sans-serif; }
body {
  min-height:100vh; display:flex; align-items:center; justify-content:center;
  background: linear-gradient(135deg, #1e3a8a, #3b82f6);
  padding: 20px;
}
.login-box {
  background: white; padding: 40px 30px; border-radius: 20px;
  box-shadow: 0 20px 60px rgba(0,0,0,0.3);
  width: 100%; max-width: 400px;
}
.login-box h1 {
  text-align: center; color: #1e3a8a; margin-bottom: 8px;
  font-size: 24px;
}
.login-box p.sub {
  text-align: center; color: #64748b; margin-bottom: 25px;
  font-size: 14px;
}
.login-box input {
  width: 100%; padding: 14px 16px; margin-bottom: 15px;
  border: 2px solid #e2e8f0; border-radius: 10px;
  font-size: 15px; transition: 0.3s;
}
.login-box input:focus {
  outline: none; border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59,130,246,0.15);
}
.login-box button {
  width: 100%; padding: 14px; background: #2563eb;
  color: white; border: none; border-radius: 10px;
  font-size: 16px; font-weight: 600; cursor: pointer;
  transition: 0.3s;
}
.login-box button:hover { background: #1e40af; }
.error {
  background: #fee2e2; color: #dc2626;
  padding: 12px; border-radius: 8px;
  margin-bottom: 15px; font-size: 14px; text-align: center;
}
</style>
</head>
<body>
<div class="login-box">
  <h1>🔐 অ্যাডমিন লগইন</h1>
  <p class="sub">ময়মনসিংহ হোম সার্ভিস</p>
  
  <?php if ($error): ?>
    <div class="error"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>
  
  <form method="POST">
    <input type="text" name="username" placeholder="ইউজারনেম" required autofocus>
    <input type="password" name="password" placeholder="পাসওয়ার্ড" required>
    <button type="submit">লগইন করুন</button>
  </form>
</div>
</body>
</html>