<?php
require_once __DIR__ . '/includes/auth.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_POST['password'] ?? '') === ADMIN_PASSWORD) {
        $_SESSION['mw_admin'] = true;
        header('Location: /offers.php');
        exit;
    }
    $error = 'Wrong password';
}
?>
<!doctype html>
<html><head><meta charset="utf-8"><title>MoneyWise — Login</title>
<style>
  * { box-sizing: border-box; }
  body{margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;background:#0b1220;color:#e6ebf5;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;}
  form{background:#121a2b;padding:36px;border-radius:14px;width:300px;}
  h1{font-size:20px;color:#22c55e;margin:0 0 20px;}
  input{width:100%;padding:10px 12px;margin-bottom:14px;background:#0b1220;border:1px solid #253046;border-radius:8px;color:#e6ebf5;}
  button{width:100%;padding:10px;background:#22c55e;color:#06210f;border:none;border-radius:8px;font-weight:700;cursor:pointer;}
  .err{color:#f87171;font-size:12px;margin:-8px 0 14px;}
</style></head>
<body>
<form method="post">
  <h1>MoneyWise Admin</h1>
  <?php if ($error): ?><div class="err"><?= htmlspecialchars($error) ?></div><?php endif; ?>
  <input type="password" name="password" placeholder="Password" autofocus>
  <button type="submit">Log In</button>
</form>
</body></html>
