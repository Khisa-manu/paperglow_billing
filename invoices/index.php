<?php
/**
 * PaperGlow Billing System - Invoices Directory
 */

declare(strict_types=1);

$pageTitle = 'Invoices';
require_once __DIR__ . '/../includes/header.php';

$userId = getCurrentUserId();
$currency = $company['currency'] ?? 'USD';

$search = trim($_GET['q'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

$where = ["i.user_id = ?"];
$params = [$userId];

if (!empty($statusFilter)) {
    $where[] = "i.status = ?";
    $params[] = $statusFilter;
}

if (!empty($search)) {
    $where[] = "(i.invoice_number LIKE ? OR c.name LIKE ? OR c.company LIKE ?)";
    $term = "%$search%";
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
}

$whereSql = implode(' AND ', $where);

// Count
$countStmt = $pdo->prepare("
    SELECT COUNT(i.id) 
    FROM invoices i
    JOIN customers c ON i.customer_id = c.id
    WHERE $whereSql
");
$countStmt->execute($params);
$totalRecords = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($totalRecords / $limit));

// List
$sql = "
    SELECT i.*, c.name as customer_name, c.company as customer_company
    FROM invoices i
    JOIN customers c ON i.customer_id = c.id
    WHERE $whereSql
    ORDER BY i.invoice_date DESC, i.id DESC
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
$invoices = $stmt->fetchAll();
?>

<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
  <div>
    <h2 class="h5 fw-bold mb-1 text-slate-900">Invoices & Billings</h2>
    <p class="text-muted small mb-0">Track accounts receivable, payment collections, and overdue receivables.</p>
  </div>
  <a href="/invoices/create.php" class="btn btn-pg-primary d-inline-flex align-items-center gap-2">
    <i class="bi bi-receipt"></i>
    <span>Create Invoice</span>
  </a>
</div>

<div class="pg-card mb-4">
  <div class="p-3 border-bottom bg-white d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
    <form method="GET" action="/invoices/index.php" class="d-flex flex-wrap gap-2 align-items-center">
      <div class="input-group input-group-sm" style="max-width: 260px;">
        <input type="text" name="q" class="form-control form-control-pg" value="<?= e($search) ?>" placeholder="Search invoice # or customer...">
        <button class="btn btn-outline-secondary" type="submit">
          <i class="bi bi-search"></i>
        </button>
      </div>

      <select name="status" class="form-select form-select-sm form-select-pg" style="max-width: 170px;" onchange="this.form.submit()">
        <option value="">All Statuses</option>
        <?php foreach (['Draft', 'Unpaid', 'Partially Paid', 'Paid', 'Overdue', 'Cancelled'] as $st): ?>
          <option value="<?= $st ?>" <?= $statusFilter === $st ? 'selected' : '' ?>><?= $st ?></option>
        <?php endforeach; ?>
      </select>

      <?php if (!empty($search) || !empty($statusFilter)): ?>
        <a href="/invoices/index.php" class="btn btn-sm btn-outline-danger" title="Clear Filters">
          <i class="bi bi-x-lg"></i>
        </a>
      <?php endif; ?>
    </form>

    <div class="text-muted small">
      Showing <strong><?= count($invoices) ?></strong> of <strong><?= $totalRecords ?></strong> invoices
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
          <th class="text-end">Paid</th>
          <th class="text-end">Balance</th>
          <th class="text-center">Status</th>
          <th class="text-end">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($invoices)): ?>
          <tr>
            <td colspan="9" class="text-center py-5 text-muted">
              <i class="bi bi-receipt fs-2 d-block mb-2 text-slate-400"></i>
              No invoices match your filter.
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($invoices as $inv): ?>
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
              <td class="text-muted small">
                <?php 
                  $isLate = ($inv['due_date'] < date('Y-m-d')) && ((float)$inv['balance'] > 0);
                ?>
                <span class="<?= $isLate ? 'text-danger fw-semibold' : '' ?>">
                  <?= e(date('M d, Y', strtotime($inv['due_date']))) ?>
                </span>
              </td>
              <td class="text-end mono-num fw-semibold"><?= formatCurrency($inv['grand_total'], $currency) ?></td>
              <td class="text-end mono-num text-success"><?= formatCurrency($inv['paid_amount'], $currency) ?></td>
              <td class="text-end mono-num fw-semibold <?= (float)$inv['balance'] > 0 ? 'text-danger' : 'text-muted' ?>">
                <?= formatCurrency($inv['balance'], $currency) ?>
              </td>
              <td class="text-center"><?= getStatusBadge($inv['status']) ?></td>
              <td class="text-end">
                <div class="dropdown">
                  <button class="btn btn-sm btn-light border-0" type="button" data-bs-toggle="dropdown">
                    <i class="bi bi-three-dots-vertical"></i>
                  </button>
                  <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                    <li><a class="dropdown-item" href="/invoices/view.php?id=<?= (int)$inv['id'] ?>"><i class="bi bi-eye me-2"></i>View Invoice</a></li>
                    <?php if ((float)$inv['balance'] > 0): ?>
                      <li><a class="dropdown-item text-success fw-medium" href="/payments/create.php?invoice_id=<?= (int)$inv['id'] ?>"><i class="bi bi-wallet2 me-2"></i>Record Payment</a></li>
                    <?php endif; ?>
                    <li><a class="dropdown-item" href="/invoices/edit.php?id=<?= (int)$inv['id'] ?>"><i class="bi bi-pencil me-2"></i>Edit Invoice</a></li>
                    <li><a class="dropdown-item" href="/invoices/pdf.php?id=<?= (int)$inv['id'] ?>" target="_blank"><i class="bi bi-file-earmark-pdf me-2 text-danger"></i>Download PDF</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                      <a class="dropdown-item text-danger" href="/invoices/delete.php?id=<?= (int)$inv['id'] ?>&csrf_token=<?= e(getCsrfToken()) ?>" data-confirm="Delete invoice <?= e($inv['invoice_number']) ?>?">
                        <i class="bi bi-trash3 me-2"></i>Delete
                      </a>
                    </li>
                  </ul>
                </div>
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
              <a class="page-link" href="?page=<?= $i ?><?= !empty($search) ? '&q=' . urlencode($search) : '' ?><?= !empty($statusFilter) ? '&status=' . urlencode($statusFilter) : '' ?>"><?= $i ?></a>
            </li>
          <?php endfor; ?>
        </ul>
      </nav>
    </div>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
