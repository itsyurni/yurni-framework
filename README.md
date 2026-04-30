# 📖 دليل استخدام Yurni Framework

---

## 1. 🚀 الإعداد الأولي (Bootstrap)

### ملف `.env`
```env
APP_DEBUG=true

DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=myapp
DB_USER=root
DB_PASS=secret
DB_CHARSET=utf8mb4
```

### ملف `index.php` (نقطة الدخول)
```php
<?php
require_once __DIR__ . '/vendor/autoload.php';

use yurni\Application;

$app = new Application(__DIR__);

// تسجيل المسارات
$app->get('/', function () {
    return '<h1>مرحباً بك في Yurni!</h1>';
});

$app->run();
```

### ملف `composer.json`
```json
{
    "require": {
        "itsyurni/yurni-framework": "^1.0"
    },
    "autoload": {
        "psr-4": {
            "App\\": "app/"
        }
    }
}
```

---

## 2. 🗺️ التوجيه (Routing)

```php
// GET بسيط
$app->get('/home', function () {
    return '<h1>الصفحة الرئيسية</h1>';
});

// POST
$app->post('/login', function () {
    return 'تم تسجيل الدخول';
});

// مسار مع معامل ديناميكي {id}
$app->get('/users/{id}', function ($id) {
    return "المستخدم رقم: $id";
});

// مسار مع معاملات متعددة
$app->get('/posts/{category}/{slug}', function ($category, $slug) {
    return "القسم: $category | المقال: $slug";
});

// PUT, PATCH, DELETE
$app->put('/users/{id}',    [UserController::class, 'update']);
$app->patch('/users/{id}',  [UserController::class, 'patch']);
$app->delete('/users/{id}', [UserController::class, 'destroy']);

// مسار يقبل أي طريقة (GET, POST, PUT, DELETE, PATCH)
$app->any('/api/ping', function () {
    return ['status' => 'ok'];
});

// مسار يقبل طرقاً محددة فقط
$app->only(['get', 'post'], '/contact', [ContactController::class, 'handle']);

// إضافة Middleware للمسار
$app->get('/dashboard', [DashboardController::class, 'index'])->middleware('auth');

// تسلسل Middlewares متعددة
$app->post('/admin/users', [AdminController::class, 'store'])
    ->middleware(['auth', 'admin']);

// اسم المسار
$app->get('/profile', [ProfileController::class, 'show'])->setName('profile');
```

---

## 3. 🎮 المتحكمات (Controllers)

```php
<?php
// app/Controllers/UserController.php
namespace App\Controllers;

use yurni\Controller;
use yurni\Http\Request;
use yurni\Http\Response;

class UserController extends Controller
{
    // عرض قائمة المستخدمين
    public function index(): string
    {
        $users = $this->table('users')->get();
        return $this->render('users.index', ['users' => $users]);
    }

    // حقن Request عبر DI تلقائياً
    public function show(Request $request, $id): string
    {
        $user = $this->table('users')->where('id', $id)->first();

        if (!$user) {
            http_response_code(404);
            return 'المستخدم غير موجود';
        }

        return $this->render('users.show', ['user' => $user]);
    }

    // إنشاء مستخدم جديد
    public function store(Request $request): Response
    {
        $name  = $request->input('name');
        $email = $request->sanitizeEmail('email');

        $this->table('users')->insert([
            'name'  => $name,
            'email' => $email,
        ]);

        return $this->response->redirect('/users');
    }

    // تحديث مستخدم
    public function update(Request $request, $id): Response
    {
        $this->table('users')
            ->where('id', $id)
            ->update(['name' => $request->input('name')]);

        return $this->response->redirect("/users/$id");
    }

    // حذف مستخدم
    public function destroy($id): Response
    {
        $this->table('users')->where('id', $id)->delete();
        return $this->response->redirect('/users');
    }

    // إرجاع JSON مباشرة
    public function apiIndex(): Response
    {
        $users = $this->table('users')->get();
        return $this->response->json(['data' => $users]);
    }
}
```

---

## 4. 🗄️ قاعدة البيانات (Database)

### Query Builder مباشرة

