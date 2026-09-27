<?php
/**
 * PaperGlow Billing System - Professional Invoice PDF Generator
 * Powered by Dompdf
 * 
 * Composer installation command:
 * composer require dompdf/dompdf
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
requireAuth();

require_once __DIR__ . '/../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$userId = getCurrentUserId();
$pdo = getDBConnection();
$invoiceId = (int)($_GET['id'] ?? 0);

// Prevent users from generating documents belonging to another user
$stmt = $pdo->prepare("
    SELECT i.*, c.name as customer_name, c.company as customer_company, c.email as customer_email, 
           c.phone as customer_phone, c.address as customer_address, c.tax_number as customer_tax
    FROM invoices i
    JOIN customers c ON i.customer_id = c.id
    WHERE i.id = ? AND i.user_id = ?
");
$stmt->execute([$invoiceId, $userId]);
$invoice = $stmt->fetch();

if (!$invoice) {
    http_response_code(404);
    die('Invoice not found or access denied.');
}

$company = getCompanySettings($pdo, $userId);
$currency = $company['currency'] ?? 'USD';

$itemStmt = $pdo->prepare("SELECT * FROM invoice_items WHERE invoice_id = ? ORDER BY sort_order ASC, id ASC");
$itemStmt->execute([$invoiceId]);
$items = $itemStmt->fetchAll();

// Prepare company logo as base64 data URI for reliable Dompdf rendering
$logoBase64 = '';
if (!empty($company['logo'])) {
    $logoFullPath = dirname(__DIR__) . '/' . ltrim($company['logo'], '/');
    if (file_exists($logoFullPath) && is_file($logoFullPath)) {
        $ext = strtolower(pathinfo($logoFullPath, PATHINFO_EXTENSION));
        $mime = $ext === 'svg' ? 'image/svg+xml' : ($ext === 'png' ? 'image/png' : 'image/jpeg');
        $raw = @file_get_contents($logoFullPath);
        if ($raw !== false) {
            $logoBase64 = 'data:' . $mime . ';base64,' . base64_encode($raw);
        }
    }
}

// Construct clean HTML for Dompdf
ob_start();
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Invoice <?= e($invoice['invoice_number']) ?></title>
<style>
  @page {
    margin: 36pt 40pt 50pt 40pt;
    size: a4 portrait;
  }
  body {
    font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
    color: #1e293b;
    font-size: 10pt;
    line-height: 1.4;
  }
  .header-table {
    width: 100%;
    margin-bottom: 24pt;
    border-bottom: 2pt solid #f1f5f9;
    padding-bottom: 16pt;
  }
  .brand-logo {
    display: inline-block;
    background-color: #f59e0b;
    color: #ffffff;
    font-weight: bold;
    font-size: 14pt;
    padding: 6pt 10pt;
    border-radius: 4pt;
    margin-right: 6pt;
  }
  .brand-title {
    font-size: 15pt;
    font-weight: bold;
    color: #0f172a;
    display: inline-block;
    vertical-align: middle;
  }
  .doc-title {
    font-size: 20pt;
    font-weight: bold;
    color: #0f172a;
    text-transform: uppercase;
    letter-spacing: 1pt;
    text-align: right;
    margin: 0;
  }
  .doc-number {
    font-size: 12pt;
    font-weight: bold;
    color: #d97706;
    text-align: right;
    margin-top: 3pt;
  }
  .meta-text {
    font-size: 9pt;
    color: #64748b;
    text-align: right;
    margin-top: 2pt;
  }
  .party-table {
    width: 100%;
    margin-bottom: 20pt;
  }
  .party-box {
    width: 50%;
    vertical-align: top;
  }
  .party-heading {
    font-size: 8pt;
    font-weight: bold;
    text-transform: uppercase;
    color: #94a3b8;
    margin-bottom: 4pt;
  }
  .party-name {
    font-size: 11pt;
    font-weight: bold;
    color: #0f172a;
  }
  .items-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 18pt;
  }
  .items-table th {
    background-color: #0f172a;
    color: #ffffff;
    font-size: 8pt;
    font-weight: bold;
    text-transform: uppercase;
    padding: 7pt 8pt;
    text-align: left;
  }
  .items-table th.text-right, .items-table td.text-right {
    text-align: right;
  }
  .items-table td {
    padding: 8pt 8pt;
    border-bottom: 1pt solid #e2e8f0;
    font-size: 9.5pt;
  }
  .items-table tr:nth-child(even) td {
    background-color: #f8fafc;
  }
  .totals-table {
    width: 45%;
    margin-left: auto;
    border-collapse: collapse;
    margin-bottom: 20pt;
  }
  .totals-table td {
    padding: 4pt 6pt;
    font-size: 9.5pt;
  }
  .totals-table td.label {
    color: #64748b;
  }
  .totals-table td.val {
    text-align: right;
    font-weight: 500;
  }
  .totals-table tr.grand-row td {
    border-top: 1.5pt solid #0f172a;
    border-bottom: 1.5pt solid #0f172a;
    padding-top: 6pt;
    padding-bottom: 6pt;
    font-weight: bold;
    font-size: 12pt;
    color: #0f172a;
  }
  .totals-table tr.balance-row td {
    font-weight: bold;
    font-size: 11pt;
    color: #d97706;
    padding-top: 4pt;
  }
  .payment-box {
    background-color: #f8fafc;
    border: 1pt solid #e2e8f0;
    border-radius: 4pt;
    padding: 10pt;
    margin-bottom: 16pt;
  }
  .payment-box-title {
    font-size: 9pt;
    font-weight: bold;
    color: #0f172a;
    text-transform: uppercase;
    margin-bottom: 4pt;
  }
  .notes-box {
    font-size: 8.5pt;
    color: #475569;
    margin-bottom: 10pt;
  }
  .footer {
    position: fixed;
    bottom: -25pt;
    left: 0;
    right: 0;
    text-align: center;
    font-size: 8pt;
    color: #94a3b8;
    border-top: 1pt solid #e2e8f0;
    padding-top: 6pt;
  }
</style>
</head>
<body>

<div class="footer">
  PaperGlow Billing System &bull; Invoice <?= e($invoice['invoice_number']) ?> &bull; Thank you for your business!
</div>

<!-- Header -->
<table class="header-table">
  <tr>
    <td style="vertical-align: top; width: 60%;">
      <?php if (!empty($logoBase64)): ?>
        <img src="<?= $logoBase64 ?>" style="max-height: 44pt; max-width: 170pt; margin-bottom: 4pt;"><br>
      <?php else: ?>
        <span class="brand-logo">P</span>
        <span class="brand-title"><?= e($company['company_name'] ?? 'PaperGlow Studio LLC') ?></span>
      <?php endif; ?>
      <div style="margin-top: 6pt; font-size: 9pt; color: #475569;">
        <?= nl2br(e($company['address'] ?? '')) ?><br>
        <?php if (!empty($company['phone'])): ?>Phone: <?= e($company['phone']) ?> &bull; <?php endif; ?>
        <?php if (!empty($company['email'])): ?>Email: <?= e($company['email']) ?><?php endif; ?><br>
        <?php if (!empty($company['tax_number'])): ?>Tax Registration / VAT: <?= e($company['tax_number']) ?><?php endif; ?>
      </div>
    </td>
    <td style="vertical-align: top; width: 40%;">
      <div class="doc-title">TAX INVOICE</div>
      <div class="doc-number"><?= e($invoice['invoice_number']) ?></div>
      <div class="meta-text">Issue Date: <strong><?= e(date('M d, Y', strtotime($invoice['invoice_date']))) ?></strong></div>
      <div class="meta-text">Due Date: <strong><?= e(date('M d, Y', strtotime($invoice['due_date']))) ?></strong></div>
      <div class="meta-text">Status: <strong><?= e($invoice['status']) ?></strong></div>
    </td>
  </tr>
</table>

<!-- Client Info -->
<table class="party-table">
  <tr>
    <td class="party-box">
      <div class="party-heading">Invoice To:</div>
      <div class="party-name"><?= e($invoice['customer_name']) ?></div>
      <?php if (!empty($invoice['customer_company'])): ?>
        <div style="font-weight: bold; color: #334155; font-size: 9.5pt;"><?= e($invoice['customer_company']) ?></div>
      <?php endif; ?>
      <div style="color: #475569; font-size: 9pt; margin-top: 3pt;">
        <?= nl2br(e($invoice['customer_address'] ?? '')) ?><br>
        <?php if (!empty($invoice['customer_email'])): ?>Email: <?= e($invoice['customer_email']) ?><br><?php endif; ?>
        <?php if (!empty($invoice['customer_tax'])): ?>Tax ID: <?= e($invoice['customer_tax']) ?><?php endif; ?>
      </div>
    </td>
  </tr>
</table>

<!-- Items Table -->
<table class="items-table">
  <thead>
    <tr>
      <th style="width: 48%;">Description</th>
      <th class="text-right" style="width: 10%;">Qty</th>
      <th class="text-right" style="width: 14%;">Unit Price</th>
      <th class="text-right" style="width: 12%;">Discount</th>
      <th class="text-right" style="width: 16%;">Amount</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($items as $item): ?>
      <tr>
        <td><strong><?= e($item['description']) ?></strong></td>
        <td class="text-right"><?= number_format((float)$item['quantity'], 2) ?></td>
        <td class="text-right"><?= formatCurrency($item['unit_price'], $currency) ?></td>
        <td class="text-right"><?= (float)$item['discount'] > 0 ? '-' . formatCurrency($item['discount'], $currency) : '—' ?></td>
        <td class="text-right"><strong><?= formatCurrency($item['line_total'], $currency) ?></strong></td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<!-- Totals -->
<table class="totals-table">
  <tr>
    <td class="label">Subtotal:</td>
    <td class="val"><?= formatCurrency($invoice['subtotal'], $currency) ?></td>
  </tr>
  <?php if ((float)$invoice['discount_total'] > 0): ?>
    <tr>
      <td class="label">Discount:</td>
      <td class="val" style="color: #ef4444;">-<?= formatCurrency($invoice['discount_total'], $currency) ?></td>
    </tr>
  <?php endif; ?>
  <?php if ((float)$invoice['tax_total'] > 0): ?>
    <tr>
      <td class="label">Tax Amount:</td>
      <td class="val"><?= formatCurrency($invoice['tax_total'], $currency) ?></td>
    </tr>
  <?php endif; ?>
  <tr class="grand-row">
    <td>Total Due:</td>
    <td class="val"><?= formatCurrency($invoice['grand_total'], $currency) ?></td>
  </tr>
  <tr>
    <td class="label">Amount Paid:</td>
    <td class="val" style="color: #10b981;"><?= formatCurrency($invoice['paid_amount'], $currency) ?></td>
  </tr>
  <tr class="balance-row">
    <td>Balance Due:</td>
    <td class="val"><?= formatCurrency($invoice['balance'], $currency) ?></td>
  </tr>
</table>

<!-- Payment Remittance Box -->
<div class="payment-box">
  <div class="payment-box-title">Remittance & Payment Details</div>
  <table style="width: 100%; font-size: 8.5pt;">
    <tr>
      <td style="width: 50%; vertical-align: top;">
        <strong>Bank Transfer:</strong><br>
        Bank: <?= e($company['bank_name'] ?: 'Cascade Horizon Bank') ?><br>
        Account Name: <?= e($company['bank_account_name'] ?: $company['company_name']) ?><br>
        Account Number: <strong><?= e($company['bank_account_number'] ?: '884920491029') ?></strong><br>
        Routing / Sort: <?= e($company['bank_routing'] ?: '125000024') ?>
      </td>
      <td style="width: 50%; vertical-align: top;">
        <strong>Mobile Money / Digital:</strong><br>
        Provider: <?= e($company['mobile_money_name'] ?: 'PaperGlow Pay') ?><br>
        Number: <strong><?= e($company['mobile_money_number'] ?: '+1 (206) 555-0199') ?></strong><br>
        Ref / Memo: <strong><?= e($invoice['invoice_number']) ?></strong>
      </td>
    </tr>
  </table>
</div>

<!-- Notes & Terms -->
<?php if (!empty($invoice['notes'])): ?>
  <div class="notes-box">
    <strong>Notes:</strong> <?= nl2br(e($invoice['notes'])) ?>
  </div>
<?php endif; ?>

<?php if (!empty($invoice['terms'])): ?>
  <div class="notes-box">
    <strong>Terms & Conditions:</strong> <?= nl2br(e($invoice['terms'])) ?>
  </div>
<?php endif; ?>

</body>
</html>
<?php
$html = ob_get_clean();

// Configure Dompdf
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', false);
$options->set('defaultFont', 'Helvetica');

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// Stream PDF inline or attachment
$filename = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $invoice['invoice_number']) . '.pdf';
$dompdf->stream($filename, ['Attachment' => false]);
exit;
