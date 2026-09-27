<?php
/**
 * PaperGlow Billing System - View Quotation
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
requireAuth();

$userId = getCurrentUserId();
$pdo = getDBConnection();
$quotationId = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("
    SELECT q.*, c.name as customer_name, c.company as customer_company, c.email as customer_email, 
           c.phone as customer_phone, c.address as customer_address, c.tax_number as customer_tax
    FROM quotations q
    JOIN customers c ON q.customer_id = c.id
    WHERE q.id = ? AND q.user_id = ?
");
$stmt->execute([$quotationId, $userId]);
$quotation = $stmt->fetch();

if (!$quotation) {
    setFlash('danger', 'Quotation not found.');
    header('Location: /quotations/index.php');
    exit;
}

$currency = $company['currency'] ?? 'USD';

// Fetch items
$itemStmt = $pdo->prepare("SELECT * FROM quotation_items WHERE quotation_id = ? ORDER BY sort_order ASC, id ASC");
$itemStmt->execute([$quotationId]);
$items = $itemStmt->fetchAll();

// Handle quick status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    if (verifyCsrfToken()) {
        $newStatus = trim($_POST['status'] ?? '');
        if (in_array($newStatus, ['Draft', 'Sent', 'Accepted', 'Rejected', 'Expired'])) {
            $up = $pdo->prepare("UPDATE quotations SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ? AND user_id = ?");
            $up->execute([$newStatus, $quotationId, $userId]);
            setFlash('success', 'Quotation status updated to ' . $newStatus);
            header('Location: /quotations/view.php?id=' . $quotationId);
            exit;
        }
    }
}

$pageTitle = 'Quotation ' . $quotation['quotation_number'];
require_once __DIR__ . '/../includes/header.php';
?>

<div class="mb-4 d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 no-print">
  <div>
    <a href="/quotations/index.php" class="text-decoration-none text-muted small d-inline-flex align-items-center gap-1 mb-2">
      <i class="bi bi-arrow-left"></i> Back to Quotations
    </a>
    <div class="d-flex align-items-center gap-2">
      <h2 class="h5 fw-bold text-slate-900 mb-0 mono-num"><?= e($quotation['quotation_number']) ?></h2>
      <?= getStatusBadge($quotation['status']) ?>
    </div>
  </div>

  <div class="d-flex flex-wrap align-items-center gap-2">
    <!-- Quick Status Dropdown -->
    <form method="POST" action="/quotations/view.php?id=<?= $quotationId ?>" class="d-inline">
      <?= csrfField() ?>
      <input type="hidden" name="update_status" value="1">
      <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
        <?php foreach (['Draft', 'Sent', 'Accepted', 'Rejected', 'Expired'] as $st): ?>
          <option value="<?= $st ?>" <?= $quotation['status'] === $st ? 'selected' : '' ?>>Mark: <?= $st ?></option>
        <?php endforeach; ?>
      </select>
    </form>

    <?php if (empty($quotation['converted_invoice_id']) && $quotation['status'] === 'Accepted'): ?>
      <form method="POST" action="/quotations/convert.php" class="d-inline">
        <?= csrfField() ?>
        <input type="hidden" name="quotation_id" value="<?= $quotationId ?>">
        <button type="submit" class="btn btn-pg-primary btn-sm d-inline-flex align-items-center gap-1" data-confirm="Convert quotation into a formal invoice?">
          <i class="bi bi-arrow-right-circle"></i>
          <span>Convert to Invoice</span>
        </button>
      </form>
    <?php elseif (!empty($quotation['converted_invoice_id'])): ?>
      <a href="/invoices/view.php?id=<?= (int)$quotation['converted_invoice_id'] ?>" class="btn btn-outline-success btn-sm">
        <i class="bi bi-receipt me-1"></i> View Converted Invoice
      </a>
    <?php endif; ?>

    <a href="/quotations/pdf.php?id=<?= $quotationId ?>" class="btn btn-outline-danger btn-sm" target="_blank">
      <i class="bi bi-file-earmark-pdf me-1"></i> PDF
    </a>
    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.print()">
      <i class="bi bi-printer me-1"></i> Print
    </button>
    <a href="/quotations/edit.php?id=<?= $quotationId ?>" class="btn btn-outline-secondary btn-sm">
      <i class="bi bi-pencil me-1"></i> Edit
    </a>
  </div>
</div>

<!-- Document Paper Layout -->
<div class="pg-document-paper">
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
    </div>
    <div class="col-sm-5 text-sm-end mt-3 mt-sm-0">
      <h1 class="h3 fw-bold text-uppercase tracking-wider text-slate-800 mb-1">Quotation</h1>
      <div class="mono-num fw-bold fs-5 text-dark mb-1"><?= e($quotation['quotation_number']) ?></div>
      <div class="text-muted small">Date: <strong><?= e(date('M d, Y', strtotime($quotation['quotation_date']))) ?></strong></div>
      <div class="text-muted small">Valid Until: <strong><?= e(date('M d, Y', strtotime($quotation['expiry_date']))) ?></strong></div>
    </div>
  </div>

  <div class="row mb-4">
    <div class="col-sm-6">
      <div class="text-muted small fw-bold text-uppercase mb-1">Quote Prepared For:</div>
      <div class="fw-bold fs-6 text-slate-900"><?= e($quotation['customer_name']) ?></div>
      <?php if (!empty($quotation['customer_company'])): ?>
        <div class="text-muted small fw-semibold"><?= e($quotation['customer_company']) ?></div>
      <?php endif; ?>
      <div class="text-muted small mt-1 whitespace-pre-line"><?= nl2br(e($quotation['customer_address'] ?? '')) ?></div>
      <?php if (!empty($quotation['customer_email'])): ?>
        <div class="text-muted small"><?= e($quotation['customer_email']) ?></div>
      <?php endif; ?>
      <?php if (!empty($quotation['customer_tax'])): ?>
        <div class="text-muted small">Tax ID: <?= e($quotation['customer_tax']) ?></div>
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
          <th class="text-end">Total</th>
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

  <!-- Financial Summary -->
  <div class="row justify-content-end mb-4">
    <div class="col-sm-6 col-md-5">
      <div class="p-3 bg-light rounded-3 border">
        <div class="d-flex justify-content-between py-1 small">
          <span class="text-muted">Subtotal:</span>
          <span class="mono-num fw-medium"><?= formatCurrency($quotation['subtotal'], $currency) ?></span>
        </div>
        <?php if ((float)$quotation['discount_total'] > 0): ?>
          <div class="d-flex justify-content-between py-1 small">
            <span class="text-muted">Discount:</span>
            <span class="mono-num text-danger">-<?= formatCurrency($quotation['discount_total'], $currency) ?></span>
          </div>
        <?php endif; ?>
        <?php if ((float)$quotation['tax_total'] > 0): ?>
          <div class="d-flex justify-content-between py-1 small">
            <span class="text-muted">Tax Amount:</span>
            <span class="mono-num"><?= formatCurrency($quotation['tax_total'], $currency) ?></span>
          </div>
        <?php endif; ?>
        <hr class="my-2">
        <div class="d-flex justify-content-between py-1">
          <span class="fw-bold text-dark">Estimated Total:</span>
          <span class="mono-num fw-bold fs-5 text-dark"><?= formatCurrency($quotation['grand_total'], $currency) ?></span>
        </div>
      </div>
    </div>
  </div>

  <!-- Notes & Terms -->
  <?php if (!empty($quotation['notes']) || !empty($quotation['terms'])): ?>
    <div class="row pt-3 border-top g-3">
      <?php if (!empty($quotation['notes'])): ?>
        <div class="col-md-6">
          <div class="text-muted small fw-bold text-uppercase mb-1">Notes:</div>
          <div class="text-muted small whitespace-pre-line"><?= nl2br(e($quotation['notes'])) ?></div>
        </div>
      <?php endif; ?>
      <?php if (!empty($quotation['terms'])): ?>
        <div class="col-md-6">
          <div class="text-muted small fw-bold text-uppercase mb-1">Terms & Conditions:</div>
          <div class="text-muted small whitespace-pre-line"><?= nl2br(e($quotation['terms'])) ?></div>
        </div>
      <?php endif; ?>
    </div>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
