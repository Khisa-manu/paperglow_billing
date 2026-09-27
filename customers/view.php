<?php
/**
 * PaperGlow Billing System - View Customer Profile & Statement
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
requireAuth();

$userId = getCurrentUserId();
$pdo = getDBConnection();
$customerId = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ? AND user_id = ?");
$stmt->execute([$customerId, $userId]);
$customer = $stmt->fetch();

if (!$customer) {
    setFlash('danger', 'Customer not found.');
    header('Location: /customers/index.php');
    exit;
}

$currency = $company['currency'] ?? 'USD';

// Financial statement aggregates
$stmtStats = $pdo->prepare("
    SELECT 
        COUNT(id) as total_invoices,
        COALESCE(SUM(grand_total), 0) as total_billed,
        COALESCE(SUM(paid_amount), 0) as total_paid,
        COALESCE(SUM(balance), 0) as total_balance
    FROM invoices 
    WHERE customer_id = ? AND user_id = ?
");
$stmtStats->execute([$customerId, $userId]);
$stats = $stmtStats->fetch();

// Customer Invoices
$stmtInv = $pdo->prepare("SELECT * FROM invoices WHERE customer_id = ? AND user_id = ? ORDER BY invoice_date DESC, id DESC");
$stmtInv->execute([$customerId, $userId]);
$invoices = $stmtInv->fetchAll();

// Customer Quotations
$stmtQuo = $pdo->prepare("SELECT * FROM quotations WHERE customer_id = ? AND user_id = ? ORDER BY quotation_date DESC, id DESC");
$stmtQuo->execute([$customerId, $userId]);
$quotations = $stmtQuo->fetchAll();

$pageTitle = 'Customer Profile — ' . $customer['name'];
require_once __DIR__ . '/../includes/header.php';
?>

<div class="mb-4 d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
  <div>
    <a href="/customers/index.php" class="text-decoration-none text-muted small d-inline-flex align-items-center gap-1 mb-2">
      <i class="bi bi-arrow-left"></i> Back to Customers
    </a>
    <div class="d-flex align-items-center gap-3">
      <div class="pg-stat-icon bg-warning text-dark fw-bold fs-4">
        <?= strtoupper(substr($customer['name'], 0, 1)) ?>
      </div>
      <div>
        <h2 class="h5 fw-bold text-slate-900 mb-0"><?= e($customer['name']) ?></h2>
        <span class="text-muted small"><?= e($customer['company'] ?: 'Individual Account') ?></span>
      </div>
    </div>
  </div>

  <div class="d-flex align-items-center gap-2">
    <a href="/quotations/create.php?customer_id=<?= $customerId ?>" class="btn btn-pg-secondary btn-sm">
      <i class="bi bi-file-earmark-plus me-1"></i> New Quote
    </a>
    <a href="/invoices/create.php?customer_id=<?= $customerId ?>" class="btn btn-pg-primary btn-sm">
      <i class="bi bi-receipt me-1"></i> New Invoice
    </a>
    <a href="/customers/edit.php?id=<?= $customerId ?>" class="btn btn-outline-secondary btn-sm">
      <i class="bi bi-pencil me-1"></i> Edit
    </a>
  </div>
</div>

<!-- Statement Cards -->
<div class="row g-3 mb-4">
  <div class="col-md-4">
    <div class="pg-stat-card">
      <span class="text-muted small fw-semibold text-uppercase">Total Billed</span>
      <div class="h4 fw-bold mono-num text-slate-900 mt-1"><?= formatCurrency($stats['total_billed'], $currency) ?></div>
      <div class="text-muted small"><?= (int)$stats['total_invoices'] ?> invoices issued</div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="pg-stat-card">
      <span class="text-muted small fw-semibold text-uppercase">Total Payments Received</span>
      <div class="h4 fw-bold mono-num text-success mt-1"><?= formatCurrency($stats['total_paid'], $currency) ?></div>
      <div class="text-muted small">Cleared receipts</div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="pg-stat-card">
      <span class="text-muted small fw-semibold text-uppercase">Outstanding Balance</span>
      <div class="h4 fw-bold mono-num text-danger mt-1"><?= formatCurrency($stats['total_balance'], $currency) ?></div>
      <div class="text-muted small">Remaining open receivables</div>
    </div>
  </div>
</div>

<div class="row g-4">
  <!-- Left Side: Profile Details -->
  <div class="col-lg-4">
    <div class="pg-card mb-4">
      <div class="pg-card-header">
        <h3 class="h6 fw-bold mb-0 text-slate-900">Billing Information</h3>
      </div>
      <div class="pg-card-body p-3">
        <div class="mb-3">
          <div class="text-muted small">Billing Email</div>
          <div class="fw-semibold text-dark"><?= e($customer['email'] ?: 'Not provided') ?></div>
        </div>
        <div class="mb-3">
          <div class="text-muted small">Phone Number</div>
          <div class="fw-semibold text-dark"><?= e($customer['phone'] ?: 'Not provided') ?></div>
        </div>
        <div class="mb-3">
          <div class="text-muted small">Tax / VAT ID</div>
          <div class="fw-semibold text-dark"><?= e($customer['tax_number'] ?: 'Not registered') ?></div>
        </div>
        <div class="mb-3">
          <div class="text-muted small">Billing Address</div>
          <div class="text-dark small whitespace-pre-line"><?= nl2br(e($customer['address'] ?: 'No address specified.')) ?></div>
        </div>
        <?php if (!empty($customer['notes'])): ?>
          <div class="pt-3 border-top">
            <div class="text-muted small">Internal Notes</div>
            <div class="text-muted small italic"><?= nl2br(e($customer['notes'])) ?></div>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Right Side: Invoice & Quotation History -->
  <div class="col-lg-8">
    <!-- Invoice History -->
    <div class="pg-card mb-4">
      <div class="pg-card-header">
        <h3 class="h6 fw-bold mb-0 text-slate-900">Invoice History</h3>
        <span class="badge bg-light text-dark border"><?= count($invoices) ?> records</span>
      </div>
      <div class="table-responsive">
        <table class="table-pg">
          <thead>
            <tr>
              <th>Invoice #</th>
              <th>Date</th>
              <th>Due Date</th>
              <th class="text-end">Total</th>
              <th class="text-end">Balance</th>
              <th class="text-center">Status</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($invoices)): ?>
              <tr>
                <td colspan="6" class="text-center py-4 text-muted small">No invoices billed to this customer yet.</td>
              </tr>
            <?php else: ?>
              <?php foreach ($invoices as $inv): ?>
                <tr>
                  <td>
                    <a href="/invoices/view.php?id=<?= (int)$inv['id'] ?>" class="fw-bold text-decoration-none mono-num" style="color: #d97706;">
                      <?= e($inv['invoice_number']) ?>
                    </a>
                  </td>
                  <td class="text-muted small"><?= e(date('M d, Y', strtotime($inv['invoice_date']))) ?></td>
                  <td class="text-muted small"><?= e(date('M d, Y', strtotime($inv['due_date']))) ?></td>
                  <td class="text-end fw-semibold mono-num"><?= formatCurrency($inv['grand_total'], $currency) ?></td>
                  <td class="text-end mono-num text-danger"><?= formatCurrency($inv['balance'], $currency) ?></td>
                  <td class="text-center"><?= getStatusBadge($inv['status']) ?></td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Quotation History -->
    <div class="pg-card">
      <div class="pg-card-header">
        <h3 class="h6 fw-bold mb-0 text-slate-900">Quotation History</h3>
        <span class="badge bg-light text-dark border"><?= count($quotations) ?> proposals</span>
      </div>
      <div class="table-responsive">
        <table class="table-pg">
          <thead>
            <tr>
              <th>Quote #</th>
              <th>Date</th>
              <th>Expiry</th>
              <th class="text-end">Grand Total</th>
              <th class="text-center">Status</th>
              <th class="text-end">Action</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($quotations)): ?>
              <tr>
                <td colspan="6" class="text-center py-4 text-muted small">No quotations prepared for this customer yet.</td>
              </tr>
            <?php else: ?>
              <?php foreach ($quotations as $quo): ?>
                <tr>
                  <td>
                    <a href="/quotations/view.php?id=<?= (int)$quo['id'] ?>" class="fw-bold text-decoration-none mono-num text-dark">
                      <?= e($quo['quotation_number']) ?>
                    </a>
                  </td>
                  <td class="text-muted small"><?= e(date('M d, Y', strtotime($quo['quotation_date']))) ?></td>
                  <td class="text-muted small"><?= e(date('M d, Y', strtotime($quo['expiry_date']))) ?></td>
                  <td class="text-end fw-semibold mono-num"><?= formatCurrency($quo['grand_total'], $currency) ?></td>
                  <td class="text-center"><?= getStatusBadge($quo['status']) ?></td>
                  <td class="text-end">
                    <a href="/quotations/view.php?id=<?= (int)$quo['id'] ?>" class="btn btn-sm btn-light border-0">
                      <i class="bi bi-chevron-right"></i>
                    </a>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
