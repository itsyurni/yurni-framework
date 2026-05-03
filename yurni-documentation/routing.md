# 🗺️ Routing System

The **Yurni Framework** features a clean, expressive, and high-performance routing system. It allows you to map URLs to specific application logic using a variety of HTTP methods, dynamic parameters, and middleware.

---

## 🚦 Basic Route Registration

Routes are typically registered in your entry file using the `$app` instance.

### Standard HTTP Methods
You can define routes for all common HTTP methods:

```php
// GET Request
$app->get('/home', function () {
    return 'Welcome Home!';
});

// POST Request
$app->post('/login', [LoginController::class, 'authenticate']);

// PUT/PATCH/DELETE Requests
$app->put('/users/{id}', [UserController::class, 'update']);
$app->patch('/users/{id}', [UserController::class, 'patch']);
$app->delete('/users/{id}', [UserController::class, 'destroy']);
```

### Flexible Registration
If you need a route to respond to any method or a specific subset:

```php
// Respond to ANY HTTP method
$app->any('/api/ping', function () {
    return ['status' => 'pong'];
});

// Respond to specific methods ONLY
$app->only(['get', 'post'], '/contact', [ContactController::class, 'handle']);
```

---

## 🎭 Dynamic Route Parameters

Capture values from the URL by wrapping segments in curly braces `{}`. These values are automatically passed as arguments to your callback or controller method.

```php
$app->get('/users/{id}', function ($id) {
    return "User Profile ID: $id";
});

// Multiple parameters
$app->get('/posts/{category}/{slug}', function ($category, $slug) {
    return "Category: $category | Article: $slug";
});
```

---

## 🛡️ Route Middleware

Protect your routes or process requests before they reach your controller by attaching middleware.

```php
// Single middleware
$app->get('/dashboard', [DashboardController::class, 'index'])->middleware('auth');

// Multiple middlewares
$app->post('/admin/users', [AdminController::class, 'store'])
    ->middleware(['auth', 'admin']);
```

> [!TIP]
> You can also use dynamic method calls for middleware if registered: `$app->get('/profile', ...)->auth();`

---

## 🏷️ Named Routes

Naming your routes makes it easier to generate URLs and manage navigation without hardcoding paths.

```php
$app->get('/user/profile/settings', [ProfileController::class, 'show'])->setName('profile.settings');
```

---

## 🔄 Redirection

Redirect users to different routes using the `Response` object or the global helper.

```php
// Within a Controller
return $this->response->redirect('/dashboard');

// Global helper (Stops execution immediately)
redirect('/login');
```

---

## ⚙️ Technical Details

- **Regex-Based**: Internally, the router converts `{param}` placeholders into safe regular expression named capture groups.
- **Efficient Matching**: Routes are compiled and matched sequentially, ensuring minimal overhead even with hundreds of definitions.
- **Entry Point**: In the `Application` class, routing methods serve as a fluent proxy to the core `Router` component.
