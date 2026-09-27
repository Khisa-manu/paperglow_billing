<?php
/**
 * PaperGlow Billing System - Quotations Directory
 */

declare(strict_types=1);

$pageTitle = 'Quotations';
require_once __DIR__ . '/../includes/header.php';

$userId = getCurrentUserId();
$currency = $company['currency'] ?? 'USD';

$search = trim($_GET['q'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

// Build query
$where = ["q.user_id = ?"];
$params = [$userId];

if (!empty($statusFilter)) {
    $where[] = "q.status = ?";
    $params[] = $statusFilter;
}

if (!empty($search)) {
    $where[] = "(q.quotation_number LIKE ? OR c.name LIKE ? OR c.company LIKE ?)";
    $term = "%$search%";
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
}

$whereSql = implode(' AND ', $where);

// Count
$countStmt = $pdo->prepare("
    SELECT COUNT(q.id) 
    FROM quotations q
    JOIN customers c ON q.customer_id = c.id
    WHERE $whereSql
");
$countStmt->execute($params);
$totalRecords = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($totalRecords / $limit));

// Fetch list
$sql = "
    SELECT q.*, c.name as customer_name, c.company as customer_company
    FROM quotations q
    JOIN customers c ON q.customer_id = c.id
    WHERE $whereSql
    ORDER BY q.quotation_date DESC, q.id DESC
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
$quotations = $stmt->fetchAll();
?>

<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
  <div>
    <h2 class="h5 fw-bold mb-1 text-slate-900">Quotations & Proposals</h2>
    <p class="text-muted small mb-0">Prepare pricing estimates, track customer acceptances, and convert into invoices.</p>
  </div>
  <a href="/quotations/create.php" class="btn btn-pg-primary d-inline-flex align-items-center gap-2">
    <i class="bi bi-file-earmark-plus"></i>
    <span>Create Quotation</span>
  </a>
</div>

<div class="pg-card mb-4">
  <div class="p-3 border-bottom bg-white d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
    <form method="GET" action="/quotations/index.php" class="d-flex flex-wrap gap-2 align-items-center">
      <div class="input-group input-group-sm" style="max-width: 260px;">
        <input type="text" name="q" class="form-control form-control-pg" value="<?= e($search) ?>" placeholder="Search quote # or customer...">
        <button class="btn btn-outline-secondary" type="submit">
          <i class="bi bi-search"></i>
        </button>
      </div>

      <select name="status" class="form-select form-select-sm form-select-pg" style="max-width: 160px;" onchange="this.form.submit()">
        <option value="">All Statuses</option>
        <?php foreach (['Draft', 'Sent', 'Accepted', 'Rejected', 'Expired'] as $st): ?>
          <option value="<?= $st ?>" <?= $statusFilter === $st ? 'selected' : '' ?>><?= $st ?></option>
        <?php endforeach; ?>
      </select>

      <?php if (!empty($search) || !empty($statusFilter)): ?>
        <a href="/quotations/index.php" class="btn btn-sm btn-outline-danger" title="Clear Filters">
          <i class="bi bi-x-lg"></i>
        </a>
      <?php endif; ?>
    </form>

    <div class="text-muted small">
      Showing <strong><?= count($quotations) ?></strong> of <strong><?= $totalRecords ?></strong> quotes
    </div>
  </div>

  <div class="table-responsive">
    <table class="table-pg">
      <thead>
        <tr>
          <th>Quote #</th>
          <th>Customer</th>
          <th>Quote Date</th>
          <th>Expiry Date</th>
          <th class="text-end">Grand Total</th>
          <th class="text-center">Status</th>
          <th class="text-end">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($quotations)): ?>
          <tr>
            <td colspan="7" class="text-center py-5 text-muted">
              <i class="bi bi-file-earmark-text fs-2 d-block mb-2 text-slate-400"></i>
              No quotations match your criteria.
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($quotations as $q): ?>
            <tr>
              <td>
                <a href="/quotations/view.php?id=<?= (int)$q['id'] ?>" class="fw-bold text-decoration-none mono-num text-dark">
                  <?= e($q['quotation_number']) ?>
                </a>
              </td>
              <td>
                <div class="fw-medium text-slate-900"><?= e($q['customer_name']) ?></div>
                <div class="text-muted small"><?= e($q['customer_company'] ?: 'Individual') ?></div>
              </td>
              <td class="text-muted small"><?= e(date('M d, Y', strtotime($q['quotation_date']))) ?></td>
              <td class="text-muted small"><?= e(date('M d, Y', strtotime($q['expiry_date']))) ?></td>
              <td class="text-end mono-num fw-semibold"><?= formatCurrency($q['grand_total'], $currency) ?></td>
              <td class="text-center"><?= getStatusBadge($q['status']) ?></td>
              <td class="text-end">
                <div class="dropdown">
                  <button class="btn btn-sm btn-light border-0" type="button" data-bs-toggle="dropdown">
                    <i class="bi bi-three-dots-vertical"></i>
                  </button>
                  <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                    <li><a class="dropdown-item" href="/quotations/view.php?id=<?= (int)$q['id'] ?>"><i class="bi bi-eye me-2"></i>View Quotation</a></li>
                    <li><a class="dropdown-item" href="/quotations/edit.php?id=<?= (int)$q['id'] ?>"><i class="bi bi-pencil me-2"></i>Edit Quote</a></li>
                    <li><a class="dropdown-item" href="/quotations/pdf.php?id=<?= (int)$q['id'] ?>" target="_blank"><i class="bi bi-file-earmark-pdf me-2 text-danger"></i>Download PDF</a></li>
                    <?php if (empty($q['converted_invoice_id']) && $q['status'] === 'Accepted'): ?>
                      <li>
                        <form method="POST" action="/quotations/convert.php" class="m-0">
                          <?= csrfField() ?>
                          <input type="hidden" name="quotation_id" value="<?= (int)$q['id'] ?>">
                          <button type="submit" class="dropdown-item text-primary fw-semibold">
                            <i class="bi bi-arrow-right-circle me-2"></i>Convert to Invoice
                          </button>
                        </form>
                      </li>
                    <?php endif; ?>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                      <a class="dropdown-item text-danger" href="/quotations/delete.php?id=<?= (int)$q['id'] ?>&csrf_token=<?= e(getCsrfToken()) ?>" data-confirm="Delete quotation <?= e($q['quotation_number']) ?>?">
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
