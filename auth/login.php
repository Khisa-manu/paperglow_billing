<?php
/**
 * PaperGlow Billing System - User Login
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
requireGuest();

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken()) {
        $error = 'Invalid security token. Please refresh and try again.';
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            $error = 'Please enter both your email address and password.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } else {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                if ($user['status'] !== 'active') {
                    $error = 'Your account has been deactivated. Please contact support.';
                } else {
                    loginUser($user);
                    setFlash('success', 'Welcome back, ' . $user['name'] . '!');
                    header('Location: /dashboard/index.php');
                    exit;
                }
            } else {
                $error = 'Invalid email credentials or password.';
            }
        }
    }
}

$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sign In — PaperGlow Billing</title>
  
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link rel="stylesheet" href="/assets/css/paperglow.css">
</head>
<body class="d-flex align-items-center justify-content-center min-vh-100 py-5" style="background: radial-gradient(circle at top, #1e293b 0%, #0f172a 100%);">

<div class="container" style="max-width: 440px;">
  <div class="text-center mb-4">
    <div class="pg-brand-emblem mx-auto mb-3" style="width: 50px; height: 50px; font-size: 1.5rem;">P</div>
    <h1 class="h3 fw-bold text-white mb-1">Paper<span style="color: #f59e0b;">Glow</span> Billing</h1>
    <p class="text-slate-400 small">Professional Invoice & Quotation Management</p>
  </div>

  <div class="card border-0 shadow-lg" style="border-radius: 16px; background: #ffffff;">
    <div class="card-body p-4 p-sm-5">
      <h2 class="h5 fw-bold text-slate-900 mb-3">Sign in to your account</h2>

      <?php if (!empty($error)): ?>
        <div class="alert alert-danger py-2 px-3 small d-flex align-items-center mb-3">
          <i class="bi bi-exclamation-octagon-fill me-2"></i>
          <div><?= e($error) ?></div>
        </div>
      <?php endif; ?>

      <?php if ($flash): ?>
        <div class="alert alert-<?= e($flash['type']) ?> py-2 px-3 small mb-3">
          <?= e($flash['message']) ?>
        </div>
      <?php endif; ?>

      <form method="POST" action="/auth/login.php" novalidate>
        <?= csrfField() ?>

        <div class="mb-3">
          <label class="form-label-pg" for="email">Work Email</label>
          <div class="input-group">
            <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-envelope"></i></span>
            <input type="email" class="form-control form-control-pg border-start-0" id="email" name="email" value="<?= e($email) ?>" placeholder="name@company.com" required autofocus>
          </div>
        </div>

        <div class="mb-4">
          <label class="form-label-pg" for="password">Password</label>
          <div class="input-group">
            <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-lock"></i></span>
            <input type="password" class="form-control form-control-pg border-start-0" id="password" name="password" placeholder="••••••••" required>
          </div>
        </div>

        <button type="submit" class="btn btn-pg-primary w-100 py-2 fs-6 mb-3">
          Sign In to Workspace
        </button>

        <div class="p-3 bg-light rounded-3 text-center border">
          <div class="text-muted small fw-semibold mb-1">Demo Credentials:</div>
          <code class="text-dark small d-block">admin@paperglow.com</code>
          <code class="text-dark small d-block">Password123!</code>
        </div>
      </form>
    </div>
    <div class="card-footer bg-light border-0 py-3 text-center rounded-bottom-4">
      <span class="text-muted small">New organization?</span>
      <a href="/auth/register.php" class="text-decoration-none fw-semibold ms-1" style="color: #d97706;">Create Account</a>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
