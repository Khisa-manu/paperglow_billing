<?php
/**
 * PaperGlow Billing System - Record Payment
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
requireAuth();

$userId = getCurrentUserId();
$pdo = getDBConnection();
$currency = $company['currency'] ?? 'USD';

$preselectInvoiceId = (int)($_GET['invoice_id'] ?? 0);

// Fetch open invoices with balance > 0
$invStmt = $pdo->prepare("
    SELECT i.id, i.invoice_number, i.balance, i.grand_total, c.name as customer_name
    FROM invoices i
    JOIN customers c ON i.customer_id = c.id
    WHERE i.user_id = ? AND (i.balance > 0 OR i.id = ?)
    ORDER BY i.invoice_date DESC
");
$invStmt->execute([$userId, $preselectInvoiceId]);
$openInvoices = $invStmt->fetchAll();

$error = '';
$selectedInvoiceId = $preselectInvoiceId;
$amount = '';
$paymentDate = date('Y-m-d');
$paymentMethod = 'Bank Transfer';
$referenceNumber = '';
$notes = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken()) {
        $error = 'Security session expired. Please refresh the page.';
    } else {
        $selectedInvoiceId = (int)($_POST['invoice_id'] ?? 0);
        $amount = (float)($_POST['amount'] ?? 0);
        $paymentDate = trim($_POST['payment_date'] ?? date('Y-m-d'));
        $paymentMethod = trim($_POST['payment_method'] ?? 'Bank Transfer');
        $referenceNumber = trim($_POST['reference_number'] ?? '');
        $notes = trim($_POST['notes'] ?? '');

        // Verify invoice
        $chkInv = $pdo->prepare("SELECT * FROM invoices WHERE id = ? AND user_id = ?");
        $chkInv->execute([$selectedInvoiceId, $userId]);
        $targetInvoice = $chkInv->fetch();

        if (!$targetInvoice) {
            $error = 'Please select a valid invoice.';
        } elseif ($amount <= 0) {
            $error = 'Payment amount must be greater than zero.';
        } elseif ($amount > (float)$targetInvoice['balance'] + 0.001) {
            $error = sprintf(
                'Payment amount (%s) exceeds remaining invoice balance (%s). Overpayments are restricted.',
                formatCurrency($amount, $currency),
                formatCurrency($targetInvoice['balance'], $currency)
            );
        } else {
            try {
                $pdo->beginTransaction();

                // 1. Insert payment record
                $ins = $pdo->prepare("
                    INSERT INTO payments (user_id, invoice_id, payment_date, amount, payment_method, reference_number, notes)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $ins->execute([$userId, $selectedInvoiceId, $paymentDate, $amount, $paymentMethod, $referenceNumber, $notes]);

                // 2. Recalculate invoice balance & update status according to billing rules
                recalculateInvoiceBalance($pdo, $selectedInvoiceId);

                $pdo->commit();

                setFlash('success', 'Payment of ' . formatCurrency($amount, $currency) . ' successfully recorded.');
                header('Location: /invoices/view.php?id=' . $selectedInvoiceId);
                exit;
            } catch (Exception $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $error = 'Failed to record payment: ' . $e->getMessage();
            }
        }
    }
}

$pageTitle = 'Record Payment';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="mb-4">
  <a href="/payments/index.php" class="text-decoration-none text-muted small d-inline-flex align-items-center gap-1 mb-2">
    <i class="bi bi-arrow-left"></i> Back to Payments Ledger
  </a>
  <h2 class="h5 fw-bold text-slate-900 mb-1">Record Inbound Payment</h2>
  <p class="text-muted small mb-0">Apply received payment towards an open invoice and recalculate balance due.</p>
</div>

<div class="row">
  <div class="col-lg-7">
    <div class="pg-card">
      <div class="pg-card-body p-4">
        <?php if (!empty($error)): ?>
          <div class="alert alert-danger py-2 px-3 small d-flex align-items-center mb-4">
            <i class="bi bi-exclamation-octagon-fill me-2"></i>
            <div><?= e($error) ?></div>
          </div>
        <?php endif; ?>

        <form method="POST" action="/payments/create.php">
          <?= csrfField() ?>

          <div class="mb-3">
            <label class="form-label-pg" for="invoiceSelect">Applied Invoice *</label>
            <select name="invoice_id" id="invoiceSelect" class="form-select form-select-pg" required>
              <option value="">Select unpaid invoice...</option>
              <?php foreach ($openInvoices as $inv): ?>
                <option value="<?= (int)$inv['id'] ?>" data-balance="<?= (float)$inv['balance'] ?>" <?= ($selectedInvoiceId === (int)$inv['id']) ? 'selected' : '' ?>>
                  <?= e($inv['invoice_number']) ?> &bull; <?= e($inv['customer_name']) ?> (Open Balance: <?= formatCurrency($inv['balance'], $currency) ?>)
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="row g-3 mb-3">
            <div class="col-sm-6">
              <label class="form-label-pg" for="payAmount">Payment Amount *</label>
              <div class="input-group">
                <span class="input-group-text bg-light text-muted font-semibold"><?= e(getCurrencySymbol($currency)) ?></span>
                <input type="number" class="form-control form-control-pg mono-num fw-bold" id="payAmount" name="amount" value="<?= e((string)$amount) ?>" step="0.01" min="0.01" placeholder="0.00" required>
              </div>
            </div>

            <div class="col-sm-6">
              <label class="form-label-pg" for="payment_date">Payment Date *</label>
              <input type="date" class="form-control form-control-pg" id="payment_date" name="payment_date" value="<?= e($paymentDate) ?>" required>
            </div>
          </div>

          <div class="row g-3 mb-3">
            <div class="col-sm-6">
              <label class="form-label-pg" for="payment_method">Payment Method *</label>
              <select name="payment_method" id="payment_method" class="form-select form-select-pg" required>
                <?php foreach (['Bank Transfer', 'Card', 'Mobile Money', 'Cash', 'Other'] as $meth): ?>
                  <option value="<?= $meth ?>" <?= $paymentMethod === $meth ? 'selected' : '' ?>><?= $meth ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="col-sm-6">
              <label class="form-label-pg" for="reference_number">Reference / Transaction ID</label>
              <input type="text" class="form-control form-control-pg" id="reference_number" name="reference_number" value="<?= e($referenceNumber) ?>" placeholder="e.g. TXN-940219">
            </div>
          </div>

          <div class="mb-4">
            <label class="form-label-pg" for="notes">Notes</label>
            <textarea class="form-control form-control-pg" id="notes" name="notes" rows="2" placeholder="Optional internal notes..."><?= e($notes) ?></textarea>
          </div>

          <div class="d-flex align-items-center justify-content-end gap-2 pt-3 border-top">
            <a href="/payments/index.php" class="btn btn-pg-secondary">Cancel</a>
            <button type="submit" class="btn btn-pg-primary">
              <i class="bi bi-check-lg me-1"></i> Confirm & Save Payment
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="col-lg-5">
    <div class="pg-card bg-light border p-4">
      <h6 class="fw-bold text-dark mb-2"><i class="bi bi-shield-check me-1 text-success"></i> Automated Balance Management</h6>
      <p class="small text-muted mb-2">PaperGlow Billing automatically applies financial calculations whenever a payment is logged:</p>
      <ul class="small text-muted ps-3 mb-3">
        <li><strong>Paid in Full:</strong> When payments equal grand total, status automatically transitions to <em>Paid</em>.</li>
        <li><strong>Partial Payments:</strong> Status updates to <em>Partially Paid</em>, preserving outstanding audit trails.</li>
        <li><strong>Overdue Status:</strong> If due date expires with zero collections, marked as <em>Overdue</em>.</li>
      </ul>
      <div class="p-3 bg-white rounded-3 border small text-muted">
        <i class="bi bi-info-circle text-primary me-1"></i> Overpayment protection ensures customers are not inadvertently credited beyond the document's total balance.
      </div>
    </div>
  </div>
</div>

<script>
  // Auto-populate remaining balance on invoice selection
  document.getElementById('invoiceSelect').addEventListener('change', function() {
    const selected = this.options[this.selectedIndex];
    const bal = selected.getAttribute('data-balance');
    const amountInput = document.getElementById('payAmount');
    if (bal && amountInput && (!amountInput.value || parseFloat(amountInput.value) <= 0)) {
      amountInput.value = parseFloat(bal).toFixed(2);
    }
  });
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
