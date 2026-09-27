<?php
/**
 * PaperGlow Billing System - Customer Directory
 */

declare(strict_types=1);

$pageTitle = 'Customers';
require_once __DIR__ . '/../includes/header.php';

$userId = getCurrentUserId();
$currency = $company['currency'] ?? 'USD';

$search = trim($_GET['q'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

// Count total
if (!empty($search)) {
    $countStmt = $pdo->prepare("
        SELECT COUNT(id) FROM customers 
        WHERE user_id = ? AND (name LIKE ? OR company LIKE ? OR email LIKE ? OR phone LIKE ?)
    ");
    $term = "%$search%";
    $countStmt->execute([$userId, $term, $term, $term, $term]);
} else {
    $countStmt = $pdo->prepare("SELECT COUNT(id) FROM customers WHERE user_id = ?");
    $countStmt->execute([$userId]);
}
$totalRecords = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($totalRecords / $limit));

// Fetch customer records with computed financial balances
if (!empty($search)) {
    $sql = "
        SELECT c.*,
            (SELECT COUNT(i.id) FROM invoices i WHERE i.customer_id = c.id) as invoice_count,
            (SELECT COALESCE(SUM(i.balance), 0) FROM invoices i WHERE i.customer_id = c.id) as outstanding_balance,
            (SELECT COUNT(q.id) FROM quotations q WHERE q.customer_id = c.id) as quotation_count
        FROM customers c
        WHERE c.user_id = ? AND (c.name LIKE ? OR c.company LIKE ? OR c.email LIKE ? OR c.phone LIKE ?)
        ORDER BY c.name ASC
        LIMIT ? OFFSET ?
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(1, $userId, PDO::PARAM_INT);
    $stmt->bindValue(2, $term, PDO::PARAM_STR);
    $stmt->bindValue(3, $term, PDO::PARAM_STR);
    $stmt->bindValue(4, $term, PDO::PARAM_STR);
    $stmt->bindValue(5, $term, PDO::PARAM_STR);
    $stmt->bindValue(6, $limit, PDO::PARAM_INT);
    $stmt->bindValue(7, $offset, PDO::PARAM_INT);
    $stmt->execute();
} else {
    $sql = "
        SELECT c.*,
            (SELECT COUNT(i.id) FROM invoices i WHERE i.customer_id = c.id) as invoice_count,
            (SELECT COALESCE(SUM(i.balance), 0) FROM invoices i WHERE i.customer_id = c.id) as outstanding_balance,
            (SELECT COUNT(q.id) FROM quotations q WHERE q.customer_id = c.id) as quotation_count
        FROM customers c
        WHERE c.user_id = ?
        ORDER BY c.name ASC
        LIMIT ? OFFSET ?
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(1, $userId, PDO::PARAM_INT);
    $stmt->bindValue(2, $limit, PDO::PARAM_INT);
    $stmt->bindValue(3, $offset, PDO::PARAM_INT);
    $stmt->execute();
}
$customers = $stmt->fetchAll();
?>

<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
  <div>
    <h2 class="h5 fw-bold mb-1 text-slate-900">Customer Accounts</h2>
    <p class="text-muted small mb-0">Manage customer organizations, contact billing records, and outstanding balances.</p>
  </div>
  <a href="/customers/create.php" class="btn btn-pg-primary d-inline-flex align-items-center gap-2">
    <i class="bi bi-person-plus-fill"></i>
    <span>Add Customer</span>
  </a>
</div>

<div class="pg-card mb-4">
  <div class="p-3 border-bottom bg-white d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
    <form method="GET" action="/customers/index.php" class="d-flex gap-2" style="max-width: 360px;">
      <div class="input-group input-group-sm">
        <input type="text" name="q" class="form-control form-control-pg" value="<?= e($search) ?>" placeholder="Search by name, company, email...">
        <button class="btn btn-outline-secondary" type="submit">
          <i class="bi bi-search"></i>
        </button>
      </div>
      <?php if (!empty($search)): ?>
        <a href="/customers/index.php" class="btn btn-sm btn-outline-danger" title="Clear Search">
          <i class="bi bi-x-lg"></i>
        </a>
      <?php endif; ?>
    </form>
    <div class="text-muted small">
      Showing <strong><?= count($customers) ?></strong> of <strong><?= $totalRecords ?></strong> customers
    </div>
  </div>

  <div class="table-responsive">
    <table class="table-pg">
      <thead>
        <tr>
          <th>Customer / Organization</th>
          <th>Contact Email</th>
          <th>Phone</th>
          <th class="text-center">Quotations</th>
          <th class="text-center">Invoices</th>
          <th class="text-end">Outstanding</th>
          <th class="text-end">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($customers)): ?>
          <tr>
            <td colspan="7" class="text-center py-5 text-muted">
              <i class="bi bi-people fs-2 d-block mb-2 text-slate-400"></i>
              No customers found. <?= !empty($search) ? 'Try a different search keyword.' : 'Click "Add Customer" to add your first billing client.' ?>
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($customers as $c): ?>
            <tr>
              <td>
                <a href="/customers/view.php?id=<?= (int)$c['id'] ?>" class="fw-bold text-dark text-decoration-none">
                  <?= e($c['name']) ?>
                </a>
                <?php if (!empty($c['company'])): ?>
                  <div class="text-muted small"><?= e($c['company']) ?></div>
                <?php endif; ?>
              </td>
              <td><?= e($c['email'] ?: '—') ?></td>
              <td><?= e($c['phone'] ?: '—') ?></td>
              <td class="text-center">
                <span class="badge bg-light text-dark border"><?= (int)$c['quotation_count'] ?></span>
              </td>
              <td class="text-center">
                <span class="badge bg-light text-dark border"><?= (int)$c['invoice_count'] ?></span>
              </td>
              <td class="text-end mono-num fw-semibold">
                <?php $bal = (float)$c['outstanding_balance']; ?>
                <?php if ($bal > 0): ?>
                  <span class="text-danger"><?= formatCurrency($bal, $currency) ?></span>
                <?php else: ?>
                  <span class="text-muted"><?= formatCurrency(0, $currency) ?></span>
                <?php endif; ?>
              </td>
              <td class="text-end">
                <div class="dropdown">
                  <button class="btn btn-sm btn-light border-0" type="button" data-bs-toggle="dropdown">
                    <i class="bi bi-three-dots-vertical"></i>
                  </button>
                  <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                    <li><a class="dropdown-item" href="/customers/view.php?id=<?= (int)$c['id'] ?>"><i class="bi bi-eye me-2"></i>View History</a></li>
                    <li><a class="dropdown-item" href="/customers/edit.php?id=<?= (int)$c['id'] ?>"><i class="bi bi-pencil me-2"></i>Edit Profile</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                      <a class="dropdown-item text-danger" href="/customers/delete.php?id=<?= (int)$c['id'] ?>&csrf_token=<?= e(getCsrfToken()) ?>" data-confirm="Are you sure you want to delete customer '<?= e($c['name']) ?>'?">
                        <i class="bi bi-trash3 me-2"></i>Delete Customer
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
              <a class="page-link" href="?page=<?= $i ?><?= !empty($search) ? '&q=' . urlencode($search) : '' ?>"><?= $i ?></a>
            </li>
          <?php endfor; ?>
        </ul>
      </nav>
    </div>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
