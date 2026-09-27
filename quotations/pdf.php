<?php
/**
 * PaperGlow Billing System - Professional Quotation PDF Generator
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
$quotationId = (int)($_GET['id'] ?? 0);

// Prevent users from generating documents belonging to another user
$stmt = $pdo->prepare("
    SELECT q.*, c.name as customer_name, c.company as customer_company, c.email as customer_email, 
           c.phone as customer_phone, c.address as customer_address, c.tax_number as customer_tax
    FROM quotations q
    JOIN customers c ON q.customer_id = c.id
    WHERE q.id = ? AND q.user_id = ?
");
$stmt->execute([$quotationId, $userId]);
$quotation = $stmt->fetch();

if (!$quotation) {
    http_response_code(404);
    die('Quotation not found or access denied.');
}

$company = getCompanySettings($pdo, $userId);
$currency = $company['currency'] ?? 'USD';

$itemStmt = $pdo->prepare("SELECT * FROM quotation_items WHERE quotation_id = ? ORDER BY sort_order ASC, id ASC");
$itemStmt->execute([$quotationId]);
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

ob_start();
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Quotation <?= e($quotation['quotation_number']) ?></title>
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
  PaperGlow Billing System &bull; Quotation <?= e($quotation['quotation_number']) ?> &bull; Page 1 of 1
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
        <?php if (!empty($company['email'])): ?>Email: <?= e($company['email']) ?><?php endif; ?>
      </div>
    </td>
    <td style="vertical-align: top; width: 40%;">
      <div class="doc-title">QUOTATION</div>
      <div class="doc-number"><?= e($quotation['quotation_number']) ?></div>
      <div class="meta-text">Proposal Date: <strong><?= e(date('M d, Y', strtotime($quotation['quotation_date']))) ?></strong></div>
      <div class="meta-text">Valid Until: <strong><?= e(date('M d, Y', strtotime($quotation['expiry_date']))) ?></strong></div>
      <div class="meta-text">Status: <strong><?= e($quotation['status']) ?></strong></div>
    </td>
  </tr>
</table>

<!-- Client Info -->
<table class="party-table">
  <tr>
    <td class="party-box">
      <div class="party-heading">Quotation Prepared For:</div>
      <div class="party-name"><?= e($quotation['customer_name']) ?></div>
      <?php if (!empty($quotation['customer_company'])): ?>
        <div style="font-weight: bold; color: #334155; font-size: 9.5pt;"><?= e($quotation['customer_company']) ?></div>
      <?php endif; ?>
      <div style="color: #475569; font-size: 9pt; margin-top: 3pt;">
        <?= nl2br(e($quotation['customer_address'] ?? '')) ?><br>
        <?php if (!empty($quotation['customer_email'])): ?>Email: <?= e($quotation['customer_email']) ?><br><?php endif; ?>
        <?php if (!empty($quotation['customer_tax'])): ?>Tax ID: <?= e($quotation['customer_tax']) ?><?php endif; ?>
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
    <td class="val"><?= formatCurrency($quotation['subtotal'], $currency) ?></td>
  </tr>
  <?php if ((float)$quotation['discount_total'] > 0): ?>
    <tr>
      <td class="label">Discount:</td>
      <td class="val" style="color: #ef4444;">-<?= formatCurrency($quotation['discount_total'], $currency) ?></td>
    </tr>
  <?php endif; ?>
  <?php if ((float)$quotation['tax_total'] > 0): ?>
    <tr>
      <td class="label">Tax Amount:</td>
      <td class="val"><?= formatCurrency($quotation['tax_total'], $currency) ?></td>
    </tr>
  <?php endif; ?>
  <tr class="grand-row">
    <td>Estimated Total:</td>
    <td class="val"><?= formatCurrency($quotation['grand_total'], $currency) ?></td>
  </tr>
</table>

<!-- Notes & Terms -->
<?php if (!empty($quotation['notes'])): ?>
  <div class="notes-box">
    <strong>Scope & Notes:</strong> <?= nl2br(e($quotation['notes'])) ?>
  </div>
<?php endif; ?>

<?php if (!empty($quotation['terms'])): ?>
  <div class="notes-box">
    <strong>Terms & Validity:</strong> <?= nl2br(e($quotation['terms'])) ?>
  </div>
<?php endif; ?>

</body>
</html>
<?php
$html = ob_get_clean();

$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', false);
$options->set('defaultFont', 'Helvetica');

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$filename = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $quotation['quotation_number']) . '.pdf';
$dompdf->stream($filename, ['Attachment' => false]);
exit;