```php
use yurni\Db;

$db = Db::getInstance();

// SELECT كل السجلات
$users = $db->table('users')->get();

// SELECT شرطي
$admins = $db->table('users')
    ->where('role', 'admin')
    ->where('active', 1)
    ->get();

// SELECT أول سجل
$user = $db->table('users')->where('email', 'user@example.com')->first();

// SELECT أعمدة محددة
$names = $db->table('users')->select(['id', 'name', 'email'])->get();

// INSERT وإرجاع الـ ID
$newId = $db->table('users')->insertGetId([
    'name'       => 'أحمد',
    'email'      => 'ahmed@example.com',
    'created_at' => date('Y-m-d H:i:s'),
]);

// INSERT متعدد
$db->table('users')->insert([
    ['name' => 'علي',   'email' => 'ali@example.com'],
    ['name' => 'سارة', 'email' => 'sara@example.com'],
]);

// UPDATE
$db->table('users')->where('id', 5)->update(['name' => 'اسم جديد']);

// DELETE
$db->table('users')->where('id', 5)->delete();

// WHERE متقدم
$db->table('orders')
    ->where('status', 'pending')
    ->whereIn('user_id', [1, 2, 3])
    ->whereNull('deleted_at')
    ->orderBy('created_at', 'desc')
    ->limit(10)
    ->get();

// Pagination
$result = $db->table('posts')->paginate(page: 1, perPage: 15);
// النتيجة: ['data' => [...], 'total' => 100, 'per_page' => 15, 'current_page' => 1]

// Transaction
$db->transaction(function ($db) {
    $db->table('accounts')->where('id', 1)->update(['balance' => 500]);
    $db->table('accounts')->where('id', 2)->update(['balance' => 1500]);
});

// Raw SQL
$users = $db->select('SELECT * FROM users WHERE age > ?', [18]);
$id    = $db->insertGetId('INSERT INTO logs (msg) VALUES (?)', ['تسجيل دخول']);
```

---

## 5. 🏛️ النماذج (Models)

```php
<?php
// app/Models/User.php
namespace App\Models;

use yurni\Model;

class User extends Model
{
    protected string $table = 'users'; // اختياري — يُكتشف تلقائياً من اسم الكلاس
    protected string $primaryKey = 'id';
    protected bool $softDeletes = true; // تفعيل الحذف اللين
}
```

```php
// الاستخدام
use App\Models\User;

$user = new User();

// جلب الكل
$users = $user->all();

// جلب بـ ID
$found = $user->find(1);

// جلب أو throw exception
$found = $user->findOrFail(1);

// إنشاء
$id = $user->create(['name' => 'محمد', 'email' => 'mo@example.com']);

// تحديث
$user->updateById(1, ['name' => 'اسم محدث']);

// حذف (لين أو حقيقي حسب $softDeletes)
$user->deleteById(1);

// استعادة سجل محذوف (Soft Delete)
$user->restore(1);

// حذف نهائي
$user->forceDeleteById(1);

// firstOrCreate — جلب أو إنشاء
$record = $user->firstOrCreate(
    ['email' => 'test@example.com'],
    ['name'  => 'مستخدم جديد']
);

// updateOrCreate
$record = $user->updateOrCreate(
    ['email' => 'test@example.com'],
    ['name'  => 'اسم محدث']
);

// Pagination
$page = $user->paginate(page: 1, perPage: 10);

// إضافة شروط مخصصة (Query Builder عبر __call)
$activeUsers = $user->where('active', 1)->orderBy('name')->get();

// العلاقات
// hasMany — مستخدم لديه طلبات متعددة
$orders = $user->hasMany(Order::class, foreignKey: 'user_id', localValue: 1)->get();

// belongsTo — طلب ينتمي لمستخدم
$owner = $user->belongsTo(User::class, foreignKey: 'user_id', value: 5)->first();

// hasOne
$profile = $user->hasOne(Profile::class, foreignKey: 'user_id', localValue: 1)->first();

// Static calls
$all = User::all(); // يعمل مثل new User()->all()
```

---

## 6. 📝 القوالب (Templates / Views)

### إعداد محرك القوالب في `index.php`

```php
use yurni\View;

View::setup([
    'temp_path'  => __DIR__ . '/views',
    'cache_path' => __DIR__ . '/storage/cache/views',
    'cache'      => false, // true في الإنتاج
    'optimize'   => false,
]);
```

### ملف قالب `views/layout.php`

```html
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>{% yield title %}</title>
</head>
<body>
    <nav>
        <a href="/">الرئيسية</a>
        <a href="/users">المستخدمون</a>
    </nav>

    <main>
        {% yield content %}
    </main>
</body>
</html>
```

### ملف قالب `views/users/index.php`

```html
{% extends 'layout' %}

{% block title %}قائمة المستخدمين{% endblock %}

{% block content %}
    <h1>المستخدمون</h1>

    {% if count($users) > 0 %}
        <ul>
        {% foreach $users as $user %}
            <li>
                {{{ $user['name'] }}}  {{-- نص مُعقَّم (escaped) --}}
                <a href="/users/{{ $user['id'] }}">عرض</a>  {{-- raw --}}
            </li>
        {% endforeach %}
        </ul>
    {% else %}
        <p>لا يوجد مستخدمون.</p>
    {% endif %}

    {% include 'partials/pagination' %}
{% endblock %}
```

