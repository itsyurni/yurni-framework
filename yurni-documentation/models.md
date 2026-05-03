# 🏛️ Models & ORM

The **Yurni Framework** provides a lightweight Model layer that allows you to interact with your database using an object-oriented approach. By extending `yurni\Model`, you inherit powerful CRUD capabilities and relationship management features.

---

## 🏗️ Creating a Model

Models are typically stored in the `app/Models` directory. You can customize the table name, primary key, and soft delete behavior.

```php
<?php
namespace App\Models;

use yurni\Model;

class User extends Model
{
    // If not specified, the table name is pluralized from the class name (e.g., 'users')
    protected string $table = 'users';

    // Specify the primary key (default: 'id')
    protected string $primaryKey = 'id';

    // Enable soft deletes (default: false)
    protected bool $softDeletes = true;
}
```

---

## 🛠️ Core CRUD Operations

Most Model methods can be called statically for convenience.

### Retrieval
```php
// Get all records
$users = User::all();

// Find a specific record by its primary key
$user = User::find(5);

// Find a record or throw an exception if not found
$user = User::findOrFail(5);

// Paginate records
$result = User::paginate(page: 1, perPage: 15);
// Returns: ['data' => [...], 'total' => 100, 'per_page' => 15, 'current_page' => 1]
```

### Persistence
```php
// Create a new record (returns the new ID)
$id = User::create([
    'name'  => 'John Doe',
    'email' => 'john@example.com'
]);

// Update a record by ID
User::updateById(5, ['name' => 'Jane Doe']);
```

### Deletion & Restoration
```php
// Delete a record (Soft deletes if $softDeletes is true)
User::deleteById(5);

// Restore a soft-deleted record
User::restore(5);

// Permanently delete a record
User::forceDeleteById(5);
```

---

## 🔗 Relationships

Yurni supports standard relationship patterns to link your data.

### `belongsTo`
Used for the "child" side of a relationship (e.g., a Comment belongs to a Post).
```php
$post = $comment->belongsTo(Post::class, foreignKey: 'post_id')->first();
```

### `hasMany`
Used for the "parent" side of a relationship (e.g., a User has many Posts).
```php
$posts = (new User())->hasMany(Post::class, foreignKey: 'user_id', localValue: $user['id'])->get();
```

### `hasOne`
Used for a 1-to-1 relationship (e.g., a User has one Profile).
```php
$profile = (new User())->hasOne(Profile::class, foreignKey: 'user_id', localValue: $user['id'])->first();
```

---

## 🔍 Advanced Queries

Since `Model` proxies calls to the `QueryBuilder`, you can chain complex queries directly on your models.

```php
// Static query chaining
$admins = User::where('role', 'admin')
    ->where('active', 1)
    ->orderBy('name', 'asc')
    ->limit(10)
    ->get();
```

---

## 💡 Best Practices

- **Naming Conventions**: Use singular class names (e.g., `Post`, `Comment`).
- **Validation**: Perform data validation in your Controller or Service layer before calling Model methods.
- **Soft Deletes**: Always include a `deleted_at` column (TIMESTAMP) in your database table if you enable `$softDeletes`.
