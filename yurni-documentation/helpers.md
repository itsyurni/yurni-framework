# الأدوات المساعدة (Helpers)

Yurni يوفر مجموعة دوال مساعدة جاهزة للتطوير السريع.

## `env($key, $default = null)`

جلب قيمة من متغيرات البيئة.

```php
$host = env('DB_HOST', '127.0.0.1');
```

## `config($key, $default = null)`

قراءة إعداد من `Config` أو البيئة.

```php
$debug = config('APP_DEBUG', false);
```

## `view($view, $args = [])`

عرض قالب بسرعة.

```php
echo view('home', ['title' => 'الرئيسية']);
```

## `redirect($url, int $status = 302, bool $allowExternal = false)`

إعادة توجيه المستخدم.

```php
redirect('/login');
```

## `csrf_field()` و `csrf_token()`

```php
<?= csrf_field() ?>
$token = csrf_token();
```

## `db()`

مصدر سريع لكائن قاعدة البيانات.

```php
$users = db()->table('users')->get();
```

## `session()`

استخدام الجلسة بطريقة بسيطة.

```php
session('locale', 'ar');
$current = session('locale');
```

## `flash()`

حفظ واسترجاع رسائل فلاش.

```php
flash('success', 'تم الحفظ');
$message = flash('success');
```

## `dd()`

طباعة بيانات التصحيح وإيقاف التنفيذ.

```php
dd($user, $posts);
```

## `base_path($path = '')`

جلب المسار الكامل للمشروع أو ملف بداخله.

```php
$path = base_path('app/views');
```
