# 🗄️ Database & Query Builder

The **Yurni Framework** offers a powerful and secure database abstraction layer. Whether you need to run raw SQL or build complex queries using a fluent interface, Yurni has you covered.

---

## 🏛️ The `Db` Class

The `Db` class is the main entry point for all database interactions. It uses a singleton pattern to manage connections efficiently.

### Getting the Instance
```php
use yurni\Db;

$db = Db::getInstance();
```

### Running Raw Queries
If you need full control, you can execute raw SQL statements safely using parameter binding.
```php
// Fetch multiple results
$users = $db->select("SELECT * FROM users WHERE active = :status", ['status' => 1]);

// Execute an update or delete (returns number of affected rows)
$count = $db->affectingStatement("UPDATE users SET last_login = NOW() WHERE id = ?", [5]);

// Run any other statement
$db->statement("DROP TABLE IF EXISTS old_logs");
```

---

## 🛠️ Fluent Query Builder

The Query Builder provides a programmatic way to build SQL queries without writing raw strings. It automatically handles parameter binding to prevent SQL injection.

### Basic Selection
```php
// Select all records from a table
$users = $db->table('users')->get();

// Select specific columns
$emails = $db->table('users')->select('id', 'email')->get();
```

### Filtering with `WHERE`
```php
$posts = $db->table('posts')
    ->where('status', 'published')
    ->where('category', 'tech')
    ->whereIn('user_id', [1, 2, 3])
    ->whereBetween('views', [100, 500])
    ->get();
```

### Table Joins
```php
$posts = $db->table('posts')
    ->join('users', 'posts.user_id', '=', 'users.id')
    ->select('posts.*', 'users.name as author_name')
    ->get();
```

### Ordering, Limits, and Offsets
```php
$recent = $db->table('posts')
    ->orderBy('created_at', 'desc')
    ->limit(10)
    ->offset(20)
    ->get();
```

---

## 🛡️ Security Features

- **Prepared Statements**: All values passed to `where()`, `insert()`, or `update()` are automatically bound to prepared statements.
- **Identifier Quoting**: Table and column names are automatically quoted to prevent conflicts with SQL reserved words.
- **Sanitization**: Built-in protection against common injection vectors.

---

## ⚙️ Configuration

Database settings are managed via your `.env` file. Yurni supports MySQL, PostgreSQL, and SQLite out of the box.

| Key | Description | Example |
|-----|-------------|---------|
| `DB_DRIVER` | The database engine | `mysql`, `pgsql`, `sqlite` |
| `DB_HOST` | Database host | `127.0.0.1` |
| `DB_PORT` | Port number | `3306` |
| `DB_NAME` | Database name | `my_app_db` |
| `DB_USER` | Username | `root` |
| `DB_PASS` | Password | `secret` |
| `DB_CHARSET`| Character set | `utf8mb4` |

---

## 💡 Transactions

Ensure data consistency by wrapping multiple operations in a transaction:

```php
$db->transaction(function ($db) {
    $db->table('accounts')->where('id', 1)->decrement('balance', 100);
    $db->table('accounts')->where('id', 2)->increment('balance', 100);
});
```
