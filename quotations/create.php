<?php
/**
 * PaperGlow Billing System - Create Quotation
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
requireAuth();

$userId = getCurrentUserId();
$pdo = getDBConnection();
$currency = $company['currency'] ?? 'USD';

// Fetch customer options
$custStmt = $pdo->prepare("SELECT id, name, company FROM customers WHERE user_id = ? ORDER BY name ASC");
$custStmt->execute([$userId]);
$customers = $custStmt->fetchAll();

// Auto generate quotation number
$defaultQuoNumber = generateDocumentNumber($pdo, 'quotation', $userId);
$defaultDate = date('Y-m-d');
$defaultExpiry = date('Y-m-d', strtotime('+30 days'));
$defaultTerms = $company['default_quotation_terms'] ?? 'Quotation valid for 30 calendar days from issue.';

$selectedCustomerId = (int)($_GET['customer_id'] ?? 0);
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

        // Line items arrays
        $itemDescs = $_POST['items']['desc'] ?? [];
        $itemQtys = $_POST['items']['qty'] ?? [];
        $itemPrices = $_POST['items']['price'] ?? [];
        $itemDiscounts = $_POST['items']['discount'] ?? [];
        $itemTaxRates = $_POST['items']['tax_rate'] ?? [];

        // Validation
        if (empty($customerId)) {
            $error = 'Please select a customer.';
        } elseif (empty($quotationNumber)) {
            $error = 'Quotation number cannot be empty.';
        } elseif (empty($quotationDate) || empty($expiryDate)) {
            $error = 'Quotation date and expiry date are required.';
        } elseif (empty($itemDescs) || count($itemDescs) === 0) {
            $error = 'Please add at least one line item to the quotation.';
        } else {
            // Check duplicate quotation number
            $chk = $pdo->prepare("SELECT id FROM quotations WHERE quotation_number = ? LIMIT 1");
            $chk->execute([$quotationNumber]);
            if ($chk->fetch()) {
                $error = 'Quotation number "' . $quotationNumber . '" already exists. Please choose a different number.';
            } else {
                // Server-side financial recalculation
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
                    $error = 'Please enter at least one valid line item with a description.';
                } else {
                    try {
                        $pdo->beginTransaction();

                        $stmtQuo = $pdo->prepare("
                            INSERT INTO quotations (
                                user_id, customer_id, quotation_number, quotation_date, expiry_date, 
                                status, subtotal, discount_total, tax_total, grand_total, notes, terms
                            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                        ");
                        $stmtQuo->execute([
                            $userId, $customerId, $quotationNumber, $quotationDate, $expiryDate,
                            $status, $calcSubtotal, $calcDiscountTotal, $calcTaxTotal, $calcGrandTotal, $notes, $terms
                        ]);
                        $quotationId = (int)$pdo->lastInsertId();

                        // Insert line items
                        $stmtItem = $pdo->prepare("
                            INSERT INTO quotation_items (
                                quotation_id, description, quantity, unit_price, discount, tax_rate, tax_amount, line_total, sort_order
                            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                        ");

                        foreach ($validItems as $item) {
                            $stmtItem->execute([
                                $quotationId,
                                $item['description'],
                                $item['quantity'],
                                $item['unit_price'],
                                $item['discount'],
                                $item['tax_rate'],
                                $item['tax_amount'],
                                $item['line_total'],
                                $item['sort_order']
                            ]);
                        }

                        $pdo->commit();

                        setFlash('success', 'Quotation ' . $quotationNumber . ' created successfully.');
                        header('Location: /quotations/view.php?id=' . $quotationId);
                        exit;
                    } catch (Exception $e) {
                        if ($pdo->inTransaction()) {
                            $pdo->rollBack();
                        }
                        $error = 'Failed to save quotation: ' . $e->getMessage();
                    }
                }
            }
        }
    }
}

$pageTitle = 'Create Quotation';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="mb-4">
  <a href="/quotations/index.php" class="text-decoration-none text-muted small d-inline-flex align-items-center gap-1 mb-2">
    <i class="bi bi-arrow-left"></i> Back to Quotations
  </a>
  <h2 class="h5 fw-bold text-slate-900 mb-1">New Pricing Quotation</h2>
  <p class="text-muted small mb-0">Compose an itemized quotation for prospective work.</p>
</div>

<?php if (!empty($error)): ?>
  <div class="alert alert-danger py-2 px-3 small d-flex align-items-center mb-4">
    <i class="bi bi-exclamation-octagon-fill me-2"></i>
    <div><?= e($error) ?></div>
  </div>
<?php endif; ?>

<form method="POST" action="/quotations/create.php" id="quotationForm">
  <?= csrfField() ?>

  <!-- Document Meta Card -->
  <div class="pg-card mb-4">
    <div class="pg-card-body p-4">
      <div class="row g-3">
        <div class="col-md-5">
          <div class="d-flex align-items-center justify-content-between mb-1">
            <label class="form-label-pg mb-0" for="customerSelect">Recipient Customer *</label>
            <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none" style="color: #d97706;" data-bs-toggle="modal" data-bs-target="#quickCustomerModal">
              + New Customer
            </button>
          </div>
          <select name="customer_id" id="customerSelect" class="form-select form-select-pg" required>
            <option value="">Select billing customer...</option>
            <?php foreach ($customers as $c): ?>
              <option value="<?= (int)$c['id'] ?>" <?= ($selectedCustomerId === (int)$c['id']) ? 'selected' : '' ?>>
                <?= e($c['name']) ?> <?= !empty($c['company']) ? '(' . e($c['company']) . ')' : '' ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-md-3 col-sm-6">
          <label class="form-label-pg" for="quotation_number">Quotation Number *</label>
          <input type="text" class="form-control form-control-pg mono-num fw-bold" id="quotation_number" name="quotation_number" value="<?= e($_POST['quotation_number'] ?? $defaultQuoNumber) ?>" required>
        </div>

        <div class="col-md-2 col-sm-6">
          <label class="form-label-pg" for="quotation_date">Quote Date *</label>
          <input type="date" class="form-control form-control-pg" id="quotation_date" name="quotation_date" value="<?= e($_POST['quotation_date'] ?? $defaultDate) ?>" required>
        </div>

        <div class="col-md-2 col-sm-6">
          <label class="form-label-pg" for="expiry_date">Expiry Date *</label>
          <input type="date" class="form-control form-control-pg" id="expiry_date" name="expiry_date" value="<?= e($_POST['expiry_date'] ?? $defaultExpiry) ?>" required>
        </div>

        <div class="col-md-3 col-sm-6">
          <label class="form-label-pg" for="status">Initial Status</label>
          <select name="status" id="status" class="form-select form-select-pg">
            <option value="Draft">Draft</option>
            <option value="Sent">Sent</option>
            <option value="Accepted">Accepted</option>
          </select>
        </div>
      </div>
    </div>
  </div>

  <!-- Line Items Card -->
  <div class="pg-card mb-4">
    <div class="pg-card-header">
      <h3 class="h6 fw-bold mb-0 text-slate-900">Quotation Line Items</h3>
      <button type="button" class="btn btn-outline-secondary btn-sm" id="addItemBtn">
        <i class="bi bi-plus-lg me-1"></i> Add Row
      </button>
    </div>
    <div class="pg-card-body p-4">
      <div class="d-none d-md-flex row g-2 mb-2 px-2 text-muted small fw-bold text-uppercase">
        <div class="col-md-4">Item Description</div>
        <div class="col-md-2 text-end">Quantity</div>
        <div class="col-md-2 text-end">Unit Price</div>
        <div class="col-md-1 text-end">Discount</div>
        <div class="col-md-1 text-end">Tax %</div>
        <div class="col-md-1 text-end">Line Total</div>
        <div class="col-md-1 text-end"></div>
      </div>

      <div id="itemsContainer" data-currency-symbol="<?= e(getCurrencySymbol($currency)) ?>">
        <div class="pg-item-row">
          <div class="row g-2 align-items-center">
            <div class="col-md-4 col-12">
              <label class="form-label-pg d-md-none">Description</label>
              <input type="text" name="items[desc][]" class="form-control form-control-pg" placeholder="Item description or service rendered..." required>
            </div>
            <div class="col-md-2 col-4">
              <label class="form-label-pg d-md-none">Qty</label>
              <input type="number" name="items[qty][]" class="form-control form-control-pg item-qty text-end" value="1.00" step="0.01" min="0.01" required>
            </div>
            <div class="col-md-2 col-4">
              <label class="form-label-pg d-md-none">Price</label>
              <input type="number" name="items[price][]" class="form-control form-control-pg item-price text-end" value="0.00" step="0.01" min="0.00" required>
            </div>
            <div class="col-md-1 col-4">
              <label class="form-label-pg d-md-none">Disc</label>
              <input type="number" name="items[discount][]" class="form-control form-control-pg item-discount text-end" value="0.00" step="0.01" min="0.00">
            </div>
            <div class="col-md-1 col-4">
              <label class="form-label-pg d-md-none">Tax %</label>
              <input type="number" name="items[tax_rate][]" class="form-control form-control-pg item-tax-rate text-end" value="0.00" step="0.01" min="0.00">
            </div>
            <div class="col-md-1 col-4 text-end">
              <label class="form-label-pg d-md-none">Line Total</label>
              <span class="mono-num fw-semibold item-line-total"><?= formatCurrency(0, $currency) ?></span>
            </div>
            <div class="col-md-1 col-4 text-end">
              <button type="button" class="btn btn-outline-danger btn-sm border-0 remove-item-btn" title="Remove Item">
                <i class="bi bi-trash3"></i>
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Financial Calculation Summary Box -->
      <div class="row justify-content-end mt-4">
        <div class="col-md-5">
          <div class="p-3 rounded-3 bg-light border">
            <div class="d-flex justify-content-between py-1 small">
              <span class="text-muted">Subtotal:</span>
              <span class="mono-num fw-semibold text-dark" id="calcSubtotal"><?= formatCurrency(0, $currency) ?></span>
            </div>
            <div class="d-flex justify-content-between py-1 small">
              <span class="text-muted">Discount Total:</span>
              <span class="mono-num fw-semibold text-danger" id="calcDiscount"><?= formatCurrency(0, $currency) ?></span>
            </div>
            <div class="d-flex justify-content-between py-1 small">
              <span class="text-muted">Estimated Tax:</span>
              <span class="mono-num fw-semibold text-dark" id="calcTax"><?= formatCurrency(0, $currency) ?></span>
            </div>
            <hr class="my-2">
            <div class="d-flex justify-content-between py-1">
              <span class="fw-bold text-slate-900">Grand Total:</span>
              <span class="mono-num fw-bold fs-5 text-slate-900" id="calcGrandTotal"><?= formatCurrency(0, $currency) ?></span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Notes & Terms -->
  <div class="row g-4 mb-4">
    <div class="col-md-6">
      <div class="pg-card p-3">
        <label class="form-label-pg" for="notes">Notes / Scope Overview</label>
        <textarea class="form-control form-control-pg" id="notes" name="notes" rows="3" placeholder="Additional notes or specifications for the client..."></textarea>
      </div>
    </div>
    <div class="col-md-6">
      <div class="pg-card p-3">
        <label class="form-label-pg" for="terms">Quotation Terms & Conditions</label>
        <textarea class="form-control form-control-pg" id="terms" name="terms" rows="3"><?= e($defaultTerms) ?></textarea>
      </div>
    </div>
  </div>

  <div class="d-flex align-items-center justify-content-end gap-2 pb-5">
    <a href="/quotations/index.php" class="btn btn-pg-secondary">Cancel</a>
    <button type="submit" class="btn btn-pg-primary px-4">
      <i class="bi bi-check-lg me-1"></i> Save Quotation
    </button>
  </div>
</form>

<!-- Modal: Quick Add Customer -->
<div class="modal fade" id="quickCustomerModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="quickCustomerForm">
        <?= csrfField() ?>
        <div class="modal-header">
          <h5 class="modal-title fw-bold">Quick Customer Creation</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label-pg">Customer / Client Name *</label>
            <input type="text" name="name" class="form-control form-control-pg" required>
          </div>
          <div class="mb-3">
            <label class="form-label-pg">Company Name</label>
            <input type="text" name="company" class="form-control form-control-pg">
          </div>
          <div class="mb-3">
            <label class="form-label-pg">Email Address</label>
            <input type="email" name="email" class="form-control form-control-pg">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-pg-primary">Create Customer</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
