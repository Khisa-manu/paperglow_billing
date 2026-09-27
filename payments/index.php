<?php
/**
 * PaperGlow Billing System - Payments Ledger
 */

declare(strict_types=1);

$pageTitle = 'Payments Ledger';
require_once __DIR__ . '/../includes/header.php';

$userId = getCurrentUserId();
$currency = $company['currency'] ?? 'USD';

$search = trim($_GET['q'] ?? '');
$methodFilter = trim($_GET['method'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 15;
$offset = ($page - 1) * $limit;

$where = ["p.user_id = ?"];
$params = [$userId];

if (!empty($methodFilter)) {
    $where[] = "p.payment_method = ?";
    $params[] = $methodFilter;
}

if (!empty($search)) {
    $where[] = "(p.reference_number LIKE ? OR i.invoice_number LIKE ? OR c.name LIKE ?)";
    $term = "%$search%";
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
}

$whereSql = implode(' AND ', $where);

// Total aggregate
$totStmt = $pdo->prepare("
    SELECT COUNT(p.id) as total_count, COALESCE(SUM(p.amount), 0) as total_collected
    FROM payments p
    JOIN invoices i ON p.invoice_id = i.id
    JOIN customers c ON i.customer_id = c.id
    WHERE $whereSql
");
$totStmt->execute($params);
$agg = $totStmt->fetch();
$totalRecords = (int)$agg['total_count'];
$totalCollected = (float)$agg['total_collected'];
$totalPages = max(1, (int)ceil($totalRecords / $limit));

// List
$sql = "
    SELECT p.*, i.invoice_number, c.name as customer_name, c.company as customer_company
    FROM payments p
    JOIN invoices i ON p.invoice_id = i.id
    JOIN customers c ON i.customer_id = c.id
    WHERE $whereSql
    ORDER BY p.payment_date DESC, p.id DESC
    LIMIT ? OFFSET ?
";
$stmt = $pdo->prepare($sql);
$paramIdx = 1;
foreach ($params as $val) {
    $stmt->bindValue($paramIdx++, $val);
}
$stmt->bindValue($paramIdx++, $limit, PDO::PARAM_INT);
$stmt->bindValue($paramIdx++, $offset, PDO::PARAM_INT);
$stmt->execute();
$payments = $stmt->fetchAll();
?>

<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
  <div>
    <h2 class="h5 fw-bold mb-1 text-slate-900">Payments & Receipts</h2>
    <p class="text-muted small mb-0">Record and track inbound collections across Bank, Mobile Money, and Card payments.</p>
  </div>
  <a href="/payments/create.php" class="btn btn-pg-primary d-inline-flex align-items-center gap-2">
    <i class="bi bi-wallet2"></i>
    <span>Record New Payment</span>
  </a>
</div>

<!-- Financial Highlight Banner -->
<div class="pg-card mb-4 bg-white">
  <div class="p-3 d-flex flex-wrap align-items-center justify-content-between gap-3">
    <div class="d-flex align-items-center gap-3">
      <div class="pg-stat-icon bg-success-subtle text-success">
        <i class="bi bi-cash-stack"></i>
      </div>
      <div>
        <span class="text-muted small text-uppercase fw-semibold">Filtered Collections</span>
        <div class="h4 fw-bold mono-num text-success mb-0"><?= formatCurrency($totalCollected, $currency) ?></div>
      </div>
    </div>
    <div class="text-muted small">
      Total Transactions: <strong><?= $totalRecords ?></strong> receipts
    </div>
  </div>
</div>

<div class="pg-card mb-4">
  <div class="p-3 border-bottom bg-white d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
    <form method="GET" action="/payments/index.php" class="d-flex flex-wrap gap-2 align-items-center">
      <div class="input-group input-group-sm" style="max-width: 280px;">
        <input type="text" name="q" class="form-control form-control-pg" value="<?= e($search) ?>" placeholder="Search ref #, invoice #, customer...">
        <button class="btn btn-outline-secondary" type="submit">
          <i class="bi bi-search"></i>
        </button>
      </div>

      <select name="method" class="form-select form-select-sm form-select-pg" style="max-width: 170px;" onchange="this.form.submit()">
        <option value="">All Methods</option>
        <?php foreach (['Bank Transfer', 'Card', 'Mobile Money', 'Cash', 'Other'] as $m): ?>
          <option value="<?= $m ?>" <?= $methodFilter === $m ? 'selected' : '' ?>><?= $m ?></option>
        <?php endforeach; ?>
      </select>

      <?php if (!empty($search) || !empty($methodFilter)): ?>
        <a href="/payments/index.php" class="btn btn-sm btn-outline-danger" title="Clear Filters">
          <i class="bi bi-x-lg"></i>
        </a>
      <?php endif; ?>
    </form>
  </div>

  <div class="table-responsive">
    <table class="table-pg">
      <thead>
        <tr>
          <th>Payment Date</th>
          <th>Applied Invoice</th>
          <th>Customer</th>
          <th>Method</th>
          <th>Reference #</th>
          <th>Notes</th>
          <th class="text-end">Amount Paid</th>
          <th class="text-end">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($payments)): ?>
          <tr>
            <td colspan="8" class="text-center py-5 text-muted">
              <i class="bi bi-wallet2 fs-2 d-block mb-2 text-slate-400"></i>
              No payments found matching this criteria.
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($payments as $pay): ?>
            <tr>
              <td class="text-muted small"><?= e(date('M d, Y', strtotime($pay['payment_date']))) ?></td>
              <td>
                <a href="/invoices/view.php?id=<?= (int)$pay['invoice_id'] ?>" class="fw-bold text-decoration-none mono-num" style="color: #d97706;">
                  <?= e($pay['invoice_number']) ?>
                </a>
              </td>
              <td>
                <div class="fw-medium text-slate-900"><?= e($pay['customer_name']) ?></div>
                <div class="text-muted small"><?= e($pay['customer_company'] ?: 'Individual') ?></div>
              </td>
              <td><span class="badge bg-light text-dark border"><?= e($pay['payment_method']) ?></span></td>
              <td class="mono-num small"><?= e($pay['reference_number'] ?: '—') ?></td>
              <td class="text-muted small text-truncate" style="max-width: 180px;"><?= e($pay['notes'] ?: '—') ?></td>
              <td class="text-end mono-num fw-bold text-success">+<?= formatCurrency($pay['amount'], $currency) ?></td>
              <td class="text-end">
                <a href="/payments/edit.php?id=<?= (int)$pay['id'] ?>" class="btn btn-sm btn-light border-0 me-1" title="Edit Payment">
                  <i class="bi bi-pencil"></i>
                </a>
                <a href="/payments/delete.php?id=<?= (int)$pay['id'] ?>&csrf_token=<?= e(getCsrfToken()) ?>" class="btn btn-sm btn-outline-danger border-0" data-confirm="Delete payment receipt of <?= formatCurrency($pay['amount'], $currency) ?>? The invoice balance will be adjusted automatically.">
                  <i class="bi bi-trash3"></i>
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <?php if ($totalPages > 1): ?>
    <div class="p-3 border-top d-flex justify-content-center">
      <nav>
        <ul class="pagination pagination-sm mb-0">
          <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <li class="page-item <?= $page === $i ? 'active' : '' ?>">
              <a class="page-link" href="?page=<?= $i ?><?= !empty($search) ? '&q=' . urlencode($search) : '' ?><?= !empty($methodFilter) ? '&method=' . urlencode($methodFilter) : '' ?>"><?= $i ?></a>
            </li>
          <?php endfor; ?>
        </ul>
      </nav>
    </div>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
