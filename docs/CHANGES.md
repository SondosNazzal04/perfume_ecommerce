# VELORA — Team Backend + Approved Cinematic V2

## Approved baselines
- **Team application:** `perfume_ecommerce(8).zip` (retains PHP classes/APIs, database schema, Composer dependencies, uploads, runtime configuration).
- **Cinematic homepage:** `VELORA_LUXURY_ECOMMERCE_V2(3).zip` (approved V2 page and all VFX image/video/cinematic scripts).
- SQL data dump `perfume_ecommerce 33.sql` was **not embedded in the deliverable** because it includes account and transaction records. Import your provided SQL separately into a local testing database if you are authorized to use those records.

## Implemented frontend integration
1. Replaced Ater homepage view with VELORA V2 nine-section cinematic homepage (`index.php`). Kept Hero, mist/smoke frames, Ingredient Fusion, Brand Story, Notes, Signature gold ribbon, Unboxing, Best Sellers, finale.
2. Copied V2 cinematic scripts and all media into `src/` and `assets/` (22 smoke + 14 fog WebP atlases, video plates, original bottle renderings). No new CGI shots were generated.
3. Replaced only V2 **demonstration** commerce module `src/commerce.js` with safe editorial cards that route to the existing live `products.php`. No fake cart items, fake prices, invented product IDs or payments. Added `src/home-live-cart.js` to read the real cart item-count when available.
4. Added live header routes for Shop, Wishlist, Account and Cart. Homepage marketing calls-to-action point to live catalogue.
5. Added `assets/css/velora-theme.css` to customer pages and `assets/css/velora-admin.css` to admin view shared shell, maintaining team JS and form endpoints.
6. Branded team shared header/footer and admin shared HTML template with VELORA typography; static page headings and titles updated. No server-side authentication, session, database, API, or payment functionality was changed.
7. Added OTP six-slot VELORA presentation and success animation driven by an actual positive server response; retained existing form endpoint and field name `otp`. Adjusted successful OTP frontend redirect to allow the confirmation animation to finish.
8. Preserved team Composer libraries and local uploads. Removed `.git` history from redistribution.

## Known decisions
- VELORA Noir, Amber and Oud Royale bottles are **cinematic editorial assets**. No SKU equivalence to team products has been established. Users shop real inventory at `products.php` instead.
- CSS fonts use Google Fonts with fallback rather than redistributing standalone font files. For completely offline font parity, use your already licensed original assets in your local development copy.
- The original app's backend environment-specific configs are preserved. Handle the ZIP as private; review and rotate credentials before sharing beyond the team.
