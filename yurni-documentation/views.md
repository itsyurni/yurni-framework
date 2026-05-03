# 🎨 Views & Template Engine

The **Yurni Framework** features a fast and intuitive template engine that combines the power of PHP with a clean, readable syntax. It supports template inheritance, blocks, and automatic data escaping to keep your views clean and secure.

---

## 🚀 Rendering Views

You can render views from your controllers or directly via the `View` class.

```php
// From a Controller
return $this->render('home', ['title' => 'Home Page']);

// Using the View class directly
return yurni\View::render('home', ['title' => 'Home Page']);
```

---

## 🛠️ Template Syntax

Yurni uses a special tag syntax to simplify common PHP tasks.

### Data Output
- **Escaped Output**: Automatically sanitizes data to prevent XSS.
  ```twig
  {{ $username }}
  ```
- **Raw Output**: Use when you need to output HTML content.
  ```twig
  {{{ $htmlContent }}}
  ```

### Control Structures
- **Conditionals**:
  ```twig
  {% if $user['active'] %}
      Welcome back, {{ $user['name'] }}!
  {% else %}
      Your account is inactive.
  {% endif %}
  ```
- **Loops**:
  ```twig
  {% foreach $posts as $post %}
      <li>{{ $post['title'] }}</li>
  {% endforeach %}
  ```

### Inheritance & Composition
- **Extending a Layout**:
  ```twig
  {% extends 'layouts/main' %}
  ```
- **Defining & Yielding Blocks**:
  ```twig
  {% block content %}
      <h1>Page Content</h1>
  {% endblock %}
  
  // In your layout:
  {% yield content %}
  ```
- **Including Partials**:
  ```twig
  {% include 'partials/navbar' %}
  ```

### Verbatim Block
If you are using frontend frameworks like Vue or React, wrap their templates in `verbatim` to prevent Yurni from parsing them.
```twig
{% verbatim %}
    <div>{{ vueVariable }}</div>
{% endverbatim %}
```

---

## ⚙️ Configuration & Performance

Manage view behavior via your `.env` file for optimal performance in production.

| Key | Description | Default |
|-----|-------------|---------|
| `views_path` | Directory for template files | `app/views` |
| `views_cache_path` | Directory for compiled cache | `storage/cache` |
| `view_cache` | Enable compiled template caching | `true` |
| `view_optimize` | Minify compiled template output | `false` |
| `view_allow_php` | Allow raw `<?php ?>` in templates | `false` |

---

## 🌐 Global Variables

You can make variables available to every single template without passing them manually in every `render()` call.

```php
// In your bootstrap/index.php
$app->setViewAttr([
    'appName' => 'Yurni Framework',
    'version' => '1.0.0'
]);
```

Then access them anywhere:
```twig
<footer>{{ appName }} v{{ version }}</footer>
```