### صياغة القوالب

| الصياغة | المعنى |
|---------|--------|
| `{{ $var }}` | طباعة متغير (raw) |
| `{{{ $var }}}` | طباعة متغير مُعقَّمة (escaped) |
| `{% if $x %} ... {% endif %}` | شرط |
| `{% foreach $arr as $item %} ... {% endforeach %}` | حلقة |
| `{% each $arr as $item %} ... {% endeach %}` | حلقة (مرادف) |
| `{% for $i=0; $i<5; $i++ %} ... {% endfor %}` | حلقة for |
| `{% while $x %} ... {% endwhile %}` | حلقة while |
| `{% unless $x %} ... {% endunless %}` | عكس if |
| `{% extends 'layout' %}` | الوراثة من قالب |
| `{% include 'partial' %}` | تضمين قالب جزئي |
| `{% block name %} ... {% endblock %}` | تعريف block |
| `{% yield name %}` | عرض block |
| `{% verbatim %} ... {% endverbatim %}` | حماية كود Vue/React |
| `{% $var = 5 %}` | PHP خام |

---

## 7. 🔐 الـ Middleware

### تعريف Middleware

```php
// app/Middleware/AuthMiddleware.php
namespace App\Middleware;

use yurni\Http\Request;
use yurni\Http\Response;

class AuthMiddleware
{
    public function __invoke(Request $request, Response $response): bool
    {
        if (!isset($_SESSION['user_id'])) {
            $response->redirect('/login');
            return false;
        }
        return true;
    }
}
```

### تسجيل وتطبيق Middleware

```php
// في index.php
use App\Middleware\AuthMiddleware;
use App\Middleware\AdminMiddleware;

$app->setMiddleware('auth',  AuthMiddleware::class);
$app->setMiddleware('admin', AdminMiddleware::class);

// تطبيق على مسار
$app->get('/dashboard', [DashboardController::class, 'index'])->middleware('auth');

// أو بالأسلوب الديناميكي (يعمل مثل ->middleware('auth'))
$app->get('/dashboard', [DashboardController::class, 'index'])->auth();

// Middlewares متعددة
$app->get('/admin', [AdminController::class, 'index'])->middleware(['auth', 'admin']);
```

---

## 8. 📨 Request — الطلب

```php
// داخل Controller أو Closure
public function store(Request $request): string
{
    // جلب مدخل
    $name  = $request->input('name');
    $email = $request->input('email');

    // خاصية سحرية
    $phone = $request->phone; // يعادل input('phone')

    // تنقية
    $safeEmail = $request->sanitizeEmail('email');
    $safeText  = $request->sanitizeString('message');

    // نوع الطلب
    $request->isPost();   // true/false
    $request->isGet();
    $request->isAjax();
    $request->isHttps();

    // مسار الطلب الحالي
    $path = $request->getPath(); // مثل: /users/5

    // ملف مرفوع
    $file = $request->file('avatar');

    // ملفات متعددة
    $files = $request->multiFiles('photos');

    // JSON body (مثال: API)
    $json = json_decode($request->body(), true);

    // Session
    $session = $request->getSession();
}
```

---

## 9. 📤 Response — الاستجابة

```php
// داخل Controller
public function show(): Response
{
    // JSON
    return $this->response->json(['name' => 'أحمد', 'age' => 25]);

    // JSON مع كود حالة
    return $this->response->json(['error' => 'غير مصرح'], 401);

    // HTML
    return $this->response->html('<h1>مرحباً</h1>');

    // HTML مع كود حالة
    return $this->response->html('<h1>غير موجود</h1>', 404);

    // Redirect
    return $this->response->redirect('/home');

    // Header مخصص
    return $this->response
        ->setHeader('X-Custom', 'value')
        ->setStatusCode(200)
        ->setBody('OK');
}
```

---

## 10. 🔒 CSRF Protection

### في النموذج (Form)

```html
<form method="POST" action="/login">
    <!-- إضافة حقل CSRF مخفي -->
    <?= csrf_field() ?>

    <input type="text" name="email">
    <input type="password" name="password">
    <button type="submit">دخول</button>
</form>
```

### أو في القالب

```html
<form method="POST" action="/login">
    {{% echo csrf_field(); %}}
    ...
</form>
```

> ✅ الإطار يتحقق من CSRF تلقائياً لكل طلبات POST/PUT/PATCH/DELETE.

---

## 11. 📂 رفع الملفات (File Upload)

