<?php
/**
 * PaperGlow Billing System - Executive Dashboard
 */

declare(strict_types=1);

$pageTitle = 'Executive Dashboard';
require_once __DIR__ . '/../includes/header.php';

$userId = getCurrentUserId();
$currency = $company['currency'] ?? 'USD';

// 1. KPI Statistics from database
// Total Invoices & Financial Totals
$stmtInvStats = $pdo->prepare("
    SELECT 
        COUNT(id) as total_invoices,
        COALESCE(SUM(grand_total), 0) as total_value,
        COALESCE(SUM(paid_amount), 0) as total_paid,
        COALESCE(SUM(balance), 0) as total_outstanding,
        SUM(CASE WHEN status = 'Paid' THEN 1 ELSE 0 END) as count_paid,
        SUM(CASE WHEN status = 'Unpaid' THEN 1 ELSE 0 END) as count_unpaid,
        SUM(CASE WHEN status = 'Partially Paid' THEN 1 ELSE 0 END) as count_partial,
        SUM(CASE WHEN status = 'Overdue' THEN 1 ELSE 0 END) as count_overdue
    FROM invoices 
    WHERE user_id = ?
");
$stmtInvStats->execute([$userId]);
$invStats = $stmtInvStats->fetch();

// Total Quotations & Acceptance
$stmtQuoStats = $pdo->prepare("
    SELECT 
        COUNT(id) as total_quotations,
        COALESCE(SUM(grand_total), 0) as total_quotations_value,
        SUM(CASE WHEN status = 'Accepted' THEN 1 ELSE 0 END) as count_accepted,
        SUM(CASE WHEN status = 'Sent' THEN 1 ELSE 0 END) as count_sent,
        SUM(CASE WHEN status = 'Draft' THEN 1 ELSE 0 END) as count_draft
    FROM quotations 
    WHERE user_id = ?
");
$stmtQuoStats->execute([$userId]);
$quoStats = $stmtQuoStats->fetch();

// Total Customers
$stmtCust = $pdo->prepare("SELECT COUNT(id) FROM customers WHERE user_id = ?");
$stmtCust->execute([$userId]);
$totalCustomers = (int)$stmtCust->fetchColumn();

// 2. Recent Invoices
$stmtRecentInv = $pdo->prepare("
    SELECT i.*, c.name as customer_name, c.company as customer_company 
    FROM invoices i
    JOIN customers c ON i.customer_id = c.id
    WHERE i.user_id = ?
    ORDER BY i.created_at DESC
    LIMIT 5
");
$stmtRecentInv->execute([$userId]);
$recentInvoices = $stmtRecentInv->fetchAll();

// 3. Recent Quotations
$stmtRecentQuo = $pdo->prepare("
    SELECT q.*, c.name as customer_name, c.company as customer_company 
    FROM quotations q
    JOIN customers c ON q.customer_id = c.id
    WHERE q.user_id = ?
    ORDER BY q.created_at DESC
    LIMIT 5
");
$stmtRecentQuo->execute([$userId]);
$recentQuotations = $stmtRecentQuo->fetchAll();

// 4. Recent Payments
$stmtRecentPay = $pdo->prepare("
    SELECT p.*, i.invoice_number, c.name as customer_name 
    FROM payments p
    JOIN invoices i ON p.invoice_id = i.id
    JOIN customers c ON i.customer_id = c.id
    WHERE p.user_id = ?
    ORDER BY p.payment_date DESC, p.id DESC
    LIMIT 5
");
$stmtRecentPay->execute([$userId]);
$recentPayments = $stmtRecentPay->fetchAll();
?>

<!-- PaperGlow Stat Cards Grid -->
<div class="row g-3 mb-4">
  <div class="col-xl-3 col-sm-6">
    <div class="pg-stat-card">
      <div class="d-flex align-items-center justify-content-between mb-2">
        <span class="text-muted small fw-semibold text-uppercase letter-spacing">Total Invoices</span>
        <div class="pg-stat-icon" style="background: rgba(15, 23, 42, 0.06); color: #0f172a;">
          <i class="bi bi-receipt"></i>
        </div>
      </div>
      <div class="h3 fw-bold mb-1 text-slate-900"><?= (int)$invStats['total_invoices'] ?></div>
      <div class="small text-muted d-flex align-items-center justify-content-between">
        <span>Lifetime value</span>
        <span class="mono-num fw-semibold text-dark"><?= formatCurrency($invStats['total_value'], $currency) ?></span>
      </div>
    </div>
  </div>

  <div class="col-xl-3 col-sm-6">
    <div class="pg-stat-card">
      <div class="d-flex align-items-center justify-content-between mb-2">
        <span class="text-muted small fw-semibold text-uppercase letter-spacing">Collected Paid</span>
        <div class="pg-stat-icon" style="background: rgba(16, 185, 129, 0.12); color: #10b981;">
          <i class="bi bi-check2-circle"></i>
        </div>
      </div>
      <div class="h3 fw-bold mb-1 text-success mono-num"><?= formatCurrency($invStats['total_paid'], $currency) ?></div>
      <div class="small text-muted d-flex align-items-center justify-content-between">
        <span>Paid invoices</span>
        <span class="badge bg-success-subtle text-success border border-success-subtle"><?= (int)$invStats['count_paid'] ?> cleared</span>
      </div>
    </div>
  </div>

  <div class="col-xl-3 col-sm-6">
    <div class="pg-stat-card">
      <div class="d-flex align-items-center justify-content-between mb-2">
        <span class="text-muted small fw-semibold text-uppercase letter-spacing">Outstanding Balance</span>
        <div class="pg-stat-icon" style="background: rgba(245, 158, 11, 0.15); color: #d97706;">
          <i class="bi bi-hourglass-split"></i>
        </div>
      </div>
      <div class="h3 fw-bold mb-1 text-warning mono-num" style="color: #d97706 !important;"><?= formatCurrency($invStats['total_outstanding'], $currency) ?></div>
      <div class="small text-muted d-flex align-items-center justify-content-between">
        <span>Pending collection</span>
        <span class="badge bg-warning-subtle text-warning border border-warning-subtle"><?= (int)$invStats['count_unpaid'] + (int)$invStats['count_partial'] ?> pending</span>
      </div>
    </div>
  </div>

  <div class="col-xl-3 col-sm-6">
    <div class="pg-stat-card">
      <div class="d-flex align-items-center justify-content-between mb-2">
        <span class="text-muted small fw-semibold text-uppercase letter-spacing">Total Quotations</span>
        <div class="pg-stat-icon" style="background: rgba(59, 130, 246, 0.12); color: #3b82f6;">
          <i class="bi bi-file-earmark-check"></i>
        </div>
      </div>
      <div class="h3 fw-bold mb-1 text-slate-900"><?= (int)$quoStats['total_quotations'] ?></div>
      <div class="small text-muted d-flex align-items-center justify-content-between">
        <span>Accepted proposals</span>
        <span class="badge bg-primary-subtle text-primary border border-primary-subtle"><?= (int)$quoStats['count_accepted'] ?> accepted</span>
      </div>
    </div>
  </div>
</div>

<!-- Secondary Actions Banner -->
<div class="pg-card mb-4 bg-white">
  <div class="pg-card-body d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
    <div class="d-flex align-items-center gap-3">
      <div class="p-3 rounded-3" style="background: var(--pg-amber-soft); color: var(--pg-amber-600);">
        <i class="bi bi-lightning-charge-fill fs-3"></i>
      </div>
      <div>
        <h5 class="fw-bold mb-1 text-slate-900">PaperGlow Billing Studio is Active</h5>
        <p class="text-muted small mb-0">Generate professional quotations, issue compliant invoices, and log customer receipts in real time.</p>
      </div>
    </div>
    <div class="d-flex align-items-center gap-2">
      <a href="/customers/create.php" class="btn btn-pg-secondary btn-sm">
        <i class="bi bi-person-plus me-1"></i> Add Customer
      </a>
      <a href="/quotations/create.php" class="btn btn-pg-secondary btn-sm">
        <i class="bi bi-file-earmark-plus me-1"></i> New Quotation
      </a>
      <a href="/invoices/create.php" class="btn btn-pg-primary btn-sm">
        <i class="bi bi-receipt me-1"></i> New Invoice
      </a>
    </div>
  </div>
</div>

<div class="row g-4">
  <!-- Recent Invoices Table -->
  <div class="col-lg-7">
    <div class="pg-card h-100">
      <div class="pg-card-header">
        <div>
          <h2 class="h6 fw-bold mb-0 text-slate-900">Recent Invoices</h2>
          <span class="text-muted small">Latest issued customer billings</span>
        </div>
        <a href="/invoices/index.php" class="btn btn-sm btn-outline-secondary">View All</a>
      </div>
      <div class="table-responsive">
        <table class="table-pg">
          <thead>
            <tr>
              <th>Invoice #</th>
              <th>Customer</th>
              <th>Date</th>
              <th class="text-end">Amount</th>
              <th class="text-center">Status</th>
              <th class="text-end">Action</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($recentInvoices)): ?>
              <tr>
                <td colspan="6" class="text-center py-4 text-muted small">No invoices recorded yet. Click "New Invoice" to create one.</td>
              </tr>
            <?php else: ?>
              <?php foreach ($recentInvoices as $inv): ?>
                <tr>
                  <td>
                    <a href="/invoices/view.php?id=<?= (int)$inv['id'] ?>" class="fw-bold text-decoration-none mono-num" style="color: #d97706;">
                      <?= e($inv['invoice_number']) ?>
                    </a>
                  </td>
                  <td>
                    <div class="fw-medium text-slate-900"><?= e($inv['customer_name']) ?></div>
                    <div class="text-muted small"><?= e($inv['customer_company'] ?: 'Individual') ?></div>
                  </td>
                  <td class="text-muted small"><?= e(date('M d, Y', strtotime($inv['invoice_date']))) ?></td>
                  <td class="text-end fw-semibold mono-num"><?= formatCurrency($inv['grand_total'], $currency) ?></td>
                  <td class="text-center"><?= getStatusBadge($inv['status']) ?></td>
                  <td class="text-end">
                    <a href="/invoices/view.php?id=<?= (int)$inv['id'] ?>" class="btn btn-sm btn-light border-0" title="View Details">
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

  <!-- Recent Quotations & Quick Payments -->
  <div class="col-lg-5">
    <!-- Recent Quotations -->
    <div class="pg-card mb-4">
      <div class="pg-card-header">
        <div>
          <h2 class="h6 fw-bold mb-0 text-slate-900">Recent Quotations</h2>
          <span class="text-muted small">Proposals pending conversion</span>
        </div>
        <a href="/quotations/index.php" class="btn btn-sm btn-outline-secondary">View All</a>
      </div>
      <div class="table-responsive">
        <table class="table-pg">
          <thead>
            <tr>
              <th>Quote #</th>
              <th>Customer</th>
              <th class="text-end">Amount</th>
              <th class="text-center">Status</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($recentQuotations)): ?>
              <tr>
                <td colspan="4" class="text-center py-3 text-muted small">No quotations recorded.</td>
              </tr>
            <?php else: ?>
              <?php foreach ($recentQuotations as $quo): ?>
                <tr>
                  <td>
                    <a href="/quotations/view.php?id=<?= (int)$quo['id'] ?>" class="fw-bold text-decoration-none mono-num text-dark">
                      <?= e($quo['quotation_number']) ?>
                    </a>
                  </td>
                  <td>
                    <div class="text-truncate" style="max-width: 120px;"><?= e($quo['customer_name']) ?></div>
                  </td>
                  <td class="text-end mono-num fw-medium small"><?= formatCurrency($quo['grand_total'], $currency) ?></td>
                  <td class="text-center"><?= getStatusBadge($quo['status']) ?></td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Recent Payments -->
    <div class="pg-card">
      <div class="pg-card-header">
        <div>
          <h2 class="h6 fw-bold mb-0 text-slate-900">Recent Payments</h2>
          <span class="text-muted small">Incoming funds allocation</span>
        </div>
        <a href="/payments/index.php" class="btn btn-sm btn-outline-secondary">View All</a>
      </div>
      <div class="p-3">
        <?php if (empty($recentPayments)): ?>
          <div class="text-center py-3 text-muted small">No payments recorded yet.</div>
        <?php else: ?>
          <div class="list-group list-group-flush">
            <?php foreach ($recentPayments as $pay): ?>
              <div class="list-group-item px-0 py-2 border-bottom d-flex align-items-center justify-content-between">
                <div>
                  <div class="fw-medium text-dark small"><?= e($pay['customer_name']) ?></div>
                  <div class="text-muted small">
                    <span class="mono-num"><?= e($pay['invoice_number']) ?></span> &bull; <?= e($pay['payment_method']) ?>
                  </div>
                </div>
                <div class="text-end">
                  <div class="text-success fw-bold mono-num small">+<?= formatCurrency($pay['amount'], $currency) ?></div>
                  <div class="text-muted small" style="font-size: 0.72rem;"><?= e(date('M d', strtotime($pay['payment_date']))) ?></div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
