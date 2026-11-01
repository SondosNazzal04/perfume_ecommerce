<?php $storeActive = $storeActive ?? ''; ?><div class="announcement"><span>VELORA · A PRESENCE BEYOND FRAGRANCE</span><i></i><span>CASH ON DELIVERY</span></div>
<header class="store-header"><a class="wordmark" href="index.php" aria-label="VELORA home">VELORA<span class="brand-rune"> / EAU DE PARFUM</span></a><button class="menu-toggle" aria-label="Open navigation" aria-expanded="false">☰</button>
    <nav class="main-nav"><a class="<?= $storeActive === 'home' ? 'active' : '' ?>" href="index.php">Home</a><a class="<?= $storeActive === 'products' ? 'active' : '' ?>" href="products.php">Shop Fragrances</a><a href="products.php">Fragrance Collections</a></nav>
    <div class="header-actions"><a class="icon-link account-link" href="login.php" aria-label="Account"><svg viewBox="0 0 24 24" aria-hidden="true">
                <circle cx="12" cy="8" r="3.5" />
                <path d="M5 21v-1.7a7 7 0 0 1 14 0V21" />
            </svg><span class="account-label">Account</span></a><a class="icon-link wishlist-nav-link" href="wishlist.php" aria-label="Wishlist">♥</a><a class="icon-link bag-link" href="cart.php" aria-label="Shopping bag"><svg viewBox="0 0 24 24" aria-hidden="true">
                <path d="M5 8h14l1 13H4L5 8Z" />
                <path d="M9 8V6a3 3 0 0 1 6 0v2" />
            </svg><span class="bag-count" id="bag-count">0</span></a></div>
</header>