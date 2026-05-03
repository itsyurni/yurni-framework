# ⚠️ Error Handling

The **Yurni Framework** provides a centralized and robust error handling system designed to provide helpful information during development and safe, professional responses in production.

---

## 🏗️ Core Mechanism

All request handling is wrapped in a high-level `try/catch` block within the `Application::run()` method. This ensures that no exception goes unhandled.

```php
public function run(): void
{
    try {
        echo $this->router->resolve();
    } catch (\Throwable $e) {
        // Centralized exception rendering
        $this->renderException($e);
    }
}
```

---

## 🛠️ Debug Mode

Yurni behaves differently based on the `APP_DEBUG` setting in your `.env` file:

- **`APP_DEBUG=true`**: Displays a detailed error page (using `exception.php`) that includes the stack trace, file path, and line number where the error occurred. Ideal for local development.
- **`APP_DEBUG=false`**: Displays a generic, user-friendly error page (using `500.php`). Sensitive system details are hidden to protect your server.

---

## 🎨 Customizing Error Pages

You can easily override the default error templates by creating your own in your application's view directory. Yurni searches for them in the following order:

1. `app/views/Exception/404.php` (For Route Not Found)
2. `app/views/Exception/500.php` (For Internal Server Errors)
3. `app/views/Exception/exception.php` (For Detailed Debug Views)

If these files do not exist, Yurni will fall back to its internal default templates.

---

## 📝 Error Logging

Before an error is displayed to the user, Yurni automatically logs the full exception details. This allows you to monitor production issues by checking your server's error logs (or the path defined in `php.ini`).

---

## 🛡️ Exception Classification

Yurni categorizes exceptions to help you identify the source of the problem quickly:
- **Framework Error**: Issues related to framework configuration or usage.
- **Internal Framework Error**: Rare issues within the framework's core logic.
- **Application Error**: Standard exceptions thrown within your application code (Controllers, Models).

---

## 💡 Production Best Practices

- **Disable Debugging**: Always set `APP_DEBUG=false` in production environments.
- **Custom Branding**: Design custom 404 and 500 pages that match your website's look and feel.
- **Monitoring**: Regularly check your error logs to catch and fix silent failures.
