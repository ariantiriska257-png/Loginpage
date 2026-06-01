<?php
require_once 'config.php';
if (isLoggedIn()) redirect('dashboard.php');
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    if ($username === 'admin' && $password === 'admin123') {
        $_SESSION['user_id'] = 1;
        $_SESSION['username'] = 'admin';
        redirect('dashboard.php');
    } else {
        $error = 'Username atau password salah.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login — List Belanja</title>
<style>
  * { box-sizing:border-box; margin:0; padding:0; }
  body {
    min-height:100vh; background:#f0f4ff;
    display:flex; align-items:center; justify-content:center;
    font-family:'Segoe UI',sans-serif;
  }
  .card {
    background:#fff; border-radius:16px;
    padding:40px 36px; width:100%; max-width:400px;
    box-shadow:0 4px 24px rgba(0,0,0,0.08);
  }
  h1 { font-size:24px; margin-bottom:6px; color:#1a1a2e; }
  .sub { color:#888; font-size:14px; margin-bottom:28px; }
  label { font-size:13px; color:#555; display:block; margin-bottom:6px; }
  input {
    width:100%; padding:11px 14px; border:1px solid #ddd;
    border-radius:8px; font-size:14px; margin-bottom:16px; outline:none;
  }
  input:focus { border-color:#4f8ef7; }
  .btn {
    width:100%; padding:13px; background:#4f8ef7;
    border:none; border-radius:8px; color:#fff;
    font-size:15px; font-weight:600; cursor:pointer;
  }
  .btn:hover { background:#3a7de0; }
  .error {
    background:#fff0f0; border:1px solid #ffcccc;
    color:#e53935; padding:10px 14px; border-radius:8px;
    font-size:13px; margin-bottom:16px;
  }
  .info {
    margin-top:20px; background:#f0f4ff; border-radius:8px;
    padding:10px 14px; font-size:12.5px; color:#666;
  }
</style>
</head>
<body>
<div class="card">
  <h1>🛒 List Belanja</h1>
  <p class="sub">Masuk untuk mengelola list belanja</p>
  <?php if ($error): ?>
    <div class="error">⚠️ <?= htmlspecialchars($error) ?></div>
  <?php endif; ?>
  <form method="POST">
    <label>Username</label>
    <input type="text" name="username" placeholder="Masukkan username" required autofocus>
    <label>Password</label>
    <input type="password" name="password" placeholder="Masukkan password" required>
    <button type="submit" class="btn">Masuk →</button>
  </form>
  <div class="info">
    👤 Username: <strong>admin</strong><br>
    🔑 Password: <strong>admin123</strong>
  </div>
</div>
</body>
</html>