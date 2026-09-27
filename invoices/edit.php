<?php
/**
 * PaperGlow Billing System - Edit Invoice
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
requireAuth();

$userId = getCurrentUserId();
$pdo = getDBConnection();
$invoiceId = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM invoices WHERE id = ? AND user_id = ?");
$stmt->execute([$invoiceId, $userId]);
$invoice = $stmt->fetch();

if (!$invoice) {
    setFlash('danger', 'Invoice not found.');
    header('Location: /invoices/index.php');
    exit;
}

$currency = $company['currency'] ?? 'USD';

// Customers
$custStmt = $pdo->prepare("SELECT id, name, company FROM customers WHERE user_id = ? ORDER BY name ASC");
$custStmt->execute([$userId]);
$customers = $custStmt->fetchAll();

// Items
$itemStmt = $pdo->prepare("SELECT * FROM invoice_items WHERE invoice_id = ? ORDER BY sort_order ASC, id ASC");
$itemStmt->execute([$invoiceId]);
$items = $itemStmt->fetchAll();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken()) {
        $error = 'Security session expired. Please refresh the page.';
    } else {
        $customerId = (int)($_POST['customer_id'] ?? 0);
        $invoiceNumber = trim($_POST['invoice_number'] ?? '');
        $invoiceDate = trim($_POST['invoice_date'] ?? '');
        $dueDate = trim($_POST['due_date'] ?? '');
        $status = trim($_POST['status'] ?? 'Unpaid');
        $notes = trim($_POST['notes'] ?? '');
        $terms = trim($_POST['terms'] ?? '');

        $itemDescs = $_POST['items']['desc'] ?? [];
        $itemQtys = $_POST['items']['qty'] ?? [];
        $itemPrices = $_POST['items']['price'] ?? [];
        $itemDiscounts = $_POST['items']['discount'] ?? [];
        $itemTaxRates = $_POST['items']['tax_rate'] ?? [];

        if (empty($customerId) || empty($invoiceNumber) || empty($invoiceDate) || empty($dueDate)) {
            $error = 'Please fill in all required header fields.';
        } else {
            // Check unique excluding current
            $chk = $pdo->prepare("SELECT id FROM invoices WHERE invoice_number = ? AND id != ? LIMIT 1");
            $chk->execute([$invoiceNumber, $invoiceId]);
            if ($chk->fetch()) {
                $error = 'Invoice number "' . $invoiceNumber . '" is already taken by another invoice.';
            } else {
                $calcSubtotal = 0.0;
                $calcDiscountTotal = 0.0;
                $calcTaxTotal = 0.0;
                $calcGrandTotal = 0.0;
                $validItems = [];

                for ($i = 0; $i < count($itemDescs); $i++) {
                    $desc = trim((string)($itemDescs[$i] ?? ''));
                    if (empty($desc)) continue;

                    $qty = max(0.001, (float)($itemQtys[$i] ?? 1.0));
                    $price = max(0.0, (float)($itemPrices[$i] ?? 0.0));
                    $discount = max(0.0, (float)($itemDiscounts[$i] ?? 0.0));
                    $taxRate = max(0.0, (float)($itemTaxRates[$i] ?? 0.0));

                    $base = max(0.0, ($qty * $price) - $discount);
                    $taxAmount = round(($base * $taxRate) / 100.0, 2);
                    $lineTotal = round($base + $taxAmount, 2);

                    $calcSubtotal += round($qty * $price, 2);
                    $calcDiscountTotal += $discount;
                    $calcTaxTotal += $taxAmount;
                    $calcGrandTotal += $lineTotal;

                    $validItems[] = [
                        'description' => $desc,
                        'quantity'    => $qty,
                        'unit_price'  => $price,
                        'discount'    => $discount,
                        'tax_rate'    => $taxRate,
                        'tax_amount'  => $taxAmount,
                        'line_total'  => $lineTotal,
                        'sort_order'  => $i + 1
                    ];
                }

                if (empty($validItems)) {
                    $error = 'Please provide at least one valid item line.';
                } else {
                    try {
                        $pdo->beginTransaction();

                        // Query existing paid amount
                        $paidStmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE invoice_id = ?");
                        $paidStmt->execute([$invoiceId]);
                        $totalPaid = (float)$paidStmt->fetchColumn();

                        $balance = max(0.0, round($calcGrandTotal - $totalPaid, 2));

                        // Status re-eval
                        if ($balance <= 0.0001 && $calcGrandTotal > 0) {
                            $status = 'Paid';
                        } elseif ($totalPaid > 0 && $balance > 0) {
                            $status = 'Partially Paid';
                        } elseif ($dueDate < date('Y-m-d') && $totalPaid == 0 && $status !== 'Draft' && $status !== 'Cancelled') {
                            $status = 'Overdue';
                        }

                        $upd = $pdo->prepare("
                            UPDATE invoices SET 
                                customer_id = ?, invoice_number = ?, invoice_date = ?, due_date = ?,
                                status = ?, subtotal = ?, discount_total = ?, tax_total = ?, grand_total = ?,
                                paid_amount = ?, balance = ?, notes = ?, terms = ?, updated_at = CURRENT_TIMESTAMP
                            WHERE id = ? AND user_id = ?
                        ");
                        $upd->execute([
                            $customerId, $invoiceNumber, $invoiceDate, $dueDate,
                            $status, $calcSubtotal, $calcDiscountTotal, $calcTaxTotal, $calcGrandTotal,
                            $totalPaid, $balance, $notes, $terms, $invoiceId, $userId
                        ]);

                        // Replace items
                        $del = $pdo->prepare("DELETE FROM invoice_items WHERE invoice_id = ?");
                        $del->execute([$invoiceId]);

                        $insItem = $pdo->prepare("
                            INSERT INTO invoice_items (
                                invoice_id, description, quantity, unit_price, discount, tax_rate, tax_amount, line_total, sort_order
                            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                        ");
                        foreach ($validItems as $it) {
                            $insItem->execute([
                                $invoiceId, $it['description'], $it['quantity'], $it['unit_price'],
                                $it['discount'], $it['tax_rate'], $it['tax_amount'], $it['line_total'], $it['sort_order']
                            ]);
                        }

                        $pdo->commit();
                        setFlash('success', 'Invoice updated successfully.');
                        header('Location: /invoices/view.php?id=' . $invoiceId);
                        exit;
                    } catch (Exception $e) {
                        if ($pdo->inTransaction()) {
                            $pdo->rollBack();
                        }
                        $error = 'Failed to update invoice: ' . $e->getMessage();
                    }
                }
            }
        }
    }
}

$pageTitle = 'Edit Invoice ' . $invoice['invoice_number'];
require_once __DIR__ . '/../includes/header.php';
?>

<div class="mb-4">
  <a href="/invoices/view.php?id=<?= $invoiceId ?>" class="text-decoration-none text-muted small d-inline-flex align-items-center gap-1 mb-2">
    <i class="bi bi-arrow-left"></i> Back to Invoice
  </a>
  <h2 class="h5 fw-bold text-slate-900 mb-1">Edit Invoice <?= e($invoice['invoice_number']) ?></h2>
</div>

<?php if (!empty($error)): ?>
  <div class="alert alert-danger py-2 px-3 small d-flex align-items-center mb-4">
    <i class="bi bi-exclamation-octagon-fill me-2"></i>
    <div><?= e($error) ?></div>
  </div>
<?php endif; ?>

<form method="POST" action="/invoices/edit.php?id=<?= $invoiceId ?>">
  <?= csrfField() ?>

  <div class="pg-card mb-4">
    <div class="pg-card-body p-4">
      <div class="row g-3">
        <div class="col-md-5">
          <label class="form-label-pg" for="customerSelect">Billed Customer *</label>
          <select name="customer_id" id="customerSelect" class="form-select form-select-pg" required>
            <?php foreach ($customers as $c): ?>
              <option value="<?= (int)$c['id'] ?>" <?= ((int)$invoice['customer_id'] === (int)$c['id']) ? 'selected' : '' ?>>
                <?= e($c['name']) ?> <?= !empty($c['company']) ? '(' . e($c['company']) . ')' : '' ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-md-3 col-sm-6">
          <label class="form-label-pg" for="invoice_number">Invoice Number *</label>
          <input type="text" class="form-control form-control-pg mono-num fw-bold" id="invoice_number" name="invoice_number" value="<?= e($invoice['invoice_number']) ?>" required>
        </div>

        <div class="col-md-2 col-sm-6">
          <label class="form-label-pg" for="invoice_date">Issue Date *</label>
          <input type="date" class="form-control form-control-pg" id="invoice_date" name="invoice_date" value="<?= e($invoice['invoice_date']) ?>" required>
        </div>

        <div class="col-md-2 col-sm-6">
          <label class="form-label-pg" for="due_date">Due Date *</label>
          <input type="date" class="form-control form-control-pg" id="due_date" name="due_date" value="<?= e($invoice['due_date']) ?>" required>
        </div>

        <div class="col-md-3 col-sm-6">
          <label class="form-label-pg" for="status">Status</label>
          <select name="status" id="status" class="form-select form-select-pg">
            <?php foreach (['Draft', 'Unpaid', 'Partially Paid', 'Paid', 'Overdue', 'Cancelled'] as $st): ?>
              <option value="<?= $st ?>" <?= $invoice['status'] === $st ? 'selected' : '' ?>><?= $st ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
    </div>
  </div>

  <!-- Items Card -->
  <div class="pg-card mb-4">
    <div class="pg-card-header">
      <h3 class="h6 fw-bold mb-0 text-slate-900">Line Items</h3>
      <button type="button" class="btn btn-outline-secondary btn-sm" id="addItemBtn">
        <i class="bi bi-plus-lg me-1"></i> Add Row
      </button>
    </div>
    <div class="pg-card-body p-4">
      <div id="itemsContainer" data-currency-symbol="<?= e(getCurrencySymbol($currency)) ?>">
        <?php foreach ($items as $it): ?>
          <div class="pg-item-row">
            <div class="row g-2 align-items-center">
              <div class="col-md-4 col-12">
                <label class="form-label-pg d-md-none">Description</label>
                <input type="text" name="items[desc][]" class="form-control form-control-pg" value="<?= e($it['description']) ?>" placeholder="Description..." required>
              </div>
              <div class="col-md-2 col-4">
                <label class="form-label-pg d-md-none">Qty</label>
                <input type="number" name="items[qty][]" class="form-control form-control-pg item-qty text-end" value="<?= number_format((float)$it['quantity'], 2, '.', '') ?>" step="0.01" min="0.01" required>
              </div>
              <div class="col-md-2 col-4">
                <label class="form-label-pg d-md-none">Price</label>
                <input type="number" name="items[price][]" class="form-control form-control-pg item-price text-end" value="<?= number_format((float)$it['unit_price'], 2, '.', '') ?>" step="0.01" min="0.00" required>
              </div>
              <div class="col-md-1 col-4">
                <label class="form-label-pg d-md-none">Disc</label>
                <input type="number" name="items[discount][]" class="form-control form-control-pg item-discount text-end" value="<?= number_format((float)$it['discount'], 2, '.', '') ?>" step="0.01" min="0.00">
              </div>
              <div class="col-md-1 col-4">
                <label class="form-label-pg d-md-none">Tax %</label>
                <input type="number" name="items[tax_rate][]" class="form-control form-control-pg item-tax-rate text-end" value="<?= number_format((float)$it['tax_rate'], 2, '.', '') ?>" step="0.01" min="0.00">
              </div>
              <div class="col-md-1 col-4 text-end">
                <span class="mono-num fw-semibold item-line-total"><?= formatCurrency($it['line_total'], $currency) ?></span>
              </div>
              <div class="col-md-1 col-4 text-end">
                <button type="button" class="btn btn-outline-danger btn-sm border-0 remove-item-btn">
                  <i class="bi bi-trash3"></i>
                </button>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <div class="row justify-content-end mt-4">
        <div class="col-md-5">
          <div class="p-3 rounded-3 bg-light border">
            <div class="d-flex justify-content-between py-1 small">
              <span class="text-muted">Subtotal:</span>
              <span class="mono-num fw-semibold text-dark" id="calcSubtotal"><?= formatCurrency($invoice['subtotal'], $currency) ?></span>
            </div>
            <div class="d-flex justify-content-between py-1 small">
              <span class="text-muted">Discount Total:</span>
              <span class="mono-num fw-semibold text-danger" id="calcDiscount">-<?= formatCurrency($invoice['discount_total'], $currency) ?></span>
            </div>
            <div class="d-flex justify-content-between py-1 small">
              <span class="text-muted">Total Tax:</span>
              <span class="mono-num fw-semibold text-dark" id="calcTax"><?= formatCurrency($invoice['tax_total'], $currency) ?></span>
            </div>
            <hr class="my-2">
            <div class="d-flex justify-content-between py-1">
              <span class="fw-bold text-slate-900">Grand Total:</span>
              <span class="mono-num fw-bold fs-5 text-slate-900" id="calcGrandTotal"><?= formatCurrency($invoice['grand_total'], $currency) ?></span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="row g-4 mb-4">
    <div class="col-md-6">
      <div class="pg-card p-3">
        <label class="form-label-pg" for="notes">Notes</label>
        <textarea class="form-control form-control-pg" id="notes" name="notes" rows="3"><?= e($invoice['notes']) ?></textarea>
      </div>
    </div>
    <div class="col-md-6">
      <div class="pg-card p-3">
        <label class="form-label-pg" for="terms">Payment Terms</label>
        <textarea class="form-control form-control-pg" id="terms" name="terms" rows="3"><?= e($invoice['terms']) ?></textarea>
      </div>
    </div>
  </div>

  <div class="d-flex align-items-center justify-content-end gap-2 pb-5">
    <a href="/invoices/view.php?id=<?= $invoiceId ?>" class="btn btn-pg-secondary">Cancel</a>
    <button type="submit" class="btn btn-pg-primary px-4">Save Changes</button>
  </div>
</form>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
