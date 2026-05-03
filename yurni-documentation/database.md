# قاعدة البيانات و Query Builder

## `Db` - نقطة الدخول إلى قواعد البيانات

### الحصول على الكائن

```php
use yurni\Db;

$db = Db::getInstance();
```

### اختيار جدول سريعًا

```php
$users = $db->table('users')->get();
```

### تنفيذ استعلامات مباشرة

```php
$results = $db->select('SELECT * FROM users WHERE active = :active', ['active' => 1]);
```

```php
$count = $db->affectingStatement('UPDATE users SET active = 0 WHERE id = :id', ['id' => 5]);
```

### إدراج وإرجاع ID

```php
$id = $db->table('users')->insertGetId([
    'name' => 'محمد',
    'email' => 'mohamed@example.com',
]);
```

## Query Builder

الكلاس `yurni\Database\QueryBuilder` يوفر استعلامات سلسة وآمنة.

### بدائل SELECT

```php
$db->table('posts')->select('id', 'title')->get();
```

### شروط WHERE

```php
$query = $db->table('posts')
    ->where('status', 'published')
    ->where('category', 'news');
```

### مسافات إضافية

```php
$db->table('users')
    ->whereIn('role', ['admin', 'editor'])
    ->whereBetween('created_at', ['2024-01-01', '2024-12-31'])
    ->get();
```

### الانضمام (JOIN)

```php
$db->table('posts')
    ->join('users', 'posts.user_id', '=', 'users.id')
    ->select('posts.*', 'users.name')
    ->get();
```

### ترتيب و حد و إزاحة

```php
$items = $db->table('products')
    ->orderBy('price', 'desc')
    ->limit(10)
    ->offset(20)
    ->get();
```

### التمييز DISTINCT

```php
$categories = $db->table('posts')
    ->distinct()
    ->select('category')
    ->get();
```

### استخدام الـ Query Builder في نموذج

```php
$users = User::query()->where('active', 1)->get();
```

### ملاحظات أمان

- يتم ربط القيم تلقائيًا عبر Prepared Statements.
- استخدم `where()` و `whereIn()` بدلًا من بناء الاستعلامات يدويًا.
- اسماء الجداول والأعمدة يتم تغليفها تلقائيًا للحماية.

## دعم قواعد بيانات متعددة

يدعم `Db` بشكل افتراضي:

- MySQL / MariaDB
- PostgreSQL
- SQLite

المعلومات تُقرأ من `.env` عبر المفاتيح:

- `DB_DRIVER`
- `DB_HOST`
- `DB_PORT`
- `DB_NAME`
- `DB_USER`
- `DB_PASS`
- `DB_CHARSET`
- `DB_COLLATION`
- `DB_TIMEZONE`
