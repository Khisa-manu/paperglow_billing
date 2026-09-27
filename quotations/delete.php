<?php
/**
 * PaperGlow Billing System - Delete Quotation
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
requireAuth();

$userId = getCurrentUserId();
$pdo = getDBConnection();
$quotationId = (int)($_GET['id'] ?? 0);
$token = $_GET['csrf_token'] ?? ($_POST['csrf_token'] ?? '');

if (!verifyCsrfToken($token)) {
    setFlash('danger', 'Invalid security token.');
    header('Location: /quotations/index.php');
    exit;
}

$stmt = $pdo->prepare("SELECT id, quotation_number, converted_invoice_id FROM quotations WHERE id = ? AND user_id = ?");
$stmt->execute([$quotationId, $userId]);
$quotation = $stmt->fetch();

if (!$quotation) {
    setFlash('danger', 'Quotation not found.');
    header('Location: /quotations/index.php');
    exit;
}

try {
    $del = $pdo->prepare("DELETE FROM quotations WHERE id = ? AND user_id = ?");
    $del->execute([$quotationId, $userId]);

    setFlash('success', 'Quotation ' . $quotation['quotation_number'] . ' deleted successfully.');
} catch (Exception $e) {
    setFlash('danger', 'Failed to delete quotation: ' . $e->getMessage());
}

header('Location: /quotations/index.php');
exit;
