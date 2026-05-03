# 🧰 Helper Functions

The **Yurni Framework** includes a collection of global helper functions designed to streamline your development process and reduce boilerplate code.

---

## 🌍 Core Helpers

### `env($key, $default = null)`
Retrieves a value from your `.env` file.
```php
$host = env('DB_HOST', '127.0.0.1');
```

### `config($key, $default = null)`
Reads a configuration value from the `Config` registry or environment.
```php
$debug = config('APP_DEBUG', false);
```

### `base_path($path = '')`
Returns the absolute path to your project's root directory.
```php
$logPath = base_path('storage/logs/app.log');
```

---

## 🎨 View & UI Helpers

### `view($view, $args = [])`
Renders a template and returns the HTML string.
```php
echo view('welcome', ['user' => 'Guest']);
```

### `csrf_field()`
Generates a hidden HTML input field containing the CSRF token.
```html
<form method="POST">
    <?= csrf_field() ?>
</form>
```

### `csrf_token()`
Retrieves the current CSRF token string.
```php
$token = csrf_token();
```

---

## 🛠️ Logic & Session Helpers

### `redirect($url, int $status = 302, bool $allowExternal = false)`
Sends a redirect header and terminates execution immediately.
```php
redirect('/dashboard');
```

### `session($key = null, $value = null)`
Get or set session values easily.
- **Get**: `session('user_id')`
- **Set**: `session('user_id', 42)`

### `flash($key = null, $message = null)`
Set or retrieve "flash" messages that expire after one read.
```php
flash('success', 'Operation completed!');
$msg = flash('success');
```

---

## 🗄️ Database & Debugging

### `db()`
Returns the singleton instance of the database connection.
```php
$users = db()->table('users')->where('active', 1)->get();
```

### `dd(...$vars)`
"Dump and Die" - prints the given variables in a readable format and stops script execution.
```php
dd($userData, $request->inputs());
```

---

## 💡 Usage Tip
These helpers are globally available throughout your application. You can use them in Controllers, Models, and even directly in your Template files (if PHP execution is allowed).
