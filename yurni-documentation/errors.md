# معالجة الأخطاء (Error Handling)

Yurni يوفر نظام معالجة أخطاء مركزي وقابل للتخصيص.

## تشغيل التطبيق

في `Application::run()` يتم احتواء استدعاء `router->resolve()` داخل `try/catch`.

```php
public function run(): void
{
    try {
        echo $this->router->resolve();
    } catch (\Throwable $e) {
        $this->renderException($e);
    }
}
```

## وضع الـ Debug

إذا كانت `APP_DEBUG=true` فسيُعرض قالب `exception.php` مع تفاصيل الاستثناء.
إذا كانت `APP_DEBUG=false` فسيُعرض قالب `500.php` العام.

## تخصيص صفحات الأخطاء

يبحث التطبيق عن القوالب في:

- `app/views/Exception/404.php`
- `app/views/Exception/500.php`
- `app/views/Exception/exception.php`

إذا لم توجد، يستخدم القوالب الافتراضية داخل إطار العمل.

## تسجيل الأخطاء

كل استثناء يتم تسجيله في سجل الأخطاء عبر `error_log()` قبل العرض.

## فصل أخطاء الإطار عن أخطاء التطبيق

تمت إضافة دوال للتصنيف:

- `Framework error`
- `Framework internal error`
- `Application error`

هذا يساعد في معرفة أن الخطأ ناتج عن كودك أو عن بنية الإطار.

## التعامل مع الأخطاء الفادحة

يستخدم الإطار `set_error_handler`, `set_exception_handler`, و `register_shutdown_function` لتحويل الأخطاء إلى استثناءات قابلة للمعالجة.

## نصيحة إنتاجية

- ضع `APP_DEBUG=false` في بيئة الإنتاج.
- استخدم صفحات خطأ مخصصة لعرض رسالة مستخدم مناسبة.
- راقب السجل (`error_log`) لمعرفة مكان حدوث الخطأ الحقيقي.
