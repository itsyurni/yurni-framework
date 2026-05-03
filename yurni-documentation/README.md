# 📖 Yurni Framework Documentation

Welcome to the official documentation for the **Yurni Framework**. This guide provides comprehensive information to help you master the framework, from initial setup to advanced application architecture.

---

## 📑 Table of Contents

- [🚀 Installation & Setup](installation.md) - Get your environment ready.
- [🗺️ Routing System](routing.md) - Learn how to define and manage application routes.
- [🎮 Controllers](controllers.md) - Structure your application logic effectively.
- [🏛️ Models & ORM](models.md) - Handle data and database relationships.
- [🗄️ Database & Query Builder](database.md) - Fluent and secure database interactions.
- [🎨 Views & Template Engine](views.md) - Build beautiful interfaces with our native engine.
- [📨 HTTP Helpers](http.md) - Deep dive into Request and Response objects.
- [🔒 Security](security.md) - Protect your application from common vulnerabilities.
- [🧰 Helpers](helpers.md) - Handy utility functions to speed up development.
- [⚠️ Error Handling](errors.md) - Managing exceptions and custom error pages.

---

## 🧐 What is Yurni?

**Yurni** is a minimalist PHP MVC framework designed for developers who want a balance between performance and productivity. It avoids the bloat of larger frameworks while providing essential features for modern web development.

### Core Philosophy
- **Simplicity**: No complex configurations or steep learning curves.
- **Speed**: Optimized core for fast execution.
- **Security**: Default protections against common web threats.

---

## 🚀 Quick Example

Here is how simple it is to get a Yurni application running:

```php
<?php
require_once __DIR__ . '/../vendor/autoload.php';

use yurni\Application;

// Initialize the application
$app = new Application(__DIR__ . '/../');

// Define a simple route
$app->get('/', function () {
    return '<h1>Welcome to Yurni!</h1>';
});

// Run the app
$app->run();
```

---

## 🛠️ Requirements

Before you begin, ensure your environment meets the following requirements:
- **PHP**: 8.0 or higher.
- **Composer**: For dependency management.
- **Web Server**: Apache, Nginx, or the built-in PHP server.
- **Database**: MySQL/MariaDB or SQLite (optional).

---

## 💡 Pro Tip

You can always define custom error pages in `app/views/Exception/` by creating files like `404.php`, `500.php`, or a general `exception.php` to match your application's branding.
