# Integration and backend readiness

This output is a frontend merge on the supplied team code, not a backend refactor.

- A complete local MySQL database and PDO MySQL extension are required. The current execution environment has no PDO MySQL driver and could not load live API data.
- Team-supplied `config/google.php`, `config/mail.php` and `config/strip.php` were preserved as given. Their credentials were not verified; use safe test credentials only.
- The SQL dump provided separately contains users/orders/password-related hashes. Treat it as private and do not upload it publicly. Do not import onto live/production without backups and authorization.
- OTP email delivery, Google OAuth redirect, payment webhooks/Stripe checkout, security/authz, and admin endpoints require real test environment inspection before production release.
- If marketing VELORA SKUs should be purchasable as distinct products, the team must create corresponding valid product, variant, stock, and images in the database; frontend MUST NOT invent identifiers or prices.
- Some business and SEO text may still refer to old brand naming outside the edited customer templates, especially inside backend-generated emails. Ask the backend team before changing templates or email logic.
- Because team config files may contain credentials, review them locally and avoid publishing this complete ZIP on public hosting or source control. Rotate anything inadvertently exposed.
