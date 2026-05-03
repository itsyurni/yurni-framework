# تثبيت Yurni Framework

## المتطلبات

- PHP 8.0 أو أحدث
- Composer
- خادم ويب مثل Apache أو Nginx، أو الخادم المدمج لـ PHP
- قاعدة بيانات MySQL أو SQLite (اختياري حسب المشروع)

## خطوات التثبيت

### 1. استنساخ المستودع

```bash
git clone https://github.com/itsyurni/yurni-framework.git
cd yurni-framework
```

### 2. تثبيت الاعتمادات

```bash
composer install
```

### 3. إعداد ملف البيئة

انسخ ملف البيئة وأعد تسميته إلى `.env`:

```bash
cp .env.example .env
```

ثم عدّل القيم التالية:

```env
APP_DEBUG=true
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=myapp
DB_USER=root
DB_PASS=secret
DB_CHARSET=utf8mb4
```

### 4. إعداد نقطة الدخول

أنشئ ملف `public/index.php` إذا لم يكن موجودًا:

```php
<?php
require_once __DIR__ . '/../vendor/autoload.php';

use yurni\Application;

$app = new Application(__DIR__ . '/..');

$app->get('/', function () {
    return '<h1>مرحباً بك في Yurni!</h1>';
});

$app->run();
```

### 5. تشغيل الخادم

```bash
php -S localhost:8000 -t public
```

افتح المتصفح على:

```
http://localhost:8000
```

## إعدادات إضافية

يمكنك تغيير المسارات والأداء من خلال `.env` و `Config::set()`، على سبيل المثال:

```env
views_path=app/views
views_cache_path=app/views/cache
view_cache=true
view_optimize=false
view_allow_php=false
```

## هيكل المجلدات المقترح

- `app/Controllers/`
- `app/Models/`
- `app/views/`
- `public/`
- `storage/` (للكاش أو الملفات المرفوعة)
