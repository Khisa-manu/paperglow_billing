<?php
/**
 * PaperGlow Billing System - Edit Quotation
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
requireAuth();

$userId = getCurrentUserId();
$pdo = getDBConnection();
$quotationId = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM quotations WHERE id = ? AND user_id = ?");
$stmt->execute([$quotationId, $userId]);
$quotation = $stmt->fetch();

if (!$quotation) {
    setFlash('danger', 'Quotation not found.');
    header('Location: /quotations/index.php');
    exit;
}

$currency = $company['currency'] ?? 'USD';

// Customers
$custStmt = $pdo->prepare("SELECT id, name, company FROM customers WHERE user_id = ? ORDER BY name ASC");
$custStmt->execute([$userId]);
$customers = $custStmt->fetchAll();

// Existing items
$itemStmt = $pdo->prepare("SELECT * FROM quotation_items WHERE quotation_id = ? ORDER BY sort_order ASC, id ASC");
$itemStmt->execute([$quotationId]);
$items = $itemStmt->fetchAll();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken()) {
        $error = 'Security session expired. Please refresh the page.';
    } else {
        $customerId = (int)($_POST['customer_id'] ?? 0);
        $quotationNumber = trim($_POST['quotation_number'] ?? '');
        $quotationDate = trim($_POST['quotation_date'] ?? '');
        $expiryDate = trim($_POST['expiry_date'] ?? '');
        $status = trim($_POST['status'] ?? 'Draft');
        $notes = trim($_POST['notes'] ?? '');
        $terms = trim($_POST['terms'] ?? '');

        $itemDescs = $_POST['items']['desc'] ?? [];
        $itemQtys = $_POST['items']['qty'] ?? [];
        $itemPrices = $_POST['items']['price'] ?? [];
        $itemDiscounts = $_POST['items']['discount'] ?? [];
        $itemTaxRates = $_POST['items']['tax_rate'] ?? [];

        if (empty($customerId) || empty($quotationNumber) || empty($quotationDate) || empty($expiryDate)) {
            $error = 'Please fill in all required header fields.';
        } else {
            // Check unique quotation number excluding current
            $chk = $pdo->prepare("SELECT id FROM quotations WHERE quotation_number = ? AND id != ? LIMIT 1");
            $chk->execute([$quotationNumber, $quotationId]);
            if ($chk->fetch()) {
                $error = 'Quotation number "' . $quotationNumber . '" is already in use by another quotation.';
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
                    $error = 'Please provide at least one valid item row.';
                } else {
                    try {
                        $pdo->beginTransaction();

                        $upd = $pdo->prepare("
                            UPDATE quotations SET 
                                customer_id = ?, quotation_number = ?, quotation_date = ?, expiry_date = ?,
                                status = ?, subtotal = ?, discount_total = ?, tax_total = ?, grand_total = ?,
                                notes = ?, terms = ?, updated_at = CURRENT_TIMESTAMP
                            WHERE id = ? AND user_id = ?
                        ");
                        $upd->execute([
                            $customerId, $quotationNumber, $quotationDate, $expiryDate,
                            $status, $calcSubtotal, $calcDiscountTotal, $calcTaxTotal, $calcGrandTotal,
                            $notes, $terms, $quotationId, $userId
                        ]);

                        // Replace items
                        $delItems = $pdo->prepare("DELETE FROM quotation_items WHERE quotation_id = ?");
                        $delItems->execute([$quotationId]);

                        $insItem = $pdo->prepare("
                            INSERT INTO quotation_items (
                                quotation_id, description, quantity, unit_price, discount, tax_rate, tax_amount, line_total, sort_order
                            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                        ");
                        foreach ($validItems as $it) {
                            $insItem->execute([
                                $quotationId, $it['description'], $it['quantity'], $it['unit_price'],
                                $it['discount'], $it['tax_rate'], $it['tax_amount'], $it['line_total'], $it['sort_order']
                            ]);
                        }

                        $pdo->commit();
                        setFlash('success', 'Quotation updated successfully.');
                        header('Location: /quotations/view.php?id=' . $quotationId);
                        exit;
                    } catch (Exception $e) {
                        if ($pdo->inTransaction()) {
                            $pdo->rollBack();
                        }
                        $error = 'Failed to update quotation: ' . $e->getMessage();
                    }
                }
            }
        }
    }
}

$pageTitle = 'Edit Quotation ' . $quotation['quotation_number'];
require_once __DIR__ . '/../includes/header.php';
?>

<div class="mb-4">
  <a href="/quotations/view.php?id=<?= $quotationId ?>" class="text-decoration-none text-muted small d-inline-flex align-items-center gap-1 mb-2">
    <i class="bi bi-arrow-left"></i> Back to Quotation
  </a>
  <h2 class="h5 fw-bold text-slate-900 mb-1">Edit Quotation <?= e($quotation['quotation_number']) ?></h2>
</div>

<?php if (!empty($error)): ?>
  <div class="alert alert-danger py-2 px-3 small d-flex align-items-center mb-4">
    <i class="bi bi-exclamation-octagon-fill me-2"></i>
    <div><?= e($error) ?></div>
  </div>
<?php endif; ?>

<form method="POST" action="/quotations/edit.php?id=<?= $quotationId ?>">
  <?= csrfField() ?>

  <div class="pg-card mb-4">
    <div class="pg-card-body p-4">
      <div class="row g-3">
        <div class="col-md-5">
          <label class="form-label-pg" for="customerSelect">Recipient Customer *</label>
          <select name="customer_id" id="customerSelect" class="form-select form-select-pg" required>
            <?php foreach ($customers as $c): ?>
              <option value="<?= (int)$c['id'] ?>" <?= ((int)$quotation['customer_id'] === (int)$c['id']) ? 'selected' : '' ?>>
                <?= e($c['name']) ?> <?= !empty($c['company']) ? '(' . e($c['company']) . ')' : '' ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-md-3 col-sm-6">
          <label class="form-label-pg" for="quotation_number">Quotation Number *</label>
          <input type="text" class="form-control form-control-pg mono-num fw-bold" id="quotation_number" name="quotation_number" value="<?= e($quotation['quotation_number']) ?>" required>
        </div>

        <div class="col-md-2 col-sm-6">
          <label class="form-label-pg" for="quotation_date">Quote Date *</label>
          <input type="date" class="form-control form-control-pg" id="quotation_date" name="quotation_date" value="<?= e($quotation['quotation_date']) ?>" required>
        </div>

        <div class="col-md-2 col-sm-6">
          <label class="form-label-pg" for="expiry_date">Expiry Date *</label>
          <input type="date" class="form-control form-control-pg" id="expiry_date" name="expiry_date" value="<?= e($quotation['expiry_date']) ?>" required>
        </div>

        <div class="col-md-3 col-sm-6">
          <label class="form-label-pg" for="status">Status</label>
          <select name="status" id="status" class="form-select form-select-pg">
            <?php foreach (['Draft', 'Sent', 'Accepted', 'Rejected', 'Expired'] as $st): ?>
              <option value="<?= $st ?>" <?= $quotation['status'] === $st ? 'selected' : '' ?>><?= $st ?></option>
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
              <span class="mono-num fw-semibold text-dark" id="calcSubtotal"><?= formatCurrency($quotation['subtotal'], $currency) ?></span>
            </div>
            <div class="d-flex justify-content-between py-1 small">
              <span class="text-muted">Discount Total:</span>
              <span class="mono-num fw-semibold text-danger" id="calcDiscount">-<?= formatCurrency($quotation['discount_total'], $currency) ?></span>
            </div>
            <div class="d-flex justify-content-between py-1 small">
              <span class="text-muted">Estimated Tax:</span>
              <span class="mono-num fw-semibold text-dark" id="calcTax"><?= formatCurrency($quotation['tax_total'], $currency) ?></span>
            </div>
            <hr class="my-2">
            <div class="d-flex justify-content-between py-1">
              <span class="fw-bold text-slate-900">Grand Total:</span>
              <span class="mono-num fw-bold fs-5 text-slate-900" id="calcGrandTotal"><?= formatCurrency($quotation['grand_total'], $currency) ?></span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="row g-4 mb-4">
    <div class="col-md-6">
      <div class="pg-card p-3">
        <label class="form-label-pg" for="notes">Notes / Scope Overview</label>
        <textarea class="form-control form-control-pg" id="notes" name="notes" rows="3"><?= e($quotation['notes']) ?></textarea>
      </div>
    </div>
    <div class="col-md-6">
      <div class="pg-card p-3">
        <label class="form-label-pg" for="terms">Quotation Terms & Conditions</label>
        <textarea class="form-control form-control-pg" id="terms" name="terms" rows="3"><?= e($quotation['terms']) ?></textarea>
      </div>
    </div>
  </div>

  <div class="d-flex align-items-center justify-content-end gap-2 pb-5">
    <a href="/quotations/view.php?id=<?= $quotationId ?>" class="btn btn-pg-secondary">Cancel</a>
    <button type="submit" class="btn btn-pg-primary px-4">Save Changes</button>
  </div>
</form>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
