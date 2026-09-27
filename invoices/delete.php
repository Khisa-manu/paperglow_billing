<?php
/**
 * PaperGlow Billing System - Delete Invoice
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
requireAuth();

$userId = getCurrentUserId();
$pdo = getDBConnection();
$invoiceId = (int)($_GET['id'] ?? 0);
$token = $_GET['csrf_token'] ?? ($_POST['csrf_token'] ?? '');

if (!verifyCsrfToken($token)) {
    setFlash('danger', 'Invalid security token.');
    header('Location: /invoices/index.php');
    exit;
}

$stmt = $pdo->prepare("SELECT id, invoice_number FROM invoices WHERE id = ? AND user_id = ?");
$stmt->execute([$invoiceId, $userId]);
$invoice = $stmt->fetch();

if (!$invoice) {
    setFlash('danger', 'Invoice not found.');
    header('Location: /invoices/index.php');
    exit;
}

try {
    $del = $pdo->prepare("DELETE FROM invoices WHERE id = ? AND user_id = ?");
    $del->execute([$invoiceId, $userId]);

    setFlash('success', 'Invoice ' . $invoice['invoice_number'] . ' deleted successfully.');
} catch (Exception $e) {
    setFlash('danger', 'Failed to delete invoice: ' . $e->getMessage());
}

header('Location: /invoices/index.php');
exit;
