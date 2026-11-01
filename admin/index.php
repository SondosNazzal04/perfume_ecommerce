<?php
require_once __DIR__ . '/../includes/admin-auth.php';
$adminActive = 'dashboard';
$adminTitle = 'Overview';
include __DIR__ . '/../includes/admin-header.php';
?>
<section class="admin-page-heading"><div><p class="admin-eyebrow">YOUR STORE, AT A GLANCE</p><h1>Your store, in focus.</h1><p>Here’s what’s happening at VELORA today.</p></div><button class="admin-button admin-button-soft" type="button" data-refresh-dashboard>↻ &nbsp; Refresh</button></section>
<div class="admin-error hidden" data-dashboard-error role="alert"></div>
<section class="admin-stat-grid" aria-label="Store statistics">
  <article class="admin-stat-card admin-stat-customers"><div class="admin-stat-top"><span>Customers</span><span class="admin-stat-icon">○</span></div><strong data-stat="customers">—</strong><small>Registered customer accounts</small></article>
  <article class="admin-stat-card admin-stat-products"><div class="admin-stat-top"><span>Active products</span><span class="admin-stat-icon">◇</span></div><strong data-stat="products">—</strong><small>Available in your storefront</small></article>
  <article class="admin-stat-card admin-stat-delivered"><div class="admin-stat-top"><span>Delivered orders</span><span class="admin-stat-icon">✓</span></div><strong data-stat="delivered_orders">—</strong><small>Successfully completed orders</small></article>
  <article class="admin-stat-card admin-stat-revenue"><div class="admin-stat-top"><span>Collected revenue</span><span class="admin-stat-icon">JD</span></div><strong data-stat="revenue">—</strong><small>Payments marked completed · JOD</small></article>
</section>
<section class="admin-stat-grid admin-stat-grid-secondary">
  <article class="admin-mini-stat"><span>Open orders</span><strong data-stat="open_orders">—</strong><a href="orders.php">Review orders ↗</a></article>
  <article class="admin-mini-stat"><span>Low stock variants</span><strong data-stat="low_stock">—</strong><a href="products.php?stock=low">Review inventory ↗</a></article>
  <article class="admin-mini-stat admin-quick-links"><span>Quick actions</span><div><a class="admin-button admin-button-dark" href="products.php?new=1">＋ Add product</a><a class="admin-button admin-button-soft" href="categories.php">Manage categories</a></div></article>
</section>
<section class="admin-dashboard-grid">
  <article class="admin-panel admin-sales-panel"><div class="admin-panel-heading"><div><p class="admin-eyebrow">PAID ORDERS</p><h2>Revenue · last 7 days</h2></div><span class="admin-panel-note">JOD</span></div><div class="admin-chart" data-sales-chart><div class="admin-loading">Loading sales…</div></div></article>
  <article class="admin-panel"><div class="admin-panel-heading"><div><p class="admin-eyebrow">LATEST ACTIVITY</p><h2>Recent orders</h2></div><a href="orders.php">All orders ↗</a></div><div class="admin-recent-orders" data-recent-orders><div class="admin-loading">Loading orders…</div></div></article>
</section>
<section class="admin-panel admin-inventory-panel"><div class="admin-panel-heading"><div><p class="admin-eyebrow">STOCK CHECK</p><h2>Needs a restock</h2></div><a href="products.php?stock=low">View products ↗</a></div><div class="admin-low-stock" data-low-stock><div class="admin-loading">Checking inventory…</div></div></section>
<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
