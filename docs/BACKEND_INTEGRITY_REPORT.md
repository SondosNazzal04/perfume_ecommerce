# Backend integrity report

Source baseline: `perfume_ecommerce(8).zip`.

**Protected source files:** 57
**Protected source files changed:** 0

Protected paths: `actions/`, `api/`, `classes/`, `core/`, `config/`, `database/`, `postman/`, `composer.json`, `composer.lock`.

SHA-256 hash per file is in `PROTECTED_BACKEND_HASHES.json`.

Shared PHP template files `includes/store-header.php`, `includes/store-footer.php`, and `includes/admin-header.php` **were changed for presentation only** (wordmarks and style references), with authentication/session logic untouched. The `admin/` folder dashboard page copy changed for branding; `includes/admin-auth.php` remains unchanged. Other customer root `.php` views contain static title changes and CSS links; `index.php` was replaced by the approved V2 homepage markup.

This file-integrity check does not prove the original backend is secure or that services are fully functional.
