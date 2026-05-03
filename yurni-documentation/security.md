# الأمان في Yurni

## حماية CSRF

Yurni يقدم حماية CSRF مدمجة عبر `yurni\Security\Csrf`.

### إدراج حقل CSRF في النموذج

```php
<form method="POST" action="/submit">
    <?= csrf_field() ?>
    <input name="name" />
</form>
```

### الحصول على التوكن مباشرة

```php
$token = csrf_token();
```

### استخدام Middleware

```php
$app->post('/submit', [FormController::class, 'submit'])->middleware('csrf');
```

### التحقق الآلي

الـ Middleware يتحقق من التوكن المرسل عبر `POST` أو رؤوس `X-CSRF-TOKEN`.

## رفع الملفات الآمن

الكلاس `yurni\Http\FileUpload` يتحقق من:

- أن الملف رفع عبر `is_uploaded_file()`
- أن حالة `UPLOAD_ERR_OK`
- نوع MIME الحقيقي عبر `finfo`
- امتدادات ممنوعة (`php`, `exe`, `sh`, `bat`, وغيرها)
- يمنع إعادة كتابة الملفات إذا كان الملف موجودًا

### مثال

```php
$file = $request->file('avatar');

if ($file && $file->isValid()) {
    $file->validate(['image/jpeg', 'image/png'], 2048, ['jpg', 'png'])
         ->moveWithUniqueName('public/uploads');
}
```

## التعامل مع الجلسات securely

- `session()` يساعدك على الوصول إلى الجلسة بسهولة.
- الكوكي يُنشأ مع `HttpOnly` و `SameSite=Lax` و `secure` عند توفر HTTPS.
- يمكنك استدعاء `session()->regenerate()` لمنع هجمات تثبيت الجلسات.

## مدخلات المستخدم

- `Request` لا ينقّي المدخلات تلقائيًا، لأن عملية التحقق والتنقية مسؤولية طبقة الأعمال.
- استخدم `Validator` للتأكد من قواعد البيانات.

## تهيئة إعدادات الأمان

يمكنك تعديل بعض الإعدادات عبر `Config` أو `.env`:

```php
Config::set('view_allow_php', false);
Config::set('APP_DEBUG', false);
```

## نصيحة

اجعل `APP_DEBUG=false` في الإنتاج وخصص صفحات خطأ آمنة بدلاً من عرض تفاصيل الاستثناءات للمستخدم.
