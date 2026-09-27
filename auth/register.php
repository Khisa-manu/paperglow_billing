<?php
/**
 * PaperGlow Billing System - User Registration
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
requireGuest();

$error = '';
$name = '';
$email = '';
$companyName = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken()) {
        $error = 'Invalid security token. Please refresh and try again.';
    } else {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $companyName = trim($_POST['company_name'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (empty($name) || empty($email) || empty($password) || empty($companyName)) {
            $error = 'All fields marked with an asterisk are required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please provide a valid business email address.';
        } elseif (strlen($password) < 6) {
            $error = 'Password must be at least 6 characters in length.';
        } elseif ($password !== $confirmPassword) {
            $error = 'Password confirmation does not match.';
        } else {
            $pdo = getDBConnection();

            // Check if email already registered
            $checkStmt = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
            $checkStmt->execute([$email]);
            if ($checkStmt->fetch()) {
                $error = 'An account with this email address already exists.';
            } else {
                try {
                    $pdo->beginTransaction();

                    $hash = password_hash($password, PASSWORD_BCRYPT);
                    $userStmt = $pdo->prepare("INSERT INTO users (name, email, password, role, status) VALUES (?, ?, ?, 'admin', 'active')");
                    $userStmt->execute([$name, $email, $hash]);
                    $userId = (int)$pdo->lastInsertId();

                    // Create matching company settings record with Kenyan Shillings as default
                    $compStmt = $pdo->prepare("INSERT INTO company_settings (user_id, company_name, email, currency) VALUES (?, ?, ?, 'KES')");
                    $compStmt->execute([$userId, $companyName, $email]);

                    $pdo->commit();

                    // Auto-login
                    $newUserStmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                    $newUserStmt->execute([$userId]);
                    $user = $newUserStmt->fetch();

                    loginUser($user);
                    setFlash('success', 'Your PaperGlow workspace has been created successfully!');
                    header('Location: /dashboard/index.php');
                    exit;
                } catch (Exception $e) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                    $error = 'Failed to register account: ' . $e->getMessage();
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Create Account — PaperGlow Billing</title>
  
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link rel="stylesheet" href="/assets/css/paperglow.css">
</head>
<body class="d-flex align-items-center justify-content-center min-vh-100 py-5" style="background: radial-gradient(circle at top, #1e293b 0%, #0f172a 100%);">

<div class="container" style="max-width: 500px;">
  <div class="text-center mb-4">
    <div class="pg-brand-emblem mx-auto mb-3" style="width: 50px; height: 50px; font-size: 1.5rem;">P</div>
    <h1 class="h3 fw-bold text-white mb-1">Paper<span style="color: #f59e0b;">Glow</span> Billing</h1>
    <p class="text-slate-400 small">Start managing quotes and invoices seamlessly</p>
  </div>

  <div class="card border-0 shadow-lg" style="border-radius: 16px; background: #ffffff;">
    <div class="card-body p-4 p-sm-5">
      <h2 class="h5 fw-bold text-slate-900 mb-3">Create your workspace</h2>

      <?php if (!empty($error)): ?>
        <div class="alert alert-danger py-2 px-3 small d-flex align-items-center mb-3">
          <i class="bi bi-exclamation-octagon-fill me-2"></i>
          <div><?= e($error) ?></div>
        </div>
      <?php endif; ?>

      <form method="POST" action="/auth/register.php" novalidate>
        <?= csrfField() ?>

        <div class="mb-3">
          <label class="form-label-pg" for="name">Full Name *</label>
          <input type="text" class="form-control form-control-pg" id="name" name="name" value="<?= e($name) ?>" placeholder="Jane Doe" required>
        </div>

        <div class="mb-3">
          <label class="form-label-pg" for="company_name">Company / Organization *</label>
          <input type="text" class="form-control form-control-pg" id="company_name" name="company_name" value="<?= e($companyName) ?>" placeholder="Acme Studio LLC" required>
        </div>

        <div class="mb-3">
          <label class="form-label-pg" for="email">Work Email *</label>
          <input type="email" class="form-control form-control-pg" id="email" name="email" value="<?= e($email) ?>" placeholder="jane@acmestudio.com" required>
        </div>

        <div class="row g-2 mb-4">
          <div class="col-sm-6">
            <label class="form-label-pg" for="password">Password *</label>
            <input type="password" class="form-control form-control-pg" id="password" name="password" placeholder="At least 6 chars" required>
          </div>
          <div class="col-sm-6">
            <label class="form-label-pg" for="confirm_password">Confirm *</label>
            <input type="password" class="form-control form-control-pg" id="confirm_password" name="confirm_password" placeholder="Repeat password" required>
          </div>
        </div>

        <button type="submit" class="btn btn-pg-primary w-100 py-2 fs-6 mb-3">
          Complete Registration
        </button>
      </form>
    </div>
    <div class="card-footer bg-light border-0 py-3 text-center rounded-bottom-4">
      <span class="text-muted small">Already have an account?</span>
      <a href="/auth/login.php" class="text-decoration-none fw-semibold ms-1" style="color: #d97706;">Sign In</a>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
