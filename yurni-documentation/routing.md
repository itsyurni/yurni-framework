# التوجيه (Routing)

نظام التوجيه في Yurni بسيط وعصري. يمكنك تعريف المسارات بطرق HTTP مختلفة وربطها بدوال Closure أو متحكمات.

## دوال التسجيل الأساسية

### GET

```php
$app->get('/home', function () {
    return 'مرحبا!';
});
```

### POST

```php
$app->post('/login', [LoginController::class, 'authenticate']);
```

### PUT, PATCH, DELETE

```php
$app->put('/users/{id}', [UserController::class, 'update']);
$app->patch('/users/{id}', [UserController::class, 'patch']);
$app->delete('/users/{id}', [UserController::class, 'destroy']);
```

### أي طريقة request

```php
$app->any('/api/ping', function () {
    return ['status' => 'ok'];
});
```

### طرق محددة فقط

```php
$app->only(['get', 'post'], '/contact', [ContactController::class, 'handle']);
```

## المسارات الديناميكية

يمكنك استخدام `{param}` لالتقاط قيمة من الرابط:

```php
$app->get('/users/{id}', function ($id) {
    return "المستخدم رقم: $id";
});
```

### أمثلة متعددة

```php
$app->get('/posts/{category}/{slug}', function ($category, $slug) {
    return "القسم: $category | المقال: $slug";
});
```

## إعادة التوجيه بين المسارات

يمكنك استخدام `Response::redirect()`:

```php
return $this->response->redirect('/dashboard');
```

أو المساعدة `redirect()` العالمية:

```php
redirect('/login');
```

## Middlewares على المسار

يمكنك تمرير اسم Middleware لكل مسار:

```php
$app->get('/dashboard', [DashboardController::class, 'index'])->middleware('auth');
```

أو استخدام مجموعة Middlewares:

```php
$app->post('/admin/users', [AdminController::class, 'store'])
    ->middleware(['auth', 'admin']);
```

## تسمية المسارات

يمكنك وضع اسم على المسار:

```php
$app->get('/profile', [ProfileController::class, 'show'])->setName('profile');
```

هذا مفيد لاحقًا لإنشاء رابط أو البحث عن المسار.

## ملحوظة تقنية

في `App\Application`، دوال التوجيه هي واجهة بسيطة على `Router` الخاص بالإطار.
يعتمد البحث على تحويل الرابط إلى Regex داخليًا، لذلك الصيغة `{param}` آمنة وتُعامل كـ Named capture group.
