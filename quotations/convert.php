<?php
/**
 * PaperGlow Billing System - Quotation to Invoice Conversion Engine
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /quotations/index.php');
    exit;
}

if (!verifyCsrfToken()) {
    setFlash('danger', 'Security token expired. Please retry conversion.');
    header('Location: /quotations/index.php');
    exit;
}

$userId = getCurrentUserId();
$pdo = getDBConnection();
$quotationId = (int)($_POST['quotation_id'] ?? 0);

// 1. Verify quotation exists and belongs to logged-in user
$stmt = $pdo->prepare("SELECT * FROM quotations WHERE id = ? AND user_id = ?");
$stmt->execute([$quotationId, $userId]);
$quotation = $stmt->fetch();

if (!$quotation) {
    setFlash('danger', 'Quotation record not found or access denied.');
    header('Location: /quotations/index.php');
    exit;
}

// 2. Prevent accidental duplicate conversions
if (!empty($quotation['converted_invoice_id'])) {
    setFlash('warning', 'This quotation has already been converted to Invoice #' . $quotation['converted_invoice_id'] . '.');
    header('Location: /invoices/view.php?id=' . $quotation['converted_invoice_id']);
    exit;
}

// 3. Fetch quotation items
$itemsStmt = $pdo->prepare("SELECT * FROM quotation_items WHERE quotation_id = ? ORDER BY sort_order ASC, id ASC");
$itemsStmt->execute([$quotationId]);
$items = $itemsStmt->fetchAll();

if (empty($items)) {
    setFlash('danger', 'Cannot convert an empty quotation with no line items.');
    header('Location: /quotations/view.php?id=' . $quotationId);
    exit;
}

// 4. Server-side totals recalculation (never trust stored totals blindly)
$subtotal = 0.0;
$discountTotal = 0.0;
$taxTotal = 0.0;
$grandTotal = 0.0;

foreach ($items as $item) {
    $qty = (float)$item['quantity'];
    $price = (float)$item['unit_price'];
    $disc = (float)$item['discount'];
    $taxRate = (float)$item['tax_rate'];

    $base = max(0.0, ($qty * $price) - $disc);
    $taxAmount = round(($base * $taxRate) / 100.0, 2);
    $lineTotal = round($base + $taxAmount, 2);

    $subtotal += round($qty * $price, 2);
    $discountTotal += $disc;
    $taxTotal += $taxAmount;
    $grandTotal += $lineTotal;
}

// 5. Generate unique invoice number
$invoiceNumber = generateDocumentNumber($pdo, 'invoice', $userId);
$invoiceDate = date('Y-m-d');
$dueDate = date('Y-m-d', strtotime('+30 days'));

try {
    $pdo->beginTransaction();

    // Create Invoice
    $insInv = $pdo->prepare("
        INSERT INTO invoices (
            user_id, customer_id, from_quotation_id, invoice_number, invoice_date, due_date,
            status, subtotal, discount_total, tax_total, grand_total, paid_amount, balance, notes, terms
        ) VALUES (?, ?, ?, ?, ?, ?, 'Unpaid', ?, ?, ?, ?, 0.00, ?, ?, ?)
    ");

    $notes = 'Converted from Quotation ' . $quotation['quotation_number'] . ($quotation['notes'] ? "\n" . $quotation['notes'] : '');
    $terms = $quotation['terms'] ?: ($company['default_invoice_terms'] ?? 'Payment due within 30 days.');

    $insInv->execute([
        $userId,
        $quotation['customer_id'],
        $quotationId,
        $invoiceNumber,
        $invoiceDate,
        $dueDate,
        $subtotal,
        $discountTotal,
        $taxTotal,
        $grandTotal,
        $grandTotal, // initial balance = grand total
        $notes,
        $terms
    ]);

    $invoiceId = (int)$pdo->lastInsertId();

    // Copy items into invoice_items
    $insItem = $pdo->prepare("
        INSERT INTO invoice_items (
            invoice_id, description, quantity, unit_price, discount, tax_rate, tax_amount, line_total, sort_order
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $sort = 1;
    foreach ($items as $item) {
        $qty = (float)$item['quantity'];
        $price = (float)$item['unit_price'];
        $disc = (float)$item['discount'];
        $taxRate = (float)$item['tax_rate'];
        $base = max(0.0, ($qty * $price) - $disc);
        $taxAmount = round(($base * $taxRate) / 100.0, 2);
        $lineTotal = round($base + $taxAmount, 2);

        $insItem->execute([
            $invoiceId,
            $item['description'],
            $qty,
            $price,
            $disc,
            $taxRate,
            $taxAmount,
            $lineTotal,
            $sort++
        ]);
    }

    // Mark quotation as Accepted and link converted invoice
    $upQuo = $pdo->prepare("
        UPDATE quotations 
        SET status = 'Accepted', converted_invoice_id = ?, updated_at = CURRENT_TIMESTAMP 
        WHERE id = ? AND user_id = ?
    ");
    $upQuo->execute([$invoiceId, $quotationId, $userId]);

    $pdo->commit();

    setFlash('success', 'Quotation successfully converted to Invoice ' . $invoiceNumber . '!');
    header('Location: /invoices/view.php?id=' . $invoiceId);
    exit;

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    setFlash('danger', 'Failed to convert quotation: ' . $e->getMessage());
    header('Location: /quotations/view.php?id=' . $quotationId);
    exit;
}
