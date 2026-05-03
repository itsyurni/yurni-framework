# HTTP Helper Classes

## Request

كلاس `yurni\Http\Request` يجمع جميع بيانات الطلب ويجعلها سهلة الوصول.

### الطرق الأساسية

- `getPath()` — يعيد مسار الطلب بعد تنظيفه.
- `getMethod()` — طريقة الطلب الحالية.
- `header(string $key, $default = null)` — الحصول على هيدر.
- `server(string $key)` — الحصول على متغير السيرفر.
- `body()` — نص جسم الطلب الأصلي `php://input`.
- `inputs()` — جميع المدخلات من `GET`, `POST`, JSON body.
- `input(string $key)` — قيمة مدخلة معينة.

### حالة الطلب

- `isGet()`
- `isPost()`
- `isPut()`
- `isPatch()`
- `isDelete()`
- `isHttps()`
- `isAjax()`

### CSRF

- `csrfToken()` — قراءة التوكن من `input` أو رؤوس الطلب.

### التحقق

```php
$validated = $request->validate([
    'name' => 'required|string',
    'email' => 'required|email',
]);
```

إذا فشل التحقق، يتم حفظ الأخطاء والبيانات القديمة في الجلسة ثم إعادة التوجيه.

## Response

كلاس `yurni\Http\Response` يسهل إعداد الاستجابة للمستخدم.

### دوال مهمة

- `html(string $content, int $status = 200)`
- `json(array $data = [], int $status = 200)`
- `redirect(string $url, int $status = 302, bool $allowExternal = false)`
- `setHeader(string $type, string $val)`
- `setContentType(string $val)`
- `body()` — الحصول على محتوى الاستجابة.

### مثال JSON

```php
return $this->response->json(['success' => true]);
```

### إعادة التوجيه آمن

`redirect()` يتحقق من أن الرابط غير خارجي أو يسمح بالروابط الداخلية فقط ما لم يُحدد `allowExternal = true`.

## Session

`yurni\Http\Session` يوفر إدارة بسيطة للجلسة.

### أمثلة

```php
session('name', 'أحمد');
$value = session('name');
```

### Flash messages

```php
flash('success', 'تم الحفظ بنجاح');
$message = flash('success');
```

### أدوات الجلسة

- `set(string $key, mixed $value)`
- `get(string $key, mixed $default = null)`
- `has(string $key)`
- `remove(string $key)`
- `flash(string $key, mixed $message)`
- `getFlash(string $key, mixed $default = null)`
- `hasFlash(string $key)`
- `regenerate(bool $deleteOld = true)`
- `destroy()`
