# 🪐 Yurni Framework

[![PHP Version](https://img.shields.io/badge/php-%5E8.0-777bb4.svg?style=flat-square&logo=php)](https://www.php.net/)
[![License](https://img.shields.io/badge/license-MIT-green.svg?style=flat-square)](LICENSE)
[![Framework](https://img.shields.io/badge/framework-Yurni-blue.svg?style=flat-square)](https://github.com/itsyurni/yurni-framework)

Yurni is a lightweight, high-performance PHP MVC framework designed for developers who value simplicity, speed, and elegance. It provides a robust set of features to build modern web applications without the overhead of heavy frameworks.

---

## ✨ Key Features

- **🚀 Expressive Routing**: Define clean, RESTful routes with ease.
- **🏗️ MVC Architecture**: Solid separation of concerns for maintainable code.
- **💉 Dependency Injection**: Powerful DI Container for seamless object management.
- **🛠️ Query Builder**: Fluent and secure database interaction layer.
- **🛡️ Security First**: Built-in CSRF protection and data sanitization.
- **🎨 Template Engine**: Fast, intuitive view rendering with inheritance.
- **⚡ Middleware Support**: Process requests efficiently before they reach your logic.
- **📂 File Handling**: Robust tools for secure file uploads and management.

---

## 🛠️ Installation

Get started with Yurni in just a few steps:

1. **Clone the Repository**:
   ```bash
   git clone https://github.com/itsyurni/yurni-framework.git
   ```

2. **Install Dependencies**:
   ```bash
   composer install
   ```

3. **Configure Environment**:
   ```bash
   cp .env.example .env
   ```
   *Edit `.env` to configure your database and app settings.*

4. **Launch Dev Server**:
   ```bash
   php -S localhost:8000 -t public
   ```

---

## 🚀 Quick Start

Creating your first route is simple. In your entry point (e.g., `index.php`):

```php
<?php
require_once __DIR__ . '/vendor/autoload.php';

use yurni\Application;

$app = new Application(__DIR__);

// Define a simple GET route
$app->get('/', function () {
    return '<h1>Welcome to Yurni!</h1>';
});

$app->run();
```

---

## 📖 Documentation Highlights

Explore the core components of the Yurni framework:

### 🗺️ Routing
```php
// Dynamic route parameters
$app->get('/users/{id}', function ($id) {
    return "User Profile ID: $id";
});

// Using Controllers
$app->get('/profile', [ProfileController::class, 'show'])->setName('profile');

// Middleware protection
$app->get('/dashboard', [AdminController::class, 'index'])->middleware('auth');
```

### 🎮 Controllers
```php
namespace App\Controllers;

use yurni\Controller;
use yurni\Http\Request;

class UserController extends Controller
{
    public function show(Request $request, $id)
    {
        $user = $this->table('users')->where('id', $id)->first();
        return $this->render('users.show', compact('user'));
    }
}
```

### 🗄️ Database & Query Builder
```php
use yurni\Db;

$db = Db::getInstance();

// Fluent interface
$users = $db->table('users')
    ->where('active', 1)
    ->orderBy('created_at', 'desc')
    ->limit(10)
    ->get();
```

### 🎨 Template Engine
```html
{% extends 'layout' %}

{% block title %}Users List{% endblock %}

{% block content %}
    <h1>Users</h1>
    {% foreach $users as $user %}
        <li>{{{ $user['name'] }}}</li>
    {% endforeach %}
{% endblock %}
```

---

## 📂 Detailed Documentation

For a deep dive into every feature, check out our full documentation suite:

- [🚀 Installation & Setup](yurni-documentation/installation.md)
- [🗺️ Routing & Middlewares](yurni-documentation/routing.md)
- [🎮 Controllers](yurni-documentation/controllers.md)
- [🏛️ Models & Relationships](yurni-documentation/models.md)
- [🗄️ Database & Query Builder](yurni-documentation/database.md)
- [🎨 Views & Template Engine](yurni-documentation/views.md)
- [🔒 Security & CSRF](yurni-documentation/security.md)
- [📨 Request & Response](yurni-documentation/http.md)
- [🧰 Helpers & Utilities](yurni-documentation/helpers.md)

---

## 🤝 Contributing

Contributions are what make the open source community such an amazing place to learn, inspire, and create. Any contributions you make are **greatly appreciated**.

1. Fork the Project
2. Create your Feature Branch (`git checkout -b feature/AmazingFeature`)
3. Commit your Changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the Branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

## 📄 License

Distributed under the MIT License. See `LICENSE` for more information.

---

<p align="center">Built with ❤️ by the Yurni Team</p>