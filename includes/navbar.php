<?php
/**
 * PaperGlow Billing System - Topbar Component
 */

declare(strict_types=1);
?>
<header class="pg-topbar">
  <div class="d-flex align-items-center gap-3">
    <button type="button" class="btn btn-outline-secondary btn-sm d-lg-none" id="pgSidebarToggle">
      <i class="bi bi-list fs-5"></i>
    </button>
    <div>
      <h1 class="h5 mb-0 fw-bold text-slate-900"><?= e($pageTitle ?? 'Overview') ?></h1>
      <span class="text-muted small"><?= e($company['company_name'] ?? 'PaperGlow Billing') ?></span>
    </div>
  </div>

  <div class="d-flex align-items-center gap-2">
    <a href="/quotations/create.php" class="btn btn-pg-secondary btn-sm d-none d-sm-inline-flex align-items-center gap-1">
      <i class="bi bi-plus-circle"></i>
      <span>Quotation</span>
    </a>
    <a href="/invoices/create.php" class="btn btn-pg-primary btn-sm d-inline-flex align-items-center gap-1">
      <i class="bi bi-plus-lg"></i>
      <span>New Invoice</span>
    </a>
  </div>
</header>
