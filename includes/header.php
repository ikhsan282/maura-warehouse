<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

$current_user = current_user();
$page_title   = $page_title ?? APP_NAME;
?><!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($page_title) ?> — <?= APP_NAME ?></title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
</head>
<body class="bg-light">
<?php if ($current_user): ?>
<div class="wrapper d-flex">
  <?php include __DIR__ . '/sidebar.php'; ?>
  <div class="main-content flex-grow-1">
    <!-- Top navbar -->
    <nav class="navbar navbar-expand-lg navbar-white bg-white border-bottom px-3 py-2 sticky-top shadow-sm">
      <button class="btn btn-sm btn-outline-secondary me-3 d-lg-none" id="sidebarToggleMobile">
        <i class="bi bi-list"></i>
      </button>
      <button class="btn btn-sm btn-outline-secondary me-3 d-none d-lg-inline-flex" id="sidebarToggle">
        <i class="bi bi-layout-sidebar"></i>
      </button>
      <span class="navbar-brand fw-semibold text-primary mb-0 h6"><?= htmlspecialchars($page_title) ?></span>
      <div class="ms-auto d-flex align-items-center gap-2">
        <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
          <i class="bi bi-shield-check me-1"></i><?= htmlspecialchars($current_user['role_name']) ?>
        </span>
        <div class="dropdown">
          <button class="btn btn-sm btn-light dropdown-toggle d-flex align-items-center gap-2" data-bs-toggle="dropdown">
            <div class="avatar-sm bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width:32px;height:32px;font-size:.8rem">
              <?= strtoupper(substr($current_user['name'], 0, 1)) ?>
            </div>
            <span class="d-none d-md-inline"><?= htmlspecialchars($current_user['name']) ?></span>
          </button>
          <ul class="dropdown-menu dropdown-menu-end shadow-sm">
            <li><span class="dropdown-item-text small text-muted"><?= htmlspecialchars($current_user['email']) ?></span></li>
            <li><hr class="dropdown-divider my-1"></li>
            <li><a class="dropdown-item" href="<?= APP_URL ?>/auth/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Keluar</a></li>
          </ul>
        </div>
      </div>
    </nav>
    <div class="content-area p-3 p-lg-4">
      <?= flash_html() ?>
<?php endif; ?>
