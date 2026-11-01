<?php
require_once __DIR__ . '/../includes/admin-auth.php';
$adminActive = 'inventory';
$adminTitle = 'Inventory';
include __DIR__ . '/../includes/admin-header.php';
?>
<section class="admin-page-heading"><div><p class="admin-eyebrow">STOCK CONTROL / BY SIZE</p><h1>Inventory atelier.</h1><p>Monitor fragrance variants and safely adjust stock through the existing catalog API.</p></div><a href="products.php" class="admin-button admin-button-soft">Product catalog</a></section>
<div class="studio-toolbar"><label class="studio-search-label">Search products<input type="search" placeholder="Search fragrance or house…" data-inventory-search></label><label>Stock status<select data-inventory-filter><option value="">All products</option><option value="low">Has low stock variant</option></select></label><button type="button" class="admin-button admin-button-soft" data-inventory-refresh>Refresh</button></div>
<div class="admin-error hidden" role="alert" data-inventory-error></div>
<p class="studio-note">Product stock totals come from the catalog. Open a product to inspect and edit its individual bottle sizes. Low stock is 5 units or fewer.</p>
<div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Fragrance / house</th><th>Total stock</th><th>Variants</th><th>Stock health</th><th>Details</th></tr></thead><tbody data-inventory-rows><tr><td colspan="5" class="admin-loading">Loading stock…</td></tr></tbody></table></div>
<div class="admin-pagination" data-inventory-pagination></div>
<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
