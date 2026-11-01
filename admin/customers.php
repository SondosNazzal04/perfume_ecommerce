<?php
require_once __DIR__ . '/../includes/admin-auth.php';
$adminActive = 'customers';
$adminTitle = 'Customers';
include __DIR__ . '/../includes/admin-header.php';
?>
<section class="admin-page-heading"><div><p class="admin-eyebrow">YOUR COMMUNITY</p><h1>Customers.</h1><p>Registered accounts and verified contact details.</p></div></section>
<div class="admin-error hidden" data-customers-error role="alert"></div><div class="admin-toolbar"><input type="search" data-customer-search placeholder="Search name or email…" aria-label="Search customers"><span class="result-count" data-customer-count></span></div>
<div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Customer</th><th>Email</th><th>Phone</th><th>Verification</th><th>Joined</th></tr></thead><tbody data-customer-rows><tr><td colspan="5"><div class="admin-loading">Loading customers…</div></td></tr></tbody></table></div><div class="admin-pagination" data-customer-pagination></div>
<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
