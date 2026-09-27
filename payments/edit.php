<?php
/**
 * PaperGlow Billing System - Edit Payment
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
requireAuth();

$userId = getCurrentUserId();
$pdo = getDBConnection();
$paymentId = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("
    SELECT p.*, i.invoice_number, i.grand_total, i.balance as invoice_balance, c.name as customer_name
    FROM payments p
    JOIN invoices i ON p.invoice_id = i.id
    JOIN customers c ON i.customer_id = c.id
    WHERE p.id = ? AND p.user_id = ?
");
$stmt->execute([$paymentId, $userId]);
$payment = $stmt->fetch();

if (!$payment) {
    setFlash('danger', 'Payment record not found.');
    header('Location: /payments/index.php');
    exit;
}

$currency = $company['currency'] ?? 'USD';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken()) {
        $error = 'Security session expired. Please refresh the page.';
    } else {
        $newAmount = (float)($_POST['amount'] ?? 0);
        $paymentDate = trim($_POST['payment_date'] ?? date('Y-m-d'));
        $paymentMethod = trim($_POST['payment_method'] ?? 'Bank Transfer');
        $referenceNumber = trim($_POST['reference_number'] ?? '');
        $notes = trim($_POST['notes'] ?? '');

        // Check if new amount exceeds invoice total minus other payments
        $otherPaidStmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE invoice_id = ? AND id != ?");
        $otherPaidStmt->execute([$payment['invoice_id'], $paymentId]);
        $otherPaid = (float)$otherPaidStmt->fetchColumn();

        $maxAllowed = (float)$payment['grand_total'] - $otherPaid;

        if ($newAmount <= 0) {
            $error = 'Payment amount must be greater than zero.';
        } elseif ($newAmount > $maxAllowed + 0.001) {
            $error = 'Updated payment exceeds remaining invoice total limit (' . formatCurrency($maxAllowed, $currency) . ').';
        } else {
            try {
                $pdo->beginTransaction();

                $upd = $pdo->prepare("
                    UPDATE payments 
                    SET amount = ?, payment_date = ?, payment_method = ?, reference_number = ?, notes = ?, updated_at = CURRENT_TIMESTAMP
                    WHERE id = ? AND user_id = ?
                ");
                $upd->execute([$newAmount, $paymentDate, $paymentMethod, $referenceNumber, $notes, $paymentId, $userId]);

                // Recalculate invoice balance & status
                recalculateInvoiceBalance($pdo, (int)$payment['invoice_id']);

                $pdo->commit();
                setFlash('success', 'Payment updated successfully.');
                header('Location: /invoices/view.php?id=' . $payment['invoice_id']);
                exit;
            } catch (Exception $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $error = 'Failed to update payment: ' . $e->getMessage();
            }
        }
    }
}

$pageTitle = 'Edit Payment #' . $payment['id'];
require_once __DIR__ . '/../includes/header.php';
?>

<div class="mb-4">
  <a href="/invoices/view.php?id=<?= (int)$payment['invoice_id'] ?>" class="text-decoration-none text-muted small d-inline-flex align-items-center gap-1 mb-2">
    <i class="bi bi-arrow-left"></i> Back to Invoice <?= e($payment['invoice_number']) ?>
  </a>
  <h2 class="h5 fw-bold text-slate-900 mb-1">Edit Payment Receipt</h2>
  <p class="text-muted small mb-0">Invoice: <strong class="mono-num text-dark"><?= e($payment['invoice_number']) ?></strong> &bull; Customer: <?= e($payment['customer_name']) ?></p>
</div>

<div class="row">
  <div class="col-lg-6">
    <div class="pg-card">
      <div class="pg-card-body p-4">
        <?php if (!empty($error)): ?>
          <div class="alert alert-danger py-2 px-3 small d-flex align-items-center mb-4">
            <i class="bi bi-exclamation-octagon-fill me-2"></i>
            <div><?= e($error) ?></div>
          </div>
        <?php endif; ?>

        <form method="POST" action="/payments/edit.php?id=<?= $paymentId ?>">
          <?= csrfField() ?>

          <div class="row g-3 mb-3">
            <div class="col-sm-6">
              <label class="form-label-pg" for="amount">Payment Amount *</label>
              <div class="input-group">
                <span class="input-group-text bg-light text-muted font-semibold"><?= e(getCurrencySymbol($currency)) ?></span>
                <input type="number" class="form-control form-control-pg mono-num fw-bold" id="amount" name="amount" value="<?= number_format((float)($_POST['amount'] ?? $payment['amount']), 2, '.', '') ?>" step="0.01" min="0.01" required>
              </div>
            </div>

            <div class="col-sm-6">
              <label class="form-label-pg" for="payment_date">Payment Date *</label>
              <input type="date" class="form-control form-control-pg" id="payment_date" name="payment_date" value="<?= e($_POST['payment_date'] ?? $payment['payment_date']) ?>" required>
            </div>
          </div>

          <div class="row g-3 mb-3">
            <div class="col-sm-6">
              <label class="form-label-pg" for="payment_method">Payment Method *</label>
              <select name="payment_method" id="payment_method" class="form-select form-select-pg" required>
                <?php foreach (['Bank Transfer', 'Card', 'Mobile Money', 'Cash', 'Other'] as $meth): ?>
                  <option value="<?= $meth ?>" <?= ($payment['payment_method'] === $meth) ? 'selected' : '' ?>><?= $meth ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="col-sm-6">
              <label class="form-label-pg" for="reference_number">Reference / Txn ID</label>
              <input type="text" class="form-control form-control-pg" id="reference_number" name="reference_number" value="<?= e($_POST['reference_number'] ?? $payment['reference_number']) ?>">
            </div>
          </div>

          <div class="mb-4">
            <label class="form-label-pg" for="notes">Notes</label>
            <textarea class="form-control form-control-pg" id="notes" name="notes" rows="2"><?= e($_POST['notes'] ?? $payment['notes']) ?></textarea>
          </div>

          <div class="d-flex align-items-center justify-content-end gap-2 pt-3 border-top">
            <a href="/invoices/view.php?id=<?= (int)$payment['invoice_id'] ?>" class="btn btn-pg-secondary">Cancel</a>
            <button type="submit" class="btn btn-pg-primary">Save Changes</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
