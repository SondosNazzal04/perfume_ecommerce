<?php
$adminActive = $adminActive ?? 'dashboard';
$adminTitle = $adminTitle ?? 'Dashboard';
$adminName = $_SESSION['user_name'] ?? 'Administrator';
$adminLinks = [
    ['dashboard', 'Overview', 'index.php'],
    ['products', 'Products', 'products.php'],
    ['inventory', 'Inventory', 'inventory.php'],
    ['categories', 'Categories', 'categories.php'],
    ['brands', 'Fragrance houses', 'brands.php'],
    ['orders', 'Orders', 'orders.php'],
    ['customers', 'Customers', 'customers.php'],
    ['reviews', 'Reviews', 'reviews.php'],
    ['coupons', 'Coupons', 'coupons.php']
];
$adminIcons = [
    'dashboard' => '<svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>',
    'products' => '<svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M3 9h18M8 4v16"/></svg>',
    'inventory' => '<svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7l9-4 9 4-9 4-9-4zM3 7v10l9 4 9-4V7M12 11v10"/></svg>',
    'categories' => '<svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h7v7H4zM13 4h7v7h-7zM4 13h7v7H4zM13 13h7v7h-7z"/></svg>',
    'brands' => '<svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M20 12V4H12L3 13l8 8 9-9Z"/><circle cx="16" cy="8" r="1.5"/></svg>',
    'orders' => '<svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M4 6h16l-1.3 15H5.3L4 6zM9 6V4a3 3 0 0 1 6 0v2"/></svg>',
    'customers' => '<svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="8" r="3"/><path d="M3 20v-2a6 6 0 0 1 12 0v2M17 5a3 3 0 0 1 0 6M21 20v-2a6 6 0 0 0-4-5.65"/></svg>',
    'reviews' => '<svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="m12 3 2.8 5.9 6.5.9-4.7 4.6 1.1 6.5L12 17.8 6.3 21l1.1-6.6-4.7-4.5 6.5-1L12 3z"/></svg>',
    'coupons' => '<svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M3 8a2 2 0 0 0 2-2h14v4a2 2 0 0 0 0 4v4H5a2 2 0 0 0-2-2V8Z"/><path d="M12 6v12" stroke-dasharray="2 2"/></svg>'
];
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= htmlspecialchars($adminTitle, ENT_QUOTES, 'UTF-8') ?> · VELORA Admin</title>
  <link rel="stylesheet" href="../assets/css/style.css"><link rel="stylesheet" href="../assets/css/admin.css?v=20261101-status-zebra">
  <link rel="stylesheet" href="../assets/css/velora-admin.css?v=1">
  <link rel="stylesheet" href="../assets/css/velora-admin-v2.css?v=2">
  <link rel="stylesheet" href="../assets/css/velora-admin-studio.css?v=1">
  <script defer src="../assets/js/admin-panel.js?v=20261101-order-actions"></script>
  <script defer src="../assets/js/admin-studio.js?v=1"></script>
</head>
<body class="admin-body">
<div class="admin-app">
  <aside class="admin-sidebar" id="admin-sidebar">
    <a class="admin-brand" href="index.php"><span class="admin-brand-mark">V</span><span>VELORA<small>FRAGRANCE HOUSE</small></span></a>
    <p class="admin-nav-label">COMMAND CENTER</p>
    <nav class="admin-nav" aria-label="Admin navigation">
      <?php foreach ($adminLinks as [$key, $label, $href]): ?>
        <a class="<?= $adminActive === $key ? 'active' : '' ?>" <?= $adminActive === $key ? 'aria-current="page"' : '' ?> href="<?= htmlspecialchars($href, ENT_QUOTES, 'UTF-8') ?>"><span class="admin-nav-icon"><?= $adminIcons[$key] ?></span><span><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></span></a>
      <?php endforeach; ?>
    </nav>
    <div class="admin-sidebar-bottom"><a href="../index.php">↗ <span>View storefront</span></a><button type="button" class="admin-signout" data-admin-logout>↪ <span>Sign out</span></button><span class="admin-version">VELORA / MANAGEMENT STUDIO</span></div>
  </aside>
  <div class="admin-main-wrap">
    <header class="admin-topbar"><button class="admin-menu-toggle" type="button" aria-label="Toggle navigation" aria-expanded="false">☰</button><div class="admin-breadcrumb"><span>VELORA ADMIN</span><b>/</b><strong><?= htmlspecialchars($adminTitle, ENT_QUOTES, 'UTF-8') ?></strong></div><div class="admin-user"><span class="admin-avatar"><?= htmlspecialchars(function_exists('mb_substr') ? mb_strtoupper(mb_substr($adminName, 0, 1, 'UTF-8'), 'UTF-8') : strtoupper(substr($adminName, 0, 1)), ENT_QUOTES, 'UTF-8') ?></span><span><strong><?= htmlspecialchars($adminName, ENT_QUOTES, 'UTF-8') ?></strong><small>Administrator</small></span></div></header>
    <main class="admin-main">
