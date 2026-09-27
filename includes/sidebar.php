<?php
/**
 * PaperGlow Billing System - Sidebar Component
 */

declare(strict_types=1);

$currentUri = $_SERVER['REQUEST_URI'] ?? '';
function isNavActive(string $segment, string $currentUri): string {
    return str_contains($currentUri, $segment) ? 'active' : '';
}
?>
<aside class="pg-sidebar">
  <div class="pg-sidebar-header d-flex align-items-center justify-content-between">
    <a href="/dashboard/index.php" class="d-flex align-items-center text-decoration-none gap-2">
      <?php if (!empty($company['logo']) && file_exists(dirname(__DIR__) . '/' . ltrim($company['logo'], '/'))): ?>
        <img src="/<?= e($company['logo']) ?>" alt="<?= e($company['company_name'] ?? 'PaperGlow') ?>" style="max-height: 38px; max-width: 140px; object-fit: contain;">
      <?php else: ?>
        <div class="pg-brand-emblem">P</div>
        <div class="pg-brand-text">Paper<span>Glow</span></div>
      <?php endif; ?>
    </a>
  </div>

  <nav class="pg-sidebar-nav">
    <div class="pg-nav-label">Core Overview</div>
    <a href="/dashboard/index.php" class="pg-nav-item <?= isNavActive('/dashboard', $currentUri) ?>">
      <i class="bi bi-grid-1x2"></i>
      <span>Dashboard</span>
    </a>

    <div class="pg-nav-label">Sales & Documents</div>
    <a href="/invoices/index.php" class="pg-nav-item <?= isNavActive('/invoices', $currentUri) ?>">
      <i class="bi bi-receipt"></i>
      <span>Invoices</span>
    </a>
    <a href="/quotations/index.php" class="pg-nav-item <?= isNavActive('/quotations', $currentUri) ?>">
      <i class="bi bi-file-earmark-text"></i>
      <span>Quotations</span>
    </a>
    <a href="/payments/index.php" class="pg-nav-item <?= isNavActive('/payments', $currentUri) ?>">
      <i class="bi bi-wallet2"></i>
      <span>Payments</span>
    </a>

    <div class="pg-nav-label">Relationships</div>
    <a href="/customers/index.php" class="pg-nav-item <?= isNavActive('/customers', $currentUri) ?>">
      <i class="bi bi-people"></i>
      <span>Customers</span>
    </a>

    <div class="pg-nav-label">Intelligence</div>
    <a href="/reports/index.php" class="pg-nav-item <?= isNavActive('/reports', $currentUri) ?>">
      <i class="bi bi-bar-chart"></i>
      <span>Financial Reports</span>
    </a>
    <a href="/company/settings.php" class="pg-nav-item <?= isNavActive('/company', $currentUri) ?>">
      <i class="bi bi-gear"></i>
      <span>Company Settings</span>
    </a>
  </nav>

  <div class="pg-sidebar-footer">
    <div class="d-flex align-items-center justify-content-between text-muted small">
      <div class="d-flex align-items-center gap-2">
        <div class="rounded-circle bg-warning text-dark fw-bold d-flex align-items-center justify-content-center" style="width: 28px; height: 28px; font-size: 0.75rem;">
          <?= strtoupper(substr($currentUser['name'] ?? 'U', 0, 1)) ?>
        </div>
        <div class="text-truncate" style="max-width: 130px;">
          <div class="text-light fw-medium text-truncate"><?= e($currentUser['name'] ?? 'User') ?></div>
          <div class="text-slate-400" style="font-size: 0.7rem;"><?= e($currentUser['email'] ?? '') ?></div>
        </div>
      </div>
      <a href="/auth/logout.php" class="text-secondary hover-white" title="Sign Out">
        <i class="bi bi-box-arrow-right fs-5"></i>
      </a>
    </div>
  </div>
</aside>
