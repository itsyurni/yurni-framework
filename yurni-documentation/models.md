# النماذج (Models)

`Model` هو الطبقة المسؤولة عن التعامل مع قواعد البيانات بطريقة كائنية.
يمكنك إنشاء نموذج جديد بامتداد الكلاس الأساسي `yurni\Model`.

## إعلان نموذج جديد

```php
<?php
namespace App\Models;

use yurni\Model;

class User extends Model
{
    protected string $table = 'users';
    protected string $primaryKey = 'id';
    protected bool $softDeletes = true;
}
```

إذا لم تحدد اسم الجدول، الاسم يُستنتج تلقائيًا من اسم الكلاس.

## دوال CRUD الأساسية

### `all(array|string $columns = ['*']): array`

استرجاع جميع السجلات.

```php
$users = User::all();
```

### `find(int|string $id, array|string $columns = ['*']): ?array`

إيجاد سجل واحد بواسطة المفتاح الأساسي.

```php
$user = User::find(5);
```

### `findOrFail(int|string $id, array|string $columns = ['*']): array`

نفس `find` ولكن يرمي استثناءً إذا لم يجد.

```php
$user = User::findOrFail(5);
```

### `create(array $attributes): int`

إدراج سجل جديد وإرجاع الـ ID.

```php
$id = User::create(['name' => 'علي', 'email' => 'ali@example.com']);
```

### `updateById(int|string $id, array $attributes): int`

تحديث سجل موجود.

```php
User::updateById(5, ['name' => 'أحمد']);
```

### `deleteById(int|string $id): int`

حذف سجل. إذا كان `softDeletes` مفعلًا فسيُحدّف منطقيًا.

```php
User::deleteById(5);
```

### `restore(int|string $id): int`

استعادة سجل بعد حذف منطقي (soft delete).

```php
User::restore(5);
```

### `forceDeleteById(int|string $id): int`

حذف نهائي.

```php
User::forceDeleteById(5);
```

### `paginate(int $page = 1, int $perPage = 15): array`

دعم ترقيم الصفحات.

```php
$page = User::paginate(2, 20);
```

## العلاقات

### `belongsTo(string $related, ?string $foreignKey = null, string $ownerKey = 'id', mixed $value = null)`

علاقة `belongsTo` بسيطة.

```php
$comment->belongsTo(Post::class, 'post_id', 'id', $comment['post_id'])->first();
```

### `hasMany(string $related, ?string $foreignKey = null, mixed $localValue = null, ?string $localKey = null)`

علاقة `hasMany`.

```php
$posts = (new User())->hasMany(Post::class, 'user_id', $userId)->get();
```

### `hasOne(string $related, ?string $foreignKey = null, mixed $localValue = null, ?string $localKey = null)`

علاقة `hasOne`.

```php
$profile = (new User())->hasOne(Profile::class, 'user_id', $userId)->first();
```

## استعلامات مخصصة

يمكنك استخدام جميع دوال Query Builder من خلال أي نموذج:

```php
$activeUsers = User::where('active', 1)
    ->orderBy('created_at', 'desc')
    ->limit(20)
    ->get();
```

أو عبر نموذج:

```php
$users = (new User())->query()
    ->where('role', 'admin')
    ->get();
```

## ملاحظة

`Model` يعتمد على `Db::getInstance()` و `QueryBuilder` لإجراء جميع العمليات بأمان وقابلية إعادة الاستخدام.