```php
public function upload(Request $request): Response
{
    if ($request->hasFile('photo')) {
        $file = $request->file('photo');

        // المعلومات الأساسية
        $file->getName();      // اسم الملف الأصلي
        $file->getSize();      // الحجم بالبايت
        $file->getMimeType();  // مثل: image/jpeg
        $file->getExtension(); // مثل: jpg

        // رفع الملف
        $saved = $file->move(__DIR__ . '/public/uploads', 'avatar.jpg');

        if ($saved) {
            return $this->response->json(['message' => 'تم الرفع بنجاح']);
        }
    }

    return $this->response->json(['error' => 'لا يوجد ملف'], 400);
}
```

---

## 12. 🎯 Session

```php
use yurni\Http\Session;

$session = new Session();

// تخزين
$session->set('user_id', 42);
$session->set('user_name', 'أحمد');

// جلب
$userId = $session->get('user_id');

// Flash Messages (تختفي بعد القراءة)
$session->flash('success', 'تم الحفظ بنجاح!');
$msg = $session->getFlash('success');

// حذف
$session->delete('user_id');

// تفريغ كامل
$session->clear();

// أو بالدوال المساعدة
session('user_id', 42);         // set
$id = session('user_id');       // get
flash('success', 'تم الحفظ'); // set flash
$msg = flash('success');        // get flash
```

---

## 13. 🧰 الدوال المساعدة (Helpers)

```php
// .env و Config
$debug = env('APP_DEBUG', false);
$db    = config('db_name');

// View
$html = view('users.index', ['users' => $users]);

// Redirect (يُوقف التنفيذ)
redirect('/home');

// CSRF
$field = csrf_field(); // <input type="hidden" ...>
$token = csrf_token();

// Database
$db = db(); // Db::getInstance()

// Session
session('key', 'value'); // set
$val = session('key');   // get

// Flash
flash('error', 'حدث خطأ!');
$msg = flash('error');

// Paths
$path = base_path('storage/logs/app.log');

// Debug
dd($users);           // Dump and Die
dd($user1, $user2);   // متعدد
```

---

## 14. 🏗️ مثال تطبيق CRUD كامل

```php
<?php
// index.php
require 'vendor/autoload.php';

use yurni\Application;
use yurni\View;
use App\Controllers\PostController;
use App\Middleware\AuthMiddleware;

$app = new Application(__DIR__);

View::setup([
    'temp_path'  => __DIR__ . '/views',
    'cache_path' => __DIR__ . '/storage/cache',
    'cache'      => false,
]);

// Middlewares
$app->setMiddleware('auth', AuthMiddleware::class);

// Routes
$app->get('/posts',           [PostController::class, 'index']);
$app->get('/posts/{id}',      [PostController::class, 'show']);
$app->get('/posts/create',    [PostController::class, 'create'])->middleware('auth');
$app->post('/posts',          [PostController::class, 'store'])->middleware('auth');
$app->get('/posts/{id}/edit', [PostController::class, 'edit'])->middleware('auth');
$app->put('/posts/{id}',      [PostController::class, 'update'])->middleware('auth');
$app->delete('/posts/{id}',   [PostController::class, 'destroy'])->middleware('auth');

// معالج 404 مخصص
$app->getRouter()->handle('404', function () {
    http_response_code(404);
    return view('errors.404');
});

$app->run();
```

```php
<?php
// app/Controllers/PostController.php
namespace App\Controllers;

use yurni\Controller;
use yurni\Http\Request;

class PostController extends Controller
{
    public function index(): string
    {
        $posts = $this->table('posts')
            ->orderBy('created_at', 'desc')
            ->paginate(1, 10);

        return $this->render('posts.index', ['posts' => $posts]);
    }

    public function show($id): string
    {
        $post = $this->table('posts')->where('id', $id)->first();
        return $this->render('posts.show', ['post' => $post]);
    }

    public function create(): string
    {
        return $this->render('posts.create');
    }

    public function store(Request $request)
    {
        $this->table('posts')->insertGetId([
            'title'      => $request->sanitizeString('title'),
            'body'       => $request->input('body'),
            'user_id'    => $_SESSION['user_id'],
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        flash('success', 'تم نشر المقال!');
        return $this->response->redirect('/posts');
    }

    public function update(Request $request, $id)
    {
        $this->table('posts')->where('id', $id)->update([
            'title' => $request->sanitizeString('title'),
            'body'  => $request->input('body'),
        ]);

        return $this->response->redirect("/posts/$id");
    }

    public function destroy($id)
    {
        $this->table('posts')->where('id', $id)->delete();
        return $this->response->redirect('/posts');
    }
}
```
