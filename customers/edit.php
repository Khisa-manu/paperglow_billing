<?php
/**
 * PaperGlow Billing System - Edit Customer
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
requireAuth();

$userId = getCurrentUserId();
$pdo = getDBConnection();
$customerId = (int)($_GET['id'] ?? 0);

// Verify ownership
$stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ? AND user_id = ?");
$stmt->execute([$customerId, $userId]);
$customer = $stmt->fetch();

if (!$customer) {
    setFlash('danger', 'Customer not found.');
    header('Location: /customers/index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken()) {
        $error = 'Security session expired. Please refresh the page.';
    } else {
        $name = trim($_POST['name'] ?? '');
        $companyName = trim($_POST['company'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $taxNumber = trim($_POST['tax_number'] ?? '');
        $notes = trim($_POST['notes'] ?? '');

        if (empty($name)) {
            $error = 'Customer name is required.';
        } else {
            try {
                $upStmt = $pdo->prepare("
                    UPDATE customers 
                    SET name = ?, company = ?, email = ?, phone = ?, address = ?, tax_number = ?, notes = ?, updated_at = CURRENT_TIMESTAMP
                    WHERE id = ? AND user_id = ?
                ");
                $upStmt->execute([$name, $companyName, $email, $phone, $address, $taxNumber, $notes, $customerId, $userId]);

                setFlash('success', 'Customer profile updated successfully.');
                header('Location: /customers/view.php?id=' . $customerId);
                exit;
            } catch (Exception $e) {
                $error = 'Failed to update customer: ' . $e->getMessage();
            }
        }
    }
}

$pageTitle = 'Edit Customer — ' . ($customer['name'] ?? '');
require_once __DIR__ . '/../includes/header.php';
?>

<div class="mb-4">
  <a href="/customers/view.php?id=<?= $customerId ?>" class="text-decoration-none text-muted small d-inline-flex align-items-center gap-1 mb-2">
    <i class="bi bi-arrow-left"></i> Back to Customer Profile
  </a>
  <h2 class="h5 fw-bold text-slate-900 mb-1">Edit Customer: <?= e($customer['name']) ?></h2>
</div>

<div class="row">
  <div class="col-lg-8">
    <div class="pg-card">
      <div class="pg-card-body p-4">
        <?php if (!empty($error)): ?>
          <div class="alert alert-danger py-2 px-3 small d-flex align-items-center mb-4">
            <i class="bi bi-exclamation-octagon-fill me-2"></i>
            <div><?= e($error) ?></div>
          </div>
        <?php endif; ?>

        <form method="POST" action="/customers/edit.php?id=<?= $customerId ?>">
          <?= csrfField() ?>

          <div class="row g-3 mb-3">
            <div class="col-sm-6">
              <label class="form-label-pg" for="name">Contact / Client Name *</label>
              <input type="text" class="form-control form-control-pg" id="name" name="name" value="<?= e($_POST['name'] ?? $customer['name']) ?>" required>
            </div>
            <div class="col-sm-6">
              <label class="form-label-pg" for="company">Company / Business Name</label>
              <input type="text" class="form-control form-control-pg" id="company" name="company" value="<?= e($_POST['company'] ?? $customer['company']) ?>">
            </div>
          </div>

          <div class="row g-3 mb-3">
            <div class="col-sm-6">
              <label class="form-label-pg" for="email">Billing Email</label>
              <input type="email" class="form-control form-control-pg" id="email" name="email" value="<?= e($_POST['email'] ?? $customer['email']) ?>">
            </div>
            <div class="col-sm-6">
              <label class="form-label-pg" for="phone">Phone Number</label>
              <input type="text" class="form-control form-control-pg" id="phone" name="phone" value="<?= e($_POST['phone'] ?? $customer['phone']) ?>">
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label-pg" for="tax_number">Tax / VAT / Business Registration No.</label>
            <input type="text" class="form-control form-control-pg" id="tax_number" name="tax_number" value="<?= e($_POST['tax_number'] ?? $customer['tax_number']) ?>">
          </div>

          <div class="mb-3">
            <label class="form-label-pg" for="address">Full Billing Address</label>
            <textarea class="form-control form-control-pg" id="address" name="address" rows="3"><?= e($_POST['address'] ?? $customer['address']) ?></textarea>
          </div>

          <div class="mb-4">
            <label class="form-label-pg" for="notes">Internal Billing Notes</label>
            <textarea class="form-control form-control-pg" id="notes" name="notes" rows="2"><?= e($_POST['notes'] ?? $customer['notes']) ?></textarea>
          </div>

          <div class="d-flex align-items-center justify-content-between pt-3 border-top">
            <a href="/customers/delete.php?id=<?= $customerId ?>&csrf_token=<?= e(getCsrfToken()) ?>" class="btn btn-outline-danger btn-sm" data-confirm="Are you sure you want to permanently delete this customer?">
              <i class="bi bi-trash3 me-1"></i> Delete Customer
            </a>
            <div class="d-flex gap-2">
              <a href="/customers/view.php?id=<?= $customerId ?>" class="btn btn-pg-secondary">Cancel</a>
              <button type="submit" class="btn btn-pg-primary">Save Changes</button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
