<?php
/**
 * PaperGlow Billing System - Core Helper Functions
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

/**
 * Escapes string for safe HTML output.
 */
function e(?string $string): string
{
    return htmlspecialchars((string)($string ?? ''), ENT_QUOTES, 'UTF-8');
}

/**
 * Get display symbol for currency.
 */
function getCurrencySymbol(string $currency = 'KES'): string
{
    $symbols = [
        'KES' => 'KSh ',
        'USD' => '$',
        'EUR' => '€',
        'GBP' => '£',
        'CAD' => 'CA$',
        'AUD' => 'A$',
        'NGN' => '₦',
        'GHS' => 'GH₵',
        'ZAR' => 'R ',
        'INR' => '₹',
        'JPY' => '¥',
    ];
    return $symbols[$currency] ?? ($currency . ' ');
}

/**
 * Format currency amount with currency symbol or code.
 */
function formatCurrency(float|int|string $amount, string $currency = 'KES'): string
{
    $val = (float)$amount;
    $sym = getCurrencySymbol($currency);
    return $sym . number_format($val, 2, '.', ',');
}

/**
 * Clean and sanitize user input.
 */
function sanitize(mixed $data): mixed
{
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return is_string($data) ? trim($data) : $data;
}

/**
 * Generate CSRF token if not present in session.
 */
function getCsrfToken(): string
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Render hidden CSRF token input field.
 */
function csrfField(): string
{
    $token = getCsrfToken();
    return '<input type="hidden" name="csrf_token" value="' . e($token) . '">';
}

/**
 * Verify CSRF token from POST request.
 */
function verifyCsrfToken(?string $token = null): bool
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $token = $token ?? ($_POST['csrf_token'] ?? '');
    return !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Store flash message for next page render.
 */
function setFlash(string $type, string $message): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['flash'] = [
        'type'    => $type, // success, danger, warning, info
        'message' => $message,
    ];
}

/**
 * Retrieve and clear flash message.
 */
function getFlash(): ?array
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!empty($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Retrieve Company Settings for a given user.
 */
function getCompanySettings(PDO $pdo, int $userId): array
{
    $stmt = $pdo->prepare("SELECT * FROM company_settings WHERE user_id = ? LIMIT 1");
    $stmt->execute([$userId]);
    $settings = $stmt->fetch();

    if (!$settings) {
        // Return default stub
        return [
            'company_name'           => 'PaperGlow Billing',
            'logo'                   => null,
            'address'                => '',
            'phone'                  => '',
            'email'                  => '',
            'website'                => '',
            'tax_number'             => '',
            'currency'               => 'KES',
            'bank_name'              => '',
            'bank_account_name'      => '',
            'bank_account_number'    => '',
            'bank_routing'           => '',
            'bank_swift'             => '',
            'mobile_money_name'      => '',
            'mobile_money_number'    => '',
            'default_invoice_terms'  => 'Payment due within 30 days of issue.',
            'default_quotation_terms'=> 'Valid for 30 calendar days.',
        ];
    }

    return $settings;
}

/**
 * Generates an auto-incrementing document number formatted as PREFIX-YYYY-0001
 * Example: INV-2026-0001, QUO-2026-0001
 */
function generateDocumentNumber(PDO $pdo, string $type, int $userId): string
{
    $prefix = $type === 'invoice' ? 'INV' : 'QUO';
    $year = date('Y');
    $pattern = $prefix . '-' . $year . '-%';

    $table = $type === 'invoice' ? 'invoices' : 'quotations';
    $column = $type === 'invoice' ? 'invoice_number' : 'quotation_number';

    $stmt = $pdo->prepare("SELECT $column FROM $table WHERE user_id = ? AND $column LIKE ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$userId, $pattern]);
    $lastNumber = $stmt->fetchColumn();

    $nextSeq = 1;
    if ($lastNumber) {
        $parts = explode('-', (string)$lastNumber);
        if (count($parts) >= 3) {
            $nextSeq = (int)end($parts) + 1;
        }
    }

    return sprintf('%s-%s-%04d', $prefix, $year, $nextSeq);
}

/**
 * Return styled badge HTML for document status.
 */
function getStatusBadge(string $status): string
{
    $map = [
        'Draft'          => 'bg-secondary-subtle text-secondary border border-secondary-subtle',
        'Sent'           => 'bg-info-subtle text-info border border-info-subtle',
        'Accepted'       => 'bg-success-subtle text-success border border-success-subtle',
        'Rejected'       => 'bg-danger-subtle text-danger border border-danger-subtle',
        'Expired'        => 'bg-warning-subtle text-warning border border-warning-subtle',
        'Unpaid'         => 'bg-warning-subtle text-warning border border-warning-subtle',
        'Partially Paid' => 'bg-primary-subtle text-primary border border-primary-subtle',
        'Paid'           => 'bg-success-subtle text-success border border-success-subtle',
        'Overdue'        => 'bg-danger-subtle text-danger border border-danger-subtle',
        'Cancelled'      => 'bg-dark-subtle text-dark border border-dark-subtle',
    ];

    $badgeClass = $map[$status] ?? 'bg-light text-dark';
    return '<span class="badge ' . $badgeClass . ' px-2.5 py-1 font-medium rounded-pill">' . e($status) . '</span>';
}

/**
 * Recalculate invoice payments, balance, and determine appropriate status.
 */
function recalculateInvoiceBalance(PDO $pdo, int $invoiceId): void
{
    // Retrieve invoice total and due date
    $stmt = $pdo->prepare("SELECT grand_total, due_date, status FROM invoices WHERE id = ?");
    $stmt->execute([$invoiceId]);
    $invoice = $stmt->fetch();
    if (!$invoice) {
        return;
    }

    $grandTotal = (float)$invoice['grand_total'];
    $dueDate = $invoice['due_date'];

    // Sum all payments for this invoice
    $stmtPay = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE invoice_id = ?");
    $stmtPay->execute([$invoiceId]);
    $totalPaid = (float)$stmtPay->fetchColumn();

    $balance = max(0.00, round($grandTotal - $totalPaid, 2));

    // Determine status
    $newStatus = 'Unpaid';
    if ($balance <= 0.0001 && $grandTotal > 0) {
        $newStatus = 'Paid';
    } elseif ($totalPaid > 0 && $balance > 0) {
        $newStatus = 'Partially Paid';
    } else {
        $today = date('Y-m-d');
        if ($dueDate < $today && $totalPaid == 0) {
            $newStatus = 'Overdue';
        } else {
            $newStatus = 'Unpaid';
        }
    }

    $updateStmt = $pdo->prepare("
        UPDATE invoices 
        SET paid_amount = ?, balance = ?, status = ?, updated_at = CURRENT_TIMESTAMP 
        WHERE id = ?
    ");
    $updateStmt->execute([$totalPaid, $balance, $newStatus, $invoiceId]);
}
