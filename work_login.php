<?php
session_start();

if (isset($_SESSION['work_id'])) {
    header("Location: work_dashboard.php");
    exit;
}

include 'db.php';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = trim($_POST['login_input'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($input && $password) {
        $stmt = $conn->prepare("SELECT * FROM technicians WHERE tech_id = ? OR phone = ?");
        $stmt->bind_param("ss", $input, $input);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if ($user && password_verify($password, $user['password'])) {
            if ($user['status'] === 'rejected') {
                $error = 'আপনার আবেদন বাতিল করা হয়েছে।';
            } elseif ($user['status'] === 'pending') {
                $error = 'আপনার আবেদন এখনো অনুমোদিত হয়নি। অপেক্ষা করুন।';
            } else {
                $_SESSION['work_id'] = $user['id'];
                $_SESSION['work_tech_id'] = $user['tech_id'];
                $_SESSION['work_name'] = $user['name'];
                header("Location: work_dashboard.php");
                exit;
            }
        } else {
            $error = 'ভুল আইডি/ফোন অথবা পাসওয়ার্ড';
        }
    } else {
        $error = 'সব তথ্য পূরণ করুন';
    }
}
?>
<!DOCTYPE html>
<html lang="bn">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>লগইন — কাজ করতে চাই</title>
<style>
* { margin:0; padding:0; box-sizing:border-box; font-family:'Segoe UI',sans-serif; }
body {
  min-height:100vh; display:flex; align-items:center; justify-content:center;
  background: linear-gradient(135deg, #16a34a, #15803d); padding: 20px;
}
.login-box {
  background: white; padding: 45px 35px; border-radius: 20px;
  box-shadow: 0 20px 60px rgba(0,0,0,0.3);
  width: 100%; max-width: 420px;
}
.login-box .icon { font-size: 60px; text-align: center; display: block; margin-bottom: 15px; }
.login-box h1 { text-align: center; color: #166534; margin-bottom: 8px; font-size: 24px; }
.login-box p.sub { text-align: center; color: #64748b; margin-bottom: 30px; font-size: 14px; }
.login-box input {
  width: 100%; padding: 14px 16px; margin-bottom: 15px;
  border: 2px solid #e2e8f0; border-radius: 10px;
  font-size: 15px; font-family: inherit;
}
.login-box input:focus {
  outline: none; border-color: #16a34a;
  box-shadow: 0 0 0 3px rgba(22,163,74,0.15);
}
.login-box button {
  width: 100%; padding: 15px;
  background: linear-gradient(135deg,#16a34a,#15803d);
  color: white; border: none; border-radius: 10px;
  font-size: 16px; font-weight: 700; cursor: pointer;
  transition: 0.3s;
}
.login-box button:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(22,163,74,0.35); }
.error {
  background: #fee2e2; color: #dc2626;
  padding: 12px; border-radius: 8px;
  margin-bottom: 15px; font-size: 14px; text-align: center;
}
.links { text-align: center; margin-top: 25px; font-size: 14px; color: #64748b; }
.links a { color: #16a34a; text-decoration: none; font-weight: 600; }
.links a:hover { text-decoration: underline; }
</style>
</head>
<body>
<div class="login-box">
  <span class="icon">💼</span>
  <h1>লগইন করুন</h1>
  <p class="sub">ময়মনসিংহ হোম সার্ভিস — কাজ করতে চাই</p>

  <?php if ($error): ?>
    <div class="error"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form method="POST">
    <input type="text" name="login_input" 
           placeholder="আইডি / মোবাইল নম্বর" required autofocus
           value="<?= htmlspecialchars($_POST['login_input'] ?? '') ?>">
    <input type="password" name="password" placeholder="পাসওয়ার্ড" required>
    <button type="submit">🔐 লগইন করুন</button>
  </form>

  <div class="links">
    নতুন? <a href="work.php">আবেদন করুন</a>
  </div>
</div>
</body>
</html>