<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
  <meta name="theme-color" content="#070707">
  <meta name="description" content="VELORA — a cinematic fragrance experience. Explore the Noir, Amber and Oud Royale collection.">
  <title>VELORA — A Presence Beyond Fragrance</title>
  <link rel="icon" href="data:,">
  <link rel="stylesheet" href="assets/fonts/fonts.css">
  <link rel="preload" as="image" href="assets/hero/bottle-open.webp">
  <link rel="preload" as="image" href="assets/hero/cap.webp">
  <link rel="stylesheet" href="src/styles.css">
  <link rel="stylesheet" href="src/phase1.css">
  <link rel="stylesheet" href="src/cinematic.css">
  <link rel="stylesheet" href="src/reference-polish.css">
  <link rel="stylesheet" href="src/final-refined.css">
  <link rel="stylesheet" href="src/luxury-v2.css">
  <link rel="stylesheet" href="src/home-integrated.css?v=1">
  <link rel="stylesheet" href="src/gold-liquid-visibility.css?v=2">
  <!-- Everything is bundled locally; no CDN is required. -->
</head>
<body>
  <a class="skip-link" href="#collection">Skip cinematic opening</a>
  <header class="nav" id="topbar">
    <a class="logo" href="#hero" aria-label="VELORA home">VELORA<span class="brand-mark"> / EAU DE PARFUM</span></a>
    <nav id="menu" aria-label="Primary navigation">
      <a class="on" href="index.php">Home</a>
      <a href="products.php">Shop</a>
      <a href="#brand-story">Our Story</a><a href="#notes">Fragrance Notes</a>
    </nav>
    <div class="tools">
      <button type="button" id="open-search" aria-label="Find a fragrance" title="Find a fragrance">
        <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="6.5"/><path d="m16 16 5 5"/></svg>
      </button>
      <a id="cart-toggle" class="nav-collection-link" href="cart.php" aria-label="Open shopping bag"><svg viewBox="0 0 24 24"><path d="M4 9h16l-1 12H5zM8 9V7a4 4 0 0 1 8 0v2"/></svg><span id="cart-count" aria-hidden="true">0</span></a>
      <a class="velora-nav-icon" href="wishlist.php" aria-label="Wishlist" title="Wishlist">♡</a><a class="velora-nav-icon" href="account.php" aria-label="Account" title="Account"><svg width="23" height="23" viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.55" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="3.6"/><path d="M4.6 20v-1.4a7.4 7.4 0 0 1 14.8 0V20"/></svg></a><button type="button" class="burger" aria-label="Menu" aria-expanded="false" aria-controls="menu"><span></span><span></span></button>
    </div>
  </header>
  <main>
    <!-- A single preserved V3 scroll stage: opening -> product/cap/mist sequence. -->
    <section class="track" id="hero" aria-label="VELORA cinematic fragrance opening">
      <div class="stage">
        <div class="bg" aria-hidden="true"><div class="editorial-plate"></div><div class="halo"></div><div class="beams"></div><div class="haze"></div><div class="floor"></div><div class="dust"></div><div class="gold-bokeh"></div></div>
        <canvas class="ambient-fog" aria-hidden="true"></canvas>
        <div class="bottle" aria-hidden="true">
          <img class="refl" src="assets/hero/bottle-open.webp" alt="">
          <img class="base" src="assets/hero/bottle-open.webp" alt="">
          <img class="cap" src="assets/hero/cap.webp" alt="">
        </div>
        <canvas class="media-smoke" aria-hidden="true"></canvas>
        <p class="vfx-status" role="status" hidden>The cinematic effect could not load. The product remains available.</p>
        <div class="ed">
          <p class="eyebrow">Luxury perfumes · The art of presence</p>
          <h1>More Than a Fragrance,<br>It’s a Presence</h1>
          <p class="lead">Rare amber, smoked woods and warm resin, composed into an eau de parfum made to be remembered.</p>
          <a class="pill" href="#collection">Discover Collection <i></i></a>
        </div>
        <div class="col" aria-label="Featured fragrances">
          <h2>Our Signature Collection</h2>
          <ul>
            <li><img src="assets/products/noir-small.webp" alt=""><b>VELORA NOIR</b><span>The dark signature</span></li>
            <li><img src="assets/products/amber-small.webp" alt=""><b>VELORA AMBER</b><span>Warm and luminous</span></li>
            <li><img src="assets/products/oud-royale-small.webp" alt=""><b>OUD ROYALE</b><span>The deeper presence</span></li>
            <li><img src="assets/hero/bottle-closed.webp" alt=""><b>VELORA</b><span>Eau de Parfum</span></li>
          </ul>
        </div>
        <div class="fx">
          <p class="eyebrow">Premium Collection · VELORA</p>
          <h2 class="big"><span class="ln"><span>Moments of</span></span><span class="ln"><span>Luxury Dreams</span></span></h2>
          <p class="lead">A composition of amber, smoked oud and warm resin. One spray, and the room remembers you.</p>
          <div class="act"><a class="gold" href="#collection">Explore Now</a><a class="sq" href="#collection" aria-label="Go to Product Collection"><i></i></a></div>
        </div>
        <div class="hero-side-index" aria-hidden="true"><span>01 / 03</span><i></i><span>VELORA</span></div>
        <a class="orb" href="#collection" aria-label="Scroll to Product Collection"><i></i></a>
      </div>
    </section>

    <!-- The reference's three-card asymmetric interactive selection, not a static composite. -->
    <section class="collection-section" id="collection" aria-labelledby="collection-title">
      <canvas id="collection-atmosphere" aria-hidden="true"></canvas>
      <div class="collection-glow" aria-hidden="true"></div>
      <div class="collection-head" data-reveal>
        <div class="collection-heading-line"><span class="eyebrow">The fragrance wardrobe</span><span class="section-number">02 / VELORA COLLECTION</span></div>
        <h2 id="collection-title" class="collection-title">Product <em>Collection</em></h2>
        <p class="collection-intro">The cinematic VELORA signatures. Explore the real fragrance catalogue in our boutique.</p>
      </div>
      <div class="collection-carousel" data-reveal>
        <button class="collection-arrow collection-prev" type="button" aria-label="Previous fragrance"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m15 5-7 7 7 7"/></svg></button>
        <div class="swiper velora-swiper" id="perfume-swiper" aria-roledescription="carousel" aria-label="VELORA perfume collection">
          <div class="swiper-wrapper">
            <article class="swiper-slide perfume-card" data-id="noir" aria-label="VELORA Noir">
              <div class="card-top"><span>01 / NOIR</span><span class="card-chip">SIGNATURE</span></div>
              <div class="card-visual"><img src="assets/products/noir.webp" alt="VELORA Noir dark amber glass perfume bottle with gold cap" width="850" height="1062" loading="lazy"></div>
              <div class="card-bottom"><span class="card-category">EAU DE PARFUM · NOIR</span><h3>Velora <i>Noir</i></h3><p>The dark signature.</p><a href="#collection-info" data-product="noir" aria-label="Explore VELORA Noir">Explore fragrance <span aria-hidden="true">↗</span></a></div>
            </article>
            <article class="swiper-slide perfume-card" data-id="amber" aria-label="VELORA Amber">
              <div class="card-top"><span>02 / AMBER</span><span class="card-chip">THE ICON</span></div>
              <div class="card-visual"><img src="assets/products/amber.webp" alt="VELORA Amber luminous amber glass perfume bottle with gold cap" width="850" height="1062" loading="lazy"></div>
              <div class="card-bottom"><span class="card-category">EAU DE PARFUM · AMBER</span><h3>Velora <i>Amber</i></h3><p>Golden warmth, unforgettable presence.</p><a href="#collection-info" data-product="amber" aria-label="Explore VELORA Amber">Explore fragrance <span aria-hidden="true">↗</span></a></div>
            </article>
            <article class="swiper-slide perfume-card" data-id="oud" aria-label="VELORA Oud Royale">
              <div class="card-top"><span>03 / OUD ROYALE</span><span class="card-chip">EXCLUSIVE</span></div>
              <div class="card-visual"><img src="assets/products/oud-royale.webp" alt="VELORA Oud Royale deep brown perfume bottle with champagne gold cap" width="850" height="1250" loading="lazy"></div>
              <div class="card-bottom"><span class="card-category">EAU DE PARFUM · OUD</span><h3>Oud <i>Royale</i></h3><p>A richer kind of elegance.</p><a href="#collection-info" data-product="oud" aria-label="Explore VELORA Oud Royale">Explore fragrance <span aria-hidden="true">↗</span></a></div>
            </article>
          </div>
        </div>
        <button class="collection-arrow collection-next" type="button" aria-label="Next fragrance"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9 5 7 7-7 7"/></svg></button>
      </div>
      <div class="collection-footer" data-reveal>
        <div class="pagination-wrap"><span>01</span><div class="collection-pagination" aria-label="Choose a fragrance"></div><span>03</span></div>
        <div class="collection-selected" aria-live="polite"><span class="selected-label">NOW EXPLORING</span> <strong id="active-fragrance">VELORA AMBER</strong></div>
        <span class="collection-instruction">DRAG TO EXPLORE <span aria-hidden="true">↔</span></span>
      </div>
    </section>

    <!-- V2 replaces only the approved showcase; every original bottle stays unchanged. -->
    <section id="showcase" class="fusion-section" aria-labelledby="fusion-title">
      <div class="fusion-stage">
        <div class="v2-index"><span>03 / THE ALCHEMY</span><span>FROM NATURE. INTO PRESENCE.</span></div>
        <div class="fusion-heading"><span class="eyebrow">THE ART OF COMPOSITION</span><h2 id="fusion-title">Separate notes.<br><em>One extraordinary presence.</em></h2></div>
        <div class="fusion-light" aria-hidden="true"></div><canvas id="fusion-particles" aria-hidden="true"></canvas>
        <div class="fusion-ingredients" aria-label="Amber resin, oud wood, saffron and vanilla">
          <figure class="fusion-ingredient" data-note="0"><div class="ingredient-photo ingredient-0" role="img" aria-label="Amber resin"></div><figcaption><span>01</span> Amber resin</figcaption></figure><figure class="fusion-ingredient" data-note="1"><div class="ingredient-photo ingredient-1" role="img" aria-label="Oud wood"></div><figcaption><span>02</span> Oud wood</figcaption></figure><figure class="fusion-ingredient" data-note="2"><div class="ingredient-photo ingredient-2" role="img" aria-label="Saffron"></div><figcaption><span>03</span> Saffron</figcaption></figure><figure class="fusion-ingredient" data-note="3"><div class="ingredient-photo ingredient-3" role="img" aria-label="Vanilla"></div><figcaption><span>04</span> Vanilla</figcaption></figure>
        </div>
        <div class="fusion-core" aria-hidden="true"></div><div class="fusion-pulse" aria-hidden="true"></div><div class="fusion-flash" aria-hidden="true"></div>
        <div class="fusion-product"><div class="fusion-plinth"></div><img src="assets/hero/bottle-closed.webp" alt="Original VELORA eau de parfum bottle" width="231" height="420"><div class="fusion-ground"></div></div>
        <div class="fusion-fog" aria-hidden="true"></div>
        <div class="fusion-finish"><span class="eyebrow">THE NOTES BECOME A SIGNATURE</span><h3>Pure <em>presence.</em></h3><a href="products.php" class="v2-text-link">SHOP THE COLLECTION <span>↗</span></a></div>
        <div class="fusion-caption"><span class="fusion-phase">01 — THE RAW ELEMENTS</span><span class="fusion-scroll">SCROLL TO COMPOSE ↓</span></div>
        <div class="fusion-progress" aria-hidden="true"><i></i></div>
      </div>
    </section>

    <!-- Reference sequence 05: editorial story with a second moving composition. -->
    <section class="cinematic-section story-section" id="brand-story" aria-labelledby="story-title">
      <div class="cinematic-index"><span>04 — OUR HERITAGE</span><span>THE ART OF PRESENCE</span></div>
      <div class="story-stage story-stage-a">
        <div class="story-art" aria-hidden="true">
          <video class="cinematic-video story-film" src="assets/cinematic/brand-story.mp4" muted playsinline loop preload="metadata"></video>
          <span class="story-orbit orbit-one"></span><span class="story-orbit orbit-two"></span>
          <div class="story-dust"></div><div class="story-gold-fragments" aria-hidden="true"><i></i><i></i><i></i><i></i><i></i><i></i></div>
        </div>
        <div class="story-copy cinematic-copy">
          <span class="eyebrow">A STORY WRITTEN IN SCENT</span>
          <h2 id="story-title" class="cinema-title">Brand <em>Story</em></h2>
          <p>VELORA begins where memory meets imagination — in the quiet elegance of amber, woods and golden light.</p>
          <a class="cinema-button" href="#notes">THE ART OF VELORA <span aria-hidden="true">↗</span></a>
        </div>
      </div>
      <div class="story-stage story-stage-b">
        <div class="story-liquid" aria-hidden="true"><video class="cinematic-video story-liquid-film" src="assets/cinematic/gold-liquid.mp4" muted playsinline loop preload="auto" poster="assets/cinematic/gold-liquid-poster.webp"></video><div class="story-b-fragments"><i></i><i></i><i></i><i></i></div></div>
        <div class="story-second cinematic-copy">
          <span class="eyebrow">TIMELESS BY DESIGN</span>
          <h3>Crafted for<br><em>the unforgettable.</em></h3>
          <p>Not simply worn. Remembered. Every note is composed to stay with you long after the moment has passed.</p>
        </div>
      </div>
    </section>

    <!-- Reference sequence 06: golden liquid, ingredient tiers, suspended pipette. -->
    <section class="cinematic-section notes-section" id="notes" aria-labelledby="notes-title">
      <div class="cinematic-index"><span>05 — THE COMPOSITION</span><span>FROM FIRST NOTE TO LAST</span></div>
      <h2 id="notes-title" class="notes-heading cinema-title" data-cinema-reveal>Fragrance <em>Notes</em></h2>
      <div class="notes-stage">
        <div class="liquid-column" aria-label="Golden perfume liquid moving in a ribbon">
          <video class="cinematic-video notes-film liquid-film" src="assets/cinematic/gold-liquid.mp4" muted playsinline loop preload="auto" poster="assets/cinematic/gold-liquid-poster.webp"></video>
        </div>
        <div class="notes-list" data-cinema-reveal>
          <div class="note-row"><span class="note-index">01</span><div><span class="note-type">THE FIRST IMPRESSION</span><strong>Top Notes</strong><small>Golden light · a bright opening</small></div></div>
          <div class="note-row"><span class="note-index">02</span><div><span class="note-type">THE HEART OF VELORA</span><strong>Heart Notes</strong><small>Warm amber · a radiant core</small></div></div>
          <div class="note-row"><span class="note-index">03</span><div><span class="note-type">THE LASTING SIGNATURE</span><strong>Base Notes</strong><small>Smoked woods · a timeless finish</small></div></div>
        </div>
        <div class="pipette-column" aria-label="Golden perfume drop falling into liquid">
          <video class="cinematic-video notes-film pipette-film" src="assets/cinematic/pipette.mp4" muted playsinline loop preload="metadata"></video>
        </div>
      </div>
    </section>

    <!-- Reference sequence 07: layered ribbon in front of and behind the same V3 bottle. -->
    <section class="cinematic-section signature-section" id="signature" aria-labelledby="signature-title">
      <div class="cinematic-index"><span>06 — THE SIGNATURE</span><span>A LASTING IMPRESSION</span></div>
      <div class="signature-words cinematic-copy" data-cinema-reveal>
        <span class="eyebrow">MORE THAN A FRAGRANCE</span>
        <h2 id="signature-title" class="cinema-title">Wear the<br><em>extraordinary.</em></h2>
        <p>One signature. A thousand memories. Discover a presence that belongs entirely to you.</p>
        <a class="cinema-button" href="#collection">EXPLORE COLLECTION <span aria-hidden="true">↗</span></a>
      </div>
      <div class="signature-vfx" aria-hidden="true">
        <svg class="ribbon ribbon-back" viewBox="0 0 700 640" preserveAspectRatio="xMidYMid meet" focusable="false">
          <defs><linearGradient id="back-gold" x1="0" x2="1"><stop offset="0" stop-color="#6c390e"/><stop offset=".45" stop-color="#ffda83"/><stop offset=".75" stop-color="#bb8138"/><stop offset="1" stop-color="#fff1c3"/></linearGradient></defs>
          <filter id="soft-back" x="-10%" y="-10%" width="120%" height="120%"><feGaussianBlur stdDeviation="1.6"/></filter><path class="ribbon-path ribbon-soft" filter="url(#soft-back)" d="M 125 535 C -35 440 130 295 340 316 S 730 200 580 92" stroke="url(#back-gold)"/><path class="ribbon-strand" d="M 118 528 C -48 436 118 288 338 308 S 718 196 572 86" /><path class="ribbon-path ribbon-edge" d="M 125 535 C -35 440 130 295 340 316 S 730 200 580 92" stroke="#fff0bd"/>
        </svg>
        <img class="signature-bottle" src="assets/hero/bottle-closed.webp" alt="" loading="lazy">
        <svg class="ribbon ribbon-front" viewBox="0 0 700 640" preserveAspectRatio="xMidYMid meet" focusable="false">
          <defs><linearGradient id="front-gold" x1="0" x2="1"><stop offset="0" stop-color="#b77822"/><stop offset=".35" stop-color="#fff1bb"/><stop offset=".65" stop-color="#ba782c"/><stop offset="1" stop-color="#ffcd6d"/></linearGradient></defs>
          <path class="ribbon-path" d="M 165 562 C 190 476 493 511 520 377 S 265 255 360 176 S 675 118 578 32" stroke="url(#front-gold)"/><path class="ribbon-strand" d="M 172 556 C 200 470 500 505 528 372 S 274 250 368 172 S 683 112 586 28" /><path class="ribbon-strand strand-2" d="M 158 568 C 178 484 484 518 512 382 S 256 262 352 182 S 668 124 570 38" /><path class="ribbon-glint" d="M 165 562 C 190 476 493 511 520 377 S 265 255 360 176 S 675 118 578 32" /><path class="ribbon-path ribbon-edge" d="M 165 562 C 190 476 493 511 520 377 S 265 255 360 176 S 675 118 578 32" stroke="#fff0c4"/>
        </svg>
        <div class="fog-bank fog-bank-signature"><i class="fog-layer fog-1"></i><i class="fog-layer fog-2"></i></div><div class="signature-smoke"></div><div class="signature-sparks" data-sparks="26"></div>
      </div>
    </section>


    <section id="unbox" class="unbox-section" aria-labelledby="unbox-title">
      <div class="unbox-stage">
        <div class="v2-index"><span>THE VELORA RITUAL</span><span>AN INVITATION TO SLOW DOWN</span></div>
        <div class="unbox-copy"><span class="eyebrow">BEFORE THE FIRST SPRAY</span><h2 id="unbox-title" data-editorial>Anticipation.<br><em>Beautifully unveiled.</em></h2><p>A quiet opening. A glimmer of gold.<br>A signature waiting to become yours.</p><a class="v2-text-link" href="#best-sellers">EXPLORE THE SIGNATURES <span>↗</span></a></div>
        <div class="unbox-scene" aria-label="Black and gold VELORA presentation box opening to reveal the original bottle">
          <div class="unbox-aura" aria-hidden="true"></div><div class="unbox-shadow" aria-hidden="true"></div>
          <div class="unbox-base" aria-hidden="true"></div><div class="unbox-innerlight" aria-hidden="true"></div>
          <img class="unbox-bottle" src="assets/hero/bottle-closed.webp" alt="VELORA bottle revealed inside its presentation box" width="231" height="420" loading="lazy">
          <div class="unbox-lid" aria-hidden="true"></div><div class="unbox-motes" aria-hidden="true"><i style="--i:0"></i><i style="--i:1"></i><i style="--i:2"></i><i style="--i:3"></i><i style="--i:4"></i><i style="--i:5"></i><i style="--i:6"></i><i style="--i:7"></i><i style="--i:8"></i><i style="--i:9"></i><i style="--i:10"></i><i style="--i:11"></i><i style="--i:12"></i><i style="--i:13"></i><i style="--i:14"></i><i style="--i:15"></i></div>
        </div>
        <div class="unbox-bottom"><span>THE ART OF GIVING</span><span>SCROLL TO UNVEIL ↓</span></div>
      </div>
    </section>
    <section id="best-sellers" class="best-sellers" aria-labelledby="best-title">
      <div class="best-heading"><div><span class="eyebrow">THE SIGNATURE EDIT</span><h2 id="best-title" data-editorial>Best <em>Sellers</em></h2></div><p>Three expressions. An enduring impression.<br>Your next signature starts here.</p></div>
      <div class="best-grid"></div><p class="shop-demo-note">Cinematic signatures shown for editorial purposes. Shop current fragrances and prices in our boutique.</p>
    </section>

    <!-- Reference sequence 08: quiet conversion and footer, rather than an invented product-details section. -->
    <section class="premium-cta" id="premium-cta" aria-labelledby="cta-heading"><div class="cta-rays" aria-hidden="true"></div><div class="fog-bank fog-bank-cta" aria-hidden="true"><i class="fog-layer fog-1"></i></div>
      <span class="eyebrow">YOUR SIGNATURE AWAITS</span>
      <h2 id="cta-heading" class="cinema-title">A Presence Beyond <em>Words.</em></h2>
      <p>Discover the collection and find the fragrance that speaks without saying a word.</p>
      <a class="cinema-button" href="#collection">DISCOVER VELORA <span aria-hidden="true">↗</span></a>
    </section>
  </main>
  <footer class="phase1-footer cinematic-footer"><div class="footer-wordmark" aria-hidden="true">VELORA</div><div class="footer-brand"><a class="logo" href="#hero">VELORA</a><p>More than a fragrance, it’s a presence.</p></div><div class="footer-links"><div><span>EXPLORE</span><a href="#collection">Collection</a><a href="#showcase">The Alchemy</a><a href="#notes">Fragrance Notes</a></div><div><span>ABOUT</span><a href="#brand-story">Our Story</a><a href="#signature">Signature</a><a href="#premium-cta">Discover</a></div><div><span>VELORA</span><a href="#hero">Back to Top ↑</a><a href="products.php">Shop All Fragrances</a><a href="login.php">Sign In</a><a href="cart.php">Cart</a></div></div><div class="footer-bottom"><span>© 2026 VELORA. A STUDY IN PRESENCE.</span><span>THE ART OF FRAGRANCE</span></div></footer>
    <dialog class="collection-info fragrance-detail-dialog" id="collection-info" aria-label="Selected fragrance details">
      <button type="button" class="detail-close" id="detail-close" aria-label="Close fragrance details">×</button>
      <div class="info-image"><img id="selected-product-image" src="assets/products/amber.webp" alt="VELORA Amber perfume bottle" loading="lazy"></div>
      <div class="info-content"><span class="eyebrow">The collection / Selected fragrance</span><h2 id="selected-product-heading">Velora <em>Amber</em></h2><p id="selected-product-description">A warm amber signature with a luminous, refined character.</p><p class="availability">Explore the collection · VELORA editorial signature · Explore available perfumes and accurate prices in the live boutique.</p><div class="info-actions"><button type="button" class="info-back" id="view-selection">Choose another signature <span aria-hidden="true">↗</span></button><a class="velora-real-shop-link" href="products.php">SHOP LIVE FRAGRANCES ↗</a><span class="info-counter">VELORA / 03</span></div></div>
    </dialog>

  <dialog id="fragrance-search" class="search-dialog" aria-labelledby="search-title">
    <form method="dialog"><button type="submit" class="search-close" aria-label="Close search">✕</button></form>
    <span class="eyebrow">Find your signature</span><h2 id="search-title">Search VELORA signatures</h2>
    <label for="fragrance-query">Choose a fragrance by name</label><input id="fragrance-query" type="search" placeholder="Noir, Amber, Oud Royale" autocomplete="off">
    <div id="fragrance-results" class="search-results" role="region" aria-label="Fragrance search results"></div><a class="velora-real-shop-link" href="products.php">SEARCH THE FULL LIVE CATALOGUE ↗</a>
  </dialog>
  <script src="assets/vendor/gsap.min.js"></script>
  <script src="assets/vendor/ScrollTrigger.min.js"></script>
  <script type="module" src="src/main.js"></script>
  <script type="module" src="src/collection.js"></script>
  <script type="module" src="src/collection-atmosphere.js"></script>
  <script type="module" src="src/cinematic.js"></script>
  <script type="module" src="src/luxury-motion.js"></script>
  <script defer src="src/home-live-cart.js"></script>

</body>
</html>
