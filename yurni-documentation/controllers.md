# المتحكمات (Controllers)

المتحكم هو نقطة الدخول لمنطق التطبيق. يستخدم Yurni كلاس أساسي `yurni\Controller` يوفر لك وظائف شائعة.

## إنشاء متحكم جديد

```php
<?php
namespace App\Controllers;

use yurni\Controller;
use yurni\Http\Request;
use yurni\Http\Response;

class UserController extends Controller
{
    public function index(): string
    {
        $users = $this->table('users')->get();
        return $this->render('users.index', ['users' => $users]);
    }
}
```

## أهم الدوال المتاحة في `Controller`

### `render(string $view, array $args = [])`

تعرض قالبًا باستخدام محرك القوالب.

```php
return $this->render('users.show', ['user' => $user]);
```

### `db(): Db`

يُرجع كائن قاعدة البيانات.

```php
$user = $this->db()->table('users')->first();
```

### `query(): QueryBuilder`

بدء استعلام عبر Query Builder.

```php
$posts = $this->query()->table('posts')->where('published', 1)->get();
```

### `table(string $table): QueryBuilder`

بدء استعلام على جدول محدد بسرعة.

```php
$this->table('users')->where('active', 1)->get();
```

### `transaction(callable $callback): mixed`

تنفيذ مجموعة عمليات قاعدة بيانات داخل معاملة.

```php
$this->transaction(function () use ($data) {
    $this->table('accounts')->insert($data);
    // ... عمليات أخرى
});
```

## مثال CRUD كامل

```php
public function store(Request $request): Response
{
    $validated = $request->validate([
        'name' => 'required|string',
        'email' => 'required|email',
    ]);

    $this->table('users')->insert($validated);
    return $this->response->redirect('/users');
}

public function show($id): string
{
    $user = $this->table('users')->where('id', $id)->first();
    if (!$user) {
        http_response_code(404);
        return 'المستخدم غير موجود';
    }
    return $this->render('users.show', ['user' => $user]);
}
```

## حقن التبعيات الآلية

يمكنك طلب `Request`, `Response`, أو كائنات أخرى في توقيع الدالة وسيستدعها الحاوية تلقائيًا:

```php
public function update(Request $request, int $id): Response
{
    // ...
}
```

## الملاحظات

- `Controller` ينتقل بكائن `Request` و `Response` و `Db` تلقائيًا.
- استخدم `render()` لعرض القوالب، و `response->json()` لردود API.
