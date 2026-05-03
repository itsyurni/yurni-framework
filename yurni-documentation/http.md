# 📨 HTTP Helper Classes

Yurni provides a suite of classes to handle the standard HTTP request-response cycle, including request parsing, response generation, and session management.

---

## 📥 Request

The `yurni\Http\Request` class encapsulates all incoming data, providing a clean API to access headers, inputs, and server information.

### Core Retrieval Methods
- `getPath()`: Returns the cleaned request URI path.
- `getMethod()`: Returns the current HTTP method (GET, POST, etc.).
- `header(string $key, $default = null)`: Retrieves a specific request header.
- `input(string $key, $default = null)`: Retrieves a value from GET, POST, or JSON body.
- `inputs()`: Returns an associative array of all input data.
- `body()`: Returns the raw request body string (useful for raw JSON payloads).

### State Detection
Check the status or type of the incoming request:
- `isGet()`, `isPost()`, `isPut()`, `isPatch()`, `isDelete()`
- `isHttps()`: Checks if the request was made over a secure connection.
- `isAjax()`: Detects if the request is an `XMLHttpRequest`.

### Data Validation
The `validate()` method provides a quick way to enforce rules on incoming data.
```php
$validated = $request->validate([
    'username' => 'required|string|min:3',
    'email'    => 'required|email',
]);
```
*Note: If validation fails, Yurni automatically stores errors in the session and redirects the user back to the previous page.*

---

## 📤 Response

The `yurni\Http\Response` class simplifies the process of sending data back to the client with the correct headers and status codes.

### Common Response Types
- **HTML**: `return $response->html('<h1>Hello World</h1>', 200);`
- **JSON**: `return $response->json(['status' => 'success'], 201);`
- **Redirect**: `return $response->redirect('/dashboard');`

### Customizing the Response
```php
$response->setHeader('X-Custom-Header', 'Value')
         ->setContentType('application/pdf')
         ->setStatusCode(200)
         ->setBody($content);
```

---

## 🎯 Session Management

The `yurni\Http\Session` class provides a clean wrapper around PHP's native sessions, adding features like flash messages.

### Standard Operations
```php
// Using the global helper
session('user_id', 42);       // Set
$id = session('user_id');     // Get

// Check if key exists
if ($session->has('user_id')) { ... }

// Remove a specific key
$session->remove('user_id');

// Clear all session data
$session->destroy();
```

### Flash Messages
Flash messages are stored in the session and automatically deleted after they are displayed once.
```php
// Set a flash message
flash('message', 'Profile updated successfully!');

// Retrieve a flash message (it will be deleted after this)
$msg = flash('message');
```

---

## 💡 Pro Tip
In any Yurni Controller, you can access these instances directly via `$this->request` and `$this->response`.
