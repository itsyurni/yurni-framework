# 🔒 Security in Yurni

Security is a core priority for the **Yurni Framework**. We provide several built-in mechanisms to protect your application from common web vulnerabilities like CSRF, Session Hijacking, and malicious file uploads.

---

## 🛡️ CSRF Protection

Cross-Site Request Forgery (CSRF) is prevented using the `yurni\Security\Csrf` class. Yurni generates a unique token for every session that must be submitted with state-changing requests (POST, PUT, PATCH, DELETE).

### Adding the CSRF Field to Forms
Use the helper function to insert a hidden input field into your HTML forms:
```html
<form method="POST" action="/submit">
    <?= csrf_field() ?>
    <input name="name" />
</form>
```

### Verifying the Token
Yurni provides a dedicated `csrf` middleware. It is highly recommended to apply this to all non-GET routes.
```php
$app->post('/submit', [FormController::class, 'submit'])->middleware('csrf');
```
*Note: The middleware automatically checks for the token in either the form inputs or the `X-CSRF-TOKEN` request header.*

---

## 📂 Secure File Uploads

Handling user-uploaded files is inherently risky. The `yurni\Http\FileUpload` class automates several safety checks:
- Verifies files were uploaded via `HTTP POST`.
- Validates real MIME types using `finfo`, not just extensions.
- Blocks execution-capable extensions (e.g., `.php`, `.exe`, `.sh`).
- Prevents file overwriting unless explicitly requested.

### Example Usage
```php
$file = $request->file('avatar');

if ($file && $file->isValid()) {
    $file->validate(
        allowedTypes: ['image/jpeg', 'image/png'], 
        maxSize: 2048, // KB
        allowedExtensions: ['jpg', 'png']
    )->moveWithUniqueName('public/uploads');
}
```

---

## 🔑 Session Security

Yurni hardens standard PHP sessions with sensible defaults:
- **HttpOnly**: Cookies are inaccessible via JavaScript.
- **SameSite=Lax**: Protects against CSRF in cross-site contexts.
- **Secure**: Cookies are only sent over HTTPS (when detected).

> [!IMPORTANT]
> To prevent Session Fixation attacks, always call `session()->regenerate()` after a user logs in.

---

## 📝 User Input Handling

- **Sanitization**: Use `$request->sanitizeString()` or `$request->sanitizeEmail()` to clean raw input.
- **Validation**: Use the `Request::validate()` method to ensure data conforms to expected patterns before processing.

---

## ⚙️ Production Hardening

When deploying your application, ensure these settings are configured in your `.env` file:

| Setting | Recommendation | Reason |
|---------|----------------|--------|
| `APP_DEBUG` | `false` | Prevents exposing sensitive stack traces to users. |
| `view_allow_php` | `false` | Disables raw PHP execution within templates. |
| `DB_PASS` | Strong Password | Use unique, long passwords for database access. |

---

## 💡 Best Practice
Always define custom error pages in `app/views/Exception/` to hide system details from end-users while providing a professional interface for errors.
