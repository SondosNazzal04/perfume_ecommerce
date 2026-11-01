<?php
require_once __DIR__ . '/../includes/admin-auth.php';
$adminActive = 'brands';
$adminTitle = 'Fragrance houses';
include __DIR__ . '/../includes/admin-header.php';
?>
<section class="admin-page-heading"><div><p class="admin-eyebrow">THE HOUSES BEHIND THE SCENTS</p><h1>Fragrance houses.</h1><p>Keep the brand directory organized and connected to the catalog.</p></div><button class="admin-button admin-button-dark" type="button" data-open-brand>＋ &nbsp; Add house</button></section>
<div class="admin-error hidden" data-brand-error role="alert"></div><div class="admin-toolbar"><input type="search" data-brand-search placeholder="Search fragrance houses…" aria-label="Search fragrance houses"><span class="result-count" data-brand-count></span></div>
<div class="admin-category-grid" data-brand-list><div class="admin-loading">Loading fragrance houses…</div></div>
<div class="admin-modal-backdrop" data-brand-modal aria-hidden="true"><section class="admin-modal admin-modal-small" role="dialog" aria-modal="true" aria-labelledby="brand-modal-title"><form data-brand-form><header class="admin-modal-header"><div><h2 id="brand-modal-title">Add a fragrance house</h2><p>Names are used in product filters and storefront listings.</p></div><button class="admin-modal-close" type="button" data-close-brand aria-label="Close">×</button></header><div class="admin-modal-body"><input type="hidden" name="id"><div class="admin-form-grid"><div class="admin-field full"><label>HOUSE NAME</label><input name="name" maxlength="100" required></div><div class="admin-field full"><label>DESCRIPTION (OPTIONAL)</label><textarea name="description" maxlength="60000"></textarea></div></div><div class="admin-error hidden" data-brand-form-error role="alert"></div></div><footer class="admin-form-footer"><button class="admin-button admin-button-soft" type="button" data-close-brand>Cancel</button><button class="admin-button admin-button-dark" type="submit">Save house</button></footer></form></section></div>
<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
