<?php
/**
 * PaperGlow Billing System - Add Customer
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
requireAuth();

$userId = getCurrentUserId();
$pdo = getDBConnection();
$isAjax = !empty($_GET['ajax']) || (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

$error = '';
$name = '';
$companyName = '';
$email = '';
$phone = '';
$address = '';
$taxNumber = '';
$notes = '';

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
                $stmt = $pdo->prepare("
                    INSERT INTO customers (user_id, name, company, email, phone, address, tax_number, notes)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$userId, $name, $companyName, $email, $phone, $address, $taxNumber, $notes]);
                $newId = (int)$pdo->lastInsertId();

                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => true,
                        'customer' => [
                            'id'      => $newId,
                            'name'    => $name,
                            'company' => $companyName,
                            'email'   => $email,
                        ]
                    ]);
                    exit;
                }

                setFlash('success', 'Customer "' . $name . '" created successfully.');
                header('Location: /customers/view.php?id=' . $newId);
                exit;
            } catch (Exception $e) {
                $error = 'Error saving customer: ' . $e->getMessage();
            }
        }
    }

    if ($isAjax && !empty($error)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => $error]);
        exit;
    }
}

$pageTitle = 'Add Customer';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="mb-4">
  <a href="/customers/index.php" class="text-decoration-none text-muted small d-inline-flex align-items-center gap-1 mb-2">
    <i class="bi bi-arrow-left"></i> Back to Customer Directory
  </a>
  <h2 class="h5 fw-bold text-slate-900 mb-1">New Customer Account</h2>
  <p class="text-muted small mb-0">Record a new client organization or individual billing party.</p>
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

        <form method="POST" action="/customers/create.php">
          <?= csrfField() ?>

          <div class="row g-3 mb-3">
            <div class="col-sm-6">
              <label class="form-label-pg" for="name">Contact / Client Name *</label>
              <input type="text" class="form-control form-control-pg" id="name" name="name" value="<?= e($name) ?>" placeholder="Marcus Thorne" required autofocus>
            </div>
            <div class="col-sm-6">
              <label class="form-label-pg" for="company">Company / Business Name</label>
              <input type="text" class="form-control form-control-pg" id="company" name="company" value="<?= e($companyName) ?>" placeholder="Aura Architectures Inc">
            </div>
          </div>

          <div class="row g-3 mb-3">
            <div class="col-sm-6">
              <label class="form-label-pg" for="email">Billing Email</label>
              <input type="email" class="form-control form-control-pg" id="email" name="email" value="<?= e($email) ?>" placeholder="billing@client.com">
            </div>
            <div class="col-sm-6">
              <label class="form-label-pg" for="phone">Phone Number</label>
              <input type="text" class="form-control form-control-pg" id="phone" name="phone" value="<?= e($phone) ?>" placeholder="+1 (555) 000-0000">
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label-pg" for="tax_number">Tax / VAT / Business Registration No.</label>
            <input type="text" class="form-control form-control-pg" id="tax_number" name="tax_number" value="<?= e($taxNumber) ?>" placeholder="e.g. US-TAX-94018">
          </div>

          <div class="mb-3">
            <label class="form-label-pg" for="address">Full Billing Address</label>
            <textarea class="form-control form-control-pg" id="address" name="address" rows="3" placeholder="Street address, Suite, City, State, Postal Code, Country"><?= e($address) ?></textarea>
          </div>

          <div class="mb-4">
            <label class="form-label-pg" for="notes">Internal Billing Notes</label>
            <textarea class="form-control form-control-pg" id="notes" name="notes" rows="2" placeholder="Optional notes for your finance team..."><?= e($notes) ?></textarea>
          </div>

          <div class="d-flex align-items-center justify-content-end gap-2 pt-3 border-top">
            <a href="/customers/index.php" class="btn btn-pg-secondary">Cancel</a>
            <button type="submit" class="btn btn-pg-primary">
              <i class="bi bi-check-lg me-1"></i> Save Customer
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="col-lg-4">
    <div class="pg-card bg-light border p-4">
      <h6 class="fw-bold text-dark mb-2"><i class="bi bi-info-circle me-1 text-primary"></i> Customer Billing Tips</h6>
      <p class="small text-muted mb-3">Adding a registered Tax/VAT number and accurate billing address ensures issued invoices and quotations meet international accounting compliance standards.</p>
      <ul class="small text-muted ps-3 mb-0">
        <li>Customer names are displayed prominently on PDF invoices.</li>
        <li>Statements calculate life-to-date billed vs collected.</li>
      </ul>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
