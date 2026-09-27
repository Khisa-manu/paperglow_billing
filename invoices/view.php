<?php
/**
 * PaperGlow Billing System - View Invoice Details & Payments
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
requireAuth();

$userId = getCurrentUserId();
$pdo = getDBConnection();
$invoiceId = (int)($_GET['id'] ?? 0);

// Recalculate balance to ensure 100% data integrity
recalculateInvoiceBalance($pdo, $invoiceId);

$stmt = $pdo->prepare("
    SELECT i.*, c.name as customer_name, c.company as customer_company, c.email as customer_email, 
           c.phone as customer_phone, c.address as customer_address, c.tax_number as customer_tax
    FROM invoices i
    JOIN customers c ON i.customer_id = c.id
    WHERE i.id = ? AND i.user_id = ?
");
$stmt->execute([$invoiceId, $userId]);
$invoice = $stmt->fetch();

if (!$invoice) {
    setFlash('danger', 'Invoice not found.');
    header('Location: /invoices/index.php');
    exit;
}

$currency = $company['currency'] ?? 'USD';

// Items
$itemStmt = $pdo->prepare("SELECT * FROM invoice_items WHERE invoice_id = ? ORDER BY sort_order ASC, id ASC");
$itemStmt->execute([$invoiceId]);
$items = $itemStmt->fetchAll();

// Payments
$payStmt = $pdo->prepare("SELECT * FROM payments WHERE invoice_id = ? ORDER BY payment_date DESC, id DESC");
$payStmt->execute([$invoiceId]);
$payments = $payStmt->fetchAll();

// Handle quick payment submission from embedded modal
$payError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['record_payment'])) {
    if (!verifyCsrfToken()) {
        $payError = 'Security session expired. Please retry.';
    } else {
        $amount = (float)($_POST['amount'] ?? 0);
        $payDate = trim($_POST['payment_date'] ?? date('Y-m-d'));
        $payMethod = trim($_POST['payment_method'] ?? 'Bank Transfer');
        $refNumber = trim($_POST['reference_number'] ?? '');
        $notes = trim($_POST['notes'] ?? '');

        $balance = (float)$invoice['balance'];
        if ($amount <= 0) {
            $payError = 'Payment amount must be greater than zero.';
        } elseif ($amount > $balance + 0.001) {
            $payError = 'Payment amount (' . formatCurrency($amount, $currency) . ') exceeds remaining invoice balance (' . formatCurrency($balance, $currency) . ').';
        } else {
            try {
                $insPay = $pdo->prepare("
                    INSERT INTO payments (user_id, invoice_id, payment_date, amount, payment_method, reference_number, notes)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $insPay->execute([$userId, $invoiceId, $payDate, $amount, $payMethod, $refNumber, $notes]);

                // Recalculate
                recalculateInvoiceBalance($pdo, $invoiceId);

                setFlash('success', 'Payment of ' . formatCurrency($amount, $currency) . ' recorded successfully.');
                header('Location: /invoices/view.php?id=' . $invoiceId);
                exit;
            } catch (Exception $e) {
                $payError = 'Failed to record payment: ' . $e->getMessage();
            }
        }
    }
}

$pageTitle = 'Invoice ' . $invoice['invoice_number'];
require_once __DIR__ . '/../includes/header.php';
?>

<div class="mb-4 d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 no-print">
  <div>
    <a href="/invoices/index.php" class="text-decoration-none text-muted small d-inline-flex align-items-center gap-1 mb-2">
      <i class="bi bi-arrow-left"></i> Back to Invoices
    </a>
    <div class="d-flex align-items-center gap-2">
      <h2 class="h5 fw-bold text-slate-900 mb-0 mono-num"><?= e($invoice['invoice_number']) ?></h2>
      <?= getStatusBadge($invoice['status']) ?>
    </div>
  </div>

  <div class="d-flex flex-wrap align-items-center gap-2">
    <?php if ((float)$invoice['balance'] > 0): ?>
      <button type="button" class="btn btn-pg-primary btn-sm d-inline-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#recordPaymentModal">
        <i class="bi bi-wallet2"></i>
        <span>Record Payment</span>
      </button>
    <?php endif; ?>

    <a href="/invoices/pdf.php?id=<?= $invoiceId ?>" class="btn btn-outline-danger btn-sm" target="_blank">
      <i class="bi bi-file-earmark-pdf me-1"></i> PDF
    </a>
    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.print()">
      <i class="bi bi-printer me-1"></i> Print
    </button>
    <a href="/invoices/edit.php?id=<?= $invoiceId ?>" class="btn btn-outline-secondary btn-sm">
      <i class="bi bi-pencil me-1"></i> Edit
    </a>
  </div>
</div>

<?php if (!empty($payError)): ?>
  <div class="alert alert-danger py-2 px-3 small d-flex align-items-center mb-4 no-print">
    <i class="bi bi-exclamation-octagon-fill me-2"></i>
    <div><?= e($payError) ?></div>
  </div>
<?php endif; ?>

<!-- Main Invoice Paper Layout -->
<div class="pg-document-paper mb-4">
  <div class="row align-items-center pb-4 mb-4 border-bottom">
    <div class="col-sm-7">
      <?php if (!empty($company['logo']) && file_exists(dirname(__DIR__) . '/' . ltrim($company['logo'], '/'))): ?>
        <img src="/<?= e($company['logo']) ?>" alt="<?= e($company['company_name'] ?? 'PaperGlow') ?>" style="max-height: 48px; max-width: 190px; object-fit: contain;" class="mb-2">
      <?php else: ?>
        <div class="d-flex align-items-center gap-2 mb-2">
          <div class="pg-brand-emblem" style="width: 32px; height: 32px; font-size: 1rem;">P</div>
          <span class="fw-bold fs-5 text-dark"><?= e($company['company_name'] ?? 'PaperGlow Studio LLC') ?></span>
        </div>
      <?php endif; ?>
      <div class="text-muted small whitespace-pre-line"><?= nl2br(e($company['address'] ?? '')) ?></div>
      <div class="text-muted small mt-1">
        <?php if (!empty($company['phone'])): ?><span><?= e($company['phone']) ?></span> &bull; <?php endif; ?>
        <?php if (!empty($company['email'])): ?><span><?= e($company['email']) ?></span><?php endif; ?>
      </div>
      <?php if (!empty($company['tax_number'])): ?>
        <div class="text-muted small">Tax / VAT: <strong><?= e($company['tax_number']) ?></strong></div>
      <?php endif; ?>
    </div>
    <div class="col-sm-5 text-sm-end mt-3 mt-sm-0">
      <h1 class="h3 fw-bold text-uppercase tracking-wider text-slate-800 mb-1">Tax Invoice</h1>
      <div class="mono-num fw-bold fs-5 text-dark mb-1"><?= e($invoice['invoice_number']) ?></div>
      <div class="text-muted small">Issue Date: <strong><?= e(date('M d, Y', strtotime($invoice['invoice_date']))) ?></strong></div>
      <div class="text-muted small">Due Date: <strong class="<?= $invoice['due_date'] < date('Y-m-d') && (float)$invoice['balance'] > 0 ? 'text-danger' : '' ?>"><?= e(date('M d, Y', strtotime($invoice['due_date']))) ?></strong></div>
    </div>
  </div>

  <div class="row mb-4">
    <div class="col-sm-6">
      <div class="text-muted small fw-bold text-uppercase mb-1">Billed To:</div>
      <div class="fw-bold fs-6 text-slate-900"><?= e($invoice['customer_name']) ?></div>
      <?php if (!empty($invoice['customer_company'])): ?>
        <div class="text-muted small fw-semibold"><?= e($invoice['customer_company']) ?></div>
      <?php endif; ?>
      <div class="text-muted small mt-1 whitespace-pre-line"><?= nl2br(e($invoice['customer_address'] ?? '')) ?></div>
      <?php if (!empty($invoice['customer_email'])): ?>
        <div class="text-muted small"><?= e($invoice['customer_email']) ?></div>
      <?php endif; ?>
      <?php if (!empty($invoice['customer_tax'])): ?>
        <div class="text-muted small">Tax ID: <?= e($invoice['customer_tax']) ?></div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Items Table -->
  <div class="table-responsive mb-4">
    <table class="table-pg">
      <thead>
        <tr>
          <th style="width: 50%;">Description</th>
          <th class="text-end">Qty</th>
          <th class="text-end">Unit Price</th>
          <th class="text-end">Discount</th>
          <th class="text-end">Tax</th>
          <th class="text-end">Line Total</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($items as $item): ?>
          <tr>
            <td>
              <div class="fw-medium text-dark"><?= e($item['description']) ?></div>
            </td>
            <td class="text-end mono-num"><?= number_format((float)$item['quantity'], 2) ?></td>
            <td class="text-end mono-num"><?= formatCurrency($item['unit_price'], $currency) ?></td>
            <td class="text-end mono-num text-muted"><?= (float)$item['discount'] > 0 ? '-' . formatCurrency($item['discount'], $currency) : '—' ?></td>
            <td class="text-end mono-num text-muted"><?= (float)$item['tax_rate'] > 0 ? (float)$item['tax_rate'] . '%' : '0%' ?></td>
            <td class="text-end mono-num fw-semibold"><?= formatCurrency($item['line_total'], $currency) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <!-- Financial Summary & Balance Due -->
  <div class="row justify-content-end mb-4">
    <div class="col-sm-6 col-md-5">
      <div class="p-3 bg-light rounded-3 border">
        <div class="d-flex justify-content-between py-1 small">
          <span class="text-muted">Subtotal:</span>
          <span class="mono-num fw-medium"><?= formatCurrency($invoice['subtotal'], $currency) ?></span>
        </div>
        <?php if ((float)$invoice['discount_total'] > 0): ?>
          <div class="d-flex justify-content-between py-1 small">
            <span class="text-muted">Discount Total:</span>
            <span class="mono-num text-danger">-<?= formatCurrency($invoice['discount_total'], $currency) ?></span>
          </div>
        <?php endif; ?>
        <?php if ((float)$invoice['tax_total'] > 0): ?>
          <div class="d-flex justify-content-between py-1 small">
            <span class="text-muted">Sales Tax:</span>
            <span class="mono-num"><?= formatCurrency($invoice['tax_total'], $currency) ?></span>
          </div>
        <?php endif; ?>
        <hr class="my-2">
        <div class="d-flex justify-content-between py-1">
          <span class="fw-bold text-dark">Invoice Total:</span>
          <span class="mono-num fw-bold fs-5 text-dark"><?= formatCurrency($invoice['grand_total'], $currency) ?></span>
        </div>
        <div class="d-flex justify-content-between py-1 small">
          <span class="text-muted">Paid to Date:</span>
          <span class="mono-num text-success fw-semibold"><?= formatCurrency($invoice['paid_amount'], $currency) ?></span>
        </div>
        <div class="d-flex justify-content-between py-2 border-top mt-1">
          <span class="fw-bold text-slate-900">Remaining Balance:</span>
          <span class="mono-num fw-bold fs-5 <?= (float)$invoice['balance'] > 0 ? 'text-danger' : 'text-success' ?>">
            <?= formatCurrency($invoice['balance'], $currency) ?>
          </span>
        </div>
      </div>
    </div>
  </div>

  <!-- Payment Details (Bank & Mobile Money) -->
  <div class="p-3 rounded-3 bg-light border mb-4">
    <div class="row g-3">
      <div class="col-sm-6">
        <h6 class="fw-bold text-dark small text-uppercase mb-2"><i class="bi bi-bank me-1 text-primary"></i> Bank Transfer Instructions</h6>
        <div class="small text-muted">Bank Name: <strong class="text-dark"><?= e($company['bank_name'] ?: 'Cascade Horizon Bank') ?></strong></div>
        <div class="small text-muted">Account Name: <strong class="text-dark"><?= e($company['bank_account_name'] ?: $company['company_name']) ?></strong></div>
        <div class="small text-muted">Account Number: <strong class="text-dark mono-num"><?= e($company['bank_account_number'] ?: '884920491029') ?></strong></div>
        <?php if (!empty($company['bank_swift'])): ?>
          <div class="small text-muted">SWIFT / BIC: <strong class="text-dark mono-num"><?= e($company['bank_swift']) ?></strong></div>
        <?php endif; ?>
      </div>
      <div class="col-sm-6">
        <h6 class="fw-bold text-dark small text-uppercase mb-2"><i class="bi bi-phone me-1 text-success"></i> Mobile Money / Online</h6>
        <div class="small text-muted">Provider: <strong class="text-dark"><?= e($company['mobile_money_name'] ?: 'PaperGlow Pay') ?></strong></div>
        <div class="small text-muted">Payment Number: <strong class="text-dark mono-num"><?= e($company['mobile_money_number'] ?: '+1 (206) 555-0199') ?></strong></div>
        <div class="small text-muted mt-2">Reference: <strong class="text-dark mono-num"><?= e($invoice['invoice_number']) ?></strong></div>
      </div>
    </div>
  </div>

  <!-- Terms & Notes -->
  <?php if (!empty($invoice['notes']) || !empty($invoice['terms'])): ?>
    <div class="row pt-3 border-top g-3">
      <?php if (!empty($invoice['notes'])): ?>
        <div class="col-md-6">
          <div class="text-muted small fw-bold text-uppercase mb-1">Notes:</div>
          <div class="text-muted small whitespace-pre-line"><?= nl2br(e($invoice['notes'])) ?></div>
        </div>
      <?php endif; ?>
      <?php if (!empty($invoice['terms'])): ?>
        <div class="col-md-6">
          <div class="text-muted small fw-bold text-uppercase mb-1">Terms:</div>
          <div class="text-muted small whitespace-pre-line"><?= nl2br(e($invoice['terms'])) ?></div>
        </div>
      <?php endif; ?>
    </div>
  <?php endif; ?>
</div>

<!-- Payments Ledger for this Invoice -->
<div class="pg-card no-print">
  <div class="pg-card-header">
    <div>
      <h3 class="h6 fw-bold mb-0 text-slate-900">Payment Transactions</h3>
      <span class="text-muted small">Recorded payments applied to this invoice</span>
    </div>
    <?php if ((float)$invoice['balance'] > 0): ?>
      <button type="button" class="btn btn-pg-primary btn-sm" data-bs-toggle="modal" data-bs-target="#recordPaymentModal">
        <i class="bi bi-plus-lg me-1"></i> Record Payment
      </button>
    <?php endif; ?>
  </div>
  <div class="table-responsive">
    <table class="table-pg">
      <thead>
        <tr>
          <th>Payment Date</th>
          <th>Method</th>
          <th>Reference #</th>
          <th>Notes</th>
          <th class="text-end">Amount</th>
          <th class="text-end">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($payments)): ?>
          <tr>
            <td colspan="6" class="text-center py-4 text-muted small">No payments recorded for this invoice yet.</td>
          </tr>
        <?php else: ?>
          <?php foreach ($payments as $pay): ?>
            <tr>
              <td class="text-muted small"><?= e(date('M d, Y', strtotime($pay['payment_date']))) ?></td>
              <td><span class="badge bg-light text-dark border"><?= e($pay['payment_method']) ?></span></td>
              <td class="mono-num small"><?= e($pay['reference_number'] ?: '—') ?></td>
              <td class="text-muted small"><?= e($pay['notes'] ?: '—') ?></td>
              <td class="text-end mono-num fw-bold text-success">+<?= formatCurrency($pay['amount'], $currency) ?></td>
              <td class="text-end">
                <a href="/payments/delete.php?id=<?= (int)$pay['id'] ?>&csrf_token=<?= e(getCsrfToken()) ?>" class="btn btn-sm btn-outline-danger border-0" data-confirm="Delete this payment of <?= formatCurrency($pay['amount'], $currency) ?>? The invoice balance will be restored.">
                  <i class="bi bi-trash3"></i>
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Modal: Record Payment -->
<div class="modal fade" id="recordPaymentModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" action="/invoices/view.php?id=<?= $invoiceId ?>">
        <?= csrfField() ?>
        <input type="hidden" name="record_payment" value="1">
        
        <div class="modal-header">
          <h5 class="modal-title fw-bold">Record Payment for <?= e($invoice['invoice_number']) ?></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="p-3 bg-light rounded-3 mb-3 border d-flex justify-content-between align-items-center">
            <span class="small text-muted">Current Open Balance:</span>
            <span class="fw-bold mono-num text-danger fs-5"><?= formatCurrency($invoice['balance'], $currency) ?></span>
          </div>

          <div class="mb-3">
            <label class="form-label-pg" for="pay_amount">Payment Amount *</label>
            <div class="input-group">
              <span class="input-group-text bg-light text-muted font-semibold"><?= e(getCurrencySymbol($currency)) ?></span>
              <input type="number" class="form-control form-control-pg mono-num fw-bold" id="pay_amount" name="amount" value="<?= number_format((float)$invoice['balance'], 2, '.', '') ?>" step="0.01" min="0.01" max="<?= number_format((float)$invoice['balance'], 2, '.', '') ?>" required>
            </div>
            <div class="form-text small">Supports full or partial payment up to <?= formatCurrency($invoice['balance'], $currency) ?>.</div>
          </div>

          <div class="row g-2 mb-3">
            <div class="col-sm-6">
              <label class="form-label-pg" for="pay_date">Payment Date *</label>
              <input type="date" class="form-control form-control-pg" id="pay_date" name="payment_date" value="<?= date('Y-m-d') ?>" required>
            </div>
            <div class="col-sm-6">
              <label class="form-label-pg" for="pay_method">Payment Method *</label>
              <select name="payment_method" id="pay_method" class="form-select form-select-pg" required>
                <option value="Bank Transfer">Bank Transfer</option>
                <option value="Card">Card / Online</option>
                <option value="Mobile Money">Mobile Money</option>
                <option value="Cash">Cash</option>
                <option value="Other">Other</option>
              </select>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label-pg" for="pay_ref">Reference / Transaction Number</label>
            <input type="text" class="form-control form-control-pg" id="pay_ref" name="reference_number" placeholder="e.g. WIRE-89201, Stripe Chg ID">
          </div>

          <div class="mb-2">
            <label class="form-label-pg" for="pay_notes">Payment Notes</label>
            <textarea class="form-control form-control-pg" id="pay_notes" name="notes" rows="2" placeholder="Optional notes..."></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-pg-primary">Save Payment Receipt</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
