# إصلاح Redirect الخاص بـGoogle Login — بدون تغيير منطق الباك إند

## المشكلة التي ظهرت بالفيديو
ملف `config/google.php` في نسخة التيم يوجه المستخدم إلى:

`http://localhost/perfume_ecommerce/actions/google-callback.php`

لكن عند التشغيل من VS Code/PHP Server على `http://127.0.0.1:8000/` لن يكون هذا المسار موجودًا في خادم Apache المعتاد، لذا تظهر صفحة 404 عند الرجوع.

## الخطوات الآمنة على جهازك
1. تأكد من الرابط الأساسي الفعلي الذي تستخدمه للموقع. مثال إذا كان `http://127.0.0.1:8000/index.php`، فإن عنوان الرجوع سيكون `http://127.0.0.1:8000/actions/google-callback.php`.
2. من حساب Google Cloud الذي يمتلك Client OAuth، افتح Credentials > OAuth 2.0 Client IDs > Authorized redirect URIs، وسجل رابط الرجوع **نفسه حرفيًا** مع البروتوكول والمنفذ والمسار. قد ترفض Google بعض عناوين HTTP غير المحلية، لذا استخدم عنوان localhost المحلي المسجل كما تسمح Google.
3. اطلب من مسؤول التيم تحديث `redirect_uri` فقط في ملف إعدادات Google ليطابق العنوان الذي سجلته. لا تغيّر Client ID أو Client Secret، ولا ترسل مفاتيح API في دردشة أو تضعها في GitHub.
4. جرّب على نفس الخادم وبنفس Session Cookie؛ افحص Network بعد إتمام Google OAuth وتأكد من وصول callback إلى خادم PHP الصحيح لا Apache آخر.
5. إذا ظهر خطأ `redirect_uri_mismatch` فهما غير متطابقين. إذا ظهر 404 فالمسار يعود لخادم/مجلد مختلف. إذا ظهر 500 فراجع سجل PHP وإعدادات PHP وComposer.

**لم نغيّر `config/google.php` آليًا** لأن المنفذ/الرابط وبيانات حساب Google الفعلية تختلف من جهاز لآخر، والتعديل العشوائي يمكن أن يعطّل الخدمة. هذه المشكلة لم نؤكد حلها فعليًا بدون اختبار حساب Google.
