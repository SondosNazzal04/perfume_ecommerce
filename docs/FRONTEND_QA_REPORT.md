# VELORA QA — Evidence and limitations

## Automated checks (this environment)
- PHP lint: **83/83 passed**.
- JavaScript parse checks: **18/18 passed**.
- Homepage directly referenced local assets: **36 references**, missing paths: **0**.
- Cinematic atlas assets: **22 smoke** and **14 fog** sheets preserved.
- PHP `-S` server routes: 13 customer pages returned HTTP 200 (this checks generated HTML, not form submission or data loading).
- Protected backend source hashes unchanged: 57/57.

## Not verified / blocked
- Browser automation: Chromium navigation blocked by environment policy (`ERR_BLOCKED_BY_ADMINISTRATOR`), even via local request proxy. **NO visual validation of GSAP motion, hover effects, mobile layout or page screenshots completed.**
- Database/API: test environment PHP lacks `pdo_mysql`. Product, storefront-products, cart endpoints returned HTTP 500 `Database connection error`. **NO live API or SQL integration tests passed in this environment**.
- Real email OTP, Google login, checkout/Stripe and admin permission flows: **NOT tested end to end**; must be checked using a configured local test database and test credentials/services.
- Video byte-range and reverse scroll: **not browser-verified**. The visual files are present, not evidence of smooth playback.
- Original site contains several external/local dependencies; Google Fonts requires internet, with CSS fallback.

## Manual acceptance checklist
1. Import authorized SQL into local MySQL and confirm DB connectivity.
2. Test registration → email OTP → login → logout and password recovery with real test mailbox.
3. Shop search/filter/product detail, real stock variants, wishlist and cart count.
4. Cash test order, and Stripe only in test mode; validate success and order status in database.
5. Admin access guard, customer listing, catalogue CRUD, order status and reviews.
6. All nine cinematic sections, Hero mist, gold ribbon, MP4 playback, forward and reverse scroll.
7. 1440, 1024, 768 and 390px viewport tests plus keyboard, reduced motion and browser Console/Network.
