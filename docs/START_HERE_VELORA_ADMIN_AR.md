# ابدأ من هنا — VELORA Admin Studio

- هذه نسخة تحسين frontend فوق `VELORA_LUXURY_POLISHED_WINDOWS.zip`، وليست مشروعًا جديدًا.
- الشفرة المعتمدة تحت جذر `V/` حتى تفك الضغط مباشرة في `C:\\` ويصير المسار `C:\\V`.
- افتح `C:\\V` من VS Code. شغّل MySQL من XAMPP، وشغّل السيرفر من PHP Server المعتاد. حافظ على قاعدة الاختبار المحلية الموجودة، **لا تستورد SQL مرة أخرى**.
- صفحات الإدارة: `/admin/index.php`, `/admin/products.php`, `/admin/inventory.php`, `/admin/coupons.php`.
- قبل أي إجراء جماعي أو تغيير مخزون أو إنشاء كوبون، جرّبه على بيانات اختبارية فقط لأنه يحدث بيانات المخزن عبر APIs الفريق الموجودة.
- لم تضاف Sales Analytics جديدة ولا أي تغيير في الجداول أو Backend.
- تفاصيل الإصلاحات وحدودها: `ADMIN_STUDIO_CHANGELOG_AR.md`, `ADMIN_STUDIO_QA_AR.md`, `GOOGLE_LOGIN_CALLBACK_FIX_AR.md`, `OTP_EMAIL_BRANDING_HANDOFF_AR.md`.
