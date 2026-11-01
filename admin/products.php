<?php
require_once __DIR__ . '/../includes/admin-auth.php';
$adminActive = 'products';
$adminTitle = 'Products';
include __DIR__ . '/../includes/admin-header.php';
?>
<section class="admin-page-heading"><div><p class="admin-eyebrow">CATALOG MANAGEMENT</p><h1>Your fragrances.</h1><p>Manage product stories, scent categories, images, sizes, pricing and inventory.</p></div><button class="admin-button admin-button-dark" type="button" data-open-new-product>＋ &nbsp; Add product</button></section>
<div class="admin-error hidden" data-product-error role="alert"></div>
<div class="admin-toolbar"><input type="search" data-product-search placeholder="Search by product or house…" aria-label="Search products"><select data-product-brand aria-label="Filter by fragrance house"><option value="">All fragrance houses</option></select><select data-product-status aria-label="Filter by status"><option value="">All products</option><option value="active">Active</option><option value="inactive">Draft / inactive</option></select><select data-product-stock aria-label="Filter by stock"><option value="">Any stock level</option><option value="low">Low stock · 5 or fewer</option></select><button class="admin-button admin-button-soft" type="button" data-reload-products>↻ &nbsp; Refresh</button></div>
<section class="studio-selection" data-bulk-toolbar aria-label="Bulk product actions"><div class="studio-selection-title"><strong data-selected-count>0 selected</strong><span>Only products on this page can be selected.</span></div><div class="studio-selection-controls"><select data-bulk-action aria-label="Choose bulk action"><option value="">Choose action</option><option value="publish">Publish selected</option><option value="unpublish">Unpublish selected</option></select><button class="admin-button admin-button-dark" type="button" data-run-bulk disabled>Apply to selected</button><button class="admin-button admin-button-soft" type="button" data-clear-bulk>Clear</button></div></section>
<div class="admin-error hidden" data-bulk-error role="alert"></div>
<div class="admin-table-wrap"><table class="admin-table"><thead><tr><th class="studio-check-col"><input type="checkbox" data-select-all-products aria-label="Select all products on current page"></th><th>Fragrance</th><th>House</th><th>Variants</th><th>Stock</th><th>From</th><th>Status</th><th>Actions</th></tr></thead><tbody data-product-rows><tr><td colspan="8"><div class="admin-loading">Loading product catalog…</div></td></tr></tbody></table></div>
<div class="admin-pagination" data-product-pagination></div>

<div class="admin-modal-backdrop" data-product-modal aria-hidden="true"><section class="admin-modal" role="dialog" aria-modal="true" aria-labelledby="product-modal-title"><form data-product-form>
  <header class="admin-modal-header"><div><h2 id="product-modal-title">Add a fragrance</h2><p>Start with the product details, then add images and size variants.</p></div><button class="admin-modal-close" type="button" data-close-product-modal aria-label="Close">×</button></header>
  <div class="admin-modal-body">
    <input type="hidden" name="id" data-product-id>
    <div class="admin-form-grid">
      <div class="admin-field"><label for="product-name">PRODUCT NAME</label><input id="product-name" name="name" maxlength="150" required placeholder="e.g. Khamrah Qahwa"></div>
      <div class="admin-field"><label for="product-slug">URL SLUG</label><input id="product-slug" name="slug" maxlength="180" required placeholder="khamrah-qahwa"></div>
      <div class="admin-field"><label for="product-brand">FRAGRANCE HOUSE</label><select id="product-brand" name="brand_id" required><option value="">Choose a house</option></select></div>
      <label class="admin-check"><input type="checkbox" name="is_active" checked> Publish this product in the storefront</label>
      <div class="admin-field full"><label for="product-description">PRODUCT DESCRIPTION</label><textarea id="product-description" name="description" maxlength="60000" placeholder="Describe the fragrance, its character and who it is for…"></textarea></div>
    </div>
    <section class="admin-form-section"><div class="admin-form-section-head"><div><h3>Categories &amp; fragrance character</h3><p>Choose the categories that describe this scent.</p></div></div><div class="admin-categories-checklist" data-product-categories><div class="admin-loading">Loading categories…</div></div></section>
    <section class="admin-form-section"><div class="admin-form-section-head"><div><h3>Product images</h3><p>The primary image appears first on the storefront.</p></div></div><div class="admin-image-list" data-product-images></div><div class="admin-form-grid"><div class="admin-field"><label for="product-image-files">UPLOAD IMAGES</label><input id="product-image-files" type="file" accept="image/jpeg,image/png,image/webp,image/gif" multiple></div><div class="admin-field"><label for="product-image-url">OR ADD IMAGE URL</label><input id="product-image-url" type="url" placeholder="https://…"></div><label class="admin-check"><input type="checkbox" data-primary-new-image> Make the first new image primary</label></div></section>
    <section class="admin-form-section"><div class="admin-form-section-head"><div><h3>Size variants &amp; inventory</h3><p>Each size has its own price, optional sale price and stock count.</p></div><button class="admin-button admin-button-soft" type="button" data-add-variant>＋ Add size</button></div><div data-variant-list></div></section>
    <div class="admin-error hidden" data-product-form-error role="alert"></div>
  </div>
  <footer class="admin-form-footer"><button class="admin-button admin-button-soft" type="button" data-close-product-modal>Cancel</button><button class="admin-button admin-button-dark" type="submit" data-save-product>Save product</button></footer>
</form></section></div>
<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
