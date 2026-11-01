<?php $storeActive = 'products'; ?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Fragrances — VELORA</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/storefront.css?v=20261101-product-actions">
    <script defer src="assets/js/storefront.js?v=20261101-product-actions"></script>
    <link rel="stylesheet" href="assets/css/velora-theme.css?v=1">
    <link rel="stylesheet" href="assets/css/velora-interior-v2.css?v=2">
</head>

<body><?php include __DIR__ . '/includes/store-header.php'; ?><main class="page-shell">
        <div class="page-heading">
            <p class="eyebrow">THE VELORA COLLECTION</p>
            <h1>Discover Your Signature.</h1>
            <p>Explore exceptional fragrances, crafted to stay with you.</p>
        </div>
        <section class="filter-panel" aria-label="Find and filter fragrances">
            <div class="filter-heading">
                <div>
                    <p class="eyebrow">FIND YOUR SCENT</p>
                    <h2>Make it yours.</h2>
                    <p>Search a fragrance, choose a house, or explore by scent family.</p>
                </div><button id="clear-filters" type="button" class="filter-reset">Clear filters <span aria-hidden="true">↺</span></button>
            </div>
            <div class="catalog-toolbar">
                <div class="filter-search"><label for="catalog-search">Search fragrances</label>
                    <div class="filter-input-wrap"><span aria-hidden="true">⌕</span><input id="catalog-search" type="search" placeholder="Name, note or house…"></div>
                </div>
                <div class="filter-control"><label for="catalog-brand">Fragrance house</label><select id="catalog-brand">
                        <option value="">All houses</option>
                    </select></div>
                <div class="filter-control"><label for="catalog-group">Scent family</label><select id="catalog-group">
                        <option value="">All scent families</option>
                    </select></div>
                <div class="filter-control"><label for="catalog-category">Style</label><select id="catalog-category" disabled>
                        <option value="">Choose a family first</option>
                    </select></div>
                <div class="filter-control filter-sort"><label for="catalog-sort">Sort by</label><select id="catalog-sort">
                        <option value="featured">Featured</option>
                        <option value="newest">Newest</option>
                        <option value="price-low">Price: low to high</option>
                        <option value="price-high">Price: high to low</option>
                    </select></div>
                <div class="result-count" id="result-count" aria-live="polite">Loading fragrances…</div>
            </div>
        </section>
        <div class="product-grid" id="catalog-products">
            <div class="loading-state">Loading our collection…</div>
        </div><button id="catalog-more" class="button button-dark catalog-more hidden" type="button">LOAD MORE</button>
    </main><?php include __DIR__ . '/includes/store-footer.php'; ?></body>

</html>