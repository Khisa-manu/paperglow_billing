<?php
/**
 * PaperGlow Billing System - Main Layout Header
 */

declare(strict_types=1);

require_once __DIR__ . '/auth.php';
requireAuth();

$currentUser = getCurrentUser();
$pdo = getDBConnection();
$company = getCompanySettings($pdo, getCurrentUserId());
$pageTitle = $pageTitle ?? 'Dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($pageTitle) ?> — PaperGlow Billing</title>
  
  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
  
  <!-- Bootstrap 5 & Bootstrap Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  
  <!-- PaperGlow Original Stylesheet -->
  <link rel="stylesheet" href="/assets/css/paperglow.css">
</head>
<body>

<div class="pg-layout">
  <?php require_once __DIR__ . '/sidebar.php'; ?>
  
  <div class="pg-main-wrapper">
    <?php require_once __DIR__ . '/navbar.php'; ?>
    
    <main class="pg-content-body">
      <?php 
      $flash = getFlash(); 
      if ($flash): 
      ?>
        <div class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show d-flex align-items-center mb-4" role="alert">
          <i class="bi bi-info-circle-fill me-2 fs-5"></i>
          <div><?= e($flash['message']) ?></div>
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
      <?php endif; ?>
