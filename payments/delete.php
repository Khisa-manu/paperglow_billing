<?php
/**
 * PaperGlow Billing System - Delete Payment
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
requireAuth();

$userId = getCurrentUserId();
$pdo = getDBConnection();
$paymentId = (int)($_GET['id'] ?? 0);
$token = $_GET['csrf_token'] ?? ($_POST['csrf_token'] ?? '');

if (!verifyCsrfToken($token)) {
    setFlash('danger', 'Invalid security token.');
    header('Location: /payments/index.php');
    exit;
}

$stmt = $pdo->prepare("SELECT id, invoice_id, amount FROM payments WHERE id = ? AND user_id = ?");
$stmt->execute([$paymentId, $userId]);
$payment = $stmt->fetch();

if (!$payment) {
    setFlash('danger', 'Payment record not found.');
    header('Location: /payments/index.php');
    exit;
}

$invoiceId = (int)$payment['invoice_id'];

try {
    $pdo->beginTransaction();

    $del = $pdo->prepare("DELETE FROM payments WHERE id = ? AND user_id = ?");
    $del->execute([$paymentId, $userId]);

    // Recalculate invoice balance & update status
    recalculateInvoiceBalance($pdo, $invoiceId);

    $pdo->commit();
    setFlash('success', 'Payment deleted. Invoice balance and status have been updated.');
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    setFlash('danger', 'Failed to delete payment: ' . $e->getMessage());
}

if (!empty($_GET['redirect_to_invoice'])) {
    header('Location: /invoices/view.php?id=' . $invoiceId);
} else {
    header('Location: /payments/index.php');
}
exit;
