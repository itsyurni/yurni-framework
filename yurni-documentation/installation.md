# 🚀 Installation & Setup

Setting up the **Yurni Framework** is a straightforward process. This guide will walk you through the requirements and steps to get your first application up and running.

---

## 📋 Requirements

Before installing, ensure your system meets the following criteria:
- **PHP**: 8.0 or higher.
- **Composer**: For managing dependencies.
- **Web Server**: Apache, Nginx, or the built-in PHP server.
- **Database**: MySQL, MariaDB, or SQLite (optional).

---

## 🛠️ Installation Steps

### 1. Clone the Repository
Start by cloning the framework core into your project directory:
```bash
git clone https://github.com/itsyurni/yurni-framework.git my-app
cd my-app
```

### 2. Install Dependencies
Use Composer to fetch the required library dependencies:
```bash
composer install
```

### 3. Configure Environment Variables
Copy the example environment file to create your own `.env` configuration:
```bash
cp .env.example .env
```
Open the `.env` file and update your application and database settings:
```env
APP_DEBUG=true

DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=myapp
DB_USER=root
DB_PASS=secret
DB_CHARSET=utf8mb4
```

### 4. Create Entry Point
If it doesn't exist, create `public/index.php`. This file acts as the front controller for your application:
```php
<?php
require_once __DIR__ . '/../vendor/autoload.php';

use yurni\Application;

// Initialize application with root directory
$app = new Application(__DIR__ . '/..');

// Register a test route
$app->get('/', function () {
    return '<h1>Welcome to Yurni!</h1>';
});

// Execute the application
$app->run();
```

### 5. Launch the Server
Start the built-in PHP development server:
```bash
php -S localhost:8000 -t public
```
Visit `http://localhost:8000` in your browser to see your application in action.

---

## ⚙️ Advanced Configuration

You can customize framework behavior via the `.env` file or programmatically. Key view settings include:

| Key | Description | Default |
|-----|-------------|---------|
| `views_path` | Directory for template files | `app/views` |
| `views_cache_path` | Directory for cached views | `storage/cache` |
| `view_cache` | Enable/Disable view caching | `true` (Production) |
| `view_optimize` | Enable/Disable template optimization | `false` |

---

## 📂 Recommended Directory Structure

To keep your project organized, we recommend the following layout:
- `app/Controllers/`: Your application controllers.
- `app/Models/`: Your data models.
- `app/views/`: UI templates and layouts.
- `public/`: Publicly accessible files (CSS, JS, images) and `index.php`.
- `storage/`: Framework-generated files (cache, logs, uploads).
- `config/`: Custom configuration files.
- `routes/`: Separate files for route definitions.
