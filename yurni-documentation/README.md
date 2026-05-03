# Yurni Framework Documentation

مرحبًا بك في توثيق إطار العمل **Yurni**. هذا الدليل يشرح الإطار من الصفر وحتى المستوى المتقدم، مع أمثلة عملية لكل دالة وميزة.

## المحتوى

- [Installation](installation.md)
- [Routing](routing.md)
- [Controllers](controllers.md)
- [Models](models.md)
- [Database & Query Builder](database.md)
- [Views & Template Engine](views.md)
- [HTTP Helper Classes](http.md)
- [Security](security.md)
- [Helpers](helpers.md)
- [Error Handling](errors.md)

## كيف تستخدم هذه الوثائق

1. ابدأ من `installation.md` لإعداد المشروع وتشغيله.
2. انتقل إلى `routing.md` لفهم كيفية تعريف المسارات وطرقها.
3. استعرض `controllers.md` و `models.md` لتعلم هيكل التطبيق ونمط MVC.
4. تابع بقية الصفحات لكل ميزة: قاعدة بيانات، عرض، أمان، وأخطاء.

---

## ما هذا الإطار؟

`Yurni` هو إطار MVC خفيف للـ PHP يوفر:

- نظام توجيه بسيط وواضح
- طبقة نماذج (`Model`) مع علاقات CRUD جاهزة
- محرك قوالب خفيف يدعم التعابير والبنى الشرطية والحلقات
- حاوية حقن تبعيات بسيطة
- حماية CSRF مدمجة
- أدوات مساعدة (`helpers`) لتسريع تطوير التطبيقات

## مثال سريع

```php
<?php
require_once __DIR__ . '/../vendor/autoload.php';

use yurni\Application;

$app = new Application(__DIR__ . '/../');

$app->get('/', function () {
    return '<h1>مرحباً بك في Yurni!</h1>';
});

$app->run();
```

---

## ملاحظة

يمكنك دائمًا إنشاء ملفات خطأ مخصصة في `app/views/Exception/` مثل `404.php`, `500.php`, `exception.php`.
