<?php
/**
 * PaperGlow Billing System - Financial Intelligence & Reports
 */

declare(strict_types=1);

$pageTitle = 'Financial Reports';
require_once __DIR__ . '/../includes/header.php';

$userId = getCurrentUserId();
$currency = $company['currency'] ?? 'USD';

$startDate = $_GET['start_date'] ?? date('Y-01-01');
$endDate = $_GET['end_date'] ?? date('Y-m-d');
$reportType = $_GET['type'] ?? 'sales';

// 1. Sales Summary for selected range
$stmtSales = $pdo->prepare("
    SELECT 
        COUNT(id) as total_invoices,
        COALESCE(SUM(grand_total), 0) as total_billed,
        COALESCE(SUM(paid_amount), 0) as total_collected,
        COALESCE(SUM(balance), 0) as total_receivable
    FROM invoices 
    WHERE user_id = ? AND invoice_date BETWEEN ? AND ?
");
$stmtSales->execute([$userId, $startDate, $endDate]);
$salesSummary = $stmtSales->fetch();

// 2. Outstanding Invoices Report
$stmtOutstanding = $pdo->prepare("
    SELECT i.*, c.name as customer_name, c.company as customer_company
    FROM invoices i
    JOIN customers c ON i.customer_id = c.id
    WHERE i.user_id = ? AND i.balance > 0
    ORDER BY i.due_date ASC
");
$stmtOutstanding->execute([$userId]);
$outstandingInvoices = $stmtOutstanding->fetchAll();

// 3. Payment Collections in range
$stmtPay = $pdo->prepare("
    SELECT p.*, i.invoice_number, c.name as customer_name
    FROM payments p
    JOIN invoices i ON p.invoice_id = i.id
    JOIN customers c ON i.customer_id = c.id
    WHERE p.user_id = ? AND p.payment_date BETWEEN ? AND ?
    ORDER BY p.payment_date DESC
");
$stmtPay->execute([$userId, $startDate, $endDate]);
$periodPayments = $stmtPay->fetchAll();

// 4. Quotation Performance
$stmtQuo = $pdo->prepare("
    SELECT 
        COUNT(id) as total_quotes,
        COALESCE(SUM(grand_total), 0) as total_quote_value,
        SUM(CASE WHEN status = 'Accepted' THEN 1 ELSE 0 END) as accepted_count,
        SUM(CASE WHEN status = 'Accepted' THEN grand_total ELSE 0 END) as accepted_value
    FROM quotations
    WHERE user_id = ? AND quotation_date BETWEEN ? AND ?
");
$stmtQuo->execute([$userId, $startDate, $endDate]);
$quotationReport = $stmtQuo->fetch();
?>

<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
  <div>
    <h2 class="h5 fw-bold mb-1 text-slate-900">Financial Reports & Auditing</h2>
    <p class="text-muted small mb-0">Detailed breakdown of receivables, closed collections, and quotation performance.</p>
  </div>
  <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.print()">
    <i class="bi bi-printer me-1"></i> Print Report
  </button>
</div>

<!-- Date Filter Form -->
<div class="pg-card mb-4">
  <div class="p-3 bg-white">
    <form method="GET" action="/reports/index.php" class="row g-2 align-items-center">
      <div class="col-md-3 col-sm-6">
        <label class="form-label-pg mb-1">From Date</label>
        <input type="date" name="start_date" class="form-control form-control-sm form-control-pg" value="<?= e($startDate) ?>">
      </div>
      <div class="col-md-3 col-sm-6">
        <label class="form-label-pg mb-1">To Date</label>
        <input type="date" name="end_date" class="form-control form-control-sm form-control-pg" value="<?= e($endDate) ?>">
      </div>
      <div class="col-md-3 col-sm-6">
        <label class="form-label-pg mb-1">Report View</label>
        <select name="type" class="form-select form-select-sm form-select-pg">
          <option value="sales" <?= $reportType === 'sales' ? 'selected' : '' ?>>Sales & Collections</option>
          <option value="outstanding" <?= $reportType === 'outstanding' ? 'selected' : '' ?>>Outstanding Receivables</option>
          <option value="quotations" <?= $reportType === 'quotations' ? 'selected' : '' ?>>Quotation Conversion</option>
        </select>
      </div>
      <div class="col-md-3 col-sm-6 pt-3">
        <button type="submit" class="btn btn-pg-primary btn-sm w-100">
          <i class="bi bi-funnel me-1"></i> Generate Report
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Period Metric Cards -->
<div class="row g-3 mb-4">
  <div class="col-sm-6 col-xl-3">
    <div class="pg-stat-card">
      <span class="text-muted small fw-semibold text-uppercase">Billed (Period)</span>
      <div class="h4 fw-bold mono-num text-slate-900 mt-1"><?= formatCurrency($salesSummary['total_billed'], $currency) ?></div>
      <div class="text-muted small"><?= (int)$salesSummary['total_invoices'] ?> invoices issued</div>
    </div>
  </div>
  <div class="col-sm-6 col-xl-3">
    <div class="pg-stat-card">
      <span class="text-muted small fw-semibold text-uppercase">Collected (Period)</span>
      <div class="h4 fw-bold mono-num text-success mt-1"><?= formatCurrency($salesSummary['total_collected'], $currency) ?></div>
      <div class="text-muted small">Cleared deposits</div>
    </div>
  </div>
  <div class="col-sm-6 col-xl-3">
    <div class="pg-stat-card">
      <span class="text-muted small fw-semibold text-uppercase">Open Period Balance</span>
      <div class="h4 fw-bold mono-num text-warning mt-1" style="color: #d97706 !important;"><?= formatCurrency($salesSummary['total_receivable'], $currency) ?></div>
      <div class="text-muted small">Period pending balance</div>
    </div>
  </div>
  <div class="col-sm-6 col-xl-3">
    <div class="pg-stat-card">
      <span class="text-muted small fw-semibold text-uppercase">Quote Win Rate</span>
      <?php 
        $winRate = (int)$quotationReport['total_quotes'] > 0 
          ? round(((int)$quotationReport['accepted_count'] / (int)$quotationReport['total_quotes']) * 100) 
          : 0;
      ?>
      <div class="h4 fw-bold mono-num text-slate-900 mt-1"><?= $winRate ?>%</div>
      <div class="text-muted small"><?= (int)$quotationReport['accepted_count'] ?> of <?= (int)$quotationReport['total_quotes'] ?> quotes won</div>
    </div>
  </div>
</div>

<!-- Outstanding Invoices Audit Section -->
<div class="pg-card mb-4">
  <div class="pg-card-header">
    <div>
      <h3 class="h6 fw-bold mb-0 text-slate-900">Current Outstanding Receivables Ledger</h3>
      <span class="text-muted small">All active invoices with pending client balances</span>
    </div>
  </div>
  <div class="table-responsive">
    <table class="table-pg">
      <thead>
        <tr>
          <th>Invoice #</th>
          <th>Customer</th>
          <th>Issue Date</th>
          <th>Due Date</th>
          <th class="text-end">Grand Total</th>
          <th class="text-end">Paid Amount</th>
          <th class="text-end">Open Balance</th>
          <th class="text-center">Status</th>
          <th class="text-end">Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($outstandingInvoices)): ?>
          <tr>
            <td colspan="9" class="text-center py-4 text-muted small">No outstanding receivables found. All invoices are fully settled!</td>
          </tr>
        <?php else: ?>
          <?php foreach ($outstandingInvoices as $out): ?>
            <tr>
              <td>
                <a href="/invoices/view.php?id=<?= (int)$out['id'] ?>" class="fw-bold text-decoration-none mono-num" style="color: #d97706;">
                  <?= e($out['invoice_number']) ?>
                </a>
              </td>
              <td>
                <div class="fw-semibold text-dark"><?= e($out['customer_name']) ?></div>
                <div class="text-muted small"><?= e($out['customer_company'] ?: 'Individual') ?></div>
              </td>
              <td class="text-muted small"><?= e(date('M d, Y', strtotime($out['invoice_date']))) ?></td>
              <td class="text-muted small">
                <span class="<?= ($out['due_date'] < date('Y-m-d')) ? 'text-danger fw-bold' : '' ?>">
                  <?= e(date('M d, Y', strtotime($out['due_date']))) ?>
                </span>
              </td>
              <td class="text-end mono-num"><?= formatCurrency($out['grand_total'], $currency) ?></td>
              <td class="text-end mono-num text-success"><?= formatCurrency($out['paid_amount'], $currency) ?></td>
              <td class="text-end mono-num fw-bold text-danger"><?= formatCurrency($out['balance'], $currency) ?></td>
              <td class="text-center"><?= getStatusBadge($out['status']) ?></td>
              <td class="text-end">
                <a href="/payments/create.php?invoice_id=<?= (int)$out['id'] ?>" class="btn btn-sm btn-outline-success">
                  Collect
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Collections in Period -->
<div class="pg-card">
  <div class="pg-card-header">
    <div>
      <h3 class="h6 fw-bold mb-0 text-slate-900">Payment Collections (<?= e($startDate) ?> to <?= e($endDate) ?>)</h3>
      <span class="text-muted small">Recorded deposits and transactions</span>
    </div>
  </div>
  <div class="table-responsive">
    <table class="table-pg">
      <thead>
        <tr>
          <th>Date</th>
          <th>Invoice #</th>
          <th>Customer</th>
          <th>Method</th>
          <th>Reference #</th>
          <th class="text-end">Collected Amount</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($periodPayments)): ?>
          <tr>
            <td colspan="6" class="text-center py-4 text-muted small">No payments recorded within the specified date range.</td>
          </tr>
        <?php else: ?>
          <?php foreach ($periodPayments as $p): ?>
            <tr>
              <td class="text-muted small"><?= e(date('M d, Y', strtotime($p['payment_date']))) ?></td>
              <td><span class="mono-num fw-medium text-dark"><?= e($p['invoice_number']) ?></span></td>
              <td><?= e($p['customer_name']) ?></td>
              <td><span class="badge bg-light text-dark border"><?= e($p['payment_method']) ?></span></td>
              <td class="mono-num small"><?= e($p['reference_number'] ?: '—') ?></td>
              <td class="text-end mono-num fw-bold text-success">+<?= formatCurrency($p['amount'], $currency) ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
