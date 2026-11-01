# تشغيل VELORA المدموج — Windows / VS Code

## طريقة التشغيل الموصى بها
1. فك ZIP مباشرة داخل `C:\` ليكون مجلد المشروع النهائي `C:\V`، ثم افتح **المجلد نفسه** في VS Code.
2. افتح XAMPP وشغّل **MySQL** (وApache فقط إذا كنت بحاجة لفتح phpMyAdmin على `http://localhost/phpmyadmin`). لا حاجة لوضع ملفات المشروع داخل `htdocs`.
3. تأكد من إضافة `pdo_mysql` في PHP الذي يشغل الموقع. على XAMPP PHP 8.2 غالبًا متاحة.
4. في phpMyAdmin جهّز قاعدة `perfume_ecommerce` واستورد النسخة الكاملة التي زوّدك بها الفريق `perfume_ecommerce 33.sql` ضمن قاعدة اختبار معزولة. لا تستوردها فوق قاعدة حية، لأنها تحتوي بيانات حسابات وطلبات اختبارية/حقيقية.
5. راجع ملفات `config/Database.php`, `config/mail.php`, `config/google.php`, `config/strip.php` مع الفريق حتى تطابق إعدادات بيئتك وتكون Test Mode. لا تشارك كلمات المرور أو مفاتيح API علنًا.
6. المشروع يحتوي `vendor/` من مشروع التيم. إذا ظهرت مشكلة Composer افتح Terminal داخل مجلد المشروع وشغّل `composer install`.
7. شغّل PHP Server من VS Code كما اعتدت، **أو** افتح Terminal من جذر المشروع وشغّل:

```powershell
& "C:\xampp\php\php.exe" -S 127.0.0.1:8000 -t .
```

8. افتح `http://127.0.0.1:8000/index.php`. متجر الفريق `products.php` وتسجيل الدخول `login.php` ولوحة التحكم `admin/index.php` (تتطلب دخول Admin).
9. جرّب اللاندينغ وأزرار Shop/Cart/Wishlist/Account، ثم اختبر العمليات على قاعدة تجريبية فقط.
10. استخدم F12 Console + Network للعثور على أخطاء API أو ملفات الوسائط. إذا ظهرت `Database connection error`، افحص MySQL وPDO والاسم/الإعدادات.

## مهم
- صفحة V2 السينمائية تستخدم وسائط WebP/MP4؛ اختبرها في Chrome مع ScrollTrigger، خصوصًا reverse scroll.
- صور الزجاجات السينمائية لا تمثل بالضرورة منتجات قابلة للشراء؛ التصفح والشراء الحقيقي عبر `products.php`.
- لا يمكن اعتبار البريد أو Google أو Stripe جاهزين دون اختبار حقيقي بإعدادات الفريق.
