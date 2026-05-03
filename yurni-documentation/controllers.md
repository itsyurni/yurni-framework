# 🎮 Controllers

Controllers act as the brain of your application, handling incoming requests and returning appropriate responses. By extending the base `yurni\Controller` class, you gain access to a suite of powerful utility methods.

---

## 🏗️ Creating a Controller

Controllers are typically stored in the `app/Controllers` directory. Here is a basic example:

```php
<?php
namespace App\Controllers;

use yurni\Controller;
use yurni\Http\Request;
use yurni\Http\Response;

class UserController extends Controller
{
    /**
     * Display a listing of users.
     */
    public function index(): string
    {
        // Fetch users using the built-in table helper
        $users = $this->table('users')->get();
        
        // Render a template with data
        return $this->render('users.index', ['users' => $users]);
    }
}
```

---

## 🛠️ Core Controller Methods

The base `Controller` provides several helpers to streamline your workflow:

### `render(string $view, array $data = [])`
Renders a view template using the framework's template engine.
```php
return $this->render('profile.show', ['user' => $user]);
```

### `db()`
Returns the singleton instance of the database connection.
```php
$user = $this->db()->table('users')->first();
```

### `table(string $tableName)`
A shortcut to start a Query Builder instance on a specific table.
```php
$posts = $this->table('posts')->where('active', 1)->get();
```

### `transaction(callable $callback)`
Wraps database operations in a transaction to ensure data integrity.
```php
$this->transaction(function () use ($userData) {
    $this->table('users')->insert($userData);
    $this->table('logs')->insert(['action' => 'user_created']);
});
```

---

## 💉 Automatic Dependency Injection

The Yurni DI Container automatically injects common objects into your controller methods based on their type-hints. You can also mix these with dynamic route parameters.

```php
public function update(Request $request, Response $response, int $id): Response
{
    $name = $request->input('name');
    
    $this->table('users')->where('id', $id)->update(['name' => $name]);
    
    return $response->redirect('/users');
}
```

---

## 📝 Full CRUD Example

Here is how you might handle a store and show operation:

```php
public function store(Request $request): Response
{
    // Validate incoming request
    $validated = $request->validate([
        'username' => 'required|string|min:3',
        'email'    => 'required|email',
    ]);

    $this->table('users')->insert($validated);
    
    return $this->response->redirect('/users');
}

public function show($id): string
{
    $user = $this->table('users')->where('id', $id)->first();
    
    if (!$user) {
        $this->response->setStatusCode(404);
        return 'User not found';
    }
    
    return $this->render('users.show', ['user' => $user]);
}
```

---

## 💡 Pro Tips

- **API Responses**: For building APIs, use `$this->response->json([...])` instead of `render()`.
- **Logic Separation**: Keep your controllers thin. Move complex business logic to Service classes or Models.
- **Consistency**: Always return a string (for views) or a `Response` object for consistency.
