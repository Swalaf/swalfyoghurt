<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

start_admin_session();

if (current_admin()) {
    redirect('/admin/index.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $username = trim($_POST['username'] ?? '');
    $password = (string) ($_POST['password'] ?? '');
    if (attempt_login($username, $password)) {
        redirect('/admin/index.php');
    }
    $error = 'Wrong username or password.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Admin login — Swalaf Yoghurt &amp; Treats</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../css/admin.css">
</head>
<body>
<div class="login-shell">
  <div class="login-card">
    <span class="logo-chip"><img src="../assets/logo.png" alt="Swalaf" style="height:28px;width:auto;display:block"></span>
    <h1>Swalaf Admin</h1>
    <p class="sub">Sign in to manage products, orders and site content.</p>
    <?php if ($error): ?><div class="login-error"><?= h($error) ?></div><?php endif; ?>
    <form method="post">
      <?= csrf_field() ?>
      <div class="field">
        <label>Username</label>
        <input type="text" name="username" autocomplete="username" required autofocus>
      </div>
      <div class="field">
        <label>Password</label>
        <input type="password" name="password" autocomplete="current-password" required>
      </div>
      <button type="submit" class="btn-primary" style="width:100%">Sign in</button>
    </form>
  </div>
</div>
</body>
</html>
