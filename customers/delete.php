<?php
/**
 * PaperGlow Billing System - Delete Customer
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
requireAuth();

$userId = getCurrentUserId();
$pdo = getDBConnection();
$customerId = (int)($_GET['id'] ?? 0);
$token = $_GET['csrf_token'] ?? ($_POST['csrf_token'] ?? '');

if (!verifyCsrfToken($token)) {
    setFlash('danger', 'Invalid security token for delete operation.');
    header('Location: /customers/index.php');
    exit;
}

// Verify existence and ownership
$stmt = $pdo->prepare("SELECT id, name FROM customers WHERE id = ? AND user_id = ?");
$stmt->execute([$customerId, $userId]);
$customer = $stmt->fetch();

if (!$customer) {
    setFlash('danger', 'Customer record not found.');
    header('Location: /customers/index.php');
    exit;
}

// Check for existing invoices or quotations
$checkInv = $pdo->prepare("SELECT COUNT(id) FROM invoices WHERE customer_id = ?");
$checkInv->execute([$customerId]);
$hasInvoices = (int)$checkInv->fetchColumn();

if ($hasInvoices > 0) {
    setFlash('danger', 'Cannot delete customer "' . $customer['name'] . '" because they have ' . $hasInvoices . ' existing invoice(s). Archive or delete the invoices first.');
    header('Location: /customers/view.php?id=' . $customerId);
    exit;
}

try {
    $del = $pdo->prepare("DELETE FROM customers WHERE id = ? AND user_id = ?");
    $del->execute([$customerId, $userId]);

    setFlash('success', 'Customer "' . $customer['name'] . '" deleted successfully.');
} catch (Exception $e) {
    setFlash('danger', 'Failed to delete customer: ' . $e->getMessage());
}

header('Location: /customers/index.php');
exit;
